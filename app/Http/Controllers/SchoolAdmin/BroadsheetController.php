<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardDesign;
use App\Models\ResultSheet;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\BroadsheetService;
use App\Support\SimpleXlsx;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Broadsheets for one class (term or full year) as a landscape PDF or an Excel file,
 * and the whole school as one Excel workbook with a tab per class.
 */
class BroadsheetController extends Controller
{
    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(private BroadsheetService $broadsheets) {}

    /** ?format=xlsx for Excel, otherwise PDF */
    public function term(Request $request, ResultSheet $sheet): Response
    {
        $b = $this->broadsheets->term($sheet);
        abort_if(! $b, 404, 'There are no results for this class and term yet.');
        $title = "Broadsheet {$b['class']} {$b['term']} {$b['session']}";

        if ($request->input('format') === 'xlsx') {
            $xlsx = new SimpleXlsx();
            $this->broadsheets->addSheet($xlsx, (string) $b['class'], $b, 'term');

            return $this->file($xlsx->bytes(), $title, 'xlsx');
        }

        $columns = 7 + count($b['grades']) + array_sum(array_map(fn ($s) => count($s['parts']) + 2, $b['subjects']));

        return $this->pdf('broadsheets.term', $b, $columns, $sheet, $title);
    }

    public function session(Request $request, ResultSheet $sheet): Response
    {
        $sheet->loadMissing('term.academicYear');
        abort_if(! $sheet->term?->academicYear, 404);
        $b = $this->broadsheets->session($sheet->term->academicYear, $sheet->class_id);
        abort_if(! $b, 404, 'There are no results for this class and year yet.');
        $title = "Full year broadsheet {$b['class']} {$b['session']}";

        if ($request->input('format') === 'xlsx') {
            $xlsx = new SimpleXlsx();
            $this->broadsheets->addSheet($xlsx, (string) $b['class'], $b, 'session');

            return $this->file($xlsx->bytes(), $title, 'xlsx');
        }

        $terms = count($b['term_names']);
        $columns = 6 + $terms + count($b['subjects']) * ($terms + 2);

        return $this->pdf('broadsheets.session', $b, $columns, $sheet, $title);
    }

    /** Every class with results in one Excel workbook, one tab per class */
    public function school(Request $request): Response
    {
        $data = $request->validate([
            'term_id' => ['required', 'integer', Rule::exists('terms', 'id')->where('school_id', $request->user()->school_id)],
            'type' => ['nullable', Rule::in(['term', 'session'])],
        ]);
        $term = Term::with('academicYear')->findOrFail($data['term_id']);
        $type = $data['type'] ?? 'term';

        $classes = SchoolClass::orderBy('numeric_name')->orderBy('id')->pluck('id');
        $sheets = ResultSheet::where('term_id', $term->id)->whereIn('class_id', $classes)->with('schoolClass:id,name')->get()
            ->sortBy(fn ($s) => $classes->search($s->class_id));

        $xlsx = new SimpleXlsx();
        $added = 0;
        foreach ($sheets as $sheet) {
            $b = $type === 'session' ? $this->broadsheets->session($term->academicYear, $sheet->class_id) : $this->broadsheets->term($sheet);
            if ($b) {
                $this->broadsheets->addSheet($xlsx, (string) $sheet->schoolClass?->name, $b, $type);
                $added++;
            }
        }
        abort_if(! $added, 404, 'No class has results for this term yet.');

        $title = $type === 'session'
            ? "Full year broadsheets {$term->academicYear?->name}"
            : "Broadsheets {$term->name} {$term->academicYear?->name}";

        return $this->file($xlsx->bytes(), $title, 'xlsx');
    }

    /** Landscape; A3 when there are too many columns to read on A4 */
    private function pdf(string $view, array $b, int $columns, ResultSheet $sheet, string $title): Response
    {
        $color = ReportCardDesign::forClass($sheet->school_id, $sheet->class_id)->primary_color ?: '#312e81';
        $bytes = Pdf::loadView($view, [
            'sheet' => $b,
            'color' => $color,
            'font' => $columns > 60 ? 6 : 7,
            'num' => fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'),
            'shortTerm' => fn ($n) => $this->broadsheets->shortTerm($n),
        ])->setPaper($columns > 32 ? 'a3' : 'a4', 'landscape')->output();

        return $this->file($bytes, $title, 'pdf');
    }

    private function file(string $bytes, string $title, string $ext): Response
    {
        return response($bytes, 200, [
            'Content-Type' => $ext === 'pdf' ? 'application/pdf' : self::XLSX,
            'Content-Disposition' => ($ext === 'pdf' ? 'inline' : 'attachment').'; filename="'.Str::slug($title).'.'.$ext.'"',
        ]);
    }
}
