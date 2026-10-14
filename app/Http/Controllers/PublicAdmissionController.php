<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSitePage;
use App\Website\SiteRenderer;
use App\Website\SiteStarter;
use Illuminate\Http\Request;

/** The public admission form. It is drawn in the school's website look, whether or not the site is switched on */
class PublicAdmissionController extends Controller
{
    public function show(School $school)
    {
        abort_if($school->status !== 'active', 404);

        $site = SiteStarter::ensure($school);
        $pages = SchoolSitePage::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')
            ->orderBy('position')->orderBy('id')->get();
        $classes = SchoolClass::withoutGlobalScopes()->where('school_id', $school->id)->orderBy('numeric_name')->orderBy('name')->pluck('name');

        return SiteRenderer::renderPlain($school, $site, $pages, 'website.apply', 'apply', 'Apply online',
            'Tell us about your child. It takes about two minutes, and our office will contact you.', ['classes' => $classes]);
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

        // A hidden box real people never see: a robot that fills it in is thanked and ignored
        if (! $request->filled('company_site')) {
            $inquiry = AdmissionInquiry::create(array_merge($data, [
                'school_id' => $school->id,
                'source'    => 'online',
            ]));

            // Tell the staff who deal with admissions: a line in their bell (and the menu shows a number)
            \App\Models\User::permission('students.view')->where('school_id', $school->id)->get()
                ->each(fn ($u) => \App\Models\SchoolNotification::create([
                    'school_id' => $school->id,
                    'user_id'   => $u->id,
                    'type'      => 'admission_inquiry',
                    'title'     => 'New application: '.$data['student_name'],
                    'body'      => "For {$data['class_interested']}. Parent: {$data['guardian_name']}, {$data['guardian_phone']}. Open Admissions > Inquiries to follow up.",
                    'data'      => ['url' => '/school/admissions/inquiries/'.$inquiry->id],
                    'channel'   => 'in-app',
                ]));
        }

        return back()->with('applied', 'Your application has been submitted. We will contact you soon.');
    }
}
