<?php

namespace App\Newsletter;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use App\Models\School;
use App\Models\SchoolSite;
use App\Website\Prose;
use App\Website\SiteRenderer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Everything the newsletter does with people and emails: sign-up with confirmation, unsubscribing, drawing an email
 * in the school's look, and sending a newsletter in small safe steps.
 *
 * Two rules hold everywhere: only people who confirmed ever get a newsletter, and a person never gets the same
 * newsletter twice (one delivery row per person per newsletter, claimed before anything is sent).
 */
class NewsletterService
{
    /** The wording next to the sign-up tick box. It is stored with each sign-up as the record of what the person agreed to. */
    public const CONSENT = 'Yes, send me this school\'s newsletter by email. I can unsubscribe at any time.';

    /** A sending that started and never came back (the server stopped mid-send) is not retried by itself: we cannot know whether it left */
    private const STUCK_AFTER_MINUTES = 10;

    // ---- signing up ----

    /**
     * Record a sign-up and send the "please confirm" email. Always answers the same way to the visitor, so nobody can
     * use the form to find out who is already on the list.
     *
     * @return string 'sent' or 'error' (the email could not be sent) or 'ignored' (already confirmed, nothing to do)
     */
    public function subscribe(School $school, string $email, ?string $name, array $interests, string $ip, ?string $page): string
    {
        $email = Str::lower(trim($email));
        $interests = array_values(array_intersect(array_keys(NewsletterSubscriber::INTERESTS), $interests));
        $sub = NewsletterSubscriber::withoutGlobalScopes()->where('school_id', $school->id)->where('email', $email)->first();

        if ($sub && $sub->status === 'confirmed') {
            return 'ignored';
        }
        // asking again within five minutes does not send a second confirmation
        if ($sub && $sub->status === 'pending' && $sub->confirm_sent_at && $sub->confirm_sent_at->gt(now()->subMinutes(5))) {
            return 'sent';
        }

        $fresh = [
            'name' => $name !== null && trim($name) !== '' ? mb_substr(trim($name), 0, 100) : null, 'interests' => $interests,
            'status' => 'pending', 'consent_text' => self::CONSENT, 'consented_at' => now(), 'consent_ip' => $ip, 'consent_page' => $page ? mb_substr($page, 0, 300) : null,
            'confirmed_at' => null, 'unsubscribed_at' => null,
        ];
        if ($sub) {
            $sub->forceFill($fresh + ['confirm_token' => Str::random(40)])->save(); // someone who left asks again: a fresh agreement is needed
        } else {
            $sub = new NewsletterSubscriber($fresh + ['email' => $email, 'confirm_token' => Str::random(40), 'unsubscribe_token' => Str::random(40)]);
            $sub->school_id = $school->id;
            $sub->save();
        }

        return $this->sendConfirmation($school, $sub) === null ? 'sent' : 'error';
    }

    private function sendConfirmation(School $school, NewsletterSubscriber $sub): ?string
    {
        $sender = NewsletterSettings::resolve($school->id);
        if (! $sender) {
            return 'The newsletter is not set up.';
        }
        $url = route('site.newsletter.confirm', [$school->slug, $sub->confirm_token]);
        $html = $this->draw($school, [
            'subject' => 'Please confirm your email', 'bodyHtml' => '<p>Hello'.($sub->name ? ' '.e($sub->name) : '').',</p><p>Someone asked for the newsletter from <strong>'.e($school->name).'</strong> to be sent to this address. If that was you, tap the button below to confirm.</p><p>If it was not you, ignore this email and nothing will be sent.</p>',
            'buttonUrl' => $url, 'buttonText' => 'Confirm my email', 'reason' => 'You are receiving this because this address was entered on the '.$school->name.' website.',
        ]);
        $error = $sender->provider->send(new OutgoingEmail($sub->email, $sub->name, $sender->fromEmail, $sender->fromName, $sender->replyTo, 'Please confirm your email', $html, $this->text($html, "Confirm your email: {$url}"), null), $sender->config);
        if ($error === null) {
            $sub->forceFill(['confirm_sent_at' => now()])->save();
        }

        return $error;
    }

    /** Confirming is safe to repeat. A person who has unsubscribed cannot be brought back by an old link. */
    public function confirm(string $token): ?NewsletterSubscriber
    {
        $sub = NewsletterSubscriber::withoutGlobalScopes()->where('confirm_token', $token)->first();
        if (! $sub || $sub->status === 'unsubscribed') {
            return null;
        }
        if ($sub->status === 'pending') {
            $sub->forceFill(['status' => 'confirmed', 'confirmed_at' => now()])->save();
        }

        return $sub;
    }

    public function unsubscribe(string $token): ?NewsletterSubscriber
    {
        $sub = NewsletterSubscriber::withoutGlobalScopes()->where('unsubscribe_token', $token)->first();
        if ($sub && $sub->status !== 'unsubscribed') {
            $sub->forceFill(['status' => 'unsubscribed', 'unsubscribed_at' => now()])->save();
        }

        return $sub;
    }

    // ---- drawing an email ----

    /** Colour of the email's top band: the school's choice, else its website colour, else a calm blue */
    public function colour(School $school): string
    {
        $own = NewsletterSettings::school($school->id)['colour'];
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->value('color');
        foreach ([$own, $site] as $c) {
            if (is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
                return $c;
            }
        }

        return '#1e3a8a';
    }

    /** @param array{subject:string, bodyHtml:string, preheader?:?string, buttonUrl?:?string, buttonText?:?string, reason?:?string, unsubscribeUrl?:?string} $content */
    public function draw(School $school, array $content): string
    {
        $s = NewsletterSettings::school($school->id);

        return view('newsletter.email', $content + [
            'preheader' => null, 'buttonUrl' => null, 'buttonText' => null, 'reason' => null, 'unsubscribeUrl' => null,
            'colour' => $this->colour($school), 'logoUrl' => $school->logo ? $school->logo_url : null,
            'headerText' => trim((string) $s['header_text']) ?: $school->name, 'footerText' => trim((string) $s['footer_text']),
            'schoolName' => $school->name, 'address' => trim(collect([$school->address, $school->city, $school->state])->filter()->implode(', ')),
        ])->render();
    }

    /** The plain-text twin of an email, for mail apps that do not show pictures and layout */
    public function text(string $html, string $extra = ''): string
    {
        $t = preg_replace('#<style.*?</style>#si', '', $html);
        $t = preg_replace(['#<br\s*/?>#i', '#</(p|h2|h3|li|tr|div)>#i'], "\n", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5);
        $t = trim(preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $t)));

        return trim($t."\n\n".$extra);
    }

    public function bodyHtml(string $body): string
    {
        return Prose::html($body, 2);
    }

    // ---- sending a newsletter ----

    /** A new draft newsletter for a school. Also used by the blast and announcement screens to reach subscribers. */
    public function draft(int $schoolId, string $subject, string $body, ?int $userId): NewsletterCampaign
    {
        $c = new NewsletterCampaign(['subject' => Str::limit(trim($subject) ?: 'School newsletter', 150, ''), 'body' => $body, 'audience' => 'all', 'status' => 'draft', 'created_by' => $userId]);
        $c->school_id = $schoolId;
        $c->save();

        return $c;
    }

    /** @return array{0:string,1:string,2:?string} the subject, the html and the plain text of one person's copy */
    private function copyFor(School $school, NewsletterCampaign $c, string $unsubscribeUrl, ?NewsletterSubscriber $sub): array
    {
        $html = $this->draw($school, [
            'subject' => $c->subject, 'preheader' => $c->preheader, 'bodyHtml' => $this->bodyHtml($c->body), 'unsubscribeUrl' => $unsubscribeUrl,
            'reason' => 'You are receiving this because you signed up for the '.$school->name.' newsletter.',
        ]);

        return [$c->subject, $html, $this->text($html, 'Unsubscribe: '.$unsubscribeUrl)];
    }

    public function unsubscribeUrl(School $school, NewsletterSubscriber $sub): string
    {
        return route('site.newsletter.unsubscribe', [$school->slug, $sub->unsubscribe_token]);
    }

    /** People this newsletter would go to: confirmed subscribers, narrowed by interest when one is chosen */
    public function audience(int $schoolId, string $audience)
    {
        $q = NewsletterSubscriber::withoutGlobalScopes()->where('school_id', $schoolId)->where('status', 'confirmed');
        if ($audience !== 'all' && isset(NewsletterSubscriber::INTERESTS[$audience])) {
            $q->whereJsonContains('interests', $audience);
        }

        return $q;
    }

    public function sentToday(int $schoolId): int
    {
        return NewsletterDelivery::withoutGlobalScopes()->where('school_id', $schoolId)->whereIn('status', ['sent', 'sending', 'unsure'])->where('updated_at', '>=', Carbon::now()->startOfDay())->count();
    }

    /** A test copy to one address, not counted and not recorded against any subscriber */
    public function sendTest(School $school, NewsletterCampaign $c, string $to): ?string
    {
        $sender = NewsletterSettings::resolve($school->id);
        if (! $sender) {
            return 'Set up how the newsletter is sent first.';
        }
        [$subject, $html, $text] = $this->copyFor($school, $c, SiteRenderer::homeUrl($school), null);

        return $sender->provider->send(new OutgoingEmail($to, null, $sender->fromEmail, $sender->fromName, $sender->replyTo, '[Test] '.$subject, $html, $text, null), $sender->config);
    }

    /** Start sending. Null when it started, otherwise the plain reason it did not. Starting twice is refused. */
    public function start(School $school, NewsletterCampaign $c): ?string
    {
        $sender = NewsletterSettings::resolve($school->id);
        if (! $sender) {
            return 'Set up how the newsletter is sent first.';
        }
        if ($c->status !== 'draft') {
            return 'This newsletter has already been sent or is being sent.';
        }
        if (trim($c->subject) === '' || trim($c->body) === '') {
            return 'Write a subject and some text first.';
        }
        $people = $this->audience($school->id, $c->audience)->get(['id', 'email']);
        if ($people->isEmpty()) {
            return 'Nobody has confirmed their email yet, so there is nobody to send to.';
        }
        $room = $sender->dailyLimit - $this->sentToday($school->id);
        if ($people->count() > $room) {
            return 'Your daily sending limit is '.$sender->dailyLimit.' emails and '.max(0, $room).' are left today, but this goes to '.$people->count().' people. Send it tomorrow, or choose a smaller group.';
        }

        // claim the start: only one request can move it from draft to sending, so a double click cannot make two sendings
        $claimed = NewsletterCampaign::withoutGlobalScopes()->where('id', $c->id)->where('status', 'draft')->update(['status' => 'sending', 'started_at' => now(), 'total' => $people->count()]);
        if (! $claimed) {
            return 'This newsletter has already been sent or is being sent.';
        }
        foreach ($people->chunk(200) as $chunk) {
            NewsletterDelivery::withoutGlobalScopes()->insertOrIgnore($chunk->map(fn ($p) => [
                'school_id' => $school->id, 'campaign_id' => $c->id, 'subscriber_id' => $p->id, 'email' => $p->email, 'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
            ])->all());
        }

        return null;
    }

    /**
     * Send the next few. The page calls this again and again until nothing is left, so no request runs long and no
     * background worker is needed. Each person is claimed first (queued becomes sending in one step), so two open
     * tabs or a double click can never send the same person's copy twice.
     *
     * @return array{sent:int, failed:int, remaining:int, status:string}
     */
    public function step(School $school, NewsletterCampaign $c, int $batch = 15): array
    {
        $sender = NewsletterSettings::resolve($school->id);
        $deliveries = NewsletterDelivery::withoutGlobalScopes();

        // a sending that never reported back may or may not have left: mark it "unsure" and never send it again by itself
        $deliveries->clone()->where('campaign_id', $c->id)->where('status', 'sending')->where('updated_at', '<', now()->subMinutes(self::STUCK_AFTER_MINUTES))
            ->update(['status' => 'unsure', 'error' => 'The sending was interrupted, so we cannot tell if this one left.']);

        if ($c->status === 'sending' && $sender) {
            $ids = $deliveries->clone()->where('campaign_id', $c->id)->where('status', 'queued')->orderBy('id')->limit($batch)->pluck('id');
            foreach ($ids as $id) {
                $claimed = $deliveries->clone()->where('id', $id)->where('status', 'queued')->update(['status' => 'sending', 'updated_at' => now()]);
                if (! $claimed) {
                    continue; // another request took this one
                }
                $d = $deliveries->clone()->find($id);
                $sub = NewsletterSubscriber::withoutGlobalScopes()->find($d->subscriber_id);
                if (! $sub || $sub->status !== 'confirmed') { // left or removed since the sending started
                    $d->forceFill(['status' => 'skipped', 'error' => 'No longer subscribed.'])->save();

                    continue;
                }
                [$subject, $html, $text] = $this->copyFor($school, $c, $this->unsubscribeUrl($school, $sub), $sub);
                $error = $sender->provider->send(new OutgoingEmail($sub->email, $sub->name, $sender->fromEmail, $sender->fromName, $sender->replyTo, $subject, $html, $text, $this->unsubscribeUrl($school, $sub)), $sender->config);
                $d->forceFill($error === null ? ['status' => 'sent', 'sent_at' => now(), 'error' => null] : ['status' => 'failed', 'error' => mb_substr($error, 0, 300)])->save();
            }
        }

        $counts = $deliveries->clone()->where('campaign_id', $c->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $open = (int) ($counts['queued'] ?? 0) + (int) ($counts['sending'] ?? 0);
        if ($open === 0 && $c->status === 'sending') {
            NewsletterCampaign::withoutGlobalScopes()->where('id', $c->id)->where('status', 'sending')->update(['status' => 'sent', 'finished_at' => now()]);
        }

        return ['sent' => (int) ($counts['sent'] ?? 0), 'failed' => (int) ($counts['failed'] ?? 0) + (int) ($counts['unsure'] ?? 0), 'remaining' => $open, 'status' => $open === 0 ? 'sent' : 'sending'];
    }

    /** Put the ones that failed with a clear error back in the queue (never the "unsure" ones, which may already have left) */
    public function retryFailed(School $school, NewsletterCampaign $c): int
    {
        $n = NewsletterDelivery::withoutGlobalScopes()->where('campaign_id', $c->id)->where('status', 'failed')->update(['status' => 'queued', 'error' => null]);
        if ($n > 0) {
            NewsletterCampaign::withoutGlobalScopes()->where('id', $c->id)->update(['status' => 'sending', 'finished_at' => null]);
        }

        return $n;
    }
}
