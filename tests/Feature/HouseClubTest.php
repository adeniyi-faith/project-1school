<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\House;
use App\Models\HousePoint;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Tests\Feature\Security\SecurityTestCase;

/** Sports houses (sharing students out, points) and clubs (members and roles) */
class HouseClubTest extends SecurityTestCase
{
    private School $school;
    private User $admin;
    private SchoolClass $jss1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('House School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
        $this->jss1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
    }

    private function student(string $no, string $gender, ?SchoolClass $class = null): Student
    {
        return Student::create([
            'school_id' => $this->school->id, 'class_id' => ($class ?? $this->jss1)->id, 'first_name' => 'S'.$no, 'last_name' => 'Test',
            'admission_no' => $no, 'gender' => $gender, 'status' => 'active',
        ]);
    }

    private function house(string $name): House
    {
        $this->post('/school/houses', ['name' => $name, 'colour' => '#dc2626', 'is_active' => true])->assertSessionHasNoErrors();

        return House::where('name', $name)->sole();
    }

    public function test_sharing_gives_each_house_a_fair_mix_and_keeps_existing_members(): void
    {
        [$red, $blue] = [$this->house('Red'), $this->house('Blue')];
        $jss2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 2', 'numeric_name' => 8]);
        foreach (range(1, 4) as $i) {
            $this->student("B{$i}", 'male');
            $this->student("G{$i}", 'female');
            $this->student("C{$i}", 'male', $jss2);
        }
        $kept = $this->student('K1', 'female');
        $kept->update(['house_id' => $red->id]);

        $this->post('/school/houses/share', [])->assertSessionHasNoErrors();

        $this->assertSame($red->id, $kept->fresh()->house_id);
        $this->assertSame(0, Student::whereNull('house_id')->count());
        // within each class, boys and girls split as evenly as they can
        foreach ([[$this->jss1->id, 'male'], [$this->jss1->id, 'female'], [$jss2->id, 'male']] as [$class, $gender]) {
            $counts = Student::where('class_id', $class)->where('gender', $gender)->get()->countBy('house_id');
            $this->assertLessThanOrEqual(1, abs(($counts[$red->id] ?? 0) - ($counts[$blue->id] ?? 0)), "{$class} {$gender}");
        }

        // "everyone" starts again but still balances
        $this->post('/school/houses/share', ['everyone' => true])->assertSessionHasNoErrors();
        $sizes = Student::get()->countBy('house_id');
        $this->assertLessThanOrEqual(1, abs($sizes[$red->id] - $sizes[$blue->id]));
    }

    public function test_sharing_needs_two_houses(): void
    {
        $this->house('Only');
        $this->student('X1', 'male');
        $this->post('/school/houses/share', [])->assertSessionHasErrors('houses');
    }

    public function test_points_add_up_and_a_mistake_is_cancelled_by_an_opposite_line(): void
    {
        $red = $this->house('Red');
        $blue = $this->house('Blue');
        $this->post('/school/houses/points', ['house_ids' => [$red->id, $blue->id], 'points' => 10, 'reason' => 'Relay', 'awarded_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->post('/school/houses/points', ['house_ids' => [$red->id], 'points' => 0, 'reason' => 'Nothing', 'awarded_on' => now()->toDateString()])->assertSessionHasErrors('points');

        $line = HousePoint::where('house_id', $red->id)->sole();
        $this->post("/school/houses/points/{$line->id}/undo")->assertSessionHasNoErrors();

        $this->assertSame(0, (int) HousePoint::where('house_id', $red->id)->sum('points'));
        $this->assertSame(10, (int) HousePoint::where('house_id', $blue->id)->sum('points'));
        $this->assertSame(3, HousePoint::count());

        $this->get('/school/houses')->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/Houses/Index', false)
            ->where('houses.0.name', 'Blue')->where('houses.0.points', 10)->where('can.manage', true));
    }

    public function test_a_teacher_can_give_points_but_not_change_houses(): void
    {
        $red = $this->house('Red');
        $teacher = $this->makeUser($this->school, 'teacher');
        $this->actingAs($teacher);

        $this->post('/school/houses/points', ['house_ids' => [$red->id], 'points' => 5, 'reason' => 'Neat', 'awarded_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->post('/school/houses', ['name' => 'Green', 'colour' => '#16a34a'])->assertForbidden();
        $this->post('/school/houses/share', [])->assertForbidden();
        $this->post('/school/houses/points/'.HousePoint::first()->id.'/undo')->assertForbidden();
    }

    public function test_captain_is_typed_by_admission_number_and_houses_are_changed_by_hand(): void
    {
        $red = $this->house('Red');
        $ada = $this->student('A1', 'female');

        $this->put("/school/houses/{$red->id}", ['name' => 'Red', 'colour' => '#dc2626', 'captain_no' => 'NOPE'])->assertSessionHasErrors('captain_no');
        $this->put("/school/houses/{$red->id}", ['name' => 'Red', 'colour' => '#dc2626', 'captain_no' => 'A1'])->assertSessionHasNoErrors();
        $this->assertSame($ada->id, $red->fresh()->captain_id);

        $this->post('/school/houses/assign', ['houses' => [$ada->id => $red->id]])->assertSessionHasNoErrors();
        $this->assertSame($red->id, $ada->fresh()->house_id);

        $this->delete("/school/houses/{$red->id}")->assertSessionHasNoErrors();
        $this->assertNull($ada->fresh()->house_id);
    }

    public function test_another_schools_house_cannot_be_used(): void
    {
        $other = $this->makeSchool('Other School');
        $theirs = House::create(['school_id' => $other->id, 'name' => 'Theirs', 'colour' => '#000000']);
        $ada = $this->student('A1', 'female');

        $this->post('/school/houses/assign', ['houses' => [$ada->id => $theirs->id]])->assertSessionHasErrors();
        $this->post('/school/houses/points', ['house_ids' => [$theirs->id], 'points' => 5, 'reason' => 'x', 'awarded_on' => now()->toDateString()])->assertSessionHasErrors();
        $this->put("/school/houses/{$theirs->id}", ['name' => 'Mine', 'colour' => '#000000'])->assertNotFound();
    }

    public function test_club_members_are_added_from_a_class_or_by_admission_number(): void
    {
        $this->post('/school/clubs', ['name' => 'JETS Club', 'is_active' => true])->assertSessionHasNoErrors();
        $club = Club::sole();
        $ada = $this->student('A1', 'female');
        $bayo = $this->student('A2', 'male');
        $chi = $this->student('A3', 'female');

        $this->post("/school/clubs/{$club->id}/members", ['student_ids' => [$ada->id]])->assertSessionHasNoErrors();
        $this->post("/school/clubs/{$club->id}/members", ['admission_nos' => "A1, A2\nZZ9"])->assertSessionHasErrors('admission_nos');

        $this->assertEqualsCanonicalizing([$ada->id, $bayo->id], ClubMember::pluck('student_id')->all());

        $member = ClubMember::where('student_id', $ada->id)->sole();
        $this->put("/school/clubs/{$club->id}/members/{$member->id}", ['role' => 'President'])->assertSessionHasNoErrors();
        $this->get("/school/clubs/{$club->id}?class_id={$this->jss1->id}")->assertOk()->assertInertia(fn ($p) => $p->component('SchoolAdmin/Clubs/Show', false)
            ->where('members.0.role', 'President')->has('candidates', 1)->where('candidates.0.id', $chi->id));

        // the student page lists the club
        $this->get("/school/students/{$ada->id}")->assertOk()->assertInertia(fn ($p) => $p->where('clubs.0.name', 'JETS Club')->where('clubs.0.role', 'President'));

        $this->delete("/school/clubs/{$club->id}/members/{$member->id}")->assertSessionHasNoErrors();
        $this->assertSame(1, ClubMember::count());
    }

    public function test_a_member_of_one_club_cannot_be_changed_through_another(): void
    {
        $a = Club::create(['school_id' => $this->school->id, 'name' => 'A']);
        $b = Club::create(['school_id' => $this->school->id, 'name' => 'B']);
        $m = ClubMember::create(['school_id' => $this->school->id, 'club_id' => $a->id, 'student_id' => $this->student('A1', 'male')->id, 'joined_on' => now()->toDateString()]);

        $this->delete("/school/clubs/{$b->id}/members/{$m->id}")->assertNotFound();
        $this->assertSame(1, ClubMember::count());
    }

    public function test_accountant_cannot_see_houses_or_clubs(): void
    {
        $this->actingAs($this->makeUser($this->school, 'accountant'));
        $this->get('/school/houses')->assertForbidden();
        $this->get('/school/clubs')->assertForbidden();
    }
}
