<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $me = $request->user();
        $ids = PlatformAccess::schoolIds($me);

        $users = User::with('roles')
            // Platform accounts are looked after on the Platform Staff page; staff limited to some schools see only those schools' logins
            ->when(! PlatformAccess::isOwner($me), fn ($q) => $q->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', array_merge([PlatformAccess::OWNER], PlatformAccess::roleNames()))))
            ->when($ids !== null, fn ($q) => $q->whereIn('school_id', $ids))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->when($request->school_id, fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->withTrashed(false)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $schools = PlatformAccess::schools($me)->select('schools.id', 'schools.name')->orderBy('schools.name')->get();
        $roles   = $this->assignableRoles($me);

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => [
                'data' => $users->items(),
                'meta' => [
                    'total'        => $users->total(),
                    'per_page'     => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                ],
            ],
            'schools' => $schools,
            'roles'   => $roles,
            'can'     => ['manage' => PlatformAccess::can($me, 'platform.users.manage'), 'impersonate' => PlatformAccess::can($me, 'platform.users.impersonate')],
            'filters' => $request->only(['search', 'role', 'school_id', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:20',
            'password'  => 'required|string|min:8',
            'role'      => ['required', 'string', Rule::in($this->assignableRoles($request->user()))],
            'school_id' => 'nullable|integer|exists:schools,id',
            'status'    => 'required|in:active,inactive,suspended',
        ]);
        $this->checkSchoolAllowed($request->user(), $data['school_id'] ?? null);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => Hash::make($data['password']),
            'school_id' => $data['school_id'] ?? null,
            'status'    => $data['status'],
        ]);

        $user->assignRole($data['role']);

        activity('users')->causedBy(auth()->user())->performedOn($user)
            ->withProperties(['school_id' => $user->school_id, 'role' => $data['role']])
            ->log('Role assigned');

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:8',
            'role'      => ['required', 'string', Rule::in($this->assignableRoles($request->user()))],
            'school_id' => 'nullable|integer|exists:schools,id',
            'status'    => 'required|in:active,inactive,suspended',
        ]);
        $this->guardTarget($request->user(), $user);
        $this->checkSchoolAllowed($request->user(), $data['school_id'] ?? null);

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'school_id' => $data['school_id'] ?? null,
            'status'    => $data['status'],
        ]);

        if (!blank($data['password'] ?? null)) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $oldRole = $user->roles()->pluck('name')->first();
        $user->syncRoles([$data['role']]);

        if ($oldRole !== $data['role']) {
            activity('users')->causedBy(auth()->user())->performedOn($user)
                ->withProperties(['school_id' => $user->school_id, 'old_role' => $oldRole, 'role' => $data['role']])
                ->log('Role changed');
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardTarget($request->user(), $user);
        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Cannot delete a super admin.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->guardTarget($request->user(), $user);
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot switch off your own account.');
        }
        $user->update(['status' => 'suspended']);
        return back()->with('success', 'User suspended.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->guardTarget($request->user(), $user);
        $user->update(['status' => 'active']);
        return back()->with('success', 'User activated.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => 'required|string|min:8']);
        $this->guardTarget($request->user(), $user);
        $user->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Password reset successfully.');
    }

    /** Roles that can be given here: school roles only. Platform roles are given on the Platform Staff page; the owner role by the owner. */
    private function assignableRoles(User $me): array
    {
        return Role::orderBy('name')->pluck('name')
            ->reject(fn ($r) => PlatformAccess::isPlatformRoleName($r) && ! ($r === PlatformAccess::OWNER && PlatformAccess::isOwner($me)))
            ->values()->all();
    }

    /** Platform accounts (the owner and the team) can be changed only by the owner; staff may only touch logins of their own schools */
    private function guardTarget(User $me, User $target): void
    {
        abort_if(PlatformAccess::isPlatform($target) && ! PlatformAccess::isOwner($me), 403);
        abort_if(! PlatformAccess::isOwner($me) && ! $target->school_id, 403);
        if ($target->school_id) {
            abort_unless(PlatformAccess::canSeeSchool($me, (int) $target->school_id), 403);
        }
    }

    /** A person limited to some schools may only make logins in those schools, and must name one */
    private function checkSchoolAllowed(User $me, ?int $schoolId): void
    {
        // Only the owner may make a login that belongs to no school
        if (! PlatformAccess::isOwner($me) && ! $schoolId) {
            throw \Illuminate\Validation\ValidationException::withMessages(['school_id' => 'Choose the school this login belongs to.']);
        }
        if (PlatformAccess::schoolIds($me) !== null) {
            abort_unless($schoolId && PlatformAccess::canSeeSchool($me, $schoolId), 403);
        }
    }
}
