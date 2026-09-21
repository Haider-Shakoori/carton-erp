<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocationBalance;
use App\Models\PurchaseItem;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\WarehouseInventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->with('locations')
            ->withCount('balances')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $balances = InventoryLocationBalance::query()
            ->with(['purchaseItem.product', 'warehouse', 'location'])
            ->where(function ($query) {
                $query->where('quantity', '>', 0)
                    ->orWhere('quantity_kg', '>', 0);
            })
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->paginate(50);

        $transfers = StockTransfer::query()
            ->with('items')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.enterprise.warehouses.index', compact('warehouses', 'balances', 'transfers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|alpha_dash|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        Warehouse::create($data + ['is_active' => true, 'is_default' => false]);

        return back()->with('success', 'Warehouse created.');
    }

    public function storeLocation(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'code' => 'required|string|max:80|alpha_dash',
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($warehouse->locations()->where('code', $data['code'])->exists()) {
            return back()->withErrors(['code' => 'This location code already exists in the warehouse.']);
        }

        $warehouse->locations()->create($data + ['is_active' => true]);

        return back()->with('success', 'Warehouse location created.');
    }

    public function transfer(Request $request, WarehouseInventoryService $service)
    {
        $data = $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses,id|different:to_warehouse_id',
            'to_warehouse_id' => 'required|integer|exists:warehouses,id',
            'reason' => 'required|string|min:5|max:2000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|integer|exists:purchase_items,id',
            'items.*.from_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'items.*.to_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.quantity_kg' => 'nullable|numeric|min:0',
        ]);

        try {
            $transfer = DB::transaction(function () use ($data) {
                $transfer = StockTransfer::create([
                    'transfer_no' => 'TRF-'.now()->format('YmdHis').'-'.uniqid(),
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                    'status' => 'draft',
                    'reason' => $data['reason'],
                    'requested_by' => auth()->id(),
                ]);

                foreach ($data['items'] as $row) {
                    $batch = PurchaseItem::findOrFail($row['purchase_item_id']);
                    $quantity = (float) ($row['quantity'] ?? 0);
                    $quantityKg = (float) ($row['quantity_kg'] ?? 0);

                    if ($batch->isRollBatch() && $quantityKg <= 0) {
                        throw new \RuntimeException('Roll-based transfers require a positive kg quantity.');
                    }

                    if (! $batch->isRollBatch() && $quantity <= 0) {
                        throw new \RuntimeException('Non-roll transfers require a positive quantity.');
                    }

                    $transfer->items()->create([
                        'purchase_item_id' => $batch->id,
                        'product_id' => $batch->product_id,
                        'from_location_id' => $row['from_location_id'] ?? null,
                        'to_location_id' => $row['to_location_id'] ?? null,
                        'quantity' => $quantity,
                        'quantity_kg' => $quantityKg,
                        'unit' => $batch->isRollBatch() ? 'kg' : ($batch->unit ?: 'unit'),
                        'unit_cost_usd' => $batch->landedCostPerInventoryUnitUsd(),
                    ]);
                }

                return $transfer;
            });

            $service->postTransfer($transfer);

            return back()->with('success', 'Stock transfer posted.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
