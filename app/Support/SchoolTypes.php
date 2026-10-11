<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ready-made starting setups for the kinds of schools found in Nigeria: the Nigerian national
 * curriculum, a British (Cambridge / Edexcel style) curriculum, an American one, and Montessori.
 *
 * Applying one only ADDS: classes, subjects, terms, a score setup and a grade scale the school does not
 * already have. It never deletes or changes classes, subjects, scores or results the school already has.
 * Uses the query builder on purpose, like SchoolDefaults, so it works from a seeder, a test or a web request.
 */
class SchoolTypes
{
    /** Parts of a setup the school can choose to apply */
    public const PARTS = ['classes' => 'Classes', 'subjects' => 'Subjects for each class', 'terms' => 'Term names', 'grading' => 'Score setup and grade scale'];

    /** Subjects that are practical rather than written (the rest are theory) */
    private const PRACTICAL = ['Art', 'Art and Design', 'Music', 'Drama', 'Physical Education', 'Physical & Health Education', 'Computing', 'Technology', 'Practical Life', 'Sensorial',
        'Music and Movement', 'Creative Arts & Colouring', 'Cultural & Creative Arts', 'Basic Digital Literacy', 'Digital Technologies', 'Basic Technology', 'Home Economics', 'Pre-vocational Studies',
        'Handwriting', 'Fellowship', 'Trade Subject (Entrepreneurship)', 'Design and Technology', 'Computer Studies (ICT)', 'Basic Computer Skills', 'Fine Art', 'Community Service', 'Expressive Arts and Design', 'Physical Development'];

    /** @return array<string, array> */
    public static function all(): array
    {
        $nigerianSubjects = [
            'early' => ['Number Work', 'Letter Work & Phonics', 'Rhymes & Songs', 'Health Habits', 'Social Habits', 'Creative Arts & Colouring', 'Bible Stories / Islamic Studies', 'Basic Computer Skills'],
            'primary' => ['English Language', 'Mathematics', 'Basic Science & Technology', 'Social Studies', 'Civic Education', 'Verbal Reasoning', 'Quantitative Reasoning', 'Christian Religious Studies',
                'Islamic Religious Studies', 'Pre-vocational Studies', 'Home Economics', 'Basic Digital Literacy', 'Phonics', 'Handwriting', 'Dictation', 'Literature', 'History', 'Yoruba Language', 'French',
                'Cultural & Creative Arts', 'Physical & Health Education', 'Agricultural Science'],
            'junior' => ['English Language', 'Mathematics', 'Intermediate Science', 'Basic Technology', 'Digital Technologies', 'Social and Citizenship Studies', 'History', 'Business Studies',
                'Christian Religious Studies', 'Islamic Religious Studies', 'Trade Subject (Entrepreneurship)', 'Yoruba Language', 'French', 'Agricultural Science', 'Home Economics', 'Cultural & Creative Arts', 'Physical & Health Education'],
            'senior' => ['English Language', 'Mathematics', 'Civic Education', 'Computer Studies (ICT)', 'Physics', 'Chemistry', 'Biology', 'Further Mathematics', 'Agricultural Science', 'Literature-in-English',
                'Christian Religious Studies', 'History', 'Fine Art', 'Government', 'Economics', 'Financial Accounting', 'Commerce', 'Marketing'],
        ];
        $senior = ['Science' => ['Physics', 'Chemistry', 'Biology', 'Further Mathematics', 'Agricultural Science'],
            'Arts' => ['Literature-in-English', 'Christian Religious Studies', 'History', 'Fine Art', 'Government'],
            'Commercial' => ['Government', 'Economics', 'Financial Accounting', 'Commerce', 'Marketing']];

        $ukCore = ['English', 'Mathematics', 'Science', 'Computing', 'History', 'Geography', 'Art and Design', 'Design and Technology', 'Music', 'Physical Education', 'PSHE', 'Religious Education', 'French'];
        $us = fn (array $extra) => array_merge(['English'], $extra);

        return [
            'nigerian' => [
                'label' => 'Nigerian curriculum',
                'tagline' => 'Creche to SS 3, three terms, continuous assessment and exam, WAEC grades.',
                'terms' => ['First Term', 'Second Term', 'Third Term'],
                'assessment' => 'two_ca', 'grading' => 'waec',
                'classes' => [
                    ['Creche', 'early_years', 'early'], ['Nursery 1', 'early_years', 'early'], ['Nursery 2', 'early_years', 'early'], ['Nursery 3', 'early_years', 'early'],
                    ['Primary 1', 'primary', 'primary'], ['Primary 2', 'primary', 'primary'], ['Primary 3', 'primary', 'primary'], ['Primary 4', 'primary', 'primary'], ['Primary 5', 'primary', 'primary'], ['Primary 6', 'primary', 'primary'],
                    ['JSS 1', 'junior', 'junior'], ['JSS 2', 'junior', 'junior'], ['JSS 3', 'junior', 'junior'],
                    ['SS 1', 'senior', 'senior'], ['SS 2', 'senior', 'senior'], ['SS 3', 'senior', 'senior'],
                ],
                'subjects' => $nigerianSubjects,
                'departments' => $senior,
                'arms' => [],
            ],
            'british' => [
                'label' => 'British curriculum',
                'tagline' => 'Nursery to Year 13 (Key Stages, IGCSE and A-level), Autumn, Spring and Summer terms, 9 to 1 grades.',
                'terms' => ['Autumn Term', 'Spring Term', 'Summer Term'],
                'assessment' => 'uk_style', 'grading' => 'gcse',
                'classes' => array_merge(
                    [['Nursery', 'early_years', 'eyfs'], ['Reception', 'early_years', 'eyfs']],
                    array_map(fn ($n) => ["Year {$n}", 'primary', 'ks12'], range(1, 6)),
                    array_map(fn ($n) => ["Year {$n}", 'junior', 'ks3'], range(7, 9)),
                    array_map(fn ($n) => ["Year {$n}", 'senior', 'gcse'], range(10, 11)),
                    array_map(fn ($n) => ["Year {$n}", 'senior', 'alevel', 'alevel'], range(12, 13)),
                ),
                'subjects' => [
                    'eyfs' => ['Communication and Language', 'Literacy', 'Mathematics', 'Understanding the World', 'Expressive Arts and Design', 'Physical Development', 'Personal, Social and Emotional Development'],
                    'ks12' => $ukCore,
                    'ks3' => array_merge($ukCore, ['Drama']),
                    'gcse' => ['English Language', 'English Literature', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Computer Science', 'Business Studies', 'Economics', 'History', 'Geography', 'Art and Design', 'French', 'Physical Education', 'PSHE'],
                    'alevel' => ['Mathematics', 'Further Mathematics', 'Biology', 'Chemistry', 'Physics', 'Economics', 'Business Studies', 'Computer Science', 'English Literature', 'History', 'Geography'],
                ],
                'departments' => [], 'arms' => [],
                'gradingOverride' => ['alevel' => 'alevel'],
            ],
            'american' => [
                'label' => 'American curriculum',
                'tagline' => 'Pre-K to Grade 12, four quarters, letter grades and a 4.0 GPA.',
                'terms' => ['Quarter 1', 'Quarter 2', 'Quarter 3', 'Quarter 4'],
                'assessment' => 'us_style', 'grading' => 'us_letter',
                'classes' => array_merge(
                    [['Pre-K', 'early_years', 'prek'], ['Kindergarten', 'early_years', 'prek']],
                    array_map(fn ($n) => ["Grade {$n}", 'primary', 'elem'], range(1, 5)),
                    array_map(fn ($n) => ["Grade {$n}", 'junior', 'middle'], range(6, 8)),
                    [['Grade 9', 'senior', 'g9'], ['Grade 10', 'senior', 'g10'], ['Grade 11', 'senior', 'g11'], ['Grade 12', 'senior', 'g12']],
                ),
                'subjects' => [
                    'prek' => ['Language and Literacy', 'Early Math', 'Science Discovery', 'Social Skills', 'Art', 'Music', 'Physical Education'],
                    'elem' => ['English Language Arts', 'Mathematics', 'Science', 'Social Studies', 'Art', 'Music', 'Physical Education', 'Health', 'Technology', 'Spanish'],
                    'middle' => ['English Language Arts', 'Mathematics', 'Science', 'Social Studies', 'Art', 'Music', 'Physical Education', 'Health', 'Technology', 'Spanish'],
                    'g9' => $us(['Algebra I', 'Biology', 'World History', 'Spanish', 'Physical Education', 'Computer Science']),
                    'g10' => $us(['Geometry', 'Chemistry', 'World History', 'Spanish', 'Physical Education', 'Art']),
                    'g11' => $us(['Algebra II', 'Physics', 'U.S. History', 'Spanish', 'Physical Education']),
                    'g12' => $us(['Pre-Calculus', 'Government', 'Economics', 'Computer Science', 'Art']),
                ],
                'departments' => [], 'arms' => [],
            ],
            'montessori' => [
                'label' => 'Montessori',
                'tagline' => 'Mixed-age classrooms from Toddler to Adolescent, three terms, progress shown as Introduced, Practising or Mastered.',
                'terms' => ['First Term', 'Second Term', 'Third Term'],
                'assessment' => 'observation', 'grading' => 'montessori',
                'classes' => [
                    ['Toddler Community', 'early_years', 'toddler'], ["Children's House", 'early_years', 'house'],
                    ['Lower Elementary', 'primary', 'lower'], ['Upper Elementary', 'primary', 'upper'], ['Adolescent Programme', 'junior', 'teen'],
                ],
                'subjects' => [
                    'toddler' => ['Practical Life', 'Sensorial', 'Language', 'Music and Movement'],
                    'house' => ['Practical Life', 'Sensorial', 'Language', 'Mathematics', 'Cultural Studies', 'Art', 'Music and Movement', 'Peace Education'],
                    'lower' => ['Language', 'Mathematics', 'Geometry', 'Biology and Botany', 'Geography', 'History', 'Art', 'Music', 'Practical Life', 'Peace Education'],
                    'upper' => ['Language', 'Mathematics', 'Geometry', 'Biology', 'Physical Science', 'Geography', 'History', 'Art', 'Music', 'Peace Education'],
                    'teen' => ['Language and Literature', 'Mathematics', 'Science', 'Humanities', 'Entrepreneurship', 'Art', 'Community Service'],
                ],
                'departments' => [], 'arms' => [],
            ],
            'custom' => [
                'label' => 'Something else',
                'tagline' => 'A mixed or unusual school. Nothing is added; you build your own classes, subjects and grades.',
                'terms' => null, 'assessment' => null, 'grading' => null,
                'classes' => [], 'subjects' => [], 'departments' => [], 'arms' => [],
            ],
        ];
    }

    /** One line per type for the picker */
    public static function summaries(): array
    {
        $out = [];
        foreach (self::all() as $key => $t) {
            $subjects = collect($t['classes'])->flatMap(fn ($c) => $t['subjects'][$c[2]] ?? [])->unique()->count();
            $out[] = [
                'key' => $key, 'label' => $t['label'], 'tagline' => $t['tagline'],
                'classes' => array_column($t['classes'], 0), 'subject_count' => $subjects,
                'terms' => $t['terms'], 'assessment' => $t['assessment'] ? SchoolDefaults::ASSESSMENT_PRESETS[$t['assessment']]['name'] : null,
                'grading' => $t['grading'] ? SchoolDefaults::GRADING_PRESETS[$t['grading']]['name'] : null,
            ];
        }

        return $out;
    }

    /**
     * Add this type's setup to the school. $parts picks what to apply (keys of PARTS).
     *
     * @return array{classes: int, subjects: int, lines: int, terms_renamed: int, terms_added: int, schemes: int}
     */
    public static function apply(int $schoolId, string $type, array $parts): array
    {
        $def = self::all()[$type] ?? throw new \InvalidArgumentException("Unknown school type {$type}");
        $parts = array_values(array_intersect($parts, array_keys(self::PARTS)));
        $done = ['classes' => 0, 'subjects' => 0, 'lines' => 0, 'terms_renamed' => 0, 'terms_added' => 0, 'schemes' => 0];

        DB::transaction(function () use ($schoolId, $type, $def, $parts, &$done) {
            self::setting($schoolId, 'school_type', $type);

            if (in_array('terms', $parts, true) && $def['terms']) {
                self::applyTerms($schoolId, $def['terms'], $done);
            }

            $gradeId = $assessmentId = null;
            $overrides = [];
            if (in_array('grading', $parts, true) && $def['grading']) {
                $assessmentId = self::ensureScheme($schoolId, 'assessment_schemes', SchoolDefaults::ASSESSMENT_PRESETS[$def['assessment']]['name'], fn () => SchoolDefaults::createAssessmentScheme($schoolId, $def['assessment'], false), $done);
                $gradeId = self::ensureScheme($schoolId, 'grading_schemes', SchoolDefaults::GRADING_PRESETS[$def['grading']]['name'], fn () => SchoolDefaults::createGradingScheme($schoolId, $def['grading'], false), $done);
                foreach ($def['gradingOverride'] ?? [] as $group => $preset) {
                    $overrides[$group] = self::ensureScheme($schoolId, 'grading_schemes', SchoolDefaults::GRADING_PRESETS[$preset]['name'], fn () => SchoolDefaults::createGradingScheme($schoolId, $preset, false), $done);
                }
                // The new setup becomes the school's default for classes that have no choice of their own
                DB::table('assessment_schemes')->where('school_id', $schoolId)->update(['is_default' => false]);
                DB::table('assessment_schemes')->where('id', $assessmentId)->update(['is_default' => true]);
                DB::table('grading_schemes')->where('school_id', $schoolId)->update(['is_default' => false]);
                DB::table('grading_schemes')->where('id', $gradeId)->update(['is_default' => true]);
            }

            if (! array_intersect(['classes', 'subjects'], $parts)) {
                return;
            }

            $now = now();
            // Names already in use (compared without capital letters), so nothing is made twice
            $classIds = DB::table('classes')->where('school_id', $schoolId)->whereNull('deleted_at')->pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id])->all();
            $subjectIds = DB::table('subjects')->where('school_id', $schoolId)->whereNull('deleted_at')->pluck('id', 'name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id])->all();
            $order = (int) DB::table('classes')->where('school_id', $schoolId)->whereNull('deleted_at')->max('numeric_name');
            foreach ($def['classes'] as $c) {
                [$name, $level, $group] = $c;
                $classId = $classIds[mb_strtolower($name)] ?? null;
                if (! $classId) {
                    if (! in_array('classes', $parts, true)) {
                        continue; // subjects only go to classes that exist
                    }
                    $classId = DB::table('classes')->insertGetId([
                        'school_id' => $schoolId, 'name' => $name, 'numeric_name' => ++$order, 'capacity' => 30, 'level' => $level,
                        'grading_scheme_id' => isset($c[3]) ? ($overrides[$c[3]] ?? null) : null, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $classIds[mb_strtolower($name)] = $classId;
                    $done['classes']++;
                    if ($level === 'senior' && $def['departments']) {
                        // Senior arms carry the department so students only get that department's subjects
                        foreach (array_keys($def['departments']) as $dept) {
                            DB::table('sections')->insert(['school_id' => $schoolId, 'class_id' => $classId, 'name' => $dept, 'department' => $dept, 'capacity' => 15, 'created_at' => $now, 'updated_at' => $now]);
                        }
                    }
                }
                if (in_array('subjects', $parts, true)) {
                    foreach ($def['subjects'][$group] ?? [] as $subject) {
                        self::offer($schoolId, $classId, $subject, $level === 'senior' ? self::departmentsFor($def['departments'], $subject) : null, $subjectIds, $done);
                    }
                }
            }
        });

        return $done;
    }

    private static function departmentsFor(array $departments, string $subject): ?array
    {
        if (! $departments) {
            return null;
        }
        $in = array_keys(array_filter($departments, fn ($list) => in_array($subject, $list, true)));

        return $in ?: null;
    }

    /** Make the subject if the school has none by that name, then offer it to the class */
    private static function offer(int $schoolId, int $classId, string $name, ?array $departments, array &$subjectIds, array &$done): void
    {
        $now = now();
        $subjectId = $subjectIds[mb_strtolower($name)] ?? null;
        if (! $subjectId) {
            $subjectId = DB::table('subjects')->insertGetId([
                'school_id' => $schoolId, 'class_id' => $classId, 'name' => $name, 'code' => self::code($name),
                'type' => in_array($name, self::PRACTICAL, true) ? 'practical' : 'theory', 'full_marks' => 100, 'pass_marks' => 40, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $subjectIds[mb_strtolower($name)] = $subjectId;
            $done['subjects']++;
        }
        if (! DB::table('class_subjects')->where('class_id', $classId)->where('subject_id', $subjectId)->exists()) {
            DB::table('class_subjects')->insert([
                'school_id' => $schoolId, 'class_id' => $classId, 'subject_id' => $subjectId,
                'departments' => $departments ? json_encode($departments) : null, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $done['lines']++;
        }
    }

    private static function code(string $name): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $code = count($words) > 1 ? implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 4))) : mb_substr($name, 0, 3);

        return mb_strtoupper($code);
    }

    /** A scheme with this name is reused; otherwise it is made */
    private static function ensureScheme(int $schoolId, string $table, string $name, \Closure $make, array &$done): int
    {
        $id = DB::table($table)->where('school_id', $schoolId)->whereNull('deleted_at')->where('name', $name)->value('id');
        if ($id) {
            return (int) $id;
        }
        $done['schemes']++;

        return (int) $make();
    }

    /**
     * Save the term names for years to come, rename terms in school years that have no results, invoices
     * or tests on them yet, and add terms the new setup has more of. Terms with data are never touched.
     */
    private static function applyTerms(int $schoolId, array $names, array &$done): void
    {
        self::setting($schoolId, 'term_names', json_encode($names));
        self::setting($schoolId, 'terms_per_year', (string) count($names));

        $now = now();
        foreach (DB::table('academic_years')->where('school_id', $schoolId)->whereNull('deleted_at')->pluck('id') as $yearId) {
            $terms = DB::table('terms')->where('academic_year_id', $yearId)->whereNull('deleted_at')->orderBy('sequence')->get()->keyBy('sequence');
            foreach ($names as $i => $name) {
                $term = $terms->get($i + 1);
                if (! $term) {
                    DB::table('terms')->insert(['school_id' => $schoolId, 'academic_year_id' => $yearId, 'name' => $name, 'sequence' => $i + 1, 'is_current' => false, 'created_at' => $now, 'updated_at' => $now]);
                    $done['terms_added']++;
                } elseif ($term->name !== $name && ! self::termHasData((int) $term->id)) {
                    DB::table('terms')->where('id', $term->id)->update(['name' => $name, 'updated_at' => $now]);
                    $done['terms_renamed']++;
                }
            }
        }
    }

    private static function termHasData(int $termId): bool
    {
        return DB::table('result_sheets')->where('term_id', $termId)->exists()
            || DB::table('invoices')->where('term_id', $termId)->exists()
            || DB::table('cbt_tests')->where('term_id', $termId)->exists();
    }

    private static function setting(int $schoolId, string $key, string $value): void
    {
        DB::table('school_settings')->updateOrInsert(['school_id' => $schoolId, 'key' => $key], ['value' => $value, 'group' => 'academic', 'updated_at' => now(), 'created_at' => now()]);
    }

    public static function current(int $schoolId): ?string
    {
        return DB::table('school_settings')->where('school_id', $schoolId)->where('key', 'school_type')->value('value') ?: null;
    }
}
