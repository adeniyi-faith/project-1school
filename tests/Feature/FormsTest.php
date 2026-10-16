<?php

namespace Tests\Feature;

use App\Forms\FormSchema;
use App\Forms\FormTemplates;
use App\Models\NewsletterSubscriber;
use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\SchoolSitePage;
use App\Models\SiteForm;
use App\Models\SiteFormSubmission;
use App\Models\User;
use App\Website\Blocks;
use App\Website\Extensions;
use App\Website\Visitor;
use Illuminate\Support\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/** Footer sign-up and custom forms: what is shown, what is stored, what is refused */
class FormsTest extends SecurityTestCase
{
    private function school(string $name = 'Bright Future School', bool $newsletter = true): School
    {
        $school = $this->makeSchool($name);
        Extensions::set($school->id, 'forms', true);
        Extensions::set($school->id, 'newsletter', $newsletter);
        $admin = $this->makeUser($school, 'school-admin');
        $this->actingAs($admin)->get('/school/website');
        $this->put('/school/website', ['enabled' => true]);
        auth()->logout();
        app('mail.manager')->mailer()->getSymfonyTransport()->flush();

        return $school->fresh();
    }

    private function admin(School $school, string $role = 'school-admin'): User
    {
        return User::where('school_id', $school->id)->get()->first(fn ($u) => $u->hasRole($role)) ?? $this->makeUser($school, $role);
    }

    private function mails()
    {
        return collect(app('mail.manager')->mailer()->getSymfonyTransport()->messages())->map(fn ($m) => $m->getOriginalMessage());
    }

    private function makeForm(School $school, array $attrs = []): SiteForm
    {
        $f = fn (string $key, string $type, string $label, array $extra = []) => $extra + ['key' => $key, 'label' => $label, 'type' => $type, 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false];
        $form = new SiteForm($attrs + [
            'name' => 'Prospectus request', 'button_text' => 'Send me the prospectus', 'active' => true,
            'fields' => [
                $f('fname1', 'text', 'Your name', ['required' => true]), $f('femail', 'email', 'Email address', ['required' => true]),
                $f('fclass', 'select', 'Class', ['options' => ['Nursery', 'Primary 1']]), $f('ftopics', 'checkboxes', 'Interested in', ['options' => ['Sports', 'Music', 'Art']]),
            ],
            'consents' => [['text' => 'I agree to the Privacy Policy of the school.', 'required' => true], ['text' => 'You may phone me.', 'required' => false]],
        ]);
        $form->school_id = $school->id;
        $form->save();

        return $form;
    }

    private function send(School $school, SiteForm $form, array $extra = [], bool $ticket = true)
    {
        $data = $extra + ['a' => ['fname1' => 'Ada Obi', 'femail' => 'ada@example.com', 'fclass' => 'Nursery'], 'c' => [0 => '1']];
        if ($ticket) {
            $data[Visitor::TICKET] ??= Visitor::ticket($school->id, 'form-'.$form->id, Carbon::now()->timestamp - 10);
        }

        return $this->from(url("/s/{$school->slug}/contact"))->post("/s/{$school->slug}/form/{$form->id}", $data);
    }

    // ---- footer sign-up ----

    public function test_the_footer_sign_up_is_off_until_the_school_switches_it_on_and_needs_the_newsletter(): void
    {
        $school = $this->school();
        $this->assertStringNotContainsString('id="footer-signup"', $this->get('/s/'.$school->slug)->assertOk()->getContent());

        $site = \App\Models\SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $site->update(['options' => ($site->options ?? []) + ['footer_signup' => true, 'footer_signup_text' => 'Join our list']]);
        $html = $this->get('/s/'.$school->slug)->getContent();
        $this->assertStringContainsString('id="footer-signup"', $html);
        $this->assertStringContainsString('Join our list', $html);
        $this->assertStringContainsString('name="from" value="footer"', $html);
        $this->assertStringContainsString('name="consent"', $html);
        $this->assertDoesNotMatchRegularExpression('/name="consent"[^>]*checked/', $html, 'never pre-ticked');

        Extensions::set($school->id, 'newsletter', false);
        $this->assertStringNotContainsString('id="footer-signup"', $this->get('/s/'.$school->slug)->getContent(), 'newsletter off: hidden');
    }

    public function test_the_footer_sign_up_uses_the_same_consent_and_confirmation_and_its_own_error_box(): void
    {
        $school = $this->school();
        $ticket = Visitor::ticket($school->id, 'newsletter', Carbon::now()->timestamp - 10);

        $this->from(url("/s/{$school->slug}"))->post("/s/{$school->slug}/newsletter", ['email' => 'p@example.com', 'from' => 'footer', Visitor::TICKET => $ticket])
            ->assertSessionHasErrors('consent', null, 'footer');
        $this->assertSame(0, NewsletterSubscriber::withoutGlobalScopes()->count());

        $this->from(url("/s/{$school->slug}"))->post("/s/{$school->slug}/newsletter", ['email' => 'p@example.com', 'consent' => '1', 'from' => 'footer', Visitor::TICKET => $ticket])
            ->assertSessionHas('newsletter_at', 'footer')->assertRedirect(url("/s/{$school->slug}").'#footer-signup');
        $this->assertSame('pending', NewsletterSubscriber::withoutGlobalScopes()->first()->status, 'still needs the confirmation email');
        $this->assertCount(1, $this->mails());
    }

    // ---- building forms ----

    public function test_an_admin_makes_a_form_from_a_template_edits_it_and_bad_input_is_cleaned_or_refused(): void
    {
        $school = $this->school();
        $this->actingAs($this->admin($school));
        $this->post('/school/website/forms', ['template' => 'job'])->assertRedirect();
        $form = SiteForm::first();
        $this->assertSame('Job application', $form->name);
        $this->assertTrue(collect($form->consents)->contains('required', true), 'the job template asks for the privacy tick');
        $this->get('/school/website/forms/'.$form->id)->assertOk();

        $fields = [
            ['key' => 'fx', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['label' => 'Hack', 'type' => 'script'],                    // unknown kind: dropped
            ['label' => '', 'type' => 'text'],                          // no label: dropped
            ['label' => 'Pick', 'type' => 'radio', 'options' => ["A\nB\nA\n  "]],
        ];
        $fields[3]['options'] = ['A', 'B', 'A', ' '];
        $this->post('/school/website/forms/'.$form->id, ['name' => 'Jobs', 'fields' => $fields, 'consents' => [['text' => 'I agree', 'required' => true], ['text' => '  ']]])->assertSessionHasNoErrors();
        $form->refresh();
        $this->assertSame(['Name', 'Pick'], collect($form->fields)->pluck('label')->all());
        $this->assertSame(['A', 'B'], $form->fields[1]['options']);
        $this->assertCount(1, $form->consents);
        $this->assertMatchesRegularExpression('/^f[a-z0-9]{4,10}$/', $form->fields[0]['key']);

        $this->post('/school/website/forms/'.$form->id, ['name' => 'Jobs', 'fields' => [['label' => 'Pick', 'type' => 'select', 'options' => ['Only one']]]])->assertSessionHasErrors('fields');
        $this->post('/school/website/forms/'.$form->id, ['name' => 'Jobs', 'fields' => []])->assertSessionHasErrors('fields');
        $this->post('/school/website/forms/'.$form->id, ['name' => 'Jobs', 'newsletter_optin' => true, 'fields' => [['label' => 'Name', 'type' => 'text']]])->assertSessionHasErrors('newsletter_optin');
        $this->assertCount(2, $form->fresh()->fields, 'a refused save changes nothing');

        $many = array_map(fn ($i) => ['label' => "Q$i", 'type' => 'text'], range(1, 40));
        $this->assertCount(FormSchema::MAX_FIELDS, FormSchema::cleanFields($many));
    }

    public function test_only_people_with_the_forms_permissions_can_use_the_screens(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school);
        $this->actingAs($this->admin($school, 'principal'))->get('/school/website/forms')->assertOk();
        $this->get('/school/website/forms/'.$form->id.'/answers')->assertOk();
        $this->post('/school/website/forms', ['template' => 'blank'])->assertForbidden();
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'fields' => [['label' => 'a', 'type' => 'text']]])->assertForbidden();
        $this->post('/school/website/forms/'.$form->id.'/delete')->assertForbidden();

        $this->actingAs($this->admin($school, 'teacher'))->get('/school/website/forms')->assertForbidden();

        $other = $this->school('Other School');
        $this->actingAs($this->admin($other));
        $this->get('/school/website/forms/'.$form->id)->assertNotFound();
        $this->get('/school/website/forms/'.$form->id.'/answers')->assertNotFound();
        $this->post('/school/website/forms/'.$form->id.'/delete')->assertNotFound();
        $this->assertNotNull($form->fresh());
    }

    public function test_the_forms_screens_are_hidden_while_the_extension_is_off(): void
    {
        $school = $this->school();
        Extensions::set($school->id, 'forms', false);
        $this->actingAs($this->admin($school))->get('/school/website/forms')->assertNotFound();
    }

    // ---- sending a form ----

    public function test_a_visitor_sends_a_form_and_the_answers_the_wording_and_the_ticks_are_kept(): void
    {
        $school = $this->school();
        $admin = $this->admin($school);
        $form = $this->makeForm($school, ['notify_email' => 'office@example.com', 'success_message' => 'Thanks a lot']);

        $this->send($school, $form, ['a' => ['fname1' => 'Ada Obi', 'femail' => 'ada@example.com', 'fclass' => 'Nursery', 'ftopics' => ['Sports', 'Art']], 'c' => [0 => '1']])
            ->assertSessionHasNoErrors()->assertSessionHas('form_done', [$form->id => 'Thanks a lot']);

        $sub = SiteFormSubmission::withoutGlobalScopes()->first();
        $this->assertSame($school->id, $sub->school_id);
        $this->assertSame('Prospectus request', $sub->form_name);
        $this->assertSame(['Your name' => 'Ada Obi', 'Email address' => 'ada@example.com', 'Class' => 'Nursery', 'Interested in' => 'Sports, Art'], collect($sub->answers)->pluck('value', 'label')->all());
        $this->assertSame([true, false], collect($sub->consents)->pluck('agreed')->all());
        $this->assertSame('I agree to the Privacy Policy of the school.', $sub->consents[0]['text'], 'the exact wording agreed to is kept');
        $this->assertSame('/s/'.$school->slug.'/contact', $sub->page);
        $this->assertSame('new', $sub->status);

        $this->assertNotNull(SchoolNotification::withoutGlobalScopes()->where('user_id', $admin->id)->where('type', 'form_submission')->first(), 'staff get a bell notice');
        $mail = $this->mails()->first(fn ($m) => $m->getTo()[0]->getAddress() === 'office@example.com');
        $this->assertNotNull($mail);
        $this->assertStringContainsString('Ada Obi', $mail->getTextBody());

        // editing the form later never changes what was answered
        $form->update(['fields' => [['key' => 'fname1', 'label' => 'Full name', 'type' => 'text', 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false]]]);
        $this->assertSame('Your name', SiteFormSubmission::withoutGlobalScopes()->first()->answers[0]['label']);
    }

    public function test_missing_answers_missing_required_ticks_and_made_up_choices_are_refused_and_nothing_is_saved(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school);

        $this->send($school, $form, ['a' => ['fname1' => '', 'femail' => 'ada@example.com'], 'c' => [0 => '1']])->assertSessionHasErrors('a.fname1', null, 'form'.$form->id);
        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'femail' => 'not-an-email'], 'c' => [0 => '1']])->assertSessionHasErrors('a.femail', null, 'form'.$form->id);
        $this->send($school, $form, ['c' => []])->assertSessionHasErrors('c.0', null, 'form'.$form->id);
        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'femail' => 'ada@example.com', 'fclass' => 'Secondary 9'], 'c' => [0 => '1']])->assertSessionHasErrors('a.fclass', null, 'form'.$form->id);
        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'femail' => 'ada@example.com', 'ftopics' => ['Sports', 'Drugs']], 'c' => [0 => '1']])->assertSessionHasErrors('a.ftopics.1', null, 'form'.$form->id);
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count());
    }

    public function test_robots_forged_and_too_quick_posts_are_stopped_by_the_safety_kit(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school);

        $this->send($school, $form, [Visitor::TRAP => 'spam'])->assertSessionHasNoErrors(); // thanked, ignored
        $this->send($school, $form, [], false)->assertSessionHasErrors('form', null, 'form'.$form->id); // no ticket
        $this->send($school, $form, [Visitor::TICKET => Visitor::ticket($school->id, 'form-'.$form->id)])->assertSessionHasErrors('form', null, 'form'.$form->id); // sent instantly
        $this->send($school, $form, [Visitor::TICKET => Visitor::ticket($school->id, 'newsletter', Carbon::now()->timestamp - 10)])->assertSessionHasErrors('form', null, 'form'.$form->id); // ticket of another form kind
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count());
    }

    public function test_a_closed_form_another_schools_form_or_a_form_with_the_extension_off_cannot_be_reached(): void
    {
        $school = $this->school();
        $other = $this->school('Other School');
        $closed = $this->makeForm($school, ['active' => false]);
        $this->send($school, $closed)->assertNotFound();
        $this->get("/s/{$school->slug}/form/{$closed->id}")->assertNotFound();

        $theirs = $this->makeForm($other);
        $this->get("/s/{$school->slug}/form/{$theirs->id}")->assertNotFound('a school cannot show another school\'s form');
        $this->send($school, $theirs)->assertNotFound();

        $open = $this->makeForm($school);
        $this->get("/s/{$school->slug}/form/{$open->id}")->assertOk();
        Extensions::set($school->id, 'forms', false);
        $this->get("/s/{$school->slug}/form/{$open->id}")->assertNotFound();
        $this->send($school, $open)->assertNotFound();
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count());
    }

    public function test_the_newsletter_tick_only_exists_when_it_can_work_and_signs_up_with_a_confirmation_email(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school, ['newsletter_optin' => true]);
        $page = $this->get("/s/{$school->slug}/form/{$form->id}")->assertOk()->getContent();
        $this->assertStringContainsString('name="join_newsletter"', $page);
        $this->assertDoesNotMatchRegularExpression('/name="join_newsletter"[^>]*checked/', $page);

        $this->send($school, $form)->assertSessionHasNoErrors();
        $this->assertSame(0, NewsletterSubscriber::withoutGlobalScopes()->count(), 'not ticked: no sign-up');

        $this->send($school, $form, ['join_newsletter' => '1', 'a' => ['fname1' => 'Ada', 'femail' => 'ada@example.com'], 'c' => [0 => '1']]);
        $sub = NewsletterSubscriber::withoutGlobalScopes()->first();
        $this->assertSame('ada@example.com', $sub->email);
        $this->assertSame('pending', $sub->status, 'added only after they confirm');
        $this->assertTrue($this->mails()->contains(fn ($m) => str_contains((string) $m->getHtmlBody(), '/newsletter/confirm/')));
        $this->assertTrue(SiteFormSubmission::withoutGlobalScopes()->latest('id')->first()->newsletter_asked);

        Extensions::set($school->id, 'newsletter', false);
        $this->assertStringNotContainsString('name="join_newsletter"', $this->get("/s/{$school->slug}/form/{$form->id}")->getContent());
        NewsletterSubscriber::withoutGlobalScopes()->delete();
        $this->send($school, $form, ['join_newsletter' => '1', 'a' => ['fname1' => 'Bo', 'femail' => 'bo@example.com'], 'c' => [0 => '1']]);
        $this->assertSame(0, NewsletterSubscriber::withoutGlobalScopes()->count(), 'newsletter off: the tick is ignored');
    }

    // ---- showing a form on a page ----

    public function test_the_form_section_only_accepts_the_schools_own_forms_and_shows_on_the_page(): void
    {
        $school = $this->school();
        $other = $this->school('Other School');
        $mine = $this->makeForm($school);
        $theirs = $this->makeForm($other);

        $clean = Blocks::clean([['type' => 'custom_form', 'form' => 'f'.$mine->id], ['type' => 'custom_form', 'form' => 'f'.$theirs->id], ['type' => 'custom_form', 'form' => (string) $mine->id]], $school->id);
        $this->assertSame('f'.$mine->id, $clean[0]['form']);
        $this->assertSame('', $clean[1]['form'], 'another school\'s form is dropped');
        $this->assertSame('', $clean[2]['form'], 'only the editor\'s own keys are accepted');
        $this->assertContains('f'.$mine->id, array_keys(collect(Blocks::catalogue($school->id))->firstWhere('type', 'custom_form')['fields'][0]['options']));
        $this->assertNotContains('f'.$theirs->id, array_keys(collect(Blocks::catalogue($school->id))->firstWhere('type', 'custom_form')['fields'][0]['options']));

        $home = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_home', true)->first();
        $home->update(['blocks' => [['type' => 'custom_form', 'hidden' => false, 'form' => 'f'.$mine->id, 'heading' => '', 'intro' => '']]]);
        $html = $this->get('/s/'.$school->slug)->assertOk()->getContent();
        $this->assertStringContainsString('name="a[fname1]"', $html);
        $this->assertStringContainsString('name="c[0]"', $html);
        $this->assertStringContainsString('<a href=', $html);
        $this->assertStringContainsString(Visitor::TICKET, $html);
        $this->assertDoesNotMatchRegularExpression('/name="c\[0\]"[^>]*checked/', $html, 'privacy ticks are never pre-ticked');

        $mine->update(['active' => false]);
        $this->assertStringNotContainsString('name="a[fname1]"', $this->get('/s/'.$school->slug)->getContent(), 'closed: hidden');
        $mine->update(['active' => true]);
        Extensions::set($school->id, 'forms', false);
        $this->assertStringNotContainsString('name="a[fname1]"', $this->get('/s/'.$school->slug)->getContent(), 'extension off: hidden');
        $this->assertSame('custom_form', $home->fresh()->blocks[0]['type'], 'still saved');
    }

    // ---- reading the answers ----

    public function test_staff_read_mark_download_and_delete_answers_and_deleting_a_form_removes_them(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school);
        $this->send($school, $form);
        $sub = SiteFormSubmission::withoutGlobalScopes()->first();

        $this->actingAs($this->admin($school));
        $this->get("/school/website/forms/{$form->id}/answers")->assertOk()->assertInertia(fn ($p) => $p->where('counts.new', 1)->where('submissions.data.0.answers.0.value', 'Ada Obi'));
        $this->post("/school/website/forms/{$form->id}/answers/{$sub->id}/read", ['read' => true]);
        $this->assertSame('read', $sub->fresh()->status);

        $xlsx = $this->get("/school/website/forms/{$form->id}/export")->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('Content-Type'));

        $this->post("/school/website/forms/{$form->id}/answers/{$sub->id}/delete");
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count());

        $this->send($school, $form);
        $this->post("/school/website/forms/{$form->id}/delete")->assertRedirect('/school/website/forms');
        $this->assertSame(0, SiteForm::withoutGlobalScopes()->count());
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count(), 'answers go with the form');
    }

    public function test_templates_make_valid_forms(): void
    {
        $school = $this->school();
        foreach (array_keys(FormTemplates::LIST) as $key) {
            $t = FormTemplates::make($key, $school);
            $this->assertNull(FormSchema::problem($t['fields']), "template {$key} must pass the form checks");
        }
    }
}
