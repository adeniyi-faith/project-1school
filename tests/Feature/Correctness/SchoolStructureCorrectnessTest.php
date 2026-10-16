<?php

namespace Tests\Feature\Correctness;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use Tests\Feature\Security\SecurityTestCase;

/** Classes, arms, subjects and the timetable: names, deletes, and lessons that clash. */
class SchoolStructureCorrectnessTest extends SecurityTestCase
{
    private $school;
    private SchoolClass $jss1;
    private SchoolClass $jss2;
    private Subject $maths;
    private Subject $english;
    private Staff $mrAde;
    private Staff $mrsBello;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Structure School');
        $this->actingAs($this->makeUser($this->school, 'school-admin'));
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
        $this->jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2']);
        $this->maths = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'Mathematics', 'code' => 'MTH']);
        $this->english = Subject::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'English', 'code' => 'ENG']);
        $this->mrAde = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Ade', 'last_name' => 'Teacher', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active']);
        $this->mrsBello = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Bello', 'last_name' => 'Teacher', 'gender' => 'female', 'salary_type' => 'fixed', 'status' => 'active']);
    }

    private function period(array $over = [])
    {
        return $this->post('/school/timetable', $over + [
            'class_id' => $this->jss1->id, 'subject_id' => $this->maths->id, 'teacher_id' => $this->mrAde->id,
            'day_of_week' => 'monday', 'start_time' => '08:00', 'end_time' => '09:00',
        ]);
    }

    public function test_two_classes_cannot_share_a_name_but_a_class_can_keep_its_own(): void
    {
        $this->post('/school/classes', ['name' => 'jss 1'])->assertSessionHasErrors('name'); // capitals do not matter
        $this->post('/school/classes', ['name' => '  JSS 1 '])->assertSessionHasErrors('name');
        $this->post('/school/classes', ['name' => 'JSS 3'])->assertSessionHasNoErrors();
        $this->put("/school/classes/{$this->jss1->id}", ['name' => 'JSS 1', 'capacity' => 40])->assertSessionHasNoErrors(); // own name
        $this->put("/school/classes/{$this->jss1->id}", ['name' => 'JSS 2'])->assertSessionHasErrors('name');
        $this->assertSame(3, SchoolClass::count());
        // another school may use the same name
        $this->actingAs($this->makeUser($this->makeSchool('Other'), 'school-admin'));
        $this->post('/school/classes', ['name' => 'JSS 1'])->assertSessionHasNoErrors();
    }

    public function test_bad_class_values_are_refused(): void
    {
        $this->post('/school/classes', ['name' => ''])->assertSessionHasErrors('name');
        $this->post('/school/classes', ['name' => str_repeat('x', 101)])->assertSessionHasErrors('name');
        $this->post('/school/classes', ['name' => 'JSS 4', 'capacity' => -5])->assertSessionHasErrors('capacity');
        $this->post('/school/classes', ['name' => 'JSS 4', 'numeric_name' => 0])->assertSessionHasErrors('numeric_name');
        $this->post('/school/classes', ['name' => 'JSS 4', 'level' => 'university'])->assertSessionHasErrors('level');
        $this->assertSame(2, SchoolClass::count());
    }

    public function test_a_class_with_pupils_on_roll_cannot_be_deleted(): void
    {
        $s = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'status' => 'active']);
        $this->delete("/school/classes/{$this->jss1->id}")->assertSessionHas('error');
        $this->assertNotNull(SchoolClass::find($this->jss1->id));
        $this->assertSame($this->jss1->id, $s->fresh()->class_id);

        // once the pupil has moved on, it can go
        $s->update(['class_id' => $this->jss2->id]);
        $this->delete("/school/classes/{$this->jss1->id}")->assertSessionHas('success');
        $this->assertNull(SchoolClass::find($this->jss1->id));
    }

    public function test_re_saving_the_same_period_does_not_clash_with_itself(): void
    {
        $this->period()->assertSessionHasNoErrors();
        // Same class, day and start: this edits the period (new room), it must not say the teacher is busy
        $this->period(['room' => 'Lab 2'])->assertSessionHasNoErrors();
        $this->assertSame(1, Timetable::count());
        $this->assertSame('Lab 2', Timetable::sole()->room);
    }

    public function test_a_teacher_cannot_be_in_two_classes_at_overlapping_times(): void
    {
        $this->period()->assertSessionHasNoErrors();                                                                          // Mon 08:00-09:00 JSS 1
        $this->period(['class_id' => $this->jss2->id, 'start_time' => '08:30', 'end_time' => '09:30'])->assertSessionHasErrors('teacher_id'); // overlaps by 30 minutes
        $this->period(['class_id' => $this->jss2->id, 'start_time' => '07:30', 'end_time' => '08:30'])->assertSessionHasErrors('teacher_id'); // overlaps at the start
        $this->period(['class_id' => $this->jss2->id, 'start_time' => '08:10', 'end_time' => '08:20'])->assertSessionHasErrors('teacher_id'); // inside
        $this->assertSame(1, Timetable::count());
        // Back to back is fine, another day is fine, another teacher is fine
        $this->period(['class_id' => $this->jss2->id, 'start_time' => '09:00', 'end_time' => '10:00'])->assertSessionHasNoErrors();
        $this->period(['class_id' => $this->jss2->id, 'day_of_week' => 'tuesday'])->assertSessionHasNoErrors();
        $this->period(['class_id' => $this->jss2->id, 'teacher_id' => $this->mrsBello->id, 'subject_id' => $this->english->id])->assertSessionHasNoErrors();
        $this->assertSame(4, Timetable::count());
    }

    public function test_one_class_cannot_have_two_lessons_at_the_same_time(): void
    {
        $this->period()->assertSessionHasNoErrors();
        $this->period(['subject_id' => $this->english->id, 'teacher_id' => $this->mrsBello->id, 'start_time' => '08:30', 'end_time' => '09:30'])->assertSessionHasErrors('start_time');
        $this->assertSame(1, Timetable::count());
        $this->period(['subject_id' => $this->english->id, 'teacher_id' => $this->mrsBello->id, 'start_time' => '09:00', 'end_time' => '10:00'])->assertSessionHasNoErrors();
    }

    public function test_bad_timetable_input_is_refused(): void
    {
        $this->period(['end_time' => '07:00'])->assertSessionHasErrors('end_time');   // ends before it starts
        $this->period(['end_time' => '08:00'])->assertSessionHasErrors('end_time');   // zero length
        $this->period(['day_of_week' => 'funday'])->assertSessionHasErrors('day_of_week');
        $this->period(['start_time' => '8am'])->assertSessionHasErrors('start_time');
        $this->assertSame(0, Timetable::count());
    }

    public function test_arms_can_be_deleted_without_losing_pupils(): void
    {
        $arm = Section::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'name' => 'A']);
        $s = Student::create(['school_id' => $this->school->id, 'class_id' => $this->jss1->id, 'section_id' => $arm->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'status' => 'active']);
        $this->delete("/school/sections/{$arm->id}")->assertSessionHas('success');
        $s->refresh();
        $this->assertNull($s->section_id);
        $this->assertSame($this->jss1->id, $s->class_id);
        $this->assertSame('active', $s->status);
    }
}
