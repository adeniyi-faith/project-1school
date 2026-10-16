<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use App\Newsletter\NewsletterService;
use App\Newsletter\NewsletterSettings;
use App\Newsletter\Providers;
use App\Support\SimpleXlsx;
use App\Website\Extensions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Website > Extensions > Newsletter: who signed up, what was sent, writing and sending, and how email is sent */
class WebsiteNewsletterController extends WebsiteBase
{
    public function __construct(private NewsletterService $newsletter) {}

    private function guardOn(): void
    {
        abort_unless(Extensions::enabled($this->getSchoolId(), 'newsletter'), 404);
    }

    public function index(Request $request): Response
    {
        $this->guardOn();
        $school = $this->school();
        $site = $this->site($school);
        $sender = NewsletterSettings::resolve($school->id);
        $s = NewsletterSettings::school($school->id);
        $user = $request->user();

        $subs = NewsletterSubscriber::query()
            ->when($request->query('status') && in_array($request->query('status'), ['pending', 'confirmed', 'unsubscribed'], true), fn ($q) => $q->where('status', $request->query('status')))
            ->when(trim((string) $request->query('q')), fn ($q, $t) => $q->where(fn ($w) => $w->where('email', 'like', '%'.str_replace(['%', '_'], '', $t).'%')->orWhere('name', 'like', '%'.str_replace(['%', '_'], '', $t).'%')))
            ->latest('id')->paginate(30)->withQueryString()
            ->through(fn ($x) => ['id' => $x->id, 'email' => $x->email, 'name' => $x->name, 'status' => $x->status, 'interests' => $x->interests ?? [], 'consented_at' => $x->consented_at?->toIso8601String(), 'confirmed_at' => $x->confirmed_at?->toIso8601String(), 'unsubscribed_at' => $x->unsubscribed_at?->toIso8601String(), 'consent_page' => $x->consent_page]);

        $counts = NewsletterSubscriber::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $platformAllowed = NewsletterSettings::platformAllowed($school->id);
        $platform = NewsletterSettings::platform();

        return Inertia::render('SchoolAdmin/Website/Newsletter', $this->shell($school, $site) + [
            'subscribers' => $subs,
            'counts' => ['confirmed' => (int) ($counts['confirmed'] ?? 0), 'pending' => (int) ($counts['pending'] ?? 0), 'unsubscribed' => (int) ($counts['unsubscribed'] ?? 0)],
            'filters' => ['status' => (string) $request->query('status', ''), 'q' => (string) $request->query('q', '')],
            'interests' => NewsletterSubscriber::INTERESTS,
            'campaigns' => NewsletterCampaign::latest('id')->limit(50)->get()->map(fn ($c) => $this->campaignRow($c))->values(),
            'sender' => $sender ? ['source' => $sender->source, 'service' => $sender->provider::label(), 'from' => $sender->fromName.' <'.$sender->fromEmail.'>', 'reply_to' => $sender->replyTo, 'limit' => $sender->dailyLimit, 'sent_today' => $this->newsletter->sentToday($school->id)] : null,
            'canSettings' => $user->can('settings.view'), 'canEditSettings' => $user->can('settings.edit'), 'canSend' => $user->can('newsletter.send'),
            'settings' => [
                'mode' => $s['mode'], 'provider' => $s['provider'], 'from_name' => (string) $s['from_name'], 'from_email' => (string) $s['from_email'], 'reply_to' => (string) $s['reply_to'],
                'daily_limit' => (string) $s['daily_limit'], 'header_text' => (string) $s['header_text'], 'footer_text' => (string) $s['footer_text'], 'colour' => (string) $s['colour'],
                // the saved boxes of each service, with secrets reduced to their last four characters
                'config' => collect(Providers::SCHOOL)->mapWithKeys(fn ($class) => [$class::key() => collect($class::fields())->mapWithKeys(function ($f) use ($s, $class) {
                    $v = (string) (($s['config'][$class::key()] ?? [])[$f['key']] ?? '');

                    return [$f['key'] => $f['secret'] ? NewsletterSettings::mask($v) : $v];
                })->all()])->all(),
            ],
            'providers' => collect(Providers::SCHOOL)->map(fn ($class) => Providers::describe($class))->values(),
            'platform' => ['allowed' => $platformAllowed, 'service' => $platformAllowed ? (Providers::make($platform['provider'])::label()) : null, 'from' => $platformAllowed ? NewsletterSettings::platformFromEmail($platform) : null, 'usable' => $platformAllowed && (bool) $sender && $sender->source === 'platform'],
            'schoolName' => $school->name,
            'consentText' => NewsletterService::CONSENT,
        ]);
    }

    private function campaignRow(NewsletterCampaign $c): array
    {
        $by = NewsletterDelivery::where('campaign_id', $c->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'id' => $c->id, 'subject' => $c->subject, 'status' => $c->status, 'audience' => $c->audience, 'total' => $c->total,
            'sent' => (int) ($by['sent'] ?? 0), 'failed' => (int) ($by['failed'] ?? 0) + (int) ($by['unsure'] ?? 0), 'skipped' => (int) ($by['skipped'] ?? 0),
            'started_at' => $c->started_at?->toIso8601String(), 'created_at' => $c->created_at?->toIso8601String(),
        ];
    }

    // ---- subscribers ----

    public function export(): \Symfony\Component\HttpFoundation\Response
    {
        $this->guardOn();
        $rows = [['Email', 'Name', 'Status', 'Interests', 'Agreed on', 'Confirmed on', 'Left on', 'Signed up from']];
        foreach (NewsletterSubscriber::orderBy('id')->get() as $x) {
            $rows[] = [$x->email, (string) $x->name, $x->status, collect($x->interests ?? [])->map(fn ($k) => NewsletterSubscriber::INTERESTS[$k] ?? $k)->implode(', '),
                (string) $x->consented_at?->format('Y-m-d H:i'), (string) $x->confirmed_at?->format('Y-m-d H:i'), (string) $x->unsubscribed_at?->format('Y-m-d H:i'), (string) $x->consent_page];
        }
        $xlsx = (new SimpleXlsx)->sheet('Subscribers', $rows);

        return response($xlsx->bytes(), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="newsletter-subscribers.xlsx"']);
    }

    /** Takes a person off for good (for someone who asks to be forgotten). It cannot be undone. */
    public function removeSubscriber(int $subscriber): RedirectResponse
    {
        $this->guardOn();
        NewsletterSubscriber::findOrFail($subscriber)->delete();

        return back()->with('success', 'Removed from the list.');
    }

    // ---- writing and sending ----

    public function storeCampaign(Request $request): RedirectResponse
    {
        $this->guardOn();
        $c = $this->newsletter->draft($this->getSchoolId(), (string) $request->input('subject', ''), (string) $request->input('body', ''), $request->user()->id);

        return redirect('/school/website/newsletter/campaigns/'.$c->id);
    }

    public function campaign(Request $request, int $campaign): Response
    {
        $this->guardOn();
        $school = $this->school();
        $c = NewsletterCampaign::findOrFail($campaign);
        $sender = NewsletterSettings::resolve($school->id);

        return Inertia::render('SchoolAdmin/Website/NewsletterCampaign', $this->shell($school, $this->site($school)) + [
            'campaign' => $c->only(['id', 'subject', 'preheader', 'body', 'audience', 'status', 'total']) + $this->campaignRow($c),
            'audiences' => ['all' => 'Everyone who confirmed'] + collect(NewsletterSubscriber::INTERESTS)->mapWithKeys(fn ($l, $k) => [$k => 'Only those interested in '.Str::lower($l)])->all(),
            'audienceCounts' => collect(['all' => $this->newsletter->audience($school->id, 'all')->count()] + collect(array_keys(NewsletterSubscriber::INTERESTS))->mapWithKeys(fn ($k) => [$k => $this->newsletter->audience($school->id, $k)->count()])->all()),
            'sender' => $sender ? ['from' => $sender->fromName.' <'.$sender->fromEmail.'>', 'limit' => $sender->dailyLimit, 'sent_today' => $this->newsletter->sentToday($school->id)] : null,
            'canSend' => $request->user()->can('newsletter.send'),
            'autostart' => $request->boolean('send') && $c->status === 'sending',
            'deliveries' => NewsletterDelivery::where('campaign_id', $c->id)->orderBy('id')->limit(500)->get(['email', 'status', 'error', 'sent_at']),
            'email' => $request->user()->email,
        ]);
    }

    public function updateCampaign(Request $request, int $campaign): RedirectResponse
    {
        $this->guardOn();
        $c = NewsletterCampaign::findOrFail($campaign);
        if ($c->status !== 'draft') {
            return back()->withErrors(['subject' => 'A newsletter that has been sent cannot be changed.']);
        }
        $data = $request->validate([
            'subject' => 'required|string|max:150', 'preheader' => 'nullable|string|max:150', 'body' => 'required|string|max:20000',
            'audience' => ['required', Rule::in(array_merge(['all'], array_keys(NewsletterSubscriber::INTERESTS)))],
        ]);
        $c->update($data);

        return back()->with('success', 'Saved.');
    }

    /** Draws the email exactly as it will look, for the preview box (not saved) */
    public function preview(Request $request): \Illuminate\Http\Response
    {
        $this->guardOn();
        $school = $this->school();
        $data = $request->validate(['subject' => 'nullable|string|max:150', 'preheader' => 'nullable|string|max:150', 'body' => 'nullable|string|max:20000']);
        $html = $this->newsletter->draw($school, [
            'subject' => $data['subject'] ?? '', 'preheader' => $data['preheader'] ?? null, 'bodyHtml' => $this->newsletter->bodyHtml((string) ($data['body'] ?? '')) ?: '<p style="color:#6b7280">Your newsletter text will show here.</p>',
            'unsubscribeUrl' => '#', 'reason' => 'You are receiving this because you signed up for the '.$school->name.' newsletter.',
        ]);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8', 'Content-Security-Policy' => "default-src 'none'; img-src * data:; style-src 'unsafe-inline'", 'X-Frame-Options' => 'SAMEORIGIN']);
    }

    public function sendTest(Request $request, int $campaign): RedirectResponse
    {
        $this->guardOn();
        $c = NewsletterCampaign::findOrFail($campaign);
        $to = $request->user()->email;
        $error = $this->newsletter->sendTest($this->school(), $c, $to);

        return $error ? back()->withErrors(['test' => $error]) : back()->with('success', 'A test copy was sent to '.$to.'. It may take a minute to arrive.');
    }

    public function send(int $campaign): RedirectResponse
    {
        $this->guardOn();
        $c = NewsletterCampaign::findOrFail($campaign);
        $error = $this->newsletter->start($this->school(), $c);

        return $error ? back()->withErrors(['send' => $error]) : redirect('/school/website/newsletter/campaigns/'.$c->id.'?send=1');
    }

    public function step(int $campaign): JsonResponse
    {
        $this->guardOn();

        return response()->json($this->newsletter->step($this->school(), NewsletterCampaign::findOrFail($campaign)));
    }

    public function retry(int $campaign): RedirectResponse
    {
        $this->guardOn();
        $n = $this->newsletter->retryFailed($this->school(), NewsletterCampaign::findOrFail($campaign));

        return redirect('/school/website/newsletter/campaigns/'.$campaign.($n ? '?send=1' : ''))->with($n ? 'success' : 'error', $n ? $n.' will be tried again.' : 'Nothing to try again.');
    }

    public function destroyCampaign(int $campaign): RedirectResponse
    {
        $this->guardOn();
        $c = NewsletterCampaign::findOrFail($campaign);
        if ($c->status !== 'draft') {
            return back()->withErrors(['subject' => 'A newsletter that has been sent is kept as a record.']);
        }
        $c->delete();

        return redirect('/school/website/newsletter?tab=newsletters')->with('success', 'Draft deleted.');
    }

    // ---- how email is sent ----

    public function saveSettings(Request $request): RedirectResponse
    {
        $this->guardOn();
        $schoolId = $this->getSchoolId();
        $classes = collect(Providers::SCHOOL)->mapWithKeys(fn ($c) => [$c::key() => $c]);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['platform', 'own'])], 'provider' => ['required', Rule::in($classes->keys()->all())],
            'from_name' => 'nullable|string|max:100', 'from_email' => 'nullable|email|max:150', 'reply_to' => 'nullable|email|max:150',
            'daily_limit' => 'nullable|integer|min:1|max:100000', 'header_text' => 'nullable|string|max:100', 'footer_text' => 'nullable|string|max:300',
            'colour' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'], 'config' => 'nullable|array',
        ], ['colour.regex' => 'Pick a colour like #1e3a8a.']);

        $saved = NewsletterSettings::school($schoolId);
        $config = $saved['config'];
        $provider = Providers::make($data['provider']);
        $class = $classes[$data['provider']];

        // keep what was saved when a secret box is left empty or still shows its hidden form
        $incoming = (array) ($data['config'][$data['provider']] ?? []);
        $merged = [];
        foreach ($class::fields() as $f) {
            $v = trim((string) ($incoming[$f['key']] ?? ''));
            $old = (string) (($config[$data['provider']] ?? [])[$f['key']] ?? '');
            $merged[$f['key']] = ($f['secret'] && ($v === '' || str_starts_with($v, '••••'))) ? $old : ($v !== '' ? $v : (string) ($f['default'] ?? ''));
        }

        if ($data['mode'] === 'own') {
            if (! NewsletterSettings::complete($class, $merged)) {
                return back()->withErrors(['config' => 'Fill in every box for '.$class::label().'.'])->withInput();
            }
            if (empty($data['from_email'])) {
                return back()->withErrors(['from_email' => 'Enter the email address newsletters are sent from. It must be one you have verified with your service.'])->withInput();
            }
            if ($problem = $provider->check($merged)) {
                return back()->withErrors(['config' => $problem])->withInput();
            }
        } elseif (! NewsletterSettings::platformAllowed($schoolId)) {
            return back()->withErrors(['mode' => 'The platform\'s email service is not part of your plan. Use your own service instead.']);
        }

        $config[$data['provider']] = $merged;
        NewsletterSettings::saveSchool($schoolId, [
            'mode' => $data['mode'], 'provider' => $data['provider'], 'config' => $config,
            'from_name' => trim((string) ($data['from_name'] ?? '')), 'from_email' => trim((string) ($data['from_email'] ?? '')), 'reply_to' => trim((string) ($data['reply_to'] ?? '')),
            'daily_limit' => $data['daily_limit'] ?? '', 'header_text' => trim((string) ($data['header_text'] ?? '')), 'footer_text' => trim((string) ($data['footer_text'] ?? '')), 'colour' => (string) ($data['colour'] ?? ''),
        ]);

        return back()->with('success', $data['mode'] === 'own' ? 'Saved. We signed in to '.$class::label().' and it worked.' : 'Saved.');
    }
}
