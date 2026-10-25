<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Staff;
use Tests\Feature\Security\SecurityTestCase;

/** The register opens on a class teacher's own class when they have exactly one */
class AttendanceDefaultClassTest extends SecurityTestCase
{
    private function teacherWithClasses(int $count): array
    {
        $school = $this->makeSchool('Register School');
        $user = $this->makeUser($school, 'teacher');
        $staff = Staff::create(['school_id' => $school->id, 'user_id' => $user->id, 'first_name' => 'Ada', 'gender' => 'female']);
        $classes = [];
        for ($i = 1; $i <= $count; $i++) {
            $classes[] = SchoolClass::create(['school_id' => $school->id, 'name' => "Class $i", 'numeric_name' => $i, 'class_teacher_staff_id' => $staff->id]);
        }
        SchoolClass::create(['school_id' => $school->id, 'name' => 'Other', 'numeric_name' => 9]);

        return [$user, $classes];
    }

    public function test_one_class_opens_straight_onto_that_class(): void
    {
        [$user, $classes] = $this->teacherWithClasses(1);

        $this->actingAs($user)->get('/school/attendance')
            ->assertInertia(fn ($p) => $p->where('filters.class_id', (string) $classes[0]->id));
    }

    public function test_a_class_chosen_in_the_address_wins(): void
    {
        [$user, $classes] = $this->teacherWithClasses(1);
        $other = SchoolClass::where('name', 'Other')->first();

        $this->actingAs($user)->get('/school/attendance?class_id='.$other->id)
            ->assertInertia(fn ($p) => $p->where('filters.class_id', (string) $other->id));
    }

    public function test_two_classes_still_ask_which_one(): void
    {
        [$user] = $this->teacherWithClasses(2);

        $this->actingAs($user)->get('/school/attendance')
            ->assertInertia(fn ($p) => $p->where('filters.class_id', null));
    }
}
