<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HousePoint;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Sports houses: who is in each, the points table, and sharing students out fairly */
class HouseController extends Controller
{
    public function index(Request $request): Response
    {
        $houses = House::with(['patron:id,first_name,last_name', 'captain:id,first_name,last_name,admission_no'])
            ->withCount(['students' => fn ($q) => $q->where('status', 'active')])
            ->withSum('points', 'points')
            ->orderBy('name')->get();
        $user = $request->user();

        return Inertia::render('SchoolAdmin/Houses/Index', [
            'houses' => $houses->map(fn (House $h) => [
                'id' => $h->id, 'name' => $h->name, 'colour' => $h->colour, 'motto' => $h->motto, 'is_active' => $h->is_active,
                'patron_id' => $h->patron_id, 'patron' => $h->patron?->full_name,
                'captain_id' => $h->captain_id, 'captain' => $h->captain?->full_name, 'captain_no' => $h->captain?->admission_no,
                'members' => $h->students_count, 'points' => (int) $h->points_sum_points,
            ]),
            'recent' => HousePoint::with(['house:id,name,colour', 'awarder:id,name'])->latest('awarded_on')->latest('id')->limit(30)->get()
                ->map(fn (HousePoint $p) => [
                    'id' => $p->id, 'house' => $p->house?->name, 'colour' => $p->house?->colour, 'points' => $p->points,
                    'reason' => $p->reason, 'event' => $p->event, 'date' => $p->awarded_on->toDateString(), 'by' => $p->awarder?->name,
                ]),
            'unassigned' => Student::where('status', 'active')->whereNull('house_id')->count(),
            'staff' => Staff::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn (Staff $s) => ['id' => $s->id, 'name' => $s->full_name]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'can' => ['manage' => $user->can('houses.manage'), 'points' => $user->can('houses.points')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        House::create($this->validated($request) + ['school_id' => $this->getSchoolId()]);

        return back()->with('success', 'House added.');
    }

    public function update(Request $request, House $house): RedirectResponse
    {
        $house->update($this->validated($request, $house));

        return back()->with('success', 'House updated.');
    }

    /** Remove a house. Its students are left without a house; its points stay in the record. */
    public function destroy(House $house): RedirectResponse
    {
        DB::transaction(function () use ($house) {
            Student::where('house_id', $house->id)->update(['house_id' => null]);
            $house->delete();
        });

        return back()->with('success', "{$house->name} removed. Its students now have no house.");
    }

    public function award(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'house_ids' => 'required|array|min:1',
            'house_ids.*' => ['integer', Rule::exists('houses', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'points' => 'required|integer|between:-1000,1000|not_in:0',
            'reason' => 'required|string|max:200',
            'event' => 'nullable|string|max:100',
            'awarded_on' => 'required|date|before_or_equal:today',
        ]);
        foreach (array_unique($data['house_ids']) as $houseId) {
            HousePoint::create([
                'school_id' => $this->getSchoolId(), 'house_id' => $houseId, 'points' => $data['points'], 'reason' => $data['reason'],
                'event' => $data['event'] ?? null, 'awarded_on' => $data['awarded_on'], 'awarded_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', ($data['points'] > 0 ? 'Points given.' : 'Points taken off.'));
    }

    /** A line given by mistake is cancelled by its opposite, so the record shows both */
    public function undo(Request $request, HousePoint $point): RedirectResponse
    {
        HousePoint::create([
            'school_id' => $point->school_id, 'house_id' => $point->house_id, 'points' => -$point->points,
            'reason' => 'Cancelled: '.$point->reason, 'event' => $point->event, 'awarded_on' => now()->toDateString(), 'awarded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Points cancelled. Both lines stay in the record.');
    }

    /** One class's students with their house, to change by hand */
    public function members(Request $request): Response
    {
        $classId = $request->integer('class_id') ?: SchoolClass::orderBy('numeric_name')->value('id');

        return Inertia::render('SchoolAdmin/Houses/Assign', [
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'class_id' => $classId,
            'houses' => House::where('is_active', true)->orderBy('name')->get(['id', 'name', 'colour']),
            'students' => Student::with('section:id,name')->where('class_id', $classId)->where('status', 'active')->orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no', 'gender', 'section_id', 'house_id', 'photo'])
                ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no, 'gender' => $s->gender, 'section' => $s->section?->name, 'house_id' => $s->house_id]),
        ]);
    }

    public function saveMembers(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'houses' => 'required|array',
            'houses.*' => ['nullable', Rule::exists('houses', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
        ]);
        $students = Student::whereIn('id', array_keys($data['houses']))->get();
        foreach ($students as $student) {
            $student->update(['house_id' => $data['houses'][$student->id] ?: null]);
        }

        return back()->with('success', 'Houses saved for '.$students->count().' student'.($students->count() === 1 ? '' : 's').'.');
    }

    /**
     * Share students into houses so every house gets a fair mix: within each class, boys and girls are
     * dealt out in turn to the house that has fewest of them so far. Students already in a house keep it
     * unless "everyone" is chosen.
     */
    public function share(Request $request): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'class_id' => ['nullable', Rule::exists('classes', 'id')->where('school_id', $sid)],
            'everyone' => 'boolean',
        ]);
        $houses = House::where('is_active', true)->orderBy('id')->pluck('id');
        if ($houses->count() < 2) {
            return back()->withErrors(['houses' => 'Add at least two houses first.']);
        }
        $everyone = (bool) ($data['everyone'] ?? false);

        $done = DB::transaction(function () use ($data, $houses, $everyone) {
            $students = Student::where('status', 'active')
                ->when($data['class_id'] ?? null, fn ($q, $c) => $q->where('class_id', $c))
                ->orderBy('class_id')->orderBy('gender')->orderBy('first_name')->orderBy('last_name')
                ->get(['id', 'class_id', 'gender', 'house_id']);
            $total = $houses->mapWithKeys(fn ($id) => [$id => 0])->all();
            $mix = [];                                   // [class|gender][house] => count
            foreach ($students as $s) {
                if (! $everyone && $s->house_id && isset($total[$s->house_id])) {
                    $total[$s->house_id]++;
                    $mix[$s->class_id.'|'.$s->gender][$s->house_id] = ($mix[$s->class_id.'|'.$s->gender][$s->house_id] ?? 0) + 1;
                }
            }
            $done = 0;
            foreach ($students as $s) {
                if (! $everyone && $s->house_id && isset($total[$s->house_id])) {
                    continue;
                }
                $key = $s->class_id.'|'.$s->gender;
                $pick = $houses->sortBy(fn ($id) => [($mix[$key][$id] ?? 0), $total[$id], $id])->first();
                $mix[$key][$pick] = ($mix[$key][$pick] ?? 0) + 1;
                $total[$pick]++;
                if ($s->house_id !== $pick) {
                    Student::whereKey($s->id)->update(['house_id' => $pick]);
                }
                $done++;
            }

            return $done;
        });

        return back()->with('success', $done === 0 ? 'Everyone already has a house.' : "{$done} student".($done === 1 ? '' : 's').' shared into houses.');
    }

    private function validated(Request $request, ?House $house = null): array
    {
        $sid = $this->getSchoolId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('houses', 'name')->where('school_id', $sid)->whereNull('deleted_at')->ignore($house?->id)],
            'colour' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'motto' => 'nullable|string|max:150',
            'patron_id' => ['nullable', Rule::exists('staff', 'id')->where('school_id', $sid)],
            'captain_no' => 'nullable|string|max:50',          // the captain is typed by admission number
            'is_active' => 'boolean',
        ]);
        $captainNo = trim((string) ($data['captain_no'] ?? ''));
        unset($data['captain_no']);
        $data['captain_id'] = null;
        if ($captainNo !== '') {
            $data['captain_id'] = Student::where('admission_no', $captainNo)->value('id');
            if (! $data['captain_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['captain_no' => "No student has admission number {$captainNo}."]);
            }
        }

        return $data;
    }
}
