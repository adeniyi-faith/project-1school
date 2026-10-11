<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Changing a room allocation, the student profile pulling the school together, and the plain "not allowed" page */
class StudentActivityAndAccessTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private Student $ada;
    private Hostel $hostel;
    private HostelRoom $room1;
    private HostelRoom $room2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Boarding School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $this->ada = Student::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi', 'admission_no' => 'A1', 'gender' => 'female', 'status' => 'active']);
        $this->hostel = Hostel::create(['school_id' => $this->school->id, 'name' => 'Queen Hall', 'type' => 'girls', 'status' => 'active']);
        $this->room1 = HostelRoom::create(['school_id' => $this->school->id, 'hostel_id' => $this->hostel->id, 'room_no' => '1', 'type' => 'double', 'capacity' => 2, 'status' => 'available']);
        $this->room2 = HostelRoom::create(['school_id' => $this->school->id, 'hostel_id' => $this->hostel->id, 'room_no' => '2', 'type' => 'double', 'capacity' => 1, 'status' => 'available']);
    }

    private function allocate(): HostelAllocation
    {
        $this->post('/school/hostel/allocations', [
            'hostel_id' => $this->hostel->id, 'room_id' => $this->room1->id, 'student_id' => $this->ada->id, 'bed_no' => 'A1', 'joining_date' => '2026-09-14',
        ])->assertSessionHasNoErrors();

        return HostelAllocation::sole();
    }

    public function test_a_saved_allocation_can_be_moved_and_the_room_counts_follow(): void
    {
        $a = $this->allocate();
        $this->assertSame(1, $this->room1->fresh()->occupied);

        $this->put("/school/hostel/allocations/{$a->id}", ['hostel_id' => $this->hostel->id, 'room_id' => $this->room2->id, 'bed_no' => 'B1', 'joining_date' => '2026-09-14', 'notes' => 'Asked to move'])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->room2->id, $a->fresh()->room_id);
        $this->assertSame('B1', $a->fresh()->bed_no);
        $this->assertSame(0, $this->room1->fresh()->occupied);
        $this->assertSame(1, $this->room2->fresh()->occupied);
        $this->assertSame('full', $this->room2->fresh()->status);

        // Changing only the bed does not touch the counts
        $this->put("/school/hostel/allocations/{$a->id}", ['hostel_id' => $this->hostel->id, 'room_id' => $this->room2->id, 'bed_no' => 'B2', 'joining_date' => '2026-09-14'])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->room2->fresh()->occupied);
    }

    public function test_a_move_into_a_full_room_or_for_someone_who_left_is_refused(): void
    {
        $a = $this->allocate();
        $other = Student::create(['school_id' => $this->school->id, 'class_id' => $this->ada->class_id, 'first_name' => 'Bola', 'last_name' => 'Eze', 'admission_no' => 'B1', 'gender' => 'female', 'status' => 'active']);
        $this->post('/school/hostel/allocations', ['hostel_id' => $this->hostel->id, 'room_id' => $this->room2->id, 'student_id' => $other->id, 'joining_date' => '2026-09-14'])->assertSessionHasNoErrors();

        $this->put("/school/hostel/allocations/{$a->id}", ['hostel_id' => $this->hostel->id, 'room_id' => $this->room2->id, 'joining_date' => '2026-09-14'])->assertSessionHasErrors('room_id');
        $this->assertSame($this->room1->id, $a->fresh()->room_id);

        $this->put("/school/hostel/allocations/{$a->id}/vacate", ['leaving_date' => '2026-10-01']);
        $this->put("/school/hostel/allocations/{$a->id}", ['hostel_id' => $this->hostel->id, 'room_id' => $this->room1->id, 'joining_date' => '2026-09-14'])->assertSessionHas('error');
    }

    public function test_student_search_for_the_allocation_form_skips_boarders_already_placed(): void
    {
        $this->getJson('/school/hostel/student-search?q=Ada')->assertOk()->assertJsonCount(1, 'data');
        $this->allocate();
        $this->getJson('/school/hostel/student-search?q=Ada')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/school/hostel/student-search?q=A')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_profile_shows_room_books_and_other_parts_the_viewer_may_see(): void
    {
        $this->allocate();
        $book = Book::create(['school_id' => $this->school->id, 'title' => 'Things Fall Apart', 'author' => 'Achebe', 'total_copies' => 1, 'available_copies' => 0, 'is_active' => true]);
        BookIssue::create(['school_id' => $this->school->id, 'book_id' => $book->id, 'member_type' => Student::class, 'member_id' => $this->ada->id, 'issued_date' => '2026-10-01', 'due_date' => '2026-10-15', 'status' => 'issued']);

        $this->get("/school/students/{$this->ada->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->where('activity.hostel.0.hostel', 'Queen Hall')->where('activity.hostel.0.room', '1')->where('activity.hostel.0.bed', 'A1')
            ->where('activity.library.0.title', 'Things Fall Apart')
            ->where('activity.transport', [])->where('activity.fees.owed', 0)->where('activity.tests', []));

        // A teacher cannot see hostel, library or fee details, so the profile leaves them out
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get("/school/students/{$this->ada->id}")->assertOk()->assertInertia(fn ($p) => $p
            ->where('activity.hostel', null)->where('activity.library', null)->where('activity.fees', null));
    }

    public function test_not_allowed_shows_a_plain_page_with_the_403_kept(): void
    {
        $a = $this->allocate();
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher)->get('/school/hostel/allocations')->assertForbidden()->assertInertia(fn ($p) => $p
            ->component('Error', false)->where('message', 'You do not have permission to do that.'));

        // Also when the request came from a button on a page
        $this->actingAs($teacher)->withHeaders(['X-Inertia' => 'true'])->put("/school/hostel/allocations/{$a->id}/vacate", ['leaving_date' => '2026-10-01'])->assertForbidden();
    }

    public function test_one_fee_can_be_set_for_many_classes_each_with_its_own_amount(): void
    {
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        $sss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'SSS 1', 'numeric_name' => 10]);
        $cat = \App\Models\FeeCategory::create(['school_id' => $this->school->id, 'name' => 'Tuition', 'type' => 'tuition', 'is_active' => true]);
        $body = fn (array $rows) => ['fee_category_id' => $cat->id, 'academic_year' => '2026/2027', 'frequency' => 'per_term', 'rows' => $rows];

        $this->post('/school/fees/structures/many', $body([
            ['class_id' => $this->ada->class_id, 'amount' => '150000'], ['class_id' => $jss2->id, 'amount' => '175000'], ['class_id' => $sss1->id, 'amount' => ''],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, \App\Models\FeeStructure::count());
        $this->assertSame(175000.0, (float) \App\Models\FeeStructure::where('class_id', $jss2->id)->value('amount'));
        $this->assertSame('per_term', \App\Models\FeeStructure::first()->frequency);

        // Running it again changes the amount rather than adding a second line
        $this->post('/school/fees/structures/many', $body([['class_id' => $jss2->id, 'amount' => '180000']]))->assertSessionHasNoErrors();
        $this->assertSame(2, \App\Models\FeeStructure::count());
        $this->assertSame(180000.0, (float) \App\Models\FeeStructure::where('class_id', $jss2->id)->value('amount'));

        $this->post('/school/fees/structures/many', $body([['class_id' => $jss2->id, 'amount' => '']]))->assertSessionHasErrors('rows');

        // Only people who may change fees can do it
        $this->actingAs($this->makeUser($this->school, 'teacher'))->post('/school/fees/structures/many', $body([['class_id' => $jss2->id, 'amount' => '1']]))->assertForbidden();
    }
}
