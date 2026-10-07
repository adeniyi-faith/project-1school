<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryIssue;
use App\Models\InventoryPurchase;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    // ── Categories ────────────────────────────────────────────────

    public function categories()
    {
        $sid = $this->getSchoolId();

        return Inertia::render('SchoolAdmin/Inventory/Categories', [
            'categories' => InventoryCategory::where('school_id', $sid)
                ->withCount('items')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        InventoryCategory::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, InventoryCategory $inventoryCategory)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $inventoryCategory->update($data);
        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(InventoryCategory $inventoryCategory)
    {
        $inventoryCategory->delete();
        return back()->with('success', 'Category deleted.');
    }

    // ── Items ─────────────────────────────────────────────────────

    public function items(Request $request)
    {
        $sid = $this->getSchoolId();

        $items = InventoryItem::with('category:id,name')
            ->where('school_id', $sid)
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->low_stock === 'yes', fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total_items'     => InventoryItem::where('school_id', $sid)->count(),
            'low_stock_count' => InventoryItem::where('school_id', $sid)
                ->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            'total_value'     => 0, // calculated from purchases
        ];

        return Inertia::render('SchoolAdmin/Inventory/Items', [
            'items'      => $items,
            'categories' => InventoryCategory::where('school_id', $sid)->orderBy('name')->get(['id', 'name']),
            'filters'    => $request->only('category_id', 'search', 'low_stock'),
            'stats'      => $stats,
        ]);
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:inventory_categories,id',
            'name'          => 'required|string|max:200',
            'unit'          => 'required|string|max:30',
            'minimum_stock' => 'required|numeric|min:0',
            'description'   => 'nullable|string',
        ]);

        InventoryItem::create(array_merge($data, ['school_id' => $this->getSchoolId()]));

        return back()->with('success', 'Item added.');
    }

    public function updateItem(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'category_id'   => 'required|exists:inventory_categories,id',
            'name'          => 'required|string|max:200',
            'unit'          => 'required|string|max:30',
            'minimum_stock' => 'required|numeric|min:0',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $inventoryItem->update($data);
        return back()->with('success', 'Item updated.');
    }

    public function destroyItem(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return back()->with('success', 'Item deleted.');
    }

    // ── Purchases ─────────────────────────────────────────────────

    public function purchases(Request $request)
    {
        $sid = $this->getSchoolId();

        $purchases = InventoryPurchase::with('item:id,name,unit')
            ->where('school_id', $sid)
            ->when($request->item_id, fn ($q) => $q->where('item_id', $request->item_id))
            ->latest('purchase_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Inventory/Purchases', [
            'purchases' => $purchases,
            'items'     => InventoryItem::where('school_id', $sid)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name', 'unit']),
            'filters'   => $request->only('item_id'),
        ]);
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'item_id'       => 'required|exists:inventory_items,id',
            'vendor'        => 'nullable|string|max:200',
            'purchase_date' => 'required|date',
            'quantity'      => 'required|numeric|min:0.01',
            'unit_price'    => 'required|numeric|min:0',
            'invoice_no'    => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        $data['total_price'] = round((float)$data['quantity'] * (float)$data['unit_price'], 2);
        $data['school_id']   = $this->getSchoolId();

        DB::transaction(function () use ($data) {
            InventoryPurchase::create($data);
            InventoryItem::where('id', $data['item_id'])
                ->increment('current_stock', (float)$data['quantity']);
        });

        return back()->with('success', 'Purchase recorded and stock updated.');
    }

    // ── Issues ────────────────────────────────────────────────────

    public function issues(Request $request)
    {
        $sid = $this->getSchoolId();

        $issues = InventoryIssue::with('item:id,name,unit')
            ->where('school_id', $sid)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('issue_date')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SchoolAdmin/Inventory/Issues', [
            'issues'    => $issues,
            'items'     => InventoryItem::where('school_id', $sid)->where('is_active', true)
                ->where('current_stock', '>', 0)->orderBy('name')->get(['id', 'name', 'unit', 'current_stock']),
            'staffList' => Staff::where('school_id', $sid)->where('status', 'active')
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'emp_id']),
            'filters'   => $request->only('status'),
        ]);
    }

    public function storeIssue(Request $request)
    {
        $data = $request->validate([
            'item_id'        => 'required|exists:inventory_items,id',
            'issued_to_type' => 'required|in:staff,department',
            'issued_to_id'   => 'required|integer',
            'issued_to_name' => 'required|string|max:200',
            'quantity'       => 'required|numeric|min:0.01',
            'issue_date'     => 'required|date',
            'purpose'        => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($data['item_id']);

        if ((float)$item->current_stock < (float)$data['quantity']) {
            return back()->withErrors(['quantity' => 'Insufficient stock. Available: ' . $item->current_stock . ' ' . $item->unit]);
        }

        $data['school_id'] = $this->getSchoolId();
        $data['status']    = 'issued';

        DB::transaction(function () use ($data, $item) {
            InventoryIssue::create($data);
            $item->decrement('current_stock', (float)$data['quantity']);
        });

        return back()->with('success', 'Item issued successfully.');
    }

    public function returnIssue(Request $request, InventoryIssue $inventoryIssue)
    {
        $data = $request->validate([
            'return_date'      => 'required|date',
            'returned_quantity'=> 'required|numeric|min:0.01',
        ]);

        $returnedQty = (float)$data['returned_quantity'];
        $issuedQty   = (float)$inventoryIssue->quantity;

        DB::transaction(function () use ($inventoryIssue, $data, $returnedQty, $issuedQty) {
            $newStatus = $returnedQty >= $issuedQty ? 'returned' : 'partial';
            $inventoryIssue->update([
                'return_date' => $data['return_date'],
                'status'      => $newStatus,
            ]);
            $inventoryIssue->item->increment('current_stock', min($returnedQty, $issuedQty));
        });

        return back()->with('success', 'Item return recorded.');
    }
}
