<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\MessageDelivery;
use App\Models\OnlinePayment;
use App\Models\School;
use App\Models\SchoolNote;
use App\Models\SchoolSubscription;
use App\Models\StaffDocument;
use App\Models\StudentDocument;
use App\Models\User;
use App\Support\SetupChecklist;

/**
 * Everything the platform team wants to know about one school on a single page ("School 360").
 * Read-only: it only looks, never changes anything. Every query names the school itself,
 * because the platform owner is not tied to any one school.
 */
class SchoolOverviewService
{
    public function build(School $school): array
    {
        $id = $school->id;

        return [
            'logins'    => $this->logins($id),
            'payments'  => $this->payments($id),
            'storage'   => $this->storage($id),
            'contact'   => $this->contact($school),
            'checklist' => $this->checklist($school),
            'problems'  => $this->problems($id),
            'notes'     => $this->notes($id),
        ];
    }

    private function logins(int $id): array
    {
        $users = User::where('school_id', $id)->whereNotNull('last_login_at');
        $last = (clone $users)->orderByDesc('last_login_at')->first();

        return [
            'last_at'      => $last?->last_login_at?->toIso8601String(),
            'last_by'      => $last?->name,
            'active_7days' => (clone $users)->where('last_login_at', '>=', now()->subDays(7))->count(),
            'never'        => User::where('school_id', $id)->whereNull('last_login_at')->count(),
            'admins'       => User::where('school_id', $id)->role('school-admin')->orderBy('name')->get(['id', 'name', 'email', 'last_login_at'])
                ->map(fn (User $u) => ['name' => $u->name, 'email' => $u->email, 'last_login_at' => $u->last_login_at?->toIso8601String()])->all(),
        ];
    }

    private function payments(int $id): array
    {
        $subs = SchoolSubscription::where('school_id', $id)->with('package')->latest('start_date')->get();

        return [
            'total'  => (float) $subs->sum('amount_paid'),
            'recent' => $subs->take(5)->map(fn (SchoolSubscription $s) => [
                'id'      => $s->id,
                'package' => $s->package?->name ?? 'Plan removed',
                'amount'  => (float) $s->amount_paid,
                'method'  => $s->payment_method,
                'date'    => $s->start_date?->toDateString(),
                'status'  => $s->status,
            ])->values()->all(),
        ];
    }

    /** Uploaded documents only (students and staff). Other files are not counted yet. */
    private function storage(int $id): array
    {
        $bytes = (int) StudentDocument::withoutGlobalScopes()->where('school_id', $id)->sum('file_size')
            + (int) StaffDocument::withoutGlobalScopes()->where('school_id', $id)->sum('file_size');

        return ['bytes' => $bytes];
    }

    private function contact(School $school): array
    {
        $deliveries = MessageDelivery::withoutGlobalScopes()->where('school_id', $school->id)->where('created_at', '>=', now()->subDays(30));

        return [
            'email'          => $school->email,
            'has_email'      => filled($school->email),
            // Custom school web addresses are not built yet, so there is nothing to check
            'domain'         => null,
            'messages_30d'   => (clone $deliveries)->count(),
            'failed_30d'     => (clone $deliveries)->where('status', 'failed')->count(),
        ];
    }

    /** Uses the same list the school's own dashboard shows, so the two always agree */
    private function checklist(School $school): array
    {
        $list = SetupChecklist::for($school->id);

        return ['items' => array_map(fn ($i) => ['key' => $i['key'], 'label' => $i['title'], 'done' => $i['done']], $list['items']), 'done' => $list['done'], 'total' => $list['total']];
    }

    /** The latest things that went wrong for this school: AI failures, texts/emails that failed, online payments needing a look */
    private function problems(int $id): array
    {
        $list = collect();

        AiUsage::withoutGlobalScopes()->where('school_id', $id)->where('ok', false)->latest()->limit(5)->get()
            ->each(fn ($r) => $list->push(['kind' => 'AI', 'text' => $r->error ?: 'An AI request failed', 'at' => $r->created_at]));

        MessageDelivery::withoutGlobalScopes()->where('school_id', $id)->where('status', 'failed')->latest()->limit(5)->get()
            ->each(fn ($r) => $list->push(['kind' => 'Message', 'text' => ($r->error ?: 'A message failed to send').' ('.$r->to.')', 'at' => $r->created_at]));

        OnlinePayment::withoutGlobalScopes()->where('school_id', $id)->whereIn('status', ['failed', 'needs_review'])->latest()->limit(5)->get()
            ->each(fn ($r) => $list->push(['kind' => 'Payment', 'text' => 'Online payment '.$r->reference.' is '.str_replace('_', ' ', $r->status), 'at' => $r->created_at]));

        return $list->sortByDesc('at')->take(8)->map(fn ($p) => [
            'kind' => $p['kind'], 'text' => (string) str($p['text'])->limit(160), 'at' => $p['at']?->toIso8601String(),
        ])->values()->all();
    }

    private function notes(int $id): array
    {
        return SchoolNote::withoutGlobalScopes()->where('school_id', $id)->with('author:id,name')
            ->orderByDesc('pinned')->latest()->limit(50)->get()
            ->map(fn (SchoolNote $n) => [
                'id' => $n->id, 'body' => $n->body, 'pinned' => $n->pinned,
                'author' => $n->author?->name ?? 'Former staff', 'at' => $n->created_at?->toIso8601String(),
            ])->all();
    }
}
