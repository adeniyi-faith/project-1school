<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The starting setup every school gets: terms in each school year, a score
 * setup (CA1, CA2, Exam ...) and grade scales. Schools can change all of it.
 *
 * Uses the query builder on purpose, so it works the same from a migration,
 * a seeder, or a web request, whoever is signed in.
 */
class SchoolDefaults
{
    public const TERM_NAMES = ['First Term', 'Second Term', 'Third Term', 'Fourth Term'];

    /**
     * Ready-made score setups. Each must add up to 100. The first is the default:
     * NERDC's 40% continuous assessment / 60% exam split, as two tests.
     */
    public const ASSESSMENT_PRESETS = [
        'two_ca' => [
            'name' => 'CA1 + CA2 + Exam (40/60)',
            'components' => [['First CA', 'CA1', 20], ['Second CA', 'CA2', 20], ['Examination', 'Exam', 60]],
        ],
        'three_ca' => [
            'name' => 'CA1 + CA2 + CA3 + Exam (30/70)',
            'components' => [['First CA', 'CA1', 10], ['Second CA', 'CA2', 10], ['Third CA', 'CA3', 10], ['Examination', 'Exam', 70]],
        ],
        'ca_assignment' => [
            'name' => 'Tests + Assignment + Exam (40/60)',
            'components' => [['First CA', 'CA1', 10], ['Second CA', 'CA2', 10], ['Third CA', 'CA3', 10], ['Assignment', 'Assign', 10], ['Examination', 'Exam', 60]],
        ],
        'single_ca' => [
            'name' => 'CA + Exam (30/70)',
            'components' => [['Continuous Assessment', 'CA', 30], ['Examination', 'Exam', 70]],
        ],
    ];

    /** Ready-made grade scales: [grade, min %, max %, remark, grade point] */
    public const GRADING_PRESETS = [
        'waec' => [
            'name' => 'WAEC (A1 – F9)',
            'bands' => [
                ['A1', 75, 100, 'Excellent', 5.00],
                ['B2', 70, 74, 'Very Good', 4.50],
                ['B3', 65, 69, 'Good', 4.00],
                ['C4', 60, 64, 'Credit', 3.50],
                ['C5', 55, 59, 'Credit', 3.00],
                ['C6', 50, 54, 'Credit', 2.50],
                ['D7', 45, 49, 'Pass', 2.00],
                ['E8', 40, 44, 'Pass', 1.00],
                ['F9', 0, 39, 'Fail', 0.00],
            ],
        ],
        'simple' => [
            'name' => 'Simple A – F',
            'bands' => [
                ['A', 70, 100, 'Excellent', 5.00],
                ['B', 60, 69, 'Very Good', 4.00],
                ['C', 50, 59, 'Good', 3.00],
                ['D', 45, 49, 'Fair', 2.00],
                ['E', 40, 44, 'Pass', 1.00],
                ['F', 0, 39, 'Fail', 0.00],
            ],
        ],
    ];

    /** What most Nigerian report cards rate, 1 (poor) to 5 (excellent) */
    public const BEHAVIOUR_TRAITS = [
        'affective' => ['Punctuality', 'Attendance', 'Neatness', 'Politeness', 'Honesty', 'Self-control', 'Relationship with others', 'Attentiveness'],
        'psychomotor' => ['Handwriting', 'Verbal fluency', 'Sports and games', 'Drawing and painting', 'Musical skills', 'Handling of tools'],
    ];

    /** Fill in whatever the school is missing. Safe to run more than once. */
    public static function apply(int $schoolId): void
    {
        DB::transaction(function () use ($schoolId) {
            self::ensureTerms($schoolId);
            self::ensureAssessmentScheme($schoolId);
            self::ensureGradingScheme($schoolId);
            self::ensureBehaviourTraits($schoolId);
            self::ensureReportCardDesign($schoolId);
        });
    }

    /** Give every school year that has no terms its terms (three unless the school set otherwise). */
    public static function ensureTerms(int $schoolId): void
    {
        $years = DB::table('academic_years')->where('school_id', $schoolId)->whereNull('deleted_at')->get();

        foreach ($years as $year) {
            self::createTermsFor($schoolId, $year->id);
        }

        // One term should be "current": the first term of the current year, if none is yet
        $hasCurrent = DB::table('terms')->where('school_id', $schoolId)->whereNull('deleted_at')->where('is_current', true)->exists();
        $currentYear = $years->firstWhere('is_current', true);
        if (! $hasCurrent && $currentYear) {
            DB::table('terms')->where('academic_year_id', $currentYear->id)->where('sequence', 1)->update(['is_current' => true]);
        }
    }

    public static function createTermsFor(int $schoolId, int $academicYearId): void
    {
        if (DB::table('terms')->where('academic_year_id', $academicYearId)->exists()) {
            return;
        }

        $count = (int) (DB::table('school_settings')->where('school_id', $schoolId)->where('key', 'terms_per_year')->value('value') ?: 3);
        $count = max(1, min(4, $count));
        $now = now();

        DB::table('terms')->insert(array_map(fn ($i) => [
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
            'name' => self::TERM_NAMES[$i],
            'sequence' => $i + 1,
            'is_current' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], range(0, $count - 1)));
    }

    public static function ensureAssessmentScheme(int $schoolId): void
    {
        if (DB::table('assessment_schemes')->where('school_id', $schoolId)->whereNull('deleted_at')->exists()) {
            return;
        }

        self::createAssessmentScheme($schoolId, 'two_ca', true);
    }

    public static function createAssessmentScheme(int $schoolId, string $preset, bool $isDefault = false): int
    {
        $p = self::ASSESSMENT_PRESETS[$preset];
        $now = now();
        $id = DB::table('assessment_schemes')->insertGetId([
            'school_id' => $schoolId, 'name' => $p['name'], 'is_default' => $isDefault,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('assessment_components')->insert(array_map(fn ($c, $i) => [
            'school_id' => $schoolId, 'assessment_scheme_id' => $id,
            'name' => $c[0], 'short_name' => $c[1], 'max_score' => $c[2], 'sort_order' => $i + 1,
            'created_at' => $now, 'updated_at' => $now,
        ], $p['components'], array_keys($p['components'])));

        return $id;
    }

    /**
     * A school that already typed in grades keeps them, wrapped in a default
     * scale. A school with none starts on WAEC.
     */
    public static function ensureGradingScheme(int $schoolId): void
    {
        if (DB::table('grading_schemes')->where('school_id', $schoolId)->whereNull('deleted_at')->exists()) {
            return;
        }

        $loose = DB::table('grade_scales')->where('school_id', $schoolId)->whereNull('grading_scheme_id');
        if ($loose->exists()) {
            $now = now();
            $id = DB::table('grading_schemes')->insertGetId([
                'school_id' => $schoolId, 'name' => 'School grade scale', 'is_default' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $loose->update(['grading_scheme_id' => $id]);

            return;
        }

        self::createGradingScheme($schoolId, 'waec', true);
    }

    public static function createGradingScheme(int $schoolId, string $preset, bool $isDefault = false): int
    {
        $p = self::GRADING_PRESETS[$preset];
        $now = now();
        $id = DB::table('grading_schemes')->insertGetId([
            'school_id' => $schoolId, 'name' => $p['name'], 'is_default' => $isDefault,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('grade_scales')->insert(array_map(fn ($b, $i) => [
            'school_id' => $schoolId, 'grading_scheme_id' => $id,
            'grade' => $b[0], 'min_marks' => $b[1], 'max_marks' => $b[2], 'remarks' => $b[3], 'gpa' => $b[4],
            'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now,
        ], $p['bands'], array_keys($p['bands'])));

        return $id;
    }

    public static function ensureBehaviourTraits(int $schoolId): void
    {
        if (DB::table('behaviour_traits')->where('school_id', $schoolId)->whereNull('deleted_at')->exists()) {
            return;
        }

        $now = now();
        $rows = [];
        foreach (self::BEHAVIOUR_TRAITS as $domain => $names) {
            foreach ($names as $i => $name) {
                $rows[] = ['school_id' => $schoolId, 'name' => $name, 'domain' => $domain, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        DB::table('behaviour_traits')->insert($rows);
    }

    /**
     * A default report card design with a Class Teacher and a Principal who comment and sign.
     * Returns [class teacher signer id, principal signer id] (null when a school changed them).
     */
    public static function ensureReportCardDesign(int $schoolId): array
    {
        $design = DB::table('report_card_designs')->where('school_id', $schoolId)->whereNull('deleted_at')
            ->orderByDesc('is_default')->orderBy('id')->first();
        $now = now();

        if (! $design) {
            $id = DB::table('report_card_designs')->insertGetId([
                'school_id' => $schoolId, 'name' => 'Standard', 'is_default' => true, 'template' => 'classic',
                'primary_color' => '#312e81', 'accent_color' => '#4f46e5', 'font_size' => 'normal', 'paper' => 'a4',
                'term_title' => 'Report Card', 'session_title' => 'Full-Year Report Card', 'options' => null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ([['Class Teacher', 'marks.entry'], ['Principal', 'results.publish']] as $i => [$label, $permission]) {
                DB::table('report_card_signers')->insert([
                    'school_id' => $schoolId, 'report_card_design_id' => $id, 'label' => $label, 'has_comment' => true,
                    'writer_permission' => $permission, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $design = (object) ['id' => $id];
        }

        $signers = DB::table('report_card_signers')->where('report_card_design_id', $design->id)->orderBy('sort_order')->get();

        return [
            $signers->firstWhere('writer_permission', 'marks.entry')?->id,
            $signers->firstWhere('writer_permission', 'results.publish')?->id,
        ];
    }
}
