<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CbtAttempt;
use App\Models\CbtTest;
use Illuminate\Http\JsonResponse;

/** The pages of a test sitting (before, during, after), shared by students and entrance-exam applicants */
trait ShowsCbtSitting
{
    protected function startProps(CbtTest $test, string $base): array
    {
        $test->load('sections', 'subject:id,name');

        return [
            'base' => $base,
            'test' => [
                'id' => $test->id, 'title' => $test->title, 'subject' => $test->subject?->name, 'instructions' => $test->instructions,
                'duration_minutes' => $test->duration_minutes, 'questions' => $test->questionTotal(),
                'sections' => $test->sections->map(fn ($s) => ['title' => $s->title, 'count' => $s->question_count]),
                'needs_code' => (bool) $test->access_code, 'open' => $test->isOpen(),
                'opens_at' => $test->opens_at->toIso8601String(), 'closes_at' => $test->closes_at?->toIso8601String(),
                'max_page_leaves' => $test->max_page_leaves,
            ],
        ];
    }

    protected function examProps(CbtTest $test, CbtAttempt $attempt, string $base, array $person, string $images = '/cbt/images'): array
    {
        $test->load('sections');

        return [
            'base' => $base,
            'test' => ['id' => $test->id, 'title' => $test->title, 'sections' => $test->sections->pluck('title'), 'max_page_leaves' => $test->max_page_leaves],
            'person' => $person,
            'questions' => $this->cbt->paper($attempt, $images),
            'answers' => (object) ($attempt->answers ?? []),
            'flags' => $attempt->flags ?? [],
            'secondsLeft' => $attempt->secondsLeft(),
            'pageLeaves' => $attempt->page_leaves,
        ];
    }

    protected function doneProps(CbtTest $test, CbtAttempt $attempt, string $base): array
    {
        $test->load('sections');

        return [
            'base' => $base,
            'test' => ['id' => $test->id, 'title' => $test->title, 'show_score' => $test->show_score],
            'result' => [
                'status' => $attempt->status, 'ended_reason' => $attempt->ended_reason,
                'answered' => count($attempt->answers ?? []), 'questions' => count($attempt->paper),
                'score' => $test->show_score ? $attempt->score : null, 'max_score' => $test->show_score ? $attempt->max_score : null,
                'sections' => $test->show_score ? $test->sections->values()->map(fn ($s, $i) => [
                    'title' => $s->title, 'score' => $attempt->section_scores[$i]['score'] ?? 0, 'max' => $attempt->section_scores[$i]['max'] ?? 0,
                ]) : [],
            ],
        ];
    }

    protected function state(CbtAttempt $attempt): JsonResponse
    {
        $this->cbt->settle($attempt);

        return response()->json(['finished' => $attempt->isFinished(), 'seconds_left' => $attempt->secondsLeft(), 'page_leaves' => $attempt->page_leaves]);
    }
}
