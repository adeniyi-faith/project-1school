<?php

namespace App\Ai;

use App\Models\PlatformSetting;

/**
 * The styles a teacher can pick for each AI task ("Short and quick", "Full and detailed" ...).
 * A teacher sees only a name and one line about each style. What the style really does (the extra
 * writing rules, which kind of model does the work, how long the answer may be) stays here and in the
 * platform owner's settings, and is never sent to the browser.
 *
 * 'tier' is 'quick' (cheapest model) or 'writing' (a stronger model for long work); see AiService.
 * The platform owner can move any style to the other tier with the "ai_style_tiers" setting.
 */
class AiStyles
{
    public const TASKS = [
        'lesson_note' => [
            'default' => 'standard',
            'styles' => [
                'short' => [
                    'label' => 'Short and quick',
                    'hint' => 'A brief outline you can fill out yourself.',
                    'tier' => 'quick', 'tokens' => 4000,
                    'rule' => 'Keep every heading brief: two to four short lines or steps. Give the main points only.',
                ],
                'standard' => [
                    'label' => 'Standard',
                    'hint' => 'A complete note for an ordinary lesson.',
                    'tier' => 'writing', 'tokens' => 12000,
                    'rule' => '',
                ],
                'detailed' => [
                    'label' => 'Full and detailed',
                    'hint' => 'More worked examples, board work and class questions.',
                    'tier' => 'writing', 'tokens' => 16000,
                    'rule' => 'Be thorough. Give worked examples with every step, what to write on the board, questions to ask the class with their answers, and a short summary.',
                ],
                'activity' => [
                    'label' => 'Hands-on and activity based',
                    'hint' => 'Pupils learn by doing, in pairs or groups.',
                    'tier' => 'writing', 'tokens' => 12000,
                    'rule' => 'Build the lesson around activities the pupils do themselves, in pairs or small groups, using cheap local materials. Keep teacher talk short.',
                ],
            ],
        ],
        'questions' => [
            'default' => 'exam',
            'styles' => [
                'quiz' => [
                    'label' => 'Quick class quiz',
                    'hint' => 'Short, direct questions that check what was just taught.',
                    'tier' => 'quick', 'tokens' => 16000,
                    'rule' => 'Keep the questions short and direct. They check that pupils remember and understand what was just taught.',
                ],
                'exam' => [
                    'label' => 'Exam style',
                    'hint' => 'Like WAEC, NECO and school exams.',
                    'tier' => 'writing', 'tokens' => 16000,
                    'rule' => '',
                ],
                'thinking' => [
                    'label' => 'Make pupils think',
                    'hint' => 'Real-life problems that need reasoning, not memory.',
                    'tier' => 'writing', 'tokens' => 16000,
                    'rule' => 'Set questions that need reasoning and application to everyday Nigerian situations, not just recall. Still keep each question answerable from what the class has been taught.',
                ],
            ],
        ],
    ];

    /** What the teacher sees: key, name and one line. Nothing else leaves the server. */
    public static function choices(string $task): array
    {
        return collect(self::TASKS[$task]['styles'] ?? [])
            ->map(fn ($s, $key) => ['key' => $key, 'label' => $s['label'], 'hint' => $s['hint']])
            ->values()->all();
    }

    public static function defaultKey(string $task): string
    {
        return self::TASKS[$task]['default'];
    }

    /**
     * The style to use. A missing or unknown key quietly gives the task's default, so an old page
     * or a mistyped value never breaks writing. 'tier' already includes the platform owner's override.
     *
     * @return array{key: string, label: string, hint: string, tier: string, tokens: int, rule: string}
     */
    public static function resolve(string $task, ?string $key): array
    {
        $styles = self::TASKS[$task]['styles'];
        $key = isset($styles[$key]) ? $key : self::defaultKey($task);
        $style = $styles[$key] + ['key' => $key];

        $overrides = json_decode((string) PlatformSetting::get('ai_style_tiers', '{}'), true) ?: [];
        $tier = $overrides["{$task}.{$key}"] ?? null;
        if (in_array($tier, ['quick', 'writing'], true)) {
            $style['tier'] = $tier;
        }

        return $style;
    }
}
