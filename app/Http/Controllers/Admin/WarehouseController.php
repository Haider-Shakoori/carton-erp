<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLocationBalance;
use App\Models\StockTransfer;
use App\Models\Warehouse;
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
}
