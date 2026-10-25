<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Ai\AiStyles;
use App\Models\AiUsage;
use App\Models\SchoolSetting;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** A school's own AI switches (all of it, or single tools) and what it has used this month */
class AiSettingsController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function index(Request $request): Response
    {
        $sid = $request->user()->school_id;
        $state = $this->ai->schoolState($sid);
        $recent = AiUsage::with('user:id,name')->where('created_at', '>=', now()->startOfMonth())->latest('id')->limit(50)->get();

        return Inertia::render('SchoolAdmin/Settings/Ai', [
            'state' => $state,
            'features' => collect(AiService::FEATURES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'on' => ! in_array($key, $state['tools_off'], true)])->values(),
            'byFeature' => $recent->groupBy('feature')->map(fn ($rows, $f) => ['label' => AiService::FEATURES[$f] ?? $f, 'requests' => $rows->count(), 'cost' => round($rows->sum('cost_naira'), 2)])->values(),
            'recent' => $recent->take(15)->map(fn (AiUsage $u) => [
                'id' => $u->id, 'when' => $u->created_at->toIso8601String(), 'who' => $u->user?->name, 'feature' => AiService::FEATURES[$u->feature] ?? $u->feature,
                'cost' => round($u->cost_naira, 2), 'ok' => $u->ok, 'error' => $u->error,
            ])->values(),
            'taskStyles' => collect(AiStyles::taskNames())->map(fn ($label, $task) => [
                'task' => $task, 'label' => $label, 'styles' => AiStyles::choices($task), 'current' => AiStyles::defaultKey($task, $sid),
            ])->values(),
            'canEdit' => $request->user()->can('settings.edit'),
            'month' => now()->format('F Y'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'on' => 'boolean',
            'tools_off' => 'array',
            'tools_off.*' => [Rule::in(array_keys(AiService::FEATURES))],
            'default_styles' => 'array',
        ]);
        $sid = $request->user()->school_id;
        SchoolSetting::set($sid, 'ai_off', $request->boolean('on') ? '0' : '1', 'ai');
        SchoolSetting::set($sid, 'ai_tools_off', json_encode(array_values(array_unique($data['tools_off'] ?? []))), 'ai');

        // Keep only known tasks and known styles; anything else is dropped
        $styles = collect($data['default_styles'] ?? [])
            ->filter(fn ($key, $task) => isset(AiStyles::TASKS[$task]['styles'][$key]))->all();
        SchoolSetting::set($sid, 'ai_default_styles', json_encode($styles), 'ai');

        return back()->with('success', $request->boolean('on') ? 'AI settings saved.' : 'AI is now off for your school. Nobody here can use it until it is switched back on.');
    }
}
