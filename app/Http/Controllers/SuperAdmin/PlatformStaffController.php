<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformStaff;
use App\Models\School;
use App\Models\User;
use App\Services\StaffAccountService;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * The platform's own team: support, finance, sales, developer and content people. Only the owner reaches this page.
 * Each person has one role, can be given extra permissions one by one, and can be limited to chosen schools.
 */
class PlatformStaffController extends Controller
{
    public function index(): Response
    {
        $staff = User::with(['roles:id,name', 'permissions:id,name', 'platformStaff'])
            ->whereHas('roles', fn ($r) => $r->whereIn('name', PlatformAccess::roleNames()))
            ->orderBy('name')->get();

        $assigned = DB::table('platform_staff_schools')->whereIn('user_id', $staff->pluck('id'))->get()->groupBy('user_id');
        $schoolNames = School::whereIn('id', $assigned->flatten(1)->pluck('school_id')->unique())->pluck('name', 'id');

        $rows = $staff->map(function (User $u) use ($assigned, $schoolNames) {
            $role = $u->roles->pluck('name')->first(fn ($r) => isset(PlatformAccess::ROLES[$r]));

            return [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'status' => $u->status,
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'role' => $role, 'role_label' => PlatformAccess::roleLabel($role),
                'extras' => $u->permissions->pluck('name')->values()->all(),
                'scope' => $u->platformStaff?->school_scope ?? 'all',
                'schools' => collect($assigned[$u->id] ?? [])->map(fn ($r) => ['id' => (int) $r->school_id, 'name' => $schoolNames[$r->school_id] ?? '—'])->values()->all(),
                'note' => $u->platformStaff?->note,
            ];
        })->values();

        return Inertia::render('SuperAdmin/Staff/Index', [
            'staff' => $rows,
            'roles' => collect(PlatformAccess::ROLES)->map(fn ($d, $key) => [
                'key' => $key, 'label' => $d['label'], 'about' => $d['about'], 'permissions' => $d['permissions'],
            ])->values(),
            'permissions' => collect(PlatformAccess::PERMISSIONS)
                ->reject(fn ($d, $key) => $key === 'platform.staff.manage')
                ->map(fn ($d, $key) => ['key' => $key, 'group' => $d[0], 'label' => $d[1]])->values(),
            'schools' => School::orderBy('name')->limit(1000)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, StaffAccountService $accounts): RedirectResponse
    {
        $data = $this->validated($request);
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:8|max:100',
        ]);

        $user = $accounts->createLogin(null, $data['name'], $request->input('email'), $data['phone'] ?? null, $data['role'], $request->input('password') ?: null);
        $this->saveDetails($user, $data, $request);
        // A password the owner chose or made up is only for the first sign-in
        \App\Support\PlatformSecurityRules::forcePasswordChange($user);

        activity('platform')->causedBy($request->user())->performedOn($user)
            ->withProperties(['role' => $data['role'], 'extras' => $data['extras'] ?? [], 'scope' => $data['school_scope'], 'school_ids' => $data['school_ids'] ?? []])
            ->log('Platform staff member added');

        return back()->with('success', "{$user->name} now works on the platform as ".PlatformAccess::roleLabel($data['role']).'.')
            ->with('login_details', [['name' => $user->name, 'email' => $user->email, 'password' => $user->plain_password]]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        $data = $this->validated($request);
        $request->validate(['email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)]]);

        $before = ['role' => $user->roles->pluck('name')->first(fn ($r) => isset(PlatformAccess::ROLES[$r])), 'extras' => $user->permissions->pluck('name')->all()];

        $user->update(['name' => $data['name'], 'email' => mb_strtolower(trim($request->input('email'))), 'phone' => $data['phone'] ?? null]);
        $this->saveDetails($user, $data, $request);

        activity('platform')->causedBy($request->user())->performedOn($user)
            ->withProperties(['before' => $before, 'role' => $data['role'], 'extras' => $data['extras'] ?? [], 'scope' => $data['school_scope'], 'school_ids' => $data['school_ids'] ?? []])
            ->log('Platform staff member changed');

        return back()->with('success', "{$user->name} updated.");
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        $user->update(['status' => 'suspended']);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff member switched off');

        return back()->with('success', "{$user->name} is switched off and can no longer sign in.");
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);
        $user->update(['status' => 'active']);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff member switched on');

        return back()->with('success', "{$user->name} is switched on again.");
    }

    public function resetPassword(Request $request, User $user, StaffAccountService $accounts): RedirectResponse
    {
        $this->guard($user);
        $password = $accounts->resetPassword($user, $request->validate(['password' => 'nullable|string|min:8|max:100'])['password'] ?? null);
        \App\Support\PlatformSecurityRules::forcePasswordChange($user);
        \App\Support\PlatformSecurityRules::signOutEverywhere($user);
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff password reset');

        return back()->with('success', "New password made for {$user->name}.")
            ->with('login_details', [['name' => $user->name, 'email' => $user->email, 'password' => $password]]);
    }

    /** Takes the person off the team. Their login stays (switched off) so past records still show who did what. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guard($user);

        DB::transaction(function () use ($user) {
            $user->syncRoles([]);
            $user->syncPermissions([]);
            DB::table('platform_staff_schools')->where('user_id', $user->id)->delete();
            PlatformStaff::where('user_id', $user->id)->delete();
            $user->update(['status' => 'inactive']);
        });
        activity('platform')->causedBy($request->user())->performedOn($user)->log('Platform staff member removed from the team');

        return back()->with('success', "{$user->name} is no longer on the platform team.");
    }

    /** Only members of the team (not the owner, not school logins) can be changed here */
    private function guard(User $user): void
    {
        abort_unless(PlatformAccess::isStaff($user), 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(PlatformAccess::roleNames())],
            'extras' => 'nullable|array',
            // `platform.staff.manage` is never handed out: only the owner decides who is on the team
            'extras.*' => ['string', Rule::in(array_diff(array_keys(PlatformAccess::PERMISSIONS), ['platform.staff.manage']))],
            'school_scope' => ['required', Rule::in(['all', 'assigned'])],
            'school_ids' => 'nullable|array',
            'school_ids.*' => 'integer|exists:schools,id',
            'note' => 'nullable|string|max:255',
        ]);

        if ($data['school_scope'] === 'assigned' && empty($data['school_ids'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['school_ids' => 'Choose at least one school, or let this person see all schools.']);
        }

        return $data;
    }

    private function saveDetails(User $user, array $data, Request $request): void
    {
        DB::transaction(function () use ($user, $data, $request) {
            $user->syncRoles([Role::findOrCreate($data['role'], 'web')]);
            // Extras are the permissions beyond the role; the role's own are not repeated
            $roleHas = PlatformAccess::ROLES[$data['role']]['permissions'];
            $user->syncPermissions(array_values(array_diff($data['extras'] ?? [], $roleHas)));

            PlatformStaff::updateOrCreate(['user_id' => $user->id], [
                'school_scope' => $data['school_scope'], 'note' => $data['note'] ?? null, 'created_by' => $request->user()->id,
            ]);

            DB::table('platform_staff_schools')->where('user_id', $user->id)->delete();
            if ($data['school_scope'] === 'assigned') {
                DB::table('platform_staff_schools')->insert(collect($data['school_ids'])->unique()->map(fn ($id) => [
                    'user_id' => $user->id, 'school_id' => $id, 'created_at' => now(), 'updated_at' => now(),
                ])->all());
            }
        });
    }
}
