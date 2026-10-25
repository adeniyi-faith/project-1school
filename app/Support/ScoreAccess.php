<?php

namespace App\Support;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\User;
use App\Services\StaffAccountService;

/**
 * Who may CHANGE scores. Everyone with the marks permission may still LOOK at any class.
 * Admins and principals change everything. A teacher changes only the subjects they are given in
 * the classes they are given (Classes page teacher button, or a line on the timetable), and
 * behaviour/skills ratings only for the class they are class teacher of.
 */
class ScoreAccess
{
    /** Whether this person is held to the "own subjects only" rule */
    public static function restricted(User $user): bool
    {
        return $user->hasRole('teacher') && ! $user->hasAnyRole(['super-admin', 'school-admin', 'principal']);
    }

    public static function staff(User $user): ?Staff
    {
        return Staff::where('user_id', $user->id)->first() ?? app(StaffAccountService::class)->staffFor($user);
    }

    /** Subject ids this person may change in a class; null means all of them */
    public static function subjectIds(User $user, int $classId): ?array
    {
        if (! self::restricted($user)) {
            return null;
        }
        $staff = self::staff($user);
        if (! $staff) {
            return [];
        }

        return ClassSubject::where('class_id', $classId)->where('teacher_id', $staff->id)->pluck('subject_id')
            ->merge(Timetable::where('class_id', $classId)->where('teacher_id', $staff->id)->pluck('subject_id'))
            ->filter()->unique()->values()->all();
    }

    /** Class ids this person teaches or is class teacher of; null for people who are not held to the own-classes rule */
    public static function classIds(User $user): ?array
    {
        if (! self::restricted($user)) {
            return null;
        }
        $staff = self::staff($user);
        if (! $staff) {
            return [];
        }

        return SchoolClass::where('class_teacher_staff_id', $staff->id)->pluck('id')
            ->merge(ClassSubject::where('teacher_id', $staff->id)->pluck('class_id'))
            ->merge(Timetable::where('teacher_id', $staff->id)->pluck('class_id'))
            ->unique()->values()->all();
    }

    public static function canEnter(User $user, int $classId, ?int $subjectId): bool
    {
        $ids = self::subjectIds($user, $classId);

        return $ids === null || ($subjectId !== null && in_array($subjectId, $ids, true));
    }

    /** Behaviour and skills ratings: the class teacher (or an admin / principal) */
    public static function canRate(User $user, int $classId): bool
    {
        if (! self::restricted($user)) {
            return true;
        }
        $staff = self::staff($user);

        return $staff && SchoolClass::where('id', $classId)->where('class_teacher_staff_id', $staff->id)->exists();
    }
}
