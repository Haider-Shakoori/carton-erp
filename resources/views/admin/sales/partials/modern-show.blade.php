@php
    $soxPs = $profitSummary ?? [];
    $soxCurrencyCode = $currencyCode ?? ($sale->currency->code ?? 'AFN');
    $soxCurrencySymbol = $currencySymbol ?? ($sale->currency->symbol ?? ($soxCurrencyCode === 'USD' ? '$' : '؋'));
    $soxExchangeRate = max((float) ($exchangeRate ?? $sale->exchange_rate ?? 1), 0.000001);
    $soxIsUsd = $soxCurrencyCode === 'USD';
    $soxActualAvailable = (bool) ($soxPs['actual_available'] ?? false);
    $soxEstimatedCost = $soxIsUsd
        ? (float) ($soxPs['estimated_cost_usd'] ?? 0)
        : (float) ($soxPs['estimated_cost_afn'] ?? 0);
    $soxActualCost = $soxIsUsd
        ? (float) ($soxPs['actual_production_cost_usd'] ?? 0)
        : (float) ($soxPs['actual_production_cost_afn'] ?? 0);
    $soxEstimatedProfit = $soxIsUsd
        ? (float) ($soxPs['estimated_profit_usd'] ?? 0)
        : (float) ($soxPs['estimated_profit_afn'] ?? 0);
    $soxActualProfit = $soxIsUsd
        ? (float) ($soxPs['actual_profit_usd'] ?? 0)
        : (float) ($soxPs['actual_profit_afn'] ?? 0);
    $soxGrandTotal = $soxIsUsd
        ? (float) ($sale->usd_grand_total ?? 0)
        : (float) ($sale->grand_total ?? 0);
    $soxMargin = $soxActualAvailable
        ? (float) ($soxPs['actual_margin_percentage'] ?? 0)
        : ($soxGrandTotal > 0 ? ($soxEstimatedProfit / $soxGrandTotal) * 100 : 0);

    $soxProductionStatus = 'Not Started';
    $soxProductionClass = 'pending';
    if ($sale->is_produced) {
        $soxProductionStatus = 'Completed';
        $soxProductionClass = 'done';
    } elseif ($sale->productionOrder && $sale->productionOrder->status === 'in_progress') {
        $soxProductionStatus = 'In Progress';
        $soxProductionClass = 'working';
    } elseif ($sale->productionOrder && $sale->productionOrder->status === 'pending') {
        $soxProductionStatus = 'Pending';
    }

    $soxBomBreakdowns = [];
    foreach ($sale->items as $soxLine) {
        $soxRows = [];
        $soxTotalMaterialCost = 0.0;
        $soxSnapshot = is_array($soxLine->manual_bom_snapshot ?? null) ? $soxLine->manual_bom_snapshot : [];

        if (count($soxSnapshot) > 0) {
            foreach ($soxSnapshot as $soxRow) {
                $soxUsage = (float) ($soxRow['kg_per_finished_unit'] ?? 0);
                $soxUsageWithWaste = (float) ($soxRow['kg_with_wastage'] ?? $soxUsage);
                $soxRate = $soxIsUsd
                    ? (float) ($soxRow['landed_cost_usd_per_kg'] ?? 0)
                    : (float) ($soxRow['per_gram_rate'] ?? 0);
                $soxLineCost = $soxIsUsd
                    ? (float) ($soxRow['physical_cost_usd'] ?? ($soxUsageWithWaste * $soxRate))
                    : (float) ($soxRow['physical_cost_usd'] ?? 0) * $soxExchangeRate;

                $soxTotalMaterialCost += $soxLineCost;
                $soxRows[] = [
                    'material' => $soxRow['material_name'] ?? 'Material',
                    'usage' => $soxUsage,
                    'usage_with_waste' => $soxUsageWithWaste,
                    'unit' => 'kg',
                    'rate' => $soxRate,
                    'total' => $soxLineCost,
                    'remark' => ($soxRow['formula_type'] ?? 'manual') === 'adhesive_mix'
                        ? 'Adhesive / mixing material'
                        : 'Manual BOM snapshot',
                ];
            }
        } elseif ($soxLine->bom) {
            foreach ($soxLine->bom->items as $soxBomItem) {
                $soxUsage = (float) ($soxBomItem->quantity ?? 0);
                $soxWaste = (float) ($soxBomItem->wastage_percentage ?? 0);
                $soxUsageWithWaste = $soxUsage * (1 + ($soxWaste / 100));
                $soxRate = $soxIsUsd
                    ? (float) ($soxBomItem->cost_per_unit_usd ?? 0)
                    : (float) ($soxBomItem->cost_per_unit_afn ?? 0);
                if ($soxRate <= 0) {
                    $soxRate = $soxIsUsd
                        ? ((float) ($soxBomItem->cost_per_unit_afn ?? 0) / $soxExchangeRate)
                        : ((float) ($soxBomItem->cost_per_unit_usd ?? 0) * $soxExchangeRate);
                }
                $soxLineCost = $soxUsageWithWaste * $soxRate;
                $soxTotalMaterialCost += $soxLineCost;
                $soxRows[] = [
                    'material' => $soxBomItem->material->name ?? 'Material',
                    'usage' => $soxUsage,
                    'usage_with_waste' => $soxUsageWithWaste,
                    'unit' => $soxBomItem->unit ?: 'kg',
                    'rate' => $soxRate,
                    'total' => $soxLineCost,
                    'remark' => ucfirst(str_replace('_', ' ', (string) ($soxBomItem->resolvedComponentType() ?? $soxBomItem->formula_type ?? 'material'))),
                ];
            }
        }

        $soxStandardPrice = (float) ($soxLine->base_price ?: $soxLine->original_unit_price ?: $soxLine->unit_price);
        $soxEffectivePrice = (float) $soxLine->unit_price;
        $soxEstimatedCostUnit = $soxIsUsd
            ? (float) ($soxLine->cost_per_unit_usd ?? 0)
            : (float) ($soxLine->cost_per_unit_usd ?? 0) * $soxExchangeRate;
        $soxEstimatedProfitUnit = $soxEffectivePrice - $soxEstimatedCostUnit;
        $soxItemMargin = $soxEffectivePrice > 0 ? ($soxEstimatedProfitUnit / $soxEffectivePrice) * 100 : 0;
        $soxManualOverride = ($soxLine->price_adjustment_type ?? null) === 'manual';

        $soxBomBreakdowns[(string) $soxLine->id] = [
            'product' => $soxLine->product->name ?? 'Product',
            'bom' => $soxLine->bom->code ?? (count($soxSnapshot) > 0 ? 'Manual BOM Snapshot' : 'No BOM'),
            'quantity' => (float) $soxLine->qty,
            'unit' => $soxLine->product->unit ?? 'pcs',
            'standard_price' => $soxStandardPrice,
            'manual_override' => $soxManualOverride ? $soxEffectivePrice : null,
            'effective_price' => $soxEffectivePrice,
            'estimated_cost' => $soxEstimatedCostUnit,
            'estimated_profit' => $soxEstimatedProfitUnit * (float) $soxLine->qty,
            'margin' => $soxItemMargin,
            'status' => ucfirst((string) $sale->status),
            'rows' => $soxRows,
            'total' => $soxTotalMaterialCost,
        ];
    }
@endphp

<style>
    .sox-shell { --sox-primary:#4f46e5; --sox-primary-soft:#eef2ff; --sox-border:#e7eaf3; --sox-text:#16213e; --sox-muted:#72809d; --sox-success:#0f9f6e; --sox-success-soft:#ecfdf5; --sox-warning:#d97706; --sox-warning-soft:#fff7e6; }
    .sox-shell { color:var(--sox-text); }
    .sox-header { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1rem; }
    .sox-title-row { display:flex; align-items:center; flex-wrap:wrap; gap:.65rem; }
    .sox-title { margin:0; font-size:1.72rem; font-weight:850; letter-spacing:-.035em; }
    .sox-title strong { color:var(--sox-primary); }
    .sox-meta { color:var(--sox-muted); font-size:.78rem; margin-top:.25rem; }
    .sox-pill { display:inline-flex; align-items:center; gap:.32rem; padding:.32rem .72rem; border-radius:999px; font-size:.69rem; font-weight:750; white-space:nowrap; }
    .sox-pill.status { background:#eef1f7; color:#44516d; }
    .sox-pill.pending { background:var(--sox-warning-soft); color:var(--sox-warning); border:1px solid #fed7aa; }
    .sox-pill.working { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
    .sox-pill.done { background:var(--sox-success-soft); color:#047857; border:1px solid #a7f3d0; }
    .sox-actions { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; justify-content:flex-end; }
    .sox-btn { border-radius:10px; min-height:38px; padding:.45rem .9rem; font-size:.76rem; font-weight:700; border:1px solid var(--sox-border); background:#fff; color:#34415c; display:inline-flex; align-items:center; gap:.45rem; }
    .sox-btn:hover { border-color:#c7cdf0; color:var(--sox-primary); }
    .sox-btn.primary { background:linear-gradient(135deg,#4f46e5,#5b35f5); border-color:transparent; color:#fff; box-shadow:0 7px 18px rgba(79,70,229,.18); }
    .sox-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.8rem; margin-bottom:1rem; }
    .sox-kpi { min-height:92px; background:#fff; border:1px solid var(--sox-border); border-radius:13px; padding:.95rem; display:flex; gap:.8rem; align-items:flex-start; box-shadow:0 4px 20px rgba(15,23,42,.025); }
    .sox-kpi-icon { width:38px; height:38px; border-radius:10px; background:var(--sox-primary-soft); color:var(--sox-primary); display:flex; align-items:center; justify-content:center; flex:0 0 auto; font-size:1rem; }
    .sox-kpi.success .sox-kpi-icon { background:var(--sox-success-soft); color:var(--sox-success); }
    .sox-kpi-label { color:var(--sox-muted); font-size:.68rem; margin-bottom:.18rem; }
    .sox-kpi-value { font-size:.9rem; font-weight:800; line-height:1.25; }
    .sox-kpi-value.total { color:var(--sox-success); font-size:1.12rem; }
    .sox-kpi-sub { color:var(--sox-muted); font-size:.66rem; margin-top:.18rem; }
    .sox-main-grid { display:grid; grid-template-columns:minmax(0,1fr) 285px; gap:.8rem; margin-bottom:1rem; }
    .sox-card { background:#fff; border:1px solid var(--sox-border); border-radius:13px; box-shadow:0 4px 20px rgba(15,23,42,.025); overflow:hidden; }
    .sox-card-head { padding:.9rem 1rem; display:flex; justify-content:space-between; gap:.8rem; align-items:center; border-bottom:1px solid var(--sox-border); }
    .sox-card-title { font-size:.93rem; font-weight:800; margin:0; display:flex; align-items:center; gap:.55rem; }
    .sox-card-title i { color:var(--sox-primary); }
    .sox-card-sub { font-size:.68rem; color:var(--sox-muted); margin-top:.12rem; }
    .sox-toolbar { display:flex; gap:.42rem; align-items:center; }
    .sox-search { width:280px; max-width:32vw; height:36px; border:1px solid var(--sox-border); border-radius:9px; padding:0 .75rem; font-size:.73rem; outline:none; }
    .sox-search:focus { border-color:#a5b4fc; box-shadow:0 0 0 3px rgba(79,70,229,.07); }
    .sox-table-wrap { overflow-x:auto; }
    .sox-table { width:100%; border-collapse:collapse; font-size:.71rem; }
    .sox-table th { padding:.7rem .65rem; background:#f8f9fd; color:#697792; font-weight:750; white-space:nowrap; border-bottom:1px solid var(--sox-border); }
    .sox-table td { padding:.7rem .65rem; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
    .sox-table tbody tr { transition:.18s ease; }
    .sox-table tbody tr:hover,.sox-table tbody tr.sox-selected { background:#f8f8ff; }
    .sox-product { font-weight:800; color:#27324b; }
    .sox-product-meta { font-size:.64rem; color:var(--sox-muted); margin-top:.14rem; }
    .sox-price-input { min-width:112px; max-width:125px; height:34px; font-size:.72rem; font-weight:700; }
    .sox-override-badge { display:block; width:max-content; max-width:125px; margin-top:.28rem; padding:.16rem .4rem; border-radius:6px; background:#ede9fe; color:#5b21b6; font-size:.56rem; font-weight:750; }
    .sox-positive { color:var(--sox-success); font-weight:800; }
    .sox-info-strip { margin:.65rem; border-radius:8px; padding:.56rem .72rem; background:#f1f3ff; color:#4f46e5; font-size:.66rem; display:flex; align-items:center; gap:.45rem; }
    .sox-order-summary { padding:1rem; }
    .sox-summary-row { display:flex; justify-content:space-between; gap:.7rem; padding:.5rem 0; font-size:.73rem; color:#53617b; }
    .sox-summary-row strong { color:#1e2a44; text-align:right; }
    .sox-summary-row.profit strong,.sox-summary-row.margin strong { color:var(--sox-success); }
    .sox-summary-note { background:#f1f3ff; color:#5b65b6; border-radius:9px; padding:.75rem; font-size:.64rem; margin-top:.7rem; display:flex; gap:.5rem; }
    .sox-lower-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:.8rem; align-items:start; }
    .sox-selected-card { background:#fff; border:1px solid var(--sox-border); border-radius:13px; overflow:hidden; box-shadow:0 4px 20px rgba(15,23,42,.025); position:sticky; top:1rem; }
    .sox-selected-head { padding:.85rem 1rem; border-bottom:1px solid var(--sox-border); font-size:.82rem; font-weight:800; display:flex; align-items:center; gap:.5rem; }
    .sox-selected-head i { color:var(--sox-primary); }
    .sox-selected-body { padding:.85rem; }
    .sox-selected-product { display:flex; align-items:center; gap:.7rem; padding:.7rem; border:1px solid var(--sox-border); border-radius:10px; background:#fbfcff; margin-bottom:.8rem; }
    .sox-selected-icon { width:42px; height:42px; border-radius:9px; display:flex; align-items:center; justify-content:center; background:#fff7ed; color:#b45309; flex:0 0 auto; font-size:1.15rem; }
    .sox-selected-name { font-size:.78rem; font-weight:850; color:#202b45; }
    .sox-selected-bom { color:var(--sox-muted); font-size:.62rem; margin-top:.12rem; }
    .sox-selected-status { margin-left:auto; background:var(--sox-success-soft); color:#047857; padding:.24rem .55rem; border-radius:999px; font-size:.58rem; font-weight:800; }
    .sox-selected-grid { display:grid; grid-template-columns:1fr 1fr; gap:.48rem .7rem; font-size:.68rem; }
    .sox-selected-grid .k { color:var(--sox-muted); }
    .sox-selected-grid .v { text-align:right; color:#293650; font-weight:700; }
    .sox-selected-grid .v.good { color:var(--sox-success); }
    .sox-selected-actions { display:grid; grid-template-columns:1fr 1fr; gap:.45rem; margin-top:.9rem; }
    .sox-selected-action { border:1px solid #e4e7ff; background:#f7f7ff; color:var(--sox-primary); border-radius:8px; padding:.55rem .5rem; font-size:.65rem; font-weight:750; text-align:center; }
    .sox-selected-empty { padding:2rem 1rem; text-align:center; color:var(--sox-muted); font-size:.7rem; }
    .sox-selected-empty i { display:block; font-size:1.5rem; color:#a5b4fc; margin-bottom:.5rem; }
    .sox-tabs-card { background:#fff; border:1px solid var(--sox-border); border-radius:13px; overflow:hidden; }
    .sox-tabs { display:flex; gap:.1rem; padding:0 .75rem; border-bottom:1px solid var(--sox-border); overflow-x:auto; }
    .sox-tab { border:0; background:transparent; color:#687791; padding:.8rem .85rem .7rem; font-size:.72rem; font-weight:720; white-space:nowrap; border-bottom:2px solid transparent; display:flex; align-items:center; gap:.42rem; }
    .sox-tab.active { color:var(--sox-primary); border-bottom-color:var(--sox-primary); }
    .sox-tab-pane { display:none; padding:.85rem; }
    .sox-tab-pane.active { display:block; }
    .sox-overview-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
    .sox-detail-card { border:1px solid var(--sox-border); border-radius:10px; padding:.9rem; }
    .sox-detail-title { font-weight:800; font-size:.76rem; margin-bottom:.7rem; display:flex; gap:.45rem; align-items:center; }
    .sox-detail-title i { color:var(--sox-primary); }
    .sox-detail-grid { display:grid; grid-template-columns:1fr 1.25fr; gap:.4rem .75rem; font-size:.69rem; }
    .sox-detail-grid .k { color:var(--sox-muted); }
    .sox-detail-grid .v { color:#293650; font-weight:650; }
    .sox-empty { padding:2.25rem 1rem; text-align:center; color:var(--sox-muted); }
    .sox-empty i { display:block; font-size:1.6rem; color:#a5b4fc; margin-bottom:.5rem; }
    .sox-bom-head { display:flex; align-items:center; flex-wrap:wrap; gap:.55rem; margin-bottom:.75rem; }
    .sox-bom-name { font-size:.82rem; font-weight:800; }
    .sox-bom-chip { background:#eafaf2; color:#087a56; border-radius:999px; padding:.24rem .6rem; font-size:.6rem; font-weight:700; }
    .sox-bom-total { margin-left:auto; background:#eafaf2; color:#087a56; border-radius:999px; padding:.32rem .65rem; font-size:.7rem; font-weight:800; }
    .sox-material-table { width:100%; border-collapse:collapse; font-size:.68rem; }
    .sox-material-table th { background:#f8f9fd; color:#697792; padding:.55rem; white-space:nowrap; }
    .sox-material-table td { padding:.5rem .55rem; border-bottom:1px solid #f1f3f7; }
    .sox-cost-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.65rem; }
    .sox-cost-box { border:1px solid var(--sox-border); border-radius:10px; padding:.9rem; }
    .sox-cost-box .k { font-size:.64rem; color:var(--sox-muted); }
    .sox-cost-box .v { font-size:1rem; font-weight:850; margin-top:.3rem; }
    .sox-notes { background:#f6f5ff; border-radius:10px; padding:.9rem 1rem; color:#4d5873; font-size:.7rem; line-height:1.65; }
    .sox-drawer.offcanvas { width:min(720px,94vw)!important; }
    .sox-drawer .offcanvas-header { padding:1rem 1.1rem; border-bottom:1px solid var(--sox-border); }
    .sox-drawer .offcanvas-title { font-weight:850; }
    .sox-mode-switch { display:grid; grid-template-columns:1fr 1fr; gap:.4rem; padding:.75rem 1rem; }
    .sox-mode-btn { border:1px solid var(--sox-border); background:#f7f8fc; color:#55617a; border-radius:9px; padding:.55rem; font-size:.73rem; font-weight:750; }
    .sox-mode-btn.active { color:var(--sox-primary); border-color:#818cf8; background:#fff; box-shadow:0 0 0 2px rgba(79,70,229,.07); }
    .sox-drawer-host { padding:0 1rem 1rem; }
    .sox-drawer-host .sale-section { box-shadow:none; border:0; margin:0; }
    .sox-drawer-host .sale-section .section-header { display:none; }
    .sox-drawer-host .sale-section .section-body { padding:.5rem 0; }
    .sox-drawer-host .row > [class*="col-md-"] { margin-bottom:.45rem; }
    .sox-row-action { width:32px; height:32px; border:1px solid var(--sox-border); border-radius:8px; background:#fff; color:#586681; }
    @media (max-width:1200px){ .sox-lower-grid{grid-template-columns:1fr}.sox-selected-card{position:static}.sox-summary-grid{grid-template-columns:repeat(3,1fr)} .sox-main-grid{grid-template-columns:1fr} .sox-order-summary{display:grid;grid-template-columns:repeat(2,1fr);gap:0 1rem}.sox-summary-note{grid-column:1/-1} }
    @media (max-width:768px){ .sox-header{flex-direction:column}.sox-actions{justify-content:flex-start}.sox-summary-grid{grid-template-columns:1fr 1fr}.sox-search{max-width:none;width:100%}.sox-toolbar{width:100%;flex-wrap:wrap}.sox-card-head{align-items:flex-start;flex-direction:column}.sox-overview-grid,.sox-cost-grid{grid-template-columns:1fr}.sox-title{font-size:1.4rem} }
</style>

<div class="sox-shell" id="modernSaleOrderUi">
    <div class="sox-header">
        <div>
            <div class="sox-title-row">
                <h1 class="sox-title">Sale Order <strong>#{{ $sale->sale_no }}</strong></h1>
                <span class="sox-pill status"><i class="bi bi-file-earmark-text"></i>{{ ucfirst($sale->status) }}</span>
                <span class="sox-pill {{ $soxProductionClass }}"><i class="bi bi-record-circle"></i>Production: {{ $soxProductionStatus }}</span>
            </div>
            <div class="sox-meta">
                Created {{ $sale->created_at?->format('M d, Y') ?? '-' }}
                <span class="mx-1">·</span>
                Last updated {{ $sale->updated_at?->diffForHumans() ?? '-' }}
            </div>
        </div>
        <div class="sox-actions">
            <a href="{{ route('admin.sales.index') }}" class="sox-btn text-decoration-none"><i class="bi bi-arrow-left"></i>Back</a>

            @if($sale->items->isNotEmpty())
                <div class="dropdown">
                    <button class="sox-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-printer"></i>Print
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item small" target="_blank" href="{{ route('admin.sales.quotation', $sale->id) }}"><i class="bi bi-file-earmark-text me-2"></i>Print Quotation</a></li>
                        <li><a class="dropdown-item small" target="_blank" href="{{ route('admin.sales.print', $sale->id) }}"><i class="bi bi-receipt me-2"></i>Print Invoice</a></li>
                    </ul>
                </div>
            @endif

            @can('update sales')
                @if($sale->status === 'draft')
                    <button type="button" class="sox-btn primary" onclick="updateStatus('confirmed')"><i class="bi bi-check2"></i>Confirm Order</button>
                @elseif($sale->status === 'confirmed' && !$sale->is_produced && $sale->productionOrder)
                    <a href="{{ route('production-orders.show', $sale->productionOrder) }}" class="sox-btn primary text-decoration-none"><i class="bi bi-gear"></i>Production</a>
                @elseif($sale->is_produced && $sale->status !== 'delivered')
                    <form action="{{ route('admin.sales.deliver', $sale->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="sox-btn primary" onclick='return confirm(@json(__('ui.deliver_order_confirm')))'><i class="bi bi-truck"></i>Deliver</button>
                    </form>
                @endif
            @endcan

            <div class="dropdown">
                <button class="sox-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i>More</button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    @if($sale->productionOrder)
                        <li><a class="dropdown-item small" href="{{ route('production-orders.show', $sale->productionOrder) }}"><i class="bi bi-gear me-2"></i>View Production</a></li>
                    @endif
                    @if($sale->status === 'delivered' && $sale->gatePass)
                        <li><a class="dropdown-item small" target="_blank" href="{{ route('admin.sales.gate-pass', $sale->id) }}"><i class="bi bi-door-open me-2"></i>Gate Pass</a></li>
                    @endif
                    @can('delete sales')
                        @if($sale->status !== 'delivered')
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item small text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="bi bi-trash3 me-2"></i>Delete Sale</button></li>
                        @endif
                    @endcan
                </ul>
            </div>
        </div>
    </div>

    <div class="sox-summary-grid">
        <div class="sox-kpi">
            <div class="sox-kpi-icon"><i class="bi bi-person"></i></div>
            <div><div class="sox-kpi-label">Customer</div><div class="sox-kpi-value">{{ $sale->customer->name ?? 'No Customer' }}</div><div class="sox-kpi-sub">Customer account</div></div>
        </div>
        <div class="sox-kpi">
            <div class="sox-kpi-icon" style="background:#eff6ff;color:#2563eb"><i class="bi bi-calendar3"></i></div>
            <div><div class="sox-kpi-label">Order Date</div><div class="sox-kpi-value">{{ $sale->sale_date ? date('M d, Y', strtotime($sale->sale_date)) : '-' }}</div></div>
        </div>
        <div class="sox-kpi">
            <div class="sox-kpi-icon"><i class="bi bi-currency-exchange"></i></div>
            <div><div class="sox-kpi-label">Currency / Exchange Rate</div><div class="sox-kpi-value">{{ $soxCurrencyCode }}</div><div class="sox-kpi-sub">1 USD = {{ number_format($soxExchangeRate, 4) }} AFN</div></div>
        </div>
        <div class="sox-kpi success">
            <div class="sox-kpi-icon"><i class="bi bi-box-seam"></i></div>
            <div><div class="sox-kpi-label">Total Items</div><div class="sox-kpi-value">{{ $sale->items->count() }} Products</div></div>
        </div>
        <div class="sox-kpi success">
            <div class="sox-kpi-icon"><i class="bi bi-cash-stack"></i></div>
            <div><div class="sox-kpi-label">Grand Total</div><div class="sox-kpi-value total">{{ $soxCurrencySymbol }} {{ number_format($soxGrandTotal, 2) }}</div></div>
        </div>
    </div>

    <div class="sox-main-grid">
        <section class="sox-card">
            <div class="sox-card-head">
                <div>
                    <h2 class="sox-card-title"><i class="bi bi-box"></i>Sale Items</h2>
                    <div class="sox-card-sub">Carton products in this order</div>
                </div>
                <div class="sox-toolbar">
                    <input type="search" class="sox-search" id="soxItemSearch" placeholder="Search products, SKU, or BOM...">
                    @if($sale->status === 'draft')
                        <button class="sox-btn primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#soxAddCartonDrawer"><i class="bi bi-plus-lg"></i>Add Carton</button>
                    @endif
                </div>
            </div>

            <div class="sox-table-wrap">
                <table class="sox-table" id="soxSaleItemsTable">
                    <thead>
                    <tr>
                        <th style="width:34px"></th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Standard Price<br><span class="fw-normal">({{ $soxCurrencyCode }})</span></th>
                        <th>Manual Override Price<br><span class="fw-normal">({{ $soxCurrencyCode }})</span></th>
                        <th>Effective Selling Price<br><span class="fw-normal">({{ $soxCurrencyCode }})</span></th>
                        <th>Estimated Cost<br><span class="fw-normal">({{ $soxCurrencyCode }})</span></th>
                        <th>Estimated Profit<br><span class="fw-normal">({{ $soxCurrencyCode }})</span></th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sale->items as $soxLine)
                        @php
                            $soxManualOverride = ($soxLine->price_adjustment_type ?? 'none') === 'manual';
                            $soxLineSnapshot = is_array($soxLine->manual_bom_snapshot ?? null) ? $soxLine->manual_bom_snapshot : [];
                            $soxStandardPriceAfn = 0.0;
                            if (count($soxLineSnapshot) > 0) {
                                foreach ($soxLineSnapshot as $soxPriceRow) {
                                    $soxStandardPriceAfn += (float) ($soxPriceRow['row_net_rate'] ?? $soxPriceRow['final_rate_afn'] ?? 0);
                                }
                            } elseif ($soxLine->bom) {
                                $soxStandardPriceAfn = (float) ($soxLine->bom->selling_price_afn ?? 0);
                            }
                            $soxStandardPrice = $soxStandardPriceAfn > 0
                                ? ($soxIsUsd ? $soxStandardPriceAfn / $soxExchangeRate : $soxStandardPriceAfn)
                                : (float) ($soxLine->base_price ?: $soxLine->original_unit_price ?: $soxLine->unit_price);
                            $soxEffectivePrice = (float) $soxLine->unit_price;
                            $soxQty = max((float) $soxLine->qty, 0.000001);
                            $soxEstimatedLineCost = (float) ($soxLine->total_cost_usd ?? 0) * ($soxIsUsd ? 1 : $soxExchangeRate);
                            $soxEstimatedCostUnit = $soxEstimatedLineCost / $soxQty;
                            $soxLineRevenue = (float) ($soxLine->total ?? 0);
                            $soxEstimatedLineProfit = $soxLineRevenue - $soxEstimatedLineCost;
                        @endphp
                        <tr data-sox-search="{{ strtolower(($soxLine->product->name ?? '') . ' ' . ($soxLine->bom->code ?? '')) }}" data-sox-item-row="{{ $soxLine->id }}">
                            <td><input class="form-check-input sox-item-select" type="checkbox" value="{{ $soxLine->id }}" aria-label="Show BOM for {{ $soxLine->product->name ?? 'item' }}"></td>
                            <td>
                                <div class="sox-product">{{ $soxLine->product->name ?? '-' }}</div>
                                <div class="sox-product-meta">{{ $soxLine->bom->code ?? (is_array($soxLine->manual_bom_snapshot) ? 'Manual BOM' : 'No BOM') }}</div>
                            </td>
                            <td><strong>{{ number_format((float) $soxLine->qty, 2) }}</strong><div class="sox-product-meta">{{ $soxLine->product->unit ?? 'pcs' }}</div></td>
                            <td>{{ number_format($soxStandardPrice, 4) }}</td>
                            <td>
                                @if($sale->status === 'draft')
                                    <input type="number" class="form-control form-control-sm sox-price-input sox-manual-price"
                                           data-id="{{ $soxLine->id }}" min="0.0001" step="0.0001"
                                           value="{{ $soxManualOverride ? number_format($soxEffectivePrice, 4, '.', '') : '' }}"
                                           placeholder="Use system price">
                                    @if($soxManualOverride)
                                        <span class="sox-override-badge"><i class="bi bi-check-circle me-1"></i>Manual Override Applied</span>
                                    @endif
                                @else
                                    <span class="{{ $soxManualOverride ? 'fw-bold text-primary' : 'text-muted' }}">
                                        {{ $soxManualOverride ? number_format($soxEffectivePrice, 4) : '—' }}
                                    </span>
                                @endif
                            </td>
                            <td class="sox-positive">{{ number_format($soxEffectivePrice, 4) }}</td>
                            <td>{{ number_format($soxEstimatedCostUnit, 4) }}</td>
                            <td class="sox-positive">{{ number_format($soxEstimatedLineProfit, 2) }}</td>
                            <td><span class="sox-pill status">{{ ucfirst($sale->status) }}</span></td>
                            <td>
                                <div class="dropdown">
                                    <button class="sox-row-action" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li><button type="button" class="dropdown-item small sox-view-bom" data-item-id="{{ $soxLine->id }}"><i class="bi bi-boxes me-2"></i>View BOM Breakdown</button></li>
                                        @if($sale->status === 'draft')
                                            <li><button type="button" class="dropdown-item small text-danger remove-item" data-id="{{ $soxLine->id }}"><i class="bi bi-x-lg me-2"></i>Remove Item</button></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="sox-empty"><i class="bi bi-box-seam"></i>No items have been added yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($sale->status === 'draft')
                <div class="sox-info-strip"><i class="bi bi-info-circle"></i>Operators can manually override an item price. Leave the field empty to use the BOM/system price. The override changes selling price only; physical BOM/inventory cost is unchanged.</div>
            @endif
        </section>

        <aside class="sox-card">
            <div class="sox-card-head"><h2 class="sox-card-title"><i class="bi bi-receipt"></i>Order Summary</h2></div>
            <div class="sox-order-summary">
                <div class="sox-summary-row"><span>Subtotal</span><strong>{{ $soxCurrencySymbol }} {{ number_format($soxGrandTotal, 2) }}</strong></div>
                <div class="sox-summary-row"><span>{{ $soxActualAvailable ? 'Actual Production Cost' : 'Estimated Production Cost' }}</span><strong>{{ $soxCurrencySymbol }} {{ number_format($soxActualAvailable ? $soxActualCost : $soxEstimatedCost, 2) }}</strong></div>
                <div class="sox-summary-row profit"><span>{{ $soxActualAvailable ? 'Actual Profit' : 'Estimated Profit' }}</span><strong>{{ $soxCurrencySymbol }} {{ number_format($soxActualAvailable ? $soxActualProfit : $soxEstimatedProfit, 2) }}</strong></div>
                <div class="sox-summary-row margin"><span>Margin</span><strong>{{ number_format($soxMargin, 1) }}%</strong></div>
                <div class="sox-summary-note"><i class="bi bi-info-circle"></i><span>{{ $soxActualAvailable ? 'Totals use actual FIFO production cost.' : 'Profit is estimated from the current BOM and inventory cost. Final profit updates after actual production consumption.' }}</span></div>
            </div>
        </aside>
    </div>

    <div class="sox-lower-grid">
    <section class="sox-tabs-card">
        <div class="sox-tabs" role="tablist">
            <button class="sox-tab active" type="button" data-sox-tab="overview"><i class="bi bi-file-earmark-text"></i>Overview</button>
            <button class="sox-tab" type="button" data-sox-tab="bom"><i class="bi bi-boxes"></i>BOM Breakdown</button>
            <button class="sox-tab" type="button" data-sox-tab="costing"><i class="bi bi-layers"></i>Costing</button>
            <button class="sox-tab" type="button" data-sox-tab="production"><i class="bi bi-gear"></i>Production</button>
            <button class="sox-tab" type="button" data-sox-tab="pricing"><i class="bi bi-card-text"></i>Pricing Notes</button>
        </div>

        <div class="sox-tab-pane active" data-sox-pane="overview">
            <div class="sox-overview-grid">
                <div class="sox-detail-card">
                    <div class="sox-detail-title"><i class="bi bi-file-earmark-text"></i>Order Information</div>
                    <div class="sox-detail-grid">
                        <div class="k">Order Number</div><div class="v">{{ $sale->sale_no }}</div>
                        <div class="k">Customer</div><div class="v">{{ $sale->customer->name ?? '-' }}</div>
                        <div class="k">Order Date</div><div class="v">{{ $sale->sale_date ? date('M d, Y', strtotime($sale->sale_date)) : '-' }}</div>
                        <div class="k">Status</div><div class="v">{{ ucfirst($sale->status) }}</div>
                        <div class="k">Production Status</div><div class="v">{{ $soxProductionStatus }}</div>
                        <div class="k">Remarks</div><div class="v">{{ $sale->notes ?: '—' }}</div>
                    </div>
                </div>
                <div class="sox-detail-card">
                    <div class="sox-detail-title"><i class="bi bi-info-circle"></i>Additional Information</div>
                    <div class="sox-detail-grid">
                        <div class="k">Currency</div><div class="v">{{ $soxCurrencyCode }}</div>
                        <div class="k">Exchange Rate</div><div class="v">1 USD = {{ number_format($soxExchangeRate, 4) }} AFN</div>
                        <div class="k">Total Items</div><div class="v">{{ $sale->items->count() }} Products</div>
                        <div class="k">Created By</div><div class="v">{{ $sale->createdBy->name ?? auth()->user()?->name ?? 'Admin' }}</div>
                        <div class="k">Last Updated</div><div class="v">{{ $sale->updated_at?->format('M d, Y H:i') ?? '-' }}</div>
                        <div class="k">Shipping</div><div class="v">{{ $sale->shipping_address ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sox-tab-pane" data-sox-pane="bom">
            <div id="soxBomEmpty" class="sox-empty"><i class="bi bi-boxes"></i><strong>Select an item above</strong><div class="mt-1">Check an item to view its exact BOM material breakdown here.</div></div>
            <div id="soxBomContent" style="display:none">
                <div class="sox-bom-head">
                    <div class="sox-bom-name" id="soxBomTitle">BOM Material Breakdown</div>
                    <span class="sox-bom-chip">Showing BOM for selected item</span>
                    <span class="sox-bom-total" id="soxBomTotal"></span>
                </div>
                <div class="table-responsive">
                    <table class="sox-material-table">
                        <thead><tr><th>#</th><th>Material</th><th class="text-end">Usage / Carton</th><th class="text-end">Usage + Waste</th><th>Unit</th><th class="text-end">Rate ({{ $soxCurrencyCode }})</th><th class="text-end">Total Cost ({{ $soxCurrencyCode }})</th><th>Remark</th></tr></thead>
                        <tbody id="soxBomRows"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="sox-tab-pane" data-sox-pane="costing">
            <div class="sox-cost-grid">
                <div class="sox-cost-box"><div class="k">Customer Quotation Total</div><div class="v">{{ $soxCurrencySymbol }} {{ number_format($soxGrandTotal, 2) }}</div></div>
                <div class="sox-cost-box"><div class="k">Estimated Production Cost</div><div class="v">{{ $soxCurrencySymbol }} {{ number_format($soxEstimatedCost, 2) }}</div></div>
                <div class="sox-cost-box"><div class="k">Actual FIFO Cost</div><div class="v">{{ $soxActualAvailable ? $soxCurrencySymbol . ' ' . number_format($soxActualCost, 2) : 'Pending' }}</div></div>
                <div class="sox-cost-box"><div class="k">{{ $soxActualAvailable ? 'Actual Profit' : 'Estimated Profit' }}</div><div class="v sox-positive">{{ $soxCurrencySymbol }} {{ number_format($soxActualAvailable ? $soxActualProfit : $soxEstimatedProfit, 2) }}</div></div>
            </div>
        </div>

        <div class="sox-tab-pane" data-sox-pane="production">
            <div id="soxProductionVarianceHost"></div>
            <div id="soxProductionEmpty" class="sox-empty"><i class="bi bi-gear"></i><strong>Production: {{ $soxProductionStatus }}</strong><div class="mt-1">Planned-vs-actual production detail appears here after real consumption is recorded.</div>
                @if($sale->productionOrder)
                    <a href="{{ route('production-orders.show', $sale->productionOrder) }}" class="sox-btn mt-3 text-decoration-none">Open Production Order</a>
                @endif
            </div>
        </div>

        <div class="sox-tab-pane" data-sox-pane="pricing">
            <div class="sox-notes">
                <strong class="d-block mb-2">Pricing rules for this order</strong>
                <ul class="mb-0 ps-3">
                    <li>Item selling prices may be manually overridden while the sale is in draft.</li>
                    <li>If the override field is empty, the line uses its BOM/system price.</li>
                    <li>A manual override changes the customer-facing selling price only; BOM material usage and production costing remain unchanged.</li>
                    <li>Estimated profit and margin use the effective selling price and current production-cost basis.</li>
                    <li>Actual profit becomes authoritative after FIFO inventory consumption is recorded.</li>
                </ul>
            </div>
        </div>
    </section>

    <aside class="sox-selected-card" id="soxSelectedItemCard">
        <div class="sox-selected-head"><i class="bi bi-cursor"></i>Selected Item Details</div>
        <div class="sox-selected-empty" id="soxSelectedEmpty">
            <i class="bi bi-box-seam"></i>
            <strong>Select an item above</strong>
            <div class="mt-1">Its BOM and pricing details will appear here.</div>
        </div>
        <div class="sox-selected-body" id="soxSelectedBody" style="display:none">
            <div class="sox-selected-product">
                <div class="sox-selected-icon"><i class="bi bi-box-seam"></i></div>
                <div class="flex-grow-1">
                    <div class="sox-selected-name" id="soxSelectedName">—</div>
                    <div class="sox-selected-bom" id="soxSelectedBom">—</div>
                </div>
                <span class="sox-selected-status" id="soxSelectedStatus">Draft</span>
            </div>
            <div class="sox-selected-grid">
                <div class="k">Quantity</div><div class="v" id="soxSelectedQty">—</div>
                <div class="k">Standard Price</div><div class="v" id="soxSelectedStandard">—</div>
                <div class="k">Manual Override Price</div><div class="v" id="soxSelectedOverride">—</div>
                <div class="k">Effective Selling Price</div><div class="v good" id="soxSelectedEffective">—</div>
                <div class="k">Estimated Cost / Unit</div><div class="v" id="soxSelectedCost">—</div>
                <div class="k">Estimated Profit</div><div class="v good" id="soxSelectedProfit">—</div>
                <div class="k">Margin</div><div class="v good" id="soxSelectedMargin">—</div>
            </div>
            <div class="sox-selected-actions">
                <button type="button" class="sox-selected-action" id="soxSelectedViewBom"><i class="bi bi-boxes me-1"></i>View Full BOM</button>
                @if($sale->status === 'draft')
                    <button type="button" class="sox-selected-action" id="soxSelectedEditPrice"><i class="bi bi-pencil me-1"></i>Edit Price</button>
                @else
                    <button type="button" class="sox-selected-action" disabled><i class="bi bi-lock me-1"></i>Price Locked</button>
                @endif
            </div>
        </div>
    </aside>
    </div>
</div>

@if($sale->status === 'draft')
<div class="offcanvas offcanvas-end sox-drawer" tabindex="-1" id="soxAddCartonDrawer" aria-labelledby="soxAddCartonDrawerLabel">
    <div class="offcanvas-header">
        <div>
            <h5 class="offcanvas-title" id="soxAddCartonDrawerLabel"><i class="bi bi-box me-2 text-primary"></i>Add Carton</h5>
            <div class="small text-muted">Add a carton to this sale order</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="sox-mode-switch">
        <button type="button" class="sox-mode-btn active" data-sox-add-mode="existing"><i class="bi bi-box me-2"></i>Existing BOM</button>
        <button type="button" class="sox-mode-btn" data-sox-add-mode="quick"><i class="bi bi-pencil me-2"></i>Quick Quotation</button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="sox-drawer-host" id="soxExistingBomHost"></div>
        <div class="sox-drawer-host" id="soxQuickQuoteHost" style="display:none"></div>
    </div>
</div>
@endif

<script>
    window.soxBomBreakdowns = @json($soxBomBreakdowns);

    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('modernSaleOrderUi');
        if (!root) return;

        function activateTab(name) {
            root.querySelectorAll('.sox-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.soxTab === name));
            root.querySelectorAll('.sox-tab-pane').forEach(pane => pane.classList.toggle('active', pane.dataset.soxPane === name));
        }

        root.querySelectorAll('.sox-tab').forEach(btn => {
            btn.addEventListener('click', () => activateTab(btn.dataset.soxTab));
        });

        const search = document.getElementById('soxItemSearch');
        if (search) {
            search.addEventListener('input', function () {
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('[data-sox-item-row]').forEach(row => {
                    row.style.display = !q || (row.dataset.soxSearch || '').includes(q) ? '' : 'none';
                });
            });
        }

        function renderSelectedItem(itemId) {
            const data = window.soxBomBreakdowns[String(itemId)];
            const empty = document.getElementById('soxSelectedEmpty');
            const body = document.getElementById('soxSelectedBody');
            if (!data || !empty || !body) return;

            document.getElementById('soxSelectedName').textContent = data.product || 'Product';
            document.getElementById('soxSelectedBom').textContent = data.bom || 'No BOM';
            document.getElementById('soxSelectedStatus').textContent = data.status || 'Draft';
            document.getElementById('soxSelectedQty').textContent = Number(data.quantity || 0).toLocaleString(undefined, {maximumFractionDigits:4}) + ' ' + (data.unit || 'pcs');
            document.getElementById('soxSelectedStandard').textContent = @json($soxCurrencyCode) + ' ' + number4(data.standard_price);
            document.getElementById('soxSelectedOverride').textContent = data.manual_override === null ? '— (Using system price)' : @json($soxCurrencyCode) + ' ' + number4(data.manual_override);
            document.getElementById('soxSelectedEffective').textContent = @json($soxCurrencyCode) + ' ' + number4(data.effective_price);
            document.getElementById('soxSelectedCost').textContent = @json($soxCurrencyCode) + ' ' + number4(data.estimated_cost);
            document.getElementById('soxSelectedProfit').textContent = @json($soxCurrencyCode) + ' ' + Number(data.estimated_profit || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            document.getElementById('soxSelectedMargin').textContent = Number(data.margin || 0).toFixed(1) + '%';
            empty.style.display = 'none';
            body.style.display = '';

            const viewBom = document.getElementById('soxSelectedViewBom');
            if (viewBom) viewBom.dataset.itemId = String(itemId);
            const editPrice = document.getElementById('soxSelectedEditPrice');
            if (editPrice) editPrice.dataset.itemId = String(itemId);
        }

        function renderBom(itemId) {
            const data = window.soxBomBreakdowns[String(itemId)];
            const empty = document.getElementById('soxBomEmpty');
            const content = document.getElementById('soxBomContent');
            const rows = document.getElementById('soxBomRows');

            if (!data || !Array.isArray(data.rows) || data.rows.length === 0) {
                content.style.display = 'none';
                empty.style.display = '';
                empty.innerHTML = '<i class="bi bi-exclamation-circle"></i><strong>No BOM breakdown is available for this line.</strong><div class="mt-1">The item may not have a linked BOM or saved manual BOM snapshot.</div>';
                activateTab('bom');
                return;
            }

            document.getElementById('soxBomTitle').textContent = 'BOM Material Breakdown — ' + data.product + ' (' + data.bom + ')';
            document.getElementById('soxBomTotal').textContent = @json($soxCurrencyCode) + ' ' + Number(data.total || 0).toFixed(4);
            rows.innerHTML = '';

            data.rows.forEach(function (row, index) {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + (index + 1) + '</td>' +
                    '<td><strong>' + escapeHtml(row.material || 'Material') + '</strong></td>' +
                    '<td class="text-end">' + number4(row.usage) + '</td>' +
                    '<td class="text-end">' + number4(row.usage_with_waste) + '</td>' +
                    '<td>' + escapeHtml(row.unit || '') + '</td>' +
                    '<td class="text-end">' + number4(row.rate) + '</td>' +
                    '<td class="text-end">' + number4(row.total) + '</td>' +
                    '<td>' + escapeHtml(row.remark || '') + '</td>';
                rows.appendChild(tr);
            });

            empty.style.display = 'none';
            content.style.display = '';
            activateTab('bom');
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = String(value ?? '');
            return div.innerHTML;
        }

        function number4(value) {
            return Number(value || 0).toLocaleString(undefined, {minimumFractionDigits:4, maximumFractionDigits:4});
        }

        document.querySelectorAll('.sox-item-select').forEach(check => {
            check.addEventListener('change', function () {
                document.querySelectorAll('.sox-item-select').forEach(other => {
                    if (other !== this) other.checked = false;
                });
                document.querySelectorAll('[data-sox-item-row]').forEach(row => row.classList.remove('sox-selected'));

                if (this.checked) {
                    const row = document.querySelector('[data-sox-item-row="' + this.value + '"]');
                    if (row) row.classList.add('sox-selected');
                    renderSelectedItem(this.value);
                    renderBom(this.value);
                }
            });
        });

        document.querySelectorAll('.sox-view-bom').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.itemId;
                const check = document.querySelector('.sox-item-select[value="' + id + '"]');
                if (check) {
                    check.checked = true;
                    check.dispatchEvent(new Event('change'));
                } else {
                    renderBom(id);
                }
            });
        });

        const legacy = document.getElementById('legacySaleOrderUi');
        if (legacy) {
            const productSelect = legacy.querySelector('#productSelect');
            const addItemsSection = productSelect ? productSelect.closest('.sale-section') : null;
            const quickSection = legacy.querySelector('#cartonSpecSection');
            if (addItemsSection) document.getElementById('soxExistingBomHost')?.appendChild(addItemsSection);
            if (quickSection) document.getElementById('soxQuickQuoteHost')?.appendChild(quickSection);

            const variance = legacy.querySelector('#productionVarianceSection');
            if (variance) {
                document.getElementById('soxProductionVarianceHost')?.appendChild(variance);
                const productionEmpty = document.getElementById('soxProductionEmpty');
                if (productionEmpty) productionEmpty.style.display = 'none';
            }
        }

        document.getElementById('soxSelectedViewBom')?.addEventListener('click', function () {
            const id = this.dataset.itemId;
            if (id) renderBom(id);
        });

        document.getElementById('soxSelectedEditPrice')?.addEventListener('click', function () {
            const id = this.dataset.itemId;
            const input = id ? document.querySelector('.sox-manual-price[data-id="' + id + '"]') : null;
            if (input) {
                input.scrollIntoView({behavior:'smooth', block:'center'});
                setTimeout(() => input.focus(), 350);
            }
        });

        document.querySelectorAll('[data-sox-add-mode]').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('[data-sox-add-mode]').forEach(b => b.classList.toggle('active', b === this));
                const existing = this.dataset.soxAddMode === 'existing';
                const a = document.getElementById('soxExistingBomHost');
                const b = document.getElementById('soxQuickQuoteHost');
                if (a) a.style.display = existing ? '' : 'none';
                if (b) b.style.display = existing ? 'none' : '';
            });
        });

        document.querySelectorAll('.sox-manual-price').forEach(field => {
            field.addEventListener('change', function () {
                const input = this;
                const itemId = input.dataset.id;
                const raw = input.value.trim();
                const payload = {_token: @json(csrf_token())};

                if (raw !== '') {
                    const value = Number(raw);
                    if (!Number.isFinite(value) || value <= 0) {
                        Swal.fire({icon:'warning', title:'Invalid price', text:'Manual unit price must be greater than zero, or leave it empty to use the system price.'});
                        return;
                    }
                    payload.unit_price = value;
                }

                input.disabled = true;
                $.ajax({
                    url: @json(url('admin/sales/item')) + '/' + itemId + '/manual-price',
                    method: 'PATCH',
                    data: payload,
                    success: function (response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            input.disabled = false;
                        }
                    },
                    error: function (xhr) {
                        input.disabled = false;
                        Swal.fire({icon:'error', title:'Error', text:xhr.responseJSON?.message || 'Could not update the selling price.'});
                    }
                });
            });
        });
    });
</script>
