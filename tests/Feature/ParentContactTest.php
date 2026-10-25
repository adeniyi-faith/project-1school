<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Message;
use App\Models\SchoolClass;
use App\Models\SchoolNotification;
use App\Models\Student;
use Tests\Feature\Security\SecurityTestCase;

/** Parents writing to the school: messages, absence notes, and keeping to their own children. */
class ParentContactTest extends SecurityTestCase
{
    private function family(string $schoolName = 'Contact School'): array
    {
        $school  = $this->makeSchool($schoolName);
        $admin   = $this->makeUser($school, 'school-admin');
        $teacher = $this->makeUser($school, 'teacher');
        $parent  = $this->makeUser($school, 'parent');
        $class   = SchoolClass::create(['school_id' => $school->id, 'name' => 'JSS 1', 'numeric_name' => 7, 'class_teacher_id' => $teacher->id]);
        $guardian = Guardian::create(['school_id' => $school->id, 'user_id' => $parent->id, 'name' => 'Mrs Test', 'phone' => '08030000000']);
        $child = Student::create([
            'school_id' => $school->id, 'admission_no' => 'A1', 'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female',
            'date_of_birth' => '2014-01-01', 'class_id' => $class->id, 'guardian_id' => $guardian->id, 'status' => 'active',
        ]);

        return compact('school', 'admin', 'teacher', 'parent', 'class', 'child');
    }

    public function test_a_parent_can_message_the_office_and_the_class_teacher(): void
    {
        $f = $this->family();

        $this->actingAs($f['parent'])->post('/school/parent/messages', ['student_id' => $f['child']->id, 'to' => 'office', 'body' => 'Please call me'])
            ->assertSessionHasNoErrors();
        $this->assertSame($f['admin']->id, Message::sole()->recipient_id);
        $this->assertSame(1, SchoolNotification::where('user_id', $f['admin']->id)->count());

        $this->actingAs($f['parent'])->post('/school/parent/messages', ['student_id' => $f['child']->id, 'to' => 'teacher', 'body' => 'About homework'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Message::where('recipient_id', $f['teacher']->id)->exists());
    }

    public function test_the_absence_note_goes_to_the_office_and_the_class_teacher(): void
    {
        $f = $this->family();

        $this->actingAs($f['parent'])->post('/school/parent/absent', ['student_id' => $f['child']->id, 'date' => now()->addDay()->toDateString(), 'reason' => 'Clinic visit'])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([$f['admin']->id, $f['teacher']->id], Message::pluck('recipient_id')->all());
        $this->assertStringContainsString('Absent note: Ada Obi', Message::first()->subject);

        // A day in the past is refused
        $this->actingAs($f['parent'])->post('/school/parent/absent', ['student_id' => $f['child']->id, 'date' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('date');
    }

    public function test_a_parent_cannot_write_about_someone_elses_child(): void
    {
        $f = $this->family();
        $other = $this->family('Other School');

        $this->actingAs($f['parent'])->post('/school/parent/messages', ['student_id' => $other['child']->id, 'to' => 'office', 'body' => 'Hi'])->assertNotFound();
        $this->assertSame(0, Message::count());
    }

    public function test_the_messages_page_lists_replies_and_marks_them_read(): void
    {
        $f = $this->family();
        Message::create(['school_id' => $f['school']->id, 'sender_id' => $f['admin']->id, 'recipient_id' => $f['parent']->id, 'subject' => 'Re: fees', 'body' => 'Thank you']);

        $this->actingAs($f['parent'])->get('/school/parent/messages')->assertOk()
            ->assertInertia(fn ($page) => $page->where('replies.0.unread', true));
        $this->assertNotNull(Message::sole()->read_at);

        // Staff cannot use the parent pages
        $this->actingAs($f['teacher'])->get('/school/parent/messages')->assertForbidden();
    }
}
