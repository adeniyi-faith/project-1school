<?php

namespace Database\Seeders;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LessonPlan;
use App\Models\OnlineClass;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Syllabus;
use Illuminate\Database\Seeder;

class HomeworkSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (!$school) return;

        $classes  = SchoolClass::where('school_id', $school->id)->get();
        $subjects = Subject::where('school_id', $school->id)->get();
        $staff    = Staff::where('school_id', $school->id)->where('status', 'active')->get();
        $students = Student::withoutGlobalScopes()->where('school_id', $school->id)->take(10)->get();

        if ($classes->isEmpty() || $subjects->isEmpty()) return;

        // ── Homework ──────────────────────────────────────────────────
        $homeworkData = [
            ['title' => 'Chapter 3 — Algebra Exercises',    'description' => 'Complete exercises 1–20 on page 45.', 'days' => 5],
            ['title' => 'Essay: The Water Cycle',           'description' => 'Write a 300-word essay on the water cycle.', 'days' => 7],
            ['title' => 'Grammar Worksheet B',              'description' => 'Fill in the blanks using correct tenses.', 'days' => 3],
            ['title' => 'History Map Activity',             'description' => 'Label the map of ancient civilisations.', 'days' => 4],
            ['title' => 'Science Lab Report: Osmosis',      'description' => 'Write up the lab report from last session.', 'days' => 6],
        ];

        foreach ($homeworkData as $i => $item) {
            $class   = $classes->get($i % $classes->count());
            $subject = $subjects->get($i % $subjects->count());
            $teacher = $staff->get($i % max($staff->count(), 1));

            $hw = Homework::create([
                'school_id'   => $school->id,
                'class_id'    => $class->id,
                'subject_id'  => $subject->id,
                'teacher_id'  => $teacher?->id,
                'title'       => $item['title'],
                'description' => $item['description'],
                'due_date'    => now()->addDays($item['days'])->toDateString(),
                'is_active'   => true,
            ]);

            // Add 2 sample submissions per homework
            foreach ($students->take(2) as $student) {
                HomeworkSubmission::create([
                    'school_id'    => $school->id,
                    'homework_id'  => $hw->id,
                    'student_id'   => $student->id,
                    'text_response'=> 'I have completed the assignment as required.',
                    'status'       => 'submitted',
                ]);
            }
        }

        // ── Lesson Plans ──────────────────────────────────────────────
        $planData = [
            ['title' => 'Introduction to Quadratic Equations', 'objectives' => 'Understand the standard form ax²+bx+c=0.', 'status' => 'approved'],
            ['title' => 'Photosynthesis Deep Dive',            'objectives' => 'Explain light-dependent and light-independent reactions.', 'status' => 'submitted'],
            ['title' => 'Shakespeare: Romeo & Juliet Act I',   'objectives' => 'Analyse key themes of love and conflict.', 'status' => 'draft'],
        ];

        foreach ($planData as $i => $item) {
            $class   = $classes->get($i % $classes->count());
            $subject = $subjects->get($i % $subjects->count());
            $teacher = $staff->get($i % max($staff->count(), 1));

            LessonPlan::create([
                'school_id'       => $school->id,
                'class_id'        => $class->id,
                'subject_id'      => $subject->id,
                'teacher_id'      => $teacher?->id,
                'title'           => $item['title'],
                'objectives'      => $item['objectives'],
                'content'         => 'Detailed lesson content goes here.',
                'teaching_methods'=> 'Lecture, Q&A, group work.',
                'resources'       => 'Textbook, whiteboard, worksheets.',
                'week_start'      => now()->startOfWeek()->addWeeks($i)->toDateString(),
                'status'          => $item['status'],
            ]);
        }

        // ── Syllabi ───────────────────────────────────────────────────
        $syllabusTopics = [
            ['title' => 'Numbers & Operations',  'covered' => true],
            ['title' => 'Algebra Basics',         'covered' => true],
            ['title' => 'Geometry',               'covered' => true],
            ['title' => 'Statistics',             'covered' => false],
            ['title' => 'Probability',            'covered' => false],
            ['title' => 'Trigonometry',           'covered' => false],
        ];

        foreach ($classes->take(3) as $i => $class) {
            $subject = $subjects->get($i % $subjects->count());
            $syllabus = Syllabus::create([
                'school_id'     => $school->id,
                'class_id'      => $class->id,
                'subject_id'    => $subject->id,
                'academic_year' => '2025-26',
                'title'         => "{$subject->name} Syllabus — {$class->name}",
                'topics'        => $syllabusTopics,
            ]);
            $syllabus->recalculateCompletion();
            $syllabus->save();
        }

        // ── Online Classes ────────────────────────────────────────────
        $onlineData = [
            ['title' => 'Live Math Doubt Session',    'platform' => 'zoom',         'days' => 1,  'status' => 'scheduled'],
            ['title' => 'Science Interactive Lab',    'platform' => 'google_meet',  'days' => 2,  'status' => 'scheduled'],
            ['title' => 'English Literature Review',  'platform' => 'jitsi',        'days' => -1, 'status' => 'completed'],
        ];

        foreach ($onlineData as $i => $item) {
            $class   = $classes->get($i % $classes->count());
            $subject = $subjects->get($i % $subjects->count());
            $teacher = $staff->get($i % max($staff->count(), 1));

            OnlineClass::create([
                'school_id'        => $school->id,
                'class_id'         => $class->id,
                'subject_id'       => $subject->id,
                'teacher_id'       => $teacher?->id,
                'title'            => $item['title'],
                'platform'         => $item['platform'],
                'meeting_url'      => 'https://meet.example.com/room-' . ($i + 1),
                'meeting_id'       => '88' . str_pad($i + 1, 8, '0', STR_PAD_LEFT),
                'passcode'         => 'abc' . ($i + 100),
                'scheduled_at'     => now()->addDays($item['days'])->setTime(10, 0),
                'duration_minutes' => 60,
                'status'           => $item['status'],
            ]);
        }
    }
}
