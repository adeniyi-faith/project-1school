<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\School;
use App\Newsletter\BrevoProvider;
use App\Newsletter\NewsletterSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Platform owner: the email service all schools may use for their newsletter, and which schools or plans get it */
class NewsletterProviderController extends Controller
{
    public function index(): Response
    {
        $p = NewsletterSettings::platform();

        return Inertia::render('SuperAdmin/Newsletter/Index', [
            'settings' => [
                'provider' => $p['provider'], 'from_email' => (string) $p['from_email'], 'brevo_key' => NewsletterSettings::mask($p['brevo_key']), 'daily_limit' => (string) $p['daily_limit'],
                'access' => $p['access'] + ['mode' => 'all', 'plans' => [], 'schools' => []],
            ],
            'mailboxAddress' => (string) config('mail.from.address'),
            'defaults' => ['brevo' => NewsletterSettings::defaultLimit('brevo'), 'mailbox' => NewsletterSettings::defaultLimit('mailbox')],
            'plans' => Package::orderBy('price_monthly')->get(['id', 'name']),
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['mailbox', 'brevo'])],
            'from_email' => 'nullable|email|max:150',
            'brevo_key' => 'nullable|string|max:300',
            'daily_limit' => 'nullable|integer|min:1|max:100000',
            'access.mode' => ['required', Rule::in(['all', 'selected'])],
            'access.plans' => 'nullable|array', 'access.plans.*' => 'integer|exists:packages,id',
            'access.schools' => 'nullable|array', 'access.schools.*' => 'integer|exists:schools,id',
        ]);

        $current = NewsletterSettings::platform();
        $key = trim((string) ($data['brevo_key'] ?? ''));
        // a key typed here is kept; a masked or empty box keeps the one already saved
        $key = ($key === '' || str_starts_with($key, '••••')) ? (string) $current['brevo_key'] : $key;
        $from = trim((string) ($data['from_email'] ?? ''));

        if ($data['provider'] === 'brevo') {
            if ($key === '' || $from === '') {
                return back()->withErrors(['provider' => 'Brevo needs your API key and the sender address you verified in Brevo.'])->withInput();
            }
            if ($problem = (new BrevoProvider)->check(['api_key' => $key])) {
                return back()->withErrors(['brevo_key' => $problem])->withInput();
            }
        }

        NewsletterSettings::savePlatform([
            'provider' => $data['provider'], 'from_email' => $from, 'brevo_key' => $key, 'daily_limit' => $data['daily_limit'] ?? '',
            'access' => ['mode' => $data['access']['mode'], 'plans' => array_values(array_map('intval', $data['access']['plans'] ?? [])), 'schools' => array_values(array_map('intval', $data['access']['schools'] ?? []))],
        ]);

        return back()->with('success', 'Saved.');
    }
}
