<?php

namespace Tests\Feature\Correctness;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\HostelRoom;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use Carbon\Carbon;
use Tests\Feature\Security\SecurityTestCase;

/**
 * Right-answer checks for the library (copies and fines), hostel (beds and who may sleep where) and transport (seats).
 * Every figure is worked out by hand.
 */
class FacilitiesCorrectnessTest extends SecurityTestCase
{
    private $school;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 09:00:00');
        $this->school = $this->makeSchool('Facilities School');
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1']);
    }

    private function as(string $role): void
    {
        $this->actingAs($this->makeUser($this->school, $role));
    }

    private function pupil(string $first, string $gender = 'female'): Student
    {
        return Student::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'first_name' => $first, 'last_name' => 'Pupil', 'gender' => $gender, 'status' => 'active']);
    }

    private function book(int $copies = 3, array $over = []): Book
    {
        return Book::create($over + ['school_id' => $this->school->id, 'title' => 'Things Fall Apart', 'author' => 'Chinua Achebe', 'total_copies' => $copies, 'available_copies' => $copies, 'is_active' => true]);
    }

    private function issue(Book $book, Student $s, string $issued = '2026-10-01', string $due = '2026-10-15', ?float $perDay = null)
    {
        return $this->post('/school/library/issues', ['book_id' => $book->id, 'member_type' => 'student', 'member_id' => $s->id, 'issued_date' => $issued, 'due_date' => $due] + ($perDay === null ? [] : ['fine_per_day' => $perDay]));
    }

    // ---------------------------------------------------------------- library

    public function test_issuing_and_returning_keeps_the_copy_count_right_and_works_out_the_fine(): void
    {
        $this->as('librarian');
        $book = $this->book(3);
        $ada = $this->pupil('Ada');
        $bayo = $this->pupil('Bayo');
        $chidi = $this->pupil('Chidi');
        $dupe = $this->pupil('Dupe');

        $this->issue($book, $ada, '2026-10-01', '2026-10-15', 2.5)->assertSessionHasNoErrors();
        $this->issue($book, $bayo, '2026-10-01', '2026-10-15')->assertSessionHasNoErrors();
        $this->issue($book, $chidi, '2026-10-01', '2026-10-15')->assertSessionHasNoErrors();
        $this->assertSame(0, $book->fresh()->available_copies);
        // All three copies are out: a fourth borrower is refused
        $this->issue($book, $dupe)->assertSessionHasErrors('book_id');
        $this->assertSame(3, BookIssue::count());

        $issueOf = fn (Student $s) => BookIssue::where('member_id', $s->id)->sole();
        // Ada returns 3 days late at ₦2.50 a day: 7.50. Bayo returns on the due date: 0. Chidi returns 2 days early: 0.
        $this->put("/school/library/issues/{$issueOf($ada)->id}/return", ['returned_date' => '2026-10-18'])->assertSessionHasNoErrors();
        $this->put("/school/library/issues/{$issueOf($bayo)->id}/return", ['returned_date' => '2026-10-15'])->assertSessionHasNoErrors();
        $this->put("/school/library/issues/{$issueOf($chidi)->id}/return", ['returned_date' => '2026-10-13'])->assertSessionHasNoErrors();
        $this->assertEquals(7.5, (float) $issueOf($ada)->fine);
        $this->assertEquals(0, (float) $issueOf($bayo)->fine);
        $this->assertEquals(0, (float) $issueOf($chidi)->fine);
        $this->assertSame(3, $book->fresh()->available_copies);
        $this->assertSame('returned', $issueOf($ada)->status);
    }

    public function test_the_default_fine_is_two_naira_a_day_and_one_day_late_is_one_day(): void
    {
        $this->as('librarian');
        $book = $this->book(1);
        $ada = $this->pupil('Ada');
        $this->issue($book, $ada)->assertSessionHasNoErrors();
        $issue = BookIssue::sole();
        $this->put("/school/library/issues/{$issue->id}/return", ['returned_date' => '2026-10-16'])->assertSessionHasNoErrors();
        $this->assertEquals(2.0, (float) $issue->fresh()->fine);
    }

    public function test_a_book_cannot_be_returned_twice_so_copies_never_exceed_the_total(): void
    {
        $this->as('librarian');
        $book = $this->book(2);
        $this->issue($book, $this->pupil('Ada'))->assertSessionHasNoErrors();
        $issue = BookIssue::sole();
        $this->put("/school/library/issues/{$issue->id}/return", ['returned_date' => '2026-10-18'])->assertSessionHasNoErrors();
        $this->assertSame(2, $book->fresh()->available_copies);

        // A second click on Return (a double click, or two librarians) must change nothing
        $this->put("/school/library/issues/{$issue->id}/return", ['returned_date' => '2026-10-25'])->assertSessionHas('error');
        $this->assertSame(2, $book->fresh()->available_copies, 'copies went above the total');
        $this->assertEquals(6.0, (float) $issue->fresh()->fine, 'the fine was changed by the second return (3 days late x 2)');
        $this->assertSame('2026-10-18', $issue->fresh()->returned_date->toDateString());
    }

    public function test_the_overdue_page_counts_the_days_and_the_fine_so_far(): void
    {
        $this->as('librarian');
        $a = $this->book(2, ['title' => 'Book A']);
        $b = $this->book(2, ['title' => 'Book B']);
        // Today is 20 Oct. Due 10 Oct = 10 days late x 2 = 20.00. Due 17 Oct at 5 a day = 3 x 5 = 15.00. Due today = not late. Due tomorrow = not late.
        $this->issue($a, $this->pupil('Ada'), '2026-10-01', '2026-10-10')->assertSessionHasNoErrors();
        $this->issue($b, $this->pupil('Bayo'), '2026-10-01', '2026-10-17', 5)->assertSessionHasNoErrors();
        $this->issue($a, $this->pupil('Chidi'), '2026-10-01', '2026-10-20')->assertSessionHasNoErrors();
        $this->issue($b, $this->pupil('Dupe'), '2026-10-01', '2026-10-21')->assertSessionHasNoErrors();

        $r = $this->get('/school/library/overdue')->assertOk();
        $rows = collect($r->viewData('page')['props']['overdue'])->keyBy('book.title');
        $this->assertSame(2, count($rows));
        $this->assertSame(10, $rows['Book A']['overdue_days']);
        $this->assertEquals(20.0, (float) $rows['Book A']['estimated_fine']);
        $this->assertSame(3, $rows['Book B']['overdue_days']);
        $this->assertEquals(15.0, (float) $rows['Book B']['estimated_fine']);
        $this->assertEquals(35.0, (float) $r->viewData('page')['props']['summary']['total_fine_est']);
        $this->assertSame(2, $r->viewData('page')['props']['summary']['count']);
    }

    public function test_the_books_page_counts_what_is_out_and_what_is_late_without_visiting_other_pages_first(): void
    {
        $this->as('librarian');
        $book = $this->book(5);
        // Written straight to the table, the way a day passing would leave them: nobody has opened the overdue page yet
        foreach ([['2026-10-10', 'issued'], ['2026-10-12', 'issued'], ['2026-10-25', 'issued']] as $i => [$due, $status]) {
            BookIssue::create(['school_id' => $this->school->id, 'book_id' => $book->id, 'member_type' => Student::class, 'member_id' => $this->pupil("P$i")->id, 'issued_date' => '2026-10-01', 'due_date' => $due, 'status' => $status, 'fine_per_day' => 2]);
        }
        $book->update(['available_copies' => 2]);

        $this->get('/school/library/books')->assertOk()->assertInertia(fn ($p) => $p
            ->where('stats.total_books', 5)->where('stats.available', 2)
            ->where('stats.issued_count', 3)   // three copies are out, two of them late
            ->where('stats.overdue_count', 2));
    }

    public function test_bad_loans_are_refused(): void
    {
        $this->as('librarian');
        $book = $this->book(2);
        $ada = $this->pupil('Ada');
        // A pupil of another school, a pupil that does not exist, and a book that was switched off
        $other = $this->makeSchool('Other Library School');
        $otherClass = SchoolClass::create(['school_id' => $other->id, 'name' => 'X']);
        $stranger = Student::withoutGlobalScopes()->create(['school_id' => $other->id, 'class_id' => $otherClass->id, 'first_name' => 'Zed', 'last_name' => 'Far', 'gender' => 'male', 'status' => 'active']);
        $this->issue($book, $stranger)->assertSessionHasErrors('member_id');
        $this->post('/school/library/issues', ['book_id' => $book->id, 'member_type' => 'student', 'member_id' => 999999, 'issued_date' => '2026-10-01', 'due_date' => '2026-10-15'])->assertSessionHasErrors('member_id');
        $off = $this->book(2, ['title' => 'Withdrawn', 'is_active' => false]);
        $this->issue($off, $ada)->assertSessionHasErrors('book_id');
        // Due before it was issued, and the same day
        $this->issue($book, $ada, '2026-10-10', '2026-10-05')->assertSessionHasErrors('due_date');
        $this->issue($book, $ada, '2026-10-10', '2026-10-10')->assertSessionHasErrors('due_date');
        $this->assertSame(0, BookIssue::count());
        $this->assertSame(2, $book->fresh()->available_copies);

        // Returned before it was ever issued
        $this->issue($book, $ada, '2026-10-10', '2026-10-20')->assertSessionHasNoErrors();
        $issue = BookIssue::sole();
        $this->put("/school/library/issues/{$issue->id}/return", ['returned_date' => '2026-10-09'])->assertSessionHasErrors('returned_date');
        $this->assertSame('issued', $issue->fresh()->status);
    }

    public function test_copies_cannot_be_cut_below_what_is_out_and_a_book_with_loans_cannot_be_deleted(): void
    {
        $this->as('librarian');
        $book = $this->book(3);
        $this->issue($book, $this->pupil('Ada'))->assertSessionHasNoErrors();
        $this->issue($book, $this->pupil('Bayo'))->assertSessionHasNoErrors();
        $payload = fn (int $total) => ['title' => $book->title, 'author' => $book->author, 'total_copies' => $total, 'is_active' => true];

        // 2 are out: a total of 1 is impossible, a total of 2 leaves none on the shelf, 5 leaves 3
        $this->put("/school/library/books/{$book->id}", $payload(1))->assertSessionHasErrors('total_copies');
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->put("/school/library/books/{$book->id}", $payload(2))->assertSessionHasNoErrors();
        $this->assertSame(0, $book->fresh()->available_copies);
        $this->put("/school/library/books/{$book->id}", $payload(5))->assertSessionHasNoErrors();
        $this->assertSame(3, $book->fresh()->available_copies);

        $this->delete("/school/library/books/{$book->id}")->assertSessionHas('error');
        $this->assertNotNull(Book::find($book->id), 'a book with copies out was deleted');
        $clean = $this->book(1, ['title' => 'Never borrowed']);
        $this->delete("/school/library/books/{$clean->id}")->assertSessionHas('success');
        $this->assertNull(Book::find($clean->id));
    }

    // ---------------------------------------------------------------- hostel

    private function hostel(string $type, string $name = 'Hall'): Hostel
    {
        return Hostel::create(['school_id' => $this->school->id, 'name' => $name, 'type' => $type, 'status' => 'active']);
    }

    private function room(Hostel $h, string $no, int $capacity, string $status = 'available'): HostelRoom
    {
        $this->post("/school/hostel/{$h->id}/rooms", ['room_no' => $no, 'type' => 'dormitory', 'capacity' => $capacity])->assertSessionHasNoErrors();
        $room = HostelRoom::where('hostel_id', $h->id)->where('room_no', $no)->sole();
        if ($status !== 'available') {
            $room->update(['status' => $status]);
        }

        return $room->fresh();
    }

    private function allocate(Hostel $h, HostelRoom $r, Student $s)
    {
        return $this->post('/school/hostel/allocations', ['hostel_id' => $h->id, 'room_id' => $r->id, 'student_id' => $s->id, 'joining_date' => '2026-10-01']);
    }

    public function test_beds_are_counted_exactly_as_pupils_move_in_and_out(): void
    {
        $this->as('school-admin');
        $h = $this->hostel('mixed');
        $r = $this->room($h, '1', 2);
        $ada = $this->pupil('Ada');
        $bayo = $this->pupil('Bayo', 'male');
        $chidi = $this->pupil('Chidi', 'male');

        $this->allocate($h, $r, $ada)->assertSessionHasNoErrors();
        $this->assertSame(1, $r->fresh()->occupied);
        $this->assertSame('available', $r->fresh()->status);
        $this->allocate($h, $r, $bayo)->assertSessionHasNoErrors();
        $this->assertSame(2, $r->fresh()->occupied);
        $this->assertSame('full', $r->fresh()->status);
        // The room is full: the third pupil is refused and nothing changes
        $this->allocate($h, $r, $chidi)->assertSessionHasErrors('room_id');
        $this->assertSame(2, $r->fresh()->occupied);
        // The same pupil cannot take a second bed
        $r2 = $this->room($h, '2', 2);
        $this->allocate($h, $r2, $ada)->assertSessionHasErrors('student_id');
        $this->assertSame(0, $r2->fresh()->occupied);

        $allocation = HostelAllocation::where('student_id', $ada->id)->sole();
        $this->put("/school/hostel/allocations/{$allocation->id}/vacate", ['leaving_date' => '2026-12-18'])->assertSessionHasNoErrors();
        $this->assertSame(1, $r->fresh()->occupied);
        $this->assertSame('available', $r->fresh()->status);
        // Vacating twice must not take a second bed away
        $this->put("/school/hostel/allocations/{$allocation->id}/vacate", ['leaving_date' => '2026-12-19'])->assertSessionHas('error');
        $this->assertSame(1, $r->fresh()->occupied, 'the room lost a bed it never had');
        $this->assertSame('2026-12-18', $allocation->fresh()->leaving_date->toDateString());
    }

    public function test_a_boys_hostel_takes_boys_and_a_girls_hostel_takes_girls(): void
    {
        $this->as('school-admin');
        $boys = $this->hostel('boys', 'Boys Hall');
        $girls = $this->hostel('girls', 'Girls Hall');
        $mixed = $this->hostel('mixed', 'Mixed Hall');
        [$rb, $rg, $rm] = [$this->room($boys, '1', 4), $this->room($girls, '1', 4), $this->room($mixed, '1', 4)];
        $girl = $this->pupil('Ada', 'female');
        $boy = $this->pupil('Bayo', 'male');

        $this->allocate($boys, $rb, $girl)->assertSessionHasErrors('student_id');
        $this->allocate($girls, $rg, $boy)->assertSessionHasErrors('student_id');
        $this->assertSame(0, HostelAllocation::count());
        $this->assertSame(0, $rb->fresh()->occupied + $rg->fresh()->occupied);

        $this->allocate($boys, $rb, $boy)->assertSessionHasNoErrors();
        $this->allocate($mixed, $rm, $girl)->assertSessionHasNoErrors();
        $this->assertSame(2, HostelAllocation::count());

        // Moving a pupil into a hostel of the other kind is refused too
        $allocation = HostelAllocation::where('student_id', $boy->id)->sole();
        $this->put("/school/hostel/allocations/{$allocation->id}", ['hostel_id' => $girls->id, 'room_id' => $rg->id, 'joining_date' => '2026-10-01'])->assertSessionHasErrors();
        $this->assertSame($rb->id, $allocation->fresh()->room_id);
        $this->assertSame(1, $rb->fresh()->occupied);
    }

    public function test_a_room_must_belong_to_the_chosen_hostel_and_must_be_open(): void
    {
        $this->as('school-admin');
        $h1 = $this->hostel('mixed', 'Hall One');
        $h2 = $this->hostel('mixed', 'Hall Two');
        $r1 = $this->room($h1, '1', 4);
        $closed = $this->room($h1, '2', 4, 'maintenance');
        $ada = $this->pupil('Ada');

        // Room 1 belongs to Hall One but the form says Hall Two
        $this->allocate($h2, $r1, $ada)->assertSessionHasErrors('room_id');
        // A room under repair takes nobody
        $this->allocate($h1, $closed, $ada)->assertSessionHasErrors('room_id');
        $this->assertSame(0, HostelAllocation::count());
        $this->assertSame(0, $r1->fresh()->occupied + $closed->fresh()->occupied);
    }

    public function test_room_and_hostel_totals_stay_true_and_occupied_rooms_cannot_be_cut_or_deleted(): void
    {
        $this->as('school-admin');
        $h = $this->hostel('mixed');
        $r1 = $this->room($h, '1', 4);
        $r2 = $this->room($h, '2', 2);
        $r3 = $this->room($h, '3', 6);
        $this->assertSame(3, $h->fresh()->total_rooms);
        $this->assertSame(12, $h->fresh()->total_capacity);

        foreach (['Ada', 'Bayo', 'Chidi'] as $n) {
            $this->allocate($h, $r1, $this->pupil($n))->assertSessionHasNoErrors();
        }
        $roomForm = fn (HostelRoom $r, int $capacity) => ['room_no' => $r->room_no, 'type' => 'dormitory', 'capacity' => $capacity, 'status' => 'available'];

        // 3 beds are taken in room 1: a capacity of 2 is impossible, 3 makes it full, 5 reopens it
        $this->put("/school/hostel/{$h->id}/rooms/{$r1->id}", $roomForm($r1, 2))->assertSessionHasErrors('capacity');
        $this->assertSame(4, $r1->fresh()->capacity);
        $this->put("/school/hostel/{$h->id}/rooms/{$r1->id}", $roomForm($r1, 3))->assertSessionHasNoErrors();
        $this->assertSame('full', $r1->fresh()->status);
        $this->assertSame(11, $h->fresh()->total_capacity);
        $this->put("/school/hostel/{$h->id}/rooms/{$r1->id}", $roomForm($r1, 5))->assertSessionHasNoErrors();
        $this->assertSame('available', $r1->fresh()->status);
        $this->assertSame(13, $h->fresh()->total_capacity);

        // A room with people in it cannot be deleted, an empty one can (and the totals follow)
        $this->delete("/school/hostel/{$h->id}/rooms/{$r1->id}")->assertSessionHas('error');
        $this->assertNotNull(HostelRoom::find($r1->id));
        $this->assertSame(3, $h->fresh()->total_rooms);
        $this->delete("/school/hostel/{$h->id}/rooms/{$r2->id}")->assertSessionHas('success');
        $this->assertSame(2, $h->fresh()->total_rooms);
        $this->assertSame(11, $h->fresh()->total_capacity);

        // A hostel with pupils in it cannot be deleted
        $this->delete("/school/hostel/{$h->id}")->assertSessionHas('error');
        $this->assertNotNull(Hostel::find($h->id));

        $this->get('/school/hostel')->assertOk()->assertInertia(fn ($p) => $p->where('stats.total_capacity', 11)->where('stats.occupied', 3));
    }

    // ---------------------------------------------------------------- transport

    public function test_a_bus_cannot_take_more_pupils_than_it_has_seats(): void
    {
        $this->as('school-admin');
        $bus = Vehicle::create(['school_id' => $this->school->id, 'registration_no' => 'LAG-123-AA', 'name' => 'Bus 1', 'type' => 'minibus', 'capacity' => 3, 'status' => 'active']);
        $route = TransportRoute::create(['school_id' => $this->school->id, 'vehicle_id' => $bus->id, 'name' => 'Ikeja route', 'is_active' => true]);
        $pupils = array_map(fn ($n) => $this->pupil($n), ['Ada', 'Bayo', 'Chidi', 'Dupe']);

        foreach (array_slice($pupils, 0, 3) as $p) {
            $this->post("/school/transport/routes/{$route->id}/assign", ['student_id' => $p->id, 'stop' => 'Ikeja'])->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $route->students()->count());
        // The same pupil again changes nothing
        $this->post("/school/transport/routes/{$route->id}/assign", ['student_id' => $pupils[0]->id])->assertSessionHasNoErrors();
        $this->assertSame(3, $route->students()->count());
        // The 4th has no seat
        $this->post("/school/transport/routes/{$route->id}/assign", ['student_id' => $pupils[3]->id])->assertSessionHasErrors('student_id');
        $this->assertSame(3, $route->students()->count());
        // Once someone is taken off, the seat can be used
        $this->delete("/school/transport/routes/{$route->id}/students/{$pupils[0]->id}")->assertSessionHasNoErrors();
        $this->post("/school/transport/routes/{$route->id}/assign", ['student_id' => $pupils[3]->id])->assertSessionHasNoErrors();
        $this->assertSame(3, $route->students()->count());

        // A pupil of another school cannot be put on the bus
        $other = $this->makeSchool('Other Bus School');
        $oc = SchoolClass::create(['school_id' => $other->id, 'name' => 'X']);
        $stranger = Student::withoutGlobalScopes()->create(['school_id' => $other->id, 'class_id' => $oc->id, 'first_name' => 'Zed', 'last_name' => 'Far', 'gender' => 'male', 'status' => 'active']);
        $this->post("/school/transport/routes/{$route->id}/assign", ['student_id' => $stranger->id])->assertSessionHasErrors('student_id');
    }

    public function test_a_bus_whose_seats_are_cut_below_its_riders_is_refused(): void
    {
        $this->as('school-admin');
        $bus = Vehicle::create(['school_id' => $this->school->id, 'registration_no' => 'LAG-1', 'name' => 'Bus', 'type' => 'bus', 'capacity' => 5, 'status' => 'active']);
        $route = TransportRoute::create(['school_id' => $this->school->id, 'vehicle_id' => $bus->id, 'name' => 'R', 'is_active' => true]);
        foreach (['Ada', 'Bayo', 'Chidi'] as $n) {
            $route->students()->attach($this->pupil($n)->id);
        }
        $form = fn (int $cap) => ['registration_no' => 'LAG-1', 'type' => 'bus', 'capacity' => $cap, 'status' => 'active'];
        $this->put("/school/transport/vehicles/{$bus->id}", $form(2))->assertSessionHasErrors('capacity');
        $this->assertSame(5, $bus->fresh()->capacity);
        $this->put("/school/transport/vehicles/{$bus->id}", $form(3))->assertSessionHasNoErrors();
        $this->assertSame(3, $bus->fresh()->capacity);
    }
}
