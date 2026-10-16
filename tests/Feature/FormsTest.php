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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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

    // ---- questions that appear after another answer ----

    private function conditionalForm(School $school): SiteForm
    {
        $f = fn (string $key, string $type, string $label, array $extra = []) => $extra + ['key' => $key, 'label' => $label, 'type' => $type, 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false, 'show_if' => null, 'maps_to' => ''];

        return $this->makeForm($school, ['consents' => [], 'fields' => [
            $f('fwho11', 'radio', 'Are you a current parent?', ['required' => true, 'options' => ['Yes', 'No']]),
            $f('fchild', 'text', 'Child\'s name', ['required' => true, 'show_if' => ['key' => 'fwho11', 'value' => 'Yes']]),
            $f('fhow11', 'select', 'How did you hear about us?', ['options' => ['Friend', 'Radio'], 'show_if' => ['key' => 'fwho11', 'value' => 'No']]),
            $f('fmore1', 'text', 'Which friend?', ['required' => true, 'show_if' => ['key' => 'fhow11', 'value' => 'Friend']]),
        ]]);
    }

    public function test_a_question_that_waits_for_an_answer_is_required_only_when_that_answer_is_given_and_ignored_otherwise(): void
    {
        $school = $this->school();
        $form = $this->conditionalForm($school);
        $err = 'form'.$form->id;

        // "No": the child's name is not needed, and anything sent for it is thrown away
        $this->send($school, $form, ['a' => ['fwho11' => 'No', 'fchild' => 'Sneaky', 'fhow11' => 'Radio'], 'c' => []])->assertSessionHasNoErrors();
        $sub = SiteFormSubmission::withoutGlobalScopes()->latest('id')->first();
        $this->assertSame(['fwho11', 'fhow11'], collect($sub->answers)->pluck('key')->all(), 'hidden questions are not stored');

        // "Yes": the child's name is now required
        $this->send($school, $form, ['a' => ['fwho11' => 'Yes'], 'c' => []])->assertSessionHasErrors('a.fchild', null, $err);
        $this->send($school, $form, ['a' => ['fwho11' => 'Yes', 'fchild' => 'Ada'], 'c' => []])->assertSessionHasNoErrors();

        // a question that depends on a hidden question stays hidden, even if someone posts an answer for it
        $this->send($school, $form, ['a' => ['fwho11' => 'Yes', 'fchild' => 'Ada', 'fhow11' => 'Friend', 'fmore1' => 'Bo'], 'c' => []])->assertSessionHasNoErrors();
        $this->assertSame(['fwho11', 'fchild'], collect(SiteFormSubmission::withoutGlobalScopes()->latest('id')->first()->answers)->pluck('key')->all());

        // chain: No -> Friend -> the friend's name is required
        $this->send($school, $form, ['a' => ['fwho11' => 'No', 'fhow11' => 'Friend'], 'c' => []])->assertSessionHasErrors('a.fmore1', null, $err);
    }

    public function test_a_condition_that_points_at_a_later_or_missing_question_or_a_choice_that_is_not_offered_is_refused_when_saving(): void
    {
        $school = $this->school();
        $form = $this->conditionalForm($school);
        $this->actingAs($this->admin($school));
        $fields = $form->fields;

        $later = [$fields[1], $fields[0]]; // the dependent question now comes first
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'fields' => $later])->assertSessionHasErrors('fields');

        $fields[1]['show_if']['value'] = 'Maybe';
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'fields' => $fields])->assertSessionHasErrors('fields');

        $fields[1]['show_if'] = ['key' => 'fnothere', 'value' => 'Yes'];
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'fields' => $fields])->assertSessionHasErrors('fields');

        $this->assertSame('Yes', $form->fresh()->fields[1]['show_if']['value'], 'a refused save changes nothing');
    }

    public function test_the_public_form_marks_dependent_questions_for_the_page_script(): void
    {
        $school = $this->school();
        $form = $this->conditionalForm($school);
        $html = $this->get("/s/{$school->slug}/form/{$form->id}")->assertOk()->getContent();
        $this->assertStringContainsString('data-if-key="fwho11" data-if-value="Yes"', $html);
        $this->assertStringContainsString('data-sf', $html);
    }

    // ---- file uploads ----

    private function fileForm(School $school, array $attrs = []): SiteForm
    {
        $f = fn (string $key, string $type, string $label, array $extra = []) => $extra + ['key' => $key, 'label' => $label, 'type' => $type, 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false, 'show_if' => null, 'maps_to' => ''];

        return $this->makeForm($school, $attrs + ['consents' => [], 'fields' => [
            $f('fname1', 'text', 'Your name', ['required' => true]), $f('fcv111', 'file', 'Your CV', ['required' => true]),
        ]]);
    }

    public function test_an_uploaded_file_is_kept_privately_for_staff_of_that_school_only_and_removed_with_its_answer(): void
    {
        Storage::fake('private');
        $school = $this->school();
        $other = $this->school('Other School');
        $form = $this->fileForm($school);

        $html = $this->get("/s/{$school->slug}/form/{$form->id}")->getContent();
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
        $this->assertStringContainsString('type="file"', $html);

        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'fcv111' => UploadedFile::fake()->create('My CV (final).pdf', 200, 'application/pdf')], 'c' => []])->assertSessionHasNoErrors();
        $sub = SiteFormSubmission::withoutGlobalScopes()->first();
        $answer = collect($sub->answers)->firstWhere('key', 'fcv111');
        $this->assertMatchesRegularExpression('#^schools/'.$school->id.'/forms/'.$form->id.'/[a-z0-9]{40}\.pdf$#', $answer['file'], 'random name in the school\'s own folder');
        $this->assertSame('My CV (final).pdf', $answer['value']);
        Storage::disk('private')->assertExists($answer['file']);
        $this->assertFalse(Storage::disk('public')->exists($answer['file']), 'never on the public disk');

        $url = "/school/website/forms/{$form->id}/answers/{$sub->id}/file/fcv111";
        $this->actingAs($this->admin($school))->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertDownload('My CV (final).pdf');
        $this->actingAs($this->admin($school, 'teacher'))->get($url)->assertForbidden();
        $this->actingAs($this->admin($other))->get($url)->assertNotFound();
        auth()->logout();
        $this->get($url)->assertRedirect();

        // listing says there is a file but never gives the storage path
        $this->actingAs($this->admin($school))->get("/school/website/forms/{$form->id}/answers")->assertInertia(fn ($p) => $p->where('submissions.data.0.answers.1.file', true)->missing('submissions.data.0.answers.1.path'));
        $this->assertStringNotContainsString($answer['file'], $this->get("/school/website/forms/{$form->id}/answers")->getContent());

        $this->post("/school/website/forms/{$form->id}/answers/{$sub->id}/delete");
        Storage::disk('private')->assertMissing($answer['file']);
    }

    public function test_deleting_a_form_removes_its_files_too(): void
    {
        Storage::fake('private');
        $school = $this->school();
        $form = $this->fileForm($school);
        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'fcv111' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')], 'c' => []]);
        $path = SiteFormSubmission::withoutGlobalScopes()->first()->answers[1]['file'];
        Storage::disk('private')->assertExists($path);

        $this->actingAs($this->admin($school))->post("/school/website/forms/{$form->id}/delete");
        Storage::disk('private')->assertMissing($path);
    }

    public function test_only_pictures_and_documents_up_to_five_megabytes_are_accepted(): void
    {
        Storage::fake('private');
        $school = $this->school();
        $form = $this->fileForm($school);
        $try = fn ($file) => $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'fcv111' => $file], 'c' => []]);

        $try(UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id);
        $try(UploadedFile::fake()->create('page.html', 10, 'text/html'))->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id);
        $try(UploadedFile::fake()->create('script.php', 10, 'application/x-php'))->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id);
        $try(UploadedFile::fake()->image('draw.svg'))->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id);
        $try(UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'))->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id);
        $this->send($school, $form, ['a' => ['fname1' => 'Ada'], 'c' => []])->assertSessionHasErrors('a.fcv111', null, 'form'.$form->id); // required
        $this->assertSame(0, SiteFormSubmission::withoutGlobalScopes()->count());
        $this->assertSame([], Storage::disk('private')->allFiles(), 'nothing is stored for a refused post');

        $try(UploadedFile::fake()->image('photo.jpg'))->assertSessionHasNoErrors();
        $try(UploadedFile::fake()->create('letter.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))->assertSessionHasNoErrors();
        $this->assertSame(2, SiteFormSubmission::withoutGlobalScopes()->count());
    }

    public function test_a_robot_cannot_store_a_file_and_a_form_with_files_has_a_tighter_limit(): void
    {
        Storage::fake('private');
        $school = $this->school();
        $form = $this->fileForm($school);
        $this->send($school, $form, [Visitor::TRAP => 'spam', 'a' => ['fname1' => 'Bot', 'fcv111' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')], 'c' => []]);
        $this->assertSame([], Storage::disk('private')->allFiles());

        for ($i = 0; $i < 5; $i++) {
            $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'fcv111' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')], 'c' => []])->assertSessionHasNoErrors();
        }
        $this->send($school, $form, ['a' => ['fname1' => 'Ada', 'fcv111' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')], 'c' => []])->assertSessionHasErrors('form', null, 'form'.$form->id);
        $this->assertCount(5, Storage::disk('private')->allFiles(), 'the sixth post stored nothing');
    }

    public function test_a_form_can_have_at_most_three_file_questions(): void
    {
        $fields = array_map(fn ($i) => ['label' => "File $i", 'type' => 'file'], range(1, 4));
        $this->assertNotNull(FormSchema::problem(FormSchema::cleanFields($fields)));
        $this->assertNull(FormSchema::problem(FormSchema::cleanFields(array_slice($fields, 0, 3))));
    }

    // ---- sending answers to Admissions inquiries ----

    private function inquiryForm(School $school, array $attrs = []): SiteForm
    {
        $f = fn (string $key, string $type, string $label, string $map = '', array $extra = []) => $extra + ['key' => $key, 'label' => $label, 'type' => $type, 'required' => true, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false, 'show_if' => null, 'maps_to' => $map];

        return $this->makeForm($school, $attrs + ['name' => 'Visit request', 'send_to_inquiries' => true, 'consents' => [], 'fields' => [
            $f('fpar111', 'text', 'Your name', 'guardian_name'), $f('fphone1', 'phone', 'Your phone', 'guardian_phone'), $f('fmail11', 'email', 'Your email', 'guardian_email', ['required' => false]),
            $f('fkid111', 'text', 'Child\'s name', 'student_name'), $f('fclass1', 'select', 'Class', 'class_interested', ['options' => ['Nursery', 'Primary 1']]),
            $f('fgen111', 'radio', 'Gender', 'student_gender', ['options' => ['Male', 'Female']]), $f('fdob111', 'date', 'Date of birth', 'student_dob'),
            $f('fnote11', 'textarea', 'Anything else', '', ['required' => false]),
        ]]);
    }

    public function test_an_answer_can_also_become_an_admissions_inquiry_with_the_marked_questions_filling_the_right_boxes(): void
    {
        $school = $this->school();
        $admin = $this->admin($school);
        $form = $this->inquiryForm($school);

        $this->send($school, $form, ['a' => ['fpar111' => 'Mrs Obi', 'fphone1' => '08031234567', 'fmail11' => 'obi@example.com', 'fkid111' => 'Ada Obi', 'fclass1' => 'Nursery', 'fgen111' => 'Female', 'fdob111' => '2022-03-04', 'fnote11' => 'We are moving from Abuja'], 'c' => []])->assertSessionHasNoErrors();

        $inq = \App\Models\AdmissionInquiry::withoutGlobalScopes()->where('school_id', $school->id)->first();
        $this->assertSame('Mrs Obi', $inq->guardian_name);
        $this->assertSame('08031234567', $inq->guardian_phone);
        $this->assertSame('obi@example.com', $inq->guardian_email);
        $this->assertSame('Ada Obi', $inq->student_name);
        $this->assertSame('Nursery', $inq->class_interested);
        $this->assertSame('female', $inq->student_gender);
        $this->assertSame('2022-03-04', $inq->student_dob->toDateString());
        $this->assertSame('website', $inq->source);
        $this->assertStringContainsString('Visit request', $inq->notes);
        $this->assertStringContainsString('Anything else: We are moving from Abuja', $inq->notes, 'the unmarked answer goes into the notes');
        $this->assertSame($inq->id, SiteFormSubmission::withoutGlobalScopes()->first()->inquiry_id);
        $this->assertNotNull(SchoolNotification::withoutGlobalScopes()->where('user_id', $admin->id)->where('type', 'admission_inquiry')->first(), 'the office is told as with the message form');

        $this->actingAs($admin)->get("/school/website/forms/{$form->id}/answers")->assertInertia(fn ($p) => $p->where('submissions.data.0.inquiry_id', $inq->id));
        $this->get('/school/admissions/inquiries/'.$inq->id)->assertOk();
    }

    public function test_nothing_goes_to_admissions_unless_the_form_is_set_to_and_a_missing_phone_or_name_keeps_it_out(): void
    {
        $school = $this->school();
        $off = $this->inquiryForm($school, ['send_to_inquiries' => false]);
        $this->send($school, $off, ['a' => ['fpar111' => 'Mrs Obi', 'fphone1' => '08031234567', 'fkid111' => 'Ada', 'fclass1' => 'Nursery', 'fgen111' => 'Female', 'fdob111' => '2022-03-04'], 'c' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, \App\Models\AdmissionInquiry::withoutGlobalScopes()->count());
        $this->assertSame(1, SiteFormSubmission::withoutGlobalScopes()->count(), 'the answer is kept either way');

        // phone optional and left empty: the answer is still saved, no half-made inquiry
        $form = $this->inquiryForm($school);
        $fields = $form->fields;
        $fields[1]['required'] = false;
        $form->update(['fields' => $fields]);
        $this->send($school, $form, ['a' => ['fpar111' => 'Mrs Obi', 'fkid111' => 'Ada', 'fclass1' => 'Nursery', 'fgen111' => 'Female', 'fdob111' => '2022-03-04'], 'c' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, \App\Models\AdmissionInquiry::withoutGlobalScopes()->count());
        $this->assertNull(SiteFormSubmission::withoutGlobalScopes()->latest('id')->first()->inquiry_id);
    }

    public function test_saving_a_form_for_admissions_needs_the_parents_name_and_phone_marked_and_marks_must_fit_the_question_kind(): void
    {
        $school = $this->school();
        $form = $this->makeForm($school);
        $this->actingAs($this->admin($school));
        $plain = [['label' => 'Your name', 'type' => 'text'], ['label' => 'Phone', 'type' => 'phone']];
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'send_to_inquiries' => true, 'fields' => $plain])->assertSessionHasErrors('fields');
        $this->assertFalse($form->fresh()->send_to_inquiries);

        $marked = [['label' => 'Your name', 'type' => 'text', 'maps_to' => 'guardian_name'], ['label' => 'Phone', 'type' => 'phone', 'maps_to' => 'guardian_phone'], ['label' => 'Born', 'type' => 'text', 'maps_to' => 'student_dob'], ['label' => 'Other name', 'type' => 'text', 'maps_to' => 'guardian_name']];
        $this->post('/school/website/forms/'.$form->id, ['name' => 'x', 'send_to_inquiries' => true, 'fields' => $marked])->assertSessionHasNoErrors();
        $saved = $form->fresh();
        $this->assertTrue($saved->send_to_inquiries);
        $this->assertSame(['guardian_name', 'guardian_phone', '', ''], collect($saved->fields)->pluck('maps_to')->all(), 'a text box cannot be a date of birth and each target is used once');
    }

    public function test_templates_make_valid_forms(): void
    {
        $school = $this->school();
        foreach (array_keys(FormTemplates::LIST) as $key) {
            $t = FormTemplates::make($key, $school);
            $this->assertNull(FormSchema::problem($t['fields'], $t['send_to_inquiries']), "template {$key} must pass the form checks");
        }
    }
}
