<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Newsletter\NewsletterService;
use App\Newsletter\NewsletterSettings;
use App\Website\Extensions;
use App\Website\SiteRenderer;
use App\Website\Visitor;
use Illuminate\Http\Request;

/** The newsletter as visitors meet it: signing up, confirming from the email, and unsubscribing */
class NewsletterPublicController extends Controller
{
    public function __construct(private NewsletterService $newsletter) {}

    public function subscribe(Request $request, string $slug)
    {
        [$school] = $this->live($slug);
        abort_unless(Extensions::enabled($school->id, 'newsletter') && NewsletterSettings::resolve($school->id), 404);

        $data = $request->validate([
            'email' => 'required|email:rfc|max:150', 'name' => 'nullable|string|max:100',
            'interests' => 'nullable|array', 'interests.*' => 'string|max:20',
            'consent' => 'accepted',
        ], ['consent.accepted' => 'Please tick the box to say you agree to receive the newsletter.', 'email.required' => 'Please enter your email address.', 'email.email' => 'That does not look like an email address.']);

        $verdict = Visitor::inspect($request, $school->id, 'newsletter', 5, 300);
        if ($verdict === Visitor::ROBOT) {
            return $this->thanks($data['email']); // thanked and ignored
        }
        if ($verdict !== Visitor::OK) {
            return back()->withInput()->withErrors(['email' => Visitor::message($verdict)]);
        }

        $page = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH) ?: null;
        $result = $this->newsletter->subscribe($school, $data['email'], $data['name'] ?? null, (array) ($data['interests'] ?? []), (string) $request->ip(), $page);
        if ($result === 'error') {
            return back()->withInput()->withErrors(['email' => 'We could not send the confirmation email just now. Please try again later.']);
        }

        return $this->thanks($data['email']);
    }

    private function thanks(string $email)
    {
        return redirect(url()->previous().'#newsletter')->with('newsletter', 'Almost done. If this address can receive the newsletter, we have sent it an email. Open it and tap "Confirm my email".');
    }

    /** The link in the email only shows a button: mail scanners open links on their own, and that must not sign anyone up */
    public function confirmPage(string $slug, string $token)
    {
        [$school, $site] = $this->live($slug);
        $sub = $this->owned($school, 'confirm_token', $token);
        if (! $sub || $sub->status === 'unsubscribed') {
            return $this->message($school, $site, 'This link no longer works', 'It may be old, or you may have unsubscribed. You are welcome to sign up again from the school website.');
        }

        return $this->message($school, $site, $sub->status === 'confirmed' ? 'You are already on the list' : 'Confirm your email', $sub->status === 'confirmed' ? 'Thank you. You will get the newsletter.' : 'Tap the button to start receiving the '.$school->name.' newsletter.',
            $sub->status === 'pending' ? ['label' => 'Yes, confirm my email', 'action' => route('site.newsletter.confirm.do', [$school->slug, $token])] : null);
    }

    public function confirm(string $slug, string $token)
    {
        [$school, $site] = $this->live($slug);
        $sub = $this->owned($school, 'confirm_token', $token);
        $done = $sub ? $this->newsletter->confirm($token) : null;

        return $done
            ? $this->message($school, $site, 'You are on the list', 'Thank you. You will now get the '.$school->name.' newsletter. Every email has a link to unsubscribe.')
            : $this->message($school, $site, 'This link no longer works', 'It may be old, or you may have unsubscribed. You are welcome to sign up again from the school website.');
    }

    public function unsubscribePage(string $slug, string $token)
    {
        [$school, $site] = $this->live($slug);
        $sub = $this->owned($school, 'unsubscribe_token', $token);
        if (! $sub) {
            return $this->message($school, $site, 'This link no longer works', 'We could not find that address on the list.');
        }
        if ($sub->status === 'unsubscribed') {
            return $this->message($school, $site, 'You are unsubscribed', 'You will not get any more newsletters from '.$school->name.'.');
        }

        return $this->message($school, $site, 'Unsubscribe', 'Stop the '.$school->name.' newsletter for '.$sub->email.'?', ['label' => 'Unsubscribe me', 'action' => route('site.newsletter.unsubscribe.do', [$school->slug, $token])]);
    }

    /** Also the address mail apps call for their own one-tap "Unsubscribe" button (no page, no login) */
    public function unsubscribe(Request $request, string $slug, string $token)
    {
        [$school, $site] = $this->live($slug);
        $sub = $this->owned($school, 'unsubscribe_token', $token);
        if ($sub) {
            $this->newsletter->unsubscribe($token);
        }
        if ($request->has('List-Unsubscribe') || $request->expectsJson()) {
            return response()->noContent();
        }

        return $sub
            ? $this->message($school, $site, 'You are unsubscribed', 'You will not get any more newsletters from '.$school->name.'. If this was a mistake, you can sign up again on the school website.')
            : $this->message($school, $site, 'This link no longer works', 'We could not find that address on the list.');
    }

    private function owned(School $school, string $column, string $token): ?NewsletterSubscriber
    {
        return NewsletterSubscriber::withoutGlobalScopes()->where('school_id', $school->id)->where($column, $token)->first();
    }

    private function message(School $school, SchoolSite $site, string $title, string $text, ?array $button = null)
    {
        $pages = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')->orderBy('position')->orderBy('id')->get();

        return SiteRenderer::renderPlain($school, $site, $pages, 'website.newsletter-message', 'newsletter', $title, $text, ['button' => $button, 'messageText' => $text]);
    }

    private function live(string $slug): array
    {
        $school = School::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->where('enabled', true)->firstOrFail();

        return [$school, $site];
    }
}
