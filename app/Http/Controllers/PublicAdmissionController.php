<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicAdmissionController extends Controller
{
    public function show(School $school)
    {
        abort_if($school->status !== 'active', 404);

        return Inertia::render('Public/AdmissionForm', [
            'school' => $school->only('id', 'name'),
        ]);
    }

    public function submit(Request $request, School $school)
    {
        abort_if($school->status !== 'active', 404);

        $data = $request->validate([
            'student_name'     => 'required|string|max:150',
            'class_interested' => 'required|string|max:50',
            'guardian_name'    => 'required|string|max:150',
            'guardian_phone'   => 'required|string|max:25',
            'guardian_email'   => 'nullable|email|max:150',
            'notes'            => 'nullable|string|max:1000',
        ]);

        AdmissionInquiry::create(array_merge($data, [
            'school_id' => $school->id,
            'source'    => 'online',
        ]));

        return back()->with('success', 'Your application has been submitted. We will contact you soon.');
    }
}
