<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read-only release risks: these must not be "fixed" by manufacturing or
 * financial guesses. Run before approving any factory go-live.
 *
 * Uses raw query-builder tables to report all business units regardless of
 * an operator's selected workspace. Never mutates accounting or stock.
 */
class FactoryReleaseAuditService
{
    public function report(): array
    {
        $reviewBoms = DB::table('boms')
            ->whereNull('deleted_at')
            ->where('description', 'like', '[REVIEW REQUIRED]%')
            ->orderBy('id')
            ->get(['id', 'product_id', 'business_unit_id', 'code', 'status', 'is_active', 'description']);

        $unapproved = $reviewBoms->map(fn ($bom) => [
            'bom_id' => (int) $bom->id,
            'product_id' => (int) $bom->product_id,
            'business_unit_id' => $bom->business_unit_id === null ? null : (int) $bom->business_unit_id,
            'bom_code' => $bom->code,
            'status' => $bom->status,
            'is_active' => (bool) $bom->is_active,
            'reason' => $bom->description,
        ])->all();

        // An old version of this ERP marked non-cash shareholder allocation
        // credits "paid". Flag those records for review, not automatic rewrite.
        $legacyPayments = DB::table('profit_distribution_items as pdi')
            ->leftJoin('transactions as t', 't.id', '=', 'pdi.transaction_id')
            ->where('pdi.status', 'paid')
            ->where(function ($query) {
                $query->whereNull('t.id')
                    ->orWhere('t.is_cash', false);
            })
            ->orderBy('pdi.id')
            ->get([
                'pdi.id as distribution_item_id',
                'pdi.distribution_id',
                'pdi.shareholder_id',
                'pdi.amount',
                'pdi.payment_date',
                'pdi.transaction_id',
                't.is_cash',
            ])->map(fn ($row) => [
                'distribution_item_id' => (int) $row->distribution_item_id,
                'distribution_id' => (int) $row->distribution_id,
                'shareholder_id' => (int) $row->shareholder_id,
                'amount' => (string) $row->amount,
                'payment_date' => $row->payment_date,
                'transaction_id' => $row->transaction_id === null ? null : (int) $row->transaction_id,
                'finding' => $row->transaction_id === null
                    ? 'Paid without linked payment transaction'
                    : 'Paid status on non-cash shareholder allocation',
            ])->all();

        // Only canonical actual material consumption counts toward realized
        // production cost. Confirmed orders without it must not fund payouts.
        $missingActual = DB::table('sales as s')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'confirmed')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('production_material_consumptions as pmc')
                    ->where(function ($q) {
                        $q->whereColumn('pmc.sale_id', 's.id')
                            ->orWhere(function ($byProduction) {
                                $byProduction->whereNotNull('s.production_order_id')
                                    ->whereColumn('pmc.production_order_id', 's.production_order_id');
                            });
                    });
            })
            ->orderBy('s.id')
            ->get(['s.id', 's.sale_no', 's.sale_date', 's.business_unit_id'])
            ->map(fn ($sale) => [
                'sale_id' => (int) $sale->id,
                'sale_no' => $sale->sale_no,
                'sale_date' => $sale->sale_date,
                'business_unit_id' => $sale->business_unit_id === null ? null : (int) $sale->business_unit_id,
            ])->all();

        $overlaps = DB::table('profit_distributions as a')
            ->join('profit_distributions as b', function ($join) {
                $join->on('a.id', '<', 'b.id')
                    ->whereColumn('a.period_start', '<=', 'b.period_end')
                    ->whereColumn('a.period_end', '>=', 'b.period_start');
            })
            ->whereIn('a.status', ['approved', 'distributed'])
            ->whereIn('b.status', ['approved', 'distributed'])
            ->orderBy('a.id')
            ->get(['a.id as first_id', 'b.id as second_id'])
            ->map(fn ($row) => [
                'first_distribution_id' => (int) $row->first_id,
                'second_distribution_id' => (int) $row->second_id,
            ])->all();

        return [
            'status' => count($unapproved) + count($legacyPayments) + count($missingActual) + count($overlaps) === 0
                ? 'ready_for_manual_signoff'
                : 'review_required',
            'automatic_release_approval' => false,
            'explanation' => 'GitHub checks cannot certify missing carton measurements, physical stock, or historic payment evidence.',
            'counts' => [
                'review_required_boms' => count($unapproved),
                'legacy_non_cash_marked_paid' => count($legacyPayments),
                'confirmed_sales_without_actual_consumption' => count($missingActual),
                'overlapping_processed_distributions' => count($overlaps),
            ],
            'review_required_boms' => $unapproved,
            'legacy_non_cash_marked_paid' => $legacyPayments,
            'confirmed_sales_without_actual_consumption' => $missingActual,
            'overlapping_processed_distributions' => $overlaps,
        ];
    }
}
