<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use App\Models\SchoolNotification;
use App\Models\User;
use App\Services\FamilyNotice;
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
            'student_gender'   => 'nullable|in:male,female,other',
            'student_dob'      => 'nullable|date|before:today|after:1990-01-01',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // A hidden box real people never see: a robot that fills it in is thanked and ignored
        $reference = null;
        if (! $request->filled('company_site')) {
            $inquiry = AdmissionInquiry::create(array_merge($data, [
                'school_id' => $school->id,
                'source'    => 'online',
            ]));
            $reference = $inquiry->fresh()->reference;
            app(\App\Services\WebhookService::class)->dispatch($school->id, 'inquiry.created', [
                'id' => $inquiry->id, 'reference' => $reference, 'student_name' => $inquiry->student_name, 'class_interested' => $inquiry->class_interested,
                'guardian_name' => $inquiry->guardian_name, 'guardian_phone' => $inquiry->guardian_phone, 'guardian_email' => $inquiry->guardian_email, 'source' => 'online',
            ]);

            // A copy for the parent, when they gave an email (a failed email never stops the application)
            app(FamilyNotice::class)->applicationReceived($inquiry->fresh(), $school);

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

        return back()->with('applied', 'Your application has been submitted.'.($reference ? " Your reference is {$reference}: keep it to check your application later." : '').' We will contact you soon.');
    }

    /** The family checks where their application stands: reference plus the phone number they gave */
    public function status(Request $request, School $school)
    {
        abort_if($school->status !== 'active', 404);

        $inquiry = null;
        $notFound = false;
        if ($request->filled('reference') || $request->filled('phone')) {
            $inquiry = $this->findInquiry($school, (string) $request->input('reference'), (string) $request->input('phone'));
            $notFound = $inquiry === null;
        }

        return $this->page($school, 'website.apply-status', 'apply/status', 'Check your application', 'Enter the reference we gave you and the phone number you applied with.', [
            'inquiry' => $inquiry, 'stage' => $inquiry?->familyStage($school->name), 'notFound' => $notFound,
            'reference' => (string) $request->input('reference'), 'phone' => (string) $request->input('phone'),
        ]);
    }

    /** The family says yes to the offered place */
    public function accept(Request $request, School $school)
    {
        abort_if($school->status !== 'active', 404);

        $inquiry = $this->findInquiry($school, (string) $request->input('reference'), (string) $request->input('phone'));
        abort_if($inquiry === null || $inquiry->status !== 'accepted', 404);

        if (! $inquiry->family_accepted_at) {
            $inquiry->forceFill(['family_accepted_at' => now()])->save();
            User::permission('admissions.manage')->where('school_id', $school->id)->get()->each(fn ($u) => SchoolNotification::create([
                'school_id' => $school->id, 'user_id' => $u->id, 'type' => 'admission_accepted',
                'title' => 'Offer accepted: '.$inquiry->student_name,
                'body' => "{$inquiry->guardian_name} accepted the place for {$inquiry->student_name}. Enrol them when they are ready to start.",
                'data' => ['url' => '/school/admissions/inquiries/'.$inquiry->id], 'channel' => 'in-app',
            ]));
        }

        return redirect()->route('public.admission.status', ['school' => $school, 'reference' => $inquiry->reference, 'phone' => $request->input('phone')]);
    }

    private function findInquiry(School $school, string $reference, string $phone): ?AdmissionInquiry
    {
        $reference = strtoupper(trim($reference));
        $digits = preg_replace('/\D+/', '', $phone);
        if ($reference === '' || strlen($digits) < 7) {
            return null;
        }

        $inquiry = AdmissionInquiry::withoutGlobalScopes()->where('school_id', $school->id)->where('reference', $reference)->first();
        // The phone must match on its last 7 digits, so +234 and 0 spellings both work
        $theirs = preg_replace('/\D+/', '', (string) $inquiry?->guardian_phone);

        return $inquiry && strlen($theirs) >= 7 && substr($theirs, -7) === substr($digits, -7) ? $inquiry : null;
    }

    private function page(School $school, string $view, string $slug, string $title, string $lead, array $data)
    {
        $site = SiteStarter::ensure($school);
        $pages = SchoolSitePage::withoutGlobalScopes()
            ->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')
            ->orderBy('position')->orderBy('id')->get();

        return SiteRenderer::renderPlain($school, $site, $pages, $view, $slug, $title, $lead, $data);
    }
}
