<?php

namespace App\Support;

use App\Models\AdmissionInquiry;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\ResultSheet;
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

        // Leave requests waiting for a decision
        if ($user->can('leave.approve') && $user->can('leave.view')) {
            $pending = LeaveRequest::where('status', 'pending')->count();
            if ($pending > 0) {
                $badges['/school/hr/leaves'] = $pending;
            }
        }

        // Class results sent in or approved, waiting for the head to approve or publish them
        if ($user->can('results.publish') && $user->can('results.view')) {
            $waiting = ResultSheet::whereIn('status', ['submitted', 'approved'])->count();
            if ($waiting > 0) {
                $badges['/school/results'] = $waiting;
            }
        }

        // Unpaid invoices that are past their date (or have a part that is past its date)
        if ($user->can('fees.view')) {
            $today = now()->toDateString();
            $late = Invoice::whereIn('status', ['unpaid', 'partial'])
                ->where(fn ($q) => $q->whereDate('due_date', '<', $today)
                    ->orWhereHas('instalments', fn ($p) => $p->whereDate('due_date', '<', $today)->whereColumn('paid', '<', 'amount')))
                ->count();
            if ($late > 0) {
                $badges['/school/fees/invoices'] = $late;
            }
        }

        return $badges;
    }
}
