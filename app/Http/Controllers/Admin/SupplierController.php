<?php
// app/Http/Controllers/Admin/SupplierController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request)
    {
        $query = Account::where('account_type', 'supplier')->where('is_active', true);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by currency
        if ($request->filled('currency_id')) {
            $currencyId = $request->currency_id;
            $supplierIds = Transaction::where('currency_id', $currencyId)
                ->where('status', 'active')
                ->whereHas('account', function($q) {
                    $q->where('account_type', 'supplier')->where('is_active', true);
                })
                ->distinct()
                ->pluck('account_id');
            
            if ($supplierIds->isNotEmpty()) {
                $query->whereIn('id', $supplierIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $query->select('accounts.*')->addSelect([
            'balance' => DB::table('transactions')
                ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END)')
                ->whereColumn('account_id', 'accounts.id')
                ->where('status', 'active'),
        ]);

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = $this->calculateStats($request->currency_id);

        $currencies = Currency::where('is_active', true)->get();

        return view('admin.suppliers.index', compact('suppliers', 'stats', 'currencies'));
    }

    /**
     * Calculate statistics for suppliers.
     */
    private function calculateStats($currencyId = null)
    {
        $supplierIds = Account::where('account_type', 'supplier')->where('is_active', true)->pluck('id');

        if ($supplierIds->isEmpty()) {
            return [
                'total_suppliers' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'balance' => 0,
            ];
        }

        $query = Transaction::whereIn('account_id', $supplierIds)->where('status', 'active');

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        $credit = (clone $query)->where('transaction_type', 'credit')->sum('amount');
        $debit = (clone $query)->where('transaction_type', 'debit')->sum('amount');

        return [
            'total_suppliers' => $supplierIds->count(),
            'total_credit' => $credit,
            'total_debit' => $debit,
            'balance' => $credit - $debit,
        ];
    }

    /**
     * Generate a unique supplier code with SUP- prefix.
     */
    public function generateCode()
    {
        $prefix = 'SUP-';
        $lastSupplier = Account::where('account_type', 'supplier')
            ->where('code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSupplier) {
            $lastNumber = intval(substr($lastSupplier->code, strlen($prefix)));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return response()->json([
            'code' => $prefix . $newNumber,
            'suffix' => $newNumber
        ]);
    }

    /**
     * Check if supplier code already exists.
     */
    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->code;
        
        // Ensure code has SUP- prefix
        if (!str_starts_with($code, 'SUP-')) {
            $code = 'SUP-' . $code;
        }
        
        $exists = Account::where('code', $code)->exists();
        
        return response()->json([
            'available' => !$exists,
            'code' => $code
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            // Ensure code has SUP- prefix
            $code = $request->code;
            if (!str_starts_with($code, 'SUP-')) {
                $code = 'SUP-' . $code;
            }

            $account = Account::create([
                'name' => $request->name,
                'code' => $code,
                'account_type' => 'supplier',
                'contact' => $request->contact,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'company' => $request->company,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => true,
                'bi_icon' => 'bi-truck',
                'bi_icon_color' => 'success',
                'is_safe' => false,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully!',
                'supplier' => $account
            ]);
        } catch (Exception $e) {
            Log::error('Supplier creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified supplier.
     */
    public function show($id)
    {
        $supplier = Account::with(['transactions.currency'])->findOrFail($id);
        $currencies = Currency::where('is_active', true)->get();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency',
                'currencies.symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            )
            ->where('account_id', $supplier->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.suppliers.show', compact('supplier', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Edit supplier.
     */
    public function edit($id)
    {
        $supplier = Account::findOrFail($id);
        return response()->json($supplier);
    }

    /**
     * Update supplier.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code,' . $id,
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier = Account::findOrFail($id);
        
        // Ensure code has SUP- prefix
        $code = $request->code;
        if (!str_starts_with($code, 'SUP-')) {
            $code = 'SUP-' . $code;
        }

        $supplier->update([
            'name' => $request->name,
            'code' => $code,
            'contact' => $request->contact,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'company' => $request->company,
            'address' => $request->address,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Supplier updated successfully!'
        ]);
    }

    /**
     * Delete supplier.
     */
    public function destroy($id)
    {
        try {
            $supplier = Account::findOrFail($id);
            Transaction::where('account_id', $supplier->id)->delete();
            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Supplier deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print supplier statement.
     */
    public function print($id)
    {
        $supplier = Account::findOrFail($id);
        $currencies = Currency::where('is_active', true)->get();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.id as currency_id',
                'currencies.code as currency',
                'currencies.symbol',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance")
            )
            ->where('account_id', $supplier->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.suppliers.print', compact('supplier', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Show account details for API.
     */
    public function showAccount($id)
    {
        $supplier = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select(
                'currency_id',
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            )
            ->where('account_id', $supplier->id)
            ->where('status', 'active')
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];
        foreach ($balances as $item) {
            $currency = Currency::find($item->currency_id);
            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'symbol' => $currency->symbol ?? '$',
                'amount' => number_format($item->net_balance, 2),
                'balance' => $item->net_balance,
            ];
        }

        return response()->json([
            'id' => $supplier->id,
            'name' => $supplier->name,
            'code' => $supplier->code,
            'balances' => $currencyBalances,
        ]);
    }

    /**
     * Send WhatsApp message to supplier.
     */
    public function sendWhatsApp($id)
    {
        $supplier = Account::findOrFail($id);
        
        if (!$supplier->whatsapp) {
            return response()->json([
                'error' => 'Supplier does not have a WhatsApp number.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp message sent successfully!'
        ]);
    }
}
