<?php

namespace App\Ai;

use App\Models\PlatformSetting;
use App\Models\SchoolSetting;

/**
 * The styles a teacher can pick for each AI task ("Short and quick", "Full and detailed" ...).
 * A teacher sees only a name and one line about each style. What the style really does (the extra
 * writing rules, which kind of model does the work, how long the answer may be) stays here and in the
 * platform owner's settings, and is never sent to the browser.
 *
 * 'tier' is 'quick' (cheapest model) or 'writing' (a stronger model for long work); see AiService.
 * Picture styles have tier 'image': they use the picture model and only add to the picture's description.
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
        'image' => [
            'default' => 'simple',
            'styles' => [
                'simple' => [
                    'label' => 'Simple picture',
                    'hint' => 'A clear, friendly picture of one thing or scene.',
                    'tier' => 'image',
                    'rule' => 'A clear, friendly, child-appropriate illustration with flat colours, a plain light background and one main subject.',
                ],
                'explainer' => [
                    'label' => 'Explainer diagram',
                    'hint' => 'A clean teaching diagram that shows how something works or its parts.',
                    'tier' => 'image',
                    'rule' => 'A clean, simple educational diagram like those in a good textbook: flat colours, clear shapes, arrows where something moves or flows, a plain white background and lots of space around each part. Show the parts clearly and keep it uncluttered.',
                ],
                'line' => [
                    'label' => 'Black and white drawing',
                    'hint' => 'A line drawing that prints well on exam papers.',
                    'tier' => 'image',
                    'rule' => 'A clear black and white line drawing with no shading and no colour, thick clean outlines and a plain white background, suitable for printing on an exam paper.',
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

    /** The style a task starts on: the school's own choice when it has made one, otherwise the built-in default */
    public static function defaultKey(string $task, ?int $schoolId = null): string
    {
        if ($schoolId) {
            $chosen = self::schoolDefaults($schoolId)[$task] ?? null;
            if (isset(self::TASKS[$task]['styles'][$chosen])) {
                return $chosen;
            }
        }

        return self::TASKS[$task]['default'];
    }

    /** @return array<string, string> task => style key, as a school saved them */
    public static function schoolDefaults(int $schoolId): array
    {
        return json_decode((string) SchoolSetting::get($schoolId, 'ai_default_styles', '{}'), true) ?: [];
    }

    /** The tasks a school can set a default style for, with the teacher-facing names only */
    public static function taskNames(): array
    {
        return ['lesson_note' => 'Lesson notes', 'questions' => 'Exam and test questions', 'image' => 'Pictures'];
    }

    /**
     * The style to use. A missing or unknown key quietly gives the task's default, so an old page
     * or a mistyped value never breaks writing. With a $schoolId the default is that school's own choice.
     * 'tier' already includes the platform owner's override.
     *
     * @return array{key: string, label: string, hint: string, tier: string, tokens: int, rule: string}
     */
    public static function resolve(string $task, ?string $key, ?int $schoolId = null): array
    {
        $styles = self::TASKS[$task]['styles'];
        $key = isset($styles[$key]) ? $key : self::defaultKey($task, $schoolId);
        $style = $styles[$key] + ['key' => $key];

        $overrides = json_decode((string) PlatformSetting::get('ai_style_tiers', '{}'), true) ?: [];
        $tier = $overrides["{$task}.{$key}"] ?? null;
        if ($style['tier'] !== 'image' && in_array($tier, ['quick', 'writing'], true)) {
            $style['tier'] = $tier;
        }

        return $style;
    }
}
