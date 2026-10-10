<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardExport;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\ReportCardExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * "Print report cards": one ZIP for chosen classes or the whole school, with one PDF per class
 * or one PDF per student. The page asks for the next step until the ZIP is ready.
 */
class ReportCardExportController extends Controller
{
    public function __construct(private ReportCardExportService $exports) {}

    public function index(Request $request): Response
    {
        $terms = Term::with('academicYear:id,name,start_date')->get()
            ->sortBy([fn ($a, $b) => strcmp((string) $b->academicYear?->start_date, (string) $a->academicYear?->start_date), ['sequence', 'asc']])
            ->values();
        $term = $terms->firstWhere('id', (int) $request->term_id) ?? $terms->firstWhere('is_current', true) ?? $terms->first();

        $sheets = collect();
        if ($term) {
            $classes = SchoolClass::orderBy('numeric_name')->orderBy('id')->pluck('name', 'id');
            $sheets = ResultSheet::where('term_id', $term->id)->whereIn('class_id', $classes->keys())->withCount('summaries')->get()
                ->sortBy(fn (ResultSheet $s) => $classes->keys()->search($s->class_id))
                ->map(fn (ResultSheet $s) => ['id' => $s->id, 'class_name' => $classes[$s->class_id], 'status' => $s->status, 'students' => $s->summaries_count])
                ->values();
        }

        return Inertia::render('SchoolAdmin/Results/PrintReportCards', [
            'terms' => $terms->map(fn (Term $t) => [
                'id' => $t->id, 'label' => trim(($t->academicYear?->name ?? '').' · '.$t->name, ' ·'), 'is_current' => $t->is_current,
            ]),
            'termId' => $term?->id,
            'sheets' => $sheets,
            'chosen' => array_map('intval', (array) $request->input('sheet', [])),
            'exports' => ReportCardExport::with(['term.academicYear:id,name', 'creator:id,name'])->latest('id')->limit(8)->get()
                ->map(fn (ReportCardExport $e) => [
                    'id' => $e->id,
                    'label' => trim(($e->term?->academicYear?->name ?? '').' · '.($e->type === 'session' ? 'Full-year cards' : ($e->term?->name ?? '').' cards'), ' ·'),
                    'layout' => $e->layout,
                    'status' => $e->status,
                    'done' => $e->next_unit,
                    'total' => $e->totalUnits(),
                    'cards' => $e->cards,
                    'error' => $e->error,
                    'by' => $e->creator?->name,
                    'created_at' => $e->created_at?->toIso8601String(),
                ]),
            'keepDays' => ReportCardExportService::KEEP_DAYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->where('school_id', $request->user()->school_id)],
            'sheet_ids' => 'required|array|min:1',
            'sheet_ids.*' => 'integer',
            'type' => ['required', Rule::in(['term', 'session'])],
            'layout' => ['required', Rule::in(['class', 'student'])],
        ], ['sheet_ids.required' => 'Choose at least one class.']);

        $term = Term::findOrFail($data['term_id']);
        $sheets = ResultSheet::where('term_id', $term->id)->whereIn('id', $data['sheet_ids'])->get();
        $classOrder = SchoolClass::orderBy('numeric_name')->orderBy('id')->pluck('id');
        $sheets = $sheets->sortBy(fn ($s) => $classOrder->search($s->class_id))->values();

        $export = $this->exports->start($term, $sheets, $data['type'], $data['layout'], $request->user());

        return redirect()->route('school.report-card-exports.index', ['term_id' => $term->id])
            ->with($export->status === 'failed' ? 'error' : 'success', $export->status === 'failed' ? $export->error : 'Making your download. Keep this page open until it is ready.');
    }

    /** One step of the work. Two tabs asking at once can't do the same step twice. */
    public function step(ReportCardExport $export): RedirectResponse
    {
        $lock = Cache::lock("report-card-export-{$export->id}", 150);
        if ($lock->get()) {
            try {
                $this->exports->step($export);
            } finally {
                $lock->release();
            }
        }

        return back();
    }

    public function download(ReportCardExport $export): BinaryFileResponse
    {
        abort_unless($export->status === 'ready' && $export->file_path && Storage::disk('local')->exists($export->file_path), 404, 'This download is no longer available. Please make it again.');
        $export->loadMissing('term.academicYear');
        $name = Str::slug(trim(($export->term?->academicYear?->name ?? '').' '.($export->type === 'session' ? 'full year' : $export->term?->name).' report cards'));

        return response()->download(Storage::disk('local')->path($export->file_path), "{$name}.zip", ['Content-Type' => 'application/zip']);
    }
}
