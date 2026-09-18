<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $s = PlatformSetting::allFor();

        return Inertia::render('SuperAdmin/Settings/Index', [
            'settings' => $s,
            'logoUrl'    => isset($s['platform_logo'])    && $s['platform_logo']    ? asset('storage/' . $s['platform_logo'])    : null,
            'faviconUrl' => isset($s['platform_favicon']) && $s['platform_favicon'] ? asset('storage/' . $s['platform_favicon']) : null,
        ]);
    }

    public function saveGeneral(Request $request)
    {
        $request->validate([
            'platform_name'    => 'required|string|max:150',
            'support_email'    => 'nullable|email|max:150',
            'support_phone'    => 'nullable|string|max:25',
            'footer_copyright' => 'nullable|string|max:300',
            'logo'             => 'nullable|image|max:2048',
            'favicon'          => 'nullable|image|max:512',
        ]);

        PlatformSetting::set('platform_name',    $request->platform_name,    'general');
        PlatformSetting::set('support_email',    $request->support_email,    'general');
        PlatformSetting::set('support_phone',    $request->support_phone,    'general');
        PlatformSetting::set('footer_copyright', $request->footer_copyright, 'general');

        if ($request->hasFile('logo')) {
            $old = PlatformSetting::get('platform_logo');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('logo')->store('platform', 'public');
            PlatformSetting::set('platform_logo', $path, 'general');
        }

        if ($request->hasFile('favicon')) {
            $old = PlatformSetting::get('platform_favicon');
            if ($old) Storage::disk('public')->delete($old);
            $path = $request->file('favicon')->store('platform', 'public');
            PlatformSetting::set('platform_favicon', $path, 'general');
        }

        return back()->with('success', 'Platform settings saved.');
    }

    public function savePayment(Request $request)
    {
        $request->validate([
            'stripe_key'      => 'nullable|string|max:200',
            'stripe_secret'   => 'nullable|string|max:200',
            'stripe_webhook'  => 'nullable|string|max:200',
            'payment_currency'=> 'nullable|string|max:10',
        ]);

        foreach (['stripe_key','stripe_secret','stripe_webhook','payment_currency'] as $key) {
            PlatformSetting::set($key, $request->input($key), 'payment');
        }

        return back()->with('success', 'Payment settings saved.');
    }

    public function saveSmtp(Request $request)
    {
        $request->validate([
            'platform_mail_host'       => 'nullable|string|max:150',
            'platform_mail_port'       => 'nullable|integer',
            'platform_mail_username'   => 'nullable|string|max:150',
            'platform_mail_password'   => 'nullable|string|max:200',
            'platform_mail_encryption' => 'nullable|in:tls,ssl,none',
            'platform_mail_from_address' => 'nullable|email|max:150',
            'platform_mail_from_name'    => 'nullable|string|max:100',
        ]);

        $keys = ['platform_mail_host','platform_mail_port','platform_mail_username',
                 'platform_mail_password','platform_mail_encryption',
                 'platform_mail_from_address','platform_mail_from_name'];

        foreach ($keys as $key) {
            PlatformSetting::set($key, $request->input($key), 'smtp');
        }

        return back()->with('success', 'Platform SMTP saved.');
    }

    public function saveLocalization(Request $request)
    {
        $request->validate([
            'default_timezone'   => 'nullable|string|max:60',
            'default_currency'   => 'nullable|string|max:10',
            'default_date_format'=> 'nullable|string|max:30',
            'default_language'   => 'nullable|string|max:10',
        ]);

        foreach (['default_timezone','default_currency','default_date_format','default_language'] as $key) {
            PlatformSetting::set($key, $request->input($key), 'localization');
        }

        return back()->with('success', 'Localization settings saved.');
    }

    public function saveMaintenance(Request $request)
    {
        $request->validate([
            'maintenance_mode'    => 'boolean',
            'maintenance_message' => 'nullable|string|max:500',
        ]);

        PlatformSetting::set('maintenance_mode',    $request->boolean('maintenance_mode') ? '1' : '0', 'maintenance');
        PlatformSetting::set('maintenance_message', $request->input('maintenance_message'), 'maintenance');

        return back()->with('success', 'Maintenance settings saved.');
    }

    public function saveStorage(Request $request)
    {
        $request->validate([
            'upload_max_mb'         => 'nullable|integer|min:1|max:500',
            'storage_per_school_gb' => 'nullable|integer|min:1|max:1000',
            'allowed_extensions'    => 'nullable|string|max:500',
        ]);

        foreach (['upload_max_mb', 'storage_per_school_gb', 'allowed_extensions'] as $key) {
            PlatformSetting::set($key, $request->input($key), 'storage');
        }

        return back()->with('success', 'Storage settings saved.');
    }

    public function saveTemplate(Request $request)
    {
        $request->validate([
            'template_key' => 'required|string|max:50',
            'subject'      => 'nullable|string|max:300',
            'body'         => 'nullable|string',
        ]);

        $key = $request->template_key;
        PlatformSetting::set("tpl_{$key}_subject", $request->subject, 'templates');
        PlatformSetting::set("tpl_{$key}_body",    $request->body,    'templates');

        return back()->with('success', 'Template saved.');
    }

    public function saveAudit(Request $request)
    {
        $request->validate([
            'audit_retention_days' => 'nullable|integer|min:0',
            'auto_purge_logs'      => 'boolean',
        ]);

        PlatformSetting::set('audit_retention_days', $request->input('audit_retention_days'), 'audit');
        PlatformSetting::set('auto_purge_logs',      $request->boolean('auto_purge_logs') ? '1' : '0', 'audit');

        return back()->with('success', 'Audit log settings saved.');
    }
}
