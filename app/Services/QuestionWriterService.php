<?php

namespace App\Services;

use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Questions drafted by the AI for a question bank: objective questions with four options, or theory
 * questions with optional (a), (b), (c) parts. Nothing is saved here: the teacher checks, changes and
 * picks the ones to keep. Sub-parts are written into the question's text and marking guide, so they work
 * everywhere a theory question already works (CBT, marking, printed papers). No student data is sent.
 */
class QuestionWriterService
{
    public const MAX_OBJECTIVE = 30;

    public const MAX_THEORY = 10;

    public const MAX_PARTS = 5;

    private const OBJECTIVE_SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'questions' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'text' => ['type' => 'STRING'],
                        'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'answer' => ['type' => 'STRING', 'description' => 'The letter of the right option: A, B, C or D'],
                        'explanation' => ['type' => 'STRING'],
                        'difficulty' => ['type' => 'STRING', 'enum' => CbtQuestion::DIFFICULTIES],
                    ],
                    'required' => ['text', 'options', 'answer'],
                ],
            ],
        ],
        'required' => ['questions'],
    ];

    private const THEORY_SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'questions' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'text' => ['type' => 'STRING', 'description' => 'The question, or the lead-in sentence before its parts'],
                        'marks' => ['type' => 'NUMBER'],
                        'guide' => ['type' => 'STRING', 'description' => 'Marking guide when the question has no parts'],
                        'parts' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'text' => ['type' => 'STRING'],
                                    'marks' => ['type' => 'NUMBER'],
                                    'guide' => ['type' => 'STRING'],
                                ],
                                'required' => ['text', 'marks', 'guide'],
                            ],
                        ],
                        'difficulty' => ['type' => 'STRING', 'enum' => CbtQuestion::DIFFICULTIES],
                    ],
                    'required' => ['text', 'marks'],
                ],
            ],
        ],
        'required' => ['questions'],
    ];

    public function __construct(private AiService $ai) {}

    /**
     * Drafts for a bank, in the same shape as a saved question (without an id).
     *
     * @param  array{type: string, count: int, topic: string, difficulty: string, level?: ?string, parts?: int, marks?: float, instructions?: ?string}  $ask
     * @return array<int, array<string, mixed>>
     */
    public function draft(CbtBank $bank, User $by, array $ask): array
    {
        $bank->loadMissing('subject.schoolClass:id,name');
        $theory = $ask['type'] === 'theory';
        $count = (int) $ask['count'];
        $parts = $theory ? max(0, min(self::MAX_PARTS, (int) ($ask['parts'] ?? 0))) : 0;
        $marks = $theory ? (float) ($ask['marks'] ?? 10) : 1.0;
        $level = trim((string) ($ask['level'] ?? '')) ?: ($bank->subject?->schoolClass?->name ?? '');
        $subject = $bank->subject?->name ?? $bank->name;
        $topic = trim($ask['topic']);

        $system = 'You are an experienced Nigerian teacher and examiner who sets questions in the style of WAEC, NECO, BECE and '
            .'school examinations, following the Nigerian national curriculum (NERDC) for the class given. Use simple British English and '
            .'Nigerian names, places, foods and money in naira where an example is needed. Every question must have one clear, correct '
            .'answer that a pupil at this level can reach from what they have been taught. Do not repeat a question or ask the same fact twice. '
            .'Write plain text only: no markdown symbols such as ** or #, and write powers and fractions plainly, for example x^2 and 3/4.';

        $what = $theory
            ? "Write {$count} theory (essay or worked-answer) question".($count === 1 ? '' : 's').", each worth {$marks} marks in total. "
                .($parts > 1
                    ? "Give each question a short lead-in and exactly {$parts} parts in \"parts\" (do not number or letter them: they are lettered (a), (b), (c) for you). "
                        .'Each part has its own marks, adding up to the question\'s total, and its own marking guide listing the points a full answer makes and the marks for each.'
                    : 'Do not split them into parts. "guide" is the marking guide: the points a full answer makes and the marks for each.')
            : "Write {$count} objective (multiple choice) question".($count === 1 ? '' : 's').' with exactly four options each, and give the letter of the right option in "answer". '
                .'The wrong options must be believable mistakes a pupil could make. Do not use "all of the above" or "none of the above". '
                .'Spread the right answers across A, B, C and D. "explanation" says in one sentence why the answer is right.';

        $prompt = collect([
            "Subject: {$subject}",
            $level !== '' ? "Class: {$level}" : null,
            "Topic: {$topic}",
            'Difficulty: '.($ask['difficulty'] === 'mixed' ? 'a mix of easy, medium and hard; give each question\'s difficulty' : $ask['difficulty']),
            ! empty($ask['instructions']) ? "The teacher's notes:\n".mb_substr((string) $ask['instructions'], 0, 1500) : null,
            "\n".$what,
        ])->filter()->implode("\n");

        $answer = $this->ai->ask($bank->school_id, $by->id, 'questions', 'writing', $system, $prompt,
            $theory ? self::THEORY_SCHEMA : self::OBJECTIVE_SCHEMA, $theory ? 12000 : 16000);

        $fixed = $ask['difficulty'] === 'mixed' ? null : $ask['difficulty'];
        $out = [];
        foreach (array_slice((array) ($answer['questions'] ?? []), 0, $count) as $q) {
            $row = $theory ? $this->theory((array) $q, $parts, $marks) : $this->objective((array) $q);
            if ($row) {
                $own = in_array($q['difficulty'] ?? null, CbtQuestion::DIFFICULTIES, true) ? $q['difficulty'] : 'medium';
                $out[] = $row + ['topic' => mb_substr($topic, 0, 120), 'difficulty' => $fixed ?? $own];
            }
        }
        if ($out === []) {
            throw ValidationException::withMessages(['ai' => 'The AI did not give any usable questions. Try again, or change the topic.']);
        }

        return $out;
    }

    /** One objective draft, or null if it is unusable (too few options, no right answer). The options are shuffled so the answers are spread out. */
    public function objective(array $q): ?array
    {
        $text = $this->tidy($q['text'] ?? '', 5000);
        $options = array_values(array_filter(array_map(fn ($o) => $this->tidy(preg_replace('/^\(?[A-Fa-f][.)]\s+/', '', (string) $o), 1000), (array) ($q['options'] ?? [])), fn ($o) => $o !== ''));
        $options = array_slice($options, 0, CbtQuestion::MAX_OPTIONS);
        $letter = strtoupper(trim((string) ($q['answer'] ?? '')));
        $right = strlen($letter) === 1 ? ord($letter) - 65 : -1;
        if ($text === '' || count($options) < 2 || $right < 0 || $right >= count($options) || count(array_unique($options)) !== count($options)) {
            return null;
        }

        $answer = $options[$right];
        // Options such as "Both A and B" point at other letters, so their order must stay
        if (! preg_grep('/\b(above|below|both|neither|[A-D] and [A-D])\b/i', $options)) {
            shuffle($options);
        }

        return [
            'type' => 'objective', 'text' => $text, 'options' => $options, 'correct_option' => array_search($answer, $options, true),
            'explanation' => $this->tidy($q['explanation'] ?? '', 2000) ?: null, 'marking_guide' => null, 'marks' => 1.0,
        ];
    }

    /** One theory draft, with its parts lettered (a), (b), (c) in the text and the guide, and marks that add up */
    public function theory(array $q, int $parts, float $marks): ?array
    {
        $text = $this->tidy($q['text'] ?? '', 3000);
        $rows = array_values(array_filter((array) ($q['parts'] ?? []), fn ($p) => is_array($p) && $this->tidy($p['text'] ?? '', 2000) !== ''));
        if ($text === '' && $rows === []) {
            return null;
        }

        if ($parts < 2 || count($rows) < 2) {
            $guide = $this->tidy($q['guide'] ?? '', 5000);
            if ($guide === '' && $rows) {
                $guide = collect($rows)->map(fn ($p) => $this->tidy($p['guide'] ?? '', 2000))->filter()->implode("\n");
            }

            return ['type' => 'theory', 'text' => $text ?: $this->tidy($rows[0]['text'], 2000), 'options' => [], 'correct_option' => null,
                'explanation' => null, 'marking_guide' => $guide ?: null, 'marks' => $marks];
        }

        $rows = array_slice($rows, 0, self::MAX_PARTS);
        $given = array_map(fn ($p) => max(0.0, (float) ($p['marks'] ?? 0)), $rows);
        $split = $this->splitMarks($given, $marks);

        $lines = $text !== '' ? [$text] : [];
        $guide = [];
        foreach ($rows as $i => $p) {
            $label = '('.chr(97 + $i).')';
            $m = $this->number($split[$i]);
            $lines[] = "{$label} ".$this->tidy($p['text'], 2000)." [{$m} mark".($split[$i] == 1 ? '' : 's').']';
            $guide[] = "{$label} ".($this->tidy($p['guide'] ?? '', 2000) ?: 'No guide given.');
        }

        return ['type' => 'theory', 'text' => mb_substr(implode("\n", $lines), 0, 5000), 'options' => [], 'correct_option' => null,
            'explanation' => null, 'marking_guide' => mb_substr(implode("\n", $guide), 0, 5000), 'marks' => (float) array_sum($split)];
    }

    /**
     * The parts' marks, made to add up to the question's total in half marks: the AI's own split when it adds up,
     * otherwise scaled to fit, with any rounding left over on the last part.
     *
     * @param  float[]  $given
     * @return float[]
     */
    public function splitMarks(array $given, float $total): array
    {
        $sum = array_sum($given);
        if ($sum <= 0) {
            $given = array_fill(0, count($given), 1.0);
            $sum = count($given);
        }
        $out = array_map(fn ($m) => max(0.5, round($m / $sum * $total * 2) / 2), $given);
        $last = count($out) - 1;
        $out[$last] = max(0.5, $out[$last] + $total - array_sum($out));

        return $out;
    }

    private function number(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1, '.', ''), '0'), '.');
    }

    private function tidy(mixed $text, int $max): string
    {
        $text = str_replace(['**', '##', "\r"], '', (string) $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);

        return mb_substr(trim(preg_replace("/\n{3,}/", "\n\n", $text)), 0, $max);
    }
}
