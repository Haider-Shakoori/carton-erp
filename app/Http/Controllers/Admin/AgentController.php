<?php
// app/Http/Controllers/Admin/AgentController.php

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
use Yajra\DataTables\Facades\DataTables;

class AgentController extends Controller
{
    /**
     * Display a listing of agents.
     */
    public function index(Request $request)
    {
        $query = Account::where('account_type', 'agent')->where('is_active', true);

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
            $agentIds = Transaction::where('currency_id', $currencyId)
                ->where('status', 'active')
                ->whereHas('account', function($q) {
                    $q->where('account_type', 'agent')->where('is_active', true);
                })
                ->distinct()
                ->pluck('account_id');
            
            if ($agentIds->isNotEmpty()) {
                $query->whereIn('id', $agentIds);
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

        $agents = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = $this->calculateStats($request->currency_id);

        $currencies = Currency::where('is_active', true)->get();

        return view('admin.agents.index', compact('agents', 'stats', 'currencies'));
    }

    /**
     * Calculate statistics for agents.
     */
    private function calculateStats($currencyId = null)
    {
        $agentIds = Account::where('account_type', 'agent')->where('is_active', true)->pluck('id');

        if ($agentIds->isEmpty()) {
            return [
                'total_agents' => 0,
                'total_credit' => 0,
                'total_debit' => 0,
                'balance' => 0,
            ];
        }

        $query = Transaction::whereIn('account_id', $agentIds)->where('status', 'active');

        if ($currencyId) {
            $query->where('currency_id', $currencyId);
        }

        $credit = (clone $query)->where('transaction_type', 'credit')->sum('amount');
        $debit = (clone $query)->where('transaction_type', 'debit')->sum('amount');

        return [
            'total_agents' => $agentIds->count(),
            'total_credit' => $credit,
            'total_debit' => $debit,
            'balance' => $credit - $debit,
        ];
    }

    /**
     * Generate a unique agent code with AGT- prefix.
     */
    public function generateCode()
    {
        $prefix = 'AGT-';
        $lastAgent = Account::where('account_type', 'agent')
            ->where('code', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAgent) {
            $lastNumber = intval(substr($lastAgent->code, strlen($prefix)));
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
     * Check if agent code already exists.
     */
    public function checkCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->code;
        
        // Ensure code has AGT- prefix
        if (!str_starts_with($code, 'AGT-')) {
            $code = 'AGT-' . $code;
        }
        
        $exists = Account::where('code', $code)->exists();
        
        return response()->json([
            'available' => !$exists,
            'code' => $code
        ]);
    }

    /**
     * Store a newly created agent.
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
            // Ensure code has AGT- prefix
            $code = $request->code;
            if (!str_starts_with($code, 'AGT-')) {
                $code = 'AGT-' . $code;
            }

            $account = Account::create([
                'name' => $request->name,
                'code' => $code,
                'account_type' => 'agent',
                'contact' => $request->contact,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
                'company' => $request->company,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => true,
                'bi_icon' => 'bi-person-badge',
                'bi_icon_color' => 'warning',
                'is_safe' => false,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Agent created successfully!',
                'agent' => $account
            ]);
        } catch (Exception $e) {
            Log::error('Agent creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating agent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified agent.
     */
    public function show($id)
    {
        $agent = Account::findOrFail($id);
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
            ->where('account_id', $agent->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.agents.show', compact('agent', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Edit agent.
     */
    public function edit($id)
    {
        $agent = Account::findOrFail($id);
        return response()->json($agent);
    }

    /**
     * Update agent.
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

        $agent = Account::findOrFail($id);
        
        // Ensure code has AGT- prefix
        $code = $request->code;
        if (!str_starts_with($code, 'AGT-')) {
            $code = 'AGT-' . $code;
        }

        $agent->update([
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
            'message' => 'Agent updated successfully!'
        ]);
    }

    /**
     * Delete agent.
     */
    public function destroy($id)
    {
        try {
            $agent = Account::findOrFail($id);
            Transaction::where('account_id', $agent->id)->delete();
            $agent->delete();

            return response()->json([
                'success' => true,
                'message' => 'Agent deleted successfully!'
            ]);
        } catch (Exception $e) {
            Log::error('Agent deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete agent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print agent statement.
     */
    public function print($id)
    {
        $agent = Account::findOrFail($id);
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
            ->where('account_id', $agent->id)
            ->where('transactions.status', 'active')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->get();

        return view('admin.agents.print', compact('agent', 'currencies', 'summariesByCurrency'));
    }

    /**
     * Show account details for API.
     */
    public function showAccount($id)
    {
        $agent = Account::findOrFail($id);

        $balances = DB::table('transactions')
            ->select(
                'currency_id',
                DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance')
            )
            ->where('account_id', $agent->id)
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
            'id' => $agent->id,
            'name' => $agent->name,
            'code' => $agent->code,
            'balances' => $currencyBalances,
        ]);
    }

    /**
     * Send WhatsApp message to agent.
     */
    public function sendWhatsApp($id)
    {
        $agent = Account::findOrFail($id);
        
        if (!$agent->whatsapp) {
            return response()->json([
                'error' => 'Agent does not have a WhatsApp number.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp message sent successfully!'
        ]);
    }

    /**
     * Fetch agents for server-side DataTable.
     */
    public function fetchAgents(Request $request)
    {
        $accounts = Account::query()
            ->where('account_type', 'agent')
            ->where('is_active', true);

        if ($request->filled('account_sub_category_id')) {
            $accounts->where('account_sub_category_id', $request->account_sub_category_id);
        }

        if ($request->filled('search_term')) {
            $search = $request->search_term;
            $accounts->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $currencyId = $request->integer('currency_id') ?: null;

        $net = DB::table('transactions')
            ->select('account_id', DB::raw('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) as net_balance'))
            ->where('status', 'active')
            ->groupBy('account_id');

        $accounts->leftJoinSub($net, 'bal', function ($join) {
            $join->on('bal.account_id', '=', 'accounts.id');
        });
        $accounts->addSelect('accounts.*', DB::raw('COALESCE(bal.net_balance, 0) as _net_balance'));

        $balanceFilter = $request->input('balance_filter');
        if (in_array($balanceFilter, ['positive', 'negative', 'zero'], true)) {
            if ($balanceFilter === 'positive') {
                $accounts->whereRaw('COALESCE(bal.net_balance, 0) >= 0');
            } elseif ($balanceFilter === 'negative') {
                $accounts->whereRaw('COALESCE(bal.net_balance, 0) < 0');
            } else {
                $accounts->whereRaw('COALESCE(bal.net_balance, 0) = 0');
            }
        }

        if ($currencyId) {
            $agentIds = Transaction::where('currency_id', $currencyId)
                ->where('status', 'active')
                ->distinct()
                ->pluck('account_id');

            if ($agentIds->isNotEmpty()) {
                $accounts->whereIn('accounts.id', $agentIds);
            } else {
                $accounts->whereRaw('1 = 0');
            }
        }

        return DataTables::of($accounts)
            ->addColumn('name_with_profile', function ($account) {
                $bgColor = $account->profile_bg ?? '#6c757d';
                $accountUrl = route('admin.agents.show', $account->id);
                $code = e($account->code);
                $shortCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $account->code), 0, 3));

                $html = '
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="
                        width:44px;height:44px;border-radius:50%;
                        background:linear-gradient(145deg, ' . $bgColor . ', #374151);
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

                if ($account->company) {
                    $html .= '<div style="font-size:12px;color:#6c757d;">
                        <i class="bi bi-building me-1"></i> ' . e($account->company) . '</div>';
                }

                $html .= '</div></div>';
                return $html;
            })
            ->addColumn('account_type_with_icon', function ($account) {
                $badge = $account->is_active
                    ? 'bg-success-subtle text-success border'
                    : 'bg-secondary-subtle text-secondary border';
                return '<span class="badge ' . $badge . '"><i class="bi bi-person-badge me-1"></i>Agent</span>';
            })
            ->addColumn('contact_details', function ($account) {
                return "<div style='font-size:12px;'>"
                    . "<b>Contact:</b> " . e($account->contact ?? 'N/A') . "<br>"
                    . "<b>Address:</b> " . e($account->address ?? 'N/A') . "<br>"
                    . "<b>Email:</b> " . e($account->email ?? 'N/A') . "<br>"
                    . "<b>WhatsApp:</b> " . e($account->whatsapp ?? 'N/A')
                    . "</div>";
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

                if ($balances->isEmpty()) {
                    return '<b style="font-size:0.9rem;color:#6c757d;">0.00</b>';
                }

                $html = '';
                foreach ($balances as $b) {
                    $currency = Currency::find($b->currency_id);
                    $flag = $currency ? "<img src='/assets/flags/" . strtolower(substr($currency->code, 0, 2)) . ".svg' height='16' width='20' />" : '';
                    $color = $b->balance < 0 ? '#b23434' : '#2b2bc7';
                    $html .= "<b style='font-size:0.9rem;color:{$color}'>{$flag} " . number_format(abs($b->balance), 2) . "</b><br>";
                }
                return $html;
            })
            ->addColumn('actions', function ($account) {
                $actions = '<a href="' . route('admin.agents.show', $account->id) . '"
                    class="btn btn-sm btn-outline-primary" title="View Agent">
                    <i class="bi bi-eye"></i></a>';

                $actions .= '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-agent"
                    data-id="' . $account->id . '" title="Edit">
                    <i class="bi bi-pencil"></i></button>';

                $actions .= '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-agent"
                    data-id="' . $account->id . '" data-name="' . e($account->name) . '" title="Delete">
                    <i class="bi bi-trash"></i></button>';

                if ($account->whatsapp) {
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-success btn-send-whatsapp"
                        data-url="' . route('admin.agents.send-whatsapp', $account->id) . '" title="Send WhatsApp">
                        <i class="bi bi-whatsapp"></i></button>';
                }

                return $actions;
            })
            ->rawColumns(['name_with_profile', 'account_type_with_icon', 'contact_details', 'balance', 'actions'])
            ->make(true);
    }

    /**
     * Update statistics for agent accounts.
     */
    public function updateStats(Request $request)
    {
        $currencyId = $request->input('currency_id');

        $agentIds = Account::where('account_type', 'agent')->where('is_active', true)->pluck('id');

        $transactions = Transaction::whereIn('account_id', $agentIds);

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
            'total_accounts' => $agentIds->count(),
            'total_credit' => number_format($credit, 2),
            'total_debit' => number_format($debit, 2),
            'balance' => number_format($balance, 2),
            'currency' => $currency,
        ]);
    }
}
