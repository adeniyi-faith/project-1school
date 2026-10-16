<?php

namespace App\Forms;

use App\Models\School;

/** Ready-made starting points for a new form. The school can change every question afterwards. */
class FormTemplates
{
    public const LIST = [
        'blank' => 'Start empty',
        'prospectus' => 'Prospectus request',
        'job' => 'Job application',
        'survey' => 'Parent survey',
        'rsvp' => 'Event sign-up',
    ];

    /** The wording of the usual privacy tick, so schools do not have to write it */
    public static function privacyText(School $school): string
    {
        return 'I agree that '.$school->name.' may keep and use the details I give here to reply to me. I have read the Privacy Policy.';
    }

    /** Wording a school can add with one tap */
    public static function consentPresets(School $school): array
    {
        return [
            ['label' => 'Privacy Policy', 'text' => self::privacyText($school), 'required' => true],
            ['label' => 'Allowed to contact me', 'text' => 'I am happy for the school to contact me about this by phone, email or WhatsApp.', 'required' => false],
            ['label' => 'I am a parent or guardian', 'text' => 'I confirm that I am the parent or legal guardian of the child I am writing about.', 'required' => true],
            ['label' => 'Keep for later', 'text' => 'You may keep my details for future opportunities at the school.', 'required' => false],
        ];
    }

    public static function make(string $template, School $school): array
    {
        $f = fn (string $type, string $label, array $extra = []) => $extra + ['key' => '', 'label' => $label, 'type' => $type, 'required' => false, 'help' => '', 'placeholder' => '', 'options' => [], 'half' => false];
        $privacy = ['text' => self::privacyText($school), 'required' => true];

        [$name, $intro, $button, $thanks, $fields, $consents] = match ($template) {
            'prospectus' => ['Prospectus request', 'Leave your details and the office will send you our prospectus.', 'Send me the prospectus', 'Thank you. The office will send the prospectus to you soon.', [
                $f('text', 'Your name', ['required' => true, 'maps_to' => 'guardian_name']), $f('email', 'Email address', ['required' => true, 'half' => true, 'maps_to' => 'guardian_email']), $f('phone', 'Phone number', ['required' => true, 'half' => true, 'maps_to' => 'guardian_phone']),
                $f('text', 'Class your child may join', ['maps_to' => 'class_interested']),
            ], [$privacy]],
            'job' => ['Job application', 'Tell us about yourself. We will contact people we would like to meet.', 'Send my application', 'Thank you. We have received your application.', [
                $f('text', 'Full name', ['required' => true]), $f('email', 'Email address', ['required' => true, 'half' => true]), $f('phone', 'Phone number', ['required' => true, 'half' => true]),
                $f('text', 'Position you are applying for', ['required' => true]), $f('number', 'Years of teaching or work experience', ['half' => true]),
                $f('select', 'Highest qualification', ['options' => ['OND / NCE', 'HND', "Bachelor's degree", "Master's degree", 'Doctorate', 'Other'], 'half' => true]),
                $f('textarea', 'Tell us about yourself', ['required' => true, 'help' => 'Your experience, subjects and why you would like to work with us.']),
                $f('file', 'Your CV (optional)', ['help' => 'A PDF or Word file.']),
            ], [$privacy, ['text' => 'You may keep my details for future vacancies.', 'required' => false]]],
            'survey' => ['Parent survey', 'Your honest answers help us improve. It takes about two minutes.', 'Send my answers', 'Thank you for your feedback.', [
                $f('radio', 'Overall, how happy are you with the school?', ['required' => true, 'options' => ['Very happy', 'Happy', 'Neutral', 'Unhappy']]),
                $f('checkboxes', 'Which areas should we improve first?', ['options' => ['Teaching', 'Communication with parents', 'Facilities', 'Safety', 'Fees and payments', 'Activities and clubs']]),
                $f('textarea', 'Anything else you would like to tell us?'),
            ], []],
            'rsvp' => ['Event sign-up', 'Tell us you are coming so we can plan.', 'Reserve my place', 'Thank you. We have reserved your place.', [
                $f('text', 'Your name', ['required' => true]), $f('phone', 'Phone number', ['required' => true, 'half' => true]), $f('number', 'How many people are coming', ['required' => true, 'half' => true]),
                $f('textarea', 'Anything we should know (for example access or food needs)'),
            ], [$privacy]],
            default => ['New form', '', 'Send', 'Thank you. We have received your answers.', [$f('text', 'Your name', ['required' => true])], []],
        };

        // a prospectus request is an enquiry, so it also lands in Admissions; the other forms stay in their own answers screen
        return ['name' => $name, 'intro' => $intro, 'button_text' => $button, 'success_message' => $thanks, 'fields' => FormSchema::cleanFields($fields), 'consents' => FormSchema::cleanConsents($consents), 'send_to_inquiries' => $template === 'prospectus'];
    }
}
