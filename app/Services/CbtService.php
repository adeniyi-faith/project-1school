<?php

namespace App\Services;

use App\Models\AdmissionAssessment;
use App\Models\AdmissionInquiry;
use App\Models\AssessmentScheme;
use App\Models\CbtAttempt;
use App\Models\CbtCandidate;
use App\Models\CbtQuestion;
use App\Models\CbtTest;
use App\Models\ResultSheet;
use App\Models\Student;
use App\Models\SubjectScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Computer-based tests: reading pasted questions, giving each student a paper, saving answers, marking, and results */
class CbtService
{
    public function __construct(private TermResultService $results) {}

    /**
     * Read questions typed or pasted in this shape (a blank line between questions):
     *
     *   What is 2 + 2?
     *   A. 3
     *   B. 4 *
     *   C. 5
     *
     * The answer is marked with a * at the end of its line, or with a line "Answer: B".
     * Returns [questions, problems] where each question is [text, options, correct_option].
     */
    public static function parse(string $pasted): array
    {
        $blocks = preg_split('/\R\s*\R/', trim(str_replace("\r", '', $pasted)));
        $questions = [];
        $problems = [];
        foreach ($blocks as $n => $block) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), fn ($l) => $l !== ''));
            if (! $lines) {
                continue;
            }
            $label = 'Question '.($n + 1);
            $text = [];
            $options = [];
            $correct = null;
            $answerLetter = null;
            foreach ($lines as $line) {
                if (preg_match('/^answer\s*[:\-]\s*([A-Fa-f])\b/i', $line, $m)) {
                    $answerLetter = strtoupper($m[1]);
                } elseif (preg_match('/^\(?([A-Fa-f])[\.\)]\s*(.+)$/', $line, $m) && ($options || $text)) {
                    $option = $m[2];
                    if (preg_match('/\s*\*\s*$/', $option)) {
                        $option = rtrim(preg_replace('/\s*\*\s*$/', '', $option));
                        $correct = count($options);
                    }
                    $options[] = $option;
                } elseif (! $options) {
                    $text[] = preg_replace('/^(?:Q(?:uestion)?\s*)?\d+\s*[\.\):]\s*/i', '', $line);
                } else {
                    $problems[] = "{$label}: the line \"".mb_strimwidth($line, 0, 40, '…').'" comes after the options. Put it before them, or start it with a letter like E.';
                    continue 2;
                }
            }
            if ($answerLetter !== null) {
                $correct = ord($answerLetter) - ord('A');
            }
            $text = trim(implode("\n", $text));
            if ($text === '') {
                $problems[] = "{$label}: the question text is missing.";
            } elseif (count($options) < 2 || count($options) > CbtQuestion::MAX_OPTIONS) {
                $problems[] = "{$label}: give between 2 and ".CbtQuestion::MAX_OPTIONS.' options, each on its own line starting with A., B., C. ...';
            } elseif ($correct === null || $correct >= count($options)) {
                $problems[] = "{$label}: mark the right answer with a * at the end of its line, or add a line like \"Answer: B\".";
            } else {
                $questions[] = ['text' => $text, 'options' => $options, 'correct_option' => $correct];
            }
        }

        return [$questions, $problems];
    }

    /** Column headings of the Excel template, in order */
    public const TEMPLATE_HEADINGS = ['Question', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Answer (letter)', 'Topic', 'Difficulty (easy, medium, hard)', 'Marks', 'Explanation'];

    /** Read rows from the Excel template (the heading row is skipped). Returns [questions, problems]. */
    public static function parseRows(array $rows): array
    {
        $questions = [];
        $problems = [];
        foreach ($rows as $i => $row) {
            $row = array_pad(array_map(fn ($v) => trim((string) $v), $row), 11, '');
            if ($i === 0 && strcasecmp($row[0], 'Question') === 0) {
                continue;
            }
            if (implode('', $row) === '') {
                continue;
            }
            $label = 'Row '.($i + 1);
            $options = array_values(array_filter(array_slice($row, 1, 5), fn ($o) => $o !== ''));
            $letter = strtoupper(substr($row[6], 0, 1));
            $correct = $letter !== '' ? ord($letter) - ord('A') : null;
            $difficulty = strtolower($row[8]) ?: 'medium';
            $marks = $row[9] === '' ? 1.0 : (float) $row[9];

            if ($row[0] === '') {
                $problems[] = "{$label}: the question is empty.";
            } elseif (count($options) < 2) {
                $problems[] = "{$label}: give at least two options.";
            } elseif ($correct === null || $correct < 0 || $correct >= count($options)) {
                $problems[] = "{$label}: the answer should be the letter of one of the options (A, B, C ...).";
            } elseif (! in_array($difficulty, CbtQuestion::DIFFICULTIES, true)) {
                $problems[] = "{$label}: difficulty should be easy, medium or hard.";
            } elseif ($marks <= 0 || $marks > 100) {
                $problems[] = "{$label}: marks should be a number from 0.5 to 100.";
            } else {
                $questions[] = ['text' => $row[0], 'options' => $options, 'correct_option' => $correct, 'topic' => $row[7] ?: null,
                    'difficulty' => $difficulty, 'marks' => $marks, 'explanation' => $row[10] ?: null];
            }
        }

        return [$questions, $problems];
    }

    /** Problems that stop a test being opened: sections whose bank has too few questions */
    public function problems(CbtTest $test): array
    {
        $problems = [];
        if ($test->sections->isEmpty()) {
            $problems[] = 'Add at least one section of questions.';
        }
        foreach ($test->sections as $section) {
            $have = $section->pool()->count();
            if ($have < $section->question_count) {
                $problems[] = "\"{$section->title}\" needs {$section->question_count} questions but its bank only has {$have} that match.";
            }
        }
        if ($test->audience === 'students' && ! $test->class_id) {
            $problems[] = 'Choose the class that sits this test.';
        }
        if ($test->audience === 'applicants' && ! $test->candidates()->exists()) {
            $problems[] = 'Add the applicants who will sit this exam.';
        }

        return $problems;
    }

    /** The person's attempt (a student, or an applicant at an entrance exam), starting one with their own paper if they have none */
    public function start(CbtTest $test, Student|CbtCandidate $person, ?string $code = null): CbtAttempt
    {
        $column = $person instanceof CbtCandidate ? 'cbt_candidate_id' : 'student_id';
        $existing = CbtAttempt::where('cbt_test_id', $test->id)->where($column, $person->id)->first();
        if ($existing) {
            return $existing;
        }
        if (! $test->isOpen()) {
            throw ValidationException::withMessages(['test' => 'This test is not open right now.']);
        }
        if ($test->access_code && strcasecmp(trim((string) $code), $test->access_code) !== 0) {
            throw ValidationException::withMessages(['code' => 'That start code is not right. Ask the exam supervisor for it.']);
        }

        $paper = $this->drawPaper($test);
        if (! $paper) {
            throw ValidationException::withMessages(['test' => 'This test has no questions yet. Tell your teacher.']);
        }
        $deadline = now()->addMinutes($test->duration_minutes);
        if ($test->closes_at && $test->closes_at->lessThan($deadline)) {
            $deadline = $test->closes_at->copy();
        }

        try {
            return CbtAttempt::create([
                'school_id' => $test->school_id, 'cbt_test_id' => $test->id, $column => $person->id,
                'started_at' => now(), 'deadline_at' => $deadline, 'status' => 'in_progress', 'paper' => $paper, 'answers' => [], 'flags' => [],
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // Pressed Start twice at once: keep the first
            return CbtAttempt::where('cbt_test_id', $test->id)->where($column, $person->id)->firstOrFail();
        }
    }

    /** A fresh paper: questions drawn at random for each section, each with its option order */
    public function drawPaper(CbtTest $test): array
    {
        $paper = [];
        $used = [];
        foreach ($test->sections as $s => $section) {
            $pool = $section->pool()->whereNotIn('id', $used)->get(['id', 'options']);
            $picked = $pool->shuffle()->take($section->question_count);
            if (! $test->shuffle_questions) {
                $picked = $picked->sortBy('id');
            }
            foreach ($picked as $q) {
                $order = range(0, count($q->options) - 1);
                if ($test->shuffle_options) {
                    shuffle($order);
                }
                $paper[] = ['q' => $q->id, 'o' => $order, 's' => $s];
                $used[] = $q->id;
            }
        }
        if ($test->shuffle_questions) {
            // Mix within each section, keeping sections in order
            $paper = collect($paper)->groupBy('s')->map(fn ($lines) => $lines->shuffle())->flatten(1)->values()->all();
        }

        return $paper;
    }

    /** Save one answer (null clears it). Refused once the attempt is over. */
    public function answer(CbtAttempt $attempt, int $questionId, ?int $option): void
    {
        $this->settle($attempt);
        if ($attempt->isFinished()) {
            throw ValidationException::withMessages(['answer' => 'Time is up, so answers can no longer be changed.']);
        }
        $line = collect($attempt->paper)->firstWhere('q', $questionId);
        if (! $line || ($option !== null && ! in_array($option, $line['o'], true))) {
            throw ValidationException::withMessages(['answer' => 'That answer does not belong to this test.']);
        }

        DB::transaction(function () use ($attempt, $questionId, $option) {
            $fresh = CbtAttempt::lockForUpdate()->find($attempt->id);
            $answers = $fresh->answers ?? [];
            if ($option === null) {
                unset($answers[$questionId]);
            } else {
                $answers[$questionId] = $option;
            }
            $fresh->update(['answers' => $answers]);
            $attempt->answers = $answers;
        });
    }

    /** Mark or unmark a question for review */
    public function flag(CbtAttempt $attempt, int $questionId, bool $on): void
    {
        if ($attempt->isFinished() || ! collect($attempt->paper)->contains('q', $questionId)) {
            return;
        }
        $flags = collect($attempt->flags ?? [])->reject(fn ($id) => $id === $questionId);
        $attempt->update(['flags' => ($on ? $flags->push($questionId) : $flags)->values()->all()]);
    }

    /** Count a time the person left the exam page; hand in when the test's limit is reached */
    public function leftPage(CbtAttempt $attempt): CbtAttempt
    {
        if ($attempt->isFinished()) {
            return $attempt;
        }
        $attempt->increment('page_leaves');
        $limit = $attempt->test->max_page_leaves;
        if ($limit && $attempt->page_leaves >= $limit) {
            $attempt->ended_reason = 'left_page';
            $this->finish($attempt);
        }

        return $attempt;
    }

    /** More time for one person, for example after a power cut. Reopens a sitting that ran out of time. */
    public function addTime(CbtAttempt $attempt, int $minutes): void
    {
        $from = $attempt->deadline_at->isPast() ? now() : $attempt->deadline_at;
        $attempt->update([
            'deadline_at' => $from->copy()->addMinutes($minutes),
            'extra_minutes' => $attempt->extra_minutes + $minutes,
            'status' => 'in_progress', 'submitted_at' => null, 'ended_reason' => null, 'marking' => null, 'section_scores' => null, 'score' => null, 'max_score' => null,
        ]);
        $this->recordAdmission($attempt);
    }

    /** Hand in: mark every question and fix the score */
    public function finish(CbtAttempt $attempt, string $status = 'submitted'): CbtAttempt
    {
        if ($attempt->isFinished()) {
            return $attempt;
        }
        $ids = collect($attempt->paper)->pluck('q');
        $questions = CbtQuestion::withTrashed()->whereIn('id', $ids)->get()->keyBy('id');
        $answers = $attempt->answers ?? [];
        $marking = [];
        $score = 0.0;
        $max = 0.0;
        foreach ($ids as $id) {
            $q = $questions->get($id);
            if (! $q) {
                continue;
            }
            $chosen = array_key_exists($id, $answers) ? (int) $answers[$id] : null;
            $right = $chosen === $q->correct_option;
            $marking[$id] = ['chosen' => $chosen, 'correct' => $q->correct_option, 'marks' => $right ? $q->marks : 0];
            $score += $right ? $q->marks : 0;
            $max += $q->marks;
        }

        $sections = [];
        foreach ($attempt->paper as $line) {
            $m = $marking[$line['q']] ?? null;
            if ($m) {
                $s = $line['s'] ?? 0;
                $sections[$s] = ['score' => ($sections[$s]['score'] ?? 0) + $m['marks'], 'max' => ($sections[$s]['max'] ?? 0) + $questions[$line['q']]->marks];
            }
        }

        $attempt->update([
            'status' => $status, 'submitted_at' => $status === 'submitted' ? now() : $attempt->deadline_at, 'ended_reason' => $attempt->ended_reason,
            'marking' => $marking, 'section_scores' => $sections, 'score' => round($score, 2), 'max_score' => round($max, 2),
        ]);
        $this->recordAdmission($attempt);

        return $attempt;
    }

    /** Mark an attempt whose time ran out without being handed in */
    public function settle(CbtAttempt $attempt): void
    {
        if (! $attempt->isFinished() && $attempt->isOverdue()) {
            $this->finish($attempt, 'timed_out');
        }
    }

    public function settleTest(CbtTest $test): void
    {
        $test->attempts()->where('status', 'in_progress')->where('deadline_at', '<', now()->subSeconds(CbtAttempt::GRACE_SECONDS))
            ->get()->each(fn (CbtAttempt $a) => $this->finish($a, 'timed_out'));
    }

    /** The questions as the student sees them: in their order, without the answers */
    public function paper(CbtAttempt $attempt, string $images = '/cbt/images'): array
    {
        $questions = CbtQuestion::withTrashed()->whereIn('id', collect($attempt->paper)->pluck('q'))->get()->keyBy('id');

        return collect($attempt->paper)->map(function (array $line) use ($questions) {
            $q = $questions->get($line['q']);

            return $q ? [
                'id' => $q->id,
                'section' => $line['s'] ?? 0,
                'text' => $q->text,
                'image' => $q->image_path ? "{$images}/{$q->id}" : null,
                'options' => collect($line['o'])->map(fn (int $i) => ['value' => $i, 'text' => $q->options[$i] ?? ''])->values()->all(),
            ] : null;
        })->filter()->values()->all();
    }

    /** Every student (or applicant) who should sit the test, with how they did */
    public function results(CbtTest $test): Collection
    {
        $this->settleTest($test);
        if ($test->audience === 'applicants') {
            return $this->candidateResults($test);
        }
        $attempts = $test->attempts()->get()->keyBy('student_id');
        $students = Student::where('school_id', $test->school_id)
            ->where(fn ($q) => $q->where(fn ($q) => $q->where('class_id', $test->class_id)->where('status', 'active'))->orWhereIn('id', $attempts->keys()))
            ->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'admission_no']);

        return $students->map(function (Student $s) use ($attempts) {
            $a = $attempts->get($s->id);

            return [
                'key' => "s{$s->id}",
                'name' => trim($s->first_name.' '.$s->last_name),
                'admission_no' => $s->admission_no,
                'candidate_id' => null,
                'outcome' => null,
            ] + $this->attemptLine($a);
        });
    }

    /** Applicants down for an entrance exam, with their exam numbers and how they did */
    private function candidateResults(CbtTest $test): Collection
    {
        return $test->candidates()->with('inquiry:id,student_name,class_interested', 'assessment:id,outcome', 'attempt')->orderBy('exam_number')->get()
            ->map(fn (CbtCandidate $c) => [
                'key' => "c{$c->id}",
                'name' => $c->inquiry?->student_name ?? 'Removed applicant',
                'admission_no' => $c->exam_number,
                'candidate_id' => $c->id,
                'inquiry_id' => $c->inquiry_id,
                'class_interested' => $c->inquiry?->class_interested,
                'outcome' => $c->assessment?->outcome,
            ] + $this->attemptLine($c->attempt));
    }

    private function attemptLine(?CbtAttempt $a): array
    {
        $minutes = $a && $a->isFinished() ? (int) ceil($a->started_at->diffInSeconds($a->submitted_at ?? $a->deadline_at) / 60) : null;

        return [
            'status' => $a?->status ?? 'not_started',
            'score' => $a?->score,
            'max_score' => $a?->max_score,
            'percent' => $a && $a->max_score ? round($a->score / $a->max_score * 100, 1) : null,
            'answered' => $a ? count($a->answers ?? []) : 0,
            'questions' => $a ? count($a->paper) : 0,
            'minutes' => $minutes,
            'attempt_id' => $a?->id,
            'page_leaves' => $a?->page_leaves ?? 0,
        ];
    }

    /**
     * Put applicants down for an entrance exam: each gets an exam number, a PIN, and an
     * entrance-exam line on their admission record that the score fills in when they hand in.
     * Applicants already down for this exam are skipped. Returns how many were added.
     */
    public function addCandidates(CbtTest $test, array $inquiryIds, ?int $by): int
    {
        if ($test->audience !== 'applicants') {
            throw ValidationException::withMessages(['inquiry_ids' => 'Only entrance exams take applicants.']);
        }
        $have = $test->candidates()->pluck('inquiry_id')->all();
        $inquiries = AdmissionInquiry::whereIn('id', $inquiryIds)->whereNotIn('id', $have)->orderBy('student_name')->get();

        DB::transaction(function () use ($test, $inquiries, $by) {
            $next = $this->nextExamSerial($test);
            foreach ($inquiries as $inquiry) {
                $assessment = AdmissionAssessment::create([
                    'school_id' => $test->school_id, 'inquiry_id' => $inquiry->id, 'type' => 'exam', 'scheduled_at' => $test->opens_at,
                    'venue' => 'Computer-based test', 'outcome' => 'pending', 'remarks' => "Computer-based test: {$test->title}", 'recorded_by' => $by,
                ]);
                CbtCandidate::create([
                    'school_id' => $test->school_id, 'cbt_test_id' => $test->id, 'inquiry_id' => $inquiry->id, 'admission_assessment_id' => $assessment->id,
                    'exam_number' => self::examNumber($test, $next++), 'pin' => self::newPin(),
                ]);
            }
        });

        return $inquiries->count();
    }

    /** Take an applicant off an entrance exam they have not started; their pending exam line goes too */
    public function removeCandidate(CbtCandidate $candidate): void
    {
        if ($candidate->attempt()->exists()) {
            throw ValidationException::withMessages(['candidate' => 'This applicant has started the exam, so they cannot be removed. Remove their sitting first.']);
        }
        DB::transaction(function () use ($candidate) {
            $assessment = $candidate->assessment;
            $candidate->delete();
            if ($assessment && $assessment->outcome === 'pending' && $assessment->score === null) {
                $assessment->delete();
            }
        });
    }

    /** A new PIN for an applicant who lost their slip, before they start */
    public function newPinFor(CbtCandidate $candidate): void
    {
        $candidate->update(['pin' => self::newPin()]);
    }

    /** When an entrance exam closes, applicants who never started are marked absent */
    public function markAbsent(CbtTest $test): void
    {
        if ($test->audience !== 'applicants') {
            return;
        }
        $test->candidates()->whereDoesntHave('attempt')->with('assessment')->get()
            ->each(fn (CbtCandidate $c) => $c->assessment?->outcome === 'pending' ? $c->assessment->update(['outcome' => 'absent']) : null);
    }

    /** Copy an applicant's result onto their admission record: score, and passed or failed against the pass mark */
    public function recordAdmission(CbtAttempt $attempt): void
    {
        if (! $attempt->cbt_candidate_id) {
            return;
        }
        $assessment = $attempt->candidate?->assessment;
        if (! $assessment) {
            return;
        }
        if (! $attempt->isFinished()) {
            $assessment->update(['score' => null, 'max_score' => null, 'outcome' => 'pending']);

            return;
        }
        $pass = $attempt->test?->pass_mark;
        $percent = $attempt->max_score ? $attempt->score / $attempt->max_score * 100 : 0;
        $assessment->update([
            'score' => $attempt->score, 'max_score' => $attempt->max_score,
            'outcome' => $pass === null ? 'pending' : (round($percent, 2) >= $pass ? 'passed' : 'failed'),
        ]);
    }

    /** Put an applicant's exam line back to waiting, when their sitting is removed so they can start again */
    public function resetAdmission(CbtAttempt $attempt): void
    {
        $attempt->candidate?->assessment?->update(['score' => null, 'max_score' => null, 'outcome' => 'pending']);
    }

    /** Exam numbers read like "E12-004": the exam's number, then the applicant's */
    public static function examNumber(CbtTest $test, int $serial): string
    {
        return 'E'.$test->id.'-'.str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
    }

    private function nextExamSerial(CbtTest $test): int
    {
        $last = CbtCandidate::withoutGlobalScopes()->where('cbt_test_id', $test->id)->pluck('exam_number')
            ->map(fn ($n) => (int) Str::afterLast($n, '-'))->max();

        return ($last ?? 0) + 1;
    }

    /** Six digits, no letters to mix up */
    public static function newPin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** For each question in the test: how many got it and how many chose each option */
    public function questionReport(CbtTest $test): array
    {
        $marked = $test->attempts()->whereNotNull('marking')->pluck('marking');

        $ids = $marked->flatMap(fn ($m) => array_keys($m))->unique();

        return CbtQuestion::withTrashed()->whereIn('id', $ids)->orderBy('id')->get()->map(function (CbtQuestion $q) use ($marked) {
            $seen = 0;
            $right = 0;
            $picks = array_fill(0, count($q->options), 0);
            foreach ($marked as $marking) {
                $line = $marking[$q->id] ?? null;
                if (! $line) {
                    continue;
                }
                $seen++;
                $right += $line['chosen'] === $line['correct'] ? 1 : 0;
                if ($line['chosen'] !== null && isset($picks[$line['chosen']])) {
                    $picks[$line['chosen']]++;
                }
            }

            return [
                'id' => $q->id, 'text' => $q->text, 'topic' => $q->topic, 'options' => $q->options, 'correct_option' => $q->correct_option,
                'seen' => $seen, 'right' => $right, 'percent' => $seen ? round($right / $seen * 100) : null, 'picks' => $picks,
            ];
        })->sortBy(fn ($r) => $r['percent'] ?? 101)->values()->all();
    }

    /**
     * Put each finished student's score into one score part (CA1, CA2 ...) of the class's term results,
     * scaled to that part's maximum. Returns how many scores were written.
     */
    public function copyToResults(CbtTest $test, int $componentId): int
    {
        if ($test->audience !== 'students' || ! $test->term_id || ! $test->subject_id || ! $test->class_id) {
            throw ValidationException::withMessages(['component_id' => 'Set the class, subject and term on the test first.']);
        }
        $part = AssessmentScheme::forSubject($test->subject)?->components->firstWhere('id', $componentId);
        if (! $part) {
            throw ValidationException::withMessages(['component_id' => 'Choose one of this subject\'s score parts.']);
        }
        $sheet = ResultSheet::firstOrCreate(['term_id' => $test->term_id, 'class_id' => $test->class_id], ['school_id' => $test->school_id, 'status' => 'draft']);
        if (! $sheet->isEditable()) {
            throw ValidationException::withMessages(['component_id' => 'These term results have been submitted, so scores cannot be added. Ask for them to be sent back first.']);
        }

        $this->settleTest($test);
        $attempts = $test->attempts()->where('status', '!=', 'in_progress')->where('max_score', '>', 0)->get();
        DB::transaction(function () use ($attempts, $sheet, $test, $part) {
            foreach ($attempts as $a) {
                SubjectScore::updateOrCreate(
                    ['result_sheet_id' => $sheet->id, 'student_id' => $a->student_id, 'subject_id' => $test->subject_id, 'assessment_component_id' => $part->id],
                    ['school_id' => $sheet->school_id, 'score' => round($a->score / $a->max_score * $part->max_score, 1)],
                );
            }
            $this->results->compute($sheet);
        });

        return $attempts->count();
    }
}
