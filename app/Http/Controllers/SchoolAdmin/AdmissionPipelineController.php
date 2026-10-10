<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionAssessment;
use App\Models\AdmissionInquiry;
use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One inquiry's journey to enrolment: entrance exams and interviews, the accept/decline
 * decision, and the single "Enrol" action that creates the student record.
 */
class AdmissionPipelineController extends Controller
{
    public function show(Request $request, AdmissionInquiry $admissionInquiry): Response
    {
        $inquiry = $admissionInquiry->load([
            'followups.staff:id,name', 'assessments.recorder:id,name', 'decider:id,name', 'enroller:id,name',
            'convertedStudent:id,first_name,last_name,admission_no,class_id', 'convertedStudent.schoolClass:id,name',
        ]);
        $user = $request->user();

        return Inertia::render('SchoolAdmin/Admissions/Inquiry', [
            'inquiry' => [
                'id' => $inquiry->id,
                'student_name' => $inquiry->student_name,
                'class_interested' => $inquiry->class_interested,
                'guardian_name' => $inquiry->guardian_name,
                'guardian_phone' => $inquiry->guardian_phone,
                'guardian_email' => $inquiry->guardian_email,
                'status' => $inquiry->status,
                'source' => $inquiry->source,
                'notes' => $inquiry->notes,
                'created_at' => $inquiry->created_at?->toDateString(),
                'decision' => $inquiry->decided_at ? [
                    'by' => $inquiry->decider?->name, 'at' => $inquiry->decided_at->toDateString(), 'note' => $inquiry->decision_note,
                ] : null,
                'enrolled' => $inquiry->convertedStudent ? [
                    'id' => $inquiry->convertedStudent->id,
                    'name' => $inquiry->convertedStudent->full_name,
                    'admission_no' => $inquiry->convertedStudent->admission_no,
                    'class' => $inquiry->convertedStudent->schoolClass?->name,
                    'by' => $inquiry->enroller?->name,
                    'at' => $inquiry->enrolled_at?->toDateString(),
                ] : null,
                'followups' => $inquiry->followups->map(fn ($f) => [
                    'id' => $f->id, 'note' => $f->note, 'next_date' => $f->next_date, 'by' => $f->staff?->name, 'at' => $f->created_at?->toDateString(),
                ]),
            ],
            'assessments' => $inquiry->assessments->map(fn (AdmissionAssessment $a) => [
                'id' => $a->id, 'type' => $a->type, 'scheduled_at' => $a->scheduled_at?->format('Y-m-d\TH:i'),
                'venue' => $a->venue, 'score' => $a->score, 'max_score' => $a->max_score, 'outcome' => $a->outcome,
                'remarks' => $a->remarks, 'recorded_by' => $a->recorder?->name,
            ]),
            // A parent already on file with the same phone number: enrolling links the child to them (siblings)
            'matchingGuardians' => Guardian::where('phone', $inquiry->guardian_phone)->with('students:id,guardian_id,first_name,last_name')
                ->get(['id', 'name', 'phone', 'email', 'relation'])
                ->map(fn (Guardian $g) => [
                    'id' => $g->id, 'name' => $g->name, 'phone' => $g->phone, 'relation' => $g->relation,
                    'children' => $g->students->map(fn ($s) => $s->full_name)->values(),
                ]),
            'classes' => SchoolClass::orderBy('numeric_name')->get(['id', 'name']),
            'sections' => Section::orderBy('name')->get(['id', 'class_id', 'name']),
            'can' => [
                'manage' => $user->can('admissions.manage'),
                'enrol' => $user->can('students.create'),
            ],
        ]);
    }

    public function storeAssessment(Request $request, AdmissionInquiry $admissionInquiry): RedirectResponse
    {
        $this->ensureOpen($admissionInquiry);
        $data = $this->validatedAssessment($request) + $request->validate(['type' => ['required', Rule::in(AdmissionAssessment::TYPES)]]);

        AdmissionAssessment::create($data + [
            'school_id' => $admissionInquiry->school_id,
            'inquiry_id' => $admissionInquiry->id,
            'recorded_by' => $request->user()->id,
        ]);
        if ($admissionInquiry->status === 'new') {
            $admissionInquiry->update(['status' => 'follow_up']);
        }

        return back()->with('success', ($data['type'] === 'exam' ? 'Entrance exam' : 'Interview') . ' saved.');
    }

    public function updateAssessment(Request $request, AdmissionInquiry $admissionInquiry, AdmissionAssessment $assessment): RedirectResponse
    {
        abort_unless($assessment->inquiry_id === $admissionInquiry->id, 404);
        $this->ensureOpen($admissionInquiry);

        $assessment->update($this->validatedAssessment($request) + ['recorded_by' => $request->user()->id]);

        return back()->with('success', 'Result saved.');
    }

    public function destroyAssessment(AdmissionInquiry $admissionInquiry, AdmissionAssessment $assessment): RedirectResponse
    {
        abort_unless($assessment->inquiry_id === $admissionInquiry->id, 404);
        $this->ensureOpen($admissionInquiry);
        $assessment->delete();

        return back()->with('success', 'Removed.');
    }

    /** Offer the child a place, or turn the application down. */
    public function decide(Request $request, AdmissionInquiry $admissionInquiry): RedirectResponse
    {
        $this->ensureOpen($admissionInquiry);
        $data = $request->validate([
            'decision' => 'required|in:accept,decline',
            'note' => 'nullable|string|max:500|required_if:decision,decline',
        ], ['note.required_if' => 'Say why, so the family can be told and the record is clear.']);

        $admissionInquiry->forceFill([
            'status' => $data['decision'] === 'accept' ? 'accepted' : 'dropped',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['note'] ?? null,
        ])->save();
        activity('admissions')->causedBy($request->user())->performedOn($admissionInquiry)
            ->withProperties(['decision' => $data['decision']])->log($data['decision'] === 'accept' ? 'Admission offered' : 'Admission declined');

        return back()->with('success', $data['decision'] === 'accept'
            ? "{$admissionInquiry->student_name} has been offered a place. Enrol them when they are ready to start."
            : 'Application declined.');
    }

    /** Turn an accepted inquiry into an enrolled student, in one step. */
    public function enrol(Request $request, AdmissionInquiry $admissionInquiry): RedirectResponse
    {
        $sid = $admissionInquiry->school_id;
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'nullable|date|before:today',
            'admission_date' => 'required|date',
            'previous_school' => 'nullable|string|max:200',
            'class_id' => ['required', Rule::exists('classes', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'section_id' => ['nullable', Rule::exists('sections', 'id')->where('school_id', $sid)->where('class_id', $request->input('class_id'))->whereNull('deleted_at')],
            'guardian_id' => ['nullable', Rule::exists('guardians', 'id')->where('school_id', $sid)->whereNull('deleted_at')],
            'guardian_relation' => 'required_without:guardian_id|nullable|string|max:50',
        ]);

        $student = DB::transaction(function () use ($admissionInquiry, $data, $sid, $request) {
            // Lock the inquiry so a double click cannot enrol the same child twice
            $inquiry = AdmissionInquiry::whereKey($admissionInquiry->id)->lockForUpdate()->firstOrFail();
            if ($inquiry->converted_student_id) {
                throw ValidationException::withMessages(['inquiry' => 'This child has already been enrolled.']);
            }
            if ($inquiry->status !== 'accepted') {
                throw ValidationException::withMessages(['inquiry' => 'Accept the application before enrolling the child.']);
            }

            $guardianId = $data['guardian_id'] ?? Guardian::create([
                'school_id' => $sid,
                'name' => $inquiry->guardian_name,
                'phone' => $inquiry->guardian_phone,
                'email' => $inquiry->guardian_email,
                'relation' => $data['guardian_relation'],
            ])->id;

            $student = Student::create([
                'school_id' => $sid,
                'class_id' => $data['class_id'],
                'section_id' => $data['section_id'] ?? null,
                'guardian_id' => $guardianId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'admission_date' => $data['admission_date'],
                'previous_school' => $data['previous_school'] ?? null,
                'status' => 'active',
                'category' => 'general',
            ]);

            $inquiry->forceFill([
                'status' => 'admitted',
                'converted_student_id' => $student->id,
                'enrolled_by' => $request->user()->id,
                'enrolled_at' => now(),
            ])->save();
            activity('admissions')->causedBy($request->user())->performedOn($inquiry)
                ->withProperties(['student_id' => $student->id])->log('Enrolled from admission inquiry');

            return $student;
        });

        return redirect()->route('school.students.show', $student)
            ->with('success', "{$student->full_name} is enrolled with admission number {$student->admission_no}.");
    }

    private function validatedAssessment(Request $request): array
    {
        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'venue' => 'nullable|string|max:150',
            'score' => 'nullable|numeric|min:0|max:9999',
            'max_score' => 'nullable|numeric|min:1|max:9999|required_with:score',
            'outcome' => ['required', Rule::in(AdmissionAssessment::OUTCOMES)],
            'remarks' => 'nullable|string|max:2000',
        ], ['max_score.required_with' => 'Out of how many marks?']);

        if (isset($data['score'], $data['max_score']) && $data['score'] > $data['max_score']) {
            throw ValidationException::withMessages(['score' => 'The score cannot be more than the total marks.']);
        }

        return $data;
    }

    /** Exams, interviews and decisions are closed once the child is enrolled or the application is declined. */
    private function ensureOpen(AdmissionInquiry $inquiry): void
    {
        if (in_array($inquiry->status, ['admitted', 'dropped'], true)) {
            throw ValidationException::withMessages(['inquiry' => 'This application is closed.']);
        }
    }
}
