<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** The headings a school wants in every lesson note */
class LessonNoteFormat extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'sections', 'is_default'];

    protected $casts = ['sections' => 'array', 'is_default' => 'boolean'];

    /** Ready-made formats every school starts with; the first is the default */
    public const PRESETS = [
        'Standard lesson note' => [
            ['objectives', 'Behavioural objectives', 'By the end of the lesson, students should be able to … (3 to 4 measurable points)'],
            ['previous_knowledge', 'Previous knowledge', 'What the students already know that this lesson builds on'],
            ['materials', 'Instructional materials', 'Charts, objects, textbooks and anything else to bring'],
            ['reference', 'Reference books', 'Textbooks and pages used'],
            ['introduction', 'Introduction', 'How the teacher starts the lesson and gets attention'],
            ['presentation', 'Presentation', 'Step 1, Step 2, Step 3 …: the teacher\'s activities and the students\' activities'],
            ['evaluation', 'Evaluation', 'Questions to check that the objectives were met'],
            ['summary', 'Summary', 'The key points written on the board'],
            ['assignment', 'Assignment', 'Homework for the students'],
        ],
        '5E lesson plan' => [
            ['objectives', 'Learning objectives', 'What students will know or be able to do'],
            ['materials', 'Materials', 'Everything needed for the lesson'],
            ['engage', 'Engage', 'A question, story or demonstration that sparks interest'],
            ['explore', 'Explore', 'A hands-on activity students do in groups'],
            ['explain', 'Explain', 'The teacher explains the idea and the key words'],
            ['elaborate', 'Elaborate', 'Students use the idea in a new situation'],
            ['evaluate', 'Evaluate', 'How the teacher checks understanding'],
            ['assignment', 'Assignment', 'Homework for the students'],
        ],
    ];

    /** Gives a school the ready-made formats the first time it needs them */
    public static function ensureFor(int $schoolId): void
    {
        if (static::withoutGlobalScopes()->where('school_id', $schoolId)->exists()) {
            return;
        }
        $first = true;
        foreach (self::PRESETS as $name => $sections) {
            static::withoutGlobalScopes()->create([
                'school_id' => $schoolId,
                'name' => $name,
                'sections' => array_map(fn ($s) => ['key' => $s[0], 'label' => $s[1], 'hint' => $s[2]], $sections),
                'is_default' => $first,
            ]);
            $first = false;
        }
    }

    /**
     * Sections typed on the formats page, with a key made from each label. Keys stay unique
     * ("Activity", "Activity" become activity and activity_2).
     *
     * @param  array<int, array{label: string, hint?: ?string}>  $rows
     * @return array<int, array{key: string, label: string, hint: string}>
     */
    public static function withKeys(array $rows, array $existing = []): array
    {
        $byLabel = collect($existing)->keyBy(fn ($s) => mb_strtolower($s['label']));
        $used = [];

        return array_values(array_map(function ($row) use ($byLabel, &$used) {
            $label = trim($row['label']);
            // A heading that keeps its name keeps its key, so notes already written still match it
            $key = $byLabel->get(mb_strtolower($label))['key'] ?? (Str::snake(Str::ascii($label)) ?: 'section');
            $key = substr(preg_replace('/[^a-z0-9_]/', '', $key) ?: 'section', 0, 40);
            $base = $key;
            for ($n = 2; in_array($key, $used, true); $n++) {
                $key = $base.'_'.$n;
            }
            $used[] = $key;

            return ['key' => $key, 'label' => $label, 'hint' => trim((string) ($row['hint'] ?? ''))];
        }, $rows));
    }
}
