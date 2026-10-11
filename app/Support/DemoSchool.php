<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\ResultSheet;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TermResultSummary;
use App\Models\User;
use App\Scopes\SchoolScope;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * The public demo school and its read-only demo accounts, one per account type.
 * Created on first use, either when a visitor picks an account type on /demo
 * or when the demo data is loaded.
 */
class DemoSchool
{
    public const SLUG = 'demo-school';

    /**
     * The account types a visitor can try: key => [role, label, what they see].
     * "admin" keeps the original demo email (config app.demo_email).
     */
    public const ACCOUNTS = [
        'admin' => ['school-admin', 'School Admin', 'Everything: students, fees, results, staff and settings.'],
        'principal' => ['principal', 'Principal', 'Results to approve, staff, attendance and reports.'],
        'teacher' => ['teacher', 'Teacher', 'Attendance, scores, CBT tests, homework and lesson plans.'],
        'accountant' => ['accountant', 'Bursar', 'Invoices, payments, scholarships and what is still owed.'],
        'parent' => ['parent', 'Parent', "A child's results, report cards, fees and attendance."],
        'student' => ['student', 'Student', 'Own results, timetable, homework and CBT tests.'],
        'librarian' => ['librarian', 'Librarian', 'Books, borrowing and returns.'],
        'receptionist' => ['receptionist', 'Front Desk', 'Visitors, admission inquiries and announcements.'],
        'driver' => ['driver', 'Driver', 'Bus routes and the students on them.'],
        'warden' => ['warden', 'Hostel Warden', 'Hostel rooms and beds.'],
        'store-manager' => ['store-manager', 'Store Keeper', 'Store items and what is running low.'],
    ];

    /** @return array{school: School, user: User} */
    public static function ensure(): array
    {
        $school = self::school();

        return ['school' => $school, 'user' => self::account($school, 'admin')];
    }

    public static function school(): School
    {
        return School::firstOrCreate(
            ['slug' => self::SLUG],
            ['name' => 'Demo School', 'email' => 'demo-school@example.test', 'status' => 'active'],
        );
    }

    /**
     * The demo account for one account type, ready to sign in. A student account is
     * linked to a pupil with published results, and the parent account to that pupil's
     * parent, so both see real sample data. Null when the demo school has no pupils yet.
     */
    public static function userFor(string $key): ?User
    {
        if (! isset(self::ACCOUNTS[$key])) {
            return null;
        }

        $school = self::school();

        if (in_array($key, ['student', 'parent'], true)) {
            $student = self::demoStudent($school);
            if (! $student) {
                return null;
            }

            if ($key === 'student') {
                $user = self::account($school, 'student', $student->full_name);
                Student::withoutGlobalScope(SchoolScope::class)->where('user_id', $user->id)->whereKeyNot($student->id)->update(['user_id' => null]);
                $student->forceFill(['user_id' => $user->id])->save();

                return $user;
            }

            $guardian = Guardian::withoutGlobalScope(SchoolScope::class)->where('school_id', $school->id)->find($student->guardian_id);
            if (! $guardian) {
                return null;
            }
            $user = self::account($school, 'parent', $guardian->name);
            // One child's parent: unlink any other parent record this account held before a refill
            Guardian::withoutGlobalScope(SchoolScope::class)->where('user_id', $user->id)->whereKeyNot($guardian->id)->update(['user_id' => null]);
            $guardian->forceFill(['user_id' => $user->id])->save();

            return $user;
        }

        return self::account($school, $key);
    }

    /** Whether the parent and student accounts have sample data to show. */
    public static function hasPupils(): bool
    {
        $school = School::where('slug', self::SLUG)->first();

        return $school !== null && self::demoStudent($school) !== null;
    }

    /** Every demo account is read-only (see DemoReadOnly). */
    public static function isDemoUser(?User $user): bool
    {
        if (! $user || ! config('app.demo_email')) {
            return false;
        }

        return in_array(strtolower($user->email), array_map('strtolower', self::emails()), true);
    }

    /** The account type key of a demo user, such as "teacher". */
    public static function keyOf(User $user): ?string
    {
        $key = array_search(strtolower($user->email), array_map('strtolower', self::emails()), true);

        return $key === false ? null : $key;
    }

    /** @return array<string, string> account type key => email */
    public static function emails(): array
    {
        $admin = (string) config('app.demo_email');
        $domain = Str::after($admin, '@') ?: 'schoolruns.demo';

        $emails = [];
        foreach (array_keys(self::ACCOUNTS) as $key) {
            $emails[$key] = $key === 'admin' ? $admin : "demo-{$key}@{$domain}";
        }

        return $emails;
    }

    private static function account(School $school, string $key, ?string $name = null): User
    {
        [$role, $label] = self::ACCOUNTS[$key];

        $user = User::firstOrCreate(
            ['email' => self::emails()[$key]],
            [
                'school_id' => $school->id,
                'name' => $name ?? ($key === 'admin' ? 'Demo Admin' : "Demo {$label}"),
                'status' => 'active',
                // Nobody signs in with this: demo entry skips the password
                'password' => Str::random(64),
            ],
        );

        if ($name && $user->name !== $name) {
            $user->forceFill(['name' => $name])->save();
        }

        if (! $user->hasRole($role) || $user->roles()->count() > 1) {
            $user->syncRoles([Role::findOrCreate($role, 'web')]);
        }

        return $user;
    }

    /**
     * The pupil the student and parent accounts look at: the one already linked, else an active
     * pupil with a parent and published results (top class first), else any active pupil with a parent.
     */
    private static function demoStudent(School $school): ?Student
    {
        $students = Student::withoutGlobalScope(SchoolScope::class)->where('school_id', $school->id)
            ->where('status', 'active')->whereNotNull('guardian_id');

        $linkedUser = User::where('email', self::emails()['student'])->value('id');
        if ($linkedUser && $found = (clone $students)->where('user_id', $linkedUser)->first()) {
            return $found;
        }

        $free = (clone $students)->whereNull('user_id');

        $published = TermResultSummary::withoutGlobalScope(SchoolScope::class)->select('student_id')
            ->whereIn('result_sheet_id', ResultSheet::withoutGlobalScope(SchoolScope::class)->select('id')
                ->where('school_id', $school->id)->whereIn('status', ['published', 'locked']));

        // A pupil from the top classes shows the most: CBT, full results and report cards
        $classes = SchoolClass::withoutGlobalScope(SchoolScope::class)->where('school_id', $school->id)
            ->orderByDesc('numeric_name')->pluck('id');
        foreach ($classes as $classId) {
            if ($found = (clone $free)->where('class_id', $classId)->whereIn('id', $published)->orderBy('id')->first()) {
                return $found;
            }
        }

        return $free->orderBy('id')->first();
    }
}
