<?php

namespace App\Services;

use App\Ai\AiFailure;
use App\Ai\GeminiClient;
use App\Models\AiUsage;
use App\Models\PlatformSetting;
use App\Models\SchoolSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

/**
 * The AI (Google Gemini) for every school. The platform owner sets the key, the models and each school's
 * monthly allowance; each school can switch the AI, or single AI tools, off for itself. Every request is
 * logged with its cost, and a school that has used its allowance is refused until the next month.
 * What the AI writes is always a draft for a person to check.
 */
class AiService
{
    /** The AI tools, as schools see them. Later tools are added here. */
    public const FEATURES = [
        'result_comments' => 'Report card comments',
        'lesson_notes' => 'Lesson notes and schemes of work',
        'questions' => 'Question writer',
    ];

    /** Two kinds of model: one for long careful writing, one for short quick jobs */
    public const DEFAULT_MODELS = ['writing' => 'gemini-3.8-flash', 'quick' => 'gemini-3.5-flash-lite'];

    /** US dollars per million tokens [sent, received], Google's paid prices in October 2026. The platform owner can change them. */
    public const PRICES = [
        'gemini-3.8-flash' => [0.75, 3.75],
        'gemini-3.6-flash' => [0.75, 3.75],
        'gemini-3.5-flash-lite' => [0.30, 2.50],
        'gemini-3.1-flash-lite' => [0.25, 1.50],
        'gemini-3-flash-preview' => [0.50, 3.00],
        'gemini-3.1-pro-preview' => [2.00, 12.00],
    ];

    public const DEFAULT_LIMIT_NAIRA = 5000;

    public const DEFAULT_NAIRA_PER_DOLLAR = 1600;

    public function __construct(private GeminiClient $gemini) {}

    /** The platform owner's settings, without the key itself */
    public function settings(): array
    {
        $s = PlatformSetting::allFor('ai');
        $models = [
            'writing' => ($s['ai_model_writing'] ?? '') ?: self::DEFAULT_MODELS['writing'],
            'quick' => ($s['ai_model_quick'] ?? '') ?: self::DEFAULT_MODELS['quick'],
        ];
        $prices = [];
        foreach ($models as $tier => $model) {
            $known = self::PRICES[$model] ?? self::PRICES[self::DEFAULT_MODELS['writing']];
            $prices[$tier] = [
                'in' => is_numeric($s["ai_price_{$tier}_in"] ?? null) ? (float) $s["ai_price_{$tier}_in"] : $known[0],
                'out' => is_numeric($s["ai_price_{$tier}_out"] ?? null) ? (float) $s["ai_price_{$tier}_out"] : $known[1],
            ];
        }
        $key = $this->key();

        return [
            'enabled' => ($s['ai_enabled'] ?? '0') === '1',
            'has_key' => $key !== null,
            'key_hint' => $key ? '…'.substr($key, -4) : null,
            'models' => $models,
            'prices' => $prices,
            'default_limit' => is_numeric($s['ai_default_limit'] ?? null) ? (float) $s['ai_default_limit'] : self::DEFAULT_LIMIT_NAIRA,
            'naira_per_dollar' => is_numeric($s['ai_naira_per_dollar'] ?? null) ? (float) $s['ai_naira_per_dollar'] : self::DEFAULT_NAIRA_PER_DOLLAR,
        ];
    }

    public function key(): ?string
    {
        $stored = PlatformSetting::get('ai_api_key');
        if (! $stored) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            return null;
        }
    }

    public function saveKey(?string $key): void
    {
        PlatformSetting::set('ai_api_key', $key ? Crypt::encryptString($key) : null, 'ai');
    }

    /** Where one school stands: whether it may use the AI, why not, its tools, and its money for this month */
    public function schoolState(int $schoolId, ?array $settings = null): array
    {
        $settings ??= $this->settings();
        $s = SchoolSetting::where('school_id', $schoolId)->whereIn('key', ['ai_blocked', 'ai_limit', 'ai_off', 'ai_tools_off'])->pluck('value', 'key');
        $limit = is_numeric($s['ai_limit'] ?? null) ? (float) $s['ai_limit'] : $settings['default_limit'];
        $used = $this->usedThisMonth($schoolId);
        $toolsOff = json_decode($s['ai_tools_off'] ?? '[]', true) ?: [];

        $reason = match (true) {
            ! $settings['enabled'] || ! $settings['has_key'] => 'AI is not available on this platform yet.',
            ($s['ai_blocked'] ?? '0') === '1' => 'AI has been switched off for this school by the platform owner.',
            ($s['ai_off'] ?? '0') === '1' => 'Your school has switched AI off. An admin can switch it on under Settings → AI Assistant.',
            $limit <= 0 || $used >= $limit => 'Your school has used this month\'s AI allowance. It starts again on the 1st.',
            default => null,
        };

        return [
            'platform_ready' => $settings['enabled'] && $settings['has_key'],
            'blocked' => ($s['ai_blocked'] ?? '0') === '1',
            'school_off' => ($s['ai_off'] ?? '0') === '1',
            'tools_off' => array_values(array_intersect($toolsOff, array_keys(self::FEATURES))),
            'limit' => round($limit, 2),
            'custom_limit' => is_numeric($s['ai_limit'] ?? null),
            'used' => round($used, 2),
            'reason' => $reason,
        ];
    }

    /** Null when this school may use this tool now; otherwise the reason it cannot */
    public function unavailable(int $schoolId, string $feature): ?string
    {
        $state = $this->schoolState($schoolId);
        if ($state['reason']) {
            return $state['reason'];
        }

        return in_array($feature, $state['tools_off'], true)
            ? 'Your school has switched off AI for '.strtolower(self::FEATURES[$feature] ?? $feature).'.'
            : null;
    }

    public function usedThisMonth(int $schoolId): float
    {
        return (float) AiUsage::withoutGlobalScopes()->where('school_id', $schoolId)->where('created_at', '>=', now()->startOfMonth())->sum('cost_naira');
    }

    /**
     * Ask the AI. $tier picks the model ('writing' or 'quick'). With a $schema the answer is decoded JSON, otherwise text.
     * Refuses (as a form error on "ai") when the school may not use the tool, and logs every request it sends.
     */
    public function ask(int $schoolId, ?int $userId, string $feature, string $tier, string $system, string $prompt, ?array $schema = null, int $maxTokens = 8192, array $files = []): mixed
    {
        if ($why = $this->unavailable($schoolId, $feature)) {
            throw ValidationException::withMessages(['ai' => $why]);
        }
        $settings = $this->settings();
        $model = $settings['models'][$tier] ?? $settings['models']['writing'];
        $price = $settings['prices'][$tier] ?? $settings['prices']['writing'];
        $log = fn (array $tokens, ?string $error) => AiUsage::create([
            'school_id' => $schoolId, 'user_id' => $userId, 'feature' => $feature, 'model' => $model,
            'input_tokens' => $tokens['input_tokens'], 'output_tokens' => $tokens['output_tokens'],
            'cost_naira' => $this->cost($price, $tokens, $settings['naira_per_dollar']), 'ok' => $error === null, 'error' => $error ? mb_strimwidth($error, 0, 300) : null,
        ]);

        try {
            $answer = $this->gemini->generate((string) $this->key(), $model, $system, $prompt, $schema, $maxTokens, $files);
        } catch (AiFailure $e) {
            $log($e->tokens, $e->getMessage());
            throw ValidationException::withMessages(['ai' => $e->getMessage()]);
        }

        if (! $schema) {
            $log($answer, null);

            return trim($answer['text']);
        }
        $data = json_decode($this->stripFence($answer['text']), true);
        if (! is_array($data)) {
            $log($answer, $answer['finish'] === 'MAX_TOKENS' ? 'Answer was cut off' : 'Answer was not valid JSON');
            throw ValidationException::withMessages(['ai' => $answer['finish'] === 'MAX_TOKENS'
                ? 'The AI answer was too long and got cut off. Try fewer at a time.'
                : 'The AI answer could not be read. Try again.']);
        }
        $log($answer, null);

        return $data;
    }

    /** A tiny request to prove the key and model work. Not logged against any school. Returns the reply or throws AiFailure. */
    public function check(string $key, string $model): string
    {
        return trim($this->gemini->generate($key, $model, 'You answer in one short line.', 'Reply with the words: Key works.', null, 256)['text']);
    }

    /** Naira for a request: tokens at the model's dollar price per million, at the platform's exchange rate */
    public function cost(array $price, array $tokens, float $nairaPerDollar): float
    {
        $dollars = $tokens['input_tokens'] / 1_000_000 * $price['in'] + $tokens['output_tokens'] / 1_000_000 * $price['out'];

        return round($dollars * $nairaPerDollar, 4);
    }

    /** Some answers come wrapped in ```json fences even when JSON was asked for */
    private function stripFence(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $text, $m)) {
            return $m[1];
        }

        return $text;
    }
}
