<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsBlast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array  $recipients, // list of phone numbers
        public readonly string $message,
        public readonly int    $schoolId,
    ) {}

    public function handle(): void
    {
        // TODO: integrate Twilio / Vonage SDK
        // $client = new \Twilio\Rest\Client(config('services.twilio.sid'), config('services.twilio.token'));
        // foreach ($this->recipients as $phone) {
        //     $client->messages->create($phone, ['from' => config('services.twilio.from'), 'body' => $this->message]);
        // }

        Log::info('SMS Blast dispatched', [
            'school_id'  => $this->schoolId,
            'recipients' => count($this->recipients),
            'preview'    => substr($this->message, 0, 80),
        ]);
    }
}
