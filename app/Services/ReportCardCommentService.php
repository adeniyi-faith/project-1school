<?php

namespace App\Services;

use App\Models\ReportCardCommentBank;
use App\Models\ReportCardDesign;
use App\Models\ReportCardRemark;
use App\Models\ReportCardSigner;
use App\Models\ResultSheet;
use App\Models\TermResultSummary;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fills report card comments in bulk: from the comment bank (the comment whose average range
 * fits each student), or one same comment for everyone. Works for one class or many classes
 * at once, so a principal writes once and it lands on every class they choose.
 */
class ReportCardCommentService
{
    /**
     * Fill one signer's comments on one class sheet.
     * $sameText: one comment for every student; null means "pick from the bank".
     * Returns how many comments were written.
     */
    public function fill(ResultSheet $sheet, ReportCardSigner $signer, User $by, bool $overwrite, ?string $sameText = null): int
    {
        if ($sheet->status === 'locked' || ! $signer->canWrite($by)) {
            return 0;
        }

        $bank = $sameText === null ? $this->bank($sheet->school_id, $signer->writer_permission) : collect();
        if ($sameText === null && $bank->isEmpty()) {
            return 0;
        }

        $summaries = TermResultSummary::where('result_sheet_id', $sheet->id)->with('student')->orderBy('position')->orderBy('id')->get()
            ->filter(fn ($s) => $s->student);
        $existing = ReportCardRemark::where('result_sheet_id', $sheet->id)->where('report_card_signer_id', $signer->id)
            ->pluck('comment', 'student_id');

        $written = 0;
        DB::transaction(function () use ($summaries, $existing, $bank, $sameText, $overwrite, $sheet, $signer, $by, &$written) {
            // Count students per bank comment so classmates in the same range get different wording
            $used = [];
            foreach ($summaries as $summary) {
                if (! $overwrite && trim((string) ($existing[$summary->student_id] ?? '')) !== '') {
                    continue;
                }

                $average = $summary->average === null ? null : (float) $summary->average;
                if ($sameText !== null) {
                    $text = $sameText;
                } else {
                    $matches = $bank->filter(fn (ReportCardCommentBank $c) => $average !== null && $c->fits($average))->values();
                    if ($matches->isEmpty()) {
                        continue;
                    }
                    $key = $matches->pluck('id')->implode(',');
                    $used[$key] = ($used[$key] ?? -1) + 1;
                    $text = $matches[$used[$key] % $matches->count()]->comment;
                }

                $text = trim(ReportCardCommentBank::render($text, $summary->student, $average, $summary->position, $summary->class_size));
                if ($text === '') {
                    continue;
                }
                ReportCardRemark::updateOrCreate(
                    ['result_sheet_id' => $sheet->id, 'student_id' => $summary->student_id, 'report_card_signer_id' => $signer->id],
                    ['school_id' => $sheet->school_id, 'comment' => mb_substr($text, 0, 600), 'written_by' => $by->id],
                );
                $written++;
            }
        });

        return $written;
    }

    /**
     * The same signer (matched by title, e.g. "Principal") on many class sheets of one term.
     * Returns [classes filled, comments written, classes skipped with the reason].
     */
    public function fillMany(Collection $sheets, string $signerLabel, User $by, bool $overwrite, ?string $sameText = null): array
    {
        $classes = 0;
        $comments = 0;
        $skipped = [];
        $label = mb_strtolower(trim($signerLabel));

        foreach ($sheets as $sheet) {
            $name = $sheet->schoolClass?->name ?? 'A class';
            $design = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id)->load('signers');
            $signer = $design->signers->first(fn (ReportCardSigner $s) => $s->has_comment && mb_strtolower(trim($s->label)) === $label);

            if (! $signer) {
                $skipped[] = "{$name}: its report card has no \"{$signerLabel}\" comment box";
            } elseif ($sheet->status === 'locked') {
                $skipped[] = "{$name}: results are locked";
            } elseif (! $signer->canWrite($by)) {
                $skipped[] = "{$name}: you can't write the {$signer->label}'s comment";
            } else {
                $n = $this->fill($sheet, $signer, $by, $overwrite, $sameText);
                $comments += $n;
                $classes += $n > 0 ? 1 : 0;
            }
        }

        return [$classes, $comments, $skipped];
    }

    /** @return Collection<int, ReportCardCommentBank> */
    public function bank(int $schoolId, string $writerPermission): Collection
    {
        return ReportCardCommentBank::where('school_id', $schoolId)->where('writer_permission', $writerPermission)
            ->orderBy('min_average')->orderBy('id')->get();
    }
}
