<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The bursar's end-of-day sheet: money received on one day, by how it was paid, to match against the bank. */
class TodayCollectionsController extends Controller
{
    private const METHODS = ['cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'pos' => 'POS', 'card' => 'Card', 'online' => 'Online', 'ussd' => 'USSD'];

    public function index(Request $request): Response
    {
        return Inertia::render('SchoolAdmin/Fees/TodayCollections', $this->data($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->data($request);
        $data['school'] = School::find($this->getSchoolId())?->name;
        $data['printedBy'] = $request->user()->name;

        return Pdf::loadView('fees.todays-collections', $data)->setPaper('a4')->stream('collections-' . $data['date'] . '.pdf');
    }

    private function data(Request $request): array
    {
        $date = $request->validate(['date' => 'nullable|date|before_or_equal:today'])['date'] ?? now()->toDateString();

        $lines = LedgerEntry::with([
            'student:id,first_name,last_name,admission_no,class_id', 'student.schoolClass:id,name',
            'invoice:id,invoice_no,fee_structure_id,period', 'invoice.feeStructure.feeCategory:id,name',
            'reversedBy:id,reverses_id', 'recorder:id,name',
        ])
            ->where('type', 'payment')->whereDate('entry_date', $date)
            ->orderBy('id')->get();

        $kept = $lines->filter(fn (LedgerEntry $e) => $e->reversedBy === null);

        $byMethod = collect(self::METHODS)->map(fn ($label, $key) => [
            'method' => $key, 'label' => $label,
            'count' => $kept->where('method', $key)->count(),
            'total' => round(-$kept->where('method', $key)->sum('amount'), 2),
        ])->filter(fn ($m) => $m['count'] > 0)->values();

        return [
            'date' => $date,
            'total' => round(-$kept->sum('amount'), 2),
            'count' => $kept->count(),
            'by_method' => $byMethod,
            'reversed_count' => $lines->count() - $kept->count(),
            'payments' => $lines->map(fn (LedgerEntry $e) => [
                'id' => $e->id,
                'receipt' => $e->reference,
                'student' => $e->student?->full_name,
                'class' => $e->student?->schoolClass?->name,
                'admission_no' => $e->student?->admission_no,
                'invoice_id' => $e->invoice_id,
                'fee' => trim(($e->invoice?->feeStructure?->feeCategory?->name ?? 'Fee') . ' · ' . ($e->invoice?->period ?? ''), ' ·'),
                'method' => $e->method,
                'method_label' => self::METHODS[$e->method] ?? ucfirst((string) $e->method),
                'amount' => round(-$e->amount, 2),
                'by' => $e->recorder?->name,
                'reversed' => $e->reversedBy !== null,
            ])->values(),
        ];
    }
}
