<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GradingService;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'greenfield-academy')->firstOrFail();
        $sid    = $school->id;

        // ── Grade Scale ───────────────────────────────────────────────
        if (GradeScale::where('school_id', $sid)->doesntExist()) {
            foreach (GradingService::defaultScales() as $scale) {
                GradeScale::create(array_merge($scale, ['school_id' => $sid]));
            }
            $this->command->info('Grade scale seeded.');
        }

        $grading = new GradingService($sid);

        // ── Exams for first 3 classes ─────────────────────────────────
        $classes = SchoolClass::where('school_id', $sid)->orderBy('numeric_name')->take(3)->get();
        $examTypes = [
            ['name' => 'Unit Test 1 — 2026',    'type' => 'unit_test', 'start' => '2026-01-15', 'end' => '2026-01-18', 'status' => 'completed'],
            ['name' => 'Mid Term Exam — 2026',   'type' => 'mid_term',  'start' => '2026-03-01', 'end' => '2026-03-07', 'status' => 'completed'],
            ['name' => 'Final Exam — 2026',      'type' => 'final',     'start' => '2026-11-01', 'end' => '2026-11-10', 'status' => 'draft'],
        ];

        $markCount = 0;
        foreach ($classes as $class) {
            $subjects = Subject::where('class_id', $class->id)->get();
            $students = Student::where('class_id', $class->id)->where('status', 'active')->get();

            foreach ($examTypes as $et) {
                $exam = Exam::firstOrCreate(
                    ['school_id' => $sid, 'class_id' => $class->id, 'name' => $et['name']],
                    ['type' => $et['type'], 'start_date' => $et['start'], 'end_date' => $et['end'], 'status' => $et['status']]
                );

                if ($et['status'] === 'draft') continue; // no marks for draft

                foreach ($students as $student) {
                    foreach ($subjects as $subject) {
                        $mo      = rand(30, 100);
                        $graded  = $grading->calculate($mo, $subject->full_marks);

                        Mark::firstOrCreate(
                            ['exam_id' => $exam->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                            [
                                'school_id'      => $sid,
                                'marks_obtained' => $mo,
                                'grade'          => $graded['grade'],
                                'gpa'            => $graded['gpa'],
                                'is_absent'      => false,
                            ]
                        );
                        $markCount++;
                    }
                }
            }
        }

        $this->command->info("Seeded " . (count($examTypes) * $classes->count()) . " exams and {$markCount} mark records.");
    }
}
