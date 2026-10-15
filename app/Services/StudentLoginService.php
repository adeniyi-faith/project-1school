<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Making sign-in accounts for many students (and their parents) at once. Each person gets a username
 * (their own email if the school has one, otherwise a made-up address that only works as a username)
 * and a random password. The passwords are shown once on a printable sheet and are never stored
 * in a readable form; a lost one is replaced by running this again with "give new passwords".
 */
class StudentLoginService
{
    /** No 0/O, 1/l/I: easy to read off a printed slip */
    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyzACDEFGHJKLMNPQRTUVWXY346789';

    public function __construct(private SupabaseAuthService $supabase) {}

    /**
     * Student ids this run covers: active students of one class (or the whole school).
     *
     * @return list<int>
     */
    public function targets(int $schoolId, ?int $classId, ?int $sectionId = null): array
    {
        return Student::where('school_id', $schoolId)->where('status', 'active')
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('class_id')->orderBy('first_name')->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    /**
     * Make (or reset) the logins for some students. Returns one line per account made and one per problem.
     *
     * @param  list<int>  $studentIds
     * @return array{lines: list<array<string, string>>, skipped: int, problems: list<string>}
     */
    public function run(int $schoolId, array $studentIds, bool $parents, bool $reset, bool $students = true): array
    {
        $school = School::find($schoolId);
        $pupils = Student::where('school_id', $schoolId)->whereIn('id', $studentIds)->with(['schoolClass:id,name', 'section:id,name', 'guardian'])->get();
        $lines = [];
        $problems = [];
        $skipped = 0;
        $doneGuardians = [];

        foreach ($pupils as $student) {
            $class = trim(($student->schoolClass?->name ?? '').($student->section ? ' '.$student->section->name : ''));

            try {
                if (! $students) {
                    // Parent logins only
                } elseif ($student->user_id && ! $reset) {
                    $skipped++;
                } else {
                    $line = $this->account($school, $student->user_id, 'student', $student->full_name, $student->email, $this->address($school, 'students', $student->admission_no ?: 'student'.$student->id), $student->phone);
                    $student->forceFill(['user_id' => $line['user_id']])->save();
                    $lines[] = ['role' => 'Student', 'name' => $student->full_name, 'class' => $class, 'admission_no' => (string) $student->admission_no, 'username' => $line['email'], 'password' => $line['password']];
                }
            } catch (\Throwable $e) {
                report($e);
                $problems[] = "{$student->full_name}: the login could not be made.";
            }

            $guardian = $parents ? $student->guardian : null;
            if (! $guardian || isset($doneGuardians[$guardian->id])) {
                continue;
            }
            $doneGuardians[$guardian->id] = true;

            try {
                if ($guardian->user_id && ! $reset) {
                    $skipped++;

                    continue;
                }
                $children = Student::where('guardian_id', $guardian->id)->where('status', 'active')->get(['first_name', 'last_name'])->map->full_name->implode(', ');
                $phone = preg_replace('/\D+/', '', (string) $guardian->phone);
                $fallback = $phone !== '' ? 'p'.$phone : 'parent'.$guardian->id;
                $line = $this->account($school, $guardian->user_id, 'parent', $guardian->name, $guardian->email, $this->address($school, 'parents', $fallback), $guardian->phone);
                $guardian->forceFill(['user_id' => $line['user_id']])->save();
                $lines[] = ['role' => 'Parent', 'name' => $guardian->name, 'class' => 'Parent of '.$children, 'admission_no' => '', 'username' => $line['email'], 'password' => $line['password']];
            } catch (\Throwable $e) {
                report($e);
                $problems[] = "{$guardian->name} (parent): the login could not be made.";
            }
        }

        return ['lines' => $lines, 'skipped' => $skipped, 'problems' => $problems];
    }

    /** A random password that is easy to read off a printed slip */
    public function password(int $length = 8): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $out;
    }

    /**
     * One account: made new, or (when the person already has one) given a new password.
     *
     * @return array{user_id: int, email: string, password: string}
     */
    private function account(?School $school, ?int $existingUserId, string $role, string $name, ?string $ownEmail, string $madeUp, ?string $phone): array
    {
        $password = $this->password();
        $useSupabase = config('app.login_driver') === 'supabase';
        $user = $existingUserId ? User::where('school_id', $school?->id)->find($existingUserId) : null;

        if ($user) {
            if ($useSupabase) {
                if ($user->supabase_id) {
                    $this->supabase->setPassword($user->supabase_id, $password);
                } else {
                    $user->supabase_id = $this->supabase->createUser($user->email, $password);
                }
                $user->password = Str::random(64);
            } else {
                $user->password = $password;
            }
            $user->status = 'active';
            $user->save();

            return ['user_id' => $user->id, 'email' => $user->email, 'password' => $password];
        }

        $email = $this->freeEmail($ownEmail, $madeUp);
        $supabaseId = $useSupabase ? $this->supabase->createUser($email, $password) : null;

        try {
            $user = User::create([
                'school_id' => $school?->id, 'name' => $name, 'email' => $email, 'phone' => $phone ? mb_substr($phone, 0, 20) : null,
                'status' => 'active', 'supabase_id' => $supabaseId, 'password' => $useSupabase ? Str::random(64) : $password,
            ]);
            $user->assignRole(Role::findOrCreate($role, 'web'));
        } catch (\Throwable $e) {
            if ($supabaseId) {
                $this->supabase->deleteUser($supabaseId);
            }
            throw $e;
        }

        return ['user_id' => $user->id, 'email' => $email, 'password' => $password];
    }

    /** The person's own email if no account uses it yet, otherwise the made-up username address (made unique) */
    private function freeEmail(?string $own, string $madeUp): string
    {
        $own = $own ? mb_strtolower(trim($own)) : '';
        if ($own !== '' && filter_var($own, FILTER_VALIDATE_EMAIL) && ! User::withTrashed()->where('email', $own)->exists()) {
            return $own;
        }

        [$local, $domain] = explode('@', $madeUp, 2);
        $email = $madeUp;
        for ($i = 2; User::withTrashed()->where('email', $email)->exists(); $i++) {
            $email = "{$local}-{$i}@{$domain}";
        }

        return $email;
    }

    /** e.g. adm-2026-0013@students.gracefield.school */
    private function address(?School $school, string $group, string $local): string
    {
        $slug = Str::slug($school?->slug ?: $school?->name ?: 'school') ?: 'school';

        return mb_strtolower(Str::slug($local)).'@'.$group.'.'.$slug.'.school';
    }
}
