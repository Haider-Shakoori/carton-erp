{{-- resources/views/admin/production-orders/create.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Create Production Order')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .bom-preview {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        .bom-preview .material-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        .bom-preview .material-item:last-child {
            border-bottom: none;
        }
        .bom-preview .material-item .material-name {
            font-weight: 500;
        }
        .bom-preview .material-item .material-qty {
            font-weight: 600;
        }
        .shortage-warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
        }
        .shortage-danger {
            background: #fecaca;
            border: 1px solid #f87171;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
        }
        .cost-summary-box {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .cost-summary-box .cost-item {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
        }
        .cost-summary-box .cost-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .sale-info-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        .sale-info-card .sale-detail {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            font-size: 0.9rem;
        }
        .sale-info-card .sale-detail .label {
            color: #6b7280;
        }
        .sale-info-card .sale-detail .value {
            font-weight: 600;
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
        .status-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-badge.confirmed {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-badge.delivered {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.shipped {
            background: #fef3c7;
            color: #92400e;
        }
        .currency-badge {
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 700;
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
        }
        .currency-badge.afn {
            background: #fef3c7;
            color: #92400e;
        }
        .currency-badge.usd {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-plus-circle me-2"></i>
                        Create Production <span class="accent">{{ __('ui.order') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        Create a new production order from a Sales Order
                    </p>
                </div>
                <div>
                    <a href="{{ route('production-orders.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i> Production Order Information
                </h6>
            </div>
            <div class="p-4">
                <form action="{{ route('production-orders.store') }}" method="POST" id="productionOrderForm">
                    @csrf

                    <div class="row g-3">
                        {{-- ─── SALE ORDER SELECTION ─── --}}
                        <div class="col-md-6">
                            <label class="form-label">Select Sale Order <span class="text-danger">*</span></label>
                            <select class="form-select select2-sale @error('sale_id') is-invalid @enderror"
                                    name="sale_id" id="sale_id" required>
                                <option value="">Select a Sale Order...</option>
                                @foreach($availableSales as $sale)
                                    <option value="{{ $sale->id }}" {{ old('sale_id') == $sale->id ? 'selected' : '' }}
                                    data-currency="{{ $sale->currency->code ?? 'AFN' }}"
                                            data-exchange="{{ $sale->exchange_rate ?? 66 }}">
                                        #{{ $sale->sale_no }} - {{ $sale->customer->name ?? 'N/A' }}
                                        ({{ $sale->currency->code ?? 'AFN' }})
                                        <span class="status-badge {{ $sale->status }}">
                                            {{ ucfirst($sale->status) }}
                                        </span>
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Only confirmed sales without production orders are shown
                            </small>
                            @error('sale_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ─── PRODUCT (Auto-filled from Sale) ─── --}}
                        <div class="col-md-6">
                            <label class="form-label">Product to Produce <span class="text-danger">*</span></label>
                            <select class="form-select select2-product @error('product_id') is-invalid @enderror"
                                    name="product_id_display" id="product_id" required disabled>
                                <option value="">{{ __('ui.select_sale_order_first') }}</option>
                            </select>
                            {{-- ─── FIX: Hidden input for product_id ─── --}}
                            <input type="hidden" name="product_id" id="product_id_hidden" value="">
                            @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ─── BOM (Auto-filled from Product) ─── --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.bom') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-bom @error('bom_id') is-invalid @enderror"
                                    name="bom_id_display" id="bom_id" required disabled>
                                <option value="">{{ __('ui.select_sale_order_first') }}</option>
                            </select>
                            {{-- ─── FIX: Hidden input for bom_id ─── --}}
                            <input type="hidden" name="bom_id" id="bom_id_hidden" value="">
                            @error('bom_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        {{-- ─── QUANTITY (Auto-filled from Sale) ─── --}}
                        <div class="col-md-3">
                            <label class="form-label">Quantity to Produce <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('quantity_ordered') is-invalid @enderror"
                                   name="quantity_ordered" id="quantity_ordered"
                                   value="{{ old('quantity_ordered', 1) }}" min="1" step="1" required readonly>
                            @error('quantity_ordered')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ─── CURRENCY DISPLAY ─── --}}
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.currency') }}</label>
                            <div class="form-control" style="background: #f8fafc; font-weight: 600;" id="currencyDisplay">
                                <i class="bi bi-currency-exchange me-1"></i>
                                <span id="currencyCodeDisplay">AFN</span>
                                <span class="badge bg-secondary ms-1" id="exchangeRateDisplay">1 USD = 66 AFN</span>
                            </div>
                        </div>

                        {{-- ─── START DATE ─── --}}
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   name="start_date" id="start_date"
                                   value="{{ old('start_date', date('Y-m-d')) }}" required>
                            @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ─── STATUS ─── --}}
                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.status') }}</label>
                            <select class="form-select" name="status" id="status">
                                <option value="pending">{{ __('ui.pending') }}</option>
                            </select>
                            <small class="text-muted">New orders start as Pending</small>
                        </div>

                        {{-- ─── NOTES ─── --}}
                        <div class="col-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      name="notes" id="notes" rows="3"
                                      placeholder="{{ __('ui.production_order_notes') }}">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- ─── SALE ORDER INFO CARD ─── --}}
                    <div id="saleInfoCard" class="sale-info-card">
                        <h6 class="mb-2">
                            <i class="bi bi-cart-plus me-2"></i> Linked Sale Order Details
                            <span class="badge bg-primary ms-2" id="saleStatusBadge">{{ __('ui.confirmed') }}</span>
                        </h6>
                        <div id="saleDetails">
                            <!-- Will be populated via AJAX -->
                        </div>
                    </div>

                    {{-- ─── BOM PREVIEW ─── --}}
                    <div id="bomPreview" class="bom-preview">
                        <h6 class="mb-2">
                            <i class="bi bi-list-ul me-2"></i> Material Requirements
                            <span id="bomName" class="badge bg-primary ms-2"></span>
                            <span class="badge currency-badge afn ms-1" id="bomCurrencyBadge">
                                <i class="bi bi-currency-exchange me-1"></i>
                                <span id="bomCurrencyCode">AFN</span>
                            </span>
                        </h6>
                        <div id="bomMaterials">
                            <!-- Will be populated via AJAX -->
                        </div>
                    <div id="bomSummary" class="cost-summary-box">
                        <div class="cost-item">
                            <span>Material Cost (incl. wastage):</span>
                            <span id="bomTotalCost" class="fw-semibold text-primary">؋0.00</span>
                        </div>
                        <div class="cost-item">
                            <span class="text-muted">Standard Work / Profit (<span id="bomWorkPercentage">40</span>%) — Commercial</span>
                            <span id="bomWorkCost" class="fw-semibold text-muted">؋0.00</span>
                        </div>
                        <div class="cost-item cost-total">
                            <span>Estimated Production Cost:</span>
                            <span id="bomTotalCostWithOverhead" class="fw-semibold text-primary">؋0.00</span>
                        </div>
                        <div class="cost-item">
                            <span>Estimated Cost per Unit:</span>
                            <span id="bomCostPerUnit" class="fw-semibold">؋0.00</span>
                        </div>
                        <div class="cost-item">
                            <span>Expected Profit / Loss:</span>
                            <span id="bomExpectedProfit" class="fw-semibold">؋0.00</span>
                        </div>
                            <div id="bomShortageWarning" style="display: none;" class="shortage-warning mt-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <span id="bomShortageMessage"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                <i class="bi bi-check2 me-1"></i> Create Production Order
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('production-orders.index') }}'">
                                <i class="bi bi-x-lg me-1"></i> {{ __('ui.cancel') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Store data ───
            let currentSaleData = null;
            let currentBomId = null;

            // ─── Initialize Select2 ───
            $('.select2-sale').select2({
                placeholder: 'Select a Sale Order...',
                allowClear: true,
                width: '100%'
            });

            // ─── When Sale Order is selected ───
            $('#sale_id').on('change', function() {
                const saleId = $(this).val();
                if (saleId) {
                    loadSaleDetails(saleId);
                } else {
                    resetForm();
                }
            });

            // ─── Load Sale Details ───
            function loadSaleDetails(saleId) {
                const $productSelect = $('#product_id');
                const $bomSelect = $('#bom_id');
                const $quantityInput = $('#quantity_ordered');
                const $saleInfoCard = $('#saleInfoCard');
                const $saleDetails = $('#saleDetails');
                const $bomPreview = $('#bomPreview');
                const $submitBtn = $('#submitBtn');

                // Show loading state
                $productSelect.html('<option value="">{{ __('ui.loading') }}</option>').prop('disabled', true);
                $bomSelect.html('<option value="">{{ __('ui.loading') }}</option>').prop('disabled', true);
                $quantityInput.prop('readonly', true);
                $submitBtn.prop('disabled', true);
                $bomPreview.hide();

                $.ajax({
                    url: '/admin/sales/' + saleId + '/details',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            currentSaleData = response;
                            currentBomId = response.bom_id;

                            // ─── Update Sale Info Card ───
                            let html = `
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.sale_order') }}</span>
                                    <span class="value">#${response.sale_no}</span>
                                </div>
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.customer') }}</span>
                                    <span class="value">${response.customer_name || 'N/A'}</span>
                                </div>
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.total_amount') }}</span>
                                    <span class="value">${response.currency_symbol || '؋'}${response.total_amount}</span>
                                </div>
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.items') }}</span>
                                    <span class="value">${response.item_count} items</span>
                                </div>
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.status') }}</span>
                                    <span class="value">
                                        <span class="status-badge ${response.status}">
                                            ${response.status.charAt(0).toUpperCase() + response.status.slice(1)}
                                        </span>
                                    </span>
                                </div>
                                <div class="sale-detail">
                                    <span class="label">{{ __('ui.currency') }}</span>
                                    <span class="value">
                                        <span class="badge ${response.currency_code === 'AFN' ? 'bg-warning' : 'bg-success'}">
                                            ${response.currency_code || 'AFN'}
                                        </span>
                                        <span class="text-muted ms-2">(1 USD = ${response.exchange_rate || 66} ${response.currency_code || 'AFN'})</span>
                                    </span>
                                </div>
                            `;

                            // ─── Add Sale Items ───
                            if (response.sale_items && response.sale_items.length > 0) {
                                html += `
                                    <div class="sale-detail" style="border-top: 1px solid #e5e7eb; padding-top: 0.5rem; margin-top: 0.5rem;">
                                        <span class="label" style="font-weight: 600;">Sale Items</span>
                                    </div>
                                `;
                                response.sale_items.forEach(function(item) {
                                    html += `
                                        <div class="sale-detail" style="padding-left: 1rem;">
                                            <span class="label">${item.product_name}</span>
                                            <span class="value">${item.qty} x ${response.currency_symbol}${item.unit_price} = ${response.currency_symbol}${item.total}</span>
                                        </div>
                                    `;
                                });
                            }

                            $saleDetails.html(html);
                            $saleInfoCard.show();

                            // ─── Update Currency Display ───
                            const currencyCode = response.currency_code || 'AFN';
                            const currencySymbol = response.currency_symbol || '؋';
                            const exchangeRate = response.exchange_rate || 66;

                            $('#currencyCodeDisplay').text(currencyCode);
                            $('#exchangeRateDisplay').text(`1 USD = ${exchangeRate} ${currencyCode}`);
                            $('#bomCurrencyCode').text(currencyCode);

                            // Update badge color
                            const badge = $('#bomCurrencyBadge');
                            badge.removeClass('afn usd');
                            badge.addClass(currencyCode === 'AFN' ? 'afn' : 'usd');

                            // ─── Set Quantity ───
                            $quantityInput.val(response.quantity || 1);

                            // ─── Update Sale Status Badge ───
                            $('#saleStatusBadge').text(response.status.charAt(0).toUpperCase() + response.status.slice(1));
                            $('#saleStatusBadge').removeClass('bg-primary bg-success bg-warning bg-danger');
                            if (response.status === 'confirmed') {
                                $('#saleStatusBadge').addClass('bg-primary');
                            } else if (response.status === 'delivered') {
                                $('#saleStatusBadge').addClass('bg-success');
                            } else if (response.status === 'shipped') {
                                $('#saleStatusBadge').addClass('bg-warning');
                            } else {
                                $('#saleStatusBadge').addClass('bg-secondary');
                            }

                            // ─── Set Product ───
                            if (response.product_id) {
                                $productSelect.html(`
                                    <option value="${response.product_id}" selected>${response.product_name || 'Product'}</option>
                                `).prop('disabled', true);

                                // ─── FIX: Set hidden input ───
                                $('#product_id_hidden').val(response.product_id);
                            } else {
                                $productSelect.html('<option value="">No product found</option>').prop('disabled', true);
                                $('#product_id_hidden').val('');
                            }

                            // ─── Set BOM ───
                            if (response.bom_id) {
                                const bomDisplayText = response.bom_code ? `${response.bom_code} - ${response.bom_name || 'BOM'}` : `BOM #${response.bom_id}`;
                                $bomSelect.html(`
                                    <option value="${response.bom_id}" selected>${bomDisplayText}</option>
                                `).prop('disabled', true);

                                // ─── FIX: Set hidden input ───
                                $('#bom_id_hidden').val(response.bom_id);

                                // ─── Load BOM details ───
                                const quantity = $('#quantity_ordered').val() || 1;
                                const currencyCode = $('#currencyCodeDisplay').text();
                                const exchangeRate = parseFloat($('#exchangeRateDisplay').text().split('=')[1]) || 66;

                                fetchBomDetails(response.bom_id, quantity, currencyCode, exchangeRate);
                            } else {
                                $bomSelect.html('<option value="">No BOM found</option>').prop('disabled', true);
                                $('#bom_id_hidden').val('');
                                $submitBtn.prop('disabled', false);
                            }

                            $submitBtn.prop('disabled', false);
                        } else {
                            alert('Error loading sale details: ' + (response.message || 'Unknown error'));
                            resetForm();
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error loading sale details.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                        resetForm();
                    }
                });
            }

            // ─── Fetch BOM Details ───
            function fetchBomDetails(bomId, quantity, currencyCode, exchangeRate) {
                const $preview = $('#bomPreview');
                const $materials = $('#bomMaterials');
                const $totalCost = $('#bomTotalCost');
                const $workCost = $('#bomWorkCost');
                const $workPercentage = $('#bomWorkPercentage');
                const $expectedProfit = $('#bomExpectedProfit');
                const $totalCostWithOverhead = $('#bomTotalCostWithOverhead');
                const $costPerUnit = $('#bomCostPerUnit');
                const $shortageWarning = $('#bomShortageWarning');
                const $shortageMessage = $('#bomShortageMessage');
                const $submitBtn = $('#submitBtn');

                $preview.show();
                $materials.html('<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span> Loading material requirements...</div>');
                $shortageWarning.hide();
                $submitBtn.prop('disabled', true);

                const currencySymbol = currencyCode === 'AFN' ? '؋' : '$';

                $.ajax({
                    url: '{{ route("production-orders.materials") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        sale_id: $('#sale_id').val(),
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            const bomName = $('#bom_id option:selected').text() || 'BOM';
                            $('#bomName').text(bomName);

                            const summary = response.summary || {};
                            const requirements = response.requirements || [];

                            // ─── Use AFN values for display ───
                            const materialCost = Number(summary.material_cost_afn || 0);
                            const workPercentage = Number(summary.work_percentage ?? 40);
                            const totalCost = Number(summary.total_cost_afn || 0);
                            const costPerUnit = Number(summary.cost_per_unit_afn || 0);

                            // ─── Display material requirements ───
                            if (requirements.length > 0) {
                                let html = '';
                                requirements.forEach(function(req) {
                                    const isAvailable = req.is_available ?? (req.available_stock >= req.total_required);
                                    const statusIcon = isAvailable
                                        ? '<i class="bi bi-check-circle text-success"></i>'
                                        : '<i class="bi bi-exclamation-triangle text-danger"></i>';

                                    const totalCostAfn = req.total_cost_afn || req.total_cost_usd || 0;

                                    html += `
                                        <div class="material-item">
                                            <span class="material-name">
                                                ${statusIcon}
                                                ${req.material_name}
                                                <small class="text-muted">(${req.category || 'Uncategorized'})</small>
                                            </span>
                                            <span class="material-qty">
                                                ${(req.total_required || 0).toFixed(4)} ${req.unit || 'kg'}
                                                <span class="text-muted">(Stock: ${(req.available_stock || 0).toFixed(4)} ${req.unit || 'kg'})</span>
                                                <span class="fw-semibold ms-2">${currencySymbol}${totalCostAfn.toFixed(2)}</span>
                                            </span>
                                        </div>
                                    `;
                                });
                                $materials.html(html);
                            } else {
                                $materials.html('<div class="text-muted">No materials required for this BOM.</div>');
                            }

                            // ─── Update costs with currency symbol ───
                            // If any material lacks a valid roll/KG cost basis, mirror the
                            // Sale Order: show "Unavailable" instead of inventing a cost.
                            const rollWeightMissing = requirements.some(r => r.roll_weight_missing === true);
                            if (rollWeightMissing) {
                                $totalCost.text(currencySymbol + 'Unavailable');
                                $workCost.text(currencySymbol + '0.00');
                                $totalCostWithOverhead.text('Unavailable — valid roll/KG cost basis required');
                                $costPerUnit.text('Unavailable');
                                $expectedProfit.text('Unavailable');
                            } else {
                                $totalCost.text(currencySymbol + materialCost.toFixed(2));
                                $totalCostWithOverhead.text(currencySymbol + totalCost.toFixed(2));
                                $costPerUnit.text(currencySymbol + costPerUnit.toFixed(2));

                                // Compare the sale revenue with estimated production cost (material only).
                                const saleTotal = currentSaleData
                                    ? Number(String(currentSaleData.total_amount || 0).replace(/,/g, ''))
                                    : 0;
                                const expectedProfit = saleTotal - totalCost;
                                const expectedLabel = expectedProfit >= 0 ? 'Profit' : 'Loss';
                                $expectedProfit
                                    .text(`${expectedLabel}: ${currencySymbol}${Math.abs(expectedProfit).toFixed(2)}`)
                                    .removeClass('text-success text-danger')
                                    .addClass(expectedProfit >= 0 ? 'text-success' : 'text-danger');
                            }
                            $workPercentage.text(workPercentage.toFixed(2).replace(/\.00$/, ''));

                            // ─── Check for shortages ───
                            const hasShortage = response.has_shortage || false;

                            if (hasShortage) {
                                const shortages = requirements.filter(r =>
                                    (r.shortage || 0) > 0 || r.available_stock < r.total_required
                                );

                                let message = '⚠️ Material shortages detected:<br>';
                                if (shortages.length > 0) {
                                    shortages.forEach(s => {
                                        const shortageQty = s.shortage || (s.total_required - s.available_stock);
                                        message += `- ${s.material_name}: Need ${(s.total_required || 0).toFixed(4)} ${s.unit || 'unit'}, Available: ${(s.available_stock || 0).toFixed(4)} ${s.unit || 'unit'}<br>`;
                                    });
                                } else {
                                    message += 'Some materials are short in stock. Please check availability.';
                                }

                                $shortageMessage.html(message);
                                $shortageWarning.removeClass('shortage-warning');
                                $shortageWarning.addClass('shortage-danger');
                                $shortageWarning.show();
                                $submitBtn.prop('disabled', true);
                            } else {
                                $shortageWarning.hide();
                                $submitBtn.prop('disabled', false);
                            }
                        } else {
                            $materials.html('<div class="text-danger"><i class="bi bi-exclamation-circle me-1"></i> ' + (response.message || 'Error loading BOM details.') + '</div>');
                            $submitBtn.prop('disabled', true);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Error loading BOM details.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        $materials.html('<div class="text-danger"><i class="bi bi-exclamation-circle me-1"></i> ' + errorMsg + '</div>');
                        $submitBtn.prop('disabled', true);
                    }
                });
            }

            // ─── Reset Form ───
            function resetForm() {
                $('#product_id').html('<option value="">{{ __('ui.select_sale_order_first') }}</option>').prop('disabled', true);
                $('#bom_id').html('<option value="">{{ __('ui.select_sale_order_first') }}</option>').prop('disabled', true);
                $('#quantity_ordered').val(1).prop('readonly', true);
                $('#saleInfoCard').hide();
                $('#bomPreview').hide();
                $('#submitBtn').prop('disabled', true);
                $('#currencyCodeDisplay').text('AFN');
                $('#exchangeRateDisplay').text('1 USD = 66 AFN');
                $('#bomCurrencyCode').text('AFN');
                const badge = $('#bomCurrencyBadge');
                badge.removeClass('afn usd');
                badge.addClass('afn');

                // ─── Clear hidden inputs ───
                $('#product_id_hidden').val('');
                $('#bom_id_hidden').val('');
            }

            // ─── Validate form before submit ───
            $('#productionOrderForm').on('submit', function(e) {
                const saleId = $('#sale_id').val();
                const productId = $('#product_id_hidden').val();
                const bomId = $('#bom_id_hidden').val();
                const quantity = $('#quantity_ordered').val();

                if (!saleId) {
                    e.preventDefault();
                    alert('Please select a Sale Order.');
                    return false;
                }

                if (!productId) {
                    e.preventDefault();
                    alert('Product ID is missing. Please select a sale order again.');
                    return false;
                }

                if (!bomId) {
                    e.preventDefault();
                    alert('BOM ID is missing. Please select a sale order again.');
                    return false;
                }

                if (!quantity || parseInt(quantity) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid quantity.');
                    return false;
                }

                // Check if BOM preview is loaded and has no shortages
                const hasShortage = $('#bomShortageWarning').is(':visible');
                if (hasShortage) {
                    e.preventDefault();
                    alert('Cannot create production order due to material shortages. Please purchase more stock first.');
                    return false;
                }

                return true;
            });
        });
    </script>
@endsection
