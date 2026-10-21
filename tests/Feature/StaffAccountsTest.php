<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\User;
use App\Services\StaffAccountService;
use Tests\Feature\Security\SecurityTestCase;

/** A person who works at the school is one thing: a staff card joined to a login */
class StaffAccountsTest extends SecurityTestCase
{
    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = $this->makeSchool('Join School');
        $this->admin = $this->makeUser($this->school, 'school-admin');
        $this->actingAs($this->admin);
    }

    private function person(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['school_id' => $this->school->id, 'status' => 'active'], $attributes));
        $user->assignRole($role);

        return $user;
    }

    private function staffPayload(array $extra = []): array
    {
        return array_merge([
            'first_name' => 'Ada', 'last_name' => 'Obi', 'gender' => 'female', 'salary_type' => 'fixed', 'status' => 'active',
            'email' => 'ada@example.com',
        ], $extra);
    }

    public function test_a_login_made_in_user_settings_also_appears_in_staff(): void
    {
        $this->post('/school/settings/admins', [
            'name' => 'Tunde Bello', 'email' => 'tunde@example.com', 'password' => 'secret123', 'role' => 'teacher', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'tunde@example.com')->sole();
        $staff = Staff::where('user_id', $user->id)->sole();
        $this->assertSame('Tunde', $staff->first_name);
        $this->assertSame('Bello', $staff->last_name);
        $this->assertSame($this->school->id, $staff->school_id);
    }

    public function test_a_login_with_the_same_email_as_an_existing_staff_card_joins_it_instead_of_making_a_second(): void
    {
        $card = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Ngozi', 'last_name' => 'Eze', 'gender' => 'female', 'email' => 'Ngozi@Example.com', 'salary_type' => 'fixed', 'status' => 'active']);

        $this->post('/school/settings/admins', [
            'name' => 'Ngozi Eze', 'email' => 'ngozi@example.com', 'password' => 'secret123', 'role' => 'teacher', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Staff::where('school_id', $this->school->id)->count());
        $this->assertSame(User::where('email', 'ngozi@example.com')->value('id'), $card->fresh()->user_id);
    }

    public function test_a_school_admin_login_also_makes_a_staff_card(): void
    {
        $this->post('/school/settings/admins', [
            'name' => 'Big Boss', 'email' => 'boss@example.com', 'password' => 'secret123', 'role' => 'school-admin', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Staff::where('school_id', $this->school->id)->where('email', 'boss@example.com')->count());
    }

    public function test_adding_staff_can_make_the_login_in_the_same_step_and_shows_the_details_once(): void
    {
        $this->post('/school/staff', $this->staffPayload(['create_login' => true, 'login_role' => 'accountant']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('login_details', fn ($rows) => $rows[0]['email'] === 'ada@example.com' && strlen($rows[0]['password']) === 8);

        $staff = Staff::where('email', 'ada@example.com')->sole();
        $user = User::findOrFail($staff->user_id);
        $this->assertTrue($user->hasRole('accountant'));
        $this->assertSame($this->school->id, $user->school_id);
    }

    public function test_a_chosen_password_is_used_and_can_sign_in(): void
    {
        $this->post('/school/staff', $this->staffPayload(['create_login' => true, 'login_role' => 'teacher', 'login_password' => 'MyOwnPass1']))->assertSessionHasNoErrors();

        $user = User::where('email', 'ada@example.com')->sole();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('MyOwnPass1', $user->password));
    }

    public function test_a_login_needs_an_email_and_a_free_one(): void
    {
        $this->post('/school/staff', $this->staffPayload(['email' => '', 'create_login' => true, 'login_role' => 'teacher']))->assertSessionHasErrors('email');
        $this->person('teacher', ['email' => 'taken@example.com']);
        $this->post('/school/staff', $this->staffPayload(['email' => 'taken@example.com', 'create_login' => true, 'login_role' => 'teacher']))->assertSessionHasErrors('email');
        $this->assertSame(0, Staff::where('first_name', 'Ada')->count(), 'nothing is half-made when the login is refused');
    }

    public function test_a_staff_member_without_a_login_can_be_given_one_and_a_new_password(): void
    {
        $staff = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Kemi', 'gender' => 'female', 'email' => 'kemi@example.com', 'salary_type' => 'fixed', 'status' => 'active']);

        $this->post("/school/staff/{$staff->id}/login", ['role' => 'librarian'])->assertSessionHasNoErrors()->assertSessionHas('login_details');
        $user = User::findOrFail($staff->fresh()->user_id);
        $this->assertTrue($user->hasRole('librarian'));

        $this->post("/school/staff/{$staff->id}/login", ['role' => 'librarian'])->assertSessionHas('error');

        $old = $user->password;
        $this->post("/school/staff/{$staff->id}/login/reset")->assertSessionHas('login_details');
        $this->assertNotSame($old, $user->fresh()->password);
    }

    public function test_a_teacher_cannot_give_logins(): void
    {
        $teacher = $this->makeUser($this->school, 'teacher');
        $staff = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Kemi', 'gender' => 'female', 'email' => 'kemi@example.com', 'salary_type' => 'fixed', 'status' => 'active']);

        $this->actingAs($teacher)->post("/school/staff/{$staff->id}/login", ['role' => 'teacher'])->assertForbidden();
    }

    public function test_tidy_up_joins_old_logins_to_staff_by_email_and_makes_cards_for_the_rest(): void
    {
        $matched = $this->person('teacher', ['email' => 'joined@example.com', 'name' => 'Joined Teacher']);
        $lonely = $this->person('teacher', ['email' => 'lonely@example.com', 'name' => 'Lonely Teacher']);
        $card = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Joined', 'gender' => 'male', 'email' => 'joined@example.com', 'salary_type' => 'fixed', 'status' => 'active']);

        $result = app(StaffAccountService::class)->syncSchool($this->school->id);

        // the lonely teacher and the school admin from setUp both get a card
        $this->assertSame(['linked' => 1, 'created' => 2], $result);
        $this->assertSame($matched->id, $card->fresh()->user_id);
        $this->assertNotNull(Staff::where('user_id', $lonely->id)->first());
        $this->assertSame(['linked' => 0, 'created' => 0], app(StaffAccountService::class)->syncSchool($this->school->id), 'safe to run again');
    }

    public function test_class_teacher_and_subject_teachers_are_saved_and_must_belong_to_the_school(): void
    {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'JSS 1', 'numeric_name' => 7]);
        $maths = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MTH', 'type' => 'theory', 'full_marks' => 100, 'pass_marks' => 40]);
        $line = ClassSubject::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'subject_id' => $maths->id]);
        $teacher = Staff::create(['school_id' => $this->school->id, 'first_name' => 'Mr', 'last_name' => 'Ade', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active']);

        $this->post("/school/classes/{$class->id}/teachers", [
            'class_teacher_staff_id' => $teacher->id, 'subjects' => [['id' => $line->id, 'teacher_id' => $teacher->id]],
        ])->assertSessionHasNoErrors();
        $this->assertSame($teacher->id, $class->fresh()->class_teacher_staff_id);
        $this->assertSame($teacher->id, $line->fresh()->teacher_id);

        $other = School::create(['name' => 'Other', 'slug' => 'other-join', 'email' => 'o@o.test', 'status' => 'active']);
        $stranger = Staff::withoutGlobalScopes()->create(['school_id' => $other->id, 'first_name' => 'Out', 'gender' => 'male', 'salary_type' => 'fixed', 'status' => 'active']);
        $this->post("/school/classes/{$class->id}/teachers", ['class_teacher_staff_id' => $stranger->id])->assertSessionHasErrors('class_teacher_staff_id');
    }

    public function test_my_classes_lists_only_the_teachers_own_classes(): void
    {
        $teacherUser = $this->person('teacher', ['name' => 'Mrs Okoro']);
        $staff = app(StaffAccountService::class)->staffFor($teacherUser);
        $mine = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary 3', 'numeric_name' => 3, 'class_teacher_staff_id' => $staff->id]);
        SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary 4', 'numeric_name' => 4]);
        Student::create(['school_id' => $this->school->id, 'class_id' => $mine->id, 'first_name' => 'A', 'last_name' => 'B', 'admission_no' => 'X1', 'gender' => 'male', 'status' => 'active']);

        $this->actingAs($teacherUser)->get('/school/my-classes')->assertOk()->assertInertia(fn ($page) => $page
            ->component('SchoolAdmin/MyClasses/Index', false)
            ->has('classes', 1)
            ->where('classes.0.name', 'Primary 3')
            ->where('classes.0.students', 1)
            ->where('classes.0.class_teacher', true));
    }
}
