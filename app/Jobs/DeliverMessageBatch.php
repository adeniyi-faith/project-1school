<?php

namespace App\Jobs;

use App\Models\MessageBatch;
use App\Services\MessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Sends the waiting lines of one SMS or email blast and records the result of each */
class DeliverMessageBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public readonly int $batchId) {}

    public function handle(MessageService $messages): void
    {
        $batch = MessageBatch::withoutGlobalScopes()->find($this->batchId);
        if ($batch) {
            $messages->deliver($batch);
        }
    }
}
