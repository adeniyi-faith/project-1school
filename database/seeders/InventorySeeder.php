<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\School;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) return;

        $sid = $school->id;

        // Categories
        $cats = [
            ['name' => 'Stationery',  'description' => 'Pens, paper, notebooks'],
            ['name' => 'Cleaning',    'description' => 'Cleaning supplies'],
            ['name' => 'Electronics', 'description' => 'Batteries, cables, accessories'],
            ['name' => 'Sports',      'description' => 'Sports equipment'],
        ];

        foreach ($cats as $cat) {
            $category = InventoryCategory::firstOrCreate(
                ['school_id' => $sid, 'name' => $cat['name']],
                ['description' => $cat['description']]
            );

            // Items per category
            $itemsData = match ($cat['name']) {
                'Stationery'  => [
                    ['name' => 'A4 Paper',        'unit' => 'ream',  'minimum_stock' => 10],
                    ['name' => 'Ball Pen (Blue)',  'unit' => 'box',   'minimum_stock' => 5],
                    ['name' => 'Marker Pen',       'unit' => 'box',   'minimum_stock' => 3],
                    ['name' => 'Notebook (200pg)', 'unit' => 'pcs',   'minimum_stock' => 20],
                ],
                'Cleaning'    => [
                    ['name' => 'Liquid Soap',      'unit' => 'litre', 'minimum_stock' => 5],
                    ['name' => 'Floor Cleaner',    'unit' => 'litre', 'minimum_stock' => 5],
                    ['name' => 'Dustbin Bags',     'unit' => 'pack',  'minimum_stock' => 10],
                ],
                'Electronics' => [
                    ['name' => 'AA Battery',       'unit' => 'pack',  'minimum_stock' => 5],
                    ['name' => 'USB Cable',        'unit' => 'pcs',   'minimum_stock' => 3],
                ],
                'Sports'      => [
                    ['name' => 'Football',         'unit' => 'pcs',   'minimum_stock' => 2],
                    ['name' => 'Badminton Shuttle','unit' => 'box',   'minimum_stock' => 3],
                ],
                default => [],
            };

            foreach ($itemsData as $itemData) {
                $item = InventoryItem::firstOrCreate(
                    ['school_id' => $sid, 'category_id' => $category->id, 'name' => $itemData['name']],
                    ['unit' => $itemData['unit'], 'minimum_stock' => $itemData['minimum_stock']]
                );

                // Purchase to set initial stock
                if ($item->current_stock == 0) {
                    $qty   = rand(20, 100);
                    $price = rand(50, 500);
                    InventoryPurchase::create([
                        'school_id'     => $sid,
                        'item_id'       => $item->id,
                        'vendor'        => 'Local Supplier',
                        'purchase_date' => now()->subDays(rand(10, 60))->toDateString(),
                        'quantity'      => $qty,
                        'unit_price'    => $price,
                        'total_price'   => $qty * $price,
                        'invoice_no'    => 'INV-' . strtoupper(substr(md5($item->name), 0, 6)),
                    ]);
                    $item->increment('current_stock', $qty);
                }
            }
        }

        // Assets
        $assetsData = [
            ['name' => 'Projector — Auditorium',    'category' => 'Electronics', 'purchase_price' => 45000, 'location' => 'Auditorium',    'depreciation_rate' => 20],
            ['name' => 'School Bus — Dhaka-01',     'category' => 'Vehicle',     'purchase_price' => 850000,'location' => 'Garage',         'depreciation_rate' => 15],
            ['name' => 'Computer Lab — PC Set',     'category' => 'Electronics', 'purchase_price' => 320000,'location' => 'Computer Lab',   'depreciation_rate' => 25],
            ['name' => 'Library Shelf Set',         'category' => 'Furniture',   'purchase_price' => 28000, 'location' => 'Library',        'depreciation_rate' => 10],
            ['name' => 'CCTV System',               'category' => 'Electronics', 'purchase_price' => 55000, 'location' => 'School Premises','depreciation_rate' => 20],
            ['name' => 'Classroom Chairs (50 pcs)', 'category' => 'Furniture',   'purchase_price' => 37500, 'location' => 'Block A',        'depreciation_rate' => 10],
        ];

        foreach ($assetsData as $a) {
            $asset = Asset::firstOrCreate(
                ['school_id' => $sid, 'name' => $a['name']],
                [
                    'category'           => $a['category'],
                    'purchase_date'      => now()->subYears(rand(1, 3))->toDateString(),
                    'purchase_price'     => $a['purchase_price'],
                    'current_value'      => round($a['purchase_price'] * 0.75, 2),
                    'depreciation_method'=> 'straight_line',
                    'depreciation_rate'  => $a['depreciation_rate'],
                    'location'           => $a['location'],
                    'status'             => 'active',
                ]
            );

            // One maintenance log per asset
            if ($asset->wasRecentlyCreated) {
                AssetMaintenanceLog::create([
                    'school_id'   => $sid,
                    'asset_id'    => $asset->id,
                    'date'        => now()->subMonths(rand(1, 6))->toDateString(),
                    'description' => 'Routine inspection and servicing.',
                    'cost'        => rand(500, 5000),
                    'vendor'      => 'Tech Services Ltd.',
                ]);
            }
        }
    }
}
