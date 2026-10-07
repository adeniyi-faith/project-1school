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
    public function index()
    {
        $sid    = $this->getSchoolId();
        $school = School::findOrFail($sid);
        $s      = SchoolSetting::allFor($sid);

        return Inertia::render('SchoolAdmin/Settings/Index', [
            'school'   => $school->only('id','name','email','phone','address','city','state','country','timezone','currency','language','logo'),
            'logoUrl'  => $school->logo_url,
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
            'logo'        => 'nullable|image|max:2048',
            'favicon'     => 'nullable|image|max:512',
            'tagline'     => 'nullable|string|max:200',
            'footer_text' => 'nullable|string|max:500',
            'primary_color' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('logo')) {
            if ($school->logo) {
                Storage::disk('public')->delete($school->logo);
            }
            $path = $request->file('logo')->store("schools/{$sid}", 'public');
            $school->update(['logo' => $path]);
        }

        if ($request->hasFile('favicon')) {
            $old = SchoolSetting::get($sid, 'favicon');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('favicon')->store("schools/{$sid}", 'public');
            SchoolSetting::set($sid, 'favicon', $path, 'branding');
        }

        foreach (['tagline', 'footer_text', 'primary_color'] as $key) {
            if ($request->has($key)) {
                SchoolSetting::set($sid, $key, $request->input($key), 'branding');
            }
        }

        return back()->with('success', 'Branding saved.');
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
