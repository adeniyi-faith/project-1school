<?php

namespace App\Services;

use App\Models\School;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Keeps "a person who works at the school" as ONE thing: a staff record (HR, timetable, homework ...)
 * joined to a login (what they sign in with). Making either one makes or finds the other.
 */
class StaffAccountService
{
    /** Roles that belong to someone on the staff list (school admins too, so they can be class or subject teachers). */
    public const STAFF_ROLES = ['school-admin', 'principal', 'teacher', 'accountant', 'librarian', 'receptionist', 'driver', 'warden', 'store-manager'];

    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyzACDEFGHJKLMNPQRTUVWXY346789';

    public function __construct(private SupabaseAuthService $supabase) {}

    public static function isStaffRole(?string $role): bool
    {
        return $role !== null && in_array($role, self::STAFF_ROLES, true);
    }

    /** A random password that is easy to read off a screen or a slip */
    public function password(int $length = 8): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $out;
    }

    /**
     * Make a login. With Supabase login the password lives there; otherwise it is kept scrambled here.
     */
    public function createLogin(?int $schoolId, string $name, string $email, ?string $phone, string $role, ?string $password = null, string $status = 'active'): User
    {
        $password = $password ?: $this->password();
        $useSupabase = config('app.login_driver') === 'supabase';
        $supabaseId = $useSupabase ? $this->supabase->createUser($email, $password) : null;

        try {
            $user = User::create([
                'school_id' => $schoolId, 'name' => $name, 'email' => mb_strtolower(trim($email)),
                'phone' => $phone ? mb_substr($phone, 0, 20) : null, 'status' => $status,
                'supabase_id' => $supabaseId, 'password' => $useSupabase ? Str::random(64) : $password,
            ]);
            $user->assignRole(Role::findOrCreate($role, 'web'));
        } catch (\Throwable $e) {
            if ($supabaseId) {
                $this->supabase->deleteUser($supabaseId);
            }
            throw $e;
        }

        // Handed back once so the screen can show it; it is never stored in readable form
        $user->setAttribute('plain_password', $password);

        return $user;
    }

    /** Give an existing login a new password (same rules as createLogin). Returns the new password. */
    public function resetPassword(User $user, ?string $password = null): string
    {
        $password = $password ?: $this->password();

        if (config('app.login_driver') === 'supabase') {
            if ($user->supabase_id) {
                $this->supabase->setPassword($user->supabase_id, $password);
            } else {
                $user->supabase_id = $this->supabase->createUser($user->email, $password);
            }
            $user->password = Str::random(64);
        } else {
            $user->password = $password;
        }
        $user->save();

        return $password;
    }

    /** Find the staff record that belongs to this login, or make one. Safe to call again. */
    public function staffFor(User $user): ?Staff
    {
        if (! $user->school_id) {
            return null;
        }

        $existing = Staff::withoutGlobalScopes()->where('school_id', $user->school_id)->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $byEmail = Staff::withoutGlobalScopes()->where('school_id', $user->school_id)->whereNull('user_id')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])->first();
        if ($byEmail) {
            $byEmail->forceFill(['user_id' => $user->id])->save();

            return $byEmail;
        }

        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [''];
        $staff = new Staff([
            'school_id' => $user->school_id, 'user_id' => $user->id,
            'first_name' => $parts[0] !== '' ? $parts[0] : 'Staff', 'last_name' => $parts[1] ?? null,
            'gender' => 'other', 'email' => $user->email, 'phone' => $user->phone,
            'salary_type' => 'fixed', 'status' => $user->status === 'active' ? 'active' : 'on_leave',
            'joining_date' => now()->toDateString(),
        ]);
        $staff->save();

        return $staff;
    }

    /** After a login is made or its role changes: staff roles get a staff record */
    public function syncFromLogin(User $user, ?string $role): ?Staff
    {
        return self::isStaffRole($role) ? $this->staffFor($user) : null;
    }

    /**
     * Give a staff member a login. Needs an email. Returns the login with `plain_password` set.
     */
    public function giveLogin(Staff $staff, string $role, ?string $email = null, ?string $password = null): User
    {
        $email = mb_strtolower(trim((string) ($email ?: $staff->email)));
        $user = $this->createLogin((int) $staff->school_id, $staff->full_name, $email, $staff->phone, $role, $password);
        $staff->forceFill(['user_id' => $user->id, 'email' => $staff->email ?: $email])->save();

        return $user;
    }

    /**
     * Tidy up a school that has logins and staff that were made separately: join by email, and give every
     * login with a staff role a staff record. Returns how many were joined and how many were made.
     *
     * @return array{linked: int, created: int}
     */
    public function syncSchool(int|School $school): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;
        $linked = 0;
        $created = 0;

        User::withoutGlobalScopes()->where('school_id', $schoolId)->role(self::STAFF_ROLES)->orderBy('id')->each(function (User $user) use (&$linked, &$created) {
            $had = Staff::withoutGlobalScopes()->where('school_id', $user->school_id)->where('user_id', $user->id)->exists();
            if ($had) {
                return;
            }
            $before = Staff::withoutGlobalScopes()->where('school_id', $user->school_id)->count();
            $this->staffFor($user);
            Staff::withoutGlobalScopes()->where('school_id', $user->school_id)->count() > $before ? $created++ : $linked++;
        });

        return ['linked' => $linked, 'created' => $created];
    }
}
