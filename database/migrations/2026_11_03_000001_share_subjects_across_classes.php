<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One subject for many classes. Until now every class had its own copy of each subject ("Mathematics" in
 * Primary 1, another "Mathematics" in Primary 2 ...). This adds:
 *  - classes.level: early years, primary, junior secondary or senior secondary (guessed from the class name);
 *  - sections.department: an arm's department (Science, Arts, Commercial ...); sections are shown as "arms";
 *  - class_subjects: which classes offer which subject, for which departments, with the class's own score setup if it had one;
 * and then merges same-named copies in different classes into one subject. Every row that pointed at a copy
 * (scores, results, marks, timetable, homework ...) is pointed at the merged subject, the copies are soft-deleted,
 * and each merge is written to subject_merges so down() can split them again.
 */
return new class extends Migration
{
    /** On SQLite, changing subjects.class_id rebuilds the table; outside a transaction the rebuild can switch off foreign keys so child rows are not cascaded away */
    public $withinTransaction = false;

    /** Tables with a subject_id, and how to tell which class a row belongs to (for splitting back) */
    private const REFERENCES = [
        'timetables' => 'class_id', 'homework' => 'class_id', 'lesson_plans' => 'class_id', 'syllabi' => 'class_id',
        'online_classes' => 'class_id', 'schemes_of_work' => 'class_id', 'cbt_tests' => 'class_id', 'exam_papers' => 'class_id',
        'marks' => 'exam', 'subject_scores' => 'sheet', 'term_results' => 'sheet', 'cbt_banks' => null,
    ];

    public function up(): void
    {
        Schema::table('classes', fn (Blueprint $t) => $t->string('level', 20)->nullable()->after('numeric_name'));
        Schema::table('sections', fn (Blueprint $t) => $t->string('department', 60)->nullable()->after('name'));

        Schema::create('class_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $t->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->json('departments')->nullable(); // empty: every student in the class takes it
            $t->foreignId('assessment_scheme_id')->nullable()->constrained()->nullOnDelete(); // this class's own score setup for it
            $t->timestamps();
            $t->unique(['class_id', 'subject_id']);
        });

        Schema::create('subject_merges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('from_subject_id'); // the copy that was merged away
            $t->unsignedBigInteger('to_subject_id');
            $t->unsignedBigInteger('class_id');
            $t->timestamps();
        });

        $classColumn = collect(Schema::getColumns('subjects'))->firstWhere('name', 'class_id');
        if ($classColumn && ! $classColumn['nullable']) {
            Schema::table('subjects', fn (Blueprint $t) => $t->unsignedBigInteger('class_id')->nullable()->change());
        }

        DB::transaction(function () {
            foreach (DB::table('classes')->whereNull('deleted_at')->get(['id', 'name']) as $class) {
                if ($level = self::guessLevel($class->name)) {
                    DB::table('classes')->where('id', $class->id)->update(['level' => $level]);
                }
            }

            // Arms already named after a department ("Science", "SS 1 Arts") get it
            foreach (DB::table('sections')->whereNull('deleted_at')->get(['id', 'name']) as $arm) {
                if ($department = self::guessDepartment($arm->name)) {
                    DB::table('sections')->where('id', $arm->id)->update(['department' => $department]);
                }
            }

            $now = now();
            $subjects = DB::table('subjects')->whereNull('deleted_at')->whereNotNull('class_id')->orderBy('id')->get();
            foreach ($subjects as $s) {
                DB::table('class_subjects')->insert(['school_id' => $s->school_id, 'class_id' => $s->class_id, 'subject_id' => $s->id, 'created_at' => $now, 'updated_at' => $now]);
            }

            $groups = $subjects->groupBy(fn ($s) => implode('|', [$s->school_id, mb_strtolower(trim(preg_replace('/\s+/', ' ', $s->name))), $s->type, (float) $s->full_marks, (float) $s->pass_marks]));
            foreach ($groups as $group) {
                $this->merge($group->values());
            }
        });
    }

    /** One group of same-named subjects: the first is kept, copies in other classes are merged into it */
    private function merge($group): void
    {
        $keeper = $group->first();
        $classes = [$keeper->class_id];
        $merged = collect([$keeper]);

        foreach ($group->slice(1) as $copy) {
            // Two subjects with the same name in the same class stay separate
            if (in_array($copy->class_id, $classes)) {
                continue;
            }
            foreach (array_keys(self::REFERENCES) as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'subject_id')) {
                    DB::table($table)->where('subject_id', $copy->id)->update(['subject_id' => $keeper->id]);
                }
            }
            DB::table('class_subjects')->where('subject_id', $copy->id)->update(['subject_id' => $keeper->id]);
            DB::table('subject_merges')->insert(['school_id' => $keeper->school_id, 'from_subject_id' => $copy->id, 'to_subject_id' => $keeper->id,
                'class_id' => $copy->class_id, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('subjects')->where('id', $copy->id)->update(['deleted_at' => now()]);
            $classes[] = $copy->class_id;
            $merged->push($copy);
        }

        // A score setup chosen for one copy stays with that copy's class
        if ($merged->count() > 1 && $merged->pluck('assessment_scheme_id')->unique()->count() > 1) {
            foreach ($merged as $m) {
                DB::table('class_subjects')->where('class_id', $m->class_id)->where('subject_id', $keeper->id)->update(['assessment_scheme_id' => $m->assessment_scheme_id]);
            }
            DB::table('subjects')->where('id', $keeper->id)->update(['assessment_scheme_id' => null]);
        }
    }

    /** early_years, primary, junior or senior from names such as "Nursery 2", "Basic 4", "JSS 1", "SS 3" */
    public static function guessLevel(string $name): ?string
    {
        $n = mb_strtolower($name);

        return match (true) {
            (bool) preg_match('/cr[eè]che|play ?group|pre-?school|pre-?nursery|nursery|kindergarten|\bkg\b|reception|pre-?k\b|toddler/', $n) => 'early_years',
            (bool) preg_match('/\bjss\b|\bjs\s*\d|junior sec|basic\s*(7|8|9)\b|year\s*(7|8|9)\b|grade\s*(7|8|9)\b/', $n) => 'junior',
            (bool) preg_match('/\bs{2,3}\s*\d|\bsss?\b|senior sec|year\s*1[0-2]\b|grade\s*1[0-2]\b/', $n) => 'senior',
            (bool) preg_match('/primary|\bpry\b|basic\s*[1-6]\b|year\s*[1-6]\b|grade\s*[1-6]\b|class\s*[1-6]\b/', $n) => 'primary',
            default => null,
        };
    }

    public static function guessDepartment(string $name): ?string
    {
        $n = mb_strtolower($name);

        return match (true) {
            (bool) preg_match('/\bscien(ce|ces|tific)\b/', $n) => 'Science',
            (bool) preg_match('/\b(arts?|humanities)\b/', $n) => 'Arts',
            (bool) preg_match('/\b(commercial|commerce|business)\b/', $n) => 'Commercial',
            (bool) preg_match('/\btechnical\b/', $n) => 'Technical',
            default => null,
        };
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (DB::table('subject_merges')->orderByDesc('id')->get() as $m) {
                DB::table('subjects')->where('id', $m->from_subject_id)->update(['deleted_at' => null]);
                foreach (self::REFERENCES as $table => $how) {
                    if (! $how || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'subject_id')) {
                        continue;
                    }
                    $rows = DB::table($table)->where('subject_id', $m->to_subject_id);
                    match ($how) {
                        'class_id' => $rows->where('class_id', $m->class_id),
                        'exam' => $rows->whereIn('exam_id', DB::table('exams')->where('class_id', $m->class_id)->select('id')),
                        'sheet' => $rows->whereIn('result_sheet_id', DB::table('result_sheets')->where('class_id', $m->class_id)->select('id')),
                    };
                    $rows->update(['subject_id' => $m->from_subject_id]);
                }
            }
            // A kept subject whose own score setup moved onto its class line gets it back
            foreach (DB::table('class_subjects')->whereNotNull('assessment_scheme_id')->get() as $line) {
                DB::table('subjects')->where('id', $line->subject_id)->where('class_id', $line->class_id)->update(['assessment_scheme_id' => $line->assessment_scheme_id]);
            }
        });

        Schema::dropIfExists('subject_merges');
        Schema::dropIfExists('class_subjects');
        Schema::table('sections', fn (Blueprint $t) => $t->dropColumn('department'));
        Schema::table('classes', fn (Blueprint $t) => $t->dropColumn('level'));
        // subjects.class_id stays nullable: subjects made since may belong to no single class
    }
};
