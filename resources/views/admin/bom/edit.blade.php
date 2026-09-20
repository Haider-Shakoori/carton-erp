{{-- resources/views/admin/bom/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit BOM')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Base Styles ─── */
        .form-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .form-section .section-title i {
            margin-right: 0.5rem;
            color: var(--primary);
        }

        /* ─── BOM Item Row ─── */
        .bom-item-row {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            position: relative;
            transition: all 0.3s ease;
        }
        .bom-item-row:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.1);
        }
        .bom-item-row .remove-item {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .bom-item-row .remove-item:hover {
            background: #fecaca;
            color: #dc2626;
        }
        .item-counter {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .material-cost-preview {
            font-size: 0.8rem;
            color: #6b7280;
            margin-left: 1rem;
        }
        .total-cost-display {
            background: #d1fae5;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: #065f46;
            font-weight: 600;
        }

        /* ─── Reel Dimensions Display ─── */
        .reel-dimensions-bar {
            background: #f0f9ff;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #bae6fd;
            margin-bottom: 0.5rem;
            display: none;
        }
        .reel-dimensions-bar .dimension-label {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
        }
        .reel-dimensions-bar .dimension-value {
            font-weight: 700;
            color: #0284c7;
            font-size: 0.9rem;
        }
        .reel-dimensions-bar .dimension-formula {
            font-size: 0.6rem;
            color: #94a3b8;
            margin-left: 0.25rem;
        }

        /* ─── Calculation Details ─── */
        .calculation-details {
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            border: 1px solid #e5e7eb;
            display: none;
        }
        .calculation-details .detail-label {
            color: #475569;
            font-weight: 500;
        }
        .calculation-details .detail-value {
            color: #1e293b;
            font-weight: 600;
        }
        .calculation-details .detail-value.highlight {
            color: #4f46e5;
            font-size: 0.9rem;
        }
        .calculation-details .detail-row {
            padding: 0.15rem 0;
        }

        /* ─── Formula Badge ─── */
        .formula-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 600;
        }
        .formula-badge.fixed { background: #f3f4f6; color: #6b7280; }
        .formula-badge.carton_3d { background: #dbeafe; color: #1e40af; }
        .formula-badge.cut_roll { background: #d1fae5; color: #065f46; }
        .formula-badge.fixed_percentage { background: #fef3c7; color: #92400e; }
        .formula-badge.fixed_rate { background: #fce4ec; color: #dc2626; }
        .formula-badge.adhesive_mix { background: #fce7f3; color: #9d174d; }

        /* ─── Adhesive Mix Defaults ─── */
        .adhesive-defaults summary {
            cursor: pointer;
            font-size: 0.72rem;
            font-weight: 600;
            color: #9d174d;
            padding: 0.25rem 0;
        }
        .adhesive-defaults summary::marker {
            color: #db2777;
        }

        /* ─── Formula Configuration ─── */
        .formula-config-section {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
        }
        .formula-config-section .formula-title {
            font-weight: 600;
            font-size: 0.8rem;
            color: #0284c7;
            margin-bottom: 0.5rem;
        }
        .formula-config-section .formula-title i {
            margin-right: 0.3rem;
        }
        .form-label-sm {
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.25rem;
        }
        .form-control-sm-custom {
            width: 100%;
            padding: 0.375rem 0.625rem;
            font-size: 0.8rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: border-color 0.15s ease;
        }
        .form-control-sm-custom:focus {
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* ─── Currency Badge ─── */
        .currency-badge {
            background: #e0e7ff;
            color: var(--primary);
            padding: 0.1rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }
        .currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }

        /* ─── Exchange Rate Container ─── */
        .exchange-rate-container {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.75rem;
            transition: all 0.3s ease;
        }
        .exchange-rate-container.hidden {
            display: none;
        }

        /* ─── Auto-Calculated Field Styles ─── */
        .quantity-input.auto-calculated {
            background-color: #f0fdf4;
            border-color: #6ee7b7;
            font-weight: 600;
            color: #065f46;
        }
        .quantity-input.auto-calculated[readonly] {
            cursor: default;
            opacity: 0.9;
        }

        /* ─── Batch Info ─── */
        .batch-info {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.75rem;
            margin-top: 0.75rem;
        }
        .batch-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.8rem;
        }
        .batch-item:last-child {
            border-bottom: none;
        }
        .batch-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 600;
        }

        /* ─── Cost Summary Card ─── */
        .cost-summary-card {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 1.25rem;
        }
        .cost-summary-card .cost-label {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .cost-summary-card .cost-value {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .cost-summary-card .cost-value.material {
            color: var(--primary);
        }
        .cost-summary-card .cost-value.total {
            color: var(--primary);
            font-size: 1.3rem;
        }
        .cost-summary-card .cost-value.selling {
            color: #059669;
            font-size: 1.3rem;
        }
        .cost-summary-divider {
            border-top: 2px dashed #bae6fd;
            margin: 0.75rem 0;
        }

        /* ─── Status Badge ─── */
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

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .bom-item-row {
                padding: 0.75rem;
            }
            .formula-config-section {
                padding: 0.75rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.bom') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        {{ $bom->code }} - {{ $bom->name }}
                        <span class="status-badge {{ $bom->status }} ms-2">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            {{ ucfirst($bom->status) }}
                        </span>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('bom.show', $bom) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view') }}
                    </a>
                    <a href="{{ route('bom.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <form action="{{ route('bom.update', $bom) }}" method="POST" id="bomForm">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> Basic Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.bom_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name', $bom->name) }}" required>
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.product') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-product @error('product_id') is-invalid @enderror"
                                        name="product_id" required>
                                    <option value="">{{ __('ui.select_product') }}</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('product_id', $bom->product_id) == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('product_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.description') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description" rows="2">{{ old('description', $bom->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Formula Defaults & Pricing -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-cash-stack"></i> Formula Defaults & Pricing
                            <span class="currency-badge afn ms-1">AFN</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Work, Overhead & Profit (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('work_percentage') is-invalid @enderror"
                                       name="work_percentage" id="workPercentageInput"
                                       value="{{ old('work_percentage', $bom->work_percentage ?? 40) }}"
                                       step="0.1" min="0" required>
                                <small class="text-muted">This percentage is added once and includes labour, overhead and profit.</small>
                                @error('work_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.profit_margin_percent') }}</label>
                                <input type="number" class="form-control @error('profit_margin_percentage') is-invalid @enderror"
                                       name="profit_margin_percentage" id="profitMarginInput"
                                       value="{{ old('profit_margin_percentage', $bom->profit_margin_percentage ?? 0) }}"
                                       step="0.1" min="0" max="100">
                                <small class="text-muted">Additional markup on top of the work percentage</small>
                                @error('profit_margin_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Exchange Rate (USD to AFN)</label>
                                <div class="exchange-rate-container" id="mainExchangeRateContainer">
                                    <input type="number" class="form-control @error('exchange_rate') is-invalid @enderror"
                                           name="exchange_rate" id="mainExchangeRate"
                                           value="{{ old('exchange_rate', $bom->exchange_rate ?? $defaultExchangeRate ?? 85) }}"
                                           step="0.0001" min="0.0001">
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <span id="exchangeRateInfo">USD → AFN conversion rate</span>
                                    </small>
                                </div>
                                <div id="afnOnlyMessage" style="display: none;">
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="bi bi-info-circle me-1"></i>
                                        All materials are purchased in AFN. No exchange rate needed.
                                    </div>
                                </div>
                                @error('exchange_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- BOM Items -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-box-seam"></i> Raw Materials
                            <span class="badge bg-primary ms-2" id="itemCount">{{ $bom->items->count() }}</span>
                            <span class="text-muted ms-2" style="font-weight: 400; font-size: 0.7rem;">
                                <i class="bi bi-info-circle"></i> Excel-based formulas calculate the cost of one carton/material line
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="total-cost-display" id="totalMaterialCost">
                                Material: ${{ number_format($bom->total_material_cost_usd ?? 0, 2) }} USD
                                <span class="text-muted">(؋{{ number_format($bom->total_material_cost_afn ?? 0, 2) }} AFN)</span>
                            </span>
                            <button type="button" class="btn btn-primary btn-sm" id="addItem">
                                <i class="bi bi-plus-circle me-1"></i> Add Material
                            </button>
                        </div>

                        <div id="itemsContainer">
                            @foreach($bom->items as $index => $item)
                                @php
                                    $itemId = $loop->iteration;
                                    $isFormulaBased = $item->formula_type && $item->formula_type !== 'fixed';
                                    $formulaType = $item->formula_type ?? 'fixed';

                                    // Calculate reel dimensions for display
                                    $length = $item->length_inch ?? 0;
                                    $width = $item->width_inch ?? 0;
                                    $height = $item->height_inch ?? 0;
                                    $reelLength = (($length + $width) * 2) + 4;
                                    $reelHeight = $width + $height + 1;
                                    $gsm = $item->paper_gsm ?? 0;
                                    $perGramRate = $item->per_gram_rate ?? 0;
                                    $multiplicationLayer = $item->multiplication_layer ?? 1;
                                    $printCost = $item->print ?? 0;
                                    $constant = $item->formula_constant ?? 1550000;
                                    $workPercentage = $item->work_percentage ?? 40;

                                    // Calculate row net rate
                                    $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;
                                    $paperRate = $constant > 0 ? $divisionValue / $constant : 0;
                                    $paperRateByLayers = $multiplicationLayer * $paperRate;
                                    $workCost = $paperRateByLayers * 0.40;
                                    $rowNetRate = $printCost + $paperRateByLayers + $workCost;

                                    // Adhesive mix snapshot (dimension driven)
                                    $adhesiveData = is_array($item->formula_data ?? null) ? $item->formula_data : [];
                                    $adhesiveRecipeKey = $adhesiveData['recipe_key'] ?? null;
                                    $adhesiveRecipeShare = $adhesiveData['recipe_percentage']
                                        ?? ($adhesiveRecipeKey ? (config('carton.adhesive.recipe.' . $adhesiveRecipeKey) ?? '') : '');
                                    $adhesiveGlueLines = $adhesiveData['glue_lines'] ?? config('carton.adhesive.glue_lines');
                                    $adhesiveDryGsm = $adhesiveData['dry_glue_gsm_per_line'] ?? config('carton.adhesive.dry_glue_gsm_per_line');
                                    $adhesiveWastage = $adhesiveData['glue_wastage_percentage'] ?? config('carton.adhesive.glue_wastage_percentage');
                                    $adhesiveSolids = $adhesiveData['adhesive_solids_percentage'] ?? config('carton.adhesive.adhesive_solids_percentage');
                                @endphp
                                <div class="bom-item-row" id="item-{{ $itemId }}">
                                    <button type="button" class="remove-item" onclick="removeItem({{ $itemId }})">
                                        <i class="bi bi-x-circle"></i>
                                    </button>

                                    <div class="item-counter">
                                        Material #{{ $itemId }}
                                        <span class="material-cost-preview" id="cost-preview-{{ $itemId }}">
                                            Cost: ${{ number_format($item->total_cost_usd ?? 0, 4) }} USD
                                        </span>
                                        <span class="formula-badge {{ $formulaType }} ms-2" id="formula-badge-{{ $itemId }}">
                                            {{ ucfirst(str_replace('_', ' ', $formulaType)) }}
                                        </span>
                                        <span class="currency-badge {{ $item->purchase_currency === 'USD' ? 'usd' : 'afn' }} ms-2" id="currency-badge-{{ $itemId }}">
                                            {{ $item->purchase_currency ?? 'AFN' }}
                                        </span>
                                    </div>

                                    <!-- ─── REEL DIMENSIONS DISPLAY ─── -->
                                    <div class="reel-dimensions-bar" id="reel-dimensions-{{ $itemId }}"
                                         style="display: {{ $formulaType === 'carton_3d' ? 'block' : 'none' }}; background: #f0f9ff; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #bae6fd; margin-bottom: 0.5rem;">
                                        <div class="row g-0 align-items-center">
                                            <div class="col-6">
                                                <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                                                <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="reel-length-display-{{ $itemId }}">
                                                    {{ $formulaType === 'carton_3d' ? number_format($reelLength, 2) : '0.00' }}
                                                </span>
                                                <span style="font-size: 0.7rem; color: #64748b;">in</span>
                                                <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">((L + W) × 2) + 4</span>
                                            </div>
                                            <div class="col-6">
                                                <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                                                <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="reel-height-display-{{ $itemId }}">
                                                    {{ $formulaType === 'carton_3d' ? number_format($reelHeight, 2) : '0.00' }}
                                                </span>
                                                <span style="font-size: 0.7rem; color: #64748b;">in</span>
                                                <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">(W + H + 1)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('ui.raw_material') }} <span class="text-danger">*</span></label>
                                            <select class="form-select select2-material"
                                                    name="items[{{ $itemId }}][material_id]"
                                                    id="material-{{ $itemId }}"
                                                    required>
                                                <option value="">{{ __('ui.select_raw_material') }}</option>
                                                @foreach($materials as $material)
                                                    <option value="{{ $material->id }}"
                                                            data-unit="{{ $material->unit }}"
                                                            data-name="{{ $material->name }}"
                                                            data-currency="{{ $material->purchase_currency ?? 'AFN' }}"
                                                            data-currency-id="{{ $material->purchase_currency_id ?? '' }}"
                                                        {{ $item->material_id == $material->id ? 'selected' : '' }}>
                                                        {{ $material->name }} ({{ $material->purchase_currency ?? 'AFN' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="items[{{ $itemId }}][purchase_currency]"
                                                   id="purchase-currency-{{ $itemId }}"
                                                   value="{{ $item->purchase_currency ?? 'AFN' }}">
                                            <input type="hidden" name="items[{{ $itemId }}][purchase_currency_id]"
                                                   id="purchase-currency-id-{{ $itemId }}"
                                                   value="{{ $item->purchase_currency_id ?? '' }}">
                                            <input type="hidden" name="items[{{ $itemId }}][id]" value="{{ $item->id }}">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">{{ __('ui.formula_type') }}</label>
                                            <select class="form-select"
                                                    name="items[{{ $itemId }}][formula_type]"
                                                    id="formula-type-{{ $itemId }}"
                                                    onchange="toggleFormulaFields({{ $itemId }})">
                                                <option value="fixed" {{ $formulaType === 'fixed' ? 'selected' : '' }}>{{ __('ui.fixed_quantity') }}</option>
                                                <option value="carton_3d" {{ $formulaType === 'carton_3d' ? 'selected' : '' }}>{{ __('ui.three_d_carton') }}</option>
                                                <option value="cut_roll" {{ $formulaType === 'cut_roll' ? 'selected' : '' }}>{{ __('ui.cut_roll') }}</option>
                                                <option value="adhesive_mix" {{ $formulaType === 'adhesive_mix' ? 'selected' : '' }}>Adhesive Mix — Dimension Based</option>
                                                <option value="fixed_percentage" {{ $formulaType === 'fixed_percentage' ? 'selected' : '' }}>{{ __('ui.material_percent') }}</option>
                                                <option value="fixed_rate" {{ $formulaType === 'fixed_rate' ? 'selected' : '' }}>{{ __('ui.fixed_rate') }}</option>
                                            </select>
                                            <input type="hidden" name="items[{{ $itemId }}][is_formula_based]"
                                                   id="is-formula-{{ $itemId }}"
                                                   value="{{ $isFormulaBased ? '1' : '0' }}">
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('ui.formula_quantity') }} <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control quantity-input auto-calculated"
                                                   name="items[{{ $itemId }}][quantity]"
                                                   id="quantity-{{ $itemId }}"
                                                   value="{{ $item->quantity }}"
                                                   step="0.0001" min="0.0001"
                                                   placeholder="{{ __('ui.auto_calculated') }}"
                                                {{ $isFormulaBased ? 'readonly' : '' }}>
                                            <small class="text-muted" id="rolls-hint-{{ $itemId }}">
                                                {!! $isFormulaBased ? '<i class="bi bi-magic"></i> Auto-calculated from Excel formula' : 'Enter quantity manually' !!}
                                            </small>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label">{{ __('ui.unit') }}</label>
                                            <input type="text" class="form-control unit-input"
                                                   name="items[{{ $itemId }}][unit]"
                                                   id="unit-{{ $itemId }}"
                                                   value="{{ $item->unit }}" readonly>
                                        </div>

                                        <div class="col-md-1">
                                            <label class="form-label">{{ __('ui.wastage_percent') }}</label>
                                            <input type="number" class="form-control"
                                                   name="items[{{ $itemId }}][wastage_percentage]"
                                                   id="item-wastage-{{ $itemId }}"
                                                   value="{{ $item->wastage_percentage }}"
                                                   step="0.01" min="0" max="100">
                                        </div>
                                    </div>

                                    <!-- ─── FORMULA CONFIGURATION ─── -->
                                    <div id="formula-fields-{{ $itemId }}"
                                         class="formula-config-section"
                                         style="display: {{ $isFormulaBased ? 'block' : 'none' }};">
                                        <div class="formula-title">
                                            <i class="bi bi-calculator"></i>
                                            Formula Configuration
                                            <span class="text-muted ms-2" style="font-size: 0.7rem; font-weight: 400;">
                                                Configure how this material quantity is calculated
                                            </span>
                                        </div>

                                        <!-- 3D Carton Formula -->
                                        <div id="carton-3d-fields-{{ $itemId }}"
                                             style="display: {{ $formulaType === 'carton_3d' ? 'block' : 'none' }};">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <div class="formula-hint mb-2" style="background: #eef2ff; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #4f46e5;">
                                                        <i class="bi bi-info-circle me-1" style="color: #4f46e5;"></i>
                                                        <strong style="color: #4f46e5;">{{ __('ui.excel_formula') }}</strong><br>
                                                        <span style="font-size: 0.75rem;">
                                                            <strong>{{ __('ui.step_1') }}</strong> Reel Length = ((L + W) × 2) + 4 &nbsp;|&nbsp; Reel Height = W + H + 1<br>
                                                            <strong>{{ __('ui.step_2') }}</strong> Division Value = Reel Length × Reel Height × GSM × Per Gram Rate<br>
                                                            <strong>{{ __('ui.step_3') }}</strong> Paper Rate = Division Value ÷ Formula Constant<br>
                                                            <strong>{{ __('ui.step_4') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate<br>
                                                            <strong>{{ __('ui.step_5') }}</strong> 40% Work = Paper Rate × Layers × 0.40<br>
                                                            <strong>{{ __('ui.step_6') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + 40% Work
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_length') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][length_inch]"
                                                           id="length-{{ $itemId }}"
                                                           value="{{ $item->length_inch }}"
                                                           placeholder="17.32" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_width') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][width_inch]"
                                                           id="width-{{ $itemId }}"
                                                           value="{{ $item->width_inch }}"
                                                           placeholder="15.75" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_height') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][height_inch]"
                                                           id="height-{{ $itemId }}"
                                                           value="{{ $item->height_inch }}"
                                                           placeholder="12.20" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.paper_gsm') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][paper_gsm]"
                                                           id="paper-gsm-{{ $itemId }}"
                                                           value="{{ $item->paper_gsm ?? 125 }}"
                                                           placeholder="125" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][per_gram_rate]"
                                                           id="per-gram-rate-{{ $itemId }}"
                                                           value="{{ $item->per_gram_rate ?? 40 }}"
                                                           placeholder="40" step="any" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.layers') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][layers]"
                                                           id="layers-{{ $itemId }}"
                                                           value="{{ $item->layers ?? 1 }}"
                                                           placeholder="1" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][multiplication_layer]"
                                                           id="multiplication-layer-{{ $itemId }}"
                                                           value="{{ $item->multiplication_layer ?? 1 }}"
                                                           placeholder="5" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][print]"
                                                           id="print-{{ $itemId }}"
                                                           value="{{ $item->print ?? 0 }}"
                                                           placeholder="0" step="0.01" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][formula_constant]"
                                                           id="formula-constant-{{ $itemId }}"
                                                           value="{{ $item->formula_constant ?? 1550000 }}"
                                                           placeholder="1550000"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][work_percentage]"
                                                           id="carton-work-percentage-{{ $itemId }}"
                                                           value="{{ $item->work_percentage ?? 40 }}"
                                                           placeholder="40" step="0.1" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cut/Roll Formula -->
                                        <div id="cut-roll-fields-{{ $itemId }}"
                                             style="display: {{ $formulaType === 'cut_roll' ? 'block' : 'none' }};">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <div class="formula-hint mb-2" style="background: #f0fdf4; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #10b981;">
                                                        <i class="bi bi-info-circle me-1" style="color: #10b981;"></i>
                                                        <strong style="color: #10b981;">{{ __('ui.formula') }}</strong>
                                                        <span style="font-size: 0.75rem;">
                                                            <strong>{{ __('ui.step_1') }}</strong> Multiplication = (Cut Length × Cut Width × Constant) ÷ 1000 (or × Constant)<br>
                                                            <strong>{{ __('ui.step_2') }}</strong> Paper Rate = (Multiplication × Per Gram Rate × GRH × Ply) ÷ Constant<br>
                                                            <strong>{{ __('ui.step_3') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate<br>
                                                            <strong>{{ __('ui.step_4') }}</strong> 40% Work = Paper Rate × Layers × 0.40<br>
                                                            <strong>{{ __('ui.step_5') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + 40% Work
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.cut_length_inches') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][cut_length_inch]"
                                                           id="cut-length-{{ $itemId }}"
                                                           value="{{ $item->cut_length_inch }}"
                                                           placeholder="26" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.cut_width_inches') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][cut_width_inch]"
                                                           id="cut-width-{{ $itemId }}"
                                                           value="{{ $item->cut_width_inch }}"
                                                           placeholder="30" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">GRH</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][grh]"
                                                           id="grh-{{ $itemId }}"
                                                           value="{{ $item->grh ?? 125 }}"
                                                           placeholder="125" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][per_gram_rate]"
                                                           id="per-gram-rate-cut-{{ $itemId }}"
                                                           value="{{ $item->per_gram_rate ?? 43 }}"
                                                           placeholder="43" step="any" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">Ply</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][ply]"
                                                           id="ply-{{ $itemId }}"
                                                           value="{{ $item->ply ?? 1 }}"
                                                           placeholder="3" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][print]"
                                                           id="print-cut-{{ $itemId }}"
                                                           value="{{ $item->print ?? 0 }}"
                                                           placeholder="0" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][multiplication_layer]"
                                                           id="multiplication-layer-cut-{{ $itemId }}"
                                                           value="{{ $item->multiplication_layer ?? 1 }}"
                                                           placeholder="3" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][formula_constant]"
                                                           id="formula-constant-cut-{{ $itemId }}"
                                                           value="{{ $item->formula_constant ?? 1550000 }}"
                                                           placeholder="1550000"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][work_percentage]"
                                                           id="cut-work-percentage-{{ $itemId }}"
                                                           value="{{ $item->work_percentage ?? 40 }}"
                                                           placeholder="40" step="0.1" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-label-sm">{{ __('ui.multiplication_method') }}</div>
                                                    <select class="form-control-sm-custom"
                                                            name="items[{{ $itemId }}][multiplication_method]"
                                                            id="multiplication-method-{{ $itemId }}"
                                                            onchange="calculateFormulaBasedItem({{ $itemId }})">
                                                        <option value="multiply" {{ ($item->multiplication_method ?? 'multiply') === 'multiply' ? 'selected' : '' }}>{{ __('ui.multiply') }}</option>
                                                        <option value="divide" {{ ($item->multiplication_method ?? 'multiply') === 'divide' ? 'selected' : '' }}>{{ __('ui.divide_by_1000') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Adhesive Mix (dimension driven) -->
                                        <div id="adhesive-fields-{{ $itemId }}"
                                             style="display: {{ $formulaType === 'adhesive_mix' ? 'block' : 'none' }};">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <div class="formula-hint mb-2" style="background: #fdf2f8; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #db2777;">
                                                        <i class="bi bi-droplet-half me-1" style="color: #db2777;"></i>
                                                        <strong style="color: #db2777;">Adhesive Mix — Dimension Based</strong><br>
                                                        <span style="font-size: 0.75rem;">
                                                            Board Area = ((L + W) × 2 + 4) × (W + H + 1) × 0.00064516 m²<br>
                                                            Wet Glue = Board Area × Glue Lines × Dry Glue GSM × (1 + Glue Wastage%) ÷ Adhesive Solids%<br>
                                                            Ingredient kg = Wet Glue kg × Recipe Share. Quantities follow the carton dimensions automatically.
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_length') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][length_inch]"
                                                           id="adhesive-length-{{ $itemId }}"
                                                           value="{{ $item->length_inch }}"
                                                           placeholder="17.32" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_width') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][width_inch]"
                                                           id="adhesive-width-{{ $itemId }}"
                                                           value="{{ $item->width_inch }}"
                                                           placeholder="15.75" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">{{ __('ui.carton_height') }} <span class="text-danger">*</span></div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][height_inch]"
                                                           id="adhesive-height-{{ $itemId }}"
                                                           value="{{ $item->height_inch }}"
                                                           placeholder="12.20" step="0.01" min="0.01"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-label-sm">Recipe Share (kg/kg wet glue)</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][recipe_percentage]"
                                                           id="adhesive-recipe-{{ $itemId }}"
                                                           value="{{ $adhesiveRecipeShare }}"
                                                           placeholder="Auto" step="0.000001" min="0"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                    <small class="text-muted" id="adhesive-recipe-hint-{{ $itemId }}">
                                                        {{ $adhesiveRecipeKey ? 'Recipe detected: ' . str_replace('_', ' ', $adhesiveRecipeKey) : 'Auto-detected from the selected mixing material.' }}
                                                    </small>
                                                </div>
                                                <div class="col-12">
                                                    <details class="adhesive-defaults">
                                                        <summary>
                                                            Factory adhesive parameters
                                                            <span class="text-muted" style="font-weight: 400;">
                                                                ({{ config('carton.adhesive.glue_lines') }} glue lines × {{ config('carton.adhesive.dry_glue_gsm_per_line') }} GSM,
                                                                {{ config('carton.adhesive.glue_wastage_percentage') }}% glue wastage,
                                                                {{ config('carton.adhesive.adhesive_solids_percentage') }}% solids — change only if this BOM differs)
                                                            </span>
                                                        </summary>
                                                        <div class="row g-2 mt-1">
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Glue Lines</div>
                                                                <input type="number" class="form-control-sm-custom"
                                                                       name="items[{{ $itemId }}][glue_lines]"
                                                                       id="adhesive-glue-lines-{{ $itemId }}"
                                                                       value="{{ $adhesiveGlueLines }}"
                                                                       step="1" min="0"
                                                                       oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Dry Glue GSM / Line</div>
                                                                <input type="number" class="form-control-sm-custom"
                                                                       name="items[{{ $itemId }}][dry_glue_gsm_per_line]"
                                                                       id="adhesive-dry-gsm-{{ $itemId }}"
                                                                       value="{{ $adhesiveDryGsm }}"
                                                                       step="0.01" min="0"
                                                                       oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Glue Wastage %</div>
                                                                <input type="number" class="form-control-sm-custom"
                                                                       name="items[{{ $itemId }}][glue_wastage_percentage]"
                                                                       id="adhesive-wastage-{{ $itemId }}"
                                                                       value="{{ $adhesiveWastage }}"
                                                                       step="0.01" min="0" max="100"
                                                                       oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Adhesive Solids %</div>
                                                                <input type="number" class="form-control-sm-custom"
                                                                       name="items[{{ $itemId }}][adhesive_solids_percentage]"
                                                                       id="adhesive-solids-{{ $itemId }}"
                                                                       value="{{ $adhesiveSolids }}"
                                                                       step="0.01" min="0" max="100"
                                                                       oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Wet Glue kg / Carton</div>
                                                                <input type="text" class="form-control-sm-custom" id="adhesive-wet-kg-{{ $itemId }}" readonly>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-label-sm">Ingredient kg / Carton</div>
                                                                <input type="text" class="form-control-sm-custom" id="adhesive-kg-{{ $itemId }}" readonly>
                                                            </div>
                                                        </div>
                                                    </details>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fixed Percentage -->
                                        <div id="fixed-percentage-fields-{{ $itemId }}"
                                             style="display: {{ $formulaType === 'fixed_percentage' ? 'block' : 'none' }};">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <div class="formula-hint mb-2" style="background: #fef3c7; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #f59e0b;">
                                                        <i class="bi bi-info-circle me-1" style="color: #f59e0b;"></i>
                                                        <strong style="color: #f59e0b;">{{ __('ui.formula') }}</strong>
                                                        <span style="font-size: 0.75rem;">
                                                            Base Material Quantity × (Percentage ÷ 100) = Required Quantity
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-label-sm">{{ __('ui.base_material') }}</div>
                                                    <select class="form-control-sm-custom select2-base-material"
                                                            name="items[{{ $itemId }}][base_material_id]"
                                                            id="base-material-{{ $itemId }}"
                                                            style="width: 100%;"
                                                            onchange="calculateFormulaBasedItem({{ $itemId }})">
                                                        <option value="">{{ __('ui.select_base_material') }}</option>
                                                        @foreach($materials as $material)
                                                            <option value="{{ $material->id }}" {{ $item->base_material_id == $material->id ? 'selected' : '' }}>
                                                                {{ $material->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-label-sm">{{ __('ui.percentage_percent') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][percentage_of_base]"
                                                           id="percentage-of-base-{{ $itemId }}"
                                                           value="{{ $item->percentage_of_base }}"
                                                           placeholder="5" step="0.1" min="0.1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Fixed Rate -->
                                        <div id="fixed-rate-fields-{{ $itemId }}"
                                             style="display: {{ $formulaType === 'fixed_rate' ? 'block' : 'none' }};">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <div class="formula-hint mb-2" style="background: #fce4ec; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #dc2626;">
                                                        <i class="bi bi-info-circle me-1" style="color: #dc2626;"></i>
                                                        <strong style="color: #dc2626;">{{ __('ui.formula') }}</strong>
                                                        <span style="font-size: 0.75rem;">
                                                            (Production Quantity ÷ Per Units) × Rate = Required Quantity
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-label-sm">{{ __('ui.rate_quantity') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][rate_per_unit]"
                                                           id="rate-per-unit-{{ $itemId }}"
                                                           value="{{ $item->rate_per_unit }}"
                                                           placeholder="0.5" step="0.0001" min="0.0001"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-label-sm">{{ __('ui.per_x_units') }}</div>
                                                    <input type="number" class="form-control-sm-custom"
                                                           name="items[{{ $itemId }}][rate_base_units]"
                                                           id="rate-base-units-{{ $itemId }}"
                                                           value="{{ $item->rate_base_units ?? 100 }}"
                                                           placeholder="100" min="1"
                                                           oninput="calculateFormulaBasedItem({{ $itemId }})">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ─── CALCULATION DETAILS ─── -->
                                    <div class="calculation-details" id="carton-calculation-details-{{ $itemId }}"
                                         style="display: {{ $formulaType === 'carton_3d' ? 'block' : 'none' }}; background: #f8fafc; padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.8rem; border: 1px solid #e5e7eb;">
                                        <div class="row g-1">
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="reel-length-detail-{{ $itemId }}">
                                                    {{ $formulaType === 'carton_3d' ? number_format($reelLength, 2) : '0.00' }}
                                                </span>
                                                <span class="text-muted">in</span>
                                            </div>
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="reel-height-detail-{{ $itemId }}">
                                                    {{ $formulaType === 'carton_3d' ? number_format($reelHeight, 2) : '0.00' }}
                                                </span>
                                                <span class="text-muted">in</span>
                                            </div>
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.division_value_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="division-value-{{ $itemId }}">
                                                    {{ $formulaType === 'carton_3d' ? number_format($divisionValue, 2) : '0.00' }}
                                                </span>
                                            </div>
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="paper-rate-{{ $itemId }}">{{ number_format($paperRate, 8) }}</span>
                                            </div>
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_layers_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="paper-rate-by-layers-{{ $itemId }}">{{ number_format($paperRateByLayers, 8) }}</span>
                                            </div>
                                            <div class="col-4 detail-row">
                                                <span style="color: #475569; font-weight: 500;">{{ __('ui.work_40_label') }}</span>
                                                <span style="color: #1e293b; font-weight: 600;" id="work-amount-{{ $itemId }}">{{ number_format($workCost, 8) }}</span>
                                            </div>
                                            <div class="col-12 detail-row" style="border-top: 1px dashed #e5e7eb; padding-top: 0.4rem; margin-top: 0.2rem;">
                                                <span style="color: #475569; font-weight: 700;">{{ __('ui.row_net_rate_label') }}</span>
                                                <span style="color: #4f46e5; font-weight: 700; font-size: 0.9rem;" id="row-net-rate-{{ $itemId }}">{{ number_format($rowNetRate, 8) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ─── COST & NOTES ─── -->
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('ui.cost_per_unit') }} <span class="currency-label usd">USD</span></label>
                                            <input type="number" class="form-control cost-input"
                                                   name="items[{{ $itemId }}][cost_per_unit_usd]"
                                                   id="cost-usd-{{ $itemId }}"
                                                   value="{{ $item->cost_per_unit_usd }}"
                                                   step="0.00001" min="0"
                                                   oninput="calculateItemCost({{ $itemId }})">
                                            <input type="hidden" name="items[{{ $itemId }}][cost_per_unit_afn]"
                                                   id="cost-afn-{{ $itemId }}"
                                                   value="{{ $item->cost_per_unit_afn }}">
                                            <small class="text-muted cost-hint" id="cost-hint-{{ $itemId }}">
                                                Auto-filled from the latest purchase cost
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('ui.cost_in_afn') }}</label>
                                            <input type="text" class="form-control" id="cost-afn-display-{{ $itemId }}"
                                                   value="{{ number_format($item->cost_per_unit_afn ?? 0, 2) }}" readonly>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Auto-converted using exchange rate
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">{{ __('ui.purchase_currency') }}</label>
                                            <input type="text" class="form-control" id="currency-display-{{ $itemId }}"
                                                   value="{{ $item->purchase_currency ?? 'AFN' }}" readonly>
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Currency of the selected material
                                            </small>
                                        </div>
                                    </div>

                                    <div id="batch-info-{{ $itemId }}" class="batch-info" style="display: none;"></div>
                                </div>
                            @endforeach
                        </div>

                        <div id="noItemsMessage" class="text-center text-muted py-4"
                             style="{{ $bom->items->count() > 0 ? 'display: none;' : '' }}">
                            <i class="bi bi-box-seam" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No materials added yet. Click "Add Material" to start building your BOM.
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> {{ __('ui.status') }}
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ $bom->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.status') }}</label>
                            <select class="form-select" name="status">
                                <option value="draft" {{ $bom->status === 'draft' ? 'selected' : '' }}>{{ __('ui.draft') }}</option>
                                <option value="active" {{ $bom->status === 'active' ? 'selected' : '' }}>{{ __('ui.active') }}</option>
                                <option value="archived" {{ $bom->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="bi bi-check2 me-1"></i> Update BOM
                        </button>
                    </div>

                    <!-- Sidebar Cost Summary -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-calculator"></i> Cost Summary
                        </div>
                        <div id="costSummary" style="display: {{ ($bom->total_material_cost_usd ?? 0) > 0 ? 'block' : 'none' }};">
                            @php
                                $materialCostUsd = $bom->total_material_cost_usd ?? 0;
                                $exchangeRate = $bom->exchange_rate ?? 85;
                                $materialCostAfn = $materialCostUsd * $exchangeRate;
                                $workPercentage = $bom->work_percentage ?? 40;
                                $workCostAfn = $materialCostAfn * ($workPercentage / 100);
                                $totalCostAfn = $materialCostAfn + $workCostAfn;
                                $profitMargin = $bom->profit_margin_percentage ?? 0;
                                $sellingPriceAfn = $totalCostAfn * (1 + ($profitMargin / 100));
                            @endphp

                            <div class="cost-summary-card">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="cost-label">{{ __('ui.material_cost') }}</div>
                                        <div class="cost-value material" id="summaryMaterialCostUsd">${{ number_format($materialCostUsd, 2) }}</div>
                                        <div class="cost-value material" id="summaryMaterialCostAfn" style="color: #8b5cf6;">؋{{ number_format($materialCostAfn, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Work Cost (<span id="workPercentDisplay">{{ $workPercentage }}</span>%)</div>
                                        <div class="cost-value labor" id="summaryWorkCostAfn" style="color: #7c3aed;">؋{{ number_format($workCostAfn, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">{{ __('ui.exchange_rate') }}</div>
                                        <div class="cost-value" style="color: #8b5cf6;" id="summaryExchangeRate">1 USD = {{ number_format($exchangeRate, 2) }} AFN</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">{{ __('ui.profit_margin') }}</div>
                                        <div class="cost-value" style="color: #10b981;" id="summaryProfitMargin">{{ number_format($profitMargin, 0) }}%</div>
                                    </div>
                                </div>
                                <div class="cost-summary-divider"></div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="cost-label">Net Rate / Unit</div>
                                        <div class="cost-value total" id="summaryTotalCost">؋{{ number_format($totalCostAfn, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">{{ __('ui.selling_price_per_unit') }}</div>
                                        <div class="cost-value selling" id="summarySellingPrice">؋{{ number_format($sellingPriceAfn, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // ─── SELECT2 ───
            $('.select2-product').select2({
                placeholder: 'Search product...',
                allowClear: true,
                width: '100%'
            });

            $('.select2-material, .select2-base-material').select2({
                placeholder: 'Search...',
                allowClear: true,
                width: '100%'
            });

            // ─── VARIABLES ───
            let itemCounter = {{ $bom->items->count() }};
            const defaultExchangeRate = {{ $defaultExchangeRate ?? 85 }};
            let hasUsdMaterial = false;
            const adhesiveConfig = @json(config('carton.adhesive'));
            const sqInchToM2 = {{ config('carton.sq_inch_to_m2') }};

            // ─── ADHESIVE MIX HELPERS ───
            function resolveAdhesiveRecipeKey(materialName) {
                const normalized = String(materialName || '').toLowerCase().trim();
                if (!normalized) return null;

                const map = adhesiveConfig.materials || {};
                if (map[normalized]) return map[normalized];

                for (const name of Object.keys(map)) {
                    if (name && normalized.includes(name)) return map[name];
                }

                return null;
            }

            window.syncAdhesiveRecipe = function(id) {
                const name = $(`#material-${id}`).find('option:selected').data('name') || '';
                const key = resolveAdhesiveRecipeKey(name);
                const $recipe = $(`#adhesive-recipe-${id}`);
                const $hint = $(`#adhesive-recipe-hint-${id}`);

                if (key && adhesiveConfig.recipe && adhesiveConfig.recipe[key] !== undefined) {
                    $recipe.val(adhesiveConfig.recipe[key]);
                    $hint.html('Recipe detected: <strong>' + key.replace(/_/g, ' ') + '</strong>');
                } else if (name) {
                    $hint.html('<span class="text-warning">Not a mapped mixing material — enter the recipe share manually.</span>');
                }
            };

            // ─── CHECK IF ANY USD MATERIAL EXISTS ───
            function checkUsdMaterialExists() {
                let hasUsd = false;
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    const currency = $(`#purchase-currency-${id}`).val() || 'AFN';
                    if (currency === 'USD') {
                        hasUsd = true;
                    }
                });
                hasUsdMaterial = hasUsd;

                if (hasUsd) {
                    $('#mainExchangeRateContainer').show();
                    $('#afnOnlyMessage').hide();
                    $('#exchangeRateInfo').text('USD → AFN conversion rate for USD materials');
                } else {
                    $('#mainExchangeRateContainer').hide();
                    $('#afnOnlyMessage').show();
                    $('#exchangeRateInfo').text('All materials in AFN');
                }

                return hasUsd;
            }

            // ─── GENERATE ITEM HTML ───
            function generateItemHtml(id) {
                return `
            <div class="bom-item-row" id="item-${id}">
                <button type="button" class="remove-item" onclick="removeItem(${id})">
                    <i class="bi bi-x-circle"></i>
                </button>

                <div class="item-counter">
                    Material #${id}
                    <span class="material-cost-preview" id="cost-preview-${id}">Cost: $0.00 USD</span>
                    <span class="formula-badge fixed ms-2" id="formula-badge-${id}">Fixed</span>
                    <span class="currency-badge afn ms-2" id="currency-badge-${id}">AFN</span>
                </div>

                <!-- ─── REEL DIMENSIONS DISPLAY ─── -->
                <div class="reel-dimensions-bar" id="reel-dimensions-${id}" style="display: none; background: #f0f9ff; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #bae6fd; margin-bottom: 0.5rem;">
                    <div class="row g-0 align-items-center">
                        <div class="col-6">
                            <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                            <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="reel-length-display-${id}">0.00</span>
                            <span style="font-size: 0.7rem; color: #64748b;">in</span>
                            <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">((L + W) × 2) + 4</span>
                        </div>
                        <div class="col-6">
                            <span style="font-size: 0.7rem; color: #64748b; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                            <span style="font-weight: 700; color: #0284c7; font-size: 0.9rem;" id="reel-height-display-${id}">0.00</span>
                            <span style="font-size: 0.7rem; color: #64748b;">in</span>
                            <span style="font-size: 0.6rem; color: #94a3b8; margin-left: 0.25rem;">(W + H + 1)</span>
                        </div>
                    </div>
                </div>

                <!-- ─── MATERIAL SELECTION ─── -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.raw_material') }} <span class="text-danger">*</span></label>
                        <select class="form-select select2-material"
                                name="items[${id}][material_id]"
                                id="material-${id}"
                                required>
                            <option value="">{{ __('ui.select_raw_material') }}</option>
                            @foreach($materials as $material)
                <option value="{{ $material->id }}"
                                        data-unit="{{ $material->unit }}"
                                        data-name="{{ $material->name }}"
                                        data-currency="{{ $material->purchase_currency ?? 'AFN' }}"
                                        data-currency-id="{{ $material->purchase_currency_id ?? '' }}">
                                    {{ $material->name }} ({{ $material->purchase_currency ?? 'AFN' }})
                                </option>
                            @endforeach
                </select>
                <input type="hidden" name="items[${id}][purchase_currency]" id="purchase-currency-${id}" value="AFN">
                        <input type="hidden" name="items[${id}][purchase_currency_id]" id="purchase-currency-id-${id}" value="">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.formula_type') }}</label>
                        <select class="form-select"
                                name="items[${id}][formula_type]"
                                id="formula-type-${id}"
                                onchange="toggleFormulaFields(${id})">
                            <option value="fixed">{{ __('ui.fixed_quantity') }}</option>
                            <option value="carton_3d">{{ __('ui.three_d_carton') }}</option>
                            <option value="cut_roll">{{ __('ui.cut_roll') }}</option>
                            <option value="adhesive_mix">Adhesive Mix — Dimension Based</option>
                            <option value="fixed_percentage">{{ __('ui.material_percent') }}</option>
                            <option value="fixed_rate">{{ __('ui.fixed_rate') }}</option>
                        </select>
                        <input type="hidden" name="items[${id}][is_formula_based]"
                               id="is-formula-${id}" value="0">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.formula_quantity') }} <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input auto-calculated"
                               name="items[${id}][quantity]"
                               id="quantity-${id}"
                               step="0.0001" min="0.0001"
                               placeholder="{{ __('ui.auto_calculated') }}"
                               readonly>
                        <small class="text-muted" id="rolls-hint-${id}">
                            <i class="bi bi-magic"></i> Auto-calculated from formula
                        </small>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">{{ __('ui.unit') }}</label>
                        <input type="text" class="form-control unit-input"
                               name="items[${id}][unit]"
                               id="unit-${id}"
                               value="Unit" readonly>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">{{ __('ui.wastage_percent') }}</label>
                        <input type="number" class="form-control"
                               name="items[${id}][wastage_percentage]"
                               id="item-wastage-${id}"
                               value="5" step="0.01" min="0" max="100">
                    </div>
                </div>

                <!-- ─── FORMULA CONFIGURATION ─── -->
                <div id="formula-fields-${id}" style="display: none;" class="formula-config-section">
                    <div class="formula-title">
                        <i class="bi bi-calculator"></i>
                        Formula Configuration
                        <span class="text-muted ms-2" style="font-size: 0.7rem; font-weight: 400;">
                            Configure how this material quantity is calculated
                        </span>
                    </div>

                    <!-- 3D Carton Formula -->
                    <div id="carton-3d-fields-${id}" style="display: none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="formula-hint mb-2" style="background: #eef2ff; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #4f46e5;">
                                    <i class="bi bi-info-circle me-1" style="color: #4f46e5;"></i>
                                    <strong style="color: #4f46e5;">{{ __('ui.excel_formula') }}</strong><br>
                                    <span style="font-size: 0.75rem;">
                                        <strong>{{ __('ui.step_1') }}</strong> Reel Length = ((L + W) × 2) + 4 &nbsp;|&nbsp; Reel Height = W + H + 1<br>
                                        <strong>{{ __('ui.step_2') }}</strong> Division Value = Reel Length × Reel Height × GSM × Per Gram Rate<br>
                                        <strong>{{ __('ui.step_3') }}</strong> Paper Rate = Division Value ÷ Formula Constant<br>
                                        <strong>{{ __('ui.step_4') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate<br>
                                        <strong>{{ __('ui.step_5') }}</strong> 40% Work = Paper Rate × Layers × 0.40<br>
                                        <strong>{{ __('ui.step_6') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + 40% Work
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_length') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][length_inch]"
                                       id="length-${id}"
                                       placeholder="17.32" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_width') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][width_inch]"
                                       id="width-${id}"
                                       placeholder="15.75" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_height') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][height_inch]"
                                       id="height-${id}"
                                       placeholder="12.20" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.paper_gsm') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][paper_gsm]"
                                       id="paper-gsm-${id}"
                                       placeholder="125" min="1" value="125"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][per_gram_rate]"
                                       id="per-gram-rate-${id}"
                                       placeholder="40" step="any" min="0" value="40"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.layers') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][layers]"
                                       id="layers-${id}"
                                       placeholder="1" min="1" value="1"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][multiplication_layer]"
                                       id="multiplication-layer-${id}"
                                       placeholder="5" min="1" value="1"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][print]"
                                       id="print-${id}"
                                       placeholder="0" step="0.01" min="0" value="0"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][formula_constant]"
                                       id="formula-constant-${id}"
                                       placeholder="1550000" value="1550000"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][work_percentage]"
                                       id="carton-work-percentage-${id}"
                                       placeholder="40" step="0.1" min="0" value="40"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                        </div>
                    </div>

                    <!-- Cut/Roll Formula -->
                    <div id="cut-roll-fields-${id}" style="display: none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="formula-hint mb-2" style="background: #f0fdf4; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #10b981;">
                                    <i class="bi bi-info-circle me-1" style="color: #10b981;"></i>
                                    <strong style="color: #10b981;">{{ __('ui.formula') }}</strong>
                                    <span style="font-size: 0.75rem;">
                                        <strong>{{ __('ui.step_1') }}</strong> Multiplication = (Cut Length × Cut Width × Constant) ÷ 1000 (or × Constant)<br>
                                        <strong>{{ __('ui.step_2') }}</strong> Paper Rate = (Multiplication × Per Gram Rate × GRH × Ply) ÷ Constant<br>
                                        <strong>{{ __('ui.step_3') }}</strong> Paper Rate × Layers = Multiplication Layer × Paper Rate<br>
                                        <strong>{{ __('ui.step_4') }}</strong> 40% Work = Paper Rate × Layers × 0.40<br>
                                        <strong>{{ __('ui.step_5') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + 40% Work
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.cut_length_inches') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][cut_length_inch]"
                                       id="cut-length-${id}"
                                       placeholder="26" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.cut_width_inches') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][cut_width_inch]"
                                       id="cut-width-${id}"
                                       placeholder="30" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">GRH</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][grh]"
                                       id="grh-${id}"
                                       placeholder="125" min="1" value="125"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.per_gram_rate') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][per_gram_rate]"
                                       id="per-gram-rate-cut-${id}"
                                       placeholder="43" step="any" min="0" value="43"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">Ply</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][ply]"
                                       id="ply-${id}"
                                       placeholder="3" min="1" value="1"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.print_cost') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][print]"
                                       id="print-cut-${id}"
                                       placeholder="0" min="0" value="0"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.multiplication_layer') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][multiplication_layer]"
                                       id="multiplication-layer-cut-${id}"
                                       placeholder="3" min="1" value="1"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.formula_constant') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][formula_constant]"
                                       id="formula-constant-cut-${id}"
                                       placeholder="1550000" value="1550000"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.work_percentage') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][work_percentage]"
                                       id="cut-work-percentage-${id}"
                                       placeholder="40" step="0.1" min="0" value="40"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-2">
                                <div class="form-label-sm">{{ __('ui.multiplication_method') }}</div>
                                <select class="form-control-sm-custom"
                                        name="items[${id}][multiplication_method]"
                                        id="multiplication-method-${id}"
                                        onchange="calculateFormulaBasedItem(${id})">
                                    <option value="multiply">{{ __('ui.multiply') }}</option>
                                    <option value="divide">{{ __('ui.divide_by_1000') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Adhesive Mix (dimension driven) -->
                    <div id="adhesive-fields-${id}" style="display: none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="formula-hint mb-2" style="background: #fdf2f8; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #db2777;">
                                    <i class="bi bi-droplet-half me-1" style="color: #db2777;"></i>
                                    <strong style="color: #db2777;">Adhesive Mix — Dimension Based</strong><br>
                                    <span style="font-size: 0.75rem;">
                                        Board Area = ((L + W) × 2 + 4) × (W + H + 1) × 0.00064516 m²<br>
                                        Wet Glue = Board Area × Glue Lines × Dry Glue GSM × (1 + Glue Wastage%) ÷ Adhesive Solids%<br>
                                        Ingredient kg = Wet Glue kg × Recipe Share. Quantities follow the carton dimensions automatically.
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_length') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][length_inch]"
                                       id="adhesive-length-${id}"
                                       placeholder="17.32" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_width') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][width_inch]"
                                       id="adhesive-width-${id}"
                                       placeholder="15.75" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">{{ __('ui.carton_height') }} <span class="text-danger">*</span></div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][height_inch]"
                                       id="adhesive-height-${id}"
                                       placeholder="12.20" step="0.01" min="0.01"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-3">
                                <div class="form-label-sm">Recipe Share (kg/kg wet glue)</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][recipe_percentage]"
                                       id="adhesive-recipe-${id}"
                                       placeholder="Auto" step="0.000001" min="0"
                                       oninput="calculateFormulaBasedItem(${id})">
                                <small class="text-muted" id="adhesive-recipe-hint-${id}">Auto-detected from the selected mixing material.</small>
                            </div>
                            <div class="col-12">
                                <details class="adhesive-defaults">
                                    <summary>
                                        Factory adhesive parameters
                                        <span class="text-muted" style="font-weight: 400;">
                                            ({{ config('carton.adhesive.glue_lines') }} glue lines × {{ config('carton.adhesive.dry_glue_gsm_per_line') }} GSM,
                                            {{ config('carton.adhesive.glue_wastage_percentage') }}% glue wastage,
                                            {{ config('carton.adhesive.adhesive_solids_percentage') }}% solids — change only if this BOM differs)
                                        </span>
                                    </summary>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Glue Lines</div>
                                            <input type="number" class="form-control-sm-custom"
                                                   name="items[${id}][glue_lines]"
                                                   id="adhesive-glue-lines-${id}"
                                                   step="1" min="0" value="{{ config('carton.adhesive.glue_lines') }}"
                                                   oninput="calculateFormulaBasedItem(${id})">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Dry Glue GSM / Line</div>
                                            <input type="number" class="form-control-sm-custom"
                                                   name="items[${id}][dry_glue_gsm_per_line]"
                                                   id="adhesive-dry-gsm-${id}"
                                                   step="0.01" min="0" value="{{ config('carton.adhesive.dry_glue_gsm_per_line') }}"
                                                   oninput="calculateFormulaBasedItem(${id})">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Glue Wastage %</div>
                                            <input type="number" class="form-control-sm-custom"
                                                   name="items[${id}][glue_wastage_percentage]"
                                                   id="adhesive-wastage-${id}"
                                                   step="0.01" min="0" max="100" value="{{ config('carton.adhesive.glue_wastage_percentage') }}"
                                                   oninput="calculateFormulaBasedItem(${id})">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Adhesive Solids %</div>
                                            <input type="number" class="form-control-sm-custom"
                                                   name="items[${id}][adhesive_solids_percentage]"
                                                   id="adhesive-solids-${id}"
                                                   step="0.01" min="0" max="100" value="{{ config('carton.adhesive.adhesive_solids_percentage') }}"
                                                   oninput="calculateFormulaBasedItem(${id})">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Wet Glue kg / Carton</div>
                                            <input type="text" class="form-control-sm-custom" id="adhesive-wet-kg-${id}" readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-label-sm">Ingredient kg / Carton</div>
                                            <input type="text" class="form-control-sm-custom" id="adhesive-kg-${id}" readonly>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Percentage -->
                    <div id="fixed-percentage-fields-${id}" style="display: none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="formula-hint mb-2" style="background: #fef3c7; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #f59e0b;">
                                    <i class="bi bi-info-circle me-1" style="color: #f59e0b;"></i>
                                    <strong style="color: #f59e0b;">{{ __('ui.formula') }}</strong>
                                    <span style="font-size: 0.75rem;">
                                        Base Material Quantity × (Percentage ÷ 100) = Required Quantity
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-label-sm">{{ __('ui.base_material') }}</div>
                                <select class="form-control-sm-custom select2-base-material"
                                        name="items[${id}][base_material_id]"
                                        id="base-material-${id}"
                                        style="width: 100%;"
                                        onchange="calculateFormulaBasedItem(${id})">
                                    <option value="">{{ __('ui.select_base_material') }}</option>
                                    @foreach($materials as $material)
                <option value="{{ $material->id }}">{{ $material->name }}</option>
                                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-label-sm">{{ __('ui.percentage_percent') }}</div>
                <input type="number" class="form-control-sm-custom"
                       name="items[${id}][percentage_of_base]"
                                       id="percentage-of-base-${id}"
                                       placeholder="5" step="0.1" min="0.1"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Rate -->
                    <div id="fixed-rate-fields-${id}" style="display: none;">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="formula-hint mb-2" style="background: #fce4ec; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #dc2626;">
                                    <i class="bi bi-info-circle me-1" style="color: #dc2626;"></i>
                                    <strong style="color: #dc2626;">{{ __('ui.formula') }}</strong>
                                    <span style="font-size: 0.75rem;">
                                        (Production Quantity ÷ Per Units) × Rate = Required Quantity
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-label-sm">{{ __('ui.rate_quantity') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][rate_per_unit]"
                                       id="rate-per-unit-${id}"
                                       placeholder="0.5" step="0.0001" min="0.0001"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                            <div class="col-md-6">
                                <div class="form-label-sm">{{ __('ui.per_x_units') }}</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][rate_base_units]"
                                       id="rate-base-units-${id}"
                                       placeholder="100" min="1" value="100"
                                       oninput="calculateFormulaBasedItem(${id})">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── CALCULATION DETAILS ─── -->
                <div class="calculation-details" id="carton-calculation-details-${id}" style="display: none; background: #f8fafc; padding: 0.75rem; border-radius: 6px; margin-top: 0.5rem; font-size: 0.8rem; border: 1px solid #e5e7eb;">
                    <div class="row g-1">
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_length_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="reel-length-detail-${id}">0.00</span>
                            <span class="text-muted">in</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.reel_height_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="reel-height-detail-${id}">0.00</span>
                            <span class="text-muted">in</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.division_value_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="division-value-${id}">0.00</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="paper-rate-${id}">0.00000000</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.paper_rate_layers_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="paper-rate-by-layers-${id}">0.00000000</span>
                        </div>
                        <div class="col-4 detail-row">
                            <span style="color: #475569; font-weight: 500;">{{ __('ui.work_40_label') }}</span>
                            <span style="color: #1e293b; font-weight: 600;" id="work-amount-${id}">0.00000000</span>
                        </div>
                        <div class="col-12 detail-row" style="border-top: 1px dashed #e5e7eb; padding-top: 0.4rem; margin-top: 0.2rem;">
                            <span style="color: #475569; font-weight: 700;">{{ __('ui.row_net_rate_label') }}</span>
                            <span style="color: #4f46e5; font-weight: 700; font-size: 0.9rem;" id="row-net-rate-${id}">0.00000000</span>
                        </div>
                    </div>
                </div>

                <!-- ─── COST & NOTES ─── -->
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.cost_per_unit') }} <span class="currency-label usd">USD</span></label>
                        <input type="number" class="form-control cost-input"
                               name="items[${id}][cost_per_unit_usd]"
                               id="cost-usd-${id}"
                               value="0" step="0.00001" min="0"
                               oninput="calculateItemCost(${id})">
                        <input type="hidden" name="items[${id}][cost_per_unit_afn]" id="cost-afn-${id}" value="0">
                        <small class="text-muted cost-hint" id="cost-hint-${id}">
                            Auto-filled from the latest purchase cost
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.cost_in_afn') }}</label>
                        <input type="text" class="form-control" id="cost-afn-display-${id}" value="0.00" readonly>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Auto-converted using exchange rate
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.purchase_currency') }}</label>
                        <input type="text" class="form-control" id="currency-display-${id}" value="AFN" readonly>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Currency of the selected material
                        </small>
                    </div>
                </div>

                <div id="batch-info-${id}" class="batch-info" style="display: none;"></div>
            </div>
            `;
            }

            // ─── ADD ITEM ───
            $('#addItem').on('click', function() {
                itemCounter++;
                const html = generateItemHtml(itemCounter);
                $('#itemsContainer').append(html);
                $('#noItemsMessage').hide();
                updateItemCount();

                // Initialize select2 for material
                $(`#material-${itemCounter}`).select2({
                    placeholder: 'Select Raw Material...',
                    allowClear: true,
                    width: '100%'
                });

                // Initialize select2 for base material
                $(`#base-material-${itemCounter}`).select2({
                    placeholder: 'Select Base Material...',
                    allowClear: true,
                    width: '100%'
                });

                // ─── Material selection handler with currency detection ───
                $(`#material-${itemCounter}`).on('change', function() {
                    const materialId = $(this).val();
                    const id = $(this).attr('id').replace('material-', '');

                    if (materialId) {
                        const selectedOption = $(this).find('option:selected');
                        const currency = selectedOption.data('currency') || 'AFN';
                        const currencyId = selectedOption.data('currency-id') || '';
                        const unit = selectedOption.data('unit') || 'Unit';

                        $(`#currency-display-${id}`).val(currency);
                        $(`#purchase-currency-${id}`).val(currency);
                        $(`#purchase-currency-id-${id}`).val(currencyId);
                        $(`#unit-${id}`).val(unit);

                        const badge = $(`#currency-badge-${id}`);
                        if (currency === 'USD') {
                            badge.removeClass('afn').addClass('usd').text('USD');
                        } else {
                            badge.removeClass('usd').addClass('afn').text('AFN');
                        }

                        checkUsdMaterialExists();

                        // Dimension-driven adhesive rows resolve their recipe share
                        // from the selected mixing material.
                        syncAdhesiveRecipe(id);

                        fetchMaterialCost(materialId, id, currency);
                    } else {
                        $(`#unit-${id}`).val('');
                        $(`#cost-usd-${id}`).val(0);
                        $(`#cost-afn-${id}`).val(0);
                        $(`#cost-afn-display-${id}`).val('0.00');
                        $(`#batch-info-${id}`).hide().html('');
                        $(`#currency-display-${id}`).val('AFN');
                        calculateItemCost(id);
                    }
                });

                toggleFormulaFields(itemCounter);
                setupAutoCalculation(itemCounter);

                $(`#quantity-${itemCounter}`).on('keyup change', function() {
                    calculateItemCost(itemCounter);
                });
                $(`#cost-usd-${itemCounter}`).on('keyup change', function() {
                    calculateItemCost(itemCounter);
                });
                $(`#item-wastage-${itemCounter}`).on('keyup change', function() {
                    calculateItemCost(itemCounter);
                });

                calculateItemCost(itemCounter);
            });

            // ─── SETUP AUTO-CALCULATION FOR ITEM ───
            function setupAutoCalculation(id) {
                const watched = [
                    `#length-${id}`, `#width-${id}`, `#height-${id}`,
                    `#paper-gsm-${id}`, `#per-gram-rate-${id}`, `#layers-${id}`,
                    `#multiplication-layer-${id}`, `#print-${id}`,
                    `#formula-constant-${id}`, `#carton-work-percentage-${id}`,
                    `#cut-length-${id}`, `#cut-width-${id}`, `#grh-${id}`,
                    `#per-gram-rate-cut-${id}`, `#ply-${id}`, `#print-cut-${id}`,
                    `#multiplication-layer-cut-${id}`, `#formula-constant-cut-${id}`,
                    `#cut-work-percentage-${id}`, `#multiplication-method-${id}`,
                    `#adhesive-length-${id}`, `#adhesive-width-${id}`, `#adhesive-height-${id}`,
                    `#adhesive-recipe-${id}`, `#adhesive-glue-lines-${id}`, `#adhesive-dry-gsm-${id}`,
                    `#adhesive-wastage-${id}`, `#adhesive-solids-${id}`,
                    `#base-material-${id}`, `#percentage-of-base-${id}`,
                    `#rate-per-unit-${id}`, `#rate-base-units-${id}`,
                    `#item-wastage-${id}`
                ].join(', ');

                $(document).off(`input.bom${id} change.bom${id}`, watched)
                    .on(`input.bom${id} change.bom${id}`, watched, function() {
                        calculateFormulaBasedItem(id);
                    });

                $(`#formula-type-${id}`).on('change', function() {
                    calculateFormulaBasedItem(id);
                });
            }

            // ─── TOGGLE FORMULA FIELDS ───
            window.toggleFormulaFields = function(id) {
                const formulaType = $(`#formula-type-${id}`).val();
                const isFormula = formulaType !== 'fixed';

                if (isFormula) {
                    $(`#formula-fields-${id}`).show();
                    $(`#is-formula-${id}`).val(1);
                    $(`#quantity-${id}`).prop('readonly', true);
                    $(`#rolls-hint-${id}`).html('<i class="bi bi-magic"></i> Auto-calculated from Excel formula');
                } else {
                    $(`#formula-fields-${id}`).hide();
                    $(`#is-formula-${id}`).val(0);
                    $(`#quantity-${id}`).prop('readonly', false);
                    $(`#rolls-hint-${id}`).html('Enter quantity manually');
                }

                $(`#carton-3d-fields-${id}`).hide();
                $(`#cut-roll-fields-${id}`).hide();
                $(`#adhesive-fields-${id}`).hide();
                $(`#fixed-percentage-fields-${id}`).hide();
                $(`#fixed-rate-fields-${id}`).hide();

                if (formulaType === 'carton_3d') {
                    $(`#carton-3d-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).show();
                    $(`#carton-calculation-details-${id}`).show();
                } else if (formulaType === 'cut_roll') {
                    $(`#cut-roll-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else if (formulaType === 'adhesive_mix') {
                    $(`#adhesive-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else if (formulaType === 'fixed_percentage') {
                    $(`#fixed-percentage-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else if (formulaType === 'fixed_rate') {
                    $(`#fixed-rate-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else {
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                }

                // Only the active formula group may submit its fields. The hidden
                // groups share names (per_gram_rate, formula_constant, print,
                // multiplication_layer, length_inch) and would otherwise overwrite
                // the active formula values in the POST payload.
                const formulaGroups = {
                    carton_3d: `carton-3d-fields-${id}`,
                    cut_roll: `cut-roll-fields-${id}`,
                    adhesive_mix: `adhesive-fields-${id}`,
                    fixed_percentage: `fixed-percentage-fields-${id}`,
                    fixed_rate: `fixed-rate-fields-${id}`
                };
                Object.keys(formulaGroups).forEach(function(type) {
                    $(`#${formulaGroups[type]}`)
                        .find('input, select, textarea')
                        .prop('disabled', type !== formulaType);
                });

                // The adhesive formula already includes the configured glue
                // wastage; a row-level wastage would double count it.
                const $wastageInput = $(`#item-wastage-${id}`);
                if (formulaType === 'adhesive_mix') {
                    if ($wastageInput.data('previous-value') === undefined) {
                        $wastageInput.data('previous-value', $wastageInput.val());
                    }
                    $wastageInput.val(0).prop('disabled', true);
                } else if ($wastageInput.prop('disabled')) {
                    $wastageInput.prop('disabled', false)
                        .val($wastageInput.data('previous-value') || 5);
                }

                const badge = $(`#formula-badge-${id}`);
                const labels = {
                    fixed: { class: 'fixed', label: 'Fixed' },
                    carton_3d: { class: 'carton_3d', label: '3D Carton' },
                    cut_roll: { class: 'cut_roll', label: 'Cut/Roll' },
                    adhesive_mix: { class: 'adhesive_mix', label: 'Adhesive Mix' },
                    fixed_percentage: { class: 'fixed_percentage', label: '% of Material' },
                    fixed_rate: { class: 'fixed_rate', label: 'Fixed Rate' }
                };
                const info = labels[formulaType] || labels.fixed;
                badge.removeClass('fixed carton_3d cut_roll adhesive_mix fixed_percentage fixed_rate')
                    .addClass(info.class)
                    .text(info.label);

                if (isFormula) {
                    calculateFormulaBasedItem(id);
                }
            };

            // ─── CALCULATE FORMULA BASED ITEM ───
            window.calculateFormulaBasedItem = function(id) {
                const formulaType = $(`#formula-type-${id}`).val();
                const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;
                const wastage = parseFloat($(`#item-wastage-${id}`).val()) || 0;

                let quantity = 1;
                let paperRate = 0;
                let paperRateByLayers = 0;
                let workCost = 0;
                let netRate = 0;
                let wetGlueKg = 0;

                if (formulaType === 'carton_3d') {
                    const length = parseFloat($(`#length-${id}`).val()) || 0;
                    const width = parseFloat($(`#width-${id}`).val()) || 0;
                    const height = parseFloat($(`#height-${id}`).val()) || 0;
                    const gsm = parseFloat($(`#paper-gsm-${id}`).val()) || 0;
                    const perGramRate = parseFloat($(`#per-gram-rate-${id}`).val()) || 0;
                    const multiplicationLayer = parseFloat($(`#multiplication-layer-${id}`).val()) || 1;
                    const printCost = parseFloat($(`#print-${id}`).val()) || 0;
                    const constant = parseFloat($(`#formula-constant-${id}`).val()) || 1550000;
                    const workPercentage = parseFloat($(`#carton-work-percentage-${id}`).val()) || 40;

                    if (length > 0 && width > 0 && height > 0 && gsm > 0 && perGramRate > 0 && constant > 0) {
                        const reelLength = ((length + width) * 2) + 4;
                        const reelHeight = width + height + 1;
                        const divisionValue = reelLength * reelHeight * gsm * perGramRate;
                        paperRate = divisionValue / constant;
                        paperRateByLayers = multiplicationLayer * paperRate;
                        workCost = paperRateByLayers * (workPercentage / 100);
                        netRate = printCost + paperRateByLayers + workCost;

                        $(`#reel-dimensions-${id}`).show();
                        $(`#reel-length-display-${id}`).text(reelLength.toFixed(2));
                        $(`#reel-height-display-${id}`).text(reelHeight.toFixed(2));
                        $(`#reel-length-detail-${id}`).text(reelLength.toFixed(2));
                        $(`#reel-height-detail-${id}`).text(reelHeight.toFixed(2));
                        $(`#carton-calculation-details-${id}`).show();
                        $(`#division-value-${id}`).text(divisionValue.toFixed(2));
                        $(`#paper-rate-${id}`).text(paperRate.toFixed(8));
                        $(`#paper-rate-by-layers-${id}`).text(paperRateByLayers.toFixed(8));
                        $(`#work-amount-${id}`).text(workCost.toFixed(8));
                        $(`#row-net-rate-${id}`).text(netRate.toFixed(8));
                    } else {
                        $(`#reel-dimensions-${id}`).hide();
                        $(`#carton-calculation-details-${id}`).hide();
                    }
                } else if (formulaType === 'cut_roll') {
                    const cutLength = parseFloat($(`#cut-length-${id}`).val()) || 0;
                    const cutWidth = parseFloat($(`#cut-width-${id}`).val()) || 0;
                    const grh = parseFloat($(`#grh-${id}`).val()) || 0;
                    const perGramRate = parseFloat($(`#per-gram-rate-cut-${id}`).val()) || 0;
                    const ply = parseFloat($(`#ply-${id}`).val()) || 1;
                    const multiplicationLayer = parseFloat($(`#multiplication-layer-cut-${id}`).val()) || 1;
                    const printCost = parseFloat($(`#print-cut-${id}`).val()) || 0;
                    const constant = parseFloat($(`#formula-constant-cut-${id}`).val()) || 1550000;
                    const workPercentage = parseFloat($(`#cut-work-percentage-${id}`).val()) || 40;
                    const multiplicationMethod = $(`#multiplication-method-${id}`).val() || 'multiply';

                    if (cutLength > 0 && cutWidth > 0 && grh > 0 && perGramRate > 0 && constant > 0) {
                        let multiplicationValue = cutLength * cutWidth * constant;
                        if (multiplicationMethod === 'divide') {
                            multiplicationValue = (cutLength * cutWidth * constant) / 1000;
                        }
                        paperRate = (multiplicationValue * perGramRate * grh * ply) / constant;
                        paperRateByLayers = multiplicationLayer * paperRate;
                        workCost = paperRateByLayers * (workPercentage / 100);
                        netRate = printCost + paperRateByLayers + workCost;

                        $(`#reel-dimensions-${id}`).hide();
                        $(`#carton-calculation-details-${id}`).hide();
                    }
                } else if (formulaType === 'adhesive_mix') {
                    const length = parseFloat($(`#adhesive-length-${id}`).val()) || 0;
                    const width = parseFloat($(`#adhesive-width-${id}`).val()) || 0;
                    const height = parseFloat($(`#adhesive-height-${id}`).val()) || 0;
                    const glueLines = parseFloat($(`#adhesive-glue-lines-${id}`).val());
                    const dryGlueGsm = parseFloat($(`#adhesive-dry-gsm-${id}`).val());
                    const glueWastage = parseFloat($(`#adhesive-wastage-${id}`).val());
                    const solids = parseFloat($(`#adhesive-solids-${id}`).val());
                    const recipe = parseFloat($(`#adhesive-recipe-${id}`).val()) || 0;

                    if (length > 0 && width > 0 && height > 0) {
                        const adhesiveReelLength = ((length + width) * 2) + 4;
                        const adhesiveReelHeight = width + height + 1;
                        const boardAreaM2 = adhesiveReelLength * adhesiveReelHeight * sqInchToM2;
                        const dryGlueGrams = boardAreaM2
                            * (Number.isFinite(glueLines) ? glueLines : 0)
                            * (Number.isFinite(dryGlueGsm) ? dryGlueGsm : 0);
                        const dryGlueWithWastage = dryGlueGrams
                            * (1 + ((Number.isFinite(glueWastage) ? glueWastage : 0) / 100));

                        wetGlueKg = (Number.isFinite(solids) && solids > 0)
                            ? (dryGlueWithWastage / 1000) / (solids / 100)
                            : 0;
                        quantity = wetGlueKg * recipe;

                        $(`#adhesive-wet-kg-${id}`).val(wetGlueKg.toFixed(8));
                    } else {
                        quantity = 0;
                        $(`#adhesive-wet-kg-${id}`).val('0');
                    }

                    $(`#adhesive-kg-${id}`).val(quantity > 0 ? quantity.toFixed(8) : '0');
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;

                    if (quantity > 0) {
                        $(`#unit-${id}`).val('kg');
                    }

                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else if (formulaType === 'fixed_percentage') {
                    const baseMaterialId = $(`#base-material-${id}`).val();
                    const percentage = parseFloat($(`#percentage-of-base-${id}`).val()) || 0;
                    let baseQuantity = 0;

                    $('.bom-item-row').each(function() {
                        const rowId = $(this).attr('id').replace('item-', '');
                        if ($(`#material-${rowId}`).val() == baseMaterialId && rowId != id) {
                            baseQuantity = parseFloat($(`#quantity-${rowId}`).val()) || 0;
                        }
                    });

                    quantity = baseQuantity * (percentage / 100);
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else if (formulaType === 'fixed_rate') {
                    const rate = parseFloat($(`#rate-per-unit-${id}`).val()) || 0;
                    const perUnits = parseFloat($(`#rate-base-units-${id}`).val()) || 1;
                    quantity = rate / perUnits;
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                } else {
                    quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;
                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                }

                const purchaseCurrency = $(`#purchase-currency-${id}`).val() || 'AFN';
                let costInUsd = 0;
                let costInAfn = 0;

                if (formulaType === 'carton_3d' || formulaType === 'cut_roll') {
                    quantity = 1;
                    const netWithWastageAfn = netRate * (1 + wastage / 100);
                    costInAfn = netWithWastageAfn;
                    costInUsd = exchangeRate > 0 ? netWithWastageAfn / exchangeRate : 0;
                } else {
                    costInUsd = parseFloat($(`#cost-usd-${id}`).val()) || 0;
                    costInAfn = costInUsd * exchangeRate;
                }

                $(`#cost-usd-${id}`).val(costInUsd.toFixed(5));
                $(`#cost-afn-${id}`).val(costInAfn.toFixed(2));
                $(`#cost-afn-display-${id}`).val(costInAfn.toFixed(2));
                // Adhesive quantities are very small (grams per carton), so keep
                // enough decimals to avoid truncating the recipe to zero.
                const quantityDecimals = formulaType === 'adhesive_mix' ? 8 : 4;
                $(`#quantity-${id}`).val(quantity > 0 ? quantity.toFixed(quantityDecimals) : '');

                if (formulaType === 'carton_3d') {
                    const reelLength = ((parseFloat($(`#length-${id}`).val()) || 0) + (parseFloat($(`#width-${id}`).val()) || 0)) * 2 + 4;
                    const reelHeight = (parseFloat($(`#width-${id}`).val()) || 0) + (parseFloat($(`#height-${id}`).val()) || 0) + 1;
                    $(`#cost-hint-${id}`).html(`
                        <i class="bi bi-calculator me-1"></i>
                        Excel net rate: <strong>؋${netRate.toFixed(8)}</strong> per carton
                        (with ${wastage.toFixed(2)}% wastage = <strong>$${costInUsd.toFixed(5)}</strong> USD)
                        <br>
                        <span class="text-muted">Reel Length: ${reelLength.toFixed(2)} in | Reel Height: ${reelHeight.toFixed(2)} in</span>
                    `);
                } else if (formulaType === 'cut_roll') {
                    $(`#cost-hint-${id}`).html(`
                        <i class="bi bi-calculator me-1"></i>
                        Cut/Roll net rate: <strong>؋${netRate.toFixed(8)}</strong>
                        (with ${wastage.toFixed(2)}% wastage = <strong>$${costInUsd.toFixed(5)}</strong> USD)
                    `);
                } else if (formulaType === 'adhesive_mix') {
                    $(`#cost-hint-${id}`).html(`
                        <i class="bi bi-droplet-half me-1"></i>
                        Wet glue: <strong>${wetGlueKg.toFixed(6)} kg</strong> / finished unit
                        (glue wastage already included). Ingredient: <strong>${quantity.toFixed(6)} kg</strong>.
                    `);
                } else {
                    $(`#cost-hint-${id}`).text(`Cost loaded in ${purchaseCurrency}: ${purchaseCurrency === 'USD' ? '$' : '؋'}${purchaseCurrency === 'USD' ? costInUsd.toFixed(4) : costInAfn.toFixed(2)}`);
                }

                calculateItemCost(id);
            };

            // ─── REMOVE ITEM ───
            window.removeItem = function(id) {
                if (confirm('Remove this material from the BOM?')) {
                    $(`#item-${id}`).remove();
                    updateItemCount();
                    updateTotalCost();
                    checkUsdMaterialExists();
                    if ($('#itemsContainer').children().length === 0) {
                        $('#noItemsMessage').show();
                        $('#totalMaterialCost').text('Material: $0.00 USD');
                    }
                }
            };

            // ─── UPDATE ITEM COUNT ───
            function updateItemCount() {
                const count = $('#itemsContainer .bom-item-row').length;
                $('#itemCount').text(count);
            }

            // ─── CALCULATE ITEM COST ───
            function calculateItemCost(id) {
                const quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                const costUsd = parseFloat($(`#cost-usd-${id}`).val()) || 0;
                const costAfn = parseFloat($(`#cost-afn-${id}`).val()) || 0;
                const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;
                const wastage = parseFloat($(`#item-wastage-${id}`).val()) || 0;
                const formulaType = $(`#formula-type-${id}`).val();
                const purchaseCurrency = $(`#purchase-currency-${id}`).val() || 'AFN';

                const multiplier = (formulaType === 'carton_3d' || formulaType === 'cut_roll')
                    ? 1
                    : (1 + wastage / 100);

                let totalCostUsd = quantity * costUsd * multiplier;
                let totalCostAfn = quantity * costAfn * multiplier;

                if (purchaseCurrency === 'AFN') {
                    totalCostAfn = quantity * costAfn * multiplier;
                    totalCostUsd = totalCostAfn / exchangeRate;
                }

                $(`#cost-preview-${id}`).html(`
                    Cost: $${totalCostUsd.toFixed(4)} USD
                    <span class="text-muted">(؋${totalCostAfn.toFixed(2)} AFN)</span>
                `);

                updateTotalCost();
            }

            // ─── FETCH MATERIAL COST ───
            function fetchMaterialCost(materialId, id, currency) {
                const $costInput = $(`#cost-usd-${id}`);
                const $costAfnInput = $(`#cost-afn-${id}`);
                const $costAfnDisplay = $(`#cost-afn-display-${id}`);
                const $batchInfo = $(`#batch-info-${id}`);
                const $hint = $(`#cost-hint-${id}`);

                $costInput.prop('disabled', true);
                $costInput.attr('placeholder', 'Loading...');
                $hint.html('<i class="bi bi-hourglass-split me-1"></i> Fetching latest cost data...');

                const url = '{{ route("bom.get-material-cost", ":material_id") }}'.replace(':material_id', materialId);

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;

                            let costInUsd = parseFloat(data.latest_cost_usd) || 0;
                            let costInAfn = parseFloat(data.latest_cost_afn) || 0;

                            if (costInUsd === 0 && costInAfn === 0) {
                                costInUsd = parseFloat(data.weighted_avg_cost) || 0;
                                if (currency === 'AFN') {
                                    costInAfn = costInUsd;
                                    costInUsd = costInUsd / exchangeRate;
                                } else {
                                    costInAfn = costInUsd * exchangeRate;
                                }
                            }

                            $(`#unit-${id}`).val(data.unit || 'Unit');
                            $costInput.val(costInUsd.toFixed(4));
                            $costAfnInput.val(costInAfn.toFixed(2));
                            $costAfnDisplay.val(costInAfn.toFixed(2));

                            let batchHtml = '';
                            const batches = data.batch_breakdown?.batches || [];
                            const latestBatchDate = data.latest_batch_date || '';

                            if (batches.length > 0) {
                                batchHtml = `
                                    <div class="fw-semibold mb-2">
                                        <i class="bi bi-clock-history me-1"></i>
                                        Batch Breakdown (Latest First):
                                        ${latestBatchDate ? `<span class="text-muted small ms-2">Latest: ${new Date(latestBatchDate).toLocaleDateString()}</span>` : ''}
                                    </div>
                                `;
                                batches.forEach(function(batch) {
                                    const batchCost = parseFloat(batch.cost_per_unit) || 0;
                                    const batchCostAfn = batchCost * exchangeRate;
                                    const displayCost = currency === 'USD' ? batchCost : batchCostAfn;
                                    const displayCurrency = currency === 'USD' ? 'USD' : 'AFN';
                                    const isLatest = batch.is_latest || false;

                                    batchHtml += `
                                        <div class="batch-item ${isLatest ? 'bg-success bg-opacity-10 p-1 rounded' : ''}"
                                             style="${isLatest ? 'border-left: 3px solid #10b981;' : ''}">
                                            <span>
                                                ${batch.batch_no || 'N/A'} (${batch.purchase_date || 'N/A'})
                                                ${isLatest ? '<span class="badge bg-success ms-1">{{ __('ui.latest') }}</span>' : ''}
                                            </span>
                                            <span>
                                                ${batch.qty_available} ${data.unit}
                                                @ ${displayCost.toFixed(4)} ${displayCurrency}
                                                <span class="text-muted small">(${batch.currency})</span>
                                            </span>
                                        </div>
                                    `;
                                });

                                batchHtml += `
                                    <div class="batch-total" style="background: #d1fae5; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">
                                        <div class="d-flex justify-content-between">
                                            <span><i class="bi bi-star-fill text-warning me-1"></i> <strong>{{ __('ui.latest_cost') }}</strong></span>
                                            <span><strong>${currency === 'USD' ? '$' : '؋'}${(currency === 'USD' ? costInUsd : costInAfn).toFixed(4)} ${data.unit}</strong></span>
                                        </div>
                                        ${currency === 'USD' ? `
                                            <div class="d-flex justify-content-between text-muted small">
                                                <span>In AFN</span>
                                                <span>؋${costInAfn.toFixed(2)} ${data.unit}</span>
                                            </div>
                                        ` : `
                                            <div class="d-flex justify-content-between text-muted small">
                                                <span>In USD</span>
                                                <span>$${costInUsd.toFixed(4)} ${data.unit}</span>
                                            </div>
                                        `}
                                        <div class="d-flex justify-content-between text-muted small">
                                            <span>{{ __('ui.exchange_rate') }}</span>
                                            <span>1 USD = ${exchangeRate.toFixed(2)} AFN</span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                batchHtml = '<div class="text-muted">{{ __('ui.no_stock_manual_cost') }}</div>';
                            }

                            $batchInfo.html(batchHtml).show();
                            $hint.html(`<i class="bi bi-check-circle text-success me-1"></i> Latest cost loaded: ${currency === 'USD' ? '$' : '؋'}${(currency === 'USD' ? costInUsd : costInAfn).toFixed(4)} ${data.unit}`);
                        } else {
                            $batchInfo.html(`<div class="text-danger">${response.message || 'Could not fetch cost.'}</div>`).show();
                            $hint.html('<i class="bi bi-exclamation-triangle text-warning me-1"></i> ' + (response.message || 'Cost not found. Enter manually.'));
                        }
                        $costInput.prop('disabled', false);
                        $costInput.attr('placeholder', 'Enter cost');
                        calculateItemCost(id);
                    },
                    error: function() {
                        $batchInfo.html('<div class="text-warning">{{ __('ui.server_cost_error') }}</div>').show();
                        $costInput.prop('disabled', false);
                        $costInput.attr('placeholder', 'Enter cost');
                        $hint.html('<i class="bi bi-exclamation-triangle text-warning me-1"></i> Could not fetch cost. Enter manually.');
                        calculateItemCost(id);
                    }
                });
            }

            // ─── UPDATE TOTAL COST ───
            function updateTotalCost() {
                let totalCostUsd = 0;
                let totalCostAfn = 0;
                // Adhesive/mixing rows carry physical material cost only, so they
                // are excluded from the commercial Standard Work / Profit base.
                let workBaseAfn = 0;
                const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;

                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    const quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                    const costUsd = parseFloat($(`#cost-usd-${id}`).val()) || 0;
                    const costAfn = parseFloat($(`#cost-afn-${id}`).val()) || 0;
                    const formulaType = $(`#formula-type-${id}`).val();
                    const wastage = parseFloat($(`#item-wastage-${id}`).val()) || 0;
                    const purchaseCurrency = $(`#purchase-currency-${id}`).val() || 'AFN';

                    const multiplier = (formulaType === 'carton_3d' || formulaType === 'cut_roll')
                        ? 1
                        : (1 + wastage / 100);

                    let itemCostUsd = quantity * costUsd * multiplier;
                    let itemCostAfn = quantity * costAfn * multiplier;

                    if (purchaseCurrency === 'AFN') {
                        itemCostAfn = quantity * costAfn * multiplier;
                        itemCostUsd = itemCostAfn / exchangeRate;
                    }

                    totalCostUsd += itemCostUsd;
                    totalCostAfn += itemCostAfn;

                    if (formulaType !== 'adhesive_mix') {
                        workBaseAfn += itemCostAfn;
                    }
                });

                $('#totalMaterialCost').html(`
                    Material: $${totalCostUsd.toFixed(2)} USD
                    <span class="text-muted">(؋${totalCostAfn.toFixed(2)} AFN)</span>
                `);

                updateCostSummary(totalCostUsd, totalCostAfn, workBaseAfn);
            }

            // ─── UPDATE COST SUMMARY ───
            function updateCostSummary(totalCostUsd, totalCostAfn, workBaseAfn) {
                const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;
                const workPercentage = parseFloat($('#workPercentageInput').val()) || 40;
                const profitMargin = parseFloat($('#profitMarginInput').val()) || 0;

                const materialCostAfn = totalCostAfn || (totalCostUsd * exchangeRate);
                const workCostAfn = (workBaseAfn === undefined ? materialCostAfn : workBaseAfn) * (workPercentage / 100);
                const totalCostAfnTotal = materialCostAfn + workCostAfn;
                const sellingPriceAfn = totalCostAfnTotal * (1 + (profitMargin / 100));

                $('#summaryMaterialCostUsd').text('$ ' + (totalCostUsd || 0).toFixed(2));
                $('#summaryMaterialCostAfn').text('؋ ' + materialCostAfn.toFixed(2));
                $('#summaryWorkCostAfn').text('؋ ' + workCostAfn.toFixed(2));
                $('#summaryTotalCost').text('؋ ' + totalCostAfnTotal.toFixed(2));
                $('#summarySellingPrice').text('؋ ' + sellingPriceAfn.toFixed(2));
                $('#summaryExchangeRate').text(`1 USD = ${exchangeRate.toFixed(2)} AFN`);

                if (totalCostUsd > 0 || totalCostAfn > 0) {
                    $('#costSummary').show();
                } else {
                    $('#costSummary').hide();
                }
            }

            // ─── EXCHANGE RATE CHANGE ───
            $('#mainExchangeRate').on('input', function() {
                const exchangeRate = parseFloat($(this).val()) || defaultExchangeRate;
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    const currency = $(`#purchase-currency-${id}`).val() || 'AFN';
                    const costUsd = parseFloat($(`#cost-usd-${id}`).val()) || 0;

                    if (currency === 'USD') {
                        const costAfn = costUsd * exchangeRate;
                        $(`#cost-afn-${id}`).val(costAfn.toFixed(2));
                        $(`#cost-afn-display-${id}`).val(costAfn.toFixed(2));
                    } else {
                        const costAfn = parseFloat($(`#cost-afn-${id}`).val()) || 0;
                        if (costAfn > 0) {
                            const newCostUsd = costAfn / exchangeRate;
                            $(`#cost-usd-${id}`).val(newCostUsd.toFixed(4));
                        }
                    }
                    calculateItemCost(id);
                });
                checkUsdMaterialExists();
            });

            // ─── WORK PERCENTAGE CHANGE ───
            $('#workPercentageInput').on('input', function() {
                const value = parseFloat($(this).val()) || 0;
                $('#workPercentDisplay').text(value);
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    if ($(`#formula-type-${id}`).val() === 'carton_3d') {
                        $(`#carton-work-percentage-${id}`).val(value);
                        calculateFormulaBasedItem(id);
                    }
                });
                updateTotalCost();
            });

            // ─── PROFIT MARGIN CHANGE ───
            $('#profitMarginInput').on('input', function() {
                updateTotalCost();
            });

            // ─── FORM SUBMIT ───
            $('#bomForm').on('submit', function(e) {
                const itemCount = $('#itemsContainer .bom-item-row').length;
                if (itemCount === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Materials Added',
                        text: 'Please add at least one raw material to the BOM.',
                        confirmButtonColor: '#4f46e5'
                    });
                    return false;
                }

                let valid = true;
                $('.bom-item-row').each(function() {
                    const select = $(this).find('select[name*="[material_id]"]');
                    const quantity = $(this).find('input[name*="[quantity]"]');
                    const cost = $(this).find('input[name*="[cost_per_unit_usd]"]');
                    const id = $(this).attr('id').replace('item-', '');
                    const formulaType = $(`#formula-type-${id}`).val();

                    if (!select.val()) {
                        valid = false;
                        $(this).addClass('border-danger');
                    }
                    if (!quantity.val() || parseFloat(quantity.val()) <= 0) {
                        valid = false;
                        $(this).addClass('border-danger');
                    }
                    if (parseFloat(cost.val()) < 0) {
                        valid = false;
                        $(this).addClass('border-danger');
                    }
                    if (formulaType === 'adhesive_mix') {
                        const length = parseFloat($(`#adhesive-length-${id}`).val()) || 0;
                        const width = parseFloat($(`#adhesive-width-${id}`).val()) || 0;
                        const height = parseFloat($(`#adhesive-height-${id}`).val()) || 0;
                        const recipe = parseFloat($(`#adhesive-recipe-${id}`).val()) || 0;
                        if (length <= 0 || width <= 0 || height <= 0 || recipe <= 0) {
                            valid = false;
                            $(this).addClass('border-danger');
                        }
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Errors',
                        text: 'Please fix all highlighted fields.',
                        confirmButtonColor: '#4f46e5'
                    });
                    return false;
                }

                $('#submitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
                );
            });

            // ─── INITIAL LOAD ───
            setTimeout(function() {
                checkUsdMaterialExists();
                // Calculate all existing items. toggleFormulaFields() also disables
                // the inactive formula groups so shared field names (length_inch,
                // per_gram_rate, formula_constant, ...) cannot overwrite the active
                // formula on submit.
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    toggleFormulaFields(id);
                    if ($(`#formula-type-${id}`).val() !== 'fixed') {
                        calculateFormulaBasedItem(id);
                    }
                });
                updateCostSummary(0, 0);
            }, 500);
        });
    </script>
@endsection
