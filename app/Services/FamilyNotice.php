<?php

namespace App\Services;

use App\Models\AdmissionInquiry;
use App\Models\School;
use App\Models\Student;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Plain emails to a family at the steps of admission: application received, place offered or declined,
 * and welcome. A failed email never stops the work it belongs to. Texts will plug in here once the school
 * has an SMS provider (see MessageService::sendSms).
 */
class FamilyNotice
{
    /** The page where the family checks their application (public, no login) */
    public static function statusUrl(School $school, ?AdmissionInquiry $inquiry = null): string
    {
        return route('public.admission.status', $school).($inquiry?->reference ? '?reference='.urlencode($inquiry->reference) : '');
    }

    /** @return bool whether an email was handed to the mail server */
    public function applicationReceived(AdmissionInquiry $inquiry, School $school): bool
    {
        return $this->send($inquiry->guardian_email, "We have your application to {$school->name}", [
            "Dear {$inquiry->guardian_name},",
            "Thank you for applying to {$school->name} for {$inquiry->student_name} ({$inquiry->class_interested}).",
            "Your reference is {$inquiry->reference}. You can check where your application stands at any time:",
            self::statusUrl($school, $inquiry),
            'The admissions office will contact you soon.',
        ], $school);
    }

    public function offer(AdmissionInquiry $inquiry, School $school): bool
    {
        return $this->send($inquiry->guardian_email, "A place for {$inquiry->student_name} at {$school->name}", [
            "Dear {$inquiry->guardian_name},",
            "We are pleased to offer {$inquiry->student_name} a place at {$school->name}.",
            'Please accept the offer on this page so we can plan for your child. Your reference is '.$inquiry->reference.':',
            self::statusUrl($school, $inquiry),
            'The school office will tell you about fees and what to bring.',
        ], $school);
    }

    public function decline(AdmissionInquiry $inquiry, School $school, ?string $note): bool
    {
        return $this->send($inquiry->guardian_email, "Your application to {$school->name}", array_filter([
            "Dear {$inquiry->guardian_name},",
            "Thank you for applying to {$school->name} for {$inquiry->student_name}. We are sorry that we cannot offer a place at this time.",
            $note ? 'Note from the school: '.$note : null,
            'Please contact the school office if you would like to talk about it.',
        ]), $school);
    }

    /** Sent after enrolment. It never contains the password: the school office hands that over. */
    public function welcome(Student $student, School $school, ?string $email, ?string $parentName, ?string $username): bool
    {
        $lines = ["Dear ".($parentName ?: 'parent').",", "Welcome to {$school->name}! {$student->full_name} is now enrolled (admission number {$student->admission_no})."];
        if ($username) {
            $lines[] = "You can sign in to the parent portal with your email or username: {$username}";
            $lines[] = 'The school office will give you your password.';
            $lines[] = url('/login');
        }

        return $this->send($email, "Welcome to {$school->name}", $lines, $school);
    }

    /** @param  list<string>  $lines */
    private function send(?string $to, string $subject, array $lines, School $school): bool
    {
        if (blank($to) || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $body = implode("\n\n", $lines)."\n\n".$school->name.($school->phone ? "\n".$school->phone : '');

        try {
            Mail::raw($body, fn (Message $m) => $m->to($to)->subject($subject));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
