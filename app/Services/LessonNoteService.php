<?php

namespace App\Services;

use App\Ai\AiStyles;
use App\Models\LessonNoteFormat;
use App\Models\SchemeOfWork;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Schemes of work and lesson notes: reading an uploaded scheme into weeks (by the AI, or from typed
 * "Week 1: topic" lines), and drafting a lesson note in the school's chosen format. The AI only drafts:
 * nothing it writes is kept until the teacher saves it. No student data is ever sent.
 */
class LessonNoteService
{
    public const MAX_WEEKS = 20;

    private const WEEKS_SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'weeks' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'week' => ['type' => 'INTEGER'],
                        'topic' => ['type' => 'STRING'],
                        'details' => ['type' => 'STRING'],
                    ],
                    'required' => ['week', 'topic'],
                ],
            ],
        ],
        'required' => ['weeks'],
    ];

    public function __construct(private AiService $ai) {}

    /**
     * What can be read out of an uploaded scheme: Word files and text files give their text here;
     * a PDF is handed to the AI as it is.
     *
     * @return array{text: ?string, file: ?array{mime: string, data: string}}
     */
    public function readUpload(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $bytes = (string) file_get_contents($file->getRealPath());

        return match ($ext) {
            'pdf' => ['text' => null, 'file' => ['mime' => 'application/pdf', 'data' => $bytes]],
            'docx' => ['text' => $this->docxText($file->getRealPath()), 'file' => null],
            default => ['text' => mb_convert_encoding($bytes, 'UTF-8', 'UTF-8'), 'file' => null],
        };
    }

    /**
     * Weeks from typed lines, without the AI. Understands "Week 1: Topic", "Wk 2 - Topic", "3. Topic"
     * and "3) Topic"; a line with no number adds to the details of the week above it.
     *
     * @return array<int, array{week: int, topic: string, details: string}>
     */
    public function parseTyped(string $text): array
    {
        $weeks = [];
        $current = null;
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(?:w(?:ee)?k\.?\s*)?(\d{1,2})\s*[:.)\-–]\s*(.+)$/iu', $line, $m)) {
                $current = (int) $m[1];
                $weeks[$current] = ['week' => $current, 'topic' => trim($m[2]), 'details' => ''];
            } elseif ($current !== null) {
                $weeks[$current]['details'] = trim($weeks[$current]['details']."\n".ltrim($line, "-•* \t"));
            }
        }

        return $this->clean(array_values($weeks));
    }

    /**
     * The AI reads a scheme (typed text, a Word file's text or a PDF) into weeks for the teacher to check.
     *
     * @return array<int, array{week: int, topic: string, details: string}>
     */
    public function readWithAi(int $schoolId, User $by, ?string $text, ?array $file, string $subject, string $class, ?string $term): array
    {
        $text = $text !== null ? mb_substr(trim($text), 0, 60000) : null;
        if (($text === null || $text === '') && ! $file) {
            throw ValidationException::withMessages(['source' => 'Upload the scheme of work or paste its text first.']);
        }

        $system = 'You read school schemes of work from Nigerian schools and list the topics week by week. '
            .'Copy the school\'s own wording for each topic; do not invent topics that are not in the document. '
            .'"details" holds the sub-topics or content listed for that week, as short lines. '
            .'Leave out weeks marked only as revision, examination or holiday unless they are all the document has. '
            .'If the document covers several terms or classes, keep only the part for the class and term asked for when you can tell which it is.';
        $prompt = "Subject: {$subject}\nClass: {$class}".($term ? "\nTerm: {$term}" : '')
            ."\n\nList this scheme of work week by week.".($text ? "\n\nScheme of work:\n".$text : '');

        $answer = $this->ai->ask($schoolId, $by->id, 'lesson_notes', 'writing', $system, $prompt, self::WEEKS_SCHEMA, 8192, $file ? [$file] : []);
        $weeks = $this->clean($answer['weeks'] ?? []);
        if ($weeks === []) {
            throw ValidationException::withMessages(['source' => 'The AI could not find any weekly topics in this document. Check that it is the scheme of work, or type the weeks in.']);
        }

        return $weeks;
    }

    /**
     * The AI reads a whole document (a scheme of work, a lesson note someone already wrote, or a syllabus) and
     * also works out which class, subject and term it is for, so the teacher does not have to pick them first.
     * $options lists the school's own names (classes, subjects, terms); the AI must choose from them or say nothing.
     * Only the document's text goes to the AI. Nothing is saved.
     *
     * @param  'scheme'|'note'|'syllabus'  $kind
     * @param  array{classes: list<string>, subjects: list<string>, terms: list<string>}  $options
     * @param  array<int, array{key: string, label: string, hint?: string}>  $sections  the lesson note headings (kind "note")
     * @return array<string, mixed>
     */
    public function readDocument(int $schoolId, User $by, string $kind, ?string $text, ?array $file, array $options, array $sections = []): array
    {
        $text = $text !== null ? mb_substr(trim($text), 0, 60000) : null;
        if (($text === null || $text === '') && ! $file) {
            throw ValidationException::withMessages(['source' => 'Upload the document or paste its text first.']);
        }

        $props = [
            'class' => ['type' => 'STRING'], 'subject' => ['type' => 'STRING'], 'title' => ['type' => 'STRING'],
        ];
        $what = match ($kind) {
            'scheme' => 'a scheme of work: the topics a teacher will teach week by week for one term',
            'syllabus' => 'a syllabus: the list of topics a class must cover in a subject for the year',
            default => 'a lesson note a teacher has already written',
        };
        if ($kind === 'scheme') {
            $props += ['term' => ['type' => 'STRING'], 'weeks' => self::WEEKS_SCHEMA['properties']['weeks']];
        } elseif ($kind === 'syllabus') {
            $props += ['academic_year' => ['type' => 'STRING'], 'topics' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']]];
        } else {
            $props += [
                'term' => ['type' => 'STRING'], 'topic' => ['type' => 'STRING'], 'week' => ['type' => 'INTEGER'], 'duration_minutes' => ['type' => 'INTEGER'],
                'sections' => ['type' => 'OBJECT', 'properties' => collect($sections)->mapWithKeys(fn ($x) => [$x['key'] => ['type' => 'STRING']])->all()],
            ];
        }

        $system = "You read school documents from Nigerian schools. The document is {$what}. "
            .'Copy the school\'s own wording; never invent content that is not in the document. '
            .'For "class", "subject" and "term" choose the closest match from the school\'s lists below and answer with the list\'s exact spelling; '
            .'answer with an empty string when the document does not say or nothing in the list fits. '
            .($kind === 'note' ? 'Sort the note\'s text under the headings given, copying it across; a heading with no matching text gets an empty string. ' : '')
            .($kind === 'scheme' ? '"details" holds the sub-topics or content for that week as short lines; skip weeks that are only revision, examination or holiday unless they are all there is. ' : '')
            .($kind === 'syllabus' ? 'List each topic once, as a short title, in the order given. ' : '');
        $prompt = "The school's classes: ".(implode('; ', $options['classes']) ?: 'none')
            ."\nThe school's subjects: ".(implode('; ', $options['subjects']) ?: 'none')
            .($kind !== 'syllabus' ? "\nThe school's terms: ".(implode('; ', $options['terms']) ?: 'none') : '')
            .($kind === 'note' ? "\n\nHeadings (key: heading):\n".collect($sections)->map(fn ($x) => "- {$x['key']}: {$x['label']}".(! empty($x['hint']) ? " ({$x['hint']})" : ''))->implode("\n") : '')
            ."\n\nRead this document.".($text ? "\n\nDocument:\n".$text : '');

        $answer = $this->ai->ask($schoolId, $by->id, 'lesson_notes', 'writing', $system, $prompt, ['type' => 'OBJECT', 'properties' => $props], 12000, $file ? [$file] : []);

        return is_array($answer) ? $answer : [];
    }

    /**
     * The id whose name best matches what a document called it: "JSS1" finds "JSS 1", "Maths" finds "Mathematics".
     *
     * @param  array<int, string>  $options  id => name
     */
    public function matchId(?string $name, array $options): ?int
    {
        $norm = fn (string $v) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower($v));
        $want = $norm((string) $name);
        if ($want === '') {
            return null;
        }
        // Short forms teachers use for subjects
        $want = ['maths' => 'mathematics', 'math' => 'mathematics', 'eng' => 'english', 'bio' => 'biology', 'chem' => 'chemistry', 'phy' => 'physics',
            'govt' => 'government', 'econs' => 'economics', 'agric' => 'agricultural', 'crs' => 'christianreligious', 'crk' => 'christianreligious',
            'irs' => 'islamicreligious', 'irk' => 'islamicreligious', 'civic' => 'civiceducation', 'lit' => 'literature'][$want] ?? $want;
        foreach ($options as $id => $label) {
            if ($norm($label) === $want) {
                return (int) $id;
            }
        }
        foreach ($options as $id => $label) {
            $have = $norm($label);
            if ($have !== '' && (str_contains($have, $want) || str_contains($want, $have))) {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * Class, subject and term named in the first lines of a typed scheme ("Subject: Mathematics"), without the AI.
     *
     * @return array{class: ?string, subject: ?string, term: ?string}
     */
    public function typedHeader(string $text): array
    {
        $found = ['class' => null, 'subject' => null, 'term' => null];
        foreach (array_slice(preg_split('/\R/', $text) ?: [], 0, 15) as $line) {
            if (preg_match('/^\s*(class|subject|term)\s*[:\-–]\s*(.+?)\s*$/iu', $line, $m)) {
                $found[mb_strtolower($m[1])] ??= $m[2];
            }
        }

        return $found;
    }

    /**
     * A lesson note drafted by the AI in the given format, for the teacher to change and save.
     *
     * @param  array<int, array{key: string, label: string, hint?: string}>  $sections
     * @return array<string, string> section key => text
     */
    public function draft(int $schoolId, User $by, array $sections, array $context): array
    {
        $class = SchoolClass::find($context['class_id']);
        $subject = Subject::find($context['subject_id']);
        $term = ! empty($context['term_id']) ? Term::find($context['term_id']) : null;
        $scheme = ! empty($context['scheme_of_work_id']) ? SchemeOfWork::find($context['scheme_of_work_id']) : null;
        $week = isset($context['week']) ? (int) $context['week'] : null;
        $before = $scheme && $week ? $scheme->week($week - 1) : null;
        $planned = $scheme && $week ? $scheme->week($week) : null;

        $topic = trim((string) ($context['topic'] ?? '')) ?: ($planned['topic'] ?? '');
        if ($topic === '') {
            throw ValidationException::withMessages(['topic' => 'Type the topic first, or pick a week from a scheme of work.']);
        }

        $headings = collect($sections)->map(fn ($s) => "- {$s['key']}: {$s['label']}".(! empty($s['hint']) ? " ({$s['hint']})" : ''))->implode("\n");
        $system = 'You are an experienced Nigerian classroom teacher who writes clear, practical lesson notes that follow the '
            .'Nigerian national curriculum (NERDC) and suit the class level. Use simple British English, Nigerian examples '
            .'(names, foods, places, money in naira) and activities that work in an ordinary classroom with a board and few materials. '
            .'Fill every heading you are given and nothing else. Use short paragraphs and numbered steps; no markdown symbols such as # or **. '
            .'Do not make up page numbers for real textbooks; name the book only when you are sure it exists.';
        $style = AiStyles::resolve('lesson_note', $context['style'] ?? null);
        if ($style['rule'] !== '') {
            $system .= ' '.$style['rule'];
        }
        $prompt = collect([
            'Class: '.($class?->name ?? ''),
            'Subject: '.($subject?->name ?? ''),
            $term ? 'Term: '.$term->name : null,
            $week ? "Week: {$week}" : null,
            "Topic: {$topic}",
            ! empty($planned['details']) && $planned['topic'] === $topic ? "Content from the scheme of work:\n".$planned['details'] : null,
            $before ? 'Last week\'s topic: '.$before['topic'] : null,
            ! empty($context['duration_minutes']) ? 'Lesson length: '.(int) $context['duration_minutes'].' minutes' : null,
            ! empty($context['instructions']) ? "The teacher's own notes for this lesson:\n".mb_substr((string) $context['instructions'], 0, 1500) : null,
            "\nWrite the lesson note under these headings (key: heading):\n".$headings,
        ])->filter()->implode("\n");

        $schema = [
            'type' => 'OBJECT',
            'properties' => collect($sections)->mapWithKeys(fn ($s) => [$s['key'] => ['type' => 'STRING']])->all(),
            'required' => collect($sections)->pluck('key')->all(),
        ];

        $answer = $this->ai->ask($schoolId, $by->id, 'lesson_notes', $style['tier'], $system, $prompt, $schema, $style['tokens']);

        $out = [];
        foreach ($sections as $s) {
            $out[$s['key']] = mb_substr(trim(str_replace(['**', '##'], '', (string) ($answer[$s['key']] ?? ''))), 0, 8000);
        }

        return $out;
    }

    /** The sections of a format, or of the school's default format */
    public function sectionsOf(?LessonNoteFormat $format): array
    {
        return $format?->sections ?: array_map(fn ($s) => ['key' => $s[0], 'label' => $s[1], 'hint' => $s[2]], array_values(LessonNoteFormat::PRESETS)[0]);
    }

    /**
     * Weeks tidied: numbered 1 to 20, one entry per week, topics and details trimmed and shortened.
     *
     * @return array<int, array{week: int, topic: string, details: string, taught?: bool}>
     */
    public function clean(array $weeks): array
    {
        $out = [];
        foreach ($weeks as $w) {
            $n = (int) ($w['week'] ?? 0);
            $topic = trim(preg_replace('/\s+/u', ' ', (string) ($w['topic'] ?? '')));
            if ($n < 1 || $n > self::MAX_WEEKS || $topic === '' || isset($out[$n])) {
                continue;
            }
            $out[$n] = ['week' => $n, 'topic' => mb_substr($topic, 0, 255), 'details' => mb_substr(trim((string) ($w['details'] ?? '')), 0, 2000)];
            // Only weeks marked as taught carry the flag
            if (filter_var($w['taught'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $out[$n]['taught'] = true;
            }
        }
        ksort($out);

        return array_values($out);
    }

    /** The words of a Word (.docx) file, one paragraph per line */
    private function docxText(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['source' => 'This Word file could not be opened. Save it again as .docx or PDF and try again.']);
        }
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        $xml = preg_replace(['/<w:tab\/>/', '/<\/w:p>/', '/<w:br[^>]*\/>/', '/<\/w:tc>/'], ["\t", "\n", "\n", "\t"], $xml);

        return trim(html_entity_decode(strip_tags((string) $xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }
}
