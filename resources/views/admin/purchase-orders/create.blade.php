{{-- resources/views/admin/bom/create.blade.php --}}

@extends('layouts.admin.base')

@section('title', __('ui.create_bom'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .bom-item-row {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            position: relative;
            transition: all 0.3s ease;
        }
        .bom-item-row:hover {
            border-color: #4f46e5;
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
            color: #4f46e5;
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
        .section-divider {
            border-top: 2px dashed #e5e7eb;
            margin: 1.5rem 0;
        }
        .batch-info {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.75rem;
            margin-top: 0.75rem;
        }
        .batch-info .batch-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.8rem;
        }
        .batch-info .batch-item:last-child {
            border-bottom: none;
        }
        .batch-info .batch-total {
            border-top: 2px solid #e5e7eb;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 600;
        }
        .cost-info-badge {
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            background: #e0e7ff;
            color: #4f46e5;
        }
        .cost-info-badge.stock-available {
            background: #d1fae5;
            color: #065f46;
        }
        .cost-info-badge.stock-low {
            background: #fef3c7;
            color: #92400e;
        }
        .cost-info-badge.stock-out {
            background: #fecaca;
            color: #991b1b;
        }
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top-color: #4f46e5;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
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
                        Create Bill of <span class="accent">Materials</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        Define material requirements for your carton products
                    </p>
                </div>
                <div>
                    <a href="{{ route('bom.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i> BOM Information
                </h6>
            </div>
            <div class="p-4">
                <form action="{{ route('bom.store') }}" method="POST" id="bomForm">
                    @csrf

                    {{-- BOM Details --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.bom_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   name="name" id="bom_name" value="{{ old('name') }}"
                                   placeholder="e.g., 120ml Syrup Box BOM v1.0" required>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Finished Product (Carton Box) <span class="text-danger">*</span></label>
                            <select class="form-select select2-product @error('product_id') is-invalid @enderror"
                                    name="product_id" id="product_id" required>
                                <option value="">{{ __('ui.select_product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->category->name ?? 'Uncategorized' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">{{ __('ui.description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      name="description" id="description" rows="2"
                                      placeholder="{{ __('ui.carton_specs') }}">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.wastage_percent') }}</label>
                            <input type="number" class="form-control @error('wastage_percentage') is-invalid @enderror"
                                   name="wastage_percentage" id="wastage_percentage"
                                   value="{{ old('wastage_percentage', 5) }}" step="0.01" min="0" max="100">
                            <small class="text-muted">Typical wastage in carton production: 5-10%</small>
                            @error('wastage_percentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Labor Cost per Unit ($)</label>
                            <input type="number" class="form-control @error('labor_cost_per_unit') is-invalid @enderror"
                                   name="labor_cost_per_unit" id="labor_cost_per_unit"
                                   value="{{ old('labor_cost_per_unit', 0.50) }}" step="0.01" min="0">
                            <small class="text-muted">Cost per box</small>
                            @error('labor_cost_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Overhead Cost per Unit ($)</label>
                            <input type="number" class="form-control @error('overhead_cost_per_unit') is-invalid @enderror"
                                   name="overhead_cost_per_unit" id="overhead_cost_per_unit"
                                   value="{{ old('overhead_cost_per_unit', 0.30) }}" step="0.01" min="0">
                            <small class="text-muted">Machine, electricity, etc.</small>
                            @error('overhead_cost_per_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('ui.profit_margin_percent') }}</label>
                            <input type="number" class="form-control @error('profit_margin_percentage') is-invalid @enderror"
                                   name="profit_margin_percentage" id="profit_margin_percentage"
                                   value="{{ old('profit_margin_percentage', 20) }}" step="0.01" min="0" max="100">
                            <small class="text-muted">Recommended: 20-30%</small>
                            @error('profit_margin_percentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold text-dark" for="is_active">
                                    {{ __('ui.active_status') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- BOM Items (Raw Materials) --}}
                    <div class="section-divider"></div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">
                                    <i class="bi bi-box-seam me-2"></i> Raw Materials
                                    <span class="badge bg-primary" id="itemCount">0</span>
                                </h6>
                                <small class="text-muted">Select raw materials required to make this carton</small>
                            </div>
                            <div>
                                <span class="total-cost-display me-3" id="totalMaterialCost">Total: $0.00</span>
                                <button type="button" class="btn btn-sm btn-primary" id="addItem">
                                    <i class="bi bi-plus-circle me-1"></i> Add Material
                                </button>
                            </div>
                        </div>

                        <div id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </div>

                        <div id="noItemsMessage" class="text-center text-muted py-4">
                            <i class="bi bi-box-seam" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No materials added yet. Click "Add Material" to start building your BOM.
                        </div>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i> Save BOM
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('bom.index') }}'">
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
            // ============================================================
            // INITIALIZE SELECT2
            // ============================================================
            $('.select2-product').select2({
                placeholder: 'Select Finished Product...',
                allowClear: true
            });

            // ============================================================
            // VARIABLES
            // ============================================================
            let itemCounter = 0;
            let totalCost = 0;
            let isFetching = false;

            // ============================================================
            // ADD ITEM
            // ============================================================
            $('#addItem').on('click', function() {
                itemCounter++;
                const html = `
                    <div class="bom-item-row" id="item-${itemCounter}">
                        <button type="button" class="remove-item" onclick="removeItem(${itemCounter})">
                            <i class="bi bi-x-circle"></i>
                        </button>
                        <div class="item-counter">
                            Material #${itemCounter}
                            <span class="material-cost-preview ms-2" id="cost-preview-${itemCounter}">Cost: $0.00</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">{{ __('ui.raw_material') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-material"
                                        name="items[${itemCounter}][material_id]"
                                        id="material-${itemCounter}"
                                        required>
                                    <option value="">{{ __('ui.select_raw_material') }}</option>
                                    @foreach($materials as $material)
                <option value="{{ $material->id }}"
                                                data-unit="{{ $material->unit }}"
                                                data-name="{{ $material->name }}">
                                            {{ $material->name }}
                </option>
@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('ui.quantity') }} <span class="text-danger">*</span></label>
                <input type="number" class="form-control quantity-input"
                       name="items[${itemCounter}][quantity]"
                                       id="quantity-${itemCounter}"
                                       step="0.0001" min="0.0001"
                                       placeholder="0.0000" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('ui.unit') }}</label>
                                <input type="text" class="form-control unit-input"
                                       name="items[${itemCounter}][unit]"
                                       id="unit-${itemCounter}"
                                       placeholder="e.g., kg, pcs" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('ui.wastage_percent') }}</label>
                                <input type="number" class="form-control"
                                       name="items[${itemCounter}][wastage_percentage]"
                                       value="5" step="0.01" min="0" max="100">
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Cost per Unit ($)</label>
                                <input type="number" class="form-control cost-input"
                                       name="items[${itemCounter}][cost_per_unit]"
                                       id="cost-${itemCounter}"
                                       value="0" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.notes') }}</label>
                                <input type="text" class="form-control"
                                       name="items[${itemCounter}][notes]"
                                       placeholder="{{ __('ui.optional_notes') }}">
                            </div>
                        </div>
                        <div id="batch-info-${itemCounter}" class="batch-info" style="display: none;">
                            <!-- Batch breakdown will be loaded here -->
                        </div>
                    </div>
                `;

                $('#itemsContainer').append(html);
                $('#noItemsMessage').hide();
                updateItemCount();

                // Initialize Select2 for new material select
                const $newSelect = $('.select2-material').last();
                $newSelect.select2({
                    placeholder: 'Select Raw Material...',
                    allowClear: true
                });

                // Auto-fetch cost when material is selected
                $newSelect.on('change', function() {
                    const materialId = $(this).val();
                    const id = $(this).attr('id').replace('material-', '');
                    if (materialId) {
                        fetchMaterialCost(materialId, id);
                    } else {
                        // Clear fields if no material selected
                        $(`#unit-${id}`).val('');
                        $(`#cost-${id}`).val(0);
                        $(`#batch-info-${id}`).hide().html('');
                        calculateItemCost(id);
                    }
                });

                // Calculate cost on quantity change
                $(`#quantity-${itemCounter}`).on('keyup change', function() {
                    calculateItemCost(itemCounter);
                });

                // Calculate cost on cost change
                $(`#cost-${itemCounter}`).on('keyup change', function() {
                    calculateItemCost(itemCounter);
                });
            });

            // ============================================================
            // FETCH MATERIAL COST FROM SERVER
            // ============================================================
            function fetchMaterialCost(materialId, id) {
                if (isFetching) return;
                isFetching = true;

                // Show loading state
                const $costInput = $(`#cost-${id}`);
                const $batchInfo = $(`#batch-info-${id}`);
                $costInput.prop('disabled', true);
                $costInput.val('Loading...');
                $batchInfo.html('<div class="text-center text-muted"><span class="loading-spinner"></span> Loading cost data...</div>').show();

                $.ajax({
                    url: '{{ route("bom.get-material-cost") }}',
                    method: 'GET',
                    data: {
                        material_id: materialId
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;

                            // Auto-fill unit
                            $(`#unit-${id}`).val(data.unit || '');

                            // Auto-fill cost
                            const cost = data.weighted_avg_cost || 0;
                            $(`#cost-${id}`).val(cost.toFixed(4));

                            // Build batch breakdown HTML
                            let batchHtml = '';
                            const batches = data.batch_breakdown?.batches || [];
                            const totalQty = data.batch_breakdown?.total_qty || 0;
                            const weightedAvg = data.batch_breakdown?.weighted_avg || 0;

                            // Stock status badge
                            let stockStatus = '';
                            if (data.current_stock > 10) {
                                stockStatus = '<span class="cost-info-badge stock-available"><i class="bi bi-check-circle"></i> In Stock</span>';
                            } else if (data.current_stock > 0 && data.current_stock <= 10) {
                                stockStatus = '<span class="cost-info-badge stock-low"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>';
                            } else {
                                stockStatus = '<span class="cost-info-badge stock-out"><i class="bi bi-x-circle"></i> Out of Stock</span>';
                            }

                            batchHtml = `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Batch Breakdown</span>
                                    ${stockStatus}
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Stock:</span>
                                    <span class="fw-semibold">${data.current_stock} ${data.unit}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Weighted Average Cost:</span>
                                    <span class="fw-semibold text-primary">$${weightedAvg.toFixed(4)}</span>
                                </div>
                            `;

                            if (batches.length > 0) {
                                batchHtml += `<div class="batch-list">`;
                                batches.forEach(function(batch) {
                                    batchHtml += `
                                        <div class="batch-item">
                                            <span>
                                                <i class="bi bi-tag me-1"></i> ${batch.batch_no || 'N/A'}
                                                <small class="text-muted">(${batch.purchase_date || 'N/A'})</small>
                                            </span>
                                            <span>
                                                ${batch.available_qty} ${data.unit} @ $${batch.cost_per_unit.toFixed(4)}
                                            </span>
                                        </div>
                                    `;
                                });
                                batchHtml += `
                                        <div class="batch-total">
                                            <div class="d-flex justify-content-between">
                                                <span>Total: ${totalQty} ${data.unit}</span>
                                                <span>Avg: $${weightedAvg.toFixed(4)}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else {
                                batchHtml += `
                                    <div class="text-muted" style="font-size: 0.8rem; padding: 0.5rem 0;">
                                        <i class="bi bi-info-circle me-1"></i> {{ __('ui.no_stock_manual_cost') }}
                                    </div>
                                `;
                            }

                            // Add note about manual override
                            batchHtml += `
                                <div class="mt-2 text-muted" style="font-size: 0.7rem; border-top: 1px solid #e5e7eb; padding-top: 0.5rem;">
                                    <i class="bi bi-pencil-square me-1"></i> You can manually override the cost above if needed.
                                </div>
                            `;

                            $batchInfo.html(batchHtml).show();
                            $costInput.prop('disabled', false);

                            // Calculate cost
                            calculateItemCost(id);

                        } else {
                            // Handle error
                            $(`#batch-info-${id}`).html(`
                                <div class="text-danger" style="font-size: 0.8rem;">
                                    <i class="bi bi-exclamation-circle me-1"></i> Could not fetch cost. Please enter manually.
                                </div>
                            `).show();
                            $(`#cost-${id}`).val(0).prop('disabled', false);
                        }
                    },
                    error: function() {
                        $(`#batch-info-${id}`).html(`
                            <div class="text-warning" style="font-size: 0.8rem;">
                                <i class="bi bi-exclamation-triangle me-1"></i> {{ __('ui.server_cost_error') }}
                            </div>
                        `).show();
                        $(`#cost-${id}`).val(0).prop('disabled', false);
                    },
                    complete: function() {
                        isFetching = false;
                    }
                });
            }

            // ============================================================
            // CALCULATE INDIVIDUAL ITEM COST
            // ============================================================
            function calculateItemCost(id) {
                const quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                const cost = parseFloat($(`#cost-${id}`).val()) || 0;
                const total = quantity * cost;
                $(`#cost-preview-${id}`).text(`Cost: $${total.toFixed(4)}`);
                updateTotalCost();
            }

            // ============================================================
            // UPDATE TOTAL COST
            // ============================================================
            function updateTotalCost() {
                let total = 0;
                let hasItems = false;
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    const quantity = parseFloat($(`#quantity-${id}`).val()) || 0;
                    const cost = parseFloat($(`#cost-${id}`).val()) || 0;
                    total += quantity * cost;
                    if (quantity > 0 && cost > 0) {
                        hasItems = true;
                    }
                });
                totalCost = total;
                $('#totalMaterialCost').text(`Total: $${total.toFixed(2)}`);

                // Update button state
                if (hasItems) {
                    $('#totalMaterialCost').removeClass('text-muted').addClass('fw-bold');
                }
            }

            // ============================================================
            // REMOVE ITEM
            // ============================================================
            window.removeItem = function(id) {
                if (confirm('Remove this material from the BOM?')) {
                    $(`#item-${id}`).remove();
                    updateItemCount();
                    updateTotalCost();
                    if ($('#itemsContainer').children().length === 0) {
                        $('#noItemsMessage').show();
                        $('#totalMaterialCost').text('Total: $0.00');
                    }
                }
            };

            // ============================================================
            // UPDATE ITEM COUNT
            // ============================================================
            function updateItemCount() {
                const count = $('#itemsContainer .bom-item-row').length;
                $('#itemCount').text(count);
            }

            // ============================================================
            // VALIDATE FORM BEFORE SUBMIT
            // ============================================================
            $('#bomForm').on('submit', function(e) {
                const itemCount = $('#itemsContainer .bom-item-row').length;
                if (itemCount === 0) {
                    e.preventDefault();
                    alert('⚠️ Please add at least one raw material to the BOM.');
                    return false;
                }

                // Validate each item has quantity and material
                let valid = true;
                let errorMessages = [];
                $('.bom-item-row').each(function() {
                    const id = $(this).attr('id').replace('item-', '');
                    const select = $(this).find('select[name*="[material_id]"]');
                    const quantity = $(this).find('input[name*="[quantity]"]');
                    const cost = $(this).find('input[name*="[cost_per_unit]"]');

                    if (!select.val()) {
                        valid = false;
                        $(this).addClass('border-danger');
                        errorMessages.push(`Material #${id}: No material selected`);
                    } else {
                        $(this).removeClass('border-danger');
                    }

                    if (!quantity.val() || parseFloat(quantity.val()) <= 0) {
                        valid = false;
                        $(this).addClass('border-danger');
                        errorMessages.push(`Material #${id}: Invalid quantity`);
                    }

                    if (parseFloat(cost.val()) < 0) {
                        valid = false;
                        $(this).addClass('border-danger');
                        errorMessages.push(`Material #${id}: Cost cannot be negative`);
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('⚠️ Please fix the following errors:\n\n' + errorMessages.join('\n'));
                    return false;
                }

                return true;
            });

            // ============================================================
            // AUTO-CALCULATE ON LOAD (if there are existing items)
            // ============================================================
            // If there are any items already in the container (from old form submission)
            $('.bom-item-row').each(function() {
                const id = $(this).attr('id').replace('item-', '');
                const materialId = $(`#material-${id}`).val();
                if (materialId) {
                    fetchMaterialCost(materialId, id);
                }
                calculateItemCost(id);
            });

            // ============================================================
            // KEYBOARD SHORTCUT: Ctrl+Enter to submit
            // ============================================================
            $(document).on('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    $('#bomForm').submit();
                }
            });

            // ============================================================
            // TOOLTIP HELPERS
            // ============================================================
            // Show cost help on hover
            $(document).on('mouseenter', '.cost-input', function() {
                const id = $(this).attr('id').replace('cost-', '');
                const $batchInfo = $(`#batch-info-${id}`);
                if ($batchInfo.is(':visible')) {
                    $batchInfo.addClass('border-primary');
                }
            });

            $(document).on('mouseleave', '.cost-input', function() {
                $('.batch-info').removeClass('border-primary');
            });

        }); // End of document ready
    </script>
@endsection
