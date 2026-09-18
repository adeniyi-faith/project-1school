<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        /* ── KPI Cards ── */
        $totalSchools   = School::count();
        $activeSchools  = School::where('status', 'active')->count();
        $suspended      = School::where('status', 'suspended')->count();
        $totalUsers     = User::count();
        $totalStudents  = Student::count();
        $totalStaff     = Staff::count();

        $activeSubscriptions = SchoolSubscription::where('status', 'active')->count();
        $trialSubscriptions  = SchoolSubscription::where('is_trial', true)
            ->where('status', 'active')->count();

        $totalRevenue      = SchoolSubscription::sum('amount_paid');
        $revenueThisMonth  = SchoolSubscription::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('amount_paid');

        $totalPackages = Package::where('is_active', true)->count();
        $totalCoupons  = Coupon::where('is_active', true)->count();

        /* ── School Growth (last 12 months) ── */
        $schoolGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $schoolGrowth[] = [
                'month'   => $d->format('M y'),
                'schools' => School::whereYear('created_at', $d->year)
                    ->whereMonth('created_at', $d->month)->count(),
                'users'   => User::whereYear('created_at', $d->year)
                    ->whereMonth('created_at', $d->month)->count(),
            ];
        }

        /* ── Revenue Trend (last 6 months) ── */
        $revenueTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $revenueTrend[] = [
                'month'   => $d->format('M y'),
                'revenue' => (float) SchoolSubscription::whereYear('created_at', $d->year)
                    ->whereMonth('created_at', $d->month)->sum('amount_paid'),
            ];
        }

        /* ── School Status Distribution ── */
        $statusDistribution = School::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        /* ── Package Distribution ── */
        $packageDistribution = SchoolSubscription::with('package:id,name')
            ->select('package_id', DB::raw('count(*) as count'))
            ->groupBy('package_id')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->package?->name ?? 'Unknown',
                'value' => $row->count,
            ]);

        /* ── Top Schools by Students ── */
        $topSchools = School::withCount('users')
            ->with(['academicYears' => fn ($q) => $q->where('is_current', true)])
            ->select('schools.*')
            ->addSelect(DB::raw('(SELECT COUNT(*) FROM students WHERE students.school_id = schools.id AND students.deleted_at IS NULL) as students_count'))
            ->orderByDesc('students_count')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'status'   => $s->status,
                'students' => $s->students_count,
                'users'    => $s->users_count,
            ]);

        /* ── Recent Schools ── */
        $recentSchools = School::latest()
            ->limit(5)
            ->get(['id', 'name', 'status', 'email', 'city', 'created_at'])
            ->map(fn ($s) => [
                'id'         => $s->id,
                'name'       => $s->name,
                'status'     => $s->status,
                'email'      => $s->email,
                'city'       => $s->city,
                'created_at' => $s->created_at->diffForHumans(),
            ]);

        /* ── Expiring Subscriptions (next 30 days) ── */
        $expiringSubscriptions = SchoolSubscription::with('school:id,name', 'package:id,name')
            ->where('status', 'active')
            ->whereBetween('end_date', [$now->toDateString(), $now->copy()->addDays(30)->toDateString()])
            ->orderBy('end_date')
            ->limit(5)
            ->get()
            ->map(fn ($sub) => [
                'id'          => $sub->id,
                'school'      => $sub->school?->name,
                'package'     => $sub->package?->name,
                'end_date'    => $sub->end_date?->format('d M Y'),
                'days_left'   => $now->diffInDays($sub->end_date, false),
            ]);

        /* ── Recent Users ── */
        $recentUsers = User::leftJoin('schools', 'users.school_id', '=', 'schools.id')
            ->latest('users.created_at')
            ->limit(6)
            ->get(['users.id', 'users.name', 'users.email', 'users.created_at', 'schools.name as school_name'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'email'      => $u->email,
                'school'     => $u->school_name ?? 'Platform',
                'created_at' => $u->created_at->diffForHumans(),
            ]);

        return Inertia::render('SuperAdmin/Dashboard', [
            'kpi' => [
                'totalSchools'        => $totalSchools,
                'activeSchools'       => $activeSchools,
                'suspendedSchools'    => $suspended,
                'totalUsers'          => $totalUsers,
                'totalStudents'       => $totalStudents,
                'totalStaff'          => $totalStaff,
                'activeSubscriptions' => $activeSubscriptions,
                'trialSubscriptions'  => $trialSubscriptions,
                'totalRevenue'        => (float) $totalRevenue,
                'revenueThisMonth'    => (float) $revenueThisMonth,
                'totalPackages'       => $totalPackages,
                'totalCoupons'        => $totalCoupons,
            ],
            'schoolGrowth'          => $schoolGrowth,
            'revenueTrend'          => $revenueTrend,
            'statusDistribution'    => $statusDistribution,
            'packageDistribution'   => $packageDistribution,
            'topSchools'            => $topSchools,
            'recentSchools'         => $recentSchools,
            'expiringSubscriptions' => $expiringSubscriptions,
            'recentUsers'           => $recentUsers,
        ]);
    }
}
