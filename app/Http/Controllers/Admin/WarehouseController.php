<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocationBalance;
use App\Models\InventoryTransfer;
use App\Models\PurchaseItem;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\InventoryLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->with(['locations' => function ($query) {
                $query->withSum(
                    ['balances as available_quantity' => fn ($q) => $q->where('condition_status', 'available')],
                    'quantity'
                )->withSum(
                    ['balances as blocked_quantity' => fn ($q) => $q->where('condition_status', 'blocked')],
                    'quantity'
                )->withSum(
                    ['balances as damaged_quantity' => fn ($q) => $q->where('condition_status', 'damaged')],
                    'quantity'
                );
            }])
            ->orderBy('name')
            ->get();

        $balances = InventoryLocationBalance::query()
            ->with(['purchaseItem.product', 'purchaseItem.purchase', 'location.warehouse'])
            ->where('quantity', '>', 0)
            ->orderByDesc('updated_at')
            ->paginate(30);

        return view('admin.warehouses.index', compact('warehouses', 'balances'));
    }

    public function transfers()
    {
        $transfers = InventoryTransfer::query()
            ->with(['fromLocation.warehouse', 'toLocation.warehouse', 'requestedBy', 'approvedBy', 'items'])
            ->latest()
            ->paginate(25);

        return view('admin.warehouses.transfers.index', compact('transfers'));
    }

    public function createTransfer()
    {
        $locations = WarehouseLocation::query()
            ->where('is_active', true)
            ->with('warehouse')
            ->whereHas('warehouse', fn ($q) => $q->where('is_active', true))
            ->orderBy('warehouse_id')
            ->orderBy('name')
            ->get();

        $batches = PurchaseItem::query()
            ->with(['product', 'purchase', 'locationBalances.location'])
            ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
            ->where('qty_available', '>', 0)
            ->orderBy('id')
            ->get()
            ->filter(fn (PurchaseItem $batch) => app(InventoryLocationService::class)->availableForBatch($batch) > 0)
            ->values();

        return view('admin.warehouses.transfers.create', compact('locations', 'batches'));
    }

    public function storeTransfer(Request $request, InventoryLocationService $inventory)
    {
        $validated = $request->validate([
            'from_location_id' => 'required|exists:warehouse_locations,id|different:to_location_id',
            'to_location_id' => 'required|exists:warehouse_locations,id',
            'reason' => 'required|string|min:5|max:1000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|integer|distinct|exists:purchase_items,id',
            'items.*.quantity' => 'required|numeric|min:0.000001',
        ]);

        try {
            $transfer = DB::transaction(function () use ($validated, $inventory) {
                $from = WarehouseLocation::query()->with('warehouse')->findOrFail($validated['from_location_id']);
                $to = WarehouseLocation::query()->with('warehouse')->findOrFail($validated['to_location_id']);

                if (! $from->is_active || ! $to->is_active) {
                    throw new RuntimeException('Both warehouse locations must be active.');
                }

                $transfer = InventoryTransfer::query()->create([
                    'transfer_no' => $inventory->nextTransferNumber(),
                    'from_location_id' => $from->id,
                    'to_location_id' => $to->id,
                    'status' => 'draft',
                    'reason' => $validated['reason'],
                    'requested_by' => Auth::id(),
                ]);

                foreach ($validated['items'] as $row) {
                    $batch = PurchaseItem::query()->lockForUpdate()->findOrFail($row['purchase_item_id']);
                    $inventory->ensureBatch($batch);

                    $sourceAvailable = (float) InventoryLocationBalance::query()
                        ->where('purchase_item_id', $batch->id)
                        ->where('warehouse_location_id', $from->id)
                        ->where('condition_status', 'available')
                        ->sum('quantity');

                    if ($sourceAvailable + 0.000001 < (float) $row['quantity']) {
                        throw new RuntimeException(
                            "Batch #{$batch->id} does not have enough available stock in the selected source location."
                        );
                    }

                    $transfer->items()->create([
                        'purchase_item_id' => $batch->id,
                        'quantity' => (float) $row['quantity'],
                        'unit' => $batch->inventoryCostBasisUnit(),
                    ]);
                }

                return $transfer;
            });

            return redirect()
                ->route('admin.warehouses.transfers')
                ->with('success', "Stock transfer {$transfer->transfer_no} created for independent approval.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function approveTransfer(
        InventoryTransfer $inventoryTransfer,
        InventoryLocationService $inventory
    ) {
        try {
            $inventory->approveTransfer($inventoryTransfer, (int) Auth::id());

            return back()->with('success', 'Stock transfer approved.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function completeTransfer(
        InventoryTransfer $inventoryTransfer,
        InventoryLocationService $inventory
    ) {
        try {
            $inventory->completeTransfer($inventoryTransfer, (int) Auth::id());

            return back()->with('success', 'Stock transfer completed without changing total company inventory.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateCondition(
        Request $request,
        InventoryLocationBalance $balance,
        InventoryLocationService $inventory
    ) {
        $validated = $request->validate([
            'condition_status' => 'required|in:available,blocked,damaged',
            'quantity' => 'required|numeric|min:0.000001',
        ]);

        try {
            $inventory->reclassify(
                $balance,
                $validated['condition_status'],
                (float) $validated['quantity']
            );

            return back()->with('success', 'Warehouse stock condition updated.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
