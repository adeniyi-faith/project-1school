<?php

namespace Tests\Feature\Security;

use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Student;
use App\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TenantCoverageTest extends SecurityTestCase
{
    /**
     * Models that carry a school_id but are deliberately NOT filtered by the
     * signed-in user's school, and why. Anything new must be added here on purpose.
     */
    private const ALLOWED_UNFILTERED = [
        'SchoolModule'       => 'platform setting managed by the super admin, always read by explicit school id',
        'SchoolSetting'      => 'always read and written with an explicit school id (SchoolSetting::get/set)',
        'SchoolSubscription' => 'platform billing record managed by the super admin',
        'User'               => 'needed to look people up at login, before anyone is signed in; screens filter by school_id',
    ];

    public function test_every_model_with_a_school_id_column_is_limited_to_one_school(): void
    {
        $unfiltered = [];

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');
            $model = new $class;

            if (! $model instanceof Model || ! Schema::hasColumn($model->getTable(), 'school_id')) {
                continue;
            }

            if (! array_key_exists(SchoolScope::class, $model->getGlobalScopes())) {
                $unfiltered[] = basename($file, '.php');
            }
        }

        sort($unfiltered);
        $expected = array_keys(self::ALLOWED_UNFILTERED);
        sort($expected);

        $this->assertSame($expected, $unfiltered, 'A model with a school_id is missing the "only my school" rule');
    }

    public function test_one_school_cannot_open_or_change_another_schools_records_by_id(): void
    {
        $a = $this->makeSchool('School A');
        $b = $this->makeSchool('School B');
        $adminA = $this->makeUser($a, 'school-admin');

        $class = SchoolClass::withoutGlobalScopes()->create(['school_id' => $b->id, 'name' => 'B Class']);
        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $b->id, 'class_id' => $class->id, 'first_name' => 'Ada', 'last_name' => 'Obi',
            'gender' => 'female', 'admission_no' => 'B1', 'status' => 'active',
            'date_of_birth' => '2015-01-01', 'admission_date' => '2024-09-01',
        ]);
        $staff = Staff::withoutGlobalScopes()->create([
            'school_id' => $b->id, 'emp_id' => 'EB1', 'first_name' => 'T', 'last_name' => 'S',
            'status' => 'active', 'salary_type' => 'fixed', 'gender' => 'male',
        ]);
        $exam = Exam::withoutGlobalScopes()->create([
            'school_id' => $b->id, 'class_id' => $class->id, 'name' => 'B Exam', 'type' => 'mid_term', 'status' => 'draft',
        ]);

        $this->actingAs($adminA);

        $this->get(route('school.students.show', $student->id), ['X-Inertia' => 'true'])->assertNotFound();
        $this->delete(route('school.students.destroy', $student->id))->assertNotFound();
        $this->get(route('school.staff.show', $staff->id), ['X-Inertia' => 'true'])->assertNotFound();
        $this->delete(route('school.staff.destroy', $staff->id))->assertNotFound();
        $this->delete(route('school.exams.destroy', $exam->id))->assertNotFound();
        $this->delete(route('school.classes.destroy', $class->id))->assertNotFound();

        $this->assertNotNull(Student::withoutGlobalScopes()->find($student->id));
        $this->assertNotNull(Staff::withoutGlobalScopes()->find($staff->id));
        $this->assertNotNull(Exam::withoutGlobalScopes()->find($exam->id));
        $this->assertNotNull(SchoolClass::withoutGlobalScopes()->find($class->id));
    }
}
