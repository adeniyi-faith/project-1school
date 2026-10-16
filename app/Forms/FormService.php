<?php

namespace App\Forms;

use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\SiteForm;
use App\Models\SiteFormSubmission;
use App\Models\User;
use App\Newsletter\NewsletterService;
use App\Newsletter\NewsletterSettings;
use App\Website\Extensions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/** What happens after a visitor sends a custom form: store it, tell the staff, and (if asked and allowed) start the newsletter sign-up */
class FormService
{
    public function __construct(private NewsletterService $newsletter) {}

    /** Whether this form may offer "also send me the newsletter": the school wants it, the extension is usable and an email service is ready */
    public function newsletterOffered(School $school, SiteForm $form): bool
    {
        return $form->newsletter_optin
            && Extensions::enabled($school->id, 'newsletter')
            && NewsletterSettings::resolve($school->id) !== null
            && collect($form->fields)->contains('type', 'email');
    }

    /** @param array $posted the validated visitor input: ['a' => answers, 'c' => privacy ticks, 'join_newsletter' => ...] */
    public function store(School $school, SiteForm $form, array $posted, string $ip, ?string $page): SiteFormSubmission
    {
        $answers = FormSchema::answers($form->fields, (array) ($posted['a'] ?? []));
        $join = $this->newsletterOffered($school, $form) && ! empty($posted['join_newsletter']);

        $sub = new SiteFormSubmission([
            'form_id' => $form->id, 'form_name' => $form->name, 'answers' => $answers,
            'consents' => FormSchema::consentsGiven($form->consents ?? [], (array) ($posted['c'] ?? [])),
            'newsletter_asked' => $join, 'ip' => $ip, 'page' => $page ? mb_substr($page, 0, 300) : null, 'status' => 'new',
        ]);
        $sub->school_id = $school->id;
        $sub->save();

        if ($join) {
            $email = collect($answers)->first(fn ($a) => $a['type'] === 'email' && $a['value'] !== '')['value'] ?? null;
            if ($email) {
                try {
                    // the same double opt-in as everywhere: they are added only after they confirm from the email
                    $this->newsletter->subscribe($school, $email, null, [], $ip, $page);
                } catch (Throwable $e) {
                    report($e);
                }
            }
        }

        $this->tell($school, $form, $sub);

        return $sub;
    }

    /** A bell notice for staff who can read the answers, and an email to the address the school chose. A failure here never loses the answer. */
    private function tell(School $school, SiteForm $form, SiteFormSubmission $sub): void
    {
        $first = collect($sub->answers)->filter(fn ($a) => $a['value'] !== '')->take(2)->map(fn ($a) => $a['label'].': '.$a['value'])->implode(' · ');
        try {
            User::permission('forms.view')->where('school_id', $school->id)->get()->each(fn ($u) => SchoolNotification::create([
                'school_id' => $school->id, 'user_id' => $u->id, 'type' => 'form_submission',
                'title' => 'New answer: '.$form->name, 'body' => Str::limit($first, 140),
                'data' => ['url' => '/school/website/forms/'.$form->id.'/answers'], 'channel' => 'in-app',
            ]));
        } catch (Throwable $e) {
            report($e);
        }

        if ($form->notify_email) {
            try {
                $lines = collect($sub->answers)->map(fn ($a) => $a['label'].': '.($a['value'] === '' ? '(not answered)' : $a['value']))->implode("\n");
                $body = "Someone filled in \"{$form->name}\" on the {$school->name} website.\n\n{$lines}\n\nAll answers: ".url('/school/website/forms/'.$form->id.'/answers');
                Mail::raw($body, fn ($m) => $m->to($form->notify_email)->subject('New answer: '.$form->name));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
