{{-- resources/views/admin/sale-returns/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Create Sale Return')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .section-card {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 8px 32px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-card:hover {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 12px 48px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ─── Available Items List ─── */
        .available-item-row {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .available-item-row:hover {
            border-color: var(--primary);
            background: var(--primary-bg);
        }

        .available-item-row .product-info {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .available-item-row .product-info .name {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 0.875rem;
        }

        .available-item-row .product-info .details {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .available-item-row .product-info .details i {
            margin-right: 0.25rem;
        }

        /* ─── Selected Items List ─── */
        .selected-item-row {
            background: #eef2ff;
            border: 2px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .selected-item-row:hover {
            background: #e0e7ff;
        }

        .selected-item-row .row {
            align-items: center;
        }

        .selected-item-row .product-name {
            font-weight: 700;
            color: var(--gray-800);
            font-size: 0.875rem;
        }

        .selected-item-row .product-details {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        .return-qty-input {
            max-width: 120px;
            text-align: center;
            font-weight: 600;
        }

        .return-qty-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .remove-item-btn {
            color: var(--danger);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
            padding: 0.25rem 0.5rem;
            font-size: 1.25rem;
        }

        .remove-item-btn:hover {
            color: #dc2626;
            transform: scale(1.1);
        }

        .empty-items-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--gray-400);
        }

        .empty-items-state i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.75rem;
            color: var(--gray-300);
        }

        .empty-items-state .title {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9375rem;
        }

        .empty-items-state .sub-text {
            font-size: 0.8125rem;
            color: var(--gray-400);
        }

        .btn-add-item {
            padding: 0.25rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            white-space: nowrap;
        }

        .btn-add-item i {
            margin-right: 0.25rem;
        }

        .totals-preview {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--gray-200);
        }

        .totals-preview .totals-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .totals-preview .totals-grid {
                grid-template-columns: 1fr;
            }
        }

        .totals-preview .total-box {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-sm);
            background: var(--gray-50);
        }

        .totals-preview .total-box .label {
            font-size: 0.65rem;
            text-transform: uppercase;
            color: var(--gray-400);
            font-weight: 700;
        }

        .totals-preview .total-box .value {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .totals-preview .total-box.refund-box {
            background: #e0e7ff;
            border: 1px solid #a5b4fc;
        }

        .totals-preview .total-box.refund-box .value {
            color: var(--primary);
        }

        /* ─── Select2 Overrides ─── */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0.3rem 0.75rem;
            color: var(--gray-800);
            line-height: 1.5;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border-radius: var(--radius-sm);
            border-color: var(--gray-200);
            box-shadow: var(--shadow-lg);
        }

        /* ─── Badge for max qty ─── */
        .max-qty-badge {
            font-size: 0.6rem;
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            background: var(--gray-200);
            color: var(--gray-600);
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* ─── Loading Spinner ─── */
        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner .spinner-border {
            width: 2rem;
            height: 2rem;
        }

        .loading-spinner p {
            margin-top: 0.75rem;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        /* ─── Empty selected state ─── */
        .empty-selected-state {
            text-align: center;
            padding: 1.5rem;
            color: var(--gray-400);
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            border: 2px dashed var(--gray-300);
        }

        .empty-selected-state i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: var(--gray-300);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">

        {{-- PAGE HEADER --}}
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-arrow-return-left me-2" style="color: #f59e0b;"></i>
                        {{ __('ui.create') }} <span class="accent">Sale Return</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Process customer returns and manage restocking
                    </p>
                </div>
                <a href="{{ route('admin.sale-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Returns
                </a>
            </div>
        </div>

        <form id="returnForm" method="POST" action="{{ route('admin.sale-returns.store') }}">
            @csrf

            {{-- RETURN HEADER --}}
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-file-text" style="color: var(--primary);"></i>
                    Return Information
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Return Number</label>
                        <input type="text" name="return_no" class="form-control"
                            value="{{ $nextReturnNo ?? 'SR-' . date('Ym') . '-0001' }}" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Select Sale <span class="text-danger">*</span></label>
                        <select name="sale_id" id="saleSelect" class="form-select" required>
                            <option value="">Select a sale...</option>
                            @foreach ($sales ?? [] as $sale)
                                <option value="{{ $sale->id }}" {{ request('sale_id') == $sale->id ? 'selected' : '' }}>
                                    #{{ $sale->sale_no }} - {{ $sale->customer->name ?? 'N/A' }}
                                    ({{ $sale->created_at->format('Y-m-d') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('ui.restocking_fee') }}</label>
                        <input type="number" name="restocking_fee" id="restockingFee" class="form-control" value="0"
                            step="0.01" min="0">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Return Reason (General)</label>
                        <input type="text" name="reason" class="form-control"
                            placeholder="e.g., Customer returned due to quality issues">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('ui.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('ui.return_notes') }}"></textarea>
                    </div>
                </div>
            </div>

            {{-- ITEMS SECTION --}}
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-list-ul" style="color: var(--primary);"></i>
                    Items to Return
                    <span class="badge bg-secondary" id="itemCount" style="font-size: 0.65rem;">0 items selected</span>
                </div>

                {{-- Available Items (from sale) --}}
                <div id="availableItemsContainer">
                    <div class="empty-items-state" id="emptyAvailableState">
                        <i class="bi bi-box-seam"></i>
                        <div class="title">Select a sale to load items</div>
                        <div class="sub-text">Choose a confirmed or delivered sale from the dropdown above</div>
                    </div>
                </div>

                {{-- Loading Spinner --}}
                <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('ui.loading') }}</span>
                    </div>
                    <p>Loading sale items...</p>
                </div>

                {{-- Selected Items --}}
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--gray-200);">
                    <h6 style="font-weight: 700; font-size: 0.8125rem; color: var(--gray-700); margin-bottom: 0.75rem;">
                        <i class="bi bi-check2-square" style="color: var(--primary);"></i>
                        Selected Items for Return
                        <span class="badge bg-primary" id="selectedCount" style="font-size: 0.6rem;">0</span>
                    </h6>

                    <div id="selectedItemsContainer">
                        <div class="empty-selected-state" id="emptySelectedState">
                            <i class="bi bi-cart-plus"></i>
                            <div style="font-weight: 500; color: var(--gray-600);">{{ __('ui.no_items_selected') }}</div>
                            <div style="font-size: 0.8125rem;">Click "Add" on items above to include them in this return
                            </div>
                        </div>
                    </div>

                    {{-- Totals Preview --}}
                    <div id="totalsPreview" class="totals-preview" style="display: none;">
                        <div class="totals-grid">
                            <div class="total-box">
                                <div class="label"><i class="bi bi-cart"></i> {{ __('ui.subtotal') }}</div>
                                <div class="value" id="previewSubtotal">$0.00</div>
                            </div>
                            <div class="total-box">
                                <div class="label"><i class="bi bi-tag"></i> {{ __('ui.restocking_fee') }}</div>
                                <div class="value" id="previewRestockingFee">$0.00</div>
                            </div>
                            <div class="total-box refund-box">
                                <div class="label"><i class="bi bi-arrow-return-left"></i> {{ __('ui.refund_amount') }}</div>
                                <div class="value" id="previewRefund">$0.00</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="submit" class="btn btn-success" id="submitBtn">
                            <i class="bi bi-check-lg me-1"></i> Create Return
                        </button>
                        <a href="{{ route('admin.sale-returns.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i> {{ __('ui.cancel') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>

    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/jquery360/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        let selectedItems = [];
        let availableItems = [];
        let currencySymbol = '$';
        let exchangeRate = 1;
        let saleId = null;

        $(document).ready(function() {
            // Initialize Select2
            $('#saleSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search for a sale...'
            });

            // Load items when sale is selected
            $('#saleSelect').on('change', function() {
                saleId = $(this).val();
                if (saleId) {
                    loadSaleItems(saleId);
                } else {
                    resetAvailableItems();
                    resetSelectedItems();
                }
            });

            // Initial load if sale_id is pre-selected
            if ($('#saleSelect').val()) {
                saleId = $('#saleSelect').val();
                loadSaleItems(saleId);
            }

            // Restocking fee change - update totals
            $('#restockingFee').on('input', function() {
                updateTotals();
            });

            // ─── Add Item Handler ───
            $(document).on('click', '.add-item-btn', function() {
                const itemId = parseInt($(this).data('id'));
                const maxQty = parseFloat($(this).data('max-qty'));
                const productName = $(this).data('product-name');
                const unitPrice = parseFloat($(this).data('unit-price'));
                const usdUnitPrice = parseFloat($(this).data('usd-unit-price'));

                // Check if item already added
                const existing = selectedItems.find(item => item.id === itemId);
                if (existing) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Already Added',
                        text: 'This item is already in the return list.'
                    });
                    return;
                }

                // Add to selected items with default qty = max available
                selectedItems.push({
                    id: itemId,
                    product_name: productName,
                    qty: Math.min(1, maxQty),
                    max_qty: maxQty,
                    unit_price: unitPrice,
                    usd_unit_price: usdUnitPrice,
                    currency_symbol: currencySymbol,
                    reason: 'other',
                    condition: 'used',
                    reason_notes: ''
                });

                // Update UI
                renderSelectedItems();
                updateAvailableItemsUI();
                updateTotals();

                // Show success feedback
                const btn = $(this);
                const originalHtml = btn.html();
                btn.html('<i class="bi bi-check2"></i> Added').addClass('btn-success').removeClass(
                    'btn-primary');
                setTimeout(() => {
                    btn.html(originalHtml).removeClass('btn-success').addClass('btn-primary');
                }, 1500);
            });

            // ─── Remove Item Handler ───
            $(document).on('click', '.remove-item-btn', function() {
                const itemId = parseInt($(this).data('id'));
                selectedItems = selectedItems.filter(item => item.id !== itemId);
                renderSelectedItems();
                updateAvailableItemsUI();
                updateTotals();
            });

            // ─── Quantity Change Handler ───
            $(document).on('input', '.return-qty-input', function() {
                const itemId = parseInt($(this).data('id'));
                const val = parseFloat($(this).val()) || 0;
                const maxQty = parseFloat($(this).data('max-qty')) || 0;

                if (val > maxQty) {
                    $(this).val(maxQty);
                    updateItemQty(itemId, maxQty);
                } else if (val < 0.01) {
                    $(this).val(0.01);
                    updateItemQty(itemId, 0.01);
                } else {
                    updateItemQty(itemId, val);
                }
            });

            // ─── Reason Change Handler ───
            $(document).on('change', '.return-reason-select', function() {
                const itemId = parseInt($(this).data('id'));
                const reason = $(this).val();
                const item = selectedItems.find(i => i.id === itemId);
                if (item) {
                    item.reason = reason;
                }
            });

            // ─── Condition Change Handler ───
            $(document).on('change', '.return-condition-select', function() {
                const itemId = parseInt($(this).data('id'));
                const condition = $(this).val();
                const item = selectedItems.find(i => i.id === itemId);
                if (item) {
                    item.condition = condition;
                }
            });

            // ─── Reason Notes Change Handler ───
            $(document).on('input', '.return-reason-notes', function() {
                const itemId = parseInt($(this).data('id'));
                const notes = $(this).val();
                const item = selectedItems.find(i => i.id === itemId);
                if (item) {
                    item.reason_notes = notes;
                }
            });

            // ─── Form Submission ───
            $('#returnForm').on('submit', function(e) {
                e.preventDefault();

                if (selectedItems.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Items',
                        text: 'Please add at least one item to return.'
                    });
                    return;
                }

                // Validate quantities
                let valid = true;
                let errorMessage = '';
                for (const item of selectedItems) {
                    if (item.qty <= 0 || item.qty > item.max_qty) {
                        valid = false;
                        errorMessage =
                            `Invalid quantity for ${item.product_name}. Please enter a value between 0.01 and ${item.max_qty}.`;
                        break;
                    }
                }

                if (!valid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Quantity',
                        text: errorMessage
                    });
                    return;
                }

                // Build form data
                const formData = new FormData(this);
                selectedItems.forEach((item, index) => {
                    formData.append(`items[${index}][sale_item_id]`, item.id);
                    formData.append(`items[${index}][qty_returned]`, item.qty);
                    formData.append(`items[${index}][reason]`, item.reason);
                    formData.append(`items[${index}][condition]`, item.condition);
                    formData.append(`items[${index}][reason_notes]`, item.reason_notes || '');
                });

                // Submit
                $('#submitBtn').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span> Creating...'
                );

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success !== undefined && !response.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Error creating return'
                            });
                            $('#submitBtn').prop('disabled', false).html(
                                '<i class="bi bi-check-lg me-1"></i> Create Return'
                            );
                            return;
                        }

                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = '{{ route('admin.sale-returns.index') }}';
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Error creating return';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            msg = errors.join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg
                        });
                        $('#submitBtn').prop('disabled', false).html(
                            '<i class="bi bi-check-lg me-1"></i> Create Return'
                        );
                    }
                });
            });
        });

        // ─── LOAD SALE ITEMS ───
        function loadSaleItems(saleId) {
            $('#loadingSpinner').show();
            $('#availableItemsContainer').hide();

            $.ajax({
                url: '{{ route('admin.sale-returns.get-sale-items') }}',
                method: 'GET',
                data: {
                    sale_id: saleId
                },
                success: function(response) {
                    $('#loadingSpinner').hide();

                    if (response.success && response.items.length > 0) {
                        currencySymbol = response.currency_symbol || '$';
                        exchangeRate = response.exchange_rate || 1;
                        availableItems = response.items;
                        renderAvailableItems(availableItems);
                        $('#availableItemsContainer').show();

                        // Reset selected items when loading new sale
                        selectedItems = [];
                        renderSelectedItems();
                        updateTotals();
                    } else {
                        availableItems = [];
                        selectedItems = [];
                        renderSelectedItems();
                        $('#availableItemsContainer').html(`
                            <div class="empty-items-state">
                                <i class="bi bi-inboxes"></i>
                                <div class="title">No items available for return</div>
                                <div class="sub-text">All items from this sale may have been fully returned already</div>
                            </div>
                        `);
                        $('#availableItemsContainer').show();
                        updateTotals();
                    }
                },
                error: function() {
                    $('#loadingSpinner').hide();
                    $('#availableItemsContainer').html(`
                        <div class="empty-items-state">
                            <i class="bi bi-exclamation-triangle" style="color: var(--danger);"></i>
                            <div class="title">Error loading items</div>
                            <div class="sub-text">Please try again or select a different sale</div>
                        </div>
                    `);
                    $('#availableItemsContainer').show();
                }
            });
        }
        // ─── RENDER AVAILABLE ITEMS ───
        function renderAvailableItems(items) {
            // Filter out items that are already selected
            const available = items.filter(item =>
                !selectedItems.some(selected => selected.id === item.id)
            );

            if (available.length === 0) {
                $('#availableItemsContainer').html(`
            <div class="empty-items-state" style="padding: 1rem;">
                <i class="bi bi-check2-all" style="color: var(--success);"></i>
                <div class="title">All items have been selected</div>
                <div class="sub-text">All available items from this sale have been added to the return</div>
            </div>
        `);
                return;
            }

            let html = `
        <div style="margin-bottom: 0.75rem;">
            <span style="font-size: 0.75rem; color: var(--gray-500);">
                <i class="bi bi-boxes"></i> ${available.length} items available for return
            </span>
        </div>
    `;

            available.forEach(function(item) {
                // Ensure numeric values with safe fallbacks
                const availableQty = parseFloat(item.available_qty) || 0;
                const unitPrice = parseFloat(item.unit_price) || 0;
                const alreadyReturned = parseFloat(item.already_returned) || 0;

                const isDisabled = availableQty <= 0;
                html += `
            <div class="available-item-row" ${isDisabled ? 'style="opacity: 0.5;"' : ''}>
                <div class="product-info">
                    <div class="name">${item.product_name || 'Unknown Product'}</div>
                    <div class="details">
                        <i class="bi bi-box"></i> ${availableQty.toFixed(2)} available
                        ${alreadyReturned > 0 ? `<span class="text-muted">· ${alreadyReturned.toFixed(2)} already returned</span>` : ''}
                        <span class="text-muted">· ${currencySymbol}${unitPrice.toFixed(2)} / unit</span>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-primary btn-add-item add-item-btn"
                        data-id="${item.id}"
                        data-max-qty="${availableQty}"
                        data-product-name="${item.product_name || 'Unknown Product'}"
                        data-unit-price="${unitPrice}"
                        data-usd-unit-price="${parseFloat(item.usd_unit_price) || 0}"
                        ${isDisabled ? 'disabled' : ''}>
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
            </div>
        `;
            });

            $('#availableItemsContainer').html(html);
        }

        // ─── UPDATE AVAILABLE ITEMS UI ───
        function updateAvailableItemsUI() {
            // Re-render available items to reflect selected state
            if (availableItems.length > 0) {
                renderAvailableItems(availableItems);
            }
        }

        // ─── RENDER SELECTED ITEMS ───
        function renderSelectedItems() {
            const container = $('#selectedItemsContainer');
            const count = selectedItems.length;

            $('#selectedCount').text(count);
            $('#itemCount').text(count + ' items selected');

            if (count === 0) {
                container.html(`
                    <div class="empty-selected-state">
                        <i class="bi bi-cart-plus"></i>
                        <div style="font-weight: 500; color: var(--gray-600);">{{ __('ui.no_items_selected') }}</div>
                        <div style="font-size: 0.8125rem;">Click "Add" on items above to include them in this return</div>
                    </div>
                `);
                $('#totalsPreview').hide();
                return;
            }

            let html = '';

            selectedItems.forEach(function(item) {
                html += `
                    <div class="selected-item-row">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <div class="product-name">${item.product_name}</div>
                                <div class="product-details">
                                    ${currencySymbol}${item.unit_price.toFixed(2)} / unit
                                    <span class="max-qty-badge">Max: ${item.max_qty}</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 0.15rem;">
                                    Qty to Return
                                </label>
                                <input type="number" class="form-control form-control-sm return-qty-input" 
                                    data-id="${item.id}"
                                    data-max-qty="${item.max_qty}"
                                    value="${item.qty}" 
                                    min="0.01" 
                                    max="${item.max_qty}" 
                                    step="0.01"
                                    style="width: 100%;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 0.15rem;">
                                    {{ __('ui.reason') }}
                                </label>
                                <select class="form-select form-select-sm return-reason-select" data-id="${item.id}">
                                    <option value="damaged" ${item.reason === 'damaged' ? 'selected' : ''}>{{ __('ui.damaged') }}</option>
                                    <option value="defective" ${item.reason === 'defective' ? 'selected' : ''}>Defective</option>
                                    <option value="wrong_item" ${item.reason === 'wrong_item' ? 'selected' : ''}>Wrong Item</option>
                                    <option value="wrong_quantity" ${item.reason === 'wrong_quantity' ? 'selected' : ''}>Wrong Quantity</option>
                                    <option value="customer_cancelled" ${item.reason === 'customer_cancelled' ? 'selected' : ''}>Customer Cancelled</option>
                                    <option value="quality_issue" ${item.reason === 'quality_issue' ? 'selected' : ''}>Quality Issue</option>
                                    <option value="other" ${item.reason === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 0.15rem;">
                                    Condition
                                </label>
                                <select class="form-select form-select-sm return-condition-select" data-id="${item.id}">
                                    <option value="new" ${item.condition === 'new' ? 'selected' : ''}>New/Unused</option>
                                    <option value="used" ${item.condition === 'used' ? 'selected' : ''}>{{ __('ui.used') }}</option>
                                    <option value="damaged" ${item.condition === 'damaged' ? 'selected' : ''}>{{ __('ui.damaged') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: var(--gray-500); margin-bottom: 0.15rem;">
                                    {{ __('ui.notes') }}
                                </label>
                                <input type="text" class="form-control form-control-sm return-reason-notes" 
                                    data-id="${item.id}"
                                    placeholder="{{ __('ui.optional') }}" 
                                    value="${item.reason_notes || ''}">
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="remove-item-btn" data-id="${item.id}" title="{{ __('ui.remove_return') }}">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.html(html);
            $('#totalsPreview').show();
        }

        // ─── UPDATE ITEM QUANTITY ───
        function updateItemQty(itemId, qty) {
            const item = selectedItems.find(i => i.id === itemId);
            if (item) {
                item.qty = qty;
                updateTotals();
            }
        }

        // ─── UPDATE TOTALS ───
        function updateTotals() {
            let subtotal = 0;
            let restockingFee = parseFloat($('#restockingFee').val()) || 0;

            selectedItems.forEach(function(item) {
                subtotal += item.qty * item.unit_price;
            });

            const refund = Math.max(0, subtotal - restockingFee);

            $('#previewSubtotal').text(currencySymbol + ' ' + subtotal.toFixed(2));
            $('#previewRestockingFee').text(currencySymbol + ' ' + restockingFee.toFixed(2));
            $('#previewRefund').text(currencySymbol + ' ' + refund.toFixed(2));

            // Color coding for refund
            const refundElement = $('#previewRefund');
            if (refund > 0) {
                refundElement.css('color', 'var(--primary)');
            } else {
                refundElement.css('color', 'var(--danger)');
            }
        }

        // ─── RESET FUNCTIONS ───
        function resetAvailableItems() {
            availableItems = [];
            $('#availableItemsContainer').html(`
                <div class="empty-items-state">
                    <i class="bi bi-box-seam"></i>
                    <div class="title">Select a sale to load items</div>
                    <div class="sub-text">Choose a confirmed or delivered sale from the dropdown above</div>
                </div>
            `);
        }

        function resetSelectedItems() {
            selectedItems = [];
            renderSelectedItems();
            updateTotals();
        }
    </script>
@endsection
