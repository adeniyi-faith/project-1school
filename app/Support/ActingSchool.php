<?php

namespace App\Support;

use App\Models\School;

/**
 * The platform owner (super admin), and platform staff allowed to, can step inside one school and work there with full access, as that school's own
 * admin would. The chosen school is kept in the session. Nobody else can ever act as another school. Staff need the right permission and the school must be one of theirs
 * (checked again on every request by the ActAsSchool middleware).
 */
class ActingSchool
{
    public const KEY = 'acting_school_id';

    /** The school the signed-in super admin is working inside, or null (everyone else, or no school chosen) */
    public static function id(): ?int
    {
        $user = auth()->user();
        if (! PlatformAccess::isPlatform($user) || ! app()->bound('session.store')) {
            return null;
        }

        $id = session(self::KEY);

        return $id ? (int) $id : null;
    }

    public static function enter(School $school): void
    {
        session([self::KEY => $school->id]);
    }

    public static function leave(): void
    {
        session()->forget(self::KEY);
    }
}
