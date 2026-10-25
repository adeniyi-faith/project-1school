<?php

namespace App\Services;

use App\Ai\AiStyles;
use App\Models\User;

/**
 * Makes one teaching picture from a teacher's description and the style they picked (simple picture,
 * explainer diagram, black and white drawing). The picture is only handed back to the browser: nothing is
 * stored until the teacher presses "Use this picture", which attaches it to the question like an uploaded file.
 * Only the description and the style go to the AI; no student data.
 */
class ImageWriterService
{
    public function __construct(private AiService $ai) {}

    /** @return array{image: string} a data address the browser can show straight away */
    public function make(int $schoolId, User $by, string $description, ?string $style, ?string $subject = null): array
    {
        $style = AiStyles::resolve('image', $style);
        $prompt = collect([
            'Make one picture for a school lesson or exam in a Nigerian school.',
            $subject ? "Subject: {$subject}." : null,
            'What it shows: '.trim($description),
            'Style: '.$style['rule'],
            'Rules: do not put any writing, letters, numbers or labels in the picture. If people are shown, they are Nigerian and dressed in a way that suits the lesson. Nothing frightening or unsuitable for children. Show only what was asked for.',
        ])->filter()->implode("\n");

        $picture = $this->ai->askImage($schoolId, $by->id, $prompt);

        return ['image' => 'data:'.$picture['mime'].';base64,'.$picture['data']];
    }
}
