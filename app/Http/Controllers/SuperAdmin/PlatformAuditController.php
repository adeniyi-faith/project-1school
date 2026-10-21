<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Support\PlatformAccess;
use App\Support\PlatformAudit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** What the platform team did: who entered which school, what they changed, when, and from where. */
class PlatformAuditController extends Controller
{
    public const ACTIONS = [
        'login' => 'Signed in', 'logout' => 'Signed out', 'enter_school' => 'Entered a school', 'leave_school' => 'Left a school',
        'changed' => 'Changed something', 'exported' => 'Downloaded the log', 'viewed' => 'Looked inside a school', 'refused' => 'Was refused',
    ];

    /** The most lines one download holds */
    private const EXPORT_MAX = 50000;

    public function index(Request $request): Response
    {
        $lines = $this->filtered($request)->latest('created_at')->latest('id')->paginate(30)->withQueryString();

        return Inertia::render('SuperAdmin/Audit/Index', [
            'enabled' => PlatformAudit::enabled(),
            'lines' => [
                'data' => collect($lines->items())->map(fn (PlatformAuditLog $l) => [
                    'id' => $l->id, 'at' => $l->created_at?->toIso8601String(), 'who' => $l->user_name ?? 'Unknown', 'email' => $l->user_email, 'role' => $l->role,
                    'action' => $l->action, 'description' => $l->description, 'school' => $l->acting_school_name, 'status' => $l->status,
                    'ip' => $l->ip, 'device' => $this->device($l->user_agent), 'method' => $l->method, 'path' => $l->path, 'route' => $l->route,
                    'fields' => $l->details['fields'] ?? [], 'changes' => $l->details['changes'] ?? [],
                ])->all(),
                'meta' => ['total' => $lines->total(), 'current_page' => $lines->currentPage(), 'last_page' => $lines->lastPage(), 'from' => $lines->firstItem(), 'to' => $lines->lastItem()],
                'links' => ['prev' => $lines->previousPageUrl(), 'next' => $lines->nextPageUrl()],
            ],
            'filters' => $request->only(['user_id', 'action', 'school', 'from', 'to', 'q']),
            'actions' => collect(self::ACTIONS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'people' => User::whereHas('roles', fn ($r) => $r->whereIn('name', array_merge([PlatformAccess::OWNER], PlatformAccess::roleNames())))
                ->orWhereIn('id', PlatformAuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** A spreadsheet-ready file (CSV) of what the same filters show. The download itself is written in the log. */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->latest('created_at')->latest('id');
        $count = (clone $query)->count();

        PlatformAudit::record('exported', "Downloaded the staff activity log ({$count} lines)", ['details' => ['filters' => $request->only(['user_id', 'action', 'school', 'from', 'to', 'q'])]], $request);

        $name = 'staff-activity-'.now()->format('Y-m-d-Hi').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads names and symbols right
            fputcsv($out, ['When', 'Who', 'Email', 'Role', 'What', 'Kind', 'School', 'Result', 'Method', 'Page', 'Fields sent', 'What changed', 'IP address', 'Device']);
            $query->limit(self::EXPORT_MAX)->lazyById(500)->each(function (PlatformAuditLog $l) use ($out) {
                fputcsv($out, array_map([$this, 'safe'], [
                    $l->created_at?->format('Y-m-d H:i:s'), $l->user_name, $l->user_email, $l->role, $l->description, self::ACTIONS[$l->action] ?? $l->action,
                    $l->acting_school_name, $l->status, $l->method, $l->path, implode(', ', $l->details['fields'] ?? []), $this->changesText($l->details['changes'] ?? []),
                    $l->ip, $this->device($l->user_agent),
                ]));
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request): Builder
    {
        $f = $request->validate([
            'user_id' => 'nullable|integer', 'action' => 'nullable|string|max:40', 'school' => 'nullable|string|max:100',
            'from' => 'nullable|date', 'to' => 'nullable|date', 'q' => 'nullable|string|max:100',
        ]);

        return PlatformAuditLog::query()
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($f['school'] ?? null, fn ($q, $v) => $q->where('acting_school_name', 'like', '%'.$this->like($v).'%'))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('description', 'like', '%'.$this->like($v).'%')
                ->orWhere('path', 'like', '%'.$this->like($v).'%')
                ->orWhere('user_name', 'like', '%'.$this->like($v).'%')
                ->orWhere('ip', 'like', '%'.$this->like($v).'%')));
    }

    private function like(string $v): string
    {
        return addcslashes($v, '%_\\');
    }

    /** "Chrome on Windows"-style label from the raw browser text */
    private function device(?string $ua): string
    {
        $ua = (string) $ua;
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge', str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox', str_contains($ua, 'Chrome') => 'Chrome', str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android', str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iPhone/iPad',
            str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Mac OS') => 'Mac', str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };

        return trim($browser.($os ? " on {$os}" : ''));
    }

    private function changesText(array $changes): string
    {
        return collect($changes)->map(fn ($c) => ($c['what'] ?? '').': '.collect($c['fields'] ?? [])
            ->map(fn ($v, $k) => "{$k} ".($v['from'] ?? '—').' → '.($v['to'] ?? '—'))->implode('; '))->implode(' | ');
    }

    /** A cell that starts with = + - @ would run as a formula in Excel; a leading apostrophe stops that */
    public function safe(mixed $value): string
    {
        $text = (string) $value;

        return $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
