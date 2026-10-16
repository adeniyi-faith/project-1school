<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $structures = FeeStructure::with(['schoolClass:id,name', 'feeCategory:id,name,type'])
            ->when($request->class_id,      fn ($q) => $q->where('class_id', $request->class_id))
            ->when($request->category_id,   fn ($q) => $q->where('fee_category_id', $request->category_id))
            ->when($request->academic_year, fn ($q) => $q->where('academic_year', $request->academic_year))
            ->orderBy('academic_year', 'desc')
            ->orderByRaw('(SELECT numeric_name FROM classes WHERE classes.id = fee_structures.class_id) ASC')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Fees/Structures', [
            'structures'   => $structures,
            'classes'      => SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->get(['id', 'name']),
            'categories'   => FeeCategory::where('school_id', $sid)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']),
            'filters'      => $request->only('class_id', 'category_id', 'academic_year'),
            'currentYear'  => AcademicYear::where('school_id', $sid)->where('is_current', true)->value('name') ?? (now()->year.'/'.(now()->year + 1)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id'        => ['required', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'fee_category_id' => ['required', Rule::exists('fee_categories', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'academic_year'   => 'required|string|max:20',
            'amount'          => 'required|numeric|min:0',
            'due_date'        => 'nullable|date',
            'frequency'       => 'required|in:per_term,annual,one_time,monthly,quarterly',
            'description'     => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ]);

        FeeStructure::create(array_merge($data, [
            'school_id' => $this->getSchoolId(),
            'is_active' => $data['is_active'] ?? true,
        ]));

        return back()->with('success', 'Fee structure created.');
    }

    /**
     * Set one fee for many classes at once, each class with its own amount. Classes left blank are skipped, and a class
     * that already has this fee for the year has its amount changed instead of getting a second line.
     */
    public function storeMany(Request $request)
    {
        $data = $request->validate([
            'fee_category_id'    => ['required', Rule::exists('fee_categories', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'academic_year'      => 'required|string|max:20',
            'frequency'          => 'required|in:per_term,annual,one_time',
            'due_date'           => 'nullable|date',
            'description'        => 'nullable|string|max:255',
            'rows'               => 'required|array|min:1',
            'rows.*.class_id'    => ['required', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'rows.*.amount'      => 'nullable|numeric|min:0',
        ]);

        $made = 0;
        $changed = 0;
        foreach ($data['rows'] as $row) {
            if (($row['amount'] ?? '') === '' || $row['amount'] === null) {
                continue;
            }
            $structure = FeeStructure::firstOrNew([
                'school_id' => $this->getSchoolId(), 'class_id' => $row['class_id'],
                'fee_category_id' => $data['fee_category_id'], 'academic_year' => $data['academic_year'],
            ]);
            $structure->exists ? $changed++ : $made++;
            $structure->fill([
                'amount' => $row['amount'], 'frequency' => $data['frequency'], 'due_date' => $data['due_date'] ?? $structure->due_date,
                'description' => $data['description'] ?? $structure->description, 'is_active' => true,
            ])->save();
        }

        if ($made + $changed === 0) {
            return back()->withErrors(['rows' => 'Type an amount for at least one class.']);
        }

        return back()->with('success', "{$made} fee".($made === 1 ? '' : 's').' added'.($changed ? ", {$changed} updated" : '').'.');
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $data = $request->validate([
            'class_id'        => ['required', Rule::exists('classes', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'fee_category_id' => ['required', Rule::exists('fee_categories', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'academic_year'   => 'required|string|max:20',
            'amount'          => 'required|numeric|min:0',
            'due_date'        => 'nullable|date',
            'frequency'       => 'required|in:per_term,annual,one_time,monthly,quarterly',
            'description'     => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ]);

        $feeStructure->update($data);

        return back()->with('success', 'Fee structure updated.');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();
        return back()->with('success', 'Fee structure deleted.');
    }
}
