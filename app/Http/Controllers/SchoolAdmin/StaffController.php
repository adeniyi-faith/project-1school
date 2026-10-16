<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Models\User;
use App\Services\StaffAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = Staff::with(['department:id,name', 'designation:id,name'])
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('emp_id',     'like', "%{$request->search}%")
                  ->orWhere('email',      'like', "%{$request->search}%");
            }))
            ->when($request->department_id,  fn ($q) => $q->where('department_id',  $request->department_id))
            ->when($request->designation_id, fn ($q) => $q->where('designation_id', $request->designation_id))
            ->when($request->status,         fn ($q) => $q->where('status',         $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Staff/Index', [
            'staff' => [
                'data'  => $staff->items(),
                'meta'  => [
                    'total'        => $staff->total(),
                    'per_page'     => $staff->perPage(),
                    'current_page' => $staff->currentPage(),
                    'last_page'    => $staff->lastPage(),
                    'from'         => $staff->firstItem(),
                    'to'           => $staff->lastItem(),
                ],
                'links' => [
                    'prev' => $staff->previousPageUrl(),
                    'next' => $staff->nextPageUrl(),
                ],
            ],
            'filters'      => $request->only('search', 'department_id', 'designation_id', 'status'),
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'department_id', 'name']),
            'stats'        => [
                'total'      => Staff::count(),
                'active'     => Staff::where('status', 'active')->count(),
                'on_leave'   => Staff::where('status', 'on_leave')->count(),
                'resigned'   => Staff::where('status', 'resigned')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SchoolAdmin/Staff/Create', [
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'department_id', 'name']),
            'loginRoles'   => StaffAccountService::STAFF_ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'nullable|string|max:100',
            'gender'         => 'required|in:male,female,other',
            'date_of_birth'  => 'nullable|date|before:tomorrow',
            'blood_group'    => 'nullable|string|max:5',
            'religion'       => 'nullable|string|max:50',
            'nationality'    => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|required_if:create_login,true|email|max:150',
            'address'        => 'nullable|string|max:500',
            'department_id'  => ['nullable', Rule::exists('departments', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'joining_date'   => 'nullable|date',
            'salary_type'    => 'required|in:fixed,hourly',
            'salary'         => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,resigned,terminated,on_leave',
            'notes'          => 'nullable|string|max:1000',
            // Optional: make their login in the same step
            'create_login'   => 'nullable|boolean',
            'login_role'     => ['nullable', 'required_if:create_login,true', Rule::in(StaffAccountService::STAFF_ROLES)],
            'login_password' => 'nullable|string|min:8|max:100',
        ], ['email.required_if' => 'A login needs an email address: it is what they sign in with.', 'login_role.required_if' => 'Choose what they can do (their role).']);

        $wantsLogin = (bool) ($data['create_login'] ?? false);
        if ($wantsLogin) {
            $this->assertLoginEmailFree($data['email'] ?? null);
        }
        unset($data['create_login'], $data['login_role'], $data['login_password']);

        $staff = Staff::create($data);

        if ($wantsLogin) {
            $user = app(StaffAccountService::class)->giveLogin($staff, (string) $request->input('login_role'), null, $request->input('login_password'));
            activity('users')->causedBy($request->user())->performedOn($user)
                ->withProperties(['school_id' => $user->school_id, 'role' => $request->input('login_role')])->log('Role assigned');

            return redirect()->route('school.staff.show', $staff)->with('success', 'Staff registered with a login.')
                ->with('login_details', [['name' => $staff->full_name, 'email' => $user->email, 'password' => $user->plain_password]]);
        }

        return redirect()->route('school.staff.index')->with('success', 'Staff registered successfully.');
    }

    public function show(Staff $staff): Response
    {
        $staff->load(['department', 'designation', 'documents']);
        $login = $staff->user_id ? User::with('roles:id,name')->find($staff->user_id) : null;

        return Inertia::render('SchoolAdmin/Staff/Show', [
            'staff' => $staff,
            'login' => $login ? ['id' => $login->id, 'email' => $login->email, 'status' => $login->status, 'role' => $login->roles->pluck('name')->first()] : null,
            'loginRoles' => StaffAccountService::STAFF_ROLES,
        ]);
    }

    /** Show or hide this person on the school website's "Our team" section */
    public function toggleWebsite(Request $request, Staff $staff): RedirectResponse
    {
        $staff->forceFill(['show_on_website' => $request->boolean('show')])->save();

        return back()->with('success', $staff->show_on_website
            ? "{$staff->full_name} now shows in the Our team section of your website."
            : "{$staff->full_name} is no longer shown on your website.");
    }

    public function edit(Staff $staff): Response
    {
        return Inertia::render('SchoolAdmin/Staff/Edit', [
            'staff'        => $staff,
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'designations' => Designation::orderBy('name')->get(['id', 'department_id', 'name']),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'nullable|string|max:100',
            'gender'         => 'required|in:male,female,other',
            'date_of_birth'  => 'nullable|date|before:tomorrow',
            'blood_group'    => 'nullable|string|max:5',
            'religion'       => 'nullable|string|max:50',
            'nationality'    => 'nullable|string|max:50',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:150',
            'address'        => 'nullable|string|max:500',
            'department_id'  => ['nullable', Rule::exists('departments', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'designation_id' => ['nullable', Rule::exists('designations', 'id')->where('school_id', $this->getSchoolId())->whereNull('deleted_at')],
            'joining_date'   => 'nullable|date',
            'salary_type'    => 'required|in:fixed,hourly',
            'salary'         => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,resigned,terminated,on_leave',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $staff->update($data);

        return redirect()->route('school.staff.show', $staff)->with('success', 'Staff updated.');
    }

    /** Give a staff member who has no login one, and show the details once */
    public function giveLogin(Request $request, Staff $staff): RedirectResponse
    {
        if ($staff->user_id && User::whereKey($staff->user_id)->exists()) {
            return back()->with('error', 'This person already has a login.');
        }

        $data = $request->validate([
            'role'     => ['required', Rule::in(StaffAccountService::STAFF_ROLES)],
            'email'    => 'nullable|email|max:150',
            'password' => 'nullable|string|min:8|max:100',
        ], ['role.required' => 'Choose what they can do (their role).']);

        $email = $data['email'] ?? $staff->email;
        if (blank($email)) {
            throw ValidationException::withMessages(['email' => 'A login needs an email address: it is what they sign in with.']);
        }
        $this->assertLoginEmailFree($email);

        $user = app(StaffAccountService::class)->giveLogin($staff, $data['role'], $email, $data['password'] ?? null);
        activity('users')->causedBy($request->user())->performedOn($user)
            ->withProperties(['school_id' => $user->school_id, 'role' => $data['role']])->log('Role assigned');

        return back()->with('success', 'Login created.')
            ->with('login_details', [['name' => $staff->full_name, 'email' => $user->email, 'password' => $user->plain_password]]);
    }

    /** New password for a staff member's login, shown once */
    public function resetLogin(Request $request, Staff $staff): RedirectResponse
    {
        $user = $staff->user_id ? User::find($staff->user_id) : null;
        if (! $user) {
            return back()->with('error', 'This person has no login yet.');
        }

        $password = app(StaffAccountService::class)->resetPassword($user);
        activity('users')->causedBy($request->user())->performedOn($user)->withProperties(['school_id' => $user->school_id])->log('Password reset');

        return back()->with('success', 'New password made.')
            ->with('login_details', [['name' => $staff->full_name, 'email' => $user->email, 'password' => $password]]);
    }

    private function assertLoginEmailFree(?string $email): void
    {
        if (blank($email) || User::withTrashed()->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->exists()) {
            throw ValidationException::withMessages(['email' => blank($email)
                ? 'A login needs an email address: it is what they sign in with.'
                : 'Someone already has a login with this email.']);
        }
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->delete();

        return redirect()->route('school.staff.index')->with('success', 'Staff removed.');
    }

    public function uploadDocument(Request $request, Staff $staff): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'file'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('file')->store("staff/{$staff->id}/documents", 'private');

        StaffDocument::create([
            'school_id' => $staff->school_id,
            'staff_id'  => $staff->id,
            'title'     => $request->title,
            'file_path' => $path,
            'file_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function deleteDocument(StaffDocument $document): RedirectResponse
    {
        // Never let one school delete another school's document
        abort_unless(
            auth()->user()->hasRole('super-admin') || $document->school_id === auth()->user()->school_id,
            403
        );

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted.');
    }

    public function downloadDocument(StaffDocument $document)
    {
        abort_unless(
            auth()->user()->hasRole('super-admin') || $document->school_id === auth()->user()->school_id,
            403
        );
        abort_unless(Storage::disk('private')->exists($document->file_path), 404);

        $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Storage::disk('private')->download($document->file_path, str($document->title)->slug()->append('.'.$extension)->toString());
    }
}
