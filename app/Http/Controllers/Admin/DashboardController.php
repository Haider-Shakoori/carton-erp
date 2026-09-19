<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected ?\Illuminate\Support\Collection $dashboardCurrencies = null;

    public function index()
    {
        $currencies = Currency::where('is_active', true)->get();
        $defaultCurrency = $currencies->firstWhere('is_default', true);

        if (!$defaultCurrency && $currencies->isNotEmpty()) {
            $defaultCurrency = $currencies->first();
        }

        return view('admin.dashboard.index', compact('currencies', 'defaultCurrency'));
    }

    public function getStats(Request $request)
    {
        try {
            $timeframe = $request->input('timeframe', 'month');
            $dateRange = $this->getDateRange($timeframe);

            // ─── GET DEFAULT CURRENCY ───
            $defaultCurrency = $this->getDashboardCurrencies()->firstWhere('is_default', true);
            if (!$defaultCurrency) {
                $defaultCurrency = $this->getDashboardCurrency('AFN');
            }
            $defaultCurrencyCode = $defaultCurrency ? $defaultCurrency->code : 'AFN';
            $defaultCurrencySymbol = $defaultCurrency ? $defaultCurrency->symbol : '؋';

            // ─── SALES REVENUE (Both Currencies) ───
            $salesStats = $this->getSalesStats($dateRange);

            // ─── COGS (USD Only) ───
            $cogsStats = $this->getCOGSStats($dateRange);

            // ─── GROSS PROFIT (Direct from Sale Items - Both Currencies) ───
            $grossProfit = $this->getGrossProfit($dateRange);

            // ─── EXPENSES (Both Currencies) ───
            $expenseStats = $this->getExpenseStats($dateRange);

            // ─── NET PROFIT (AFN) ───
            $netProfit = $this->getNetProfit($grossProfit, $expenseStats);

            // ─── INVENTORY (USD Only) ───
            $inventoryStats = $this->getInventoryStats();

            // ─── RECEIVABLES (Both Currencies) ───
            $receivableStats = $this->getReceivableStats();

            // ─── PAYABLES (Both Currencies) ───
            $payableStats = $this->getPayableStats();

            // ─── CURRENCY BALANCES ───
            $currencyBalances = $this->getCurrencyBalancesByAccountType();

            // ─── CUSTOMER & SUPPLIER STATS ───
            $customerStats = $this->getCustomerStats();
            $supplierStats = $this->getSupplierStats();
            $agentStats = $this->getAgentStats();
            $sarafStats = $this->getSarafStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'default_currency' => [
                        'code' => $defaultCurrencyCode,
                        'symbol' => $defaultCurrencySymbol,
                    ],
                    'sales' => $salesStats,
                    'cogs' => $cogsStats,
                    'gross_profit' => $grossProfit,
                    'expenses' => $expenseStats,
                    'net_profit' => $netProfit,
                    'inventory' => $inventoryStats,
                    'receivables' => $receivableStats,
                    'payables' => $payableStats,
                    'customers' => $customerStats,
                    'suppliers' => $supplierStats,
                    'agents' => $agentStats,
                    'saraf' => $sarafStats,
                    'currency_balances' => $currencyBalances,
                    'timeframe' => $timeframe,
                    'alerts' => $this->getAlertData(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard Stats Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── SALES REVENUE (Both Currencies) ───
    protected function getSalesStats($dateRange)
    {
        // Get USD Sales
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');

        $usdSales = SaleItem::whereHas('sale', function ($q) use ($dateRange, $usdCurrency) {
            $q->whereIn('status', ['confirmed', 'delivered'])
                ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->get();

        $afnSales = SaleItem::whereHas('sale', function ($q) use ($dateRange, $afnCurrency) {
            $q->whereIn('status', ['confirmed', 'delivered'])
                ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->get();

        $usdReturns = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange, $usdCurrency) {
            $q->where('status', 'processed')
                ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->get();

        $afnReturns = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange, $afnCurrency) {
            $q->where('status', 'processed')
                ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->get();

        $usdRevenue = $usdSales->sum('total') - $usdReturns->sum('total');
        $afnRevenue = $afnSales->sum('total') - $afnReturns->sum('total');

        // ─── PREVIOUS PERIOD ───
        $prevUsdSales = SaleItem::whereHas('sale', function ($q) use ($dateRange, $usdCurrency) {
            $q->whereIn('status', ['confirmed', 'delivered'])
                ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']]);
        })->get();

        $prevAfnSales = SaleItem::whereHas('sale', function ($q) use ($dateRange, $afnCurrency) {
            $q->whereIn('status', ['confirmed', 'delivered'])
                ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']]);
        })->get();

        $prevUsdReturns = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange, $usdCurrency) {
            $q->where('status', 'processed')
                ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']]);
        })->get();

        $prevAfnReturns = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange, $afnCurrency) {
            $q->where('status', 'processed')
                ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
                ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']]);
        })->get();

        $prevUsdRevenue = $prevUsdSales->sum('total') - $prevUsdReturns->sum('total');
        $prevAfnRevenue = $prevAfnSales->sum('total') - $prevAfnReturns->sum('total');

        $totalRevenue = $usdRevenue + $afnRevenue;
        $prevTotalRevenue = $prevUsdRevenue + $prevAfnRevenue;
        $growth = $prevTotalRevenue > 0 ? (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100 : 0;

        return [
            'usd' => (float) $usdRevenue,
            'afn' => (float) $afnRevenue,
            'total' => (float) $totalRevenue,
            'growth' => (float) $growth,
            'prev_total' => (float) $prevTotalRevenue,
        ];
    }

    // ─── COGS (USD) ───
    // Uses ACTUAL FIFO production cost for production sales that already have
    // material consumption (consistent with SaleProfitService), and falls back to
    // the stored quotation cost for all other sales. Sales returns keep the
    // stored quotation cost (processed returns have no realized production cost).
    protected function getCOGSStats($dateRange)
    {
        $cogsPeriod = function ($from, $to) {
            $sales = Sale::with('items')
                ->whereIn('status', ['confirmed', 'delivered'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'exchange_rate', 'production_order_id']);

            $realized = $this->getRealizedCostBySales($sales);

            $total = 0.0;
            foreach ($sales as $sale) {
                if (isset($realized[$sale->id])) {
                    $total += $realized[$sale->id]['cost_usd'];
                } else {
                    foreach ($sale->items as $item) {
                        $total += (float) $item->total_cost_usd;
                    }
                }
            }
            return $total;
        };

        $totalCOGS = $cogsPeriod($dateRange['start'], $dateRange['end']);
        $prevCOGS = $cogsPeriod($dateRange['previous_start'], $dateRange['previous_end']);

        $returnCOGS = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange) {
            $q->where('status', 'processed')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        })->sum('total_cost_usd');

        $prevReturnCOGS = SaleReturnItem::whereHas('saleReturn', function ($q) use ($dateRange) {
            $q->where('status', 'processed')
                ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']]);
        })->sum('total_cost_usd');

        $netCOGS = $totalCOGS - $returnCOGS;
        $prevNetCOGS = $prevCOGS - $prevReturnCOGS;
        $growth = $prevNetCOGS > 0 ? (($netCOGS - $prevNetCOGS) / $prevNetCOGS) * 100 : 0;

        return [
            'total' => (float) $netCOGS,
            'growth' => (float) $growth,
            'prev_total' => (float) $prevNetCOGS,
        ];
    }

    // ─── GROSS PROFIT (Both Currencies) ───
    // For production sales with actual FIFO material consumption, uses the
    // realized profit (sale revenue - actual production cost) consistent with
    // SaleProfitService. All other sales keep the stored sale-item profit
    // snapshot. Margin is computed against revenue in the default (AFN) currency.
    protected function getGrossProfit($dateRange)
    {
        $afnCurrency = $this->getDashboardCurrency('AFN');
        $afnCurrencyId = $afnCurrency ? (int) $afnCurrency->id : 0;
        $afnRate = $afnCurrency ? (float) ($afnCurrency->exchange_rate ?? 66) : 66;

        $profitPeriod = function ($from, $to) use ($afnCurrencyId, $afnRate) {
            $sales = Sale::with('items')
                ->whereIn('status', ['confirmed', 'delivered'])
                ->whereBetween('created_at', [$from, $to])
                ->get(['id', 'currency_id', 'grand_total', 'usd_grand_total', 'exchange_rate', 'production_order_id']);

            $realized = $this->getRealizedCostBySales($sales);

            $profitAfn = 0.0;
            $profitUsd = 0.0;
            $revenueAfn = 0.0;

            foreach ($sales as $sale) {
                $rate = max((float) ($sale->exchange_rate ?? 1), 0.000001);
                $saleRevAfn = (float) ($sale->grand_total ?? 0);
                $saleRevUsd = (float) ($sale->usd_grand_total ?? ($saleRevAfn / $rate));

                if (isset($realized[$sale->id])) {
                    $costAfn = $realized[$sale->id]['cost_afn'];
                    $costUsd = $realized[$sale->id]['cost_usd'];
                    $saleProfitAfn = round($saleRevAfn - $costAfn, 2);
                    $saleProfitUsd = round($saleRevUsd - $costUsd, 2);
                } else {
                    $saleProfitAfn = 0.0;
                    $saleProfitUsd = 0.0;
                    foreach ($sale->items as $item) {
                        $saleProfitAfn += (float) $item->profit_afn;
                        $saleProfitUsd += (float) $item->profit_usd;
                    }
                }

                $profitAfn += $saleProfitAfn;
                $profitUsd += $saleProfitUsd;
                $revenueAfn += ((int) $sale->currency_id === $afnCurrencyId)
                    ? $saleRevAfn
                    : $saleRevUsd * $afnRate;
            }

            return [$profitAfn, $profitUsd, $revenueAfn];
        };

        [$grossProfitAfn, $grossProfitUsd, $totalRevenueAfn] = $profitPeriod($dateRange['start'], $dateRange['end']);
        [$prevGrossProfitAfn, $prevGrossProfitUsd, $_] = $profitPeriod($dateRange['previous_start'], $dateRange['previous_end']);

        // ─── CALCULATE GROWTH ───
        $grossGrowth = $prevGrossProfitAfn > 0 ? (($grossProfitAfn - $prevGrossProfitAfn) / $prevGrossProfitAfn) * 100 : 0;

        // ─── CALCULATE MARGIN ───
        $grossMargin = $totalRevenueAfn > 0 ? ($grossProfitAfn / $totalRevenueAfn) * 100 : 0;

        return [
            'afn' => (float) $grossProfitAfn,
            'usd' => (float) $grossProfitUsd,
            'growth' => (float) $grossGrowth,
            'margin' => (float) $grossMargin,
            'prev_afn' => (float) $prevGrossProfitAfn,
            'prev_usd' => (float) $prevGrossProfitUsd,
        ];
    }
    // ─── EXPENSES (Both Currencies) ───
    protected function getExpenseStats($dateRange)
    {
        $expenseAccountIds = $this->getExpenseAccountIds();
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');

        $usdExpenses = Transaction::whereIn('account_id', $expenseAccountIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        $afnExpenses = Transaction::whereIn('account_id', $expenseAccountIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        // ─── PREVIOUS PERIOD ───
        $prevUsdExpenses = Transaction::whereIn('account_id', $expenseAccountIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
            ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']])
            ->sum('amount');

        $prevAfnExpenses = Transaction::whereIn('account_id', $expenseAccountIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
            ->whereBetween('created_at', [$dateRange['previous_start'], $dateRange['previous_end']])
            ->sum('amount');

        $totalExpenses = $usdExpenses + $afnExpenses;
        $prevTotalExpenses = $prevUsdExpenses + $prevAfnExpenses;
        $growth = $prevTotalExpenses > 0 ? (($totalExpenses - $prevTotalExpenses) / $prevTotalExpenses) * 100 : 0;

        return [
            'usd' => (float) $usdExpenses,
            'afn' => (float) $afnExpenses,
            'total' => (float) $totalExpenses,
            'growth' => (float) $growth,
            'prev_total' => (float) $prevTotalExpenses,
        ];
    }

// ─── NET PROFIT (AFN) ───
    protected function getNetProfit($grossProfit, $expenseStats)
    {
        // Gross profit in AFN from sale items
        $grossProfitAfn = $grossProfit['afn'] ?? 0;

        // Expenses in AFN (convert USD expenses to AFN)
        $usdExpenses = $expenseStats['usd'] ?? 0;
        $afnExpenses = $expenseStats['afn'] ?? 0;

        // Convert USD expenses to AFN
        $exchangeRate = $this->getDashboardCurrency('AFN')->exchange_rate ?? 66;
        $totalExpensesAfn = ($usdExpenses * $exchangeRate) + $afnExpenses;

        // Net profit in AFN
        $netProfitAfn = $grossProfitAfn - $totalExpensesAfn;
        $netProfitUsd = $netProfitAfn / $exchangeRate;

        // Previous period
        $prevGrossProfitAfn = $grossProfit['prev_afn'] ?? 0;
        $prevUsdExpenses = $expenseStats['prev_usd'] ?? 0;
        $prevAfnExpenses = $expenseStats['prev_afn'] ?? 0;
        $prevTotalExpensesAfn = ($prevUsdExpenses * $exchangeRate) + $prevAfnExpenses;
        $prevNetProfitAfn = $prevGrossProfitAfn - $prevTotalExpensesAfn;

        $netGrowth = $prevNetProfitAfn > 0 ? (($netProfitAfn - $prevNetProfitAfn) / $prevNetProfitAfn) * 100 : 0;
        $netMargin = $grossProfitAfn > 0 ? ($netProfitAfn / $grossProfitAfn) * 100 : 0;

        return [
            'afn' => (float) $netProfitAfn,
            'usd' => (float) $netProfitUsd,
            'growth' => (float) $netGrowth,
            'margin' => (float) $netMargin,
            'prev_afn' => (float) $prevNetProfitAfn,
        ];
    }

    // ─── INVENTORY (USD Only) ───
    protected function getInventoryStats()
    {
        $purchaseItems = PurchaseItem::where('qty_available', '>', 0)
            ->whereHas('purchase', function ($q) {
                $q->where('status', 'arrived');
            })
            ->with(['purchase.expenses', 'product'])
            ->get();

        $totalValue = 0;
        $totalUnits = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $topProducts = [];

        foreach ($purchaseItems as $item) {
            $totalExpenses = $item->purchase->expenses->sum('usd_amount') ?? 0;
            $expensePerUnit = $item->qty > 0 ? ($totalExpenses / $item->qty) : 0;
            $unitCost = $item->qty > 0 ? ($item->usd_total / $item->qty) : 0;
            $unitCostWithExpenses = $unitCost + $expensePerUnit;

            $value = $item->qty_available * $unitCostWithExpenses;
            $totalValue += $value;
            $totalUnits += $item->qty_available;

            if ($item->qty_available < 10) {
                $lowStockCount++;
            }

            if ($item->product) {
                $key = $item->product_id;
                if (!isset($topProducts[$key])) {
                    $topProducts[$key] = [
                        'name' => $item->product->name,
                        'value' => 0,
                        'qty' => 0,
                    ];
                }
                $topProducts[$key]['value'] += $value;
                $topProducts[$key]['qty'] += $item->qty_available;
            }
        }

        $outOfStockCount = Product::where('is_active', true)
            ->whereDoesntHave('purchaseItems', function ($q) {
                $q->where('qty_available', '>', 0)
                    ->whereHas('purchase', function ($p) {
                        $p->where('status', 'arrived');
                    });
            })
            ->count();

        usort($topProducts, function ($a, $b) {
            return $b['value'] - $a['value'];
        });
        $topProducts = array_slice($topProducts, 0, 5);

        return [
            'value' => (float) $totalValue,
            'units' => (float) $totalUnits,
            'low_stock' => (int) $lowStockCount,
            'out_of_stock' => (int) $outOfStockCount,
            'top_products' => $topProducts,
        ];
    }

    // ─── RECEIVABLES (Both Currencies) ───
    protected function getReceivableStats()
    {
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');

        // USD Receivables
        $usdSales = Sale::where('status', 'confirmed')
            ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
            ->get();

        $usdReceivables = 0;
        $usdTopCustomers = [];

        foreach ($usdSales as $sale) {
            $balance = ($sale->grand_total ?? 0) - ($sale->advance_payment ?? 0);
            $usdReceivables += $balance;

            if ($balance > 0 && $sale->customer) {
                $usdTopCustomers[] = [
                    'name' => $sale->customer->name ?? 'Unknown',
                    'balance' => (float) $balance,
                ];
            }
        }

        // AFN Receivables
        $afnSales = Sale::where('status', 'confirmed')
            ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
            ->get();

        $afnReceivables = 0;
        $afnTopCustomers = [];

        foreach ($afnSales as $sale) {
            $balance = ($sale->grand_total ?? 0) - ($sale->advance_payment ?? 0);
            $afnReceivables += $balance;

            if ($balance > 0 && $sale->customer) {
                $afnTopCustomers[] = [
                    'name' => $sale->customer->name ?? 'Unknown',
                    'balance' => (float) $balance,
                ];
            }
        }

        // Sort top customers
        usort($usdTopCustomers, function ($a, $b) { return $b['balance'] - $a['balance']; });
        usort($afnTopCustomers, function ($a, $b) { return $b['balance'] - $a['balance']; });

        return [
            'usd' => (float) $usdReceivables,
            'afn' => (float) $afnReceivables,
            'top_usd' => array_slice($usdTopCustomers, 0, 5),
            'top_afn' => array_slice($afnTopCustomers, 0, 5),
        ];
    }

    // ─── PAYABLES (Both Currencies) ───
    protected function getPayableStats()
    {
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');

        // USD Payables
        $usdPurchases = Purchase::where('status', 'arrived')
            ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
            ->get();

        $usdPayables = $usdPurchases->sum('grand_total') ?? 0;

        // AFN Payables
        $afnPurchases = Purchase::where('status', 'arrived')
            ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
            ->get();

        $afnPayables = $afnPurchases->sum('grand_total') ?? 0;

        // Paid amounts
        $supplierIds = Account::where('account_type', 'supplier')->pluck('id');

        $usdPaid = Transaction::whereIn('account_id', $supplierIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $usdCurrency ? $usdCurrency->id : 0)
            ->sum('amount') ?? 0;

        $afnPaid = Transaction::whereIn('account_id', $supplierIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('currency_id', $afnCurrency ? $afnCurrency->id : 0)
            ->sum('amount') ?? 0;

        return [
            'usd' => (float) ($usdPayables - $usdPaid),
            'afn' => (float) ($afnPayables - $afnPaid),
            'total_usd_purchases' => (float) $usdPayables,
            'total_afn_purchases' => (float) $afnPayables,
        ];
    }

    // ─── CURRENCY BALANCES BY ACCOUNT TYPE ───
    protected function getCurrencyBalancesByAccountType()
    {
        $accountTypes = ['customer', 'supplier', 'agent', 'expense', 'saraf'];
        $result = [];

        foreach ($accountTypes as $type) {
            $balances = DB::table('transactions')
                ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
                ->where('accounts.account_type', $type)
                ->where('transactions.status', 'active')
                ->select(
                    'currencies.id as currency_id',
                    'currencies.code as currency_code',
                    'currencies.symbol as currency_symbol',
                    DB::raw('
                        COALESCE(
                            SUM(CASE WHEN transactions.transaction_type = "credit" THEN transactions.amount ELSE 0 END) -
                            SUM(CASE WHEN transactions.transaction_type = "debit" THEN transactions.amount ELSE 0 END),
                            0
                        ) as balance
                    ')
                )
                ->groupBy('currencies.id', 'currencies.code', 'currencies.symbol')
                ->having('balance', '!=', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'currency_code' => $item->currency_code ?? 'USD',
                        'currency_symbol' => $item->currency_symbol ?? '$',
                        'balance' => (float) ($item->balance ?? 0),
                    ];
                });

            $result[$type] = $balances;
        }

        return $result;
    }

    protected function getDashboardCurrencies(): \Illuminate\Support\Collection
    {
        if ($this->dashboardCurrencies === null) {
            $this->dashboardCurrencies = Currency::whereIn('code', ['USD', 'AFN'])
                ->orWhere('is_default', true)
                ->get();
        }

        return $this->dashboardCurrencies;
    }

    protected function getDashboardCurrency(string $code): ?Currency
    {
        return $this->getDashboardCurrencies()->firstWhere('code', $code);
    }

    // ─── HELPER: Get Date Range ───
    protected function getDateRange($timeframe)
    {
        $now = Carbon::now();

        return match ($timeframe) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Today',
                'previous_start' => $now->copy()->subDay()->startOfDay(),
                'previous_end' => $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => 'This Week',
                'previous_start' => $now->copy()->subWeek()->startOfWeek(),
                'previous_end' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
                'label' => 'This Quarter',
                'previous_start' => $now->copy()->subQuarter()->startOfQuarter(),
                'previous_end' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => 'This Year',
                'previous_start' => $now->copy()->subYear()->startOfYear(),
                'previous_end' => $now->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'This Month',
                'previous_start' => $now->copy()->subMonth()->startOfMonth(),
                'previous_end' => $now->copy()->subMonth()->endOfMonth(),
            ],
        };
    }

    // ─── HELPER: Get Expense Account IDs ───
    protected function getExpenseAccountIds()
    {
        return Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->pluck('id');
    }

    // ─── HELPER: Get Customer Stats ───
    protected function getCustomerStats()
    {
        return [
            'total' => Account::where('account_type', 'customer')->count(),
            'active' => Account::where('account_type', 'customer')->where('is_active', true)->count(),
            'new' => Account::where('account_type', 'customer')->whereMonth('created_at', Carbon::now()->month)->count(),
        ];
    }

    // ─── HELPER: Get Supplier Stats ───
    protected function getSupplierStats()
    {
        return [
            'total' => Account::where('account_type', 'supplier')->count(),
            'active' => Account::where('account_type', 'supplier')->where('is_active', true)->count(),
        ];
    }

    // ─── HELPER: Get Agent Stats ───
    protected function getAgentStats()
    {
        return [
            'total' => Account::where('account_type', 'agent')->count(),
            'active' => Account::where('account_type', 'agent')->where('is_active', true)->count(),
        ];
    }

    // ─── HELPER: Get Saraf Stats ───
    protected function getSarafStats()
    {
        return [
            'total' => Account::where('account_type', 'saraf')->count(),
            'active' => Account::where('account_type', 'saraf')->where('is_active', true)->count(),
        ];
    }

    // ─── HELPER: Get Alert Data ───
    protected function getAlertData()
    {
        $alerts = [];

        // Low Stock Alerts
        $lowStockItems = PurchaseItem::where('qty_available', '>', 0)
            ->where('qty_available', '<', 10)
            ->whereHas('purchase', function ($q) {
                $q->where('status', 'arrived');
            })
            ->with('product')
            ->limit(3)
            ->get();

        foreach ($lowStockItems as $item) {
            if ($item->product) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'bi-exclamation-triangle',
                    'title' => '⚠️ Low Stock Alert',
                    'message' => "Product '{$item->product->name}' has only {$item->qty_available} units remaining",
                    'link' => route('admin.stock.index'),
                ];
            }
        }

        // Out of Stock
        $outOfStock = Product::where('is_active', true)
            ->whereDoesntHave('purchaseItems', function ($q) {
                $q->where('qty_available', '>', 0)
                    ->whereHas('purchase', function ($p) {
                        $p->where('status', 'arrived');
                    });
            })
            ->limit(3)
            ->get();

        foreach ($outOfStock as $product) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'bi-x-circle',
                'title' => '🚫 Out of Stock',
                'message' => "Product '{$product->name}' is completely out of stock",
                'link' => route('admin.stock.index'),
            ];
        }

        return array_slice($alerts, 0, 10);
    }

    // ─── CHART DATA ───
    public function getChartData(Request $request)
    {
        try {
            $type = $request->input('type', 'sales');
            $period = $request->input('period', 'monthly');

            $data = match ($type) {
                'sales' => $this->getSalesChartData($period),
                'profit' => $this->getProfitChartData($period),
                'expenses' => $this->getExpenseChartData($period),
                'customers' => $this->getCustomerChartData(),
                'categories' => $this->getCategoryChartData(),
                default => $this->getSalesChartData($period),
            };

            // ─── ENSURE DATA IS IN CORRECT FORMAT ───
            if (empty($data)) {
                $data = [
                    ['label' => 'No Data', 'value' => 0]
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Chart Data Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── HELPER: Get Sales Chart Data ───
    protected function getSalesChartData($period)
    {
        $dates = $this->getPeriodDates($period);
        $data = [];
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');

        if (!$usdCurrency || !$afnCurrency) {
            return [];
        }

        $aggregates = $this->getChartFinancialAggregates($dates);

        foreach ($dates as $date) {
            // USD Sales
            $bucket = $date['start']->toDateString();
            $usdSales = $aggregates['sales']->get($bucket.'|'.$usdCurrency->id);

            // AFN Sales
            $afnSales = $aggregates['sales']->get($bucket.'|'.$afnCurrency->id);

            // USD Returns
            $usdReturns = $aggregates['returns']->get($bucket.'|'.$usdCurrency->id);

            // AFN Returns
            $afnReturns = $aggregates['returns']->get($bucket.'|'.$afnCurrency->id);

            $usdRevenue = ($usdSales->total ?? 0) - ($usdReturns->total ?? 0);
            $afnRevenue = ($afnSales->total ?? 0) - ($afnReturns->total ?? 0);

            // ─── CALCULATE PROFIT ───
            $usdCogs = ($usdSales->total_cost_usd ?? 0) - ($usdReturns->total_cost_usd ?? 0);
            $afnCogs = ($afnSales->total_cost_usd ?? 0) - ($afnReturns->total_cost_usd ?? 0);
            $totalCogs = $usdCogs + $afnCogs;

            // Get exchange rate
            $exchangeRate = $afnCurrency->exchange_rate ?? 66;

            // Total revenue in AFN
            $totalRevenueAfn = ($usdRevenue * $exchangeRate) + $afnRevenue;
            $profitAfn = $totalRevenueAfn - ($totalCogs * $exchangeRate);

            $data[] = [
                'label' => $date['label'],
                'usd' => (float) $usdRevenue,
                'afn' => (float) $afnRevenue,
                'revenue' => (float) $totalRevenueAfn,
                'profit' => (float) $profitAfn,
                'usd_revenue' => (float) $usdRevenue,
                'afn_revenue' => (float) $afnRevenue,
            ];
        }

        return $data;
    }
    // ─── HELPER: Get Profit Chart Data ───
    protected function getProfitChartData($period)
    {
        $dates = $this->getPeriodDates($period);
        $data = [];
        $exchangeRate = $this->getDashboardCurrency('AFN')->exchange_rate ?? 66;
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');
        $aggregates = $this->getChartFinancialAggregates($dates);
        $expenses = $this->getChartExpenseAggregates($dates);

        foreach ($dates as $date) {
            // Revenue
            $bucket = $date['start']->toDateString();
            $usdSales = $aggregates['sales']->get($bucket.'|'.($usdCurrency ? $usdCurrency->id : 0));

            $afnSales = $aggregates['sales']->get($bucket.'|'.($afnCurrency ? $afnCurrency->id : 0));

            $usdReturns = $aggregates['returns']->get($bucket.'|'.($usdCurrency ? $usdCurrency->id : 0));

            $afnReturns = $aggregates['returns']->get($bucket.'|'.($afnCurrency ? $afnCurrency->id : 0));

            $usdRevenue = ($usdSales->total ?? 0) - ($usdReturns->total ?? 0);
            $afnRevenue = ($afnSales->total ?? 0) - ($afnReturns->total ?? 0);

            // COGS (USD) — realized FIFO cost for production sales with
            // consumption, stored quotation cost otherwise.
            $usdCogs = ($usdSales->total_cost_usd ?? 0) - ($usdReturns->total_cost_usd ?? 0);
            $afnCogs = ($afnSales->total_cost_usd ?? 0) - ($afnReturns->total_cost_usd ?? 0);
            $totalCogsUsd = $usdCogs + $afnCogs;

            // Revenue is already split: usdRevenue is USD, afnRevenue is AFN.
            // Convert USD to the default currency, keep AFN as-is.
            $totalRevenueAfn = ($usdRevenue * $exchangeRate) + $afnRevenue;

            // Expenses (AFN)
            $usdExpenses = $expenses->get($bucket.'|'.($usdCurrency ? $usdCurrency->id : 0))->amount ?? 0;
            $afnExpenses = $expenses->get($bucket.'|'.($afnCurrency ? $afnCurrency->id : 0))->amount ?? 0;

            $totalExpensesAfn = ($usdExpenses * $exchangeRate) + $afnExpenses;
            $profitAfn = $totalRevenueAfn - ($totalCogsUsd * $exchangeRate) - $totalExpensesAfn;

            $data[] = [
                'label' => $date['label'],
                'revenue' => (float) $totalRevenueAfn,
                'profit' => (float) $profitAfn,
                'usd_revenue' => (float) $usdRevenue,
                'afn_revenue' => (float) $afnRevenue,
            ];
        }

        return $data;
    }

    // ─── HELPER: Get Expense Chart Data ───
    protected function getExpenseChartData($period)
    {
        $dates = $this->getPeriodDates($period);
        $data = [];
        $usdCurrency = $this->getDashboardCurrency('USD');
        $afnCurrency = $this->getDashboardCurrency('AFN');
        $expenses = $this->getChartExpenseAggregates($dates);

        foreach ($dates as $date) {
            $bucket = $date['start']->toDateString();
            $usdExpenses = $expenses->get($bucket.'|'.($usdCurrency ? $usdCurrency->id : 0))->amount ?? 0;
            $afnExpenses = $expenses->get($bucket.'|'.($afnCurrency ? $afnCurrency->id : 0))->amount ?? 0;

            $data[] = [
                'label' => $date['label'],
                'usd' => (float) $usdExpenses,
                'afn' => (float) $afnExpenses,
                'total' => (float) ($usdExpenses + $afnExpenses),
            ];
        }

        return $data;
    }

    /**
     * Actual FIFO production cost per sale for sales that already have material
     * consumption, consistent with SaleProfitService::calculate(): material is
     * summed in USD (matched by sale_id first, else production_order_id) and
     * actual labour/overhead/other direct costs are taken in AFN from the
     * production order. Set-based (no per-sale service calls).
     *
     * @return array<int, array{cost_afn: float, cost_usd: float}>
     */
    protected function getRealizedCostBySales(\Illuminate\Support\Collection $sales): array
    {
        return \App\Services\SaleProfitService::realizedCostBySales($sales);
    }

    /**
     * Convert the period-date descriptors into explicit [start, end) ranges.
     * The final bucket ends at "now". Keys match the consumers' bucket lookup
     * (`$date['start']->toDateString()`).
     *
     * @return array<int, array{key: string, label: string, start: Carbon, end: Carbon}>
     */
    protected function getChartBucketRanges(array $dates): array
    {
        $ranges = [];
        foreach ($dates as $index => $date) {
            $start = $date['start']->copy();
            $end = isset($dates[$index + 1])
                ? $dates[$index + 1]['start']->copy()
                : Carbon::now();
            $ranges[] = [
                'key' => $start->toDateString(),
                'label' => $date['label'],
                'start' => $start,
                'end' => $end,
            ];
        }
        return $ranges;
    }

    /**
     * Map a timestamp to its chart bucket key (the bucket start date), or null
     * when it falls outside every bucket range.
     */
    protected function getBucketKeyFor(?Carbon $timestamp, array $ranges): ?string
    {
        if (!$timestamp) {
            return null;
        }
        foreach ($ranges as $range) {
            if ($timestamp->gte($range['start']) && $timestamp->lt($range['end'])) {
                return $range['key'];
            }
        }
        return null;
    }

    // ─── HELPER: Get Chart Financial Aggregates ───
    // Sales group by the bucket ranges that contain sales.created_at. COGS uses
    // ACTUAL FIFO production cost (getRealizedCostBySales) for production sales
    // with consumption and the stored quotation cost otherwise. Returns always
    // use the stored quotation cost.
    protected function getChartFinancialAggregates(array $dates): array
    {
        $ranges = $this->getChartBucketRanges($dates);
        $overallStart = $ranges[0]['start'];
        $overallEnd = $ranges[count($ranges) - 1]['end'];

        $sales = Sale::with('items')
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $overallStart)
            ->where('created_at', '<', $overallEnd)
            ->get(['id', 'currency_id', 'grand_total', 'exchange_rate', 'production_order_id', 'created_at']);

        $realized = $this->getRealizedCostBySales($sales);

        $grouped = [];
        foreach ($sales as $sale) {
            $bucket = $this->getBucketKeyFor($sale->created_at, $ranges);
            if ($bucket === null) {
                continue;
            }
            $key = $bucket.'|'.$sale->currency_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['total' => 0.0, 'total_cost_usd' => 0.0];
            }
            $grouped[$key]['total'] += (float) ($sale->grand_total ?? 0);
            if (isset($realized[$sale->id])) {
                $grouped[$key]['total_cost_usd'] += $realized[$sale->id]['cost_usd'];
            } else {
                foreach ($sale->items as $item) {
                    $grouped[$key]['total_cost_usd'] += (float) $item->total_cost_usd;
                }
            }
        }

        $salesAgg = collect($grouped)->map(fn ($values) => (object) $values);

        $returns = SaleReturn::with('items')
            ->where('status', 'processed')
            ->where('created_at', '>=', $overallStart)
            ->where('created_at', '<', $overallEnd)
            ->get(['id', 'currency_id', 'grand_total', 'created_at']);

        $returnGrouped = [];
        foreach ($returns as $return) {
            $bucket = $this->getBucketKeyFor($return->created_at, $ranges);
            if ($bucket === null) {
                continue;
            }
            $key = $bucket.'|'.$return->currency_id;
            if (!isset($returnGrouped[$key])) {
                $returnGrouped[$key] = ['total' => 0.0, 'total_cost_usd' => 0.0];
            }
            $returnGrouped[$key]['total'] += (float) ($return->grand_total ?? 0);
            foreach ($return->items as $item) {
                $returnGrouped[$key]['total_cost_usd'] += (float) $item->total_cost_usd;
            }
        }

        $returnsAgg = collect($returnGrouped)->map(fn ($values) => (object) $values);

        return ['sales' => $salesAgg, 'returns' => $returnsAgg];
    }

    protected function getChartExpenseAggregates(array $dates): \Illuminate\Support\Collection
    {
        $ranges = $this->getChartBucketRanges($dates);
        $overallStart = $ranges[0]['start'];
        $overallEnd = $ranges[count($ranges) - 1]['end'];

        $rows = Transaction::query()
            ->whereIn('account_id', $this->getExpenseAccountIds())
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->where('created_at', '>=', $overallStart)
            ->where('created_at', '<', $overallEnd)
            ->selectRaw('created_at, currency_id, amount')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $bucket = $this->getBucketKeyFor(Carbon::parse($row->created_at), $ranges);
            if ($bucket === null) {
                continue;
            }
            $key = $bucket.'|'.$row->currency_id;
            $grouped[$key] = ($grouped[$key] ?? 0.0) + (float) $row->amount;
        }

        return collect($grouped)->map(fn ($amount) => (object) ['amount' => $amount]);
    }

    protected function getCustomerChartData()
    {
        $customers = Account::where('account_type', 'customer')
            ->withCount([
                'sales as confirmed_sales_count' => function ($query) {
                    $query->where('status', 'confirmed');
                },
            ])
            ->get(['id']);

        if ($customers->isEmpty()) {
            return ['labels' => ['No Data'], 'values' => [1], 'colors' => ['#94a3b8']];
        }

        $hasPurchases = $customers->where('confirmed_sales_count', '>', 0)->count();
        $noPurchases = $customers->count() - $hasPurchases;

        return [
            'labels' => ['Has Purchases', 'No Purchases'],
            'values' => [$hasPurchases, $noPurchases],
            'colors' => ['#10b981', '#6b7280'],
        ];
    }

    // ─── HELPER: Get Category Chart Data ───
    protected function getCategoryChartData()
    {
        $categories = Category::withCount('products')->get();

        if ($categories->isEmpty()) {
            return ['labels' => ['No Data'], 'values' => [1], 'colors' => ['#94a3b8']];
        }

        return [
            'labels' => $categories->pluck('name')->toArray(),
            'values' => $categories->pluck('products_count')->toArray(),
            'colors' => ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#3b82f6'],
        ];
    }
// ─── HELPER: Get Period Dates ───
    protected function getPeriodDates($period)
    {
        $dates = [];

        switch ($period) {
            case 'daily':
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $dates[] = [
                        'label' => $date->format('M d'),
                        'start' => $date->copy()->startOfDay(),
                    ];
                }
                break;
            case 'weekly':
                for ($i = 11; $i >= 0; $i--) {
                    $date = Carbon::today()->subWeeks($i);
                    $dates[] = [
                        'label' => 'W' . $date->format('W'),
                        'start' => $date->copy()->startOfWeek(),
                    ];
                }
                break;
            case 'yearly':
                for ($i = 4; $i >= 0; $i--) {
                    $date = Carbon::today()->subYears($i);
                    $dates[] = [
                        'label' => $date->format('Y'),
                        'start' => $date->copy()->startOfYear(),
                    ];
                }
                break;
            default: // monthly
                for ($i = 11; $i >= 0; $i--) {
                    $date = Carbon::today()->subMonths($i);
                    $dates[] = [
                        'label' => $date->format('M Y'),
                        'start' => $date->copy()->startOfMonth(),
                    ];
                }
                break;
        }

        return $dates;
    }
    // ─── RECENT ACTIVITY ───
    public function getRecentActivity(Request $request)
    {
        try {
            $type = $request->input('type', 'all');
            $limit = $request->input('limit', 10);

            return response()->json([
                'success' => true,
                'data' => $this->getRecentData($type, $limit)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── HELPER: Get Recent Data ───
    protected function getRecentData($type, $limit)
    {
        $data = [];

        if ($type === 'all' || $type === 'sales') {
            $sales = Sale::with(['customer', 'currency'])
                ->withSum([
                    'returns as processed_return_amount' => function ($query) {
                        $query->where('status', 'processed');
                    },
                ], 'grand_total')
                ->orderBy('created_at', 'desc')
                ->limit($type === 'all' ? $limit : $limit)
                ->get()
                ->map(function ($item) {
                    $returnAmount = $item->processed_return_amount ?? 0;

                    return [
                        'type' => 'sale',
                        'id' => $item->id,
                        'no' => $item->sale_no,
                        'title' => 'Sale #' . $item->sale_no,
                        'description' => $item->customer->name ?? 'N/A',
                        'amount' => (float) ($item->grand_total - $returnAmount),
                        'currency' => $item->currency->code ?? 'USD',
                        'status' => $item->status,
                        'date' => $item->created_at->format('Y-m-d H:i'),
                        'icon' => 'bi-cart-plus',
                        'color' => $this->getStatusColor($item->status),
                    ];
                });

            if ($type === 'sales') {
                return $sales;
            }
            $data = array_merge($data, $sales->toArray());
        }

        if ($type === 'all' || $type === 'purchases') {
            $purchases = Purchase::with(['supplier', 'currency'])
                ->orderBy('created_at', 'desc')
                ->limit($type === 'all' ? $limit : $limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'purchase',
                        'id' => $item->id,
                        'no' => $item->purchase_no,
                        'title' => 'Purchase #' . $item->purchase_no,
                        'description' => $item->supplier->name ?? 'N/A',
                        'amount' => (float) $item->grand_total,
                        'currency' => $item->currency->code ?? 'USD',
                        'status' => $item->status,
                        'date' => $item->created_at->format('Y-m-d H:i'),
                        'icon' => 'bi-truck',
                        'color' => $this->getStatusColor($item->status),
                    ];
                });

            if ($type === 'purchases') {
                return $purchases;
            }
            $data = array_merge($data, $purchases->toArray());
        }

        if ($type === 'all' || $type === 'expenses') {
            $expenseAccountIds = $this->getExpenseAccountIds();

            $expenses = Transaction::whereIn('account_id', $expenseAccountIds)
                ->where('transaction_type', 'debit')
                ->where('status', 'active')
                ->with(['account', 'currency'])
                ->orderBy('created_at', 'desc')
                ->limit($type === 'all' ? $limit : $limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'expense',
                        'id' => $item->id,
                        'title' => 'Expense: ' . ($item->account->name ?? 'Unknown'),
                        'description' => $item->description ?? 'No description',
                        'amount' => (float) $item->amount,
                        'currency' => $item->currency ? $item->currency->code : 'USD',
                        'date' => $item->created_at->format('Y-m-d H:i'),
                        'icon' => 'bi-receipt',
                        'color' => '#f59e0b',
                    ];
                });

            if ($type === 'expenses') {
                return $expenses;
            }
            $data = array_merge($data, $expenses->toArray());
        }

        usort($data, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($data, 0, $limit);
    }

    // ─── GET ALERTS ───
    public function getAlerts(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->getAlertData()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── HELPER: Get Status Color ───
    protected function getStatusColor($status)
    {
        return match ($status) {
            'draft' => '#6b7280',
            'confirmed' => '#3b82f6',
            'shipped' => '#f59e0b',
            'delivered', 'arrived', 'processed' => '#10b981',
            'shipping' => '#8b5cf6',
            'approved' => '#3b82f6',
            'rejected' => '#ef4444',
            default => '#6b7280',
        };
    }
}
