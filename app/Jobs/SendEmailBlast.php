<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Message;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array  $recipients, // list of email addresses
        public readonly string $subject,
        public readonly string $body,
        public readonly int    $schoolId,
    ) {}

    public function handle(): void
    {
        foreach ($this->recipients as $email) {
            Mail::raw($this->body, function (Message $msg) use ($email) {
                $msg->to($email)->subject($this->subject);
            });
        }
    }
}
