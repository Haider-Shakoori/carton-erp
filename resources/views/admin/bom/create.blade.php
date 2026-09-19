{{-- resources/views/admin/bom/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.create_bom'))

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
        .auto-calc-badge {
            display: inline-block;
            font-size: 0.55rem;
            font-weight: 600;
            padding: 0.1rem 0.4rem;
            border-radius: 10px;
            background: #d1fae5;
            color: #065f46;
            margin-left: 0.3rem;
        }

        /* ─── Stock Summary ─── */
        .stock-summary-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
        }
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

{{-- resources/views/admin/bom/create.blade.php --}}
{{-- CONTENT SECTION --}}

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-plus-circle me-2"></i> {{ __('ui.create') }} <span class="accent">{{ __('ui.bom') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i> Create a production-ready BOM with landed inventory costing and a separate commercial selling rate
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('bom.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="alert alert-primary border-0 shadow-sm mb-4" style="background:#eef2ff;color:#3730a3;">
            <div class="d-flex gap-2 align-items-start">
                <i class="bi bi-shield-check fs-5"></i>
                <div>
                    <strong>Costing rule:</strong> raw-material cost comes from the latest arrived landed inventory rate.
                    Roll paper is always costed in <strong>USD/kg</strong>. Wastage increases physical production cost,
                    while Standard Work / Profit and optional Additional Markup determine the commercial selling price.
                </div>
            </div>
        </div>

        <form action="{{ route('bom.store') }}" method="POST" id="bomForm">
            @csrf

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
                                       name="name" value="{{ old('name') }}" required>
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
                                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
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
                                          name="description" rows="2">{{ old('description') }}</textarea>
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
                                <label class="form-label">Standard Work / Profit (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('work_percentage') is-invalid @enderror"
                                       name="work_percentage" value="{{ old('work_percentage', 40) }}"
                                       step="0.1" min="0" required>
                                <small class="text-muted">Commercial markup on base material only. It is not recorded as actual production labour/overhead.</small>
                                @error('work_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.profit_margin_percent') }}</label>
                                <input type="number" class="form-control @error('profit_margin_percentage') is-invalid @enderror"
                                       name="profit_margin_percentage" value="{{ old('profit_margin_percentage', 0) }}"
                                       step="0.1" min="0" max="100">
                                <small class="text-muted">Optional extra markup applied after the Standard Work / Profit amount.</small>
                                @error('profit_margin_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Exchange Rate (USD to AFN)</label>
                                <div class="exchange-rate-container" id="mainExchangeRateContainer">
                                    <input type="number" class="form-control @error('exchange_rate') is-invalid @enderror"
                                           name="exchange_rate" id="mainExchangeRate"
                                           value="{{ old('exchange_rate', $exchangeRate ?? 85) }}"
                                           step="0.01" min="0.0001">
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
                            <span class="badge bg-primary ms-2" id="itemCount">0</span>
                            <span class="text-muted ms-2" style="font-weight: 400; font-size: 0.7rem;">
                                <i class="bi bi-info-circle"></i> Formula rows calculate physical consumption; landed inventory cost remains the costing source of truth
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="total-cost-display" id="totalMaterialCost">
                                Material: $0.00 USD <span class="text-muted">(؋0.00 AFN)</span>
                            </span>
                            <button type="button" class="btn btn-primary btn-sm" id="addItem">
                                <i class="bi bi-plus-circle me-1"></i> Add Material
                            </button>
                        </div>

                        <div id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </div>

                        <div id="noItemsMessage" class="text-center text-muted py-4">
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
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.status') }}</label>
                            <select class="form-select" name="status">
                                <option value="draft">{{ __('ui.draft') }}</option>
                                <option value="active" selected>{{ __('ui.active') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.create_bom') }}
                        </button>
                    </div>

                    <!-- Sidebar Cost Summary -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-calculator"></i> BOM Cost & Price Summary
                        </div>
                        <div id="costSummary" style="display: none;">
                            <div class="cost-summary-card">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="cost-label">Base Material</div>
                                        <div class="cost-value material" id="summaryBaseMaterialCostAfn">؋0.00</div>
                                        <small class="text-muted">Before wastage</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Wastage Cost</div>
                                        <div class="cost-value" id="summaryWastageCostAfn">؋0.00</div>
                                        <small class="text-muted">Production cost only</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Physical Material Cost</div>
                                        <div class="cost-value material" id="summaryMaterialCostAfn">؋0.00</div>
                                        <small class="text-muted">Includes wastage</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Standard Work / Profit</div>
                                        <div class="cost-value" id="summaryWorkCostAfn" style="color:#7c3aed;">؋0.00</div>
                                        <small class="text-muted">Commercial only</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Print Cost</div>
                                        <div class="cost-value" id="summaryPrintCostAfn">؋0.00</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="cost-label">Additional Markup</div>
                                        <div class="cost-value" id="summaryMarkupAfn">؋0.00</div>
                                        <small class="text-muted"><span id="summaryProfitMargin">0%</span></small>
                                    </div>
                                </div>
                                <div class="cost-summary-divider"></div>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <div class="cost-label">Commercial Base Rate</div>
                                        <div class="cost-value total" id="summaryCommercialBase">؋0.00</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="cost-label">Final Selling Price / Unit</div>
                                        <div class="cost-value selling" id="summarySellingPrice">؋0.00</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between text-muted small mt-1">
                                            <span id="summaryMaterialCostUsd">$0.00 physical material</span>
                                            <span id="summaryExchangeRate">1 USD = 85 AFN</span>
                                        </div>
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

            // ─── VARIABLES ───
            let itemCounter = 0;
            const defaultExchangeRate = {{ $exchangeRate ?? 85 }};
            let hasUsdMaterial = false;

            // ─── INCHES TO CM CONVERSION ───
            window.inchesToCm = function(inches) {
                return inches * 2.54;
            };

            window.convertInchesToCm = function(inputElement, targetId) {
                if (!inputElement || !targetId) return;
                const inches = parseFloat(inputElement.value) || 0;
                const cm = inchesToCm(inches);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.textContent = cm.toFixed(2);
                }
            };

            // ─── SAFE NUMBER FUNCTIONS ───
            function safeNumber(value, decimals = 2) {
                if (isNaN(value) || !isFinite(value) || value === null || value === undefined) {
                    return 0;
                }
                return parseFloat(value.toFixed(decimals));
            }

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
                    <span class="material-cost-preview" id="cost-preview-${id}">Physical cost: $0.00 USD</span>
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
                                    {{ $material->name }}
                <span class="text-muted small">
                    ({{ $material->purchase_currency ?? 'AFN' }})
                                    </span>
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
                            <option value="fixed_percentage">{{ __('ui.material_percent') }}</option>
                            <option value="fixed_rate">{{ __('ui.fixed_rate') }}</option>
                        </select>
                        <input type="hidden" name="items[${id}][is_formula_based]"
                               id="is-formula-${id}" value="0">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Consumption / Finished Unit <span class="text-danger">*</span></label>
                        <input type="number" class="form-control quantity-input auto-calculated"
                               name="items[${id}][quantity]"
                               id="quantity-${id}"
                               step="0.0001" min="0.0001"
                               placeholder="{{ __('ui.auto_calculated') }}"
                               readonly>
                        <small class="text-muted" id="rolls-hint-${id}">
                            <i class="bi bi-magic"></i> Formula rows calculate physical consumption automatically
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
                                        <strong>{{ __('ui.step_5') }}</strong> Standard Work / Profit = Paper Rate × Layers × configured %<br>
                                        <strong>{{ __('ui.step_6') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + Standard Work / Profit
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
                                <div class="form-label-sm">Landed Paper Rate (AFN/kg)</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][per_gram_rate]"
                                       id="per-gram-rate-${id}"
                                       placeholder="Auto" step="0.0001" min="0" value="0"
                                       readonly>
                                <small class="text-muted">Synced from the selected material's landed inventory cost.</small>
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
                                        <strong>{{ __('ui.step_4') }}</strong> Standard Work / Profit = Paper Rate × Layers × configured %<br>
                                        <strong>{{ __('ui.step_5') }}</strong> Row Net Rate = Print Cost + Paper Rate × Layers + Standard Work / Profit
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
                                <div class="form-label-sm">Landed Paper Rate (AFN/kg)</div>
                                <input type="number" class="form-control-sm-custom"
                                       name="items[${id}][per_gram_rate]"
                                       id="per-gram-rate-cut-${id}"
                                       placeholder="Auto" step="0.0001" min="0" value="0"
                                       readonly>
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
                            <span style="color: #475569; font-weight: 500;">Standard Work / Profit</span>
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
                        <label class="form-label">Landed Inventory Cost <span class="currency-label usd" id="cost-basis-label-${id}">USD / unit</span></label>
                        <input type="number" class="form-control cost-input"
                               name="items[${id}][cost_per_unit_usd]"
                               id="cost-usd-${id}"
                               value="0" step="0.00001" min="0"
                               oninput="calculateItemCost(${id})">
                        <input type="hidden" name="items[${id}][cost_per_unit_afn]" id="cost-afn-${id}" value="0">
                        <small class="text-muted cost-hint" id="cost-hint-${id}">
                            Auto-filled from the latest arrived landed inventory cost
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
                        <label class="form-label">Source Purchase Currency</label>
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
                $('#itemsContainer').prepend(html);
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

                        // Update currency display
                        $(`#currency-display-${id}`).val(currency);
                        $(`#purchase-currency-${id}`).val(currency);
                        $(`#purchase-currency-id-${id}`).val(currencyId);
                        $(`#unit-${id}`).val(unit);

                        // Update currency badge
                        const badge = $(`#currency-badge-${id}`);
                        if (currency === 'USD') {
                            badge.removeClass('afn').addClass('usd').text('USD');
                        } else {
                            badge.removeClass('usd').addClass('afn').text('AFN');
                        }

                        // Check if we have USD materials
                        checkUsdMaterialExists();

                        // Fetch material cost
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

                // ─── Formula type toggle ───
                toggleFormulaFields(itemCounter);

                // ─── Setup auto-calculation ───
                setupAutoCalculation(itemCounter);

                // ─── Quantity and cost change handlers ───
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
                    // 3D Carton fields
                    `#length-${id}`, `#width-${id}`, `#height-${id}`,
                    `#paper-gsm-${id}`, `#per-gram-rate-${id}`, `#layers-${id}`,
                    `#multiplication-layer-${id}`, `#print-${id}`,
                    `#formula-constant-${id}`, `#carton-work-percentage-${id}`,
                    // Cut/Roll fields
                    `#cut-length-${id}`, `#cut-width-${id}`, `#grh-${id}`,
                    `#per-gram-rate-cut-${id}`, `#ply-${id}`, `#print-cut-${id}`,
                    `#multiplication-layer-cut-${id}`, `#formula-constant-cut-${id}`,
                    `#cut-work-percentage-${id}`, `#multiplication-method-${id}`,
                    // Fixed Percentage fields
                    `#base-material-${id}`, `#percentage-of-base-${id}`,
                    // Fixed Rate fields
                    `#rate-per-unit-${id}`, `#rate-base-units-${id}`,
                    // Common
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

                // Hide all formula groups
                $(`#carton-3d-fields-${id}`).hide();
                $(`#cut-roll-fields-${id}`).hide();
                $(`#fixed-percentage-fields-${id}`).hide();
                $(`#fixed-rate-fields-${id}`).hide();

                // Show selected formula group
                if (formulaType === 'carton_3d') {
                    $(`#carton-3d-fields-${id}`).show();
                    $(`#reel-dimensions-${id}`).show();
                    $(`#carton-calculation-details-${id}`).show();
                } else if (formulaType === 'cut_roll') {
                    $(`#cut-roll-fields-${id}`).show();
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

                // Update formula badge
                const badge = $(`#formula-badge-${id}`);
                const labels = {
                    fixed: { class: 'fixed', label: 'Fixed' },
                    carton_3d: { class: 'carton_3d', label: '3D Carton' },
                    cut_roll: { class: 'cut_roll', label: 'Cut/Roll' },
                    fixed_percentage: { class: 'fixed_percentage', label: '% of Material' },
                    fixed_rate: { class: 'fixed_rate', label: 'Fixed Rate' }
                };
                const info = labels[formulaType] || labels.fixed;
                badge.removeClass('fixed carton_3d cut_roll fixed_percentage fixed_rate')
                    .addClass(info.class)
                    .text(info.label);

                // Recalculate if formula-based
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

                // ─── 3D CARTON FORMULA (EXACT EXCEL MATCH) ───
                if (formulaType === 'carton_3d') {
                    const length = parseFloat($(`#length-${id}`).val()) || 0;
                    const width = parseFloat($(`#width-${id}`).val()) || 0;
                    const height = parseFloat($(`#height-${id}`).val()) || 0;
                    const gsm = parseFloat($(`#paper-gsm-${id}`).val()) || 0;
                    const perGramRate = parseFloat($(`#per-gram-rate-${id}`).val()) || 0;
                    const layers = parseFloat($(`#layers-${id}`).val()) || 1;
                    const multiplicationLayer = parseFloat($(`#multiplication-layer-${id}`).val()) || 1;
                    const printCost = parseFloat($(`#print-${id}`).val()) || 0;
                    const constant = parseFloat($(`#formula-constant-${id}`).val()) || 1550000;
                    const workPercentage = parseFloat($(`#carton-work-percentage-${id}`).val()) || 40;

                    if (length > 0 && width > 0 && height > 0 && gsm > 0 && perGramRate > 0 && constant > 0) {

                        // ─── Step 1: Reel Length (Excel: D4 = (A4 + B4) * 2 + 4) ───
                        const reelLength = ((length + width) * 2) + 4;

                        // ─── Step 2: Reel Height (Excel: E4 = B4 + C4 + 1) ───
                        const reelHeight = width + height + 1;

                        // ─── Step 3: Division Value (Excel: M4 = D4 * E4 * F4 * G4) ───
                        const divisionValue = reelLength * reelHeight * gsm * perGramRate;

                        // ─── Step 4: Paper Rate (Excel: N4 = M4 / L4) ───
                        paperRate = divisionValue / constant;

                        // ─── Step 5: Paper Rate × Layers (Excel: O4 = H4 * N4) ───
                        paperRateByLayers = multiplicationLayer * paperRate;

                        // ─── Step 6: 40% Work (Excel: P4 = O4 * 0.4) ───
                        workCost = paperRateByLayers * 0.40;

                        // ─── Step 7: Row Net Rate (Excel: Q4 = K4 + O4 + P4) ───
                        netRate = printCost + paperRateByLayers + workCost;

                        // ─── Display Reel Dimensions ───
                        $(`#reel-dimensions-${id}`).show();
                        $(`#reel-length-display-${id}`).text(reelLength.toFixed(2));
                        $(`#reel-height-display-${id}`).text(reelHeight.toFixed(2));
                        $(`#reel-length-detail-${id}`).text(reelLength.toFixed(2));
                        $(`#reel-height-detail-${id}`).text(reelHeight.toFixed(2));

                        // ─── Display Calculation Details ───
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
                }

                // ─── CUT/ROLL FORMULA ───
                else if (formulaType === 'cut_roll') {
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
                        workCost = paperRateByLayers * 0.40;
                        netRate = printCost + paperRateByLayers + workCost;

                        // Hide reel dimensions for cut/roll
                        $(`#reel-dimensions-${id}`).hide();
                        $(`#carton-calculation-details-${id}`).hide();
                    }
                }

                // ─── FIXED PERCENTAGE ───
                else if (formulaType === 'fixed_percentage') {
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
                }

                // ─── FIXED RATE ───
                else if (formulaType === 'fixed_rate') {
                    const rate = parseFloat($(`#rate-per-unit-${id}`).val()) || 0;
                    const perUnits = parseFloat($(`#rate-base-units-${id}`).val()) || 1;
                    quantity = rate / perUnits;
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;

                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                }

                // ─── FIXED QUANTITY ───
                else {
                    quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                    paperRate = 0;
                    paperRateByLayers = 0;
                    workCost = 0;
                    netRate = 0;

                    $(`#reel-dimensions-${id}`).hide();
                    $(`#carton-calculation-details-${id}`).hide();
                }

                // ─── HANDLE CURRENCY CONVERSION FOR COST ───
                const purchaseCurrency = $(`#purchase-currency-${id}`).val() || 'AFN';
                let costInUsd = 0;
                let costInAfn = 0;

                // For 3D Carton and Cut/Roll, use the calculated net rate
                if (formulaType === 'carton_3d' || formulaType === 'cut_roll') {
                    quantity = 1;
                    const netWithWastageAfn = netRate * (1 + wastage / 100);
                    costInAfn = netWithWastageAfn;
                    costInUsd = exchangeRate > 0 ? netWithWastageAfn / exchangeRate : 0;
                } else {
                    costInUsd = parseFloat($(`#cost-usd-${id}`).val()) || 0;
                    costInAfn = costInUsd * exchangeRate;
                }

                // Store both costs
                $(`#cost-usd-${id}`).val(costInUsd.toFixed(5));
                $(`#cost-afn-${id}`).val(costInAfn.toFixed(2));
                $(`#cost-afn-display-${id}`).val(costInAfn.toFixed(2));

                // Set quantity
                $(`#quantity-${id}`).val(quantity > 0 ? quantity.toFixed(4) : '');

                // Update cost hint
                if (formulaType === 'carton_3d') {
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

            // ─── FETCH MATERIAL COST (USES LATEST PRICE) ───
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

                            // ✅ USE LATEST COST
                            let costInUsd = parseFloat(data.latest_cost_usd) || 0;
                            let costInAfn = parseFloat(data.latest_cost_afn) || 0;

                            // If latest cost is 0, fallback to weighted average
                            if (costInUsd === 0 && costInAfn === 0) {
                                costInUsd = parseFloat(data.weighted_avg_cost) || 0;
                                if (currency === 'AFN') {
                                    costInAfn = costInUsd;
                                    costInUsd = costInUsd / exchangeRate;
                                } else {
                                    costInAfn = costInUsd * exchangeRate;
                                }
                            }

                            // Update fields
                            $(`#unit-${id}`).val(data.unit || 'Unit');
                            $costInput.val(costInUsd.toFixed(4));
                            $costAfnInput.val(costInAfn.toFixed(2));
                            $costAfnDisplay.val(costInAfn.toFixed(2));

                            // Build batch info with LATEST highlighted
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
                });

                $('#totalMaterialCost').html(`
                    Material: $${totalCostUsd.toFixed(2)} USD
                    <span class="text-muted">(؋${totalCostAfn.toFixed(2)} AFN)</span>
                `);

                updateCostSummary(totalCostUsd, totalCostAfn);
            }

// ─── UPDATE COST SUMMARY ───
            function updateCostSummary(totalCostUsd, totalCostAfn) {
                const exchangeRate = parseFloat($('#mainExchangeRate').val()) || defaultExchangeRate;
                const workPercentage = parseFloat($('input[name="work_percentage"]').val()) || 40;
                const profitMargin = parseFloat($('input[name="profit_margin_percentage"]').val()) || 0;

                // ─── WORK PERCENTAGE AFFECTS THE TOTAL ───
                const materialCostAfn = totalCostAfn || (totalCostUsd * exchangeRate);
                const workCostAfn = materialCostAfn * (workPercentage / 100);
                const totalCostAfnTotal = materialCostAfn + workCostAfn;
                const sellingPriceAfn = totalCostAfnTotal * (1 + (profitMargin / 100));

                $('#summaryMaterialCostUsd').text('$ ' + (totalCostUsd || 0).toFixed(2));
                $('#summaryMaterialCostAfn').text('؋ ' + materialCostAfn.toFixed(2));
                $('#summaryWorkCostAfn').text('؋ ' + workCostAfn.toFixed(2));  // Show work cost
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
            $('input[name="work_percentage"]').on('input', function() {
                const value = parseFloat($(this).val()) || 0;
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    if ($(`#formula-type-${id}`).val() === 'carton_3d') {
                        $(`#carton-work-percentage-${id}`).val(value);
                        calculateFormulaBasedItem(id);
                    }
                });
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
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
                );
            });

            // ─── INITIAL LOAD ───
            setTimeout(function() {
                checkUsdMaterialExists();
                updateCostSummary(0, 0);
            }, 500);
        });
    </script>
@endsection
