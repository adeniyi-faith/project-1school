<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Big, careful changes to a whole school: deleting after a waiting period.
 * Every change is logged with who did it and why. Deleting never wipes data: a purged school is only hidden
 * (soft-deleted) and can be brought back from the Schools page.
 */
class SchoolLifecycleService
{
    public const GRACE_DAYS = 30;

    public function scheduleDeletion(School $school, User $by, string $reason): School
    {
        DB::transaction(function () use ($school, $by, $reason) {
            $school->forceFill([
                'delete_scheduled_at' => now()->addDays(self::GRACE_DAYS), 'delete_reason' => $reason,
                'delete_requested_by' => $by->id, 'status_before_delete' => $school->status,
                // Paused straight away: the school's people cannot sign in while the clock runs
                'status' => 'suspended',
            ])->save();

            activity()->causedBy($by)->performedOn($school)
                ->withProperties(['reason' => $reason, 'delete_on' => $school->delete_scheduled_at->toDateString()])->log('School deletion scheduled');
        });

        return $school;
    }

    public function cancelDeletion(School $school, User $by): School
    {
        DB::transaction(function () use ($school, $by) {
            $school->forceFill([
                'status' => $school->status_before_delete ?: 'active',
                'delete_scheduled_at' => null, 'delete_reason' => null, 'delete_requested_by' => null, 'status_before_delete' => null,
            ])->save();

            activity()->causedBy($by)->performedOn($school)->log('School deletion cancelled');
        });

        return $school;
    }

    /** Hides every school whose waiting period is over. Runs whenever the schools list is opened, so no scheduler is needed. */
    public function purgeDue(?User $by = null): int
    {
        $due = School::whereNotNull('delete_scheduled_at')->where('delete_scheduled_at', '<=', now())->get();

        foreach ($due as $school) {
            activity()->causedBy($by)->performedOn($school)->withProperties(['reason' => $school->delete_reason])->log('School deleted after the waiting period');
            $school->delete();
        }

        return $due->count();
    }

    public function restore(School $school, User $by): School
    {
        $school->restore();
        $school->forceFill([
            // Comes back paused, so a person decides when its people may sign in again
            'status' => 'suspended',
            'delete_scheduled_at' => null, 'delete_reason' => null, 'delete_requested_by' => null, 'status_before_delete' => null,
        ])->save();

        activity()->causedBy($by)->performedOn($school)->log('Deleted school restored');

        return $school;
    }
}
