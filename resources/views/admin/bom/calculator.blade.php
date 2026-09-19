{{-- resources/views/admin/bom/calculator.blade.php --}}

@extends('layouts.admin.base')

@section('title', __('ui.bom_calculator'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .result-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            animation: fadeIn 0.5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.2s ease;
        }
        .result-item:hover {
            background: #f1f5f9;
            margin: 0 -0.5rem;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            border-radius: 4px;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: #4f46e5;
        }
        .result-price {
            color: #059669;
            font-weight: 700;
        }
        .result-profit {
            color: #10b981;
            font-weight: 700;
        }
        .result-loss {
            color: #ef4444;
            font-weight: 700;
        }
        .material-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .material-list::-webkit-scrollbar {
            width: 6px;
        }
        .material-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .material-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .material-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .material-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
            border-radius: 4px;
        }
        .material-item:hover {
            background: #f8fafc;
        }
        .material-item:last-child {
            border-bottom: none;
        }
        .material-item .material-name {
            font-weight: 500;
            color: #1e293b;
        }
        .material-item .material-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
            flex-wrap: wrap;
        }
        .material-item .material-details .qty {
            font-weight: 600;
            color: #4f46e5;
        }
        .material-item .material-details .cost {
            color: #059669;
        }
        .material-item .material-details .unit {
            color: #94a3b8;
            font-size: 0.8rem;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e5e7eb;
        }
        .summary-card .label {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .summary-card .value {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }
        .summary-card .value.green {
            color: #10b981;
        }
        .summary-card .value.red {
            color: #ef4444;
        }
        .summary-card .value.blue {
            color: #4f46e5;
        }
        .summary-card .value.purple {
            color: #8b5cf6;
        }
        .summary-card .sub-text {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #94a3b8;
        }
        .empty-state i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        .availability-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .availability-badge.available {
            background: #d1fae5;
            color: #065f46;
        }
        .availability-badge.shortage {
            background: #fecaca;
            color: #991b1b;
        }
        .availability-badge.low {
            background: #fef3c7;
            color: #92400e;
        }
        .profit-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .profit-badge.positive {
            background: #d1fae5;
            color: #065f46;
        }
        .profit-badge.negative {
            background: #fecaca;
            color: #991b1b;
        }
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 0.75rem !important;
            color: #1e293b !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
        .select2-container--default .select2-dropdown {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }
        .currency-display {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .capacity-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .capacity-alert.success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .capacity-alert.warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        .capacity-alert.danger {
            background: #fecaca;
            border: 1px solid #f87171;
            color: #991b1b;
        }
        .capacity-alert .alert-icon {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        .capacity-alert .max-production {
            font-weight: 700;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
            .material-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
            .material-item .material-details {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        {{-- Page Header --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-calculator me-2"></i>
                        {{ __('ui.bom') }} <span class="accent">{{ __('ui.calculator') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        Calculate material requirements, costs, and check inventory availability
                        <span class="currency-display ms-2">
                            <i class="bi bi-currency-exchange me-1"></i>
                            Currency: {{ $currencySymbol }} ({{ $currencyCode }})
                        </span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('bom.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to BOMs
                    </a>
                </div>
            </div>
        </div>

        {{-- Calculator Form --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-gear me-2"></i> Calculation Parameters
                </h6>
            </div>
            <div class="p-4">
                <form id="calculatorForm">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">{{ __('ui.select_bom') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-bom" name="bom_id" id="bom_id" required style="width: 100%;">
                                <option value="">{{ __('ui.select_bom_dots') }}</option>
                                @foreach($boms as $bom)
                                    <option value="{{ $bom->id }}">
                                        {{ $bom->code }} - {{ $bom->name }} ({{ $bom->product->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.quantity') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity" id="quantity"
                                   value="1" min="1" step="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100" id="calculateBtn">
                                <i class="bi bi-calculator me-1"></i> Calculate & Check Availability
                            </button>
                        </div>
                    </div>
                </form>

                {{-- ─── RESULTS ─── --}}
                <div id="resultsContainer" style="display: none;">
                    <div class="result-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Calculation Results
                                <span id="resultProductName" class="badge bg-primary ms-2"></span>
                            </h6>
                            <span class="text-muted" style="font-size: 0.8rem;">
                                <i class="bi bi-currency-exchange me-1"></i>
                                {{ $currencySymbol }} ({{ $currencyCode }})
                            </span>
                        </div>

                        {{-- Availability Alert --}}
                        <div id="availabilityAlert" style="display: none;"></div>

                        {{-- Summary Grid --}}
                        <div class="summary-grid mb-4">
                            <div class="summary-card" style="border-color: #4f46e5; background: #eef2ff;">
                                <div class="label">{{ __('ui.total_cost_per_unit') }}</div>
                                <div class="value" style="color: #4f46e5; font-size: 1.5rem;" id="totalCost">{{ $currencySymbol }}0.00</div>
                                <div class="sub-text">{{ __('ui.material_labor_overhead') }}</div>
                            </div>
                            <div class="summary-card" style="border-color: #10b981; background: #ecfdf5;">
                                <div class="label">{{ __('ui.selling_price_per_unit') }}</div>
                                <div class="value green" style="font-size: 1.5rem;" id="sellingPrice">{{ $currencySymbol }}0.00</div>
                                <div class="sub-text">{{ __('ui.including_profit_margin') }}</div>
                            </div>
                            <div class="summary-card" style="border-color: #8b5cf6; background: #f5f3ff;">
                                <div class="label">{{ __('ui.profit_per_unit') }}</div>
                                <div class="value" style="color: #8b5cf6; font-size: 1.5rem;" id="profit">{{ $currencySymbol }}0.00</div>
                                <div class="sub-text">
                                    <span id="profitMarginDisplay">0.0%</span> margin
                                    <span class="profit-badge positive" id="profitBadge">{{ __('ui.positive') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Material Requirements --}}
                        <h6 class="mb-2">
                            <i class="bi bi-list-ul me-2"></i> Material Requirements
                            <span class="badge bg-secondary" id="materialCount">0</span>
                        </h6>
                        <div class="material-list" id="materialRequirements">
                            <div class="empty-state">
                                <i class="bi bi-box-seam"></i>
                                <p>{{ __('ui.no_materials_display') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Initialize Select2 ───
            $('.select2-bom').select2({
                placeholder: @json(__('ui.select_bom_dots')),
                allowClear: true,
                width: '100%',
                dropdownParent: $('.table-card')
            });

            // ─── Currency Setup ───
            const currencySymbol = '{{ $currencySymbol ?? '$' }}';
            const currencyCode = '{{ $currencyCode ?? 'AFN' }}';

            // ─── FIX: Safe format currency function ───
            function formatCurrency(amount, symbol) {
                // ─── FIX: Check if amount is valid ───
                if (amount === undefined || amount === null || isNaN(amount)) {
                    return (symbol || currencySymbol) + ' 0.00';
                }
                return (symbol || currencySymbol) + ' ' + parseFloat(amount).toFixed(2);
            }

            function formatCurrencyWithSymbol(amount, symbol) {
                if (amount === undefined || amount === null || isNaN(amount)) {
                    return (symbol || currencySymbol) + ' 0.00';
                }
                return (symbol || currencySymbol) + ' ' + parseFloat(amount).toFixed(2);
            }

            $('#calculatorForm').on('submit', function(e) {
                e.preventDefault();

                const bomId = $('#bom_id').val();
                const quantity = $('#quantity').val();

                if (!bomId) {
                    Swal.fire({
                        icon: 'warning',
                        title: @json(__('ui.no_bom_selected')),
                        text: @json(__('ui.select_bom_calculate')),
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                if (!quantity || quantity < 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: @json(__('ui.invalid_quantity')),
                        text: @json(__('ui.valid_quantity_min')),
                        confirmButtonColor: '#4f46e5'
                    });
                    return;
                }

                const $btn = $('#calculateBtn');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Calculating...'
                );

                $.ajax({
                    url: '{{ route("bom.calculate") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        bom_id: bomId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            displayResults(response, quantity);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: @json(__('ui.error')),
                                text: response.message || 'Error calculating BOM.',
                                confirmButtonColor: '#4f46e5'
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Error calculating BOM.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('ui.calculation_failed')),
                            text: message,
                            confirmButtonColor: '#4f46e5'
                        });
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html(originalText);
                    }
                });
            });

            function displayResults(response, quantity) {
                const bomName = $('#bom_id option:selected').text();
                $('#resultProductName').text(bomName);

                // ─── FIX: Safe access to response data ───
                const requirements = response.requirements || [];
                const summary = response.summary || {};

                // ─── FIX: Get values with fallbacks ───
                const totalCost = summary.total_cost_afn || summary.total_cost_usd || 0;
                const sellingPrice = summary.selling_price_afn || summary.selling_price_usd || 0;
                const profit = summary.profit_afn || summary.profit_usd || 0;
                const profitMargin = summary.profit_margin_percentage || 0;
                const exchangeRate = summary.exchange_rate || 66;
                const materialCost = summary.material_cost_afn || summary.material_cost_usd || 0;
                const laborCost = summary.labor_cost_afn || summary.labor_cost_usd || 0;
                const overheadCost = summary.overhead_cost_afn || summary.overhead_cost_usd || 0;
                const totalCostAfn = summary.total_cost_afn || 0;
                const sellingPriceAfn = summary.selling_price_afn || 0;
                const profitAfn = summary.profit_afn || 0;

                // ─── Check Availability ───
                let hasShortage = false;
                let maxProduction = Infinity;
                let limitingMaterial = null;

                if (requirements.length > 0) {
                    requirements.forEach(function(req) {
                        const shortage = req.shortage || 0;
                        if (shortage > 0) {
                            hasShortage = true;
                        }
                        const availableStock = req.available_stock || 0;
                        const totalRequired = req.total_required || 0;
                        if (availableStock > 0 && totalRequired > 0) {
                            const possible = Math.floor(availableStock / totalRequired);
                            if (possible < maxProduction) {
                                maxProduction = possible;
                                limitingMaterial = req;
                            }
                        } else if (totalRequired > 0 && availableStock === 0) {
                            maxProduction = 0;
                            limitingMaterial = req;
                        }
                    });
                }

                if (maxProduction === Infinity) {
                    maxProduction = 0;
                }

                // ─── Display Availability Alert ───
                const $alert = $('#availabilityAlert');
                let alertHtml = '';

                if (hasShortage || maxProduction < quantity) {
                    if (maxProduction === 0) {
                        alertHtml = `
                            <div class="capacity-alert danger">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-x-circle-fill alert-icon"></i>
                                    <div>
                                        <strong>{{ __('ui.cannot_produce') }}</strong><br>
                                        Insufficient raw materials to produce ${quantity} units.
                                        ${limitingMaterial ? `Limiting material: <strong>${limitingMaterial.material_name}</strong> (${limitingMaterial.available_stock || 0} ${limitingMaterial.unit || 'unit'} available, needs ${limitingMaterial.total_required || 0} ${limitingMaterial.unit || 'unit'})` : ''}
                                        <br>
                                        <small class="text-muted">{{ __('ui.please_purchase_materials') }}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else if (maxProduction < quantity) {
                        alertHtml = `
                            <div class="capacity-alert warning">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                                    <div>
                                        <strong>{{ __('ui.low_stock_warning') }}</strong><br>
                                        You requested ${quantity} units, but can only produce <strong class="max-production">${maxProduction}</strong> units with current raw materials.
                                        ${limitingMaterial ? `Limiting material: <strong>${limitingMaterial.material_name}</strong> (${limitingMaterial.available_stock || 0} ${limitingMaterial.unit || 'unit'} available)` : ''}
                                        <br>
                                        <small class="text-muted">{{ __('ui.purchase_more_materials') }}</small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    alertHtml = `
                        <div class="capacity-alert success">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-check-circle-fill alert-icon"></i>
                                <div>
                                    <strong>{{ __('ui.production_capacity') }}</strong><br>
                                    You can produce <strong class="max-production">${maxProduction}</strong> units with current raw materials.
                                    ${limitingMaterial ? `Limiting material: <strong>${limitingMaterial.material_name}</strong> (${limitingMaterial.available_stock || 0} ${limitingMaterial.unit || 'unit'} available)` : ''}
                                    <br>
                                    <small class="text-muted">{{ __('ui.sufficient_stock') }}</small>
                                </div>
                            </div>
                        </div>
                    `;
                }

                $alert.html(alertHtml).show();

                // ─── Summary Cards ───
                $('#totalCost').text(formatCurrency(totalCost));
                $('#sellingPrice').text(formatCurrency(sellingPrice));

                const profitValue = parseFloat(profit) || 0;
                const profitMarginValue = parseFloat(profitMargin) || 0;

                $('#profit').text(formatCurrency(profitValue));
                $('#profitMarginDisplay').text(profitMarginValue.toFixed(1) + '%');

                const $badge = $('#profitBadge');
                if (profitValue > 0) {
                    $badge.text('💰 ' + @json(__('ui.profit'))).removeClass('negative').addClass('positive');
                } else if (profitValue < 0) {
                    $badge.text('⚠️ ' + @json(__('ui.loss'))).removeClass('positive').addClass('negative');
                } else {
                    $badge.text('⚖️ ' + @json(__('ui.break_even'))).removeClass('positive negative');
                }

                // ─── Material Requirements ───
                let html = '';
                let hasItems = false;

                if (requirements && requirements.length > 0) {
                    requirements.forEach(function(req) {
                        // ─── FIX: Safe access with fallbacks ───
                        const shortage = req.shortage || 0;
                        const availableStock = req.available_stock || 0;
                        const totalRequired = req.total_required || 0;
                        const materialName = req.material_name || 'Unknown';
                        const unit = req.unit || 'unit';
                        const totalCost = req.total_cost_usd || req.total_cost || 0;
                        const category = req.category || 'Uncategorized';

                        const isAvailable = shortage === 0;
                        const isLow = availableStock > 0 && availableStock < totalRequired;
                        const isOut = availableStock === 0;

                        let availabilityClass = 'available';
                        let availabilityText = '✅ Available';
                        if (isOut) {
                            availabilityClass = 'shortage';
                            availabilityText = '❌ Out of Stock';
                        } else if (isLow) {
                            availabilityClass = 'low';
                            availabilityText = '⚠️ Low Stock';
                        }

                        // Calculate shortage percentage
                        let shortagePercent = 0;
                        if (totalRequired > 0 && availableStock > 0) {
                            shortagePercent = ((totalRequired - availableStock) / totalRequired) * 100;
                            if (shortagePercent < 0) shortagePercent = 0;
                        } else if (totalRequired > 0 && availableStock === 0) {
                            shortagePercent = 100;
                        }

                        const shortageText = shortage > 0
                            ? `<span class="availability-badge shortage">
                                   <i class="bi bi-exclamation-triangle"></i>
                                   Shortage: ${shortage.toFixed(2)} ${unit}
                                   (${shortagePercent.toFixed(0)}%)
                               </span>`
                            : `<span class="availability-badge available">
                                   <i class="bi bi-check-circle"></i>
                                   ${availableStock} ${unit} available
                               </span>`;

                        html += `
                            <div class="material-item">
                                <span class="material-name">
                                    <i class="bi bi-box-seam me-1"></i>
                                    ${materialName}
                                    <small class="text-muted">(${category})</small>
                                </span>
                                <div class="material-details">
                                    <span class="qty">${(totalRequired || 0).toFixed(4)}</span>
                                    <span class="unit">${unit}</span>
                                    <span class="cost">${formatCurrency(totalCost)}</span>
                                    ${shortageText}
                                </div>
                            </div>
                        `;
                        hasItems = true;
                    });
                }

                if (!hasItems) {
                    html = `
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <p>{{ __('ui.no_bom_materials') }}</p>
                        </div>
                    `;
                }

                $('#materialRequirements').html(html);
                $('#materialCount').text(requirements ? requirements.length : 0);

                // ─── Show Results ───
                $('#resultsContainer').show();
                $('html, body').animate({
                    scrollTop: $('#resultsContainer').offset().top - 100
                }, 500);
            }

            // ─── Auto-submit on Enter key ───
            $('#quantity').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#calculatorForm').submit();
                }
            });

            // ─── If there's a pre-selected BOM, auto-calculate ───
            const initialBom = $('#bom_id').val();
            if (initialBom) {
                $('#calculatorForm').submit();
            }
        });
    </script>
@endsection
