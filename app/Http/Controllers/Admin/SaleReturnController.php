<?php
// app/Http/Controllers/Admin/SaleReturnController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleReturnController extends Controller
{
    /**
     * Display a listing of sale returns.
     */
    public function index(Request $request)
    {
        $query = SaleReturn::with(['customer', 'currency', 'sale', 'items']);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhereHas('sale', function ($q) use ($search) {
                        $q->where('sale_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $returns = $query->latest()->paginate(15);

        $stats = [
            'total' => SaleReturn::count(),
            'draft' => SaleReturn::where('status', 'draft')->count(),
            'approved' => SaleReturn::where('status', 'approved')->count(),
            'rejected' => SaleReturn::where('status', 'rejected')->count(),
            'processed' => SaleReturn::where('status', 'processed')->count(),
        ];

        return view('admin.sale-returns.index', compact('returns', 'stats'));
    }

    /**
     * Show the form for creating a new sale return.
     */
    public function create(Request $request)
    {
        $saleId = $request->sale_id;
        $sale = null;

        if ($saleId) {
            $sale = Sale::with(['customer', 'currency', 'items.product', 'items.purchaseItem'])
                ->findOrFail($saleId);

            if (!in_array($sale->status, ['confirmed', 'shipped', 'delivered'])) {
                return redirect()->route('admin.sales.index')
                    ->with('error', 'Only confirmed or delivered sales can be returned.');
            }
        }

        $sales = Sale::whereIn('status', ['confirmed', 'shipped', 'delivered'])
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        $customers = Account::where('account_type', 'customer')
            ->where('is_active', true)
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $nextReturnNo = SaleReturn::generateNumber();

        return view('admin.sale-returns.create', compact(
            'sale',
            'sales',
            'customers',
            'currencies',
            'nextReturnNo'
        ));
    }

    /**
     * Store a newly created sale return.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'return_date' => 'nullable|date',
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'restocking_fee' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.sale_item_id' => 'required|exists:sale_items,id',
            'items.*.qty_returned' => 'required|numeric|min:0.01',
            'items.*.reason' => 'required|string|in:damaged,defective,wrong_item,wrong_quantity,customer_cancelled,quality_issue,other',
            'items.*.condition' => 'required|string|in:new,used,damaged',
            'items.*.reason_notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $sale = Sale::with(['items.purchaseItem', 'currency', 'customer'])->findOrFail($request->sale_id);

            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'return_no' => $request->return_no ?? SaleReturn::generateNumber(),
                'customer_id' => $sale->customer_id,
                'currency_id' => $sale->currency_id,
                'return_date' => $request->return_date ?? now(),
                'status' => 'draft',
                'exchange_rate' => $sale->exchange_rate ?? 1,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'restocking_fee' => $request->restocking_fee ?? 0,
                'usd_restocking_fee' => ($request->restocking_fee ?? 0) / ($sale->exchange_rate ?? 1),
            ]);

            foreach ($request->items as $itemData) {
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);

                if ($saleItem->sale_id != $sale->id) {
                    throw new \Exception('Item does not belong to the selected sale.');
                }

                if ($itemData['qty_returned'] > $saleItem->qty) {
                    throw new \Exception("Return quantity exceeds original quantity for item: {$saleItem->product->name}");
                }

                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->whereHas('saleReturn', function ($q) {
                        $q->whereIn('status', ['draft', 'approved']);
                    })
                    ->sum('qty_returned');

                if (($alreadyReturned + $itemData['qty_returned']) > $saleItem->qty) {
                    throw new \Exception("Item already partially returned. Available to return: " .
                        ($saleItem->qty - $alreadyReturned) . " units");
                }

                $exchangeRate = $sale->exchange_rate ?? 1;
                $unitPrice = $saleItem->unit_price;
                $qty = $itemData['qty_returned'];
                $total = $unitPrice * $qty;
                $usdUnitPrice = $saleItem->usd_unit_price;
                $usdTotal = $usdUnitPrice * $qty;

                $returnItem = SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'sale_id' => $sale->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'purchase_item_id' => $saleItem->purchase_item_id,
                    'qty_returned' => $qty,
                    'original_qty' => $saleItem->qty,
                    'original_unit_price' => $saleItem->unit_price,
                    'original_usd_unit_price' => $saleItem->usd_unit_price,
                    'unit_price' => $unitPrice,
                    'total' => $total,
                    'discount' => 0,
                    'usd_unit_price' => $usdUnitPrice,
                    'usd_total' => $usdTotal,
                    'usd_discount' => 0,
                    'rate' => $exchangeRate,
                    'cost_per_unit_usd' => $saleItem->cost_per_unit_usd,
                    'total_cost_usd' => $saleItem->cost_per_unit_usd * $qty,
                    'reason' => $itemData['reason'],
                    'reason_notes' => $itemData['reason_notes'] ?? null,
                    'condition' => $itemData['condition'],
                    'restocked' => false,
                ]);
            }

            $return->recalculateTotals();

            DB::commit();

            return redirect()->route('admin.sale-returns.show', $return->id)
                ->with('success', 'Sale return created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error creating return: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified sale return.
     */
    public function show($id)
    {
        $return = SaleReturn::with([
            'sale',
            'sale.customer',
            'customer',
            'currency',
            'items.product',
            'items.saleItem',
            'items.purchaseItem.purchase',
        ])->findOrFail($id);

        $canProcess = $return->status === 'approved' && $return->items()->where('restocked', false)->count() > 0;

        return view('admin.sale-returns.show', compact('return', 'canProcess'));
    }

    /**
     * Update the specified sale return.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'restocking_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $return = SaleReturn::findOrFail($id);

            if ($return->status !== 'draft') {
                throw new \Exception('Only draft returns can be updated.');
            }

            $return->reason = $request->reason;
            $return->notes = $request->notes;
            $return->restocking_fee = $request->restocking_fee ?? 0;
            $return->usd_restocking_fee = ($request->restocking_fee ?? 0) / ($return->exchange_rate ?? 1);

            $return->recalculateTotals();
            $return->save();

            DB::commit();

            return redirect()->route('admin.sale-returns.show', $return->id)
                ->with('success', 'Sale return updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error updating return: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update return status (approve/reject).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $return = SaleReturn::findOrFail($id);

            if ($return->status !== 'draft') {
                throw new \Exception('Only draft returns can be approved or rejected.');
            }

            $return->status = $request->status;

            if ($request->notes) {
                $return->notes = ($return->notes ? $return->notes . "\n\n" : '') .
                    "Status update notes: " . $request->notes;
            }

            $return->save();

            DB::commit();

            $message = $request->status === 'approved'
                ? 'Return approved successfully. You can now process it to restock inventory.'
                : 'Return rejected.';

            return redirect()->route('admin.sale-returns.show', $return->id)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error updating status: ' . $e->getMessage()]);
        }
    }

    /**
     * Process the return (restock items and create transactions).
     */
    public function process($id)
    {
        try {
            DB::beginTransaction();

            $return = SaleReturn::with(['items', 'sale', 'sale.items'])->findOrFail($id);

            if ($return->status !== 'approved') {
                throw new \Exception('Only approved returns can be processed.');
            }

            if ($return->status === 'processed') {
                throw new \Exception('This return has already been processed.');
            }

            $itemsToRestock = $return->items()->where('restocked', false)->get();

            if ($itemsToRestock->count() === 0) {
                throw new \Exception('No items to restock. All items have already been processed.');
            }

            foreach ($itemsToRestock as $returnItem) {
                $saleItem = SaleItem::find($returnItem->sale_item_id);
                
                if ($saleItem && $saleItem->purchase_item_id) {
                    $purchaseItem = \App\Models\PurchaseItem::find($saleItem->purchase_item_id);
                    
                    if ($purchaseItem) {
                        $purchaseItem->qty_available += $returnItem->qty_returned;
                        $purchaseItem->save();
                        
                        $returnItem->restocked = true;
                        $returnItem->restocked_at = now();
                        $returnItem->save();
                    }
                }
            }

            $return->status = 'processed';
            $return->processed_at = now();
            
            $remainingItems = $return->items()->where('restocked', false)->count();
            if ($remainingItems === 0) {
                $return->fully_restocked = true;
            }
            
            $return->save();

            $this->createReturnTransactions($return);

            DB::commit();

            return redirect()->route('admin.sale-returns.show', $return->id)
                ->with('success', 'Return processed successfully! Inventory has been restocked and transactions created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error processing return: ' . $e->getMessage()]);
        }
    }

    /**
     * Create transactions for the return.
     */
    private function createReturnTransactions($return)
    {
        $currencySymbol = $return->currency->symbol ?? '$';
        $returnNo = $return->return_no;
        $saleNo = $return->sale->sale_no ?? 'N/A';

        Transaction::create([
            'type' => 'sale_return',
            'table_name' => 'sale_returns',
            'table_row_id' => $return->id,
            'account_id' => $return->customer_id,
            'currency_id' => $return->currency_id,
            'amount' => $return->grand_total,
            'transaction_type' => 'credit',
            'is_cash' => false,
            'description' => "Sale Return #{$returnNo} for Sale #{$saleNo} - Credit amount " . $currencySymbol . ' ' . number_format($return->grand_total, 2),
            'is_visible' => true,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        if ($return->sale && $return->sale->advance_payment > 0) {
            $refundAmount = min($return->sale->advance_payment, $return->grand_total);
            
            if ($refundAmount > 0) {
                Transaction::create([
                    'type' => 'sale_return_refund',
                    'table_name' => 'sale_returns',
                    'table_row_id' => $return->id,
                    'account_id' => $return->customer_id,
                    'currency_id' => $return->currency_id,
                    'amount' => $refundAmount,
                    'transaction_type' => 'debit',
                    'is_cash' => true,
                    'description' => "Sale Return #{$returnNo} - Advance payment refund " . $currencySymbol . ' ' . number_format($refundAmount, 2),
                    'is_visible' => true,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Delete the specified sale return.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $return = SaleReturn::findOrFail($id);

            if (!in_array($return->status, ['draft', 'rejected'])) {
                throw new \Exception('Only draft or rejected returns can be deleted.');
            }

            $restockedItems = $return->items()->where('restocked', true)->count();
            if ($restockedItems > 0) {
                throw new \Exception('Cannot delete return with restocked items. Please reverse the restock first.');
            }

            $return->items()->delete();
            $return->delete();

            DB::commit();

            return redirect()->route('admin.sale-returns.index')
                ->with('success', 'Sale return deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting return: ' . $e->getMessage());
        }
    }

    /**
     * Get sale items for selection in the create form.
     */
    public function getSaleItems(Request $request)
    {
        try {
            $request->validate([
                'sale_id' => 'required|exists:sales,id',
            ]);

            $sale = Sale::with(['items.product', 'currency'])->findOrFail($request->sale_id);

            $items = [];
            foreach ($sale->items as $saleItem) {
                $alreadyReturned = SaleReturnItem::where('sale_item_id', $saleItem->id)
                    ->whereHas('saleReturn', function ($q) {
                        $q->whereIn('status', ['draft', 'approved']);
                    })
                    ->sum('qty_returned') ?? 0;

                $qty = (float) $saleItem->qty;
                $alreadyReturned = (float) $alreadyReturned;
                $availableQty = $qty - $alreadyReturned;

                if ($availableQty > 0) {
                    $items[] = [
                        'id' => (int) $saleItem->id,
                        'product_name' => $saleItem->product->name ?? 'Unknown Product',
                        'product_id' => (int) $saleItem->product_id,
                        'qty' => $qty,
                        'already_returned' => $alreadyReturned,
                        'available_qty' => $availableQty,
                        'unit_price' => (float) $saleItem->unit_price,
                        'usd_unit_price' => (float) $saleItem->usd_unit_price,
                        'total' => (float) $saleItem->total,
                        'currency_symbol' => $sale->currency->symbol ?? '$',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'items' => $items,
                'currency_symbol' => $sale->currency->symbol ?? '$',
                'currency_code' => $sale->currency->code ?? 'USD',
                'exchange_rate' => (float) ($sale->exchange_rate ?? 1),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale items: ' . $e->getMessage()
            ], 500);
        }
    }
}