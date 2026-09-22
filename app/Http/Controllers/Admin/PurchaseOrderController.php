<?php
// app/Http/Controllers/Admin/PurchaseOrderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Account;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryLocationService;
use App\Services\ProcurementService;
use App\Models\Setting;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        $suppliers = Account::where('account_type', 'supplier')
            ->where('is_active', true)
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $nextPurchaseNo = Purchase::generateNumber();

        // Build query with search and status filter - EAGER LOAD items and expenses
        $query = Purchase::with(['supplier', 'currency', 'items', 'expenses']);

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Get paginated results
        $purchaseOrders = $query->latest()->paginate(15);

        // Simple stats
        $stats = [
            'total' => Purchase::count(),
            'draft' => Purchase::where('status', 'draft')->count(),
            'shipping' => Purchase::where('status', 'shipping')->count(),
            'arrived' => Purchase::where('status', 'arrived')->count(),
        ];

        return view('admin.purchase-orders.index', compact(
            'suppliers',
            'currencies',
            'stats',
            'nextPurchaseNo',
            'purchaseOrders'
        ));
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create()
    {
        return redirect()->route('admin.purchase-orders.index');
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request)
    {
        // Validate the fields coming from your modal
        $request->validate([
            'purchase_no' => 'required|unique:purchases,purchase_no',
            'supplier_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        try {
            DB::beginTransaction();

            // Create the main Purchase record
            $purchase = Purchase::create([
                'purchase_no' => $request->purchase_no,
                'supplier_id' => $request->supplier_id,
                'currency_id' => $request->currency_id,
                'status' => 'draft', // Default status for new orders
                'purchase_date' => now(),
            ]);

            DB::commit();

            // Redirect back or to the show page so they can add items
            return redirect()->route('admin.purchase-orders.show', $purchase->id)
                ->with('success', 'Purchase order created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error creating purchase order: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Store a newly created purchase item to an existing order.
     */
    public function storeItem(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail($request->purchase_id);

            // Calculate values
            $total = $request->qty * $request->unit_price;
            $usdUnitPrice = $request->unit_price / $request->rate;
            $usdTotal = $total / $request->rate;

            // Create item
            $item = PurchaseItem::create([
                'purchase_id' => $request->purchase_id,
                'product_id' => $request->product_id,
                'qty' => $request->qty,
                'remarks' => $request->remarks,
                'purchase_currency_id' => $purchase->currency_id,
                'unit_price' => $request->unit_price,
                'total' => $total,
                'rate' => $request->rate,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'expense_per_item' => 0,
                'usd_expense_per_item' => 0,
            ]);

            // Update purchase totals
            if (method_exists($this, 'updatePurchaseTotals')) {
                $this->updatePurchaseTotals($purchase);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully',
                'item' => $item
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show($id)
    {
        $purchase = Purchase::with([
            'supplier',
            'currency',
            'items',           // Load items
            'items.product',   // Load product for each item
            'expenses',
            'expenses.agent',
            'expenses.currency',
        ])->findOrFail($id);

        $products = Product::where('is_active', true)->where('type','raw_material')->get();
        $agents = Account::where('account_type', 'agent')
            ->where('is_active', true)
            ->get();
        $currencies = Currency::where('is_active', true)->get();

        return view('admin.purchase-orders.show', compact(
            'purchase',
            'products',
            'agents',
            'currencies'
        ));
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit($id)
    {
        $purchase = Purchase::with(['supplier', 'currency'])->findOrFail($id);
        $suppliers = Account::where('account_type', 'supplier')
            ->where('is_active', true)
            ->get();
        $currencies = Currency::where('is_active', true)->get();

        return view('admin.purchase-orders.edit', compact('purchase', 'suppliers', 'currencies'));
    }

    /**
     * Update the specified purchase order.
     */
    public function update(Request $request, $id)
    {
        $purchase = Purchase::findOrFail($id);

        $request->validate([
            'supplier_id' => 'required|exists:accounts,id',
            'currency_id' => 'required|exists:currencies,id',
            'purchase_date' => 'nullable|date',
            'arrival_date' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric|min:0',
            'descriptions' => 'nullable|string',
        ]);

        $purchase->update([
            'supplier_id' => $request->supplier_id,
            'currency_id' => $request->currency_id,
            'purchase_date' => $request->purchase_date,
            'arrival_date' => $request->arrival_date,
            'exchange_rate' => $request->exchange_rate ?? $purchase->exchange_rate,
            'descriptions' => $request->descriptions,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Purchase order updated successfully',
            'redirect' => route('admin.purchase-orders.show', $purchase->id)
        ]);
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy($id)
    {
        try {
            $purchase = Purchase::findOrFail($id);

            DB::beginTransaction();

            // Delete related items and expenses
            $purchase->items()->delete();
            $purchase->expenses()->delete();
            $purchase->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting purchase order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update purchase order status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,shipping,arrived'
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::with(['supplier', 'currency', 'items'])->findOrFail($id);
            $oldStatus = $purchase->status;
            $newStatus = $request->status;

            $approvalRequired = (bool) (Setting::query()->value('purchase_approval_required') ?? false);
            if (
                $approvalRequired
                && in_array($newStatus, ['shipping', 'arrived'], true)
                && $purchase->approval_status !== 'approved'
            ) {
                throw new \RuntimeException('Purchase approval is required before shipping or receiving stock.');
            }

            // If status is changing to 'shipping', create a transaction
            if ($newStatus === 'shipping' && $oldStatus !== 'shipping') {
                // Check if transaction already exists for this purchase
                $existingTransaction = Transaction::where('table_name', 'purchases')
                    ->where('table_row_id', $purchase->id)
                    ->where('type', 'purchase')
                    ->where('account_id', $purchase->supplier_id)
                    ->first();

                if (!$existingTransaction) {
                    // Generate description
                    $description = $this->generateTransactionDescription($purchase);

                    // Create transaction for supplier (CREDIT)
                    Transaction::create([
                        'type' => 'purchase',
                        'table_name' => 'purchases',
                        'table_row_id' => $purchase->id,
                        'account_id' => $purchase->supplier_id,
                        'currency_id' => $purchase->currency_id,
                        'amount' => $purchase->subtotal ?? 0,
                        'transaction_type' => 'credit', // Supplier account is credited
                        'is_cash' => false,
                        'description' => $description,
                        'is_visible' => true,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            // Update status
            $purchase->status = $newStatus;

            if ($newStatus === 'arrived' && !$purchase->arrival_date) {
                $purchase->arrival_date = now();
            }

            $purchase->save();

            if ($newStatus === 'arrived') {
                $purchase->loadMissing('items');
                $warehouseInventory = app(InventoryLocationService::class);

                foreach ($purchase->items as $item) {
                    $warehouseInventory->ensureBatch($item);
                }

                app(ProcurementService::class)->ensureLegacyGoodsReceipt($purchase);
            }

            DB::commit();

            return redirect()->back()->with('success', "Purchase order status changed from {$oldStatus} to {$newStatus}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    public function approve($id, ProcurementService $procurement)
    {
        try {
            $purchase = Purchase::query()->findOrFail($id);
            $procurement->approvePurchase($purchase, Auth::user());

            return back()->with('success', 'Purchase order approved.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Generate automated transaction description
     */
    private function generateTransactionDescription($purchase)
    {
        $currencySymbol = $purchase->currency->symbol ?? '$';
        $amount = number_format($purchase->grand_total ?? 0, 2);
        $supplierName = $purchase->supplier->name ?? 'Unknown Supplier';
        $itemCount = $purchase->items->count();
        $totalQty = number_format($purchase->items->sum('qty'));

        return "Purchase Order #{$purchase->purchase_no} - Credit for ({$itemCount} items, {$totalQty} units)";
    }

    /**
     * DataTable for purchase orders listing.
     */
    public function datatable(Request $request)
    {
        $query = Purchase::with(['supplier', 'currency'])->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('purchase_no', function ($row) {
                return '<a href="' . route('admin.purchase-orders.show', $row->id) . '"
                        class="fw-bold text-primary text-decoration-none">
                        ' . e($row->purchase_no) . '
                    </a>';
            })
            ->addColumn('supplier', function ($row) {
                if (!$row->supplier) return '-';
                return e($row->supplier->name);
            })
            ->addColumn('currency', function ($row) {
                return $row->currency?->code ?? '-';
            })
            ->editColumn('purchase_date', function ($row) {
                return $row->purchase_date ? date('Y-m-d', strtotime($row->purchase_date)) : '-';
            })
            ->editColumn('arrival_date', function ($row) {
                return $row->arrival_date ? date('Y-m-d', strtotime($row->arrival_date)) : '-';
            })
            ->editColumn('usd_grand_total', function ($row) {
                return '$' . number_format($row->usd_grand_total, 2);
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'draft' => '<span class="badge bg-secondary">Draft</span>',
                    'shipping' => '<span class="badge bg-warning">Shipping</span>',
                    'arrived' => '<span class="badge bg-success">Arrived</span>',
                ];
                return $badges[$row->status] ?? '<span class="badge bg-dark">Unknown</span>';
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="btn-group">
                        <a href="' . route('admin.purchase-orders.show', $row->id) . '"
                           class="btn btn-sm btn-info">View</a>
                        <a href="' . route('admin.purchase-orders.edit', $row->id) . '"
                           class="btn btn-sm btn-primary">Edit</a>
                        <button type="button"
                                class="btn btn-sm btn-danger delete-btn"
                                data-id="' . $row->id . '">Delete</button>
                    </div>
                ';
            })
            ->rawColumns(['purchase_no', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Generate unique purchase number.
     */
    private function generatePurchaseNumber()
    {
        $year = date('Y');
        $month = date('m');

        $lastPurchase = Purchase::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastNumber = intval(substr($lastPurchase->purchase_no, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PO-{$year}{$month}-{$newNumber}";
    }
}
