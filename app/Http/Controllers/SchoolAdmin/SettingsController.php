<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /** One front door for every settings page: a card per page with a sentence on what it is for. */
    public function hub()
    {
        return Inertia::render('SchoolAdmin/Settings/Hub');
    }

    public function index()
    {
        $sid    = $this->getSchoolId();
        $school = School::findOrFail($sid);
        $s      = SchoolSetting::allFor($sid);

        return Inertia::render('SchoolAdmin/Settings/Index', [
            'school'   => $school->only('id','name','email','phone','address','city','state','country','timezone','currency','language','logo'),
            'logoUrl'  => $school->logo_url,
            'logoColors' => \App\Support\LogoColors::for($school),
            'settings' => $s,
        ]);
    }

    public function saveGeneral(Request $request)
    {
        $sid    = $this->getSchoolId();
        $school = School::findOrFail($sid);

        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'nullable|email|max:150',
            'phone'    => 'nullable|string|max:25',
            'address'  => 'nullable|string|max:500',
            'city'     => 'nullable|string|max:100',
            'state'    => 'nullable|string|max:100',
            'country'  => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:60',
            'currency' => 'nullable|string|max:10',
            'language' => 'nullable|string|max:10',
        ]);

        $school->update($data);

        return back()->with('success', 'General settings saved.');
    }

    public function saveBranding(Request $request)
    {
        $sid    = $this->getSchoolId();
        $school = School::findOrFail($sid);

        $request->validate([
            'logo'        => 'nullable|image|max:10240',
            'favicon'     => 'nullable|image|max:2048',
            'tagline'     => 'nullable|string|max:200',
            'footer_text' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('logo')) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $path = \App\Support\ImageShrinker::store($request->file('logo'), "schools/{$sid}", 'public', 600);
            $school->update(['logo' => $path]);
        }

        if ($request->hasFile('favicon')) {
            $old = SchoolSetting::get($sid, 'favicon');
            if ($old) Storage::disk('public')->delete($old);
            $path = \App\Support\ImageShrinker::store($request->file('favicon'), "schools/{$sid}", 'public', 128);
            SchoolSetting::set($sid, 'favicon', $path, 'branding');
        }

        foreach (['tagline', 'footer_text', 'primary_color'] as $key) {
            if ($request->has($key)) {
                SchoolSetting::set($sid, $key, $request->input($key), 'branding');
            }
        }

        return back()->with('success', 'Branding saved.');
    }

    /**
     * The logo (or favicon) on its own small step: the page sends just the picture here, right after it is
     * chosen, and gets back the new picture and the colours found in it. If anything breaks, the answer says
     * what, so the person sees the real reason on screen instead of a blank error.
     */
    public function saveBrandImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'kind'  => 'required|in:logo,favicon',
            'image' => 'required|image|max:10240',
        ]);

        try {
            $sid    = $this->getSchoolId();
            $school = School::findOrFail($sid);
            $file   = $request->file('image');

            if ($request->input('kind') === 'logo') {
                $path = \App\Support\ImageShrinker::store($file, "schools/{$sid}", 'public', 600);
                $old  = $school->logo;
                $school->update(['logo' => $path]);
                if ($old && $old !== $path) {
                    Storage::disk('public')->delete($old);
                }
                $url = $school->fresh()->logo_url;

                return response()->json(['kind' => 'logo', 'url' => $url, 'colors' => \App\Support\LogoColors::for($school->fresh())]);
            }

            $old  = SchoolSetting::get($sid, 'favicon');
            $path = \App\Support\ImageShrinker::store($file, "schools/{$sid}", 'public', 128);
            SchoolSetting::set($sid, 'favicon', $path, 'branding');
            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }

            return response()->json(['kind' => 'favicon', 'url' => '/storage/'.$path, 'colors' => []]);
        } catch (\Throwable $e) {
            report($e);
            $what = str_replace(base_path().'/', '', class_basename($e).': '.$e->getMessage());

            return response()->json(['message' => mb_substr($what, 0, 250)], 500);
        }
    }

    public function saveAcademic(Request $request)
    {
        $sid = $this->getSchoolId();

        $request->validate([
            'academic_year'  => 'nullable|string|max:20',
            'year_start'     => 'nullable|string|max:10',
            'terms_per_year' => 'nullable|integer|min:1|max:4',
            'grading_scale'  => 'nullable|string|in:percentage,letter,gpa',
            'pass_mark'      => 'nullable|integer|min:0|max:100',
        ]);

        foreach (['academic_year','year_start','terms_per_year','grading_scale','pass_mark'] as $key) {
            SchoolSetting::set($sid, $key, $request->input($key), 'academic');
        }

        return back()->with('success', 'Academic settings saved.');
    }

    public function saveNotifications(Request $request)
    {
        $sid = $this->getSchoolId();

        $toggles = [
            'notify_attendance_email','notify_attendance_sms',
            'notify_fee_due_email',   'notify_fee_due_sms',
            'notify_exam_email',      'notify_exam_sms',
            'notify_homework_email',  'notify_homework_sms',
            'notify_announcement_email','notify_announcement_sms',
        ];

        foreach ($toggles as $key) {
            SchoolSetting::set($sid, $key, $request->boolean($key) ? '1' : '0', 'notifications');
        }

        return back()->with('success', 'Notification preferences saved.');
    }
}
