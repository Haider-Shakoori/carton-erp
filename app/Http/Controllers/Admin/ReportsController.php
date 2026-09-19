<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSubCategory;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\AccountBalance;
use App\Models\Exchange;
use App\Models\ExchangePurchase;
use App\Models\Remittance;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use PDF;


class ReportsController extends Controller
{
    public function customers(Request $request)
    {
        $subCategories = AccountSubCategory::all();
        $currencies = Currency::where('is_active', 1)->get();

        return view('admin.reports.customers', compact('subCategories', 'currencies'));
    }



    public function customersData(Request $request)
    {
        $baseQuery = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('sub_category_id')) {
            $baseQuery->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }
        if ($request->filled('customer_id')) {
            $baseQuery->where('account_id', $request->customer_id);
        }


        if ($request->filled('currency_id')) {
            $baseQuery->where('currency_id', $request->currency_id);
        }

        // Clone query for totals (no pagination)
        $totals = (clone $baseQuery)
            ->join('currencies', 'account_balances.currency_id', '=', 'currencies.id')
            ->selectRaw('currencies.code as currency_code, currencies.symbol, SUM(credit) as credit, SUM(debit) as debit, SUM(balance) as balance')
            ->groupBy('currencies.code')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->currency_code => [
                        'credit' => number_format($item->credit, 2),
                        'debit' => number_format($item->debit, 2),
                        'balance' => number_format($item->balance, 2),
                        'symbol' => $item->symbol
                    ]
                ];
            });

        $baseQuery->orderBy('created_at', 'desc');

        // Paginated DataTable
        return DataTables::of($baseQuery)
            ->addIndexColumn()
            ->addColumn('customer', fn($r) => $r->account->name . ' (' . $r->account->code . ')')
            ->addColumn('currency', fn($r) => $r->currency->code ?? '-')
            ->addColumn('currency_symbol', fn($r) => $r->currency->symbol ?? '-')
            ->addColumn('credit', fn($r) => number_format($r->credit, 2))
            ->addColumn('debit', fn($r) => number_format($r->debit, 2))
            ->addColumn('balance', fn($r) => number_format($r->balance, 2))
            ->with(['totals_by_currency' => $totals])
            ->make(true);
    }

    public function getCustomersBySubCategory(Request $request)
    {
        $query = Account::query()->where('is_active', true);

        if ($request->filled('sub_category_id')) {
            $query->where('account_sub_category_id', $request->sub_category_id);
        }

        return $query->select('id', 'name', 'code')->orderBy('name')->get();
    }


    public function customersPdf(Request $request)
    {
        $query = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) use ($request) {
                $q->where('is_active', true);

                if ($request->filled('sub_category_id')) {
                    $q->where('account_sub_category_id', $request->sub_category_id);
                }

                if ($request->filled('customer_id')) {
                    $q->where('id', $request->customer_id); // filtering account id here
                }
            });

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        $query->orderBy('created_at', 'desc');
        $data = $query->get()->groupBy('currency.code');

        return PDF::loadView('admin.reports.exports.customers_pdf', compact('data'))
            ->setPaper('a4', 'portrait')
            ->stream('customers_report.pdf');
    }



    // EXCHANGE
    public function exchange(Request $request)
    {
        $currencies = Currency::where('is_active', 1)->get();
        $customers = Account::where('is_active', true)->get();
        return view('admin.reports.exchange', compact('currencies', 'customers'));
    }

    public function exchangeData(Request $request)
    {
        $query = Exchange::with(['customerAccount', 'officeAccount', 'baseCurrency', 'targetCurrency'])
            ->whereHas('customerAccount', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('officeAccount', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('customer_account_id')) {
            $query->where('customer_account_id', $request->customer_account_id);
        }

        if ($request->filled('base_currency_id')) {
            $query->where('base_currency_id', $request->base_currency_id);
        }

        if ($request->filled('target_currency_id')) {
            $query->where('target_currency_id', $request->target_currency_id);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('exchanges.created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        $query->orderBy('created_at', 'desc');

        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer', fn($r) => $r->customerAccount->name . ' (' . $r->customerAccount->code . ')')
            ->addColumn('base_currency', fn($r) => $r->baseCurrency->code)
            ->addColumn('target_currency', fn($r) => $r->targetCurrency->code)
            ->addColumn('amount', fn($r) => number_format($r->base_amount, 2))
            ->addColumn('rate', fn($r) => $r->rate)
            ->addColumn('received', fn($r) => number_format($r->target_amount, 2))
            ->addColumn('profit', fn($r) => number_format($r->target_profit, 2))
            ->addColumn('created_at', fn($r) => $r->created_at->format('Y-m-d'));

        $totals = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->join('currencies as base_currencies', 'exchanges.base_currency_id', '=', 'base_currencies.id')
            ->join('currencies as target_currencies', 'exchanges.target_currency_id', '=', 'target_currencies.id')
            ->selectRaw('
                base_currencies.code as base_currency_code,
                base_currencies.symbol as base_currency_symbol,
                target_currencies.symbol as target_currency_symbol,
                SUM(exchanges.base_amount) as base_amount,
                SUM(exchanges.target_amount) as target_amount,
                SUM(exchanges.target_profit) as target_profit,
                MAX(exchanges.created_at) as latest_created_at
            ')
            ->groupBy(
                'base_currencies.id',
                'base_currencies.code',
                'base_currencies.symbol',
                'target_currencies.id',
                'target_currencies.symbol'
            )
            ->orderByDesc('latest_created_at')
            ->get()
            ->groupBy('base_currency_code')
            ->map(function ($items) {
            return [
                'amount' => number_format($items->sum('base_amount'), 2),
                'received' => number_format($items->sum('target_amount'), 2),
                'profit' => number_format($items->sum('target_profit'), 2),
                'symbol' => $items->first()->base_currency_symbol,
                'profit_symbol' => $items->first()->target_currency_symbol
            ];
        });

        return $dataTable->with(['totals_by_currency' => $totals])->make(true);
    }

    public function exchangePdf(Request $request)
    {
        $query = Exchange::with(['customerAccount', 'officeAccount', 'baseCurrency', 'targetCurrency'])
            ->whereHas('customerAccount', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('officeAccount', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('customer_account_id')) {
            $query->where('customer_account_id', $request->customer_account_id);
        }

        if ($request->filled('base_currency_id')) {
            $query->where('base_currency_id', $request->base_currency_id);
        }

        if ($request->filled('target_currency_id')) {
            $query->where('target_currency_id', $request->target_currency_id);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }
        $query->orderBy('created_at', 'desc');
        $data = $query->get()->groupBy('baseCurrency.code');

        return PDF::loadView('admin.reports.exports.exchange_pdf', compact('data'))
            ->setPaper('a4', 'portrait')
            ->stream('exchange_report.pdf');
    }


    // Remittance
    public function remittance(Request $request)
    {
        $customers = Account::where('account_sub_category_id', 1)
            ->where('is_active', true)
            ->get();
        $currencies = Currency::where('is_active', 1)->get();
        return view('admin.reports.remittance', compact('customers', 'currencies'));
    }

    public function remittanceData(Request $request)
    {
        $query = Remittance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('remittances.created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        $query->orderBy('created_at', 'desc');

        $dataTable = DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer', fn($r) => $r->account->name . ' (' . $r->account->code . ')')
            ->addColumn('currency', fn($r) => $r->currency->code)
            ->addColumn('amount', fn($r) => number_format($r->amount, 2))
            ->addColumn('status', fn($r) => ucfirst($r->status))
            ->addColumn('date', fn($r) => $r->created_at->format('Y-m-d'));

        // Totals grouped by currency
        $totals = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->join('currencies', 'remittances.currency_id', '=', 'currencies.id')
            ->selectRaw('
                currencies.code as currency_code,
                currencies.symbol,
                SUM(remittances.amount) as amount,
                COUNT(*) as aggregate_count,
                MAX(remittances.created_at) as latest_created_at
            ')
            ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
            ->orderByDesc('latest_created_at')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->currency_code => [
                        'amount' => number_format($item->amount, 2),
                        'count' => (int) $item->aggregate_count,
                        'symbol' => $item->symbol
                    ]
                ];
            });

        return $dataTable->with(['totals_by_currency' => $totals])->make(true);
    }

    public function remittancePdf(Request $request)
    {
        $query = Remittance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('is_active', true);
            });

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_range')) {
            [$start, $end] = explode(' - ', $request->date_range);
            $query->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        }

        $query->orderBy('created_at', 'desc');

        $data = $query->get()->groupBy('currency.code');

        return PDF::loadView('admin.reports.exports.remittance_pdf', compact('data'))
            ->setPaper('a4', 'portrait')
            ->stream('remittance_report.pdf');
    }

    // --- Controller Methods for Creditors Report ---

    public function creditors(Request $request)
    {
        $currencies = Currency::where('is_active', 1)->get();
        $subCategories = AccountSubCategory::all();
        return view('admin.reports.creditors', compact('currencies', 'subCategories'));
    }

    public function creditorsData(Request $request)
    {
        $query = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true);
            })
            ->where('balance', '>', 0);

        if ($request->filled('sub_category_id')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->currency_id);
        }

        $query->orderBy('balance', 'desc');

        $collection = $query->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => number_format($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        $data = [];
        foreach ($collection->groupBy('account_id') as $group) {
            $account = $group->first()->account;
            $balancesHtml = '';
            foreach ($group as $balance) {
                $balancesHtml .= $balance->currency->code . ': ' . number_format($balance->balance, 2) . '<br>';
            }
            $data[] = [
                'account' => $account->name . ' (' . $account->code . ')',
                'balances' => $balancesHtml
            ];
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->rawColumns(['balances'])
            ->with(['totals_by_currency' => $summary])
            ->make(true);
    }




    public function creditorsPdf(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true);
            })
            ->where('balance', '>', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $balances->orderBy('balance', 'desc');

        $collection = $balances->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => number_format($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        $data = $collection->groupBy('account_id')->map(function ($group) {
            $account = $group->first()->account;
            $currencies = $group->mapWithKeys(fn($b) => [$b->currency->code => number_format($b->balance, 2)]);
            return ['account' => $account, 'balances' => $currencies];
        });

        return PDF::loadView('admin.reports.exports.creditors_pdf', compact('data', 'summary'))
            ->setPaper('a4', 'portrait')
            ->stream('debtors_report.pdf');
    }

    // --- Controller Methods for Debtors Report ---

    public function debtors(Request $request)
    {
        $currencies = Currency::where('is_active', 1)->get();
        $subCategories = AccountSubCategory::all();
        return view('admin.reports.debtors', compact('currencies', 'subCategories'));
    }

    public function debtorsData(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true);
            })
            ->where('balance', '<', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $balances->orderBy('balance', 'asc');

        $collection = $balances->get();

        $grouped = $collection->groupBy('account_id');

        $formatted = $grouped->map(function ($group) {
            $account = $group->first()->account;
            $currencyBalances = $group->mapWithKeys(function ($item) {
                return [$item->currency->code => number_format($item->balance, 2)];
            });
            return [
                'account' => $account->name . ' (' . $account->code . ')',
                'balances' => $currencyBalances
            ];
        })->values();

        $totals = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => number_format($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        return DataTables::of($formatted)
            ->addIndexColumn()
            ->addColumn('account', fn($r) => $r['account'])
            ->addColumn('balances', function ($r) {
                return collect($r['balances'])->map(function ($val, $cur) {
                    return "<div><strong>{$cur}:</strong> {$val}</div>";
                })->implode('');
            })
            ->rawColumns(['balances'])
            ->with(['totals_by_currency' => $totals])
            ->make(true);
    }


    public function debtorsPdf(Request $request)
    {
        $balances = AccountBalance::with(['account', 'currency'])
            ->whereHas('account', function ($q) {
                $q->where('account_sub_category_id', '!=', 4)->where('is_active', true);
            })
            ->where('balance', '<', 0);

        if ($request->filled('sub_category_id')) {
            $balances->whereHas('account', function ($q) use ($request) {
                $q->where('account_sub_category_id', $request->sub_category_id);
            });
        }

        if ($request->filled('currency_id')) {
            $balances->where('currency_id', $request->currency_id);
        }

        $balances->orderBy('balance', 'asc');

        $collection = $balances->get();

        $summary = $collection->groupBy('currency.code')->map(function ($items) {
            return [
                'balance' => number_format($items->sum('balance'), 2),
                'count' => $items->count()
            ];
        });

        $data = $collection->groupBy('account_id')->map(function ($group) {
            $account = $group->first()->account;
            $currencies = $group->mapWithKeys(fn($b) => [$b->currency->code => number_format($b->balance, 2)]);
            return ['account' => $account, 'balances' => $currencies];
        });

        return PDF::loadView('admin.reports.exports.debtors_pdf', compact('data', 'summary'))
            ->setPaper('a4', 'portrait')
            ->stream('debtors_report.pdf');
    }
}
