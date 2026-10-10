<?php

namespace App\Services;

use App\Http\Controllers\SchoolAdmin\ReportCardController;
use App\Models\ReportCardExport;
use App\Models\ResultSheet;
use App\Models\Term;
use App\Models\TermResultSummary;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Builds a ZIP of report card PDFs for many classes, one small step per request:
 * one class's PDF, or up to STUDENTS_PER_STEP students' own PDFs. The ZIP sits on the
 * server's own disk (`local`) while it is built and is removed after KEEP_DAYS.
 */
class ReportCardExportService
{
    public const STUDENTS_PER_STEP = 10;

    public const KEEP_DAYS = 2;

    public function __construct(private ReportCardService $cards) {}

    /** Plan the steps. Classes with no results yet are left out. */
    public function start(Term $term, Collection $sheets, string $type, string $layout, User $by): ReportCardExport
    {
        $this->clearOld($term->school_id);
        $term->loadMissing('academicYear');

        $units = [];
        foreach ($sheets as $sheet) {
            $students = $this->studentsFor($term, $sheet, $type);
            if ($students->isEmpty()) {
                continue;
            }
            if ($layout === 'class') {
                $units[] = ['sheet' => $sheet->id];
            } else {
                foreach ($students->chunk(self::STUDENTS_PER_STEP) as $chunk) {
                    $units[] = ['sheet' => $sheet->id, 'students' => $chunk->values()->all()];
                }
            }
        }

        return ReportCardExport::create([
            'school_id' => $term->school_id,
            'term_id' => $term->id,
            'type' => $type,
            'layout' => $layout,
            'units' => $units,
            'status' => $units ? 'running' : 'failed',
            'error' => $units ? null : 'None of the chosen classes has results to print yet.',
            'created_by' => $by->id,
        ]);
    }

    /** Do the next step. Safe to call again after a failed request: a step only counts once it is in the ZIP. */
    public function step(ReportCardExport $export): ReportCardExport
    {
        if ($export->status !== 'running') {
            return $export;
        }

        @set_time_limit(120);
        $unit = $export->units[$export->next_unit] ?? null;
        $path = $export->file_path ?? "exports/report-cards/{$export->school_id}/".$export->id.'-'.Str::random(12).'.zip';

        if ($unit) {
            $sheet = ResultSheet::with(['term.academicYear', 'schoolClass'])->find($unit['sheet']);
            [$files, $count] = $sheet ? $this->pdfs($export, $sheet, $unit['students'] ?? null) : [[], 0];

            if ($files) {
                Storage::disk('local')->makeDirectory(dirname($path));
                $zip = new ZipArchive();
                if ($zip->open(Storage::disk('local')->path($path), ZipArchive::CREATE) !== true) {
                    throw new RuntimeException('Could not open the ZIP file.');
                }
                foreach ($files as $name => $bytes) {
                    $zip->addFromString($name, $bytes);
                }
                $zip->close();
            }

            $export->cards += $count;
            $export->next_unit++;
            $export->file_path = ($files || $export->file_path) ? $path : null;
        }

        if ($export->next_unit >= $export->totalUnits()) {
            $ready = $export->file_path && Storage::disk('local')->exists($export->file_path);
            $export->status = $ready ? 'ready' : 'failed';
            $export->error = $ready ? null : 'None of the chosen classes has results to print yet.';
        }
        $export->save();

        return $export;
    }

    /** The PDFs for one step, keyed by their name inside the ZIP, and how many cards they hold */
    private function pdfs(ReportCardExport $export, ResultSheet $sheet, ?array $studentIds): array
    {
        $class = $this->safe($sheet->schoolClass?->name ?? 'Class '.$sheet->class_id);
        $cards = $export->type === 'session'
            ? $this->cards->sessionCards($sheet->term->academicYear, $sheet->class_id, $studentIds)
            : $this->cards->termCards($sheet, $studentIds);
        if (! $cards) {
            return [[], 0];
        }

        $view = "report-cards.{$export->type}";
        if ($export->layout === 'class') {
            $suffix = $export->type === 'session' ? ' full year' : '';

            return [["{$class}{$suffix}.pdf" => ReportCardController::pdfBytes($view, $cards)], count($cards)];
        }

        $files = [];
        foreach ($cards as $card) {
            $name = $this->safe(trim(($card['student']['admission_no'] ?? '').' '.$card['student']['name']));
            $files["{$class}/{$name}.pdf"] = ReportCardController::pdfBytes($view, [$card]);
        }

        return [$files, count($cards)];
    }

    /** Students who get a card for this class: on the term's sheet, or on any of the year's sheets */
    private function studentsFor(Term $term, ResultSheet $sheet, string $type): Collection
    {
        $sheetIds = $type === 'session'
            ? ResultSheet::where('class_id', $sheet->class_id)->whereIn('term_id', $term->academicYear?->terms()->pluck('id') ?? [$term->id])->pluck('id')
            : collect([$sheet->id]);

        return TermResultSummary::whereIn('result_sheet_id', $sheetIds)->distinct()->orderBy('student_id')->pluck('student_id');
    }

    /** A name that is safe as a file or folder name on any computer */
    private function safe(string $name): string
    {
        $name = trim(preg_replace('/[\\\\\/:*?"<>|]+/', '-', $name) ?? '', " .-");

        return $name !== '' ? Str::limit($name, 80, '') : 'Untitled';
    }

    /** Remove this school's downloads older than KEEP_DAYS */
    public function clearOld(int $schoolId): void
    {
        ReportCardExport::where('school_id', $schoolId)->where('created_at', '<', now()->subDays(self::KEEP_DAYS))->get()
            ->each(function (ReportCardExport $e) {
                if ($e->file_path) {
                    Storage::disk('local')->delete($e->file_path);
                }
                $e->delete();
            });
    }
}
