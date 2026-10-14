<?php

namespace Tests\Feature;

use App\Models\CbtBank;
use App\Models\CbtQuestion;
use App\Models\CbtTest;
use App\Models\CbtTestSection;
use App\Models\ExamPaper;
use App\Models\SchemeOfWork;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Syllabus;
use Tests\Feature\Security\SecurityTestCase;

/** Setting an exam from the topics already taught: the taught list, test sections limited to topics, and a paper's scope */
class ExamScopeTest extends SecurityTestCase
{
    public function test_taught_topics_come_from_schemes_and_syllabi_and_limit_a_test_section_and_a_paper(): void
    {
        $school = $this->makeSchool('Scope School');
        $teacher = $this->makeUser($school, 'teacher');
        $this->actingAs($teacher);
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $subject = Subject::create(['school_id' => $school->id, 'class_id' => $class->id, 'name' => 'Mathematics', 'code' => 'MTH2']);
        SchemeOfWork::create(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'title' => 'Maths JSS 2', 'weeks' => [
            ['week' => 1, 'topic' => 'Number bases', 'details' => '', 'taught' => true],
            ['week' => 2, 'topic' => 'Indices', 'details' => ''],
        ]]);
        Syllabus::create(['school_id' => $school->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'academic_year' => '2025/26', 'title' => 'Maths syllabus', 'topics' => [
            ['title' => 'Fractions', 'covered' => true], ['title' => 'Algebra', 'covered' => false],
        ]]);

        $sources = $this->getJson("/school/cbt/taught?class_id={$class->id}&subject_id={$subject->id}")->assertOk()->json('sources');
        $this->assertCount(2, $sources);
        $this->assertSame([['label' => 'Week 1', 'topic' => 'Number bases', 'taught' => true], ['label' => 'Week 2', 'topic' => 'Indices', 'taught' => false]], $sources[0]['items']);
        $this->assertTrue($sources[1]['items'][0]['taught']);

        // A test section limited to several topics only draws from them
        $bank = CbtBank::create(['school_id' => $school->id, 'name' => 'Maths', 'subject_id' => $subject->id]);
        foreach (['Number bases', 'Fractions', 'Indices', 'Algebra'] as $topic) {
            CbtQuestion::create(['school_id' => $school->id, 'cbt_bank_id' => $bank->id, 'text' => "About {$topic}", 'options' => ['a', 'b'], 'correct_option' => 0, 'difficulty' => 'easy', 'topic' => $topic, 'marks' => 1]);
        }
        $test = CbtTest::create([
            'school_id' => $school->id, 'title' => 'Test', 'class_id' => $class->id, 'subject_id' => $subject->id, 'duration_minutes' => 30,
            'opens_at' => now()->addDay(), 'status' => 'draft', 'created_by' => $teacher->id,
        ]);
        $section = CbtTestSection::create(['cbt_test_id' => $test->id, 'position' => 0, 'title' => 'A', 'cbt_bank_id' => $bank->id, 'topics' => ['Number bases', 'Fractions'], 'question_count' => 2]);
        $this->assertEqualsCanonicalizing(['Number bases', 'Fractions'], $section->pool()->pluck('topic')->all());

        // The older single topic still works when no list is set
        $old = CbtTestSection::create(['cbt_test_id' => $test->id, 'position' => 1, 'title' => 'B', 'cbt_bank_id' => $bank->id, 'topic' => 'Indices', 'question_count' => 1]);
        $this->assertSame(['Indices'], $old->pool()->pluck('topic')->all());

        // A paper remembers its scope
        $q = CbtQuestion::first()->id;
        $this->post('/school/cbt/papers', [
            'title' => 'Exam', 'class_id' => $class->id, 'subject_id' => $subject->id, 'scope' => ['Number bases', ' Fractions ', 'Number bases'],
            'sections' => [['title' => 'A', 'instructions' => '', 'questions' => [$q]]],
        ])->assertSessionHasNoErrors();
        $this->assertSame(['Number bases', 'Fractions'], ExamPaper::sole()->scope);
    }
}
