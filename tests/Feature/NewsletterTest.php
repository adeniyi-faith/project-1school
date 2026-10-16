<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use App\Models\Package;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SchoolSitePage;
use App\Models\SchoolSubscription;
use App\Models\User;
use App\Newsletter\NewsletterService;
use App\Newsletter\NewsletterSettings;
use App\Website\Extensions;
use App\Website\Visitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\Feature\Security\SecurityTestCase;

/** Newsletter: consent, confirming, unsubscribing, no double sends, who may send through what, and what readers see */
class NewsletterTest extends SecurityTestCase
{
    private function school(string $name = 'Bright Future School'): School
    {
        $school = $this->makeSchool($name);
        $school->update(['address' => '1 School Road', 'city' => 'Lagos']);
        Extensions::set($school->id, 'newsletter', true);
        // the site must be on for public pages
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
        auth()->logout();
        $this->flushMail();

        return $school->fresh();
    }

    private function admin(School $school, string $role = 'school-admin'): User
    {
        return User::where('school_id', $school->id)->get()->first(fn ($u) => $u->hasRole($role)) ?? $this->makeUser($school, $role);
    }

    /** @return \Illuminate\Support\Collection<int, \Symfony\Component\Mime\Email> */
    private function mails()
    {
        return collect(app('mail.manager')->mailer()->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage());
    }

    private function flushMail(): void
    {
        app('mail.manager')->mailer()->getSymfonyTransport()->flush();
    }

    private function signupData(School $school, array $extra = []): array
    {
        return $extra + ['email' => 'parent@example.com', 'name' => 'Ada', 'consent' => '1', Visitor::TICKET => Visitor::ticket($school->id, 'newsletter', Carbon::now()->timestamp - 10)];
    }

    private function subscriber(School $school, string $email, string $status = 'confirmed', array $interests = []): NewsletterSubscriber
    {
        $s = new NewsletterSubscriber(['email' => $email, 'status' => $status, 'interests' => $interests, 'confirm_token' => Str::random(40), 'unsubscribe_token' => Str::random(40), 'consented_at' => now(), 'confirmed_at' => $status === 'confirmed' ? now() : null]);
        $s->school_id = $school->id;
        $s->save();

        return $s;
    }

    private function draft(School $school, array $attrs = []): NewsletterCampaign
    {
        return app(NewsletterService::class)->draft($school->id, $attrs['subject'] ?? 'Term news', $attrs['body'] ?? "Hello **parents**.\n\nSchool resumes Monday.", null);
    }

    private function sendAll(School $school, NewsletterCampaign $c): array
    {
        $svc = app(NewsletterService::class);
        $this->assertNull($svc->start($school, $c->fresh()));
        $out = [];
        for ($i = 0; $i < 20; $i++) {
            $out = $svc->step($school, $c->fresh(), 3);
            if ($out['remaining'] === 0) {
                break;
            }
        }

        return $out;
    }

    // ---- signing up ----

    public function test_a_visitor_signs_up_with_consent_and_gets_a_confirmation_email_but_is_not_on_the_list_yet(): void
    {
        $school = $this->school();
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, ['interests' => ['events']]), ['referer' => url("/s/{$school->slug}/about")])->assertSessionHasNoErrors();

        $sub = NewsletterSubscriber::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $this->assertSame('pending', $sub->status);
        $this->assertSame('parent@example.com', $sub->email);
        $this->assertSame(NewsletterService::CONSENT, $sub->consent_text, 'the wording agreed to is kept');
        $this->assertNotNull($sub->consented_at);
        $this->assertSame('/s/'.$school->slug.'/about', $sub->consent_page);
        $this->assertSame(['events'], $sub->interests);

        $mail = $this->mails()->first();
        $this->assertSame('parent@example.com', $mail->getTo()[0]->getAddress());
        $this->assertStringContainsString("/s/{$school->slug}/newsletter/confirm/{$sub->confirm_token}", $mail->getHtmlBody());
        $this->assertSame(0, $this->newsletterAudience($school)->count(), 'a person who has not confirmed gets no newsletter');
    }

    private function newsletterAudience(School $school)
    {
        return app(NewsletterService::class)->audience($school->id, 'all');
    }

    public function test_signing_up_without_ticking_the_consent_box_does_nothing(): void
    {
        $school = $this->school();
        $data = $this->signupData($school);
        unset($data['consent']);
        $this->post("/s/{$school->slug}/newsletter", $data)->assertSessionHasErrors('consent');
        $this->assertSame(0, NewsletterSubscriber::withoutGlobalScopes()->count());
        $this->assertCount(0, $this->mails());
    }

    public function test_robots_and_forged_forms_are_stopped_before_anything_is_saved(): void
    {
        $school = $this->school();
        // robot fills the hidden box: thanked, ignored
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, [Visitor::TRAP => 'spam']))->assertSessionHasNoErrors();
        // no ticket
        $data = $this->signupData($school);
        unset($data[Visitor::TICKET]);
        $this->post("/s/{$school->slug}/newsletter", $data)->assertSessionHasErrors('email');
        $this->assertSame(0, NewsletterSubscriber::withoutGlobalScopes()->count());
        $this->assertCount(0, $this->mails());
    }

    public function test_one_visitor_cannot_flood_the_form(): void
    {
        $school = $this->school();
        RateLimiter::clear("visitor:newsletter:{$school->id}:127.0.0.1");
        for ($i = 0; $i < 5; $i++) {
            $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, ['email' => "p{$i}@example.com"]))->assertSessionHasNoErrors();
        }
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, ['email' => 'p9@example.com']))->assertSessionHasErrors('email');
        $this->assertSame(5, NewsletterSubscriber::withoutGlobalScopes()->count());
    }

    public function test_the_sign_up_does_not_exist_while_the_extension_is_off_or_the_service_is_not_set_up(): void
    {
        $school = $this->school();
        Extensions::set($school->id, 'newsletter', false);
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school))->assertNotFound();

        Extensions::set($school->id, 'newsletter', true);
        PlatformSetting::set(NewsletterSettings::PLATFORM, Crypt::encryptString(json_encode(['provider' => 'brevo', 'brevo_key' => '', 'from_email' => 'x@example.com'])));
        $this->assertNull(NewsletterSettings::resolve($school->id));
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school))->assertNotFound();
    }

    public function test_the_sign_up_section_shows_on_the_site_only_when_it_can_work(): void
    {
        $school = $this->school();
        $home = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_home', true)->first();
        $home->update(['blocks' => [['type' => 'newsletter_signup', 'hidden' => false, 'heading' => 'Stay in touch', 'intro' => 'Hi', 'button' => 'Sign up', 'interests' => 'yes']]]);

        $html = $this->get('/s/'.$school->slug)->assertOk()->getContent();
        $this->assertStringContainsString('name="consent"', $html);
        $this->assertStringContainsString(Visitor::TICKET, $html);
        $this->assertStringContainsString('name="'.Visitor::TRAP.'"', $html);
        $this->assertDoesNotMatchRegularExpression('/name="consent"[^>]*checked/', $html, 'the box is never pre-ticked');

        Extensions::set($school->id, 'newsletter', false);
        $this->assertStringNotContainsString('name="consent"', $this->get('/s/'.$school->slug)->getContent(), 'extension off: hidden but still saved');
        $this->assertSame('newsletter_signup', $home->fresh()->blocks[0]['type']);
    }

    // ---- confirming and leaving ----

    public function test_opening_the_confirm_link_does_not_confirm_only_pressing_the_button_does(): void
    {
        $school = $this->school();
        $sub = $this->subscriber($school, 'a@example.com', 'pending');
        $url = "/s/{$school->slug}/newsletter/confirm/{$sub->confirm_token}";

        $this->get($url)->assertOk()->assertSee('Yes, confirm my email');
        $this->assertSame('pending', $sub->fresh()->status, 'a mail scanner opening the link must not sign anyone up');

        $this->post($url)->assertOk();
        $this->assertSame('confirmed', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->confirmed_at);
        $this->post($url)->assertOk(); // safe to repeat
        $this->assertSame(1, NewsletterSubscriber::withoutGlobalScopes()->where('status', 'confirmed')->count());
    }

    public function test_a_confirm_link_from_another_school_or_a_made_up_one_does_nothing(): void
    {
        $a = $this->school('School Aaa');
        $b = $this->school('School Bbb');
        $sub = $this->subscriber($a, 'a@example.com', 'pending');
        $this->post("/s/{$b->slug}/newsletter/confirm/{$sub->confirm_token}")->assertOk()->assertSee('no longer works');
        $this->post("/s/{$a->slug}/newsletter/confirm/".Str::random(40))->assertOk()->assertSee('no longer works');
        $this->assertSame('pending', $sub->fresh()->status);
    }

    public function test_unsubscribing_works_in_one_press_and_the_one_tap_mail_app_button_works_too(): void
    {
        $school = $this->school();
        $sub = $this->subscriber($school, 'a@example.com');
        $other = $this->subscriber($school, 'b@example.com');
        $url = "/s/{$school->slug}/newsletter/unsubscribe/{$sub->unsubscribe_token}";

        $this->get($url)->assertOk()->assertSee('Unsubscribe me');
        $this->assertSame('confirmed', $sub->fresh()->status, 'opening the page changes nothing');

        $this->post($url)->assertOk()->assertSee('You are unsubscribed');
        $this->assertSame('unsubscribed', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->unsubscribed_at);
        $this->assertSame('confirmed', $other->fresh()->status);

        $oneTap = $this->subscriber($school, 'c@example.com');
        $this->post("/s/{$school->slug}/newsletter/unsubscribe/{$oneTap->unsubscribe_token}", ['List-Unsubscribe' => 'One-Click'])->assertNoContent();
        $this->assertSame('unsubscribed', $oneTap->fresh()->status);
    }

    public function test_someone_who_left_must_agree_and_confirm_again_and_an_old_link_does_not_bring_them_back(): void
    {
        $school = $this->school();
        $sub = $this->subscriber($school, 'a@example.com');
        $oldConfirm = $sub->confirm_token;
        app(NewsletterService::class)->unsubscribe($sub->unsubscribe_token);
        $this->post("/s/{$school->slug}/newsletter/confirm/{$oldConfirm}")->assertSee('no longer works');
        $this->assertSame('unsubscribed', $sub->fresh()->status);

        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, ['email' => 'A@Example.com']))->assertSessionHasNoErrors();
        $this->assertSame('pending', $sub->fresh()->status);
        $this->assertNull($sub->fresh()->unsubscribed_at);
        $this->assertNotSame($oldConfirm, $sub->fresh()->confirm_token);
        $this->assertSame(1, NewsletterSubscriber::withoutGlobalScopes()->count(), 'the same address is one person');
        $this->assertCount(1, $this->mails());
    }

    public function test_someone_already_on_the_list_gets_the_same_answer_and_no_extra_email(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'a@example.com');
        $this->post("/s/{$school->slug}/newsletter", $this->signupData($school, ['email' => 'a@example.com']))->assertSessionHas('newsletter');
        $this->assertCount(0, $this->mails());
    }

    // ---- sending ----

    public function test_only_confirmed_people_get_the_newsletter_and_every_email_can_be_unsubscribed_from(): void
    {
        $school = $this->school();
        $yes = $this->subscriber($school, 'yes@example.com');
        $this->subscriber($school, 'waiting@example.com', 'pending');
        $this->subscriber($school, 'left@example.com', 'unsubscribed');

        $out = $this->sendAll($school, $this->draft($school));
        $this->assertSame(1, $out['sent']);

        $this->assertCount(1, $this->mails());
        $mail = $this->mails()->first();
        $this->assertSame('yes@example.com', $mail->getTo()[0]->getAddress());
        $link = "/s/{$school->slug}/newsletter/unsubscribe/{$yes->unsubscribe_token}";
        $this->assertStringContainsString($link, $mail->getHtmlBody());
        $this->assertStringContainsString($link, $mail->getTextBody());
        $this->assertStringContainsString($link, $mail->getHeaders()->get('List-Unsubscribe')->getBodyAsString());
        $this->assertSame('List-Unsubscribe=One-Click', $mail->getHeaders()->get('List-Unsubscribe-Post')->getBodyAsString());
    }

    public function test_the_email_carries_the_schools_name_logo_header_and_reply_address(): void
    {
        $school = $this->school();
        $school->update(['email' => 'office@brightfuture.test']);
        $this->subscriber($school, 'yes@example.com');
        NewsletterSettings::saveSchool($school->id, ['header_text' => 'Bright Future News', 'footer_text' => 'Reg. No. 12345', 'colour' => '#aa2200', 'from_name' => ''] + NewsletterSettings::SCHOOL_DEFAULTS);
        $this->sendAll($school, $this->draft($school));

        $mail = $this->mails()->first();
        $this->assertSame('Bright Future School', $mail->getFrom()[0]->getName(), 'the school is the sender name');
        $this->assertSame('office@brightfuture.test', $mail->getReplyTo()[0]->getAddress());
        $html = $mail->getHtmlBody();
        $this->assertStringContainsString('Bright Future News', $html);
        $this->assertStringContainsString('#aa2200', $html);
        $this->assertStringContainsString('Reg. No. 12345', $html);
        $this->assertStringContainsString('1 School Road, Lagos', $html);
        $this->assertStringContainsString('<strong>parents</strong>', $html);
    }

    public function test_the_text_a_school_writes_cannot_inject_code_into_the_email(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'yes@example.com');
        $this->sendAll($school, $this->draft($school, ['body' => "<script>alert(1)</script> hi [x](javascript:alert(1))"]));
        $html = $this->mails()->first()->getHtmlBody();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript', $html, 'it can only ever show as harmless text, never as a link');
    }

    public function test_nobody_gets_the_same_newsletter_twice(): void
    {
        $school = $this->school();
        foreach (['a', 'b', 'c', 'd'] as $n) {
            $this->subscriber($school, "{$n}@example.com");
        }
        $c = $this->draft($school);
        $svc = app(NewsletterService::class);

        $this->assertNull($svc->start($school, $c->fresh()));
        $this->assertNotNull($svc->start($school, $c->fresh()), 'pressing Send twice starts nothing the second time');
        $this->assertSame(4, NewsletterDelivery::withoutGlobalScopes()->where('campaign_id', $c->id)->count());

        // two open tabs both keep asking for the next few
        for ($i = 0; $i < 4; $i++) {
            $svc->step($school, $c->fresh(), 2);
            $svc->step($school, $c->fresh(), 2);
        }
        $svc->step($school, $c->fresh(), 5);

        $this->assertCount(4, $this->mails());
        $this->assertSame(4, $this->mails()->map(fn ($m) => $m->getTo()[0]->getAddress())->unique()->count());
        $this->assertSame('sent', $c->fresh()->status);

        // a delivery someone else is already sending is never picked up again
        $queuedCopy = NewsletterDelivery::withoutGlobalScopes()->where('campaign_id', $c->id)->first();
        $queuedCopy->update(['status' => 'sending']);
        $svc->step($school, $c->fresh(), 5);
        $this->assertCount(4, $this->mails());

        // the unique pair is the last line of defence
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('newsletter_deliveries')->insert(['school_id' => $school->id, 'campaign_id' => $c->id, 'subscriber_id' => $queuedCopy->subscriber_id, 'email' => 'dup@example.com', 'status' => 'queued', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_someone_who_unsubscribes_while_a_sending_is_under_way_is_skipped(): void
    {
        $school = $this->school();
        $a = $this->subscriber($school, 'a@example.com');
        $b = $this->subscriber($school, 'b@example.com');
        $c = $this->draft($school);
        $svc = app(NewsletterService::class);
        $svc->start($school, $c->fresh());
        $svc->step($school, $c->fresh(), 1);
        $svc->unsubscribe($b->unsubscribe_token);
        $svc->step($school, $c->fresh(), 5);

        $this->assertCount(1, $this->mails());
        $this->assertSame('skipped', NewsletterDelivery::withoutGlobalScopes()->where('subscriber_id', $b->id)->value('status'));
    }

    public function test_a_sending_over_the_daily_limit_is_refused_and_nothing_is_sent(): void
    {
        $school = $this->school();
        NewsletterSettings::savePlatform(['daily_limit' => 2] + NewsletterSettings::PLATFORM_DEFAULTS);
        foreach (['a', 'b', 'c'] as $n) {
            $this->subscriber($school, "{$n}@example.com");
        }
        $c = $this->draft($school);
        $error = app(NewsletterService::class)->start($school, $c->fresh());
        $this->assertStringContainsString('daily sending limit is 2', $error);
        $this->assertSame('draft', $c->fresh()->status);
        $this->assertCount(0, $this->mails());
    }

    public function test_failed_emails_are_tried_again_on_request_but_unsure_ones_never_are(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'a@example.com');
        $this->subscriber($school, 'b@example.com');
        $c = $this->draft($school);
        $svc = app(NewsletterService::class);
        $svc->start($school, $c->fresh());
        $rows = NewsletterDelivery::withoutGlobalScopes()->where('campaign_id', $c->id)->orderBy('id')->get();
        $rows[0]->update(['status' => 'failed', 'error' => 'refused']);
        DB::table('newsletter_deliveries')->where('id', $rows[1]->id)->update(['status' => 'sending', 'updated_at' => now()->subMinutes(30)]); // the server stopped in the middle of this one

        $out = $svc->step($school, $c->fresh());
        $this->assertSame('unsure', $rows[1]->fresh()->status);
        $this->assertSame(0, $out['remaining']);
        $this->assertCount(0, $this->mails(), 'the unsure one is not sent again');

        $this->assertSame(1, $svc->retryFailed($school, $c->fresh()));
        $svc->step($school, $c->fresh());
        $this->assertCount(1, $this->mails());
        $this->assertSame('a@example.com', $this->mails()->first()->getTo()[0]->getAddress());
        $this->assertSame('unsure', $rows[1]->fresh()->status);
    }

    public function test_a_newsletter_can_go_to_only_the_people_interested_in_a_topic(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'ev@example.com', 'confirmed', ['events']);
        $this->subscriber($school, 'news@example.com', 'confirmed', ['news']);
        $c = $this->draft($school);
        $c->update(['audience' => 'events']);
        $this->sendAll($school, $c);
        $this->assertSame(['ev@example.com'], $this->mails()->map(fn ($m) => $m->getTo()[0]->getAddress())->all());
    }

    public function test_a_test_copy_goes_to_one_address_and_records_nothing(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'a@example.com');
        $c = $this->draft($school);
        $this->assertNull(app(NewsletterService::class)->sendTest($school, $c, 'me@example.com'));
        $this->assertCount(1, $this->mails());
        $this->assertSame('[Test] Term news', $this->mails()->first()->getSubject());
        $this->assertSame(0, NewsletterDelivery::withoutGlobalScopes()->count());
        $this->assertSame('draft', $c->fresh()->status);
    }

    // ---- which service sends ----

    public function test_a_school_uses_the_platform_service_by_default_and_the_platform_owner_can_limit_who_may(): void
    {
        $school = $this->school();
        $other = $this->school('Other School');
        $plan = Package::create(['name' => 'Premium', 'slug' => 'premium', 'price_monthly' => 1, 'price_yearly' => 1, 'is_active' => true]);
        SchoolSubscription::create(['school_id' => $other->id, 'package_id' => $plan->id, 'start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'status' => 'active']);

        $this->assertSame('platform', NewsletterSettings::resolve($school->id)->source);
        $this->assertSame('mailbox', NewsletterSettings::resolve($school->id)->provider::key());

        NewsletterSettings::savePlatform(['access' => ['mode' => 'selected', 'plans' => [$plan->id], 'schools' => []]] + NewsletterSettings::PLATFORM_DEFAULTS);
        $this->assertNull(NewsletterSettings::resolve($school->id), 'not chosen, not on the plan');
        $this->assertNotNull(NewsletterSettings::resolve($other->id), 'on a chosen plan');

        NewsletterSettings::savePlatform(['access' => ['mode' => 'selected', 'plans' => [], 'schools' => [$school->id]]] + NewsletterSettings::PLATFORM_DEFAULTS);
        $this->assertNotNull(NewsletterSettings::resolve($school->id), 'chosen by name');
        $this->assertNull(NewsletterSettings::resolve($other->id));
    }

    public function test_a_school_that_may_not_use_the_platform_service_can_still_use_its_own_brevo_account(): void
    {
        Http::fake(['api.brevo.com/v3/account' => Http::response(['email' => 'x'], 200), 'api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '1'], 201)]);
        $school = $this->school();
        NewsletterSettings::savePlatform(['access' => ['mode' => 'selected', 'plans' => [], 'schools' => []]] + NewsletterSettings::PLATFORM_DEFAULTS);
        $this->assertNull(NewsletterSettings::resolve($school->id));

        $this->actingAs($this->admin($school))->post('/school/website/newsletter/settings', [
            'mode' => 'own', 'provider' => 'brevo', 'from_email' => 'news@myschool.test', 'from_name' => 'My School', 'config' => ['brevo' => ['api_key' => 'xkeysib-secret-1234']],
        ])->assertSessionHasNoErrors();

        $sender = NewsletterSettings::resolve($school->id);
        $this->assertSame('own', $sender->source);
        $this->subscriber($school, 'a@example.com');
        $this->sendAll($school, $this->draft($school));
        Http::assertSent(fn ($r) => str_contains($r->url(), '/smtp/email') && $r->header('api-key')[0] === 'xkeysib-secret-1234'
            && $r['sender']['email'] === 'news@myschool.test' && $r['sender']['name'] === 'My School' && $r['to'][0]['email'] === 'a@example.com'
            && str_contains($r['htmlContent'], '/newsletter/unsubscribe/') && isset($r['headers']['List-Unsubscribe']));
        $this->assertCount(0, $this->mails(), 'it did not go through the platform mailbox');
    }

    public function test_service_keys_are_kept_encrypted_and_never_sent_back_to_the_browser(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response([], 200)]);
        $school = $this->school();
        $admin = $this->admin($school);
        $this->actingAs($admin)->post('/school/website/newsletter/settings', ['mode' => 'own', 'provider' => 'brevo', 'from_email' => 'n@s.test', 'config' => ['brevo' => ['api_key' => 'xkeysib-topsecret-9876']]])->assertSessionHasNoErrors();

        $raw = SchoolSetting::where('school_id', $school->id)->where('key', NewsletterSettings::SCHOOL)->value('value');
        $this->assertStringNotContainsString('topsecret', $raw);

        $page = $this->get('/school/website/newsletter?tab=service');
        $this->assertStringNotContainsString('topsecret', $page->getContent());
        $page->assertInertia(fn ($p) => $p->where('settings.config.brevo.api_key', '••••9876'));

        // saving again with the hidden form of the key keeps the real one
        $this->post('/school/website/newsletter/settings', ['mode' => 'own', 'provider' => 'brevo', 'from_email' => 'n@s.test', 'config' => ['brevo' => ['api_key' => '••••9876']]])->assertSessionHasNoErrors();
        $this->assertSame('xkeysib-topsecret-9876', NewsletterSettings::school($school->id)['config']['brevo']['api_key']);
    }

    public function test_settings_that_do_not_work_are_not_saved(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Key not found'], 401)]);
        $school = $this->school();
        $this->actingAs($this->admin($school))->post('/school/website/newsletter/settings', ['mode' => 'own', 'provider' => 'brevo', 'from_email' => 'n@s.test', 'config' => ['brevo' => ['api_key' => 'wrong']]])->assertSessionHasErrors('config');
        $this->assertSame('platform', NewsletterSettings::school($school->id)['mode']);

        // an SMTP server on a private network is refused
        $this->post('/school/website/newsletter/settings', ['mode' => 'own', 'provider' => 'smtp', 'from_email' => 'n@s.test', 'config' => ['smtp' => ['host' => '127.0.0.1', 'port' => '25', 'username' => 'u', 'password' => 'p', 'encryption' => 'tls']]])->assertSessionHasErrors('config');
        $this->assertSame('platform', NewsletterSettings::school($school->id)['mode']);
    }

    public function test_the_platform_owner_saves_the_platform_service_with_a_checked_key_and_who_may_use_it(): void
    {
        Http::fake(['api.brevo.com/*' => Http::sequence()->push([], 200)->push([], 401)]);
        $school = $this->school();
        $owner = $this->makeUser($this->makeSchool('Platform Co'), 'super-admin');
        $this->actingAs($owner)->post('/super-admin/newsletter', ['provider' => 'brevo', 'from_email' => 'hello@platform.test', 'brevo_key' => 'xkeysib-platform-5555', 'access' => ['mode' => 'selected', 'plans' => [], 'schools' => [$school->id]]])->assertSessionHasNoErrors();

        $p = NewsletterSettings::platform();
        $this->assertSame('brevo', $p['provider']);
        $this->assertSame([$school->id], $p['access']['schools']);
        $this->assertStringNotContainsString('platform-5555', PlatformSetting::get(NewsletterSettings::PLATFORM));
        $this->get('/super-admin/newsletter')->assertInertia(fn ($pg) => $pg->where('settings.brevo_key', '••••5555'));
        $this->assertSame('brevo', NewsletterSettings::resolve($school->id)->provider::key());
        $this->assertSame('hello@platform.test', NewsletterSettings::resolve($school->id)->fromEmail);
        $this->assertSame('Bright Future School', NewsletterSettings::resolve($school->id)->fromName, 'schools keep their own name on platform mail');

        // a key Brevo rejects is not saved
        $this->post('/super-admin/newsletter', ['provider' => 'brevo', 'from_email' => 'hello@platform.test', 'brevo_key' => 'bad', 'access' => ['mode' => 'all']])->assertSessionHasErrors('brevo_key');
        $this->assertSame([$school->id], NewsletterSettings::platform()['access']['schools']);
    }

    public function test_only_the_platform_owner_can_change_the_platform_service(): void
    {
        $school = $this->school();
        $this->actingAs($this->admin($school))->post('/super-admin/newsletter', ['provider' => 'mailbox', 'access' => ['mode' => 'all']])->assertForbidden();
    }

    // ---- the school's screens ----

    public function test_a_school_sees_its_subscribers_and_what_it_sent_and_nobody_elses(): void
    {
        $school = $this->school();
        $other = $this->school('Other School');
        $this->subscriber($school, 'mine@example.com');
        $this->subscriber($other, 'theirs@example.com');
        $c = $this->draft($school, ['subject' => 'Our newsletter']);
        $this->sendAll($school, $c);
        $this->draft($other, ['subject' => 'Their secret']);

        $this->actingAs($this->admin($school))->get('/school/website/newsletter')->assertOk()->assertInertia(fn ($p) => $p
            ->where('counts.confirmed', 1)->has('subscribers.data', 1)->where('subscribers.data.0.email', 'mine@example.com')
            ->has('campaigns', 1)->where('campaigns.0.subject', 'Our newsletter')->where('campaigns.0.sent', 1));
        $this->get('/school/website/newsletter/campaigns/'.$c->id)->assertOk()->assertInertia(fn ($p) => $p->has('deliveries', 1)->where('deliveries.0.email', 'mine@example.com'));
        $theirs = NewsletterCampaign::withoutGlobalScopes()->where('school_id', $other->id)->first();
        $this->get('/school/website/newsletter/campaigns/'.$theirs->id)->assertNotFound();
    }

    public function test_the_subscriber_list_can_be_searched_filtered_downloaded_and_a_person_removed(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'ada@example.com');
        $gone = $this->subscriber($school, 'bola@example.com', 'unsubscribed');
        $admin = $this->admin($school);
        $this->actingAs($admin)->get('/school/website/newsletter?status=unsubscribed')->assertInertia(fn ($p) => $p->has('subscribers.data', 1)->where('subscribers.data.0.email', 'bola@example.com'));
        $this->get('/school/website/newsletter?q=ada')->assertInertia(fn ($p) => $p->has('subscribers.data', 1));
        $this->get('/school/website/newsletter/export')->assertOk()->assertHeader('content-disposition', 'attachment; filename="newsletter-subscribers.xlsx"');

        $this->post("/school/website/newsletter/subscribers/{$gone->id}/remove")->assertSessionHasNoErrors();
        $this->assertNull(NewsletterSubscriber::withoutGlobalScopes()->find($gone->id));
    }

    public function test_the_screens_follow_permissions_and_the_extension_switch(): void
    {
        $school = $this->school();
        $this->actingAs($this->makeUser($school, 'teacher'))->get('/school/website/newsletter')->assertForbidden();

        $principal = $this->makeUser($school, 'principal');
        $this->actingAs($principal)->get('/school/website/newsletter')->assertOk();
        $this->post('/school/website/newsletter/settings', ['mode' => 'platform', 'provider' => 'brevo'])->assertForbidden();
        $c = $this->draft($school);
        $this->post("/school/website/newsletter/campaigns/{$c->id}", ['subject' => 'New', 'body' => 'Hi', 'audience' => 'all'])->assertSessionHasNoErrors();

        Extensions::set($school->id, 'newsletter', false);
        $this->get('/school/website/newsletter')->assertNotFound();
    }

    public function test_a_sent_newsletter_cannot_be_edited_or_deleted_or_sent_again(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'a@example.com');
        $c = $this->draft($school);
        $this->sendAll($school, $c);
        $admin = $this->admin($school);
        $this->actingAs($admin)->post("/school/website/newsletter/campaigns/{$c->id}", ['subject' => 'Changed', 'body' => 'x', 'audience' => 'all'])->assertSessionHasErrors('subject');
        $this->post("/school/website/newsletter/campaigns/{$c->id}/delete")->assertSessionHasErrors('subject');
        $this->post("/school/website/newsletter/campaigns/{$c->id}/send")->assertSessionHasErrors('send');
        $this->assertSame('Term news', $c->fresh()->subject);
        $this->assertCount(1, $this->mails());
    }

    // ---- reaching subscribers from notices ----

    public function test_an_announcement_can_also_become_a_draft_newsletter_but_is_never_mailed_without_a_look(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'a@example.com');
        $principal = $this->makeUser($school, 'principal');
        $this->actingAs($principal)->post('/school/communication/announcements', ['title' => 'Sports day', 'body' => 'Friday at 10am.', 'audience' => 'all', 'email_subscribers' => true])->assertSessionHasNoErrors();

        $this->assertSame(1, Announcement::where('school_id', $school->id)->count());
        $draft = NewsletterCampaign::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $this->assertSame('Sports day', $draft->subject);
        $this->assertSame('draft', $draft->status);
        $this->assertCount(0, $this->mails());

        // without the tick, no draft
        $this->post('/school/communication/announcements', ['title' => 'Another', 'body' => 'Text', 'audience' => 'all'])->assertSessionHasNoErrors();
        $this->assertSame(1, NewsletterCampaign::withoutGlobalScopes()->where('school_id', $school->id)->count());
    }

    public function test_the_email_blast_can_target_newsletter_subscribers_and_reaches_only_confirmed_ones(): void
    {
        $school = $this->school();
        $this->subscriber($school, 'yes@example.com');
        $this->subscriber($school, 'wait@example.com', 'pending');
        $admin = $this->admin($school);
        $this->actingAs($admin)->get('/school/communication/blast')->assertInertia(fn ($p) => $p->where('subscribers', 1));

        $res = $this->post('/school/communication/blast', ['channel' => 'email', 'audience' => 'subscribers', 'subject' => 'Resumption', 'message' => 'School resumes Monday.']);
        $campaign = NewsletterCampaign::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $res->assertRedirect('/school/website/newsletter/campaigns/'.$campaign->id.'?send=1');
        $this->assertSame('sending', $campaign->status);

        app(NewsletterService::class)->step($school, $campaign->fresh());
        $this->assertSame(['yes@example.com'], $this->mails()->map(fn ($m) => $m->getTo()[0]->getAddress())->all());
        $this->assertStringContainsString('/newsletter/unsubscribe/', $this->mails()->first()->getHtmlBody());
    }

    public function test_the_blast_does_not_offer_subscribers_when_the_newsletter_is_off(): void
    {
        $school = $this->school();
        Extensions::set($school->id, 'newsletter', false);
        $admin = $this->admin($school);
        $this->actingAs($admin)->get('/school/communication/blast')->assertInertia(fn ($p) => $p->where('subscribers', null));
        $this->post('/school/communication/blast', ['channel' => 'email', 'audience' => 'subscribers', 'subject' => 'x', 'message' => 'y'])->assertForbidden();
    }
}
