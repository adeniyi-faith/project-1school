<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\MessageBatch;
use App\Models\MessageDelivery;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** The sent-messages log: every blast, and for each one who it reached */
class MessageLogController extends Controller
{
    public function index(Request $request): Response
    {
        $batches = MessageBatch::with('sender:id,name')
            ->when(in_array($request->channel, ['sms', 'email'], true), fn ($q) => $q->where('channel', $request->channel))
            ->latest('id')->paginate(20)->withQueryString();
        $counts = MessageService::counts($batches->pluck('id'));

        $batches->through(fn (MessageBatch $b) => $this->row($b, $counts[$b->id] ?? []));

        return Inertia::render('SchoolAdmin/Communication/SentMessages', [
            'batches' => $batches,
            'filters' => ['channel' => $request->channel],
        ]);
    }

    public function show(Request $request, MessageBatch $batch): Response
    {
        $status = in_array($request->status, MessageDelivery::STATUSES, true) ? $request->status : null;
        $deliveries = $batch->deliveries()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('recipient_name', 'like', "%{$s}%")->orWhere('to', 'like', "%{$s}%")))
            ->orderBy('id')
            ->paginate(50, ['id', 'recipient_type', 'recipient_name', 'to', 'status', 'error', 'attempts', 'sent_at'])->withQueryString();

        return Inertia::render('SchoolAdmin/Communication/SentMessage', [
            'batch' => $this->row($batch->load('sender:id,name'), MessageService::counts([$batch->id])[$batch->id] ?? []) + ['body' => $batch->body],
            'deliveries' => $deliveries,
            'filters' => ['status' => $status, 'search' => (string) $request->search],
            'canRetry' => $request->user()->can($batch->channel === 'sms' ? 'sms.send' : 'email.send'),
        ]);
    }

    public function retry(Request $request, MessageService $messages, MessageBatch $batch): RedirectResponse
    {
        abort_unless($request->user()->can($batch->channel === 'sms' ? 'sms.send' : 'email.send'), 403);
        $count = $messages->retry($batch);

        return back()->with($count ? 'success' : 'error', $count ? "Tried {$count} failed message(s) again. The list below shows the new result." : 'There are no failed messages to try again.');
    }

    private function row(MessageBatch $b, array $counts): array
    {
        return [
            'id' => $b->id,
            'channel' => $b->channel,
            'audience' => $b->audience,
            'subject' => $b->subject,
            'preview' => mb_strimwidth($b->body, 0, 90, '…'),
            'sent_by' => $b->sender?->name,
            'created_at' => $b->created_at?->toIso8601String(),
            'counts' => array_merge(array_fill_keys(MessageDelivery::STATUSES, 0), $counts),
            'total' => array_sum($counts),
        ];
    }
}
