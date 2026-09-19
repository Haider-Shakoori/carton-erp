<?php
// app/Http/Controllers/Admin/AccountsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Spatie\Browsershot\Browsershot;

class AccountsController extends Controller
{
    /**
     * Display a listing of customer accounts.
     */
    public function index()
    {
        $accountCategories = AccountCategory::all();
        $currencies = Currency::where('is_active', 1)->get();
        return view('admin.accounts.index', compact('accountCategories', 'currencies'));
    }

    /**
     * Export PDF for customer account.
     */
    public function exportPdf($id)
    {
        $account = Account::with(['transactions.currency'])->findOrFail($id);
        $transactions = $account->transactions->sortBy('created_at');

        $summariesByCurrency = $account->transactions
            ->groupBy('currency.code')
            ->map(function ($group) {
                return [
                    'credit' => $group->where('transaction_type', 'credit')->sum('amount'),
                    'debit'  => $group->where('transaction_type', 'debit')->sum('amount'),
                ];
            });

        $setting = Setting::first();

        $pdf = Pdf::loadView('admin.accounts.exports.account_statement', compact(
            'account',
            'summariesByCurrency',
            'transactions',
            'setting'
        ))->setPaper('A4', 'portrait');

        return $pdf->download("Statement_{$account->code}.pdf");
    }

    /**
     * Export view for customer account.
     */
    public function exportView(Account $account)
    {
        $setting = Setting::first();
        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->where('account_id', $account->id)
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => $item->credit,
                    'debit' => $item->debit
                ]];
            });

        return view('admin.accounts.export', compact('account', 'setting', 'summariesByCurrency'));
    }

    /**
     * Store a newly created customer account.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:accounts,code',
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $account = new Account();
            $account->name = $request->name;
            $account->account_sub_category_id = 1; // Customer sub-category
            $account->code = $request->code ?? $this->generateCustomerCode();
            $account->contact = $request->contact;
            $account->address = $request->address;
            $account->company = $request->company;
            $account->email = $request->email;
            $account->is_safe = false;
            $account->bi_icon = 'bi-people';
            $account->bi_icon_color = 'primary';
            $account->is_active = true;
            $account->created_by = Auth::id();
            $account->save();

            return redirect()->back()->with('success', 'Customer created successfully!');
        } catch (Exception $e) {
            Log::error('Customer creation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error creating customer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified customer account.
     */
    public function show($id)
    {
        $account = Account::with('subCategory')->findOrFail($id);
        $currencies = Currency::all();

        $summariesByCurrency = DB::table('transactions')
            ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->select(
                'currencies.code as currency',
                DB::raw("SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credit"),
                DB::raw("SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debit")
            )
            ->where('account_id', $account->id)
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->currency => [
                    'credit' => $item->credit,
                    'debit' => $item->debit
                ]];
            });

        return view('admin.accounts.show', compact('account', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Print customer account preview.
     */
    public function print(Account $account)
    {
        $summariesByCurrency = AccountBalance::where('account_id', $account->id)
            ->with('currency')
            ->get()
            ->groupBy('currency.code')
            ->map(function ($rows, $currencyCode) {
                $credit = $rows->sum('credit');
                $debit = $rows->sum('debit');

                return [
                    'credit' => $credit,
                    'debit' => $debit,
                    'currency' => $currencyCode,
                ];
            });

        return view('admin.accounts.print-preview', compact('account', 'summariesByCurrency'));
    }

    /**
     * Get account balances for API.
     */
    public function showAccount($id)
    {
        $account = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select('currency_id')
            ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            ->where('account_id', $account->id)
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];

        foreach ($balances as $item) {
            $currency = Currency::find($item->currency_id);
            $flag = $currency ? strtolower(substr($currency->code, 0, 2)) : 'xx';

            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'amount' => number_format($item->net_balance, 2),
                'flag' => "/assets/flags/{$flag}.svg",
            ];
        }

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'balances' => $currencyBalances,
        ]);
    }

    /**
     * Get balances by account.
     */
    public function getBalancesByAccount($id)
    {
        $account = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select('currency_id')
            ->selectRaw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            ->where('account_id', $account->id)
            ->groupBy('currency_id')
            ->get();

        $currencyBalances = [];

        foreach ($balances as $item) {
            $currency = Currency::find($item->currency_id);
            $flag = $currency ? strtolower(substr($currency->code, 0, 2)) : 'xx';

            $currencyBalances[] = [
                'currency' => $currency->code ?? 'N/A',
                'amount' => number_format($item->net_balance, 2),
                'flag' => "/assets/flags/{$flag}.svg",
            ];
        }

        return response()->json([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'balances' => $currencyBalances,
        ]);
    }

    /**
     * Get currency balance for an account.
     */
    public function getCurrencyBalance($accountId, $currencyId)
    {
        $credit = Transaction::where('account_id', $accountId)
            ->where('currency_id', $currencyId)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $debit = Transaction::where('account_id', $accountId)
            ->where('currency_id', $currencyId)
            ->where('transaction_type', 'debit')
            ->sum('amount');

        $balance = $credit - $debit;
        $currency = Currency::find($currencyId);

        return response()->json([
            'amount' => number_format($balance, 2),
            'raw_amount' => $balance,
            'currency' => $currency?->code ?? 'N/A',
            'symbol' => $currency?->symbol ?? '',
        ]);
    }

    /**
     * Get transaction data for DataTable.
     */
    public function transactionData($id, $type, Request $request)
    {
        $query = Transaction::with('currency')
            ->where('account_id', $id)
            ->where('account_type', $type)
            ->orderByDesc('created_at');

        if ($request->currency_id) {
            $query->where('currency_id', $request->currency_id);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('amount', fn($row) => number_format($row->amount, 2))
            ->addColumn('currency', fn($row) => $row->currency->code ?? '-')
            ->editColumn(
                'transaction_type',
                fn($row) =>
                $row->transaction_type === 'credit'
                    ? '<span class="badge bg-success">Credit</span>'
                    : '<span class="badge bg-danger">Debit</span>'
            )
            ->editColumn('created_at', fn($row) => $row->created_at->format('Y-m-d H:i'))
            ->rawColumns(['transaction_type'])
            ->make(true);
    }

    /**
     * Edit customer account.
     */
    public function edit(string $id)
    {
        $account = Account::findOrFail($id);
        return response()->json($account);
    }

    /**
     * Update customer account.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:accounts,code,' . $id,
            'contact' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $account = Account::findOrFail($id);
        $account->update($request->only(['name', 'code', 'contact', 'address', 'company', 'email']));

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    /**
     * Delete customer account.
     */
    public function destroy(string $id)
    {
        $account = Account::findOrFail($id);

        try {
            $hasTransactions = Transaction::where('account_id', $account->id)->exists();

            if ($hasTransactions) {
                Transaction::where('account_id', $account->id)->delete();
            }

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Customer and related transactions deleted successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Customer deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer or its transactions.'
            ], 500);
        }
    }

    /**
     * Send WhatsApp message to customer.
     */
    public function sendWhatsApp($id)
    {
        $account = Account::findOrFail($id);
        $setting = Setting::first();

        $phones = array_filter([
            $account->contact,
            '+93700209514',
        ], fn($p) => !empty($p));

        if (empty($phones)) {
            return response()->json(['error' => 'No valid phone numbers found.'], 422);
        }

        $balances = AccountBalance::where('account_id', $id)->with('currency')->get();

        $msg = "*{$setting->company_name}*\n\n";
        $msg .= "*Dear {$account->name} [{$account->code}],*\n";
        $msg .= "Here is the summary of your current account balances:\n\n";

        foreach ($balances as $b) {
            $amt    = number_format($b->balance, 2);
            $icon   = $b->balance > 0 ? '🟢' : ($b->balance < 0 ? '🔴' : '⚪');
            $status = $b->balance > 0 ? 'CREDIT' : ($b->balance < 0 ? 'DEBIT' : 'NEUTRAL');
            $msg   .= "{$icon} *{$amt}* {$b->currency->symbol} ({$status})\n";
        }

        $msg .= "\nWe appreciate your continued trust in our services.\n\n";
        $msg .= "📌 مشتری گرامی، لطفاً از صحت بودن بیلانس حساب خود اطمینان حاصل نموده تایید نمایید.";

        $now  = now()->timestamp;
        $last = DB::table('jobs')
            ->where('payload', 'like', '%SendWhatsAppMessage%')
            ->max('available_at');

        $next = $last && $last > $now ? $last : $now;
        $gap  = 20;

        foreach ($phones as $phone) {
            $delayTime = Carbon::createFromTimestamp($next);
            \Log::info("🕓 Queued WhatsApp to {$phone} at {$delayTime}");
            SendWhatsAppMessage::dispatch((string) $phone, (string) $msg)->delay($delayTime);
            $next += $gap;
        }

        return response()->json(['message' => 'Messages queued to multiple phones.']);
    }

    /**
     * Update stats for customer accounts.
     */
    public function updateStats(Request $request)
    {
        $currencyId = $request->input('currency_id');

        $accounts = Account::whereIn('account_sub_category_id', [1])->pluck('id');
        $transactions = Transaction::whereIn('account_id', $accounts);

        if ($currencyId) {
            $transactions = $transactions->where('currency_id', $currencyId);
            $currency = Currency::find($currencyId)?->name ?? '';
        } else {
            $currency = '';
        }

        $credit = (clone $transactions)->where('transaction_type', 'credit')->sum('amount');
        $debit = (clone $transactions)->where('transaction_type', 'debit')->sum('amount');
        $balance = $credit - $debit;

        return response()->json([
            'total_accounts' => $accounts->count(),
            'total_credit' => number_format($credit, 2),
            'total_debit' => number_format($debit, 2),
            'balance' => number_format($balance, 2),
            'currency' => $currency,
        ]);
    }

    /**
     * Get sub-categories.
     */
    public function getSubCategories($categoryId)
    {
        $subCategories = AccountSubCategory::where('account_category_id', $categoryId)->get();
        return response()->json($subCategories);
    }

    /**
     * Get category prefix.
     */
    public function getCategoryPrefix($id)
    {
        $category = AccountCategory::findOrFail($id);
        return response()->json(['prefix' => strtoupper($category->code_prefix)]);
    }

    /**
     * Check if code exists.
     */
    public function checkCode(Request $request)
    {
        $exists = Account::where('code', $request->code)->exists();
        return response()->json(['available' => !$exists]);
    }

    /**
     * Fetch customers for DataTable.
     */
    public function fetchAccounts(Request $request)
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->whereIn('account_sub_category_id', [1]); // Only customers

        // Search
        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $accounts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Filter by currency
        $currencyId = $request->integer('currency_id') ?: null;

        if ($currencyId) {
            $perCurrency = $this->perCurrencyNetSubquery($currencyId);
            $accounts->leftJoinSub($perCurrency, 'bal', function ($join) {
                $join->on('bal.account_id', '=', 'accounts.id');
            });
            $accounts->addSelect('accounts.*', DB::raw('COALESCE(bal.net_balance, 0) as _net_balance'));
        } else {
            $dom = $this->dominantBalanceSubquery();
            $accounts->leftJoinSub($dom, 'dom', function ($join) {
                $join->on('dom.account_id', '=', 'accounts.id');
            });
            $accounts->addSelect(
                'accounts.*',
                DB::raw('COALESCE(dom.dominant_balance, 0) as _dominant_balance'),
                DB::raw('COALESCE(dom.has_pos, 0) as _has_pos'),
                DB::raw('COALESCE(dom.has_neg, 0) as _has_neg')
            );
        }

        return DataTables::of($accounts)
            ->addColumn('name_with_profile', function ($account) {
                $bgColor = $account->profile_bg ?? '#6c757d';
                $accountUrl = route('admin.accounts.show', $account->id);
                $code = e($account->code);
                $shortCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $account->code), 0, 3));

                $html = '
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="
                        width:44px;height:44px;border-radius:50%;
                        background:linear-gradient(145deg, ' . $bgColor . ', ' . $this->colorMix($bgColor, '#000', 0.15) . ');
                        color:#fff;font-weight:600;display:flex;justify-content:center;align-items:center;
                        box-shadow:0 2px 6px rgba(0,0,0,0.1);font-size:15px;">
                        ' . $shortCode . '
                    </div>
                    <div style="line-height:1.4;">
                        <a href="' . $accountUrl . '"
                            style="font-weight:600;color:#212529;font-size:15px;text-decoration:none;">
                            ' . e($account->name) . '
                        </a>
                        <div style="font-size:12px;color:#6c757d;">' . $code . '</div>';

                if ($account->user) {
                    $html .= '
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1"
                                style="font-size:12px;">
                                <i class="bi bi-person-circle text-primary"></i> ' . e($account->user->username) . '
                            </span>
                        </div>';
                }

                $html .= '</div></div>';
                return $html;
            })
            ->addColumn('balance', function ($account) use ($currencyId) {
                $balances = DB::table('transactions')
                    ->select('currency_id', DB::raw("
                        SUM(CASE WHEN transaction_type='credit' THEN amount ELSE 0 END) -
                        SUM(CASE WHEN transaction_type='debit'  THEN amount ELSE 0 END) AS balance
                    "))
                    ->where('account_id', $account->id)
                    ->where('status', 'active')
                    ->groupBy('currency_id')
                    ->when($currencyId, function ($q) use ($currencyId) {
                        $q->havingRaw('currency_id = ?', [$currencyId]);
                    })
                    ->get();

                if ($balances->isEmpty()) return '0';

                $html = '';
                foreach ($balances as $b) {
                    $currency = Currency::find($b->currency_id);
                    $flag = $currency ? "<img src='/assets/flags/" . strtolower(substr($currency->code, 0, 2)) . ".svg' height='16' width='20' />" : '';
                    $color = $b->balance < 0 ? '#b23434' : '#2b2bc7';
                    $html .= "<b style='font-size:0.9rem;color:{$color}'>{$flag} " . number_format(abs($b->balance), 2) . "</b><br>";
                }
                return $html;
            })
            ->addColumn('contact_details', function ($account) {
                return "<div style='font-size:12px;'>"
                    . "<b>Contact:</b> " . e($account->contact) . "<br>"
                    . "<b>Address:</b> " . e($account->address) . "<br>"
                    . "<b>Company:</b> " . e($account->company) . "<br>"
                    . "<b>Email:</b> " . e($account->email ?? 'N/A') . "<br>"
                    . "<b>Created By:</b> " . (optional($account->creator)->name ?? 'N/A')
                    . "</div>";
            })
            ->addColumn('actions', function ($account) {
                $show_url = route('admin.accounts.show', $account->id);
                $whatsapp_url = route('admin.accounts.send-whatsapp', $account->id);

                $actions = '';

                $actions .= '<a href="' . $show_url . '" class="btn btn-sm btn-outline-primary" title="View Account">
                    <i class="bi bi-eye"></i>
                </a>';

                $actions .= '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-account" 
                    data-id="' . $account->id . '" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>';

                $actions .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-account" 
                    data-id="' . $account->id . '" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>';

                $actions .= '<button type="button" class="btn btn-sm btn-outline-success btn-send-whatsapp" 
                    data-url="' . $whatsapp_url . '" title="Send WhatsApp">
                    <i class="bi bi-whatsapp"></i>
                </button>';

                if (!$account->user_id) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-info btn-create-user"
                        data-id="' . $account->id . '" title="Create User">
                        <i class="bi bi-person-plus"></i>
                    </button>';
                }

                return $actions;
            })
            ->rawColumns(['name_with_profile', 'balance', 'contact_details', 'actions'])
            ->make(true);
    }

    /**
     * Create user for customer account.
     */
    public function createUserForAccount(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:6',
            'repeat_password' => 'required|same:password',
            'permissions' => 'array'
        ]);

        DB::beginTransaction();
        try {
            $user = new User();
            $user->name = $request->name;
            $user->username = $request->username;
            $user->account_type = 'client';
            $user->account_id = $request->account_id;
            $user->created_by = auth()->id();
            $user->password = Hash::make($request->password);
            $user->is_active = true;
            $user->save();

            $clientRole = Role::firstOrCreate(['name' => 'client']);
            $user->assignRole($clientRole);

            if ($request->filled('permissions')) {
                foreach ($request->permissions as $permName) {
                    $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permName]);
                    $user->givePermissionTo($permission);
                }
            }

            $account = Account::findOrFail($request->account_id);
            $account->user_id = $user->id;
            $account->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Client user created and linked successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('User creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create and link user.'
            ], 500);
        }
    }

    /**
     * Get account info.
     */
    public function getAccountInfo($id)
    {
        $account = Account::select('id', 'name', 'code')->findOrFail($id);
        return response()->json($account);
    }

    // ─── PRIVATE HELPERS ───

    private function generateCustomerCode()
    {
        $last = Account::where('account_sub_category_id', 1)->orderBy('id', 'desc')->first();
        $number = $last ? intval(substr($last->code, -4)) + 1 : 1;
        return 'CUS-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    private function colorMix($hex, $mix, $percent)
    {
        $hex = str_replace('#', '', $hex);
        $mix = str_replace('#', '', $mix);
        $r = (hexdec(substr($hex, 0, 2)) * (1 - $percent)) + (hexdec(substr($mix, 0, 2)) * $percent);
        $g = (hexdec(substr($hex, 2, 2)) * (1 - $percent)) + (hexdec(substr($mix, 2, 2)) * $percent);
        $b = (hexdec(substr($hex, 4, 2)) * (1 - $percent)) + (hexdec(substr($mix, 4, 2)) * $percent);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function perCurrencyNetSubquery(?int $currencyId = null)
    {
        $q = DB::table('transactions')
            ->select([
                'account_id',
                'currency_id',
                DB::raw("SUM(CASE WHEN transaction_type='credit' THEN amount ELSE -amount END) AS net_balance")
            ])
            ->where('status', 'active')
            ->groupBy('account_id', 'currency_id');

        if ($currencyId) {
            $q->where('currency_id', $currencyId);
        }
        return $q;
    }

    private function dominantBalanceSubquery()
    {
        $perCur = $this->perCurrencyNetSubquery(null);

        return DB::query()->fromSub($perCur, 't')
            ->selectRaw("
                account_id,
                MAX(CASE WHEN net_balance > 0 THEN net_balance ELSE 0 END) AS max_pos,
                MIN(CASE WHEN net_balance < 0 THEN net_balance ELSE 0 END) AS min_neg,
                CASE
                    WHEN ABS(COALESCE(MAX(CASE WHEN net_balance > 0 THEN net_balance END), 0))
                    >= ABS(COALESCE(MIN(CASE WHEN net_balance < 0 THEN net_balance END), 0))
                    THEN COALESCE(MAX(CASE WHEN net_balance > 0 THEN net_balance END), 0)
                    ELSE COALESCE(MIN(CASE WHEN net_balance < 0 THEN net_balance END), 0)
                END AS dominant_balance,
                CASE WHEN SUM(CASE WHEN net_balance > 0 THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END AS has_pos,
                CASE WHEN SUM(CASE WHEN net_balance < 0 THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END AS has_neg
            ")
            ->groupBy('account_id');
    }
}
