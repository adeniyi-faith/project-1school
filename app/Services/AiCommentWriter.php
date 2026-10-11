<?php

namespace App\Services;

use App\Models\BehaviourRating;
use App\Models\ReportCardSigner;
use App\Models\ResultSheet;
use App\Models\TermResult;
use App\Models\TermResultSummary;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Report card comments drafted by the AI from each student's results. Names never leave the school:
 * the AI sees only "Student 3, a girl", her scores and ratings, and writes {name} where the first name goes.
 * The drafts go into the comment boxes for the teacher to read, change and save.
 */
class AiCommentWriter
{
    /** Students per AI request, so a long class does not get cut off */
    private const BATCH = 20;

    public function __construct(private AiService $ai) {}

    /** @return array<int, string> student id => draft comment */
    public function draft(ResultSheet $sheet, ReportCardSigner $signer, array $studentIds, User $by): array
    {
        $summaries = TermResultSummary::where('result_sheet_id', $sheet->id)->whereIn('student_id', $studentIds)
            ->with('student:id,first_name,gender')->get()->filter(fn ($s) => $s->student)->values();
        $results = TermResult::where('result_sheet_id', $sheet->id)->whereIn('student_id', $summaries->pluck('student_id'))
            ->with('subject:id,name')->get()->groupBy('student_id');
        $ratings = BehaviourRating::where('result_sheet_id', $sheet->id)->whereIn('student_id', $summaries->pluck('student_id'))
            ->with(['behaviourTrait' => fn ($q) => $q->withTrashed()])->get()->groupBy('student_id');
        $head = $signer->writer_permission === 'results.publish';

        $drafts = [];
        foreach ($summaries->chunk(self::BATCH) as $batch) {
            $batch = $batch->values();
            $lines = $batch->map(fn (TermResultSummary $s, int $i) => $this->line($i + 1, $s, $results->get($s->student_id, collect()), $ratings->get($s->student_id, collect())));
            $answer = $this->ai->ask($sheet->school_id, $by->id, 'result_comments', 'quick', $this->system($signer->label, $head), $lines->implode("\n"), self::SCHEMA, 8192);

            foreach ($answer['comments'] ?? [] as $row) {
                $summary = $batch->get(((int) ($row['n'] ?? 0)) - 1);
                $text = trim((string) ($row['comment'] ?? ''));
                if ($summary && $text !== '') {
                    $drafts[$summary->student_id] = mb_substr(str_replace('{name}', $summary->student->first_name, $text), 0, 600);
                }
            }
        }

        return $drafts;
    }

    private function line(int $n, TermResultSummary $s, Collection $results, Collection $ratings): string
    {
        $who = match (strtolower((string) $s->student->gender)) {
            'male' => 'a boy',
            'female' => 'a girl',
            default => 'a student',
        };
        $subjects = $results->sortByDesc('total')->map(fn ($r) => ($r->subject?->name ?? 'Subject').' '.round((float) $r->total).($r->grade ? " ({$r->grade})" : ''))->implode(', ');
        $behaviour = $ratings->filter(fn ($r) => $r->behaviourTrait)->map(fn ($r) => "{$r->behaviourTrait->name} {$r->rating}/5")->implode(', ');

        return "Student {$n}, {$who}: average ".round((float) $s->average, 1).'%'
            .($s->position ? ", position {$s->position} of {$s->class_size}" : '')
            .($subjects !== '' ? ". Subjects: {$subjects}" : '')
            .($behaviour !== '' ? ". Behaviour and skills: {$behaviour}" : '').'.';
    }

    private function system(string $label, bool $head): string
    {
        return implode("\n", [
            "You write the {$label}'s comment on end-of-term report cards in a Nigerian school.",
            $head
                ? 'Write as the head of the school: short, firm and encouraging, about the overall result and conduct.'
                : 'Write as the class teacher who knows the student: warm, specific and honest.',
            'For each student write one or two sentences, at most 220 characters.',
            'Write {name} where the student\'s first name goes, at most once. Use he/him for a boy and she/her for a girl.',
            'Mention a real strength from the results, and when the average is below 50 or a subject is weak, one thing to work on.',
            'Use only the facts given. Never invent scores, subjects or events. Do not mention positions below the top three.',
            'Use plain British English. Vary the wording so students in one class do not get the same sentence.',
            'Answer for every student, using the same student number.',
        ]);
    }

    private const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'comments' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => ['n' => ['type' => 'INTEGER'], 'comment' => ['type' => 'STRING']],
                    'required' => ['n', 'comment'],
                ],
            ],
        ],
        'required' => ['comments'],
    ];
}
