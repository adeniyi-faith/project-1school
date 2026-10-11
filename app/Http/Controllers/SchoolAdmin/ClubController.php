<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Clubs and societies, and who belongs to each */
class ClubController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('SchoolAdmin/Clubs/Index', [
            'clubs' => Club::with('patron:id,first_name,last_name')->withCount('members')->orderBy('name')->get()
                ->map(fn (Club $c) => [
                    'id' => $c->id, 'name' => $c->name, 'description' => $c->description, 'meets' => $c->meets, 'is_active' => $c->is_active,
                    'patron_id' => $c->patron_id, 'patron' => $c->patron?->full_name, 'members' => $c->members_count,
                ]),
            'staff' => $this->staff(),
            'can' => ['manage' => $request->user()->can('clubs.manage')],
        ]);
    }

    public function show(Request $request, Club $club): Response
    {
        $club->load(['patron:id,first_name,last_name', 'members.student:id,first_name,last_name,admission_no,class_id,status', 'members.student.schoolClass:id,name']);
        $classId = $request->integer('class_id') ?: null;
        $memberIds = $club->members->pluck('student_id');

        return Inertia::render('SchoolAdmin/Clubs/Show', [
            'club' => ['id' => $club->id, 'name' => $club->name, 'description' => $club->description, 'meets' => $club->meets, 'is_active' => $club->is_active, 'patron_id' => $club->patron_id, 'patron' => $club->patron?->full_name],
            'staff' => $this->staff(),
            'members' => $club->members->sortBy(fn (ClubMember $m) => [$m->role === 'Member' ? 1 : 0, $m->student?->full_name])->values()
                ->map(fn (ClubMember $m) => [
                    'id' => $m->id, 'role' => $m->role, 'joined_on' => $m->joined_on->toDateString(),
                    'student' => $m->student ? ['id' => $m->student->id, 'name' => $m->student->full_name, 'admission_no' => $m->student->admission_no, 'class' => $m->student->schoolClass?->name, 'active' => $m->student->status === 'active'] : null,
                ]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'class_id' => $classId,
            // Students of the chosen class who are not in the club yet, to tick and add
            'candidates' => $classId ? Student::where('class_id', $classId)->where('status', 'active')->whereNotIn('id', $memberIds)
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'admission_no'])
                ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->full_name, 'admission_no' => $s->admission_no]) : [],
            'can' => ['manage' => $request->user()->can('clubs.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $club = Club::create($this->validated($request) + ['school_id' => $this->getSchoolId()]);

        return redirect()->route('school.clubs.show', $club)->with('success', 'Club added. Now add its members.');
    }

    public function update(Request $request, Club $club): RedirectResponse
    {
        $club->update($this->validated($request, $club));

        return back()->with('success', 'Club updated.');
    }

    public function destroy(Club $club): RedirectResponse
    {
        $club->delete();

        return redirect()->route('school.clubs.index')->with('success', "{$club->name} removed.");
    }

    public function addMembers(Request $request, Club $club): RedirectResponse
    {
        $sid = $this->getSchoolId();
        $data = $request->validate([
            'student_ids' => 'array',
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->where('school_id', $sid)],
            'admission_nos' => 'nullable|string|max:5000',
        ]);
        $ids = collect($data['student_ids'] ?? []);
        $missing = [];
        // Admission numbers typed or pasted, separated by commas, spaces or new lines
        $numbers = collect(preg_split('/[\s,;]+/', (string) ($data['admission_nos'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))->unique();
        if ($numbers->isNotEmpty()) {
            $found = Student::whereIn('admission_no', $numbers)->pluck('id', 'admission_no');
            $ids = $ids->merge($found->values());
            $missing = $numbers->reject(fn ($n) => $found->has($n))->values()->all();
        }
        $existing = ClubMember::where('club_id', $club->id)->pluck('student_id');
        $added = 0;
        foreach ($ids->unique()->diff($existing) as $studentId) {
            ClubMember::create(['school_id' => $club->school_id, 'club_id' => $club->id, 'student_id' => $studentId, 'role' => 'Member', 'joined_on' => now()->toDateString()]);
            $added++;
        }
        $message = $added === 0 ? 'Nobody new to add.' : "{$added} member".($added === 1 ? '' : 's').' added.';
        if ($missing) {
            return back()->with('success', $message)->withErrors(['admission_nos' => 'Not found: '.implode(', ', array_slice($missing, 0, 20))]);
        }

        return back()->with('success', $message);
    }

    public function updateMember(Request $request, Club $club, ClubMember $member): RedirectResponse
    {
        abort_unless($member->club_id === $club->id, 404);
        $member->update($request->validate(['role' => 'required|string|max:40']));

        return back()->with('success', 'Role saved.');
    }

    public function removeMember(Club $club, ClubMember $member): RedirectResponse
    {
        abort_unless($member->club_id === $club->id, 404);
        $member->delete();

        return back()->with('success', 'Removed from the club.');
    }

    private function staff()
    {
        return Staff::where('status', 'active')->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
            ->map(fn (Staff $s) => ['id' => $s->id, 'name' => $s->full_name]);
    }

    private function validated(Request $request, ?Club $club = null): array
    {
        $sid = $this->getSchoolId();

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('clubs', 'name')->where('school_id', $sid)->whereNull('deleted_at')->ignore($club?->id)],
            'description' => 'nullable|string|max:500',
            'meets' => 'nullable|string|max:100',
            'patron_id' => ['nullable', Rule::exists('staff', 'id')->where('school_id', $sid)],
            'is_active' => 'boolean',
        ]);
    }
}
