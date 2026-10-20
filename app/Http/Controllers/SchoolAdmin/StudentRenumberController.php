<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gives students admission numbers in the order of the school register (alphabetical), instead of the order
 * they were typed in. Shows the new numbers first; nothing changes until the admin confirms.
 */
class StudentRenumberController extends Controller
{
    private const ORDERS = [
        'surname' => 'Surname, then first name (A to Z)',
        'first_name' => 'First name, then surname (A to Z)',
        'current' => 'Keep the current order of their numbers',
    ];

    public function index(Request $request): Response
    {
        $data = $this->options($request);
        $plan = $request->boolean('preview') ? $this->plan($data) : null;

        return Inertia::render('SchoolAdmin/Students/Renumber', [
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'orders' => self::ORDERS,
            'options' => $data,
            'plan' => $plan,
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        $data = $this->options($request);
        $plan = $this->plan($data);

        if ($plan['problem']) {
            return back()->withErrors(['prefix' => $plan['problem']]);
        }

        $changes = collect($plan['rows'])->filter(fn ($r) => $r['old'] !== $r['new']);

        DB::transaction(function () use ($changes) {
            // Two steps, so a number that is about to be handed to someone else never clashes while we work
            foreach ($changes as $r) {
                Student::whereKey($r['id'])->update(['admission_no' => 'TMP-'.$r['id']]);
            }
            foreach ($changes as $r) {
                Student::whereKey($r['id'])->update(['admission_no' => $r['new']]);
                activity()->performedOn(Student::find($r['id']))
                    ->withProperties(['old' => $r['old'], 'new' => $r['new']])
                    ->log('admission number renumbered');
            }
        });

        return redirect()->route('school.students.index')
            ->with('success', $changes->count().' admission '.($changes->count() === 1 ? 'number was' : 'numbers were').' changed.');
    }

    private function options(Request $request): array
    {
        $v = $request->validate([
            'class_id' => 'nullable|exists:classes,id',
            'order' => 'nullable|in:'.implode(',', array_keys(self::ORDERS)),
            'prefix' => 'nullable|string|max:20',
            'start' => 'nullable|integer|min:1|max:999999',
            'digits' => 'nullable|integer|min:1|max:8',
        ]);

        return [
            'class_id' => $v['class_id'] ?? null,
            'order' => $v['order'] ?? 'surname',
            'prefix' => trim($v['prefix'] ?? 'ADM-'.now()->format('Y').'-'),
            'start' => (int) ($v['start'] ?? 1),
            'digits' => (int) ($v['digits'] ?? 4),
        ];
    }

    /** @return array{rows: array<int, array>, problem: ?string} */
    private function plan(array $o): array
    {
        $students = Student::where('status', 'active')
            ->when($o['class_id'], fn ($q) => $q->where('class_id', $o['class_id']))
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'class_id']);

        $key = fn (string $s) => mb_strtolower(trim($s));
        $sorted = match ($o['order']) {
            'first_name' => $students->sortBy(fn ($s) => [$key($s->first_name), $key((string) $s->last_name)]),
            'current' => $students->sortBy(fn ($s) => [strlen($s->admission_no), $s->admission_no]),
            default => $students->sortBy(fn ($s) => [$key((string) $s->last_name), $key($s->first_name)]),
        };

        $rows = [];
        $n = $o['start'];
        foreach ($sorted as $s) {
            $rows[] = [
                'id' => $s->id, 'name' => $s->full_name, 'old' => $s->admission_no,
                'new' => $o['prefix'].str_pad((string) $n++, $o['digits'], '0', STR_PAD_LEFT),
            ];
        }

        // New numbers must not collide with students outside this group (including removed ones, which keep their number)
        $taken = Student::withoutGlobalScopes()->withTrashed()->where('school_id', $this->getSchoolId())
            ->whereNotIn('id', $students->pluck('id'))->pluck('admission_no')->all();
        $clash = collect($rows)->pluck('new')->intersect($taken)->first();

        return [
            'rows' => $rows,
            'problem' => $clash ? "The number {$clash} already belongs to another student. Change the start number or the prefix." : null,
        ];
    }
}
