<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\WhatsAppHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Currency;
use App\Models\Exchange;
use App\Models\ExchangePurchase;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExchangePurchaseController extends Controller
{
    public function index()
    {
        $accounts = Account::whereIn('account_sub_category_id', [2, 3])->get();
        $officeAccounts = Account::where('account_sub_category_id', 4)->get();
        $currencies = Currency::all();
        return view('admin.exchange-purchase.index', compact('accounts', 'officeAccounts', 'currencies'));
    }

    public function data(Request $request)
    {
        $query = ExchangePurchase::with(['customerAccount', 'baseCurrency', 'targetCurrency']);
        if ($request->filled('code')) {
            $query->whereHas('customerAccount', function($q) use ($request) {
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
            ->addColumn('rate', fn($row) => number_format($row->rate, 3))
            ->addColumn('received', fn($row) => $row->target_amount)
            ->addColumn('created_at', fn($row) => $row->created_at->format('Y-m-d H:i'))
            ->addColumn('actions', function ($row) {
                $buttons = '';
                if ($row->status === 'pending') {
                    $buttons .= '
                    <button class="btn btn-sm btn-success btn-process" data-id="' . $row->id . '">
                        <i class="bi bi-check-circle"></i>
                    </button>';
                }

                if ($row->status === 'processed') {
                    $buttons .= '
                    <button class="btn btn-sm btn-success btn-whatsapp me-1" data-id="' . $row->id . '" title="Send WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </button>';
                }

                $buttons .= '
                    <button class="btn btn-sm btn-dark btn-sm me-1 btn-preview-purchase" data-id="' . $row->id . '" title="Print Receipt">
                        <i class="bi bi-printer"></i>
                    </button>
                    <button class="btn btn-sm btn-primary btn-edit me-1" data-id="' . $row->id . '">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete me-1" data-id="' . $row->id . '">
                        <i class="bi bi-trash"></i>
                    </button>';

                return $buttons;
            })

            ->rawColumns(['actions'])
            ->make(true);
    }

    public function showReceipt($id)
    {
        $purchase = ExchangePurchase::with(['customerAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
        return view('admin.exchange-purchase.receipt', compact('purchase'));
    }


    // Controller
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
    //     $details = ExchangePurchase::selectRaw('
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
    //         'safe' => $safe,
    //     ]);

    // }

    public function totals(Request $request)
{
    // 1. Total Exchanges
    $total_exchanges = ExchangePurchase::count();

    // 2. Sum of base_amount grouped by base_currency
    $total_base = ExchangePurchase::selectRaw('base_currency_id, SUM(base_amount) as total')
        ->groupBy('base_currency_id')
        ->with('baseCurrency:id,code')
        ->get()
        ->mapWithKeys(fn($row) => [$row->baseCurrency->code => (float) $row->total]);
    Log::info($total_base);

    // 3. Sum of target_amount grouped by target_currency
    $total_target = ExchangePurchase::selectRaw('target_currency_id, SUM(target_amount) as total')
        ->groupBy('target_currency_id')
        ->with('targetCurrency:id,code')
        ->get()
        ->mapWithKeys(fn($row) => [$row->targetCurrency->code => (float) $row->total]);

    // 4. Current balance for each currency (credit - debit from all transactions)
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
        'total_base' => $total_base,
        'total_target' => $total_target,
        'balances' => $balances,
    ]);
}


    public function process($id)
    {
        try {
            DB::beginTransaction();

            $exchange = ExchangePurchase::findOrFail($id);

            if ($exchange->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Exchange already processed.'], 400);
            }

            $exchange->status = 'processed';
            $exchange->is_cash = 1;
            $exchange->is_withdrawn = 1;
            $exchange->save();

            $transactions = [];

            if ($exchange->is_cash) {
                $transactions[] = ['account_id' => $exchange->customer_account_id, 'currency_id' => $exchange->target_currency_id, 'amount' => $exchange->target_amount, 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => true, 'is_visible' => false];
                $transactions[] = ['account_id' => $exchange->customer_account_id, 'currency_id' => $exchange->target_currency_id, 'amount' => $exchange->target_amount, 'note' => $exchange->note, 'type' => 'debit', 'is_cash' => true, 'is_visible' => false];
                $transactions[] = ['account_id' => $exchange->office_account_id, 'currency_id' => $exchange->target_currency_id, 'amount' => $exchange->target_amount, 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => true, 'is_visible' => true];
            }


            foreach ($transactions as $tx) {
                $this->recordTransaction($exchange, $tx['account_id'], $tx['currency_id'], $tx['amount'], $tx['note'], $tx['type'], $tx['is_cash'], $tx['is_visible']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Exchange marked as processed.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exchange Process Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'exchange_id' => 'nullable|exists:exchange_purchases,id',
                'customer_account_id' => 'required|exists:accounts,id',
                'office_account_id' => 'required|exists:accounts,id',
                'base_currency_id' => 'required|exists:currencies,id',
                'target_currency_id' => 'required|exists:currencies,id|different:base_currency_id',
                'base_amount' => 'required|numeric|min:0.01',
                'rate' => 'required|numeric|min:0',
                'target_amount' => 'required|numeric|min:0',
                'note' => 'nullable|string',
                'is_cash' => 'required|boolean',
            ]);

            DB::beginTransaction();

            $exchange = null;
            if (is_null($validated['note'])) {
                $baseCurrency = Currency::findOrFail($validated['base_currency_id'])->code;
                $targetCurrency = Currency::findOrFail($validated['target_currency_id'])->code;
                $validated['note'] = sprintf('Exchange from %s at rate %s to %s', $baseCurrency, $validated['rate'], $targetCurrency);
            }

            if (!empty($validated['exchange_id'])) {
                // Update existing
                $exchange = ExchangePurchase::findOrFail($validated['exchange_id']);

                // Delete old transactions
                Transaction::where('table_name', 'exchange-purchase')
                    ->where('table_row_id', $exchange->id)
                    ->delete();

                // Update values
                $exchange->update([
                    'customer_account_id' => $validated['customer_account_id'],
                    'office_account_id'   => $validated['office_account_id'],
                    'base_currency_id'    => $validated['base_currency_id'],
                    'target_currency_id'  => $validated['target_currency_id'],
                    'base_amount'         => $validated['base_amount'],
                    'rate'                => $validated['rate'],
                    'cost_rate'           => $validated['rate'],
                    'target_amount'       => $validated['target_amount'],
                    'note'                => $validated['note'] ?? null,
                    'is_cash'             => $validated['is_cash'],
                    'status'              => $validated['is_cash'] ? 'processed' : 'pending',
                    'is_withdrawn'        => $validated['is_cash'],
                ]);
            } else {
                // Create new
                $exchange = ExchangePurchase::create([
                    'customer_account_id' => $validated['customer_account_id'],
                    'base_currency_id'    => $validated['base_currency_id'],
                    'target_currency_id'  => $validated['target_currency_id'],
                    'base_amount'         => $validated['base_amount'],
                    'rate'                => $validated['rate'],
                    'target_amount'       => $validated['target_amount'],
                    'cost_rate'           => $validated['rate'],
                    'target_profit'       => 0,
                    'note'                => $validated['note'] ?? null,
                    'office_account_id'   => $validated['office_account_id'],
                    'is_cash'             => $validated['is_cash'],
                    'status'              => $validated['is_cash'] ? 'processed' : 'pending',
                    'destination'         => 'account',
                    'is_withdrawn'        => $validated['is_cash'],
                ]);
            }

            // Define and record transactions
            $transactions = [];

            if ($exchange->is_cash) {
                $transactions[] = ['account_id' => $exchange->office_account_id, 'currency_id' => $validated['base_currency_id'], 'amount' => $validated['base_amount'], 'note' => $exchange->note, 'type' => 'debit', 'is_cash' => true, 'is_visible' => true];
                $transactions[] = ['account_id' => $exchange->customer_account_id, 'currency_id' => $validated['base_currency_id'], 'amount' => $validated['base_amount'], 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => true, 'is_visible' => true];

                $transactions[] = ['account_id' => $exchange->customer_account_id, 'currency_id' => $exchange->target_currency_id, 'amount' => $exchange->target_amount, 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => true, 'is_visible' => false];
                $transactions[] = ['account_id' => $exchange->customer_account_id, 'currency_id' => $exchange->target_currency_id, 'amount' => $exchange->target_amount, 'note' => $exchange->note, 'type' => 'debit', 'is_cash' => true, 'is_visible' => false];

                $transactions[] = ['account_id' => $exchange->office_account_id, 'currency_id' => $validated['target_currency_id'], 'amount' => $validated['target_amount'], 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => true, 'is_visible' => true];



            } else {
                $transactions[] = ['account_id' => $exchange->office_account_id, 'currency_id' => $validated['base_currency_id'], 'amount' => $validated['base_amount'], 'note' => $exchange->note, 'type' => 'debit', 'is_cash' => false, 'is_visible' => true];
                $transactions[] = ['account_id' => $validated['customer_account_id'], 'currency_id' => $validated['base_currency_id'], 'amount' => $validated['base_amount'], 'note' => $exchange->note, 'type' => 'credit', 'is_cash' => false, 'is_visible' => true];
            }

            foreach ($transactions as $tx) {
                $this->recordTransaction($exchange, $tx['account_id'], $tx['currency_id'], $tx['amount'], $tx['note'], $tx['type'], $tx['is_cash'], $tx['is_visible']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Exchange saved successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exchange Save Failed', [
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
            'table_name' => 'exchange-purchase',
            'table_row_id' => $exchange->id,
            'type' => 'exchange-purchase',
            'account_id' => $accountId,
            'currency_id' => $currencyId,
            'amount' => $amount,
            'transaction_type' => $type,
            'is_cash' => $isCash,
            'is_visible' => $isVisible,
            'status' => 'active',
            'note' => $note,
            'created_by' => Auth::user()->id,
        ]);
    }

    public function show($id)
    {
        $exchange = ExchangePurchase::with(['customerAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
        return response()->json($exchange);
    }

    public function destroy($id)
    {
        try {
            $exchange = ExchangePurchase::findOrFail($id);

            $transactions = Transaction::where('table_name', 'exchange-purchase')
                ->where('table_row_id', $exchange->id)
                ->get();

            foreach ($transactions as $transaction) {
                $transaction->delete(); // this ensures model events are triggered
            }

            $exchange->delete();

            return response()->json([
                'success' => true,
                'message' => 'Exchange and related transactions deleted successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Exchange Delete Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exchange.'
            ], 500);
        }
    }


    public function sendWhatsApp($id)
    {
        $exchange = ExchangePurchase::with(['customerAccount', 'officeAccount', 'baseCurrency', 'targetCurrency'])->findOrFail($id);
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
