{{-- resources/views/admin/bom/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'BOM Details')

@section('css')
    <style>
        .detail-label {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 1rem;
            font-weight: 500;
            color: #1a1a2e;
        }
        .cost-summary {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .cost-summary .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .cost-summary .cost-item:last-child {
            border-bottom: none;
        }
        .cost-summary .cost-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: #4f46e5;
        }
        .formula-breakdown {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1.25rem;
        }
        .formula-breakdown .step {
            padding: 0.5rem 0;
            border-bottom: 1px dashed #bae6fd;
        }
        .formula-breakdown .step:last-child {
            border-bottom: none;
        }
        .formula-breakdown .step-label {
            font-weight: 600;
            color: #0284c7;
        }
        .formula-breakdown .step-calculation {
            font-size: 0.85rem;
            color: #64748b;
            font-family: monospace;
        }
        .formula-breakdown .step-result {
            font-weight: 700;
            color: #0f766e;
        }
        .formula-breakdown .step-result.highlight {
            color: #4f46e5;
            font-size: 1.1rem;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.draft {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.archived {
            background: #f3f4f6;
            color: #6b7280;
        }
        .reel-info-box {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
        }
        .reel-info-box .reel-label {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
        }
        .reel-info-box .reel-value {
            font-weight: 700;
            color: #0284c7;
            font-size: 1rem;
        }
        .reel-info-box .reel-formula {
            font-size: 0.6rem;
            color: #94a3b8;
        }
        .calculation-step-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }
        .calculation-step-card:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .calculation-step-card .step-number {
            font-weight: 700;
            color: #4f46e5;
            font-size: 0.8rem;
        }
        .calculation-step-card .step-formula {
            font-family: monospace;
            font-size: 0.8rem;
            color: #475569;
        }
        .calculation-step-card .step-result {
            font-weight: 700;
            color: #0f766e;
            font-size: 0.9rem;
        }
        .calculation-step-card .step-result.final {
            color: #4f46e5;
            font-size: 1.1rem;
        }
        .material-currency-badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }
        .material-currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }
        .material-currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }
        .text-purple {
            color: #8b5cf6;
        }
        .bg-purple {
            background: #e0e7ff;
        }
        .bg-primary-light {
            background: #eef2ff;
        }
        .bg-success-light {
            background: #d1fae5;
        }
        .border-primary-light {
            border-color: #c7d2fe;
        }
        .row-net-rate-cell {
            font-family: monospace;
            font-size: 0.85rem;
        }
        .formula-params {
            font-size: 0.7rem;
            color: #64748b;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-eye me-2"></i>
                        {{ __('ui.bom') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        {{ $bom->code }} - {{ $bom->name }}
                        <span class="status-badge {{ $bom->status }} ms-2">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            {{ ucfirst($bom->status) }}
                        </span>
                        <span class="badge bg-primary ms-2">
                            {{ $bom->formula_type === 'cut_roll' ? '📐 Cut/Roll' : '📦 3D Carton' }}
                        </span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('bom.edit', $bom) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>
                    <a href="{{ route('bom.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- BOM Information --}}
            <div class="col-md-4">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i> BOM Information
                        </h6>
                    </div>
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.bom_code') }}</div>
                            <div class="detail-value">{{ $bom->code }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.version') }}</div>
                            <div class="detail-value">v{{ $bom->version }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.formula_type') }}</div>
                            <div class="detail-value">
                                <span class="badge bg-primary">
                                    {{ $bom->formula_type === 'cut_roll' ? '📐 Cut/Roll' : '📦 3D Carton' }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.product') }}</div>
                            <div class="detail-value">{{ $bom->product->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.description') }}</div>
                            <div class="detail-value">{{ $bom->description ?? 'No description' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.exchange_rate') }}</div>
                            <div class="detail-value">
                                1 USD = {{ number_format($bom->getUSDtoAFNRate(), 2) }} AFN
                                <span class="text-muted small ms-1">
                                    ({{ $bom->exchange_rate_source }})
                                </span>
                                @if($bom->exchange_rate_updated_at)
                                    <br>
                                    <span class="text-muted" style="font-size: 0.7rem;">
                                        Updated: {{ $bom->exchange_rate_updated_at->format('d M Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.work_percentage_plain') }}</div>
                            <div class="detail-value">{{ $bom->work_percentage ?? 40 }}%</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.profit_margin') }}</div>
                            <div class="detail-value">{{ $bom->profit_margin_percentage ?? 0 }}%</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.created_by') }}</div>
                            <div class="detail-value">{{ $bom->createdBy->name ?? 'Unknown' }}</div>
                        </div>
                        <div>
                            <div class="detail-label">{{ __('ui.created_at') }}</div>
                            <div class="detail-value">{{ $bom->created_at->format('d M, Y h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formula Parameters --}}
            <div class="col-md-4">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h6 class="mb-0">
                            <i class="bi bi-calculator me-2"></i> Formula Parameters
                        </h6>
                    </div>
                    <div class="p-3">
                        @php
                            // ─── GET DIMENSIONS FROM FIRST BOM ITEM ───
                            $firstItem = $bom->items->first();
                            $baseLength = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
                            $baseWidth = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
                            $baseHeight = $firstItem ? ($firstItem->height_inch ?? 0) : 0;

                            // Use first item's values as base
                            $length = $baseLength;
                            $width = $baseWidth;
                            $height = $baseHeight;
                            $paperGsm = $firstItem ? ($firstItem->paper_gsm ?? 0) : 0;
                            $perGramRate = $firstItem ? ($firstItem->per_gram_rate ?? 0) : 0;
                            $multiplicationLayer = $firstItem ? ($firstItem->multiplication_layer ?? 1) : 1;
                            $constant = $firstItem ? ($firstItem->formula_constant ?? 1550000) : 1550000;
                            $workPercentage = $firstItem ? ($firstItem->work_percentage ?? 40) : ($bom->work_percentage ?? 40);
                            $printCost = $firstItem ? ($firstItem->print ?? 0) : 0;

                            // Calculate reel dimensions
                            $reelLength = (($length + $width) * 2) + 4;
                            $reelHeight = $width + $height + 1;
                            $cartonBlankLength = ($length + $width + $height) * 2;
                            $cartonBlankWidth = ($width + $height) * 2;
                            $cartonsPerLength = ($reelLength > 0 && $cartonBlankLength > 0) ? floor($reelLength / $cartonBlankLength) : 0;
                            $cartonsPerWidth = ($reelHeight > 0 && $cartonBlankWidth > 0) ? floor($reelHeight / $cartonBlankWidth) : 0;
                            $cartonsPerRoll = $cartonsPerLength * $cartonsPerWidth;
                        @endphp

                        @if($bom->formula_type === 'cut_roll')
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.cut_length') }}</div>
                                <div class="detail-value">{{ number_format($bom->cut_length_inch ?? 0, 2) }} inches</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.cut_width') }}</div>
                                <div class="detail-value">{{ number_format($bom->cut_width_inch ?? 0, 2) }} inches</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">GRH</div>
                                <div class="detail-value">{{ $bom->grh ?? 0 }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.per_gram_rate') }}</div>
                                <div class="detail-value">؋{{ number_format($perGramRate, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.ply') }}</div>
                                <div class="detail-value">{{ $bom->ply ?? 1 }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.print_cost') }}</div>
                                <div class="detail-value">؋{{ number_format($bom->print ?? 0, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.multiplication_layer') }}</div>
                                <div class="detail-value">{{ $multiplicationLayer }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.multiplication_method') }}</div>
                                <div class="detail-value">
                                    <span class="badge bg-secondary">
                                        {{ $bom->multiplication_method === 'divide' ? 'Divide by 1000' : 'Multiply' }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.carton_length') }}</div>
                                <div class="detail-value">{{ number_format($length, 2) }} inches</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.carton_width') }}</div>
                                <div class="detail-value">{{ number_format($width, 2) }} inches</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.carton_height') }}</div>
                                <div class="detail-value">{{ number_format($height, 2) }} inches</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.reel_length') }}</div>
                                <div class="detail-value">
                                    {{ number_format($reelLength, 2) }} inches
                                    <span class="text-muted small ms-2">
                                        (({{ number_format($length, 2) }} + {{ number_format($width, 2) }}) × 2) + 4
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.reel_height') }}</div>
                                <div class="detail-value">
                                    {{ number_format($reelHeight, 2) }} inches
                                    <span class="text-muted small ms-2">
                                        ({{ number_format($width, 2) }} + {{ number_format($height, 2) }}) + 1
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.paper_gsm') }}</div>
                                <div class="detail-value">{{ $paperGsm }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.layers') }}</div>
                                <div class="detail-value">{{ $bom->layers ?? 1 }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.per_gram_rate') }}</div>
                                <div class="detail-value">؋{{ number_format($perGramRate, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.multiplication_layer') }}</div>
                                <div class="detail-value">{{ $multiplicationLayer }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.division_factor') }}</div>
                                <div class="detail-value">{{ $bom->division_factor ?? 1 }}</div>
                            </div>
                            <div class="mb-3">
                                <div class="detail-label">{{ __('ui.print_cost') }}</div>
                                <div class="detail-value">؋{{ number_format($firstItem ? ($firstItem->print ?? 0) : 0, 2) }}</div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.formula_constant') }}</div>
                            <div class="detail-value">{{ number_format($constant) }}</div>
                        </div>
                        <div>
                            <div class="detail-label">{{ __('ui.work_percentage_plain') }}</div>
                            <div class="detail-value">{{ $workPercentage }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="col-md-4">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h6 class="mb-0">
                            <i class="bi bi-gear me-2"></i> Quick Actions
                        </h6>
                    </div>
                    <div class="p-3">
                        <div class="d-grid gap-2">
                            <a href="{{ route('bom.edit', $bom) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Edit BOM
                            </a>
                            <button type="button" class="btn btn-outline-success" onclick="calculateBOM()">
                                <i class="bi bi-calculator me-1"></i> Calculate Cost
                            </button>
                            <form action="{{ route('bom.clone', $bom) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-files me-1"></i> {{ __('ui.clone_bom') }}
                                </button>
                            </form>
                            <form action="{{ route('bom.toggle-status', $bom) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-{{ $bom->status === 'active' ? 'warning' : 'success' }} w-100">
                                    <i class="bi bi-{{ $bom->status === 'active' ? 'archive' : 'check-circle' }} me-1"></i>
                                    {{ $bom->status === 'active' ? 'Archive' : 'Activate' }} BOM
                                </button>
                            </form>
                            @if($bom->status !== 'active')
                                <form action="{{ route('bom.destroy', $bom) }}" method="POST"
                                      onsubmit="return confirm('Delete this BOM? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-trash me-1"></i> {{ __('ui.delete_bom') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reel Dimensions Summary (for 3D Carton) --}}
        @if($bom->formula_type === 'carton_3d')
            <div class="table-card mt-4">
                <div class="card-header-custom">
                    <h6 class="mb-0">
                        <i class="bi bi-box me-2"></i> Reel & Carton Summary
                    </h6>
                </div>
                <div class="p-4">
                    @php
                        // ─── USE FIRST BOM ITEM DIMENSIONS ───
                        $firstItem = $bom->items->first();
                        $length = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
                        $width = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
                        $height = $firstItem ? ($firstItem->height_inch ?? 0) : 0;

                        $reelLength = (($length + $width) * 2) + 4;
                        $reelHeight = $width + $height + 1;
                        $cartonBlankLength = ($length + $width + $height) * 2;
                        $cartonBlankWidth = ($width + $height) * 2;

                        $cartonsPerLength = ($reelLength > 0 && $cartonBlankLength > 0) ? floor($reelLength / $cartonBlankLength) : 0;
                        $cartonsPerWidth = ($reelHeight > 0 && $cartonBlankWidth > 0) ? floor($reelHeight / $cartonBlankWidth) : 0;
                        $cartonsPerRoll = $cartonsPerLength * $cartonsPerWidth;

                        $reelArea = $reelLength * $reelHeight;
                        $usedArea = $cartonBlankLength * $cartonBlankWidth * $cartonsPerRoll;
                        $wastePercent = $reelArea > 0 ? (($reelArea - $usedArea) / $reelArea) * 100 : 0;
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="reel-info-box">
                                <div class="reel-label">{{ __('ui.reel_length_icon') }}</div>
                                <div class="reel-value">{{ number_format($reelLength, 2) }} in</div>
                                <div class="reel-formula">(({{ number_format($length, 2) }} + {{ number_format($width, 2) }}) × 2) + 4</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="reel-info-box">
                                <div class="reel-label">{{ __('ui.reel_height_icon') }}</div>
                                <div class="reel-value">{{ number_format($reelHeight, 2) }} in</div>
                                <div class="reel-formula">{{ number_format($width, 2) }} + {{ number_format($height, 2) }} + 1</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="reel-info-box" style="background: #eef2ff; border-color: #c7d2fe;">
                                <div class="reel-label">{{ __('ui.carton_blank_size') }}</div>
                                <div class="reel-value" style="color: #4f46e5;">
                                    {{ number_format($cartonBlankLength, 2) }} × {{ number_format($cartonBlankWidth, 2) }} in
                                </div>
                                <div class="reel-formula">Length = (L+W+H)×2, Width = (W+H)×2</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="reel-info-box" style="background: #ecfdf5; border-color: #6ee7b7;">
                                <div class="reel-label">{{ __('ui.cost_per_carton') }}</div>
                                <div class="reel-value" style="color: #059669; font-size: 1.2rem;">
                                    ؋{{ number_format($bom->total_cost_afn ?? 0, 4) }}
                                </div>
                                <div class="reel-formula">{{ __('ui.net_rate_formula') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Cost Summary --}}
        <div class="table-card mt-4">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-calculator me-2"></i> Cost Summary
                </h6>
            </div>
            <div class="p-4">
                @php
                    $exchangeRate = $bom->getUSDtoAFNRate();

                    // Calculate totals from items
                    $materialCostUsd = 0;
                    $materialCostAfn = 0;
                    foreach ($bom->items as $item) {
                        $materialCostUsd += floatval($item->total_cost_usd ?? 0);
                        $materialCostAfn += floatval($item->total_cost_afn ?? 0);
                    }

                    $workPercentage = floatval($bom->work_percentage ?? 40) / 100;
                    $workCostUsd = $materialCostUsd * $workPercentage;
                    $workCostAfn = $materialCostAfn * $workPercentage;

                    $totalCostUsd = $materialCostUsd + $workCostUsd;
                    $totalCostAfn = $materialCostAfn + $workCostAfn;

                    $profitMargin = floatval($bom->profit_margin_percentage ?? 0) / 100;
                    $sellingPriceUsd = $totalCostUsd * (1 + $profitMargin);
                    $sellingPriceAfn = $totalCostAfn * (1 + $profitMargin);
                    $profitUsd = $sellingPriceUsd - $totalCostUsd;
                    $profitAfn = $sellingPriceAfn - $totalCostAfn;
                @endphp

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.material_cost_usd') }}</div>
                        <div class="detail-value fw-bold text-primary">${{ number_format($materialCostUsd, 2) }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.material_cost_afn') }}</div>
                        <div class="detail-value fw-bold text-purple">؋{{ number_format($materialCostAfn, 2) }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.work_cost_afn') }}</div>
                        <div class="detail-value">؋{{ number_format($workCostAfn, 2) }}</div>
                        <div class="text-muted" style="font-size: 0.65rem;">{{ $bom->work_percentage ?? 40 }}% of material</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.total_cost_per_unit') }}</div>
                        <div class="detail-value fw-bold text-dark">؋{{ number_format($totalCostAfn, 2) }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.selling_price_per_unit') }}</div>
                        <div class="detail-value fw-bold text-success">؋{{ number_format($sellingPriceAfn, 2) }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.profit_per_unit') }}</div>
                        <div class="detail-value fw-bold text-primary">؋{{ number_format($profitAfn, 2) }}</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.profit_margin') }}</div>
                        <div class="detail-value fw-bold text-success">{{ $bom->profit_margin_percentage ?? 0 }}%</div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="detail-label">{{ __('ui.exchange_rate') }}</div>
                        <div class="detail-value fw-bold">1 USD = {{ number_format($exchangeRate, 2) }} AFN</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formula Breakdown --}}
        <div class="table-card mt-4" id="formulaBreakdownSection">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-list-steps me-2"></i> Formula Breakdown
                </h6>
            </div>
            <div class="p-4">
                <div class="formula-breakdown">
                    @php
                        // ─── USE FIRST ITEM FOR BASE CALCULATION ───
                        $firstItem = $bom->items->first();
                        $length = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
                        $width = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
                        $height = $firstItem ? ($firstItem->height_inch ?? 0) : 0;
                        $paperGsm = $firstItem ? ($firstItem->paper_gsm ?? 0) : 0;
                        $perGramRate = $firstItem ? ($firstItem->per_gram_rate ?? 0) : 0;
                        $multiplicationLayer = $firstItem ? ($firstItem->multiplication_layer ?? 1) : 1;
                        $constant = $firstItem ? ($firstItem->formula_constant ?? 1550000) : 1550000;
                        $printCost = $firstItem ? ($firstItem->print ?? 0) : 0;
                        $workPercentage = $firstItem ? ($firstItem->work_percentage ?? 40) : ($bom->work_percentage ?? 40);

                        $reelLength = (($length + $width) * 2) + 4;
                        $reelHeight = $width + $height + 1;
                        $divisionValue = $reelLength * $reelHeight * $paperGsm * $perGramRate;
                        $paperRate = $constant > 0 ? $divisionValue / $constant : 0;
                        $paperRateByLayers = $multiplicationLayer * $paperRate;
                        $workCost = $paperRateByLayers * ((float) $workPercentage / 100);
                        $netRate = $printCost + $paperRateByLayers + $workCost;
                    @endphp

                    @if($bom->formula_type === 'cut_roll')
                        <!-- Cut/Roll breakdown -->
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step1_multiplication') }}</span>
                                    <div class="step-calculation">{{ number_format($bom->cut_length_inch ?? 0, 2) }} × {{ number_format($bom->cut_width_inch ?? 0, 2) }} × {{ number_format($bom->formula_constant ?? 1550000) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($bom->calculated_total_area ?? 0, 2) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step2_paper_rate') }}</span>
                                    <div class="step-calculation">{{ number_format($bom->calculated_total_area ?? 0, 2) }} × {{ number_format($perGramRate, 2) }} × {{ $bom->grh ?? 0 }} × {{ $bom->ply ?? 1 }} ÷ {{ number_format($constant) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($bom->calculated_paper_rate ?? 0, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step3_layers') }}</span>
                                    <div class="step-calculation">{{ $multiplicationLayer }} × {{ number_format($bom->calculated_paper_rate ?? 0, 8) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($bom->calculated_paper_rate_by_layers ?? 0, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step4_work') }}</span>
                                    <div class="step-calculation">{{ number_format($bom->calculated_paper_rate_by_layers ?? 0, 8) }} × 0.40</div>
                                </div>
                                <span class="step-result">{{ number_format($bom->calculated_work_amount ?? 0, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step5_net_rate') }}</span>
                                    <div class="step-calculation">{{ number_format($bom->calculated_paper_rate_by_layers ?? 0, 8) }} + {{ number_format($bom->calculated_work_amount ?? 0, 8) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($bom->calculated_net_rate ?? 0, 8) }}</span>
                            </div>
                        </div>
                    @else
                        <!-- 3D Carton breakdown -->
                        <div class="step" style="background: #eef2ff; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.5rem; border: 1px solid #c7d2fe;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step1_reel') }}</span>
                                    <div class="step-calculation">Reel Length = (({{ number_format($length, 2) }} + {{ number_format($width, 2) }}) × 2) + 4 = {{ number_format($reelLength, 2) }}, Reel Height = {{ number_format($width, 2) }} + {{ number_format($height, 2) }} + 1 = {{ number_format($reelHeight, 2) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($reelLength, 2) }} × {{ number_format($reelHeight, 2) }} inches</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step2_division') }}</span>
                                    <div class="step-calculation">{{ number_format($reelLength, 2) }} × {{ number_format($reelHeight, 2) }} × {{ $paperGsm }} × {{ number_format($perGramRate, 2) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($divisionValue, 2) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step3_paper_rate') }}</span>
                                    <div class="step-calculation">{{ number_format($divisionValue, 2) }} ÷ {{ number_format($constant) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($paperRate, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step4_layers') }}</span>
                                    <div class="step-calculation">{{ $multiplicationLayer }} × {{ number_format($paperRate, 8) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($paperRateByLayers, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step5_work') }}</span>
                                    <div class="step-calculation">{{ number_format($paperRateByLayers, 8) }} × {{ $workPercentage }}%</div>
                                </div>
                                <span class="step-result">{{ number_format($workCost, 8) }}</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label">{{ __('ui.step6_row_rate') }}</span>
                                    <div class="step-calculation">{{ number_format($printCost, 2) }} + {{ number_format($paperRateByLayers, 8) }} + {{ number_format($workCost, 8) }}</div>
                                </div>
                                <span class="step-result">{{ number_format($netRate, 8) }}</span>
                            </div>
                        </div>
                        <div class="step" style="background: #d1fae5; border-radius: 8px; padding: 0.75rem; margin-top: 0.5rem; border: 1px solid #6ee7b7;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="step-label"><i class="bi bi-box me-1"></i> {{ __('ui.carton_net_rate_final') }}</span>
                                    <div class="step-calculation">{{ $bom->items->count() }} material row(s)</div>
                                </div>
                                <span class="step-result" style="font-size: 1.2rem; color: #4f46e5;">{{ number_format($bom->calculated_net_rate ?? $netRate, 8) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Detailed Calculation Steps (for 3D Carton) --}}
        @if($bom->formula_type === 'carton_3d' && $bom->items->count() > 0)
            <div class="table-card mt-4">
                <div class="card-header-custom">
                    <h6 class="mb-0">
                        <i class="bi bi-calculator me-2"></i> Material Row Calculations
                        <span class="text-muted ms-2" style="font-size: 0.7rem; font-weight: 400;">
                            Each row calculated with its own formula parameters
                        </span>
                    </h6>
                </div>
                <div class="p-4">
                    @php
                        // ─── GET DIMENSIONS FROM FIRST BOM ITEM ───
                        $firstItem = $bom->items->first();
                        $baseLength = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
                        $baseWidth = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
                        $baseHeight = $firstItem ? ($firstItem->height_inch ?? 0) : 0;
                        $baseReelLength = (($baseLength + $baseWidth) * 2) + 4;
                        $baseReelHeight = $baseWidth + $baseHeight + 1;
                        $totalRowNetRate = 0;
                    @endphp

                    @foreach($bom->items as $itemIndex => $item)
                        @php
                            // ─── USE EACH ITEM'S SPECIFIC VALUES ───
                            $length = $item->length_inch ?? $baseLength;
                            $width = $item->width_inch ?? $baseWidth;
                            $height = $item->height_inch ?? $baseHeight;
                            $reelLength = (($length + $width) * 2) + 4;
                            $reelHeight = $width + $height + 1;

                            // Get item-specific formula values
                            $gsm = $item->paper_gsm ?? 0;
                            $perGramRate = $item->per_gram_rate ?? 0;
                            $multiplicationLayer = $item->multiplication_layer ?? 1;
                            $printCost = $item->print ?? 0;
                            $constant = $item->formula_constant ?? 1550000;
                            $workPercentage = $item->work_percentage ?? ($bom->work_percentage ?? 40);

                            // Calculate row net rate for THIS item
                            $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;
                            $paperRate = $constant > 0 ? $divisionValue / $constant : 0;
                            $paperRateByLayers = $multiplicationLayer * $paperRate;
                            $workCost = $paperRateByLayers * ((float) $workPercentage / 100);
                            $rowNetRate = $printCost + $paperRateByLayers + $workCost;

                            // Add to total
                            $totalRowNetRate += $rowNetRate;
                        @endphp

                        <div class="calculation-step-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="step-number">Row #{{ $loop->iteration }}</span>
                                    <span class="fw-semibold ms-2">{{ $item->material->name ?? 'Unknown Material' }}</span>
                                    <span class="material-currency-badge {{ $item->purchase_currency === 'USD' ? 'usd' : 'afn' }} ms-2">
                                        {{ $item->purchase_currency ?? 'AFN' }}
                                    </span>
                                    <span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">
                                        Cost: ${{ number_format($item->cost_per_unit_usd ?? 0, 4) }}
                                    </span>
                                </div>
                                <span class="step-result final">؋{{ number_format($rowNetRate, 8) }}</span>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <div>{{ __('ui.reel_length_colon') }} <strong>{{ number_format($reelLength, 2) }} in</strong> {{ __('ui.reel_height_inline') }} <strong>{{ number_format($reelHeight, 2) }} in</strong></div>
                                        <div>{{ __('ui.gsm_colon') }} <strong>{{ $gsm }}</strong> {{ __('ui.per_gram_rate_inline') }} <strong>؋{{ number_format($perGramRate, 2) }}</strong></div>
                                        <div>{{ __('ui.multiplication_layer_colon') }} <strong>{{ $multiplicationLayer }}</strong> {{ __('ui.print_cost_inline') }} <strong>؋{{ number_format($printCost, 2) }}</strong></div>
                                        <div>{{ __('ui.formula_constant_colon') }} <strong>{{ number_format($constant) }}</strong> {{ __('ui.work_percent_inline') }} <strong>{{ $workPercentage }}%</strong></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <div>{{ __('ui.division_value_colon') }} <strong>{{ number_format($divisionValue, 2) }}</strong></div>
                                        <div>{{ __('ui.paper_rate_colon') }} <strong>{{ number_format($paperRate, 8) }}</strong></div>
                                        <div>{{ __('ui.paper_rate_layers_colon') }} <strong>{{ number_format($paperRateByLayers, 8) }}</strong></div>
                                        <div>{{ number_format($workPercentage, 2) }}% Work: <strong>{{ number_format($workCost, 8) }}</strong></div>
                                    </div>
                                </div>
                            </div>

                            @if($loop->iteration < $bom->items->count())
                                <div class="text-center text-muted" style="font-size: 0.6rem; padding-top: 0.25rem; border-top: 1px dashed #e5e7eb; margin-top: 0.25rem;">
                                    <i class="bi bi-arrow-down"></i> Next row
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="calculation-step-card" style="background: #eef2ff; border-color: #c7d2fe;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="step-number">{{ __('ui.final_carton_net_rate') }}</span>
                                <span class="text-muted ms-2" style="font-size: 0.75rem;">Sum of all {{ $bom->items->count() }} rows</span>
                            </div>
                            <span class="step-result final" style="font-size: 1.2rem; color: #4f46e5;">
                                ؋{{ number_format($totalRowNetRate, 8) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- BOM Items --}}
        <div class="table-card mt-4">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i> Material Requirements
                    <span class="badge bg-primary ms-2">{{ $bom->items->count() }}</span>
                    <span class="text-muted ms-2" style="font-size: 0.7rem; font-weight: 400;">
                        Each row shows its own calculated net rate
                    </span>
                </h6>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-ledger mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.material') }}</th>
                            <th>{{ __('ui.currency') }}</th>
                            <th>Usage / Carton</th>
                            <th>Usage + Waste</th>
                            <th>{{ __('ui.unit') }}</th>
                            <th>{{ __('ui.wastage_percent') }}</th>
                            <th>Rate USD / Unit</th>
                            <th>Rate AFN / Unit</th>
                            <th>{{ __('ui.total_cost_usd') }}</th>
                            <th>{{ __('ui.total_cost_afn') }}</th>
                            <th>{{ __('ui.row_net_rate') }}</th>
                            <th>{{ __('ui.formula_params') }}</th>
                            <th>{{ __('ui.notes') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            // ─── GET BASE DIMENSIONS ───
                            $firstItem = $bom->items->first();
                            $baseLength = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
                            $baseWidth = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
                            $baseHeight = $firstItem ? ($firstItem->height_inch ?? 0) : 0;
                            $totalRowNetRate = 0;
                        @endphp

                        @forelse($bom->items as $index => $item)
                            @php
                                // ─── USE EACH ITEM'S SPECIFIC VALUES ───
                                $length = $item->length_inch ?? $baseLength;
                                $width = $item->width_inch ?? $baseWidth;
                                $height = $item->height_inch ?? $baseHeight;
                                $reelLength = (($length + $width) * 2) + 4;
                                $reelHeight = $width + $height + 1;

                                // Get item-specific formula values
                                $gsm = $item->paper_gsm ?? 0;
                                $perGramRate = $item->per_gram_rate ?? 0;
                                $multiplicationLayer = $item->multiplication_layer ?? 1;
                                $printCost = $item->print ?? 0;
                                $constant = $item->formula_constant ?? 1550000;
                                $workPercentage = $item->work_percentage ?? ($bom->work_percentage ?? 40);

                                // Calculate row net rate for THIS item
                                $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;
                                $paperRate = $constant > 0 ? $divisionValue / $constant : 0;
                                $paperRateByLayers = $multiplicationLayer * $paperRate;
                                $workCost = $paperRateByLayers * ((float) $workPercentage / 100);
                                $rowNetRate = $printCost + $paperRateByLayers + $workCost;

                                // Add to total
                                $totalRowNetRate += $rowNetRate;

                                // Get item costs
                                $costUsd = floatval($item->cost_per_unit_usd ?? 0);
                                $costAfn = floatval($item->cost_per_unit_afn ?? 0);
                                $quantity = (float) $item->calculateStockRequirement(1, false);
                                $quantityWithWaste = (float) $item->calculateStockRequirement(1, true);
                                $wastage = floatval($item->wastage_percentage ?? 0);
                                $totalCostUsd = floatval($item->total_cost_usd ?? 0);
                                $totalCostAfn = floatval($item->total_cost_afn ?? 0);
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    {{ $item->material->name ?? 'N/A' }}
                                    @if($bom->formula_type === 'carton_3d' && $rowNetRate > 0)
                                        <br>
                                        <span class="text-muted" style="font-size: 0.6rem;">
                                            Net rate: ؋{{ number_format($rowNetRate, 4) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $item->purchase_currency === 'USD' ? 'bg-info' : 'bg-secondary' }}">
                                        {{ $item->purchase_currency ?? 'AFN' }}
                                    </span>
                                </td>
                                <td class="num-cell fw-semibold">{{ number_format($quantity, 6) }}</td>
                                <td class="num-cell">{{ number_format($quantityWithWaste, 6) }}</td>
                                <td>{{ $item->unit ?? '-' }}</td>
                                <td class="num-cell">{{ number_format($wastage, 2) }}%</td>
                                <td class="num-cell">${{ number_format($costUsd, 6) }}</td>
                                <td class="num-cell">؋{{ number_format($costAfn, 4) }}</td>
                                <td class="num-cell fw-semibold">${{ number_format($totalCostUsd, 4) }}</td>
                                <td class="num-cell fw-semibold">؋{{ number_format($totalCostAfn, 2) }}</td>
                                <td class="num-cell fw-semibold text-primary row-net-rate-cell">
                                    @if($bom->formula_type === 'carton_3d')
                                        ؋{{ number_format($rowNetRate, 8) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($bom->formula_type === 'carton_3d')
                                        <span class="formula-params">
                                            GSM: {{ $gsm }}<br>
                                            Rate: ؋{{ number_format($perGramRate, 2) }}<br>
                                            Layers: {{ $multiplicationLayer }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-4">
                                    <i class="bi bi-box-seam" style="font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #cbd5e1;"></i>
                                    No materials found for this BOM.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        <tfoot>
                        <tr class="fw-bold">
                            <td colspan="7" class="text-end">{{ __('ui.total_material_cost') }}</td>
                            <td class="num-cell text-primary">${{ number_format($bom->total_material_cost_usd ?? 0, 2) }}</td>
                            <td class="num-cell text-primary">؋{{ number_format($bom->total_material_cost_afn ?? 0, 2) }}</td>
                            <td class="num-cell text-primary row-net-rate-cell">
                                @if($bom->formula_type === 'carton_3d')
                                    ؋{{ number_format($totalRowNetRate, 8) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        function calculateBOM() {
            Swal.fire({
                title: 'Calculate Cost',
                html: `
                <div class="text-start">
                    <label class="form-label">{{ __('ui.quantity') }}</label>
                    <input type="number" class="form-control" id="calcQuantity" value="1" min="1" step="1">
                </div>
            `,
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Calculate',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const quantity = document.getElementById('calcQuantity').value;
                    if (!quantity || quantity < 1) {
                        Swal.showValidationMessage('Please enter a valid quantity');
                    }
                    return quantity;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const quantity = result.value;
                    $.ajax({
                        url: '{{ route("bom.calculate") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            bom_id: '{{ $bom->id }}',
                            quantity: quantity
                        },
                        success: function(response) {
                            if (response.success) {
                                let html = '<div class="text-start">';
                                html += `<h6 class="mb-3">Material Requirements for ${quantity} units</h6>`;

                                // ─── Reel Info ───
                                if (response.reel_length !== undefined && response.reel_length > 0) {
                                    html += `
                                        <div class="alert alert-info mb-3" style="background: #f0f9ff; border-color: #bae6fd;">
                                            <strong>{{ __('ui.reel_dimensions') }}</strong>
                                            Length: ${response.reel_length.toFixed(2)} in |
                                            Height: ${response.reel_height.toFixed(2)} in
                                        </div>
                                    `;
                                }

                                // ─── Row Net Rates ───
                                if (response.row_net_rates && response.row_net_rates.length > 0) {
                                    html += `<div class="mb-2"><strong>{{ __('ui.material_row_calculations') }}</strong></div>`;
                                    response.row_net_rates.forEach(function(row, index) {
                                        html += `
                                            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size: 0.85rem;">
                                                <span>
                                                    Row ${index + 1}: ${row.material_name}
                                                    <span class="text-muted small">
                                                        (Rate: ${row.paper_rate_by_layers.toFixed(4)} × 1.40)
                                                    </span>
                                                </span>
                                                <span class="fw-semibold text-primary">
                                                    ؋${row.row_net_rate.toFixed(8)}
                                                </span>
                                            </div>
                                        `;
                                    });
                                }

                                // ─── Material Requirements ───
                                if (response.requirements && response.requirements.length > 0) {
                                    html += `<div class="mt-2"><strong>{{ __('ui.material_requirements_colon') }}</strong></div>`;
                                    response.requirements.forEach(function(req) {
                                        html += `
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span>
                                                    ${req.material_name}
                                                    <span class="badge ${req.purchase_currency === 'USD' ? 'bg-info' : 'bg-secondary'} ms-1">${req.purchase_currency || 'AFN'}</span>
                                                    ${req.shortage > 0 ? `<span class="text-danger ms-2">⚠️ Shortage: ${req.shortage.toFixed(2)}</span>` : ''}
                                                </span>
                                                <span class="fw-semibold">
                                                    ${req.total_required.toFixed(4)} ${req.unit}
                                                    ($${req.total_cost_usd.toFixed(2)})
                                                </span>
                                            </div>
                                        `;
                                    });
                                }

                                html += `
                                <div class="mt-3 pt-2 border-top">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.total_material_cost_usd') }}</span>
                                        <span class="fw-bold">$${response.summary.total_cost_usd.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.total_material_cost_afn') }}</span>
                                        <span class="fw-bold">؋${response.summary.total_cost_afn.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Work Cost (${response.summary.work_percentage || 40}%)</span>
                                        <span class="fw-bold">؋${(response.summary.total_cost_afn * (response.summary.work_percentage / 100)).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.total_cost_per_unit') }}</span>
                                        <span class="fw-bold">؋${response.summary.cost_per_unit_afn.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.selling_price') }}</span>
                                        <span class="fw-bold text-success">؋${response.summary.selling_price_afn.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.profit') }}</span>
                                        <span class="fw-bold text-primary">؋${response.summary.profit_afn.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>{{ __('ui.profit_margin') }}</span>
                                        <span class="fw-bold text-success">${response.summary.profit_margin_percentage.toFixed(1)}%</span>
                                    </div>
                                </div>
                            `;
                                html += '</div>';

                                Swal.fire({
                                    title: 'Cost Calculation',
                                    html: html,
                                    width: 700,
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#4f46e5'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to calculate BOM costs.', 'error');
                        }
                    });
                }
            });
        }
    </script>
@endsection
