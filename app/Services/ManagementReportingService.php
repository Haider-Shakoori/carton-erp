<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\ProductionMaterialConsumption;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ManagementReportingService
{
    public function __construct(
        private readonly ProductionVarianceService $variance
    ) {
    }

    public function overview(
        Carbon $from,
        Carbon $to,
        bool $consolidated,
        ?User $user = null
    ): array {
        $user ??= auth()->user();
        $businessUnitIds = $this->allowedBusinessUnitIds($user);

        $salesQuery = $this->salesQuery($consolidated, $businessUnitIds)
            ->with(['customer', 'items'])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereBetween('created_at', [$from, $to]);

        $sales = $salesQuery->get();
        $realized = SaleProfitService::realizedCostBySales($sales);

        $revenueUsd = 0.0;
        $costUsd = 0.0;
        $customerRows = [];

        foreach ($sales as $sale) {
            $saleRevenue = (float) ($sale->usd_grand_total ?? 0);
            $saleCost = isset($realized[$sale->id])
                ? (float) $realized[$sale->id]['cost_usd']
                : (float) $sale->items->sum('total_cost_usd');

            $revenueUsd += $saleRevenue;
            $costUsd += $saleCost;

            $customerId = (int) $sale->customer_id;
            $customerRows[$customerId] ??= [
                'customer_id' => $customerId,
                'customer_name' => $sale->customer?->name ?? 'Unknown',
                'orders' => 0,
                'revenue_usd' => 0.0,
                'cost_usd' => 0.0,
                'profit_usd' => 0.0,
            ];

            $customerRows[$customerId]['orders']++;
            $customerRows[$customerId]['revenue_usd'] += $saleRevenue;
            $customerRows[$customerId]['cost_usd'] += $saleCost;
            $customerRows[$customerId]['profit_usd'] += $saleRevenue - $saleCost;
        }

        $customerProfitability = collect($customerRows)
            ->map(function (array $row) {
                $row['margin_percentage'] = $row['revenue_usd'] > 0
                    ? ($row['profit_usd'] / $row['revenue_usd']) * 100
                    : 0.0;

                foreach (['revenue_usd', 'cost_usd', 'profit_usd'] as $key) {
                    $row[$key] = round($row[$key], 2);
                }

                $row['margin_percentage'] = round($row['margin_percentage'], 2);

                return $row;
            })
            ->sortByDesc('profit_usd')
            ->values()
            ->take(20)
            ->all();

        $productionQuery = $this->productionQuery($consolidated, $businessUnitIds)
            ->whereBetween('created_at', [$from, $to]);

        $productionOrders = (clone $productionQuery)
            ->where('status', ProductionOrder::STATUS_COMPLETED)
            ->with(['materials.product', 'events'])
            ->get();

        $plannedOutput = (float) $productionOrders->sum(
            fn ($order) => (float) ($order->quantity_planned ?: $order->quantity_ordered)
        );
        $manufacturedOutput = (float) $productionOrders->sum('quantity_manufactured');
        $goodOutput = (float) $productionOrders->sum('quantity_produced');
        $rejectedOutput = (float) $productionOrders->sum('quantity_rejected');

        $usageVariance = 0.0;
        $rateVariance = 0.0;
        $materialVariance = 0.0;
        $totalProductionVariance = 0.0;

        foreach ($productionOrders as $order) {
            $report = $this->variance->forProductionOrder($order);
            $summary = $report['summary'];

            $usageVariance += (float) ($summary['material_usage_variance_usd'] ?? 0);
            $rateVariance += (float) ($summary['material_rate_variance_usd'] ?? 0);
            $materialVariance += (float) ($summary['material_cost_variance_usd'] ?? 0);
            $totalProductionVariance += (float) ($summary['total_production_cost_variance_usd'] ?? 0);
        }

        $purchases = $this->purchaseQuery($consolidated, $businessUnitIds)
            ->with(['supplier', 'items', 'expenses'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $supplierPerformance = $purchases
            ->groupBy('supplier_id')
            ->map(function (Collection $rows, $supplierId) {
                $first = $rows->first();
                $received = $rows->filter(fn ($purchase) => $purchase->arrival_date);

                $leadDays = $received
                    ->map(function ($purchase) {
                        if (! $purchase->purchase_date || ! $purchase->arrival_date) {
                            return null;
                        }

                        return $purchase->purchase_date->diffInDays($purchase->arrival_date);
                    })
                    ->filter(fn ($days) => $days !== null);

                return [
                    'supplier_id' => (int) $supplierId,
                    'supplier_name' => $first->supplier?->name ?? 'Unknown',
                    'purchase_orders' => $rows->count(),
                    'received_orders' => $received->count(),
                    'spend_usd' => round((float) $rows->sum('usd_grand_total'), 2),
                    'average_receipt_lead_days' => $leadDays->isNotEmpty()
                        ? round((float) $leadDays->average(), 1)
                        : null,
                ];
            })
            ->sortByDesc('spend_usd')
            ->values()
            ->take(20)
            ->all();

        $arrivedPurchases = $this->purchaseQuery($consolidated, $businessUnitIds)
            ->where('status', 'arrived')
            ->with(['items.product', 'expenses'])
            ->get();

        $inventoryValue = 0.0;
        $inventoryUnits = 0.0;
        $slowMovingValue = 0.0;
        $slowMovingBatches = 0;

        foreach ($arrivedPurchases as $purchase) {
            foreach ($purchase->items as $item) {
                $available = $item->isRollBatch()
                    ? (float) ($item->qty_kg_available ?? 0)
                    : (float) ($item->qty_available ?? 0);

                if ($available <= 0) {
                    continue;
                }

                $unitCost = $item->isRollBatch()
                    ? (float) $item->landedCostPerKg()
                    : (float) ($item->usd_cost_per_item ?? $item->usd_unit_price ?? 0);

                $value = $available * $unitCost;
                $inventoryValue += $value;
                $inventoryUnits += $available;

                $purchaseDate = $purchase->purchase_date ?: $purchase->created_at;
                if ($purchaseDate && $purchaseDate->lte(now()->subDays(90))) {
                    $slowMovingBatches++;
                    $slowMovingValue += $value;
                }
            }
        }

        $businessBreakdown = $this->businessBreakdown(
            $from,
            $to,
            $consolidated,
            $businessUnitIds
        );

        $grossProfit = $revenueUsd - $costUsd;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'scope' => $consolidated ? 'consolidated' : 'active_business',
            ],
            'financial' => [
                'revenue_usd' => round($revenueUsd, 2),
                'cost_usd' => round($costUsd, 2),
                'gross_profit_usd' => round($grossProfit, 2),
                'gross_margin_percentage' => $revenueUsd > 0
                    ? round(($grossProfit / $revenueUsd) * 100, 2)
                    : 0.0,
            ],
            'production' => [
                'completed_orders' => $productionOrders->count(),
                'planned_output' => round($plannedOutput, 2),
                'manufactured_output' => round($manufacturedOutput, 2),
                'good_output' => round($goodOutput, 2),
                'rejected_output' => round($rejectedOutput, 2),
                'yield_percentage' => $manufacturedOutput > 0
                    ? round(($goodOutput / $manufacturedOutput) * 100, 2)
                    : 0.0,
                'material_usage_variance_usd' => round($usageVariance, 2),
                'material_rate_variance_usd' => round($rateVariance, 2),
                'material_cost_variance_usd' => round($materialVariance, 2),
                'total_production_cost_variance_usd' => round($totalProductionVariance, 2),
            ],
            'inventory' => [
                'value_usd' => round($inventoryValue, 2),
                'available_quantity' => round($inventoryUnits, 4),
                'slow_moving_batches' => $slowMovingBatches,
                'slow_moving_value_usd' => round($slowMovingValue, 2),
            ],
            'customer_profitability' => $customerProfitability,
            'supplier_performance' => $supplierPerformance,
            'business_breakdown' => $businessBreakdown,
        ];
    }

    private function salesQuery(bool $consolidated, array $ids): Builder
    {
        $query = Sale::query();

        return $consolidated
            ? $this->applyConsolidated($query->withoutGlobalScope('business_unit'), $ids)
            : $query;
    }

    private function purchaseQuery(bool $consolidated, array $ids): Builder
    {
        $query = Purchase::query();

        return $consolidated
            ? $this->applyConsolidated($query->withoutGlobalScope('business_unit'), $ids)
            : $query;
    }

    private function productionQuery(bool $consolidated, array $ids): Builder
    {
        $query = ProductionOrder::query();

        return $consolidated
            ? $this->applyConsolidated($query->withoutGlobalScope('business_unit'), $ids)
            : $query;
    }

    private function applyConsolidated(Builder $query, array $ids): Builder
    {
        if ($ids === []) {
            return $query;
        }

        return $query->where(function (Builder $businessQuery) use ($ids) {
            $businessQuery->whereIn('business_unit_id', $ids)
                ->orWhereNull('business_unit_id');
        });
    }

    private function allowedBusinessUnitIds(?User $user): array
    {
        if (! $user) {
            return BusinessUnit::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $assigned = $user->businessUnits()->pluck('business_units.id')->map(fn ($id) => (int) $id)->all();

        return $assigned !== []
            ? $assigned
            : BusinessUnit::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function businessBreakdown(
        Carbon $from,
        Carbon $to,
        bool $consolidated,
        array $allowedIds
    ): array {
        if (! $consolidated) {
            return [];
        }

        return BusinessUnit::query()
            ->active()
            ->when($allowedIds !== [], fn ($query) => $query->whereIn('id', $allowedIds))
            ->get()
            ->map(function (BusinessUnit $business) use ($from, $to) {
                $sales = Sale::query()
                    ->withoutGlobalScope('business_unit')
                    ->where('business_unit_id', $business->id)
                    ->whereIn('status', ['confirmed', 'delivered'])
                    ->whereBetween('created_at', [$from, $to])
                    ->get();

                $production = ProductionOrder::query()
                    ->withoutGlobalScope('business_unit')
                    ->where('business_unit_id', $business->id)
                    ->where('status', ProductionOrder::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$from, $to])
                    ->get();

                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'code' => $business->code,
                    'revenue_usd' => round((float) $sales->sum('usd_grand_total'), 2),
                    'sales_orders' => $sales->count(),
                    'completed_production_orders' => $production->count(),
                    'good_output' => round((float) $production->sum('quantity_produced'), 2),
                ];
            })
            ->all();
    }
}
