<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Ai\AiFailure;
use App\Http\Controllers\Controller;
use App\Models\AiUsage;
use App\Models\PlatformSetting;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\AiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** The platform owner's AI (Gemini) settings: the key, the models, prices, and each school's allowance */
class AiController extends Controller
{
    public function __construct(private AiService $ai) {}

    public function index(): Response
    {
        $settings = $this->ai->settings();
        $since = now()->startOfMonth();
        $usage = AiUsage::withoutGlobalScopes()->where('created_at', '>=', $since)->get(['school_id', 'cost_naira', 'ok'])->groupBy('school_id');

        return Inertia::render('SuperAdmin/Ai/Index', [
            'settings' => $settings,
            'knownModels' => array_keys(AiService::PRICES),
            'prices' => AiService::PRICES,
            'schools' => School::orderBy('name')->get(['id', 'name', 'status'])->map(function (School $s) use ($settings, $usage) {
                $state = $this->ai->schoolState($s->id, $settings);

                return [
                    'id' => $s->id, 'name' => $s->name, 'status' => $s->status,
                    'blocked' => $state['blocked'], 'school_off' => $state['school_off'],
                    'limit' => $state['limit'], 'custom_limit' => $state['custom_limit'],
                    'used' => $state['used'], 'requests' => $usage->get($s->id)?->count() ?? 0,
                ];
            }),
            'month' => $since->format('F Y'),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => 'boolean',
            'api_key' => 'nullable|string|min:20|max:200',
            'remove_key' => 'boolean',
            'model_writing' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9.\-]*$/'],
            'model_quick' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9.\-]*$/'],
            'price_writing_in' => 'nullable|numeric|min:0|max:1000',
            'price_writing_out' => 'nullable|numeric|min:0|max:1000',
            'price_quick_in' => 'nullable|numeric|min:0|max:1000',
            'price_quick_out' => 'nullable|numeric|min:0|max:1000',
            'default_limit' => 'required|numeric|min:0|max:100000000',
            'naira_per_dollar' => 'required|numeric|min:1|max:100000',
        ], ['model_writing.regex' => 'Type the model name as Google writes it, e.g. gemini-3.8-flash.', 'model_quick.regex' => 'Type the model name as Google writes it, e.g. gemini-3.5-flash-lite.']);

        $key = trim((string) ($data['api_key'] ?? ''));
        if ($key !== '') {
            try {
                $this->ai->check($key, $data['model_quick']);
            } catch (AiFailure $e) {
                throw ValidationException::withMessages(['api_key' => 'Google did not accept this key: '.$e->getMessage()]);
            }
            $this->ai->saveKey($key);
        } elseif ($request->boolean('remove_key')) {
            $this->ai->saveKey(null);
        }
        if ($request->boolean('enabled') && ! $this->ai->key()) {
            throw ValidationException::withMessages(['api_key' => 'Add a Gemini key before switching AI on.']);
        }

        PlatformSetting::set('ai_enabled', $request->boolean('enabled') ? '1' : '0', 'ai');
        foreach (['model_writing', 'model_quick', 'price_writing_in', 'price_writing_out', 'price_quick_in', 'price_quick_out', 'default_limit', 'naira_per_dollar'] as $field) {
            PlatformSetting::set("ai_{$field}", isset($data[$field]) && $data[$field] !== '' ? (string) $data[$field] : null, 'ai');
        }

        return back()->with('success', 'AI settings saved.');
    }

    /** Ask Google whether the saved key and both models answer */
    public function check(): RedirectResponse
    {
        $settings = $this->ai->settings();
        if (! $settings['has_key']) {
            return back()->with('error', 'Add a Gemini key first.');
        }
        foreach ($settings['models'] as $model) {
            try {
                $this->ai->check($this->ai->key(), $model);
            } catch (AiFailure $e) {
                return back()->with('error', "{$model}: ".$e->getMessage());
            }
        }

        return back()->with('success', 'The key works with '.implode(' and ', array_unique($settings['models'])).'.');
    }

    /** One school: switch its AI off, or give it its own monthly allowance */
    public function saveSchool(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'blocked' => 'boolean',
            'limit' => 'nullable|numeric|min:0|max:100000000',
        ]);
        SchoolSetting::set($school->id, 'ai_blocked', $request->boolean('blocked') ? '1' : '0', 'ai');
        SchoolSetting::set($school->id, 'ai_limit', isset($data['limit']) ? (string) $data['limit'] : null, 'ai');

        return back()->with('success', "AI settings for {$school->name} saved.");
    }
}
