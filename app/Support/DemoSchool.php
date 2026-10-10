<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * The public demo school and its read-only demo admin. Created on first
 * use, either when a visitor opens /demo or when the demo data is loaded.
 */
class DemoSchool
{
    public const SLUG = 'demo-school';

    /** @return array{school: School, user: User} */
    public static function ensure(): array
    {
        $school = School::firstOrCreate(
            ['slug' => self::SLUG],
            ['name' => 'Demo School', 'email' => 'demo-school@example.test', 'status' => 'active'],
        );

        $user = User::firstOrCreate(
            ['email' => config('app.demo_email')],
            [
                'school_id' => $school->id,
                'name' => 'Demo Admin',
                'status' => 'active',
                // Nobody signs in with this: demo entry skips the password
                'password' => Str::random(64),
            ],
        );

        if (! $user->hasRole('school-admin')) {
            $user->assignRole(Role::findOrCreate('school-admin', 'web'));
        }

        return ['school' => $school, 'user' => $user];
    }
}
