<?php

namespace App\Forms;

use App\Models\AdmissionInquiry;
use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\SiteForm;
use App\Models\SiteFormSubmission;
use App\Models\User;
use App\Newsletter\NewsletterService;
use App\Newsletter\NewsletterSettings;
use App\Website\Extensions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
        $visible = FormSchema::visible($form->fields, (array) ($posted['a'] ?? []));
        $answers = FormSchema::answers($form->fields, (array) ($posted['a'] ?? []), $visible, fn (UploadedFile $file, string $key) => $this->keepFile($school, $form, $file));
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

        if ($form->send_to_inquiries) {
            $this->addToAdmissions($school, $form, $sub);
        }

        $this->tell($school, $form, $sub);

        return $sub;
    }

    /** Whether a form takes files, so it can use a stricter limit and the right kind of post */
    public static function hasFiles(SiteForm $form): bool
    {
        return collect($form->fields)->contains('type', 'file');
    }

    /** Keeps an upload on the private disk, in the school's own folder, under a random name. The visitor's file name is only kept as a label. */
    private function keepFile(School $school, SiteForm $form, UploadedFile $file): array
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $ext = in_array($ext, explode(',', FormSchema::FILE_TYPES), true) ? $ext : 'bin';
        $path = $file->storeAs("schools/{$school->id}/forms/{$form->id}", Str::lower(Str::random(40)).'.'.$ext, 'private');
        $name = trim(preg_replace('/[^\p{L}\p{N} ._()-]+/u', '', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?? '');
        $name = Str::limit($name !== '' ? $name : 'file', 80, '').'.'.$ext;

        return ['path' => $path, 'name' => $name, 'size' => (int) $file->getSize()];
    }

    /** Takes every stored file of the given answers off the private disk (used when an answer or a whole form is deleted) */
    public static function forgetFiles(iterable $submissions): void
    {
        foreach ($submissions as $sub) {
            foreach ($sub->answers ?? [] as $a) {
                if (! empty($a['file'])) {
                    try {
                        Storage::disk('private')->delete($a['file']);
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            }
        }
    }

    /**
     * Also makes an Admissions inquiry from the answers, the same kind the website message form makes, so the office follows it up in
     * the usual place. Questions the school marked as the parent's name, phone and so on fill the matching boxes; every other answer is
     * written into the inquiry's notes. A failure here never loses the answer.
     */
    private function addToAdmissions(School $school, SiteForm $form, SiteFormSubmission $sub): void
    {
        try {
            $by = collect($form->fields)->keyBy('key');
            $mapped = [];
            $notes = [];
            foreach ($sub->answers as $a) {
                $map = $by[$a['key']]['maps_to'] ?? '';
                if ($map !== '' && $a['value'] !== '') {
                    $mapped[$map] = $a['value'];
                } elseif ($a['value'] !== '' && $a['type'] !== 'tick') {
                    $notes[] = $a['label'].': '.($a['type'] === 'file' ? $a['value'].' (open it from the form answers)' : $a['value']);
                } elseif ($a['type'] === 'tick' && $a['value'] === 'Yes') {
                    $notes[] = $a['label'].': Yes';
                }
            }
            if (empty($mapped['guardian_name']) || empty($mapped['guardian_phone'])) {
                return; // an optional name or phone left empty: the answer stays in the form answers only
            }
            $gender = Str::lower($mapped['student_gender'] ?? '');
            $inquiry = AdmissionInquiry::create([
                'school_id' => $school->id, 'source' => 'website',
                'student_name' => Str::limit($mapped['student_name'] ?? $form->name, 150, ''), 'class_interested' => Str::limit($mapped['class_interested'] ?? 'Not stated', 50, ''),
                'guardian_name' => Str::limit($mapped['guardian_name'], 150, ''), 'guardian_phone' => Str::limit($mapped['guardian_phone'], 25, ''), 'guardian_email' => $mapped['guardian_email'] ?? null,
                'student_gender' => in_array($gender, ['male', 'female', 'other'], true) ? $gender : null, 'student_dob' => $mapped['student_dob'] ?? null,
                'notes' => Str::limit('From the website form "'.$form->name.'".'.($notes ? "\n".implode("\n", $notes) : ''), 1900, ''),
            ]);
            $sub->forceFill(['inquiry_id' => $inquiry->id])->save();

            app(\App\Services\WebhookService::class)->dispatch($school->id, 'inquiry.created', [
                'id' => $inquiry->id, 'reference' => $inquiry->fresh()->reference, 'student_name' => $inquiry->student_name, 'guardian_name' => $inquiry->guardian_name,
                'guardian_phone' => $inquiry->guardian_phone, 'guardian_email' => $inquiry->guardian_email, 'message' => $inquiry->notes, 'source' => 'website',
            ]);
            User::permission('students.view')->where('school_id', $school->id)->get()->each(fn ($u) => SchoolNotification::create([
                'school_id' => $school->id, 'user_id' => $u->id, 'type' => 'admission_inquiry',
                'title' => 'Website form: '.$form->name.' from '.$inquiry->guardian_name, 'body' => 'Phone: '.$inquiry->guardian_phone,
                'data' => ['url' => '/school/admissions/inquiries/'.$inquiry->id], 'channel' => 'in-app',
            ]));
        } catch (Throwable $e) {
            report($e);
        }
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
