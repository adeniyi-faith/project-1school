<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\InquiryFollowup;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdmissionInquiryController extends Controller
{
    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $inquiries = AdmissionInquiry::with('followups.staff:id,name')
            ->where('school_id', $sid)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('student_name',  'like', "%{$request->search}%")
                   ->orWhere('guardian_name',  'like', "%{$request->search}%")
                   ->orWhere('guardian_phone', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Admissions/Inquiries', [
            'inquiries' => $inquiries,
            'filters'   => $request->only('status', 'search'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name'       => 'required|string|max:150',
            'class_interested'   => 'required|string|max:50',
            'guardian_name'      => 'required|string|max:150',
            'guardian_phone'     => 'required|string|max:25',
            'guardian_email'     => 'nullable|email|max:150',
            'status'             => 'in:new,follow_up,admitted,dropped',
            'notes'              => 'nullable|string',
            'next_followup_date' => 'nullable|date',
            'source'             => 'nullable|string|max:50',
        ]);

        AdmissionInquiry::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Inquiry created.');
    }

    public function update(Request $request, AdmissionInquiry $admissionInquiry)
    {
        if ($admissionInquiry->school_id !== $this->getSchoolId()) abort(403);

        $data = $request->validate([
            'student_name'       => 'required|string|max:150',
            'class_interested'   => 'required|string|max:50',
            'guardian_name'      => 'required|string|max:150',
            'guardian_phone'     => 'required|string|max:25',
            'guardian_email'     => 'nullable|email|max:150',
            'status'             => 'in:new,follow_up,admitted,dropped',
            'notes'              => 'nullable|string',
            'next_followup_date' => 'nullable|date',
            'source'             => 'nullable|string|max:50',
        ]);

        $admissionInquiry->update($data);

        return back()->with('success', 'Inquiry updated.');
    }

    public function destroy(AdmissionInquiry $admissionInquiry)
    {
        if ($admissionInquiry->school_id !== $this->getSchoolId()) abort(403);
        $admissionInquiry->delete();

        return back()->with('success', 'Inquiry deleted.');
    }

    public function addFollowup(Request $request, AdmissionInquiry $admissionInquiry)
    {
        if ($admissionInquiry->school_id !== $this->getSchoolId()) abort(403);

        $data = $request->validate([
            'note'      => 'required|string',
            'next_date' => 'nullable|date',
        ]);

        InquiryFollowup::create(array_merge($data, [
            'inquiry_id' => $admissionInquiry->id,
            'staff_id'   => auth()->id(),
        ]));

        if ($data['next_date'] ?? null) {
            $admissionInquiry->update(['next_followup_date' => $data['next_date'], 'status' => 'follow_up']);
        }

        return back()->with('success', 'Followup added.');
    }
}
