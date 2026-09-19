<?php
// app/Services/ProfitSharingService.php

namespace App\Services;

use App\Models\Account;
use App\Models\Currency;
use App\Models\ProfitDistribution;
use App\Models\ProfitDistributionItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shareholder;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProfitSharingService
{
    public function __construct(private SaleProfitService $saleProfitService)
    {
    }

    /**
     * Calculate business profit using the same normalized/default-currency
     * calculation used by shareholder distributions.
     */
    public function calculateTotalProfit($startDate, $endDate)
    {
        return $this->calculatePeriodProfit($startDate, $endDate)['total_profit'];
    }

    /**
     * Calculate distributable profit for a period in the application's
     * default currency.
     *
     * Revenue is normalized from each sale into the default currency.
     * COGS is canonical USD sale-item cost converted into the same default
     * currency using the sale's historical exchange rate where possible.
     * Expense transactions are also normalized into the default currency.
     */
    public function calculatePeriodProfit($startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $defaultCurrency = Currency::where('is_default', true)->first();

        if (!$defaultCurrency) {
            throw new RuntimeException('Default currency is not configured.');
        }

        $sales = Sale::with(['items', 'currency'])
            ->where('status', 'confirmed')
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $purchases = Purchase::with('currency')
            ->where('status', 'arrived')
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $expenseAccountIds = Account::where('account_type', 'expense')
            ->where('is_active', true)
            ->pluck('id');

        $expenseTransactions = Transaction::with('currency')
            ->whereIn('account_id', $expenseAccountIds)
            ->where('transaction_type', 'debit')
            ->where('status', 'active')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalSales = 0.0;
        $salesCost = 0.0;

        $actualSalesCount = 0;
        $estimatedSalesCount = 0;

        foreach ($sales as $sale) {
            $profitSummary = $this->saleProfitService->calculate($sale);
            $totalSales += $this->profitSummaryAmountInDefaultCurrency(
                $profitSummary,
                'gross_sales',
                $defaultCurrency
            );

            if ($profitSummary['actual_available'] ?? false) {
                $salesCost += $this->profitSummaryAmountInDefaultCurrency(
                    $profitSummary,
                    'actual_production_cost',
                    $defaultCurrency
                );
                $actualSalesCount++;
            } else {
                // Fall back to the sale's canonical estimated COGS only when
                // actual production consumption does not exist yet.
                $salesCost += $this->profitSummaryAmountInDefaultCurrency(
                    $profitSummary,
                    'estimated_cost',
                    $defaultCurrency
                );
                $estimatedSalesCount++;
            }
        }

        $totalPurchases = 0.0;
        foreach ($purchases as $purchase) {
            $totalPurchases += $this->documentAmountInDefaultCurrency(
                (float) ($purchase->grand_total ?? 0),
                (float) ($purchase->usd_grand_total ?? 0),
                $purchase->currency,
                (float) ($purchase->exchange_rate ?? 1),
                $defaultCurrency
            );
        }

        $totalExpenses = 0.0;
        foreach ($expenseTransactions as $transaction) {
            $totalExpenses += $this->transactionAmountInDefaultCurrency($transaction, $defaultCurrency);
        }

        $salesProfit = $totalSales - $salesCost;
        $totalProfit = $salesProfit - $totalExpenses;

        return [
            'currency_id' => $defaultCurrency->id,
            'currency_code' => $defaultCurrency->code,
            'currency_symbol' => $defaultCurrency->symbol,
            'total_sales' => round($totalSales, 2),
            'total_purchases' => round($totalPurchases, 2),
            'total_expenses' => round($totalExpenses, 2),
            'sales_cost' => round($salesCost, 2),
            'sales_profit' => round($salesProfit, 2),
            'total_profit' => round($totalProfit, 2),
            'sales_count' => $sales->count(),
            'purchases_count' => $purchases->count(),
            'expense_count' => $expenseTransactions->count(),
            'actual_sales_count' => $actualSalesCount,
            'estimated_sales_count' => $estimatedSalesCount,
        ];
    }

    /**
     * Distribute a positive period profit to active shareholders.
     */
    public function distributeProfitLoss($startDate, $endDate, $notes = null, $createdBy = null)
    {
        return DB::transaction(function () use ($startDate, $endDate, $notes, $createdBy) {
            $createdBy = $createdBy ?? Auth::id() ?? 1;
            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate = Carbon::parse($endDate)->toDateString();

            $existing = ProfitDistribution::whereDate('period_start', $startDate)
                ->whereDate('period_end', $endDate)
                ->whereIn('status', [
                    ProfitDistribution::STATUS_APPROVED,
                    ProfitDistribution::STATUS_DISTRIBUTED,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new RuntimeException('A distribution for this period has already been processed.');
            }

            $profitData = $this->calculatePeriodProfit($startDate, $endDate);
            $totalProfit = round((float) $profitData['total_profit'], 2);

            if ($totalProfit <= 0) {
                throw new RuntimeException('There is no positive distributable profit for the selected period.');
            }

            $shareholders = Shareholder::where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($shareholders->isEmpty()) {
                throw new RuntimeException('No active shareholders found.');
            }

            $totalSharePercentage = round((float) $shareholders->sum('share_percentage'), 2);
            if (abs($totalSharePercentage - 100.0) > 0.01) {
                throw new RuntimeException("Total share percentage is {$totalSharePercentage}%. It must be 100%.");
            }

            $defaultCurrency = Currency::where('is_default', true)->first();
            if (!$defaultCurrency) {
                throw new RuntimeException('Default currency is not configured.');
            }

            // Round each shareholder to currency precision and put any one/two-cent
            // remainder on the final shareholder so persisted items equal total profit.
            $distributionData = [];
            $allocated = 0.0;
            $lastIndex = $shareholders->count() - 1;

            foreach ($shareholders->values() as $index => $shareholder) {
                $amount = $index === $lastIndex
                    ? round($totalProfit - $allocated, 2)
                    : round($totalProfit * ((float) $shareholder->share_percentage / 100), 2);

                $distributionData[] = [
                    'shareholder' => $shareholder,
                    'share_percentage' => (float) $shareholder->share_percentage,
                    'amount' => $amount,
                ];

                $allocated += $amount;
            }

            $totalDistributed = round(array_sum(array_column($distributionData, 'amount')), 2);

            $distribution = ProfitDistribution::create([
                'distribution_number' => $this->generateDistributionNumber(),
                'period_start' => $startDate,
                'period_end' => $endDate,
                'total_profit' => $totalProfit,
                'distributed_amount' => $totalDistributed,
                'remaining_amount' => round($totalProfit - $totalDistributed, 2),
                'distribution_date' => now(),
                'status' => ProfitDistribution::STATUS_APPROVED,
                'notes' => $notes ?? 'Profit distribution for ' . Carbon::parse($startDate)->format('M d') . ' - ' . Carbon::parse($endDate)->format('M d, Y'),
                'created_by' => $createdBy,
                'approved_by' => $createdBy,
                'approved_at' => now(),
            ]);

            foreach ($distributionData as $data) {
                $shareholder = $data['shareholder'];
                $amount = $data['amount'];

                $item = ProfitDistributionItem::create([
                    'distribution_id' => $distribution->id,
                    'shareholder_id' => $shareholder->id,
                    'share_percentage' => $data['share_percentage'],
                    'amount' => $amount,
                    'status' => ProfitDistributionItem::STATUS_PENDING,
                ]);

                $transaction = Transaction::create([
                    'table_name' => 'profit_distributions',
                    'table_row_id' => $distribution->id,
                    // "adjustment" is part of the canonical transaction enum.
                    // table_name + shareholder_id identify the shareholder event.
                    'type' => 'adjustment',
                    'account_id' => null,
                    'shareholder_id' => $shareholder->id,
                    'currency_id' => $defaultCurrency->id,
                    'amount' => $amount,
                    'usd_amount' => strtoupper((string) $defaultCurrency->code) === 'USD'
                        ? $amount
                        : null,
                    'exchange_rate' => 1,
                    'transaction_type' => 'credit',
                    'is_cash' => false,
                    'description' => "Profit Distribution - {$shareholder->name} - {$distribution->distribution_number}",
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'is_visible' => true,
                ]);

                $item->update([
                    'transaction_id' => $transaction->id,
                    'status' => ProfitDistributionItem::STATUS_PAID,
                    'payment_date' => now(),
                ]);
            }

            $distribution->update([
                'status' => ProfitDistribution::STATUS_DISTRIBUTED,
                'distributed_amount' => $totalDistributed,
                'remaining_amount' => round($totalProfit - $totalDistributed, 2),
            ]);

            return $distribution->fresh(['items.shareholder']);
        });
    }

    private function profitSummaryAmountInDefaultCurrency(array $summary, string $prefix, Currency $defaultCurrency): float
    {
        $defaultCode = strtoupper((string) $defaultCurrency->code);

        if ($defaultCode === 'AFN') {
            return (float) ($summary[$prefix . '_afn'] ?? 0);
        }

        if ($defaultCode === 'USD') {
            return (float) ($summary[$prefix . '_usd'] ?? 0);
        }

        $usdAmount = (float) ($summary[$prefix . '_usd'] ?? 0);
        $usdCurrency = Currency::where('code', 'USD')->first();
        if ($usdCurrency) {
            return (float) $usdCurrency->convertToDefault($usdAmount);
        }

        throw new RuntimeException("Cannot convert {$prefix} into the configured default currency.");
    }

    private function documentAmountInDefaultCurrency(
        float $localAmount,
        float $usdAmount,
        ?Currency $documentCurrency,
        float $historicalRate,
        Currency $defaultCurrency
    ): float {
        if ($documentCurrency && (int) $documentCurrency->id === (int) $defaultCurrency->id) {
            return $localAmount;
        }

        $defaultCode = strtoupper((string) $defaultCurrency->code);
        $documentCode = strtoupper((string) ($documentCurrency?->code ?? ''));

        if ($defaultCode === 'USD') {
            if ($usdAmount != 0.0) {
                return $usdAmount;
            }

            if ($documentCode === 'USD') {
                return $localAmount;
            }

            if ($historicalRate > 0) {
                return $localAmount / $historicalRate;
            }
        }

        if ($documentCode === 'USD' && $historicalRate > 0) {
            return $localAmount * $historicalRate;
        }

        // Hybrid sale/purchase records keep a historical USD amount. When the
        // default is a non-USD local currency, the document rate is the safest
        // historical USD -> local conversion available on the record.
        if ($usdAmount != 0.0 && $historicalRate > 0) {
            return $usdAmount * $historicalRate;
        }

        if ($documentCurrency) {
            return (float) $documentCurrency->convertToDefault($localAmount);
        }

        return $localAmount;
    }

    private function transactionAmountInDefaultCurrency(Transaction $transaction, Currency $defaultCurrency): float
    {
        $amount = (float) $transaction->amount;

        if ((int) $transaction->currency_id === (int) $defaultCurrency->id) {
            return $amount;
        }

        $currency = $transaction->currency;
        $defaultCode = strtoupper((string) $defaultCurrency->code);
        $currencyCode = strtoupper((string) ($currency?->code ?? ''));
        $usdAmount = (float) ($transaction->usd_amount ?? 0);
        $rate = (float) ($transaction->exchange_rate ?? 0);

        if ($defaultCode === 'USD') {
            if ($usdAmount != 0.0) {
                return $usdAmount;
            }
            if ($currencyCode === 'USD') {
                return $amount;
            }
            if ($rate > 1) {
                return $amount / $rate;
            }
        } else {
            if ($currencyCode === 'USD') {
                if ($rate > 1) {
                    return $amount * $rate;
                }
                $usdCurrency = Currency::where('code', 'USD')->first();
                if ($usdCurrency) {
                    return (float) $usdCurrency->convertToDefault($amount);
                }
            }

            if ($usdAmount != 0.0) {
                $usdCurrency = Currency::where('code', 'USD')->first();
                if ($usdCurrency) {
                    return (float) $usdCurrency->convertToDefault($usdAmount);
                }
            }
        }

        if ($currency) {
            return (float) $currency->convertToDefault($amount);
        }

        throw new RuntimeException("Cannot normalize transaction #{$transaction->id} into the default currency.");
    }

    private function generateDistributionNumber()
    {
        $prefix = 'PD-' . date('Y') . '-';
        $last = ProfitDistribution::where('distribution_number', 'like', $prefix . '%')
            ->orderBy('distribution_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->distribution_number, -6));
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '000001';
        }

        return $prefix . $newNumber;
    }

    public function getDistributionSummary($distributionId)
    {
        $distribution = ProfitDistribution::with(['items.shareholder'])
            ->findOrFail($distributionId);

        return [
            'distribution' => $distribution,
            'total_profit' => $distribution->total_profit,
            'is_profit' => (float) $distribution->total_profit >= 0,
            'status_label' => (float) $distribution->total_profit >= 0 ? 'Profit' : 'Loss',
            'total_distributed' => $distribution->distributed_amount,
            'remaining' => $distribution->remaining_amount,
            'shareholders' => $distribution->items->map(function ($item) {
                return [
                    'id' => $item->shareholder_id,
                    'name' => $item->shareholder->name,
                    'percentage' => $item->share_percentage,
                    'amount' => $item->amount,
                    'type' => 'Profit',
                    'status' => $item->status_label,
                ];
            }),
        ];
    }

    /**
     * Delete a distribution and neutralize its shareholder transactions.
     *
     * The previous implementation both marked the original transaction as
     * reversed AND created an active opposite transaction. Because balances
     * only use active rows, that produced the opposite balance rather than 0.
     * We keep the audit row and mark it reversed; it no longer affects balance.
     */
    public function deleteDistribution($distributionId)
    {
        return DB::transaction(function () use ($distributionId) {
            $distribution = ProfitDistribution::with('items')->findOrFail($distributionId);

            foreach ($distribution->items as $item) {
                if ($item->transaction_id) {
                    $transaction = Transaction::find($item->transaction_id);
                    if ($transaction && $transaction->status === 'active') {
                        $transaction->update([
                            'status' => 'reversed',
                            'description' => 'REVERSED: ' . ($transaction->description ?: 'Profit distribution'),
                        ]);
                    }
                }
            }

            $distribution->items()->delete();
            $distribution->delete();

            return true;
        });
    }
}
