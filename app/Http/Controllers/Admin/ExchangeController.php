<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WhatsAppHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exchange;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Currency;
use App\Models\ExchangePurchase;
use App\Models\Transaction;
use App\Models\ExchangeRate;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExchangeController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $accounts = Account::whereIn('account_sub_category_id', [1])->get();
        $officeAccounts = Account::where('account_sub_category_id', 4)->get();
        $currencies = Currency::all();
        return view('admin.exchanges.index', compact('accounts', 'officeAccounts', 'currencies'));
    }

    public function data(Request $request)
    {
        $query = Exchange::with(['customerAccount', 'baseCurrency', 'targetCurrency']);
        if ($request->filled('code')) {
            $query->whereHas('customerAccount', function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->code . '%');
            });
        }
        $query->orderBy('created_at', 'desc');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('customer', fn($row) => $row->customerAccount->name ?? '-')
            ->addColumn('customer_code', fn($row) => $row->customerAccount->code ?? '-')
            ->addColumn('from_currency', fn($row) => $row->baseCurrency->code ?? '-')
            ->addColumn('to_currency', fn($row) => $row->targetCurrency->code ?? '-')
            ->addColumn('amount', fn($row) => $row->base_amount)
            ->addColumn('rate', fn($row) => $row->rate)
            ->addColumn('cost_rate', fn($row) => auth()->user()->can('view cost rate') ? number_format($row->cost_rate, 2) : '0.00')
            ->addColumn('received', fn($row) => $row->target_amount)
            ->addColumn('profit', fn($row) => auth()->user()->can('view profit') ? $row->target_profit : '0.00')
            ->addColumn('created_at', fn($row) => $row->created_at->format('Y-m-d H:i'))
            ->addColumn('actions', function ($row) {
                return '
                    <button class="btn btn-sm btn-success btn-whatsapp me-1" data-id="' . $row->id . '" title="Send WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </button>
                    <button class="btn btn-sm btn-dark btn-sm me-1 btn-preview-sale" data-id="' . $row->id . '" title="Print Receipt">
                        <i class="bi bi-printer"></i>
                    </button>
                    <button class="btn btn-sm btn-primary btn-edit me-1" data-id="' . $row->id . '">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '">
                        <i class="bi bi-trash"></i>
                    </button>
                ';
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function showReceipt($id)
    {
        $exchange = Exchange::with(['customerAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
        return view('admin.exchanges.receipt', compact('exchange'));
    }
    public function getRate(Request $request)
    {
        $amount = $request->amount;

        $rate = ExchangeRate::where('base_currency_id', $request->base_currency_id)
            ->where('target_currency_id', $request->target_currency_id)
            ->where(function ($query) use ($amount) {
                $query->where(function ($q) use ($amount) {
                    $q->where('min_amount', '<=', $amount)
                        ->where('max_amount', '>=', $amount);
                })->orWhere(function ($q) use ($amount) {
                    $q->where('max_amount', '<', $amount);
                });
            })
            ->orderByDesc('max_amount') // prioritize higher max ranges
            ->orderByDesc('id')         // fallback to latest if same max
            ->first();

        return response()->json([
            'rate' => $rate?->rate ?? 0,
            'cost_rate' => $rate?->cost_rate ?? 0,
        ]);
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'exchange_id'         => 'nullable|exists:exchanges,id',
                'customer_account_id' => 'required|exists:accounts,id',
                'office_account_id'   => 'required|exists:accounts,id',
                'base_currency_id'    => 'required|exists:currencies,id',
                'target_currency_id'  => 'required|exists:currencies,id|different:base_currency_id',
                'base_amount'         => 'required|numeric|min:0.01',
                'rate'                => 'required|numeric|min:0',
                'cost_rate'           => 'required|numeric|min:0',
                'target_amount'       => 'required|numeric|min:0',
                'target_profit'       => 'nullable|numeric',
                'note'                => 'nullable|string'
            ]);

            DB::beginTransaction();

            if (is_null($validated['note'])) {
                $baseCurrency = Currency::findOrFail($validated['base_currency_id'])->code;
                $targetCurrency = Currency::findOrFail($validated['target_currency_id'])->code;
                $validated['note'] = sprintf('Exchange from %s at rate %s to %s', $baseCurrency, $validated['rate'], $targetCurrency);
            }

            // CREATE or UPDATE logic
            if (!empty($validated['exchange_id'])) {
                $exchange = Exchange::findOrFail($validated['exchange_id']);

                // Delete existing related transactions
                Transaction::where('table_row_id', $exchange->id)
                    ->where('table_name', 'exchanges')
                    ->delete();

                // Update exchange
                $exchange->update([
                    'customer_account_id' => $validated['customer_account_id'],
                    'base_currency_id'    => $validated['base_currency_id'],
                    'target_currency_id'  => $validated['target_currency_id'],
                    'base_amount'         => $validated['base_amount'],
                    'rate'                => $validated['rate'],
                    'target_amount'       => $validated['target_amount'],
                    'cost_rate'           => $validated['cost_rate'],
                    'target_profit'       => $validated['target_profit'] ?? 0,
                    'note'                => $validated['note'],
                    'office_account_id'   => $validated['office_account_id'],
                ]);
            } else {
                // Create new exchange
                $exchange = Exchange::create([
                    'customer_account_id' => $validated['customer_account_id'],
                    'base_currency_id'    => $validated['base_currency_id'],
                    'target_currency_id'  => $validated['target_currency_id'],
                    'base_amount'         => $validated['base_amount'],
                    'rate'                => $validated['rate'],
                    'target_amount'       => $validated['target_amount'],
                    'cost_rate'           => $validated['cost_rate'],
                    'target_profit'       => $validated['target_profit'] ?? 0,
                    'note'                => $validated['note'],
                    'office_account_id'   => $validated['office_account_id'],
                    'destination'         => 'account',
                    'is_withdrawn'        => false,
                ]);
            }

            // Record transactions
            $this->recordTransaction($exchange, $exchange->office_account_id, $validated['base_currency_id'], $validated['base_amount'], $exchange->note, 'debit', false, false);
            $this->recordTransaction($exchange, $validated['customer_account_id'], $validated['base_currency_id'], $validated['base_amount'], $exchange->note, 'credit', false, true);
            $this->recordTransaction($exchange, $exchange->office_account_id, $validated['target_currency_id'], $validated['target_amount'], $exchange->note, 'credit', false, false);
            $this->recordTransaction($exchange, $validated['customer_account_id'], $validated['target_currency_id'], $validated['target_amount'], $exchange->note, 'debit', false, true);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Exchange saved successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exchange Store Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    private function recordTransaction($exchange, $accountId, $currencyId, $amount, $note, $type, $isCash, $isVisible)
    {
        Transaction::create([
            'table_name' => 'exchanges',
            'table_row_id' => $exchange->id,
            'type' => 'exchange',
            'account_id' => $accountId,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'transaction_type' => $type,
            'is_cash' => $isCash,
            'is_visible' => $isVisible,
            'status' => 'active',
            'note' => $note,
            'created_by' => Auth::id(),
        ]);
    }

    // public function totals(Request $request)
    // {
    //     $sold = Exchange::selectRaw('base_currency_id, SUM(base_amount) as total')
    //         ->groupBy('base_currency_id')
    //         ->with('baseCurrency:id,code')
    //         ->get()
    //         ->mapWithKeys(function ($item) {
    //             return [$item->baseCurrency->code => (float) $item->total];
    //         });


    //     // 2. Get purchased totals (from ExchangePurchase)
    //     $purchased = ExchangePurchase::selectRaw('target_currency_id, SUM(target_amount) as total')
    //         ->groupBy('target_currency_id')
    //         ->with('targetCurrency:id,code')
    //         ->get()
    //         ->mapWithKeys(function ($item) {
    //             return [$item->targetCurrency->code => (float) $item->total];
    //         });

    //     // 3. Exchange summary details
    //     $details = Exchange::selectRaw('
    //         base_currency_id,
    //         target_currency_id,
    //         COUNT(*) as count,
    //         SUM(base_amount) as total_base,
    //         SUM(target_amount) as total_target
    //     ')
    //         ->groupBy('base_currency_id', 'target_currency_id')
    //         ->with(['baseCurrency:id,code', 'targetCurrency:id,code'])
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'base_currency' => $item->baseCurrency->code ?? 'N/A',
    //                 'target_currency' => $item->targetCurrency->code ?? 'N/A',
    //                 'count' => $item->count,
    //                 'total_base' => round($item->total_base, 2),
    //                 'total_target' => round($item->total_target, 2),
    //             ];
    //         });

    //     // 4. Safe balances per currency from accounts in sub_category 4
    //     $safeAccounts = Account::where('account_sub_category_id', 4)->pluck('id');

    //     $safe = DB::table('transactions')
    //         ->select('currency_id', DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as total'))
    //         ->whereIn('account_id', $safeAccounts)
    //         ->whereNotIn('type', ['exchange-purchase', 'exchange'])
    //         ->groupBy('currency_id')
    //         ->get()
    //         ->mapWithKeys(function ($row) {
    //             $code = \App\Models\Currency::find($row->currency_id)?->code ?? 'N/A';
    //             return [$code => (float) $row->total];
    //         });

    //     return response()->json([
    //         'details' => $details,
    //         'purchased' => $purchased,
    //         'sold' => $sold,
    //         'safe' => $safe
    //     ]);
    // }

    public function totals(Request $request)
    {
        // Card 1: Total count of exchanges
        $total_exchanges = Exchange::count();

        // Card 2: Sum of base_amount grouped by currency (from Exchange)
        $base_totals = Exchange::selectRaw('base_currency_id, SUM(base_amount) as total')
            ->groupBy('base_currency_id')
            ->with('baseCurrency:id,code')
            ->get()
            ->mapWithKeys(fn($row) => [$row->baseCurrency->code => (float) $row->total]);

        // Card 3: Sum of target_amount grouped by currency (from Exchange)
        $target_totals = Exchange::selectRaw('target_currency_id, SUM(target_amount) as total')
            ->groupBy('target_currency_id')
            ->with('targetCurrency:id,code')
            ->get()
            ->mapWithKeys(fn($row) => [$row->targetCurrency->code => (float) $row->total]);

        // Card 4: Sum of target_amount grouped by currency (from ExchangePurchase)
        $purchased_totals = ExchangePurchase::selectRaw('target_currency_id, SUM(target_amount) as total')
            ->groupBy('target_currency_id')
            ->with('targetCurrency:id,code')
            ->get()
            ->mapWithKeys(fn($row) => [$row->targetCurrency->code => (float) $row->total]);

        // Card 5: Final balances per currency from all accounts (no filter)
        $balances = DB::table('transactions')
            ->select('currency_id', DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as total'))
            ->groupBy('currency_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $code = \App\Models\Currency::find($row->currency_id)?->code ?? 'N/A';
                return [$code => (float) $row->total];
            });

        return response()->json([
            'total_exchanges' => $total_exchanges,
            'base_totals' => $base_totals,
            'target_totals' => $target_totals,
            'purchased_totals' => $purchased_totals,
            'balances' => $balances,
        ]);
    }



    public function show($id)
    {
        $exchange = Exchange::with(['customerAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
        return response()->json($exchange);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $exchange = Exchange::findOrFail($id);

            // Check if related transactions exist
            $hasTransactions = Transaction::where('table_name', 'exchanges')
                ->where('table_row_id', $exchange->id)
                ->exists();

            if ($hasTransactions) {
                $transactions = Transaction::where('table_name', 'exchanges')
                    ->where('table_row_id', $exchange->id)
                    ->get();

                foreach ($transactions as $transaction) {
                    $transaction->delete(); // This will now trigger the observer
                }
            }

            $exchange->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $hasTransactions
                    ? 'Exchange and related transactions deleted successfully.'
                    : 'Exchange deleted successfully. No related transactions found.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Exchange Delete Failed', [
                'exchange_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exchange and its transactions.'
            ], 500);
        }
    }

    public function sendWhatsApp($id)
    {
        $exchange = Exchange::with(['customerAccount', 'officeAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
        $account = $exchange->customerAccount;
        $setting = Setting::first();

        $currency = $exchange->baseCurrency;
        $targetCurrency = $exchange->targetCurrency;
        $amount = number_format($exchange->base_amount, 2);
        $targetAmount = number_format($exchange->target_amount, 2);

        // ✅ Get all balances for this account
        $balances = AccountBalance::with('currency')
            ->where('account_id', $account->id)
            ->get();

        // ✅ Format balance summary across all currencies
        $balanceLines = $balances->map(function ($b) {
            $symbol = $b->currency->symbol ?? $b->currency->code;
            $balance = (float) str_replace(',', '', $b->balance); // Ensure numeric
            $formatted = number_format(abs($balance), 2);
            $label = $balance > 0 ? '🟢 Cr' : ($balance < 0 ? '🔴 Dr' : '⚪');
            return "{$label}: *{$formatted} {$symbol}*";
        })->implode("\n");

        // ✅ Build the WhatsApp message
        $message = "*" . $setting->company_name . "*\n\n"
            . "🔄 *Exchange*\n"
            . "👤 *Customer:* {$account->name}\n"
            . "🔖 *Code:* {$account->code}\n"
            . "💵 *Base:* {$amount} {$currency->symbol}\n"
            . "💱 *Target:* {$targetAmount} {$targetCurrency->symbol}\n"
            . "💬 *Rate:* " . number_format($exchange->rate, 2) . "\n"
            . "📅 *Date:* " . $exchange->created_at->format('Y-m-d H:i') . "\n\n"
            . "📊 *Balances:*\n{$balanceLines}\n\n"
            . "🙏 Thank you for choosing us!\n"
            . "📧 {$setting->email}\n"
            . "📞 {$setting->contact}\n\n"
            . "📌 مشتری گرامی، لطفاً از صحت بودن بیلانس حساب خود اطمینان حاصل نموده تایید نمایید.";

        // ✅ Send if valid contact
        if ($account->contact && (Str::startsWith($account->contact, '+') || Str::startsWith($account->contact, '00'))) {
            WhatsAppHelper::sendMessage($account->contact, $message);
            return response()->json(['success' => true, 'message' => 'Message sent successfully.']);
        }

        return response()->json(['error' => 'Invalid phone number format.'], 422);
    }
}
