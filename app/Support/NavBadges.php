<?php

namespace App\Support;

use App\Models\AdmissionInquiry;
use App\Models\User;

/**
 * Small red numbers beside menu items for things that are waiting for the person.
 * Returns [page address => how many]; a page is only listed when the person may open it and the count is above zero.
 */
class NavBadges
{
    /** @return array<string, int> */
    public static function for(User $user): array
    {
        if (! $user->school_id && ! ActingSchool::id()) {
            return [];
        }

        $badges = [];

        // Applications and inquiries nobody has looked at yet
        if ($user->can('students.view')) {
            $new = AdmissionInquiry::where('status', 'new')->count();
            if ($new > 0) {
                $badges['/school/admissions/inquiries'] = $new;
            }
        }

        return $badges;
    }
}
