<?php

namespace App\Services;

use App\Jobs\DeliverMessageBatch;
use App\Models\Guardian;
use App\Models\MessageBatch;
use App\Models\MessageDelivery;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** SMS and email blasts: who they go to, a log line per person, sending, and trying again */
class MessageService
{
    public const AUDIENCES = [
        'all_parents' => 'All parents',
        'all_students' => 'All students',
        'all_staff' => 'All staff',
        'class' => 'Parents of one class',
    ];

    /** Shown on SMS lines until a real SMS provider is connected */
    public const NO_SMS_PROVIDER = 'No SMS provider is set up yet, so this text was not sent.';

    /**
     * Everyone the blast is for: [type, id, name, contact]. The contact is the phone
     * number for SMS or the email address for email, and null when there is none.
     */
    public function recipients(int $schoolId, string $channel, string $audience, ?int $classId = null): Collection
    {
        $field = $channel === 'sms' ? 'phone' : 'email';

        if ($audience === 'all_staff') {
            return Staff::where('school_id', $schoolId)->where('status', '!=', 'resigned')->where('status', '!=', 'terminated')
                ->orderBy('first_name')->get()
                ->map(fn (Staff $s) => ['staff', $s->id, trim($s->first_name.' '.$s->last_name), $s->{$field}]);
        }

        $students = Student::where('school_id', $schoolId)->where('status', 'active')
            ->when($audience === 'class', fn ($q) => $q->where('class_id', $classId))
            ->with('guardian')->orderBy('first_name')->get();

        if ($audience === 'all_students') {
            return $students->map(fn (Student $s) => ['student', $s->id, trim($s->first_name.' '.$s->last_name), $s->{$field}]);
        }

        // Parents: each parent once, however many children they have here; a child with no parent on record is listed so the gap shows
        return $students->groupBy(fn (Student $s) => $s->guardian_id && $s->guardian ? 'g'.$s->guardian_id : 's'.$s->id)
            ->map(function (Collection $children) use ($field) {
                $guardian = $children->first()->guardian;
                $names = $children->map(fn (Student $s) => $s->first_name)->join(', ', ' and ');
                if (! $guardian) {
                    $child = $children->first();

                    return ['parent', null, 'Parent of '.trim($child->first_name.' '.$child->last_name).' (none on record)', null];
                }

                return ['parent', $guardian->id, "{$guardian->name} (parent of {$names})", $guardian->{$field}];
            })->values();
    }

    /** Write the log and start sending */
    public function send(User $user, string $channel, string $audience, ?int $classId, ?string $subject, string $body): MessageBatch
    {
        $schoolId = (int) $user->school_id;
        $label = self::AUDIENCES[$audience];
        if ($audience === 'class') {
            $label = 'Parents of '.(SchoolClass::where('school_id', $schoolId)->whereKey($classId)->value('name') ?? 'a class');
        }

        $batch = MessageBatch::create([
            'school_id' => $schoolId, 'channel' => $channel, 'audience' => $label,
            'subject' => $channel === 'email' ? $subject : null, 'body' => $body, 'sent_by' => $user->id,
        ]);

        $seen = [];
        $now = now();
        $rows = [];
        foreach ($this->recipients($schoolId, $channel, $audience, $classId) as [$type, $id, $name, $contact]) {
            $contact = $contact !== null ? trim((string) $contact) : '';
            $problem = $this->problemWith($channel, $contact);
            // The same number or address gets one message, even if two people share it (0803 111 2222 and +234 803 111 2222 are one phone)
            $key = $channel === 'sms' ? $this->phoneKey($contact) : strtolower($contact);
            if (! $problem && isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = [
                'school_id' => $schoolId, 'message_batch_id' => $batch->id, 'recipient_type' => $type, 'recipient_id' => $id,
                'recipient_name' => mb_substr($name, 0, 150), 'to' => $contact !== '' ? mb_substr($contact, 0, 150) : null,
                'status' => $problem ? 'not_sent' : 'queued', 'error' => $problem, 'attempts' => 0, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            MessageDelivery::insert($chunk);
        }

        DeliverMessageBatch::dispatch($batch->id);

        return $batch;
    }

    /** Put the failed lines back in the queue and send them again */
    public function retry(MessageBatch $batch): int
    {
        $count = $batch->deliveries()->where('status', 'failed')->update(['status' => 'queued', 'error' => null]);
        if ($count) {
            DeliverMessageBatch::dispatch($batch->id);
        }

        return $count;
    }

    /** Send every waiting line of a batch, recording what happened to each */
    public function deliver(MessageBatch $batch): void
    {
        $batch->deliveries()->where('status', 'queued')->orderBy('id')->chunkById(100, function ($lines) use ($batch) {
            foreach ($lines as $line) {
                $error = $batch->channel === 'sms' ? $this->sendSms($line->to, $batch->body) : $this->sendEmail($line->to, (string) $batch->subject, $batch->body);
                $line->update([
                    'attempts' => $line->attempts + 1,
                    'status' => $error ? 'failed' : 'sent',
                    'error' => $error ? mb_substr($error, 0, 300) : null,
                    'sent_at' => $error ? null : now(),
                ]);
            }
        });
    }

    /** Counts per status for each batch */
    public static function counts(iterable $batchIds): array
    {
        $counts = [];
        MessageDelivery::whereIn('message_batch_id', $batchIds)->get(['message_batch_id', 'status'])
            ->each(function (MessageDelivery $d) use (&$counts) {
                $counts[$d->message_batch_id][$d->status] = ($counts[$d->message_batch_id][$d->status] ?? 0) + 1;
            });

        return $counts;
    }

    /** A phone number reduced to its digits in the local form, so spacing, dashes and +234 do not make one phone look like two */
    private function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            return '0'.substr($digits, 3);
        }
        if (strlen($digits) === 10 && ! str_starts_with($digits, '0')) {
            return '0'.$digits;
        }

        return $digits;
    }

    private function problemWith(string $channel, string $contact): ?string
    {
        if ($contact === '') {
            return $channel === 'sms' ? 'No phone number on record.' : 'No email address on record.';
        }
        if ($channel === 'email' && ! filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return 'The email address on record does not look right.';
        }
        if ($channel === 'sms' && strlen(preg_replace('/\D/', '', $contact)) < 10) {
            return 'The phone number on record is too short.';
        }

        return null;
    }

    /** Returns null when sent, or the reason it was not */
    private function sendEmail(string $to, string $subject, string $body): ?string
    {
        try {
            Mail::raw($body, fn (Message $m) => $m->to($to)->subject($subject));

            return null;
        } catch (Throwable $e) {
            return 'The email server refused it: '.$e->getMessage();
        }
    }

    /** Returns null when sent, or the reason it was not. A provider (for example Termii) plugs in here. */
    private function sendSms(string $to, string $body): ?string
    {
        return self::NO_SMS_PROVIDER;
    }
}
