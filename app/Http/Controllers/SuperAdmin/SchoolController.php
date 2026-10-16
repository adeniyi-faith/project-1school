<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SchoolRequest;
use App\Models\AiUsage;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\School;
use App\Models\SchoolModule;
use App\Models\SchoolSetting;
use App\Models\SchoolSubscription;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\StaffAccountService;
use App\Support\ActingSchool;
use App\Support\SchoolTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        $schools = School::withTrashed(false)
            ->withCount('users')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Per-school numbers for the page's rows, worked out in a few grouped queries
        $ids = collect($schools->items())->pluck('id');
        $students = Student::withoutGlobalScopes()->whereIn('school_id', $ids)->where('status', 'active')->selectRaw('school_id, count(*) as n')->groupBy('school_id')->pluck('n', 'school_id');
        $lastLogin = User::whereIn('school_id', $ids)->selectRaw('school_id, max(last_login_at) as last')->groupBy('school_id')->pluck('last', 'school_id');
        $plans = SchoolSubscription::with('package:id,name')->whereIn('school_id', $ids)->latest('id')->get()->unique('school_id')->keyBy('school_id');

        $rows = collect($schools->items())->map(fn ($school) => array_merge($school->toArray(), [
            'students_count' => (int) ($students[$school->id] ?? 0),
            'last_login_at'  => $lastLogin[$school->id] ?? null,
            'plan'           => $plans[$school->id]->package->name ?? null,
            'plan_status'    => $plans[$school->id]->status ?? null,
            'plan_ends'      => optional($plans[$school->id]->end_date ?? null)->toDateString(),
        ]))->all();

        return Inertia::render('SuperAdmin/Schools/Index', [
            'schools' => [
                'data'  => $rows,
                'meta'  => [
                    'total'        => $schools->total(),
                    'per_page'     => $schools->perPage(),
                    'current_page' => $schools->currentPage(),
                    'last_page'    => $schools->lastPage(),
                    'from'         => $schools->firstItem(),
                    'to'           => $schools->lastItem(),
                ],
                'links' => [
                    'first' => $schools->url(1),
                    'last'  => $schools->url($schools->lastPage()),
                    'prev'  => $schools->previousPageUrl(),
                    'next'  => $schools->nextPageUrl(),
                ],
            ],
            'filters' => $request->only('search', 'status'),
            'stats'   => [
                'total'     => School::count(),
                'active'    => School::where('status', 'active')->count(),
                'suspended' => School::where('status', 'suspended')->count(),
            ],
        ]);
    }

    /** Step inside a school and work there with full access */
    public function enter(School $school): RedirectResponse
    {
        ActingSchool::enter($school);

        return redirect('/school/reports/dashboard')->with('success', "You are now working inside {$school->name}.");
    }

    /** Step back out to the platform */
    public function leave(): RedirectResponse
    {
        ActingSchool::leave();

        return redirect()->route('super-admin.schools.index');
    }

    public function create(): Response
    {
        return Inertia::render('SuperAdmin/Schools/Create');
    }

    public function store(SchoolRequest $request, StaffAccountService $accounts): RedirectResponse
    {
        $data = $request->validated();
        // Checked before anything is made, so a typo in the administrator's email leaves no half-made school
        $extra = $request->validate([
            'admin_name'     => 'nullable|required_with:admin_email|string|max:255',
            'admin_email'    => 'nullable|required_with:admin_name|email|max:255|unique:users,email',
            'admin_password' => 'nullable|string|min:8|max:100',
            'school_type'    => ['nullable', Rule::in(array_keys(SchoolTypes::all()))],
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $school = School::create($data);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School created');

        $notes = [];
        if (! empty($extra['school_type'])) {
            SchoolTypes::apply($school->id, $extra['school_type'], array_keys(SchoolTypes::PARTS));
            $notes[] = SchoolTypes::all()[$extra['school_type']]['label'].' setup added';
        }

        $flash = [];
        if (! empty($extra['admin_email'])) {
            $admin = $accounts->createLogin($school->id, $extra['admin_name'], $extra['admin_email'], null, 'school-admin', $extra['admin_password'] ?? null);
            activity('users')->causedBy($request->user())->performedOn($admin)
                ->withProperties(['school_id' => $school->id, 'role' => 'school-admin'])->log('School administrator added');
            $flash = [['name' => $admin->name, 'email' => $admin->email, 'password' => $admin->plain_password]];
            $notes[] = 'first administrator made';
        }

        $redirect = redirect()
            ->route('super-admin.schools.show', $school)
            ->with('success', "School \"{$school->name}\" created".($notes ? ': '.implode(', ', $notes) : '').'.');

        return $flash ? $redirect->with('login_details', $flash) : $redirect;
    }

    public function show(School $school): Response
    {
        $school->load(['academicYears' => fn ($q) => $q->latest()]);
        $id = $school->id;

        $subscription = SchoolSubscription::with('package')->where('school_id', $id)->latest('id')->first();
        $overrides = SchoolModule::where('school_id', $id)->pluck('is_enabled', 'module_slug')->all();
        $aiSpent = (float) AiUsage::where('school_id', $id)->where('created_at', '>=', now()->startOfMonth())->sum('cost_naira');

        return Inertia::render('SuperAdmin/Schools/Show', [
            'school' => $school,
            'usage'  => [
                'students'    => Student::withoutGlobalScopes()->where('school_id', $id)->where('status', 'active')->count(),
                'staff'       => Staff::withoutGlobalScopes()->where('school_id', $id)->count(),
                'users'       => User::where('school_id', $id)->count(),
                'outstanding' => (float) Invoice::withoutGlobalScopes()->where('school_id', $id)->where('status', '!=', 'void')->sum('balance'),
                'last_login'  => User::where('school_id', $id)->max('last_login_at'),
            ],
            'subscription' => $subscription ? [
                'id'           => $subscription->id,
                'package'      => $subscription->package?->name,
                'status'       => $subscription->status,
                'start'        => $subscription->start_date?->toDateString(),
                'end'          => $subscription->end_date?->toDateString(),
                'is_trial'     => $subscription->is_trial,
                'max_students' => $subscription->package?->max_students,
                'max_staff'    => $subscription->package?->max_staff,
            ] : null,
            'packages' => Package::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price_monthly', 'price_yearly']),
            'modules'  => collect(ModuleManagerController::ALL_MODULES)->map(fn ($slug) => [
                'slug'       => $slug,
                'label'      => ucwords(str_replace('_', ' ', $slug)),
                'is_enabled' => $overrides[$slug] ?? true,
            ])->values(),
            'admins' => User::where('school_id', $id)
                ->whereHas('roles', fn ($r) => $r->where('name', 'school-admin'))
                ->orderBy('name')->get(['id', 'name', 'email', 'phone', 'status', 'last_login_at']),
            'ai' => [
                'blocked' => SchoolSetting::get($id, 'ai_blocked') === '1',
                'limit'   => SchoolSetting::get($id, 'ai_limit'),
                'spent'   => round($aiSpent, 2),
            ],
            'activity' => Activity::with('causer:id,name')
                ->where('properties->school_id', $id)->latest()->limit(10)->get()
                ->map(fn ($a) => ['id' => $a->id, 'text' => $a->description, 'by' => $a->causer?->name, 'at' => $a->created_at?->toIso8601String()]),
        ]);
    }

    /** Give a school a new administrator (or a first one for a school with none) */
    public function addAdmin(Request $request, School $school, StaffAccountService $accounts): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $user = $accounts->createLogin($school->id, $data['name'], $data['email'], $data['phone'] ?? null, 'school-admin', $data['password']);

        activity('users')->causedBy($request->user())->performedOn($user)
            ->withProperties(['school_id' => $school->id, 'role' => 'school-admin'])
            ->log('School administrator added');

        return back()->with('success', "{$user->name} is now an administrator of {$school->name}.");
    }

    public function edit(School $school): Response
    {
        return Inertia::render('SuperAdmin/Schools/Edit', [
            'school' => $school,
        ]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School updated');

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', "School \"{$school->name}\" updated.");
    }

    public function suspend(Request $request, School $school): RedirectResponse
    {
        $school->update(['status' => 'suspended']);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School suspended');

        return back()->with('success', "School \"{$school->name}\" suspended.");
    }

    public function activate(Request $request, School $school): RedirectResponse
    {
        $school->update(['status' => 'active']);

        activity()
            ->causedBy($request->user())
            ->performedOn($school)
            ->log('School activated');

        return back()->with('success', "School \"{$school->name}\" activated.");
    }

    public function destroy(School $school): RedirectResponse
    {
        $school->delete();

        return redirect()
            ->route('super-admin.schools.index')
            ->with('success', "School deleted.");
    }
}
