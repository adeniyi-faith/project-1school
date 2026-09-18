<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Return the school_id for the current session.
     * For school-scoped users it comes from their profile.
     * For super-admin (school_id = null) fall back to the first school.
     */
    protected function getSchoolId(): int
    {
        $sid = auth()->user()?->school_id;

        if (! $sid) {
            $sid = \App\Models\School::orderBy('id')->value('id');
        }

        return (int) $sid;
    }
}
