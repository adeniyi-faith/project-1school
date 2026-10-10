<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    const AVAILABLE_MODULES = [
        'students', 'staff', 'attendance', 'timetable', 'exams',
        'fees', 'library', 'transport', 'hostel', 'inventory',
        'homework', 'communication', 'reports', 'hr',
    ];

    public function index(): Response
    {
        $packages = Package::withTrashed(false)
            ->withCount('subscriptions')
            ->with('modules')
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/Packages/Index', [
            'packages'         => $packages,
            'availableModules' => self::AVAILABLE_MODULES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'max_students'  => 'required|integer|min:0',
            'max_staff'     => 'required|integer|min:0',
            'storage_gb'    => 'required|integer|min:1',
            'is_active'     => 'boolean',
            'modules'       => 'array',
            'modules.*'     => 'string|in:' . implode(',', self::AVAILABLE_MODULES),
        ]);

        $package = Package::create([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'description'   => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_yearly'  => $data['price_yearly'],
            'max_students'  => $data['max_students'],
            'max_staff'     => $data['max_staff'],
            'storage_gb'    => $data['storage_gb'],
            'is_active'     => $data['is_active'] ?? true,
        ]);

        foreach ($data['modules'] ?? [] as $slug) {
            PackageModule::create(['package_id' => $package->id, 'module_slug' => $slug]);
        }

        return back()->with('success', 'Package created.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string|max:500',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly'  => 'required|numeric|min:0',
            'max_students'  => 'required|integer|min:0',
            'max_staff'     => 'required|integer|min:0',
            'storage_gb'    => 'required|integer|min:1',
            'is_active'     => 'boolean',
            'modules'       => 'array',
            'modules.*'     => 'string|in:' . implode(',', self::AVAILABLE_MODULES),
        ]);

        $package->update([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_yearly'  => $data['price_yearly'],
            'max_students'  => $data['max_students'],
            'max_staff'     => $data['max_staff'],
            'storage_gb'    => $data['storage_gb'],
            'is_active'     => $data['is_active'] ?? true,
        ]);

        $package->modules()->delete();
        foreach ($data['modules'] ?? [] as $slug) {
            PackageModule::create(['package_id' => $package->id, 'module_slug' => $slug]);
        }

        return back()->with('success', 'Package updated.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        if ($package->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete a package with active subscriptions.');
        }

        $package->delete();
        return back()->with('success', 'Package deleted.');
    }
}
