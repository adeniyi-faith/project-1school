<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $sid = $this->getSchoolId();

        $assets = Asset::withCount('maintenanceLogs')
            ->where('school_id', $sid)
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->search,   fn ($q) => $q->where(fn ($q2) =>
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('asset_code', 'like', "%{$request->search}%")
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $categories = Asset::where('school_id', $sid)
            ->whereNotNull('category')
            ->distinct()->pluck('category')->sort()->values();

        $stats = [
            'total'       => Asset::where('school_id', $sid)->count(),
            'active'      => Asset::where('school_id', $sid)->where('status', 'active')->count(),
            'maintenance' => Asset::where('school_id', $sid)->where('status', 'maintenance')->count(),
            'disposed'    => Asset::where('school_id', $sid)->where('status', 'disposed')->count(),
            'total_value' => (float) Asset::where('school_id', $sid)->where('status', 'active')->sum('current_value'),
        ];

        return Inertia::render('SchoolAdmin/Inventory/Assets', [
            'assets'     => $assets,
            'categories' => $categories,
            'filters'    => $request->only('status', 'category', 'search'),
            'stats'      => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:200',
            'category'            => 'nullable|string|max:100',
            'purchase_date'       => 'nullable|date',
            'purchase_price'      => 'required|numeric|min:0',
            'depreciation_method' => 'required|in:straight_line,reducing_balance',
            'depreciation_rate'   => 'required|numeric|min:0|max:100',
            'location'            => 'nullable|string|max:200',
            'assigned_to'         => 'nullable|string|max:200',
            'description'         => 'nullable|string',
        ]);

        $data['school_id']    = $this->getSchoolId();
        $data['current_value']= $data['purchase_price'];

        Asset::create($data);

        return back()->with('success', 'Asset registered.');
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:200',
            'category'            => 'nullable|string|max:100',
            'purchase_date'       => 'nullable|date',
            'purchase_price'      => 'required|numeric|min:0',
            'current_value'       => 'required|numeric|min:0',
            'depreciation_method' => 'required|in:straight_line,reducing_balance',
            'depreciation_rate'   => 'required|numeric|min:0|max:100',
            'location'            => 'nullable|string|max:200',
            'assigned_to'         => 'nullable|string|max:200',
            'status'              => 'required|in:active,disposed,maintenance',
            'description'         => 'nullable|string',
        ]);

        $asset->update($data);

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return back()->with('success', 'Asset removed.');
    }

    // ── Maintenance Logs ──────────────────────────────────────────

    public function storeMaintenance(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'date'                 => 'required|date',
            'description'          => 'required|string',
            'cost'                 => 'nullable|numeric|min:0',
            'vendor'               => 'nullable|string|max:200',
            'next_maintenance_date'=> 'nullable|date',
        ]);

        $data['asset_id']  = $asset->id;
        $data['school_id'] = $this->getSchoolId();
        $data['cost']      = $data['cost'] ?? 0;

        AssetMaintenanceLog::create($data);

        // Set asset to maintenance status if needed
        if ($asset->status === 'active') {
            $asset->update(['status' => 'maintenance']);
        }

        return back()->with('success', 'Maintenance log added.');
    }

    public function show(Asset $asset)
    {
        $asset->load('maintenanceLogs');

        return Inertia::render('SchoolAdmin/Inventory/AssetDetail', [
            'asset' => $asset,
        ]);
    }
}
