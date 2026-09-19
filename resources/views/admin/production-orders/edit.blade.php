{{-- resources/views/admin/production-orders/edit.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Edit Production Order')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.in_progress {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-badge.completed {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.cancelled {
            background: #f3f4f6;
            color: #6b7280;
        }
        .bom-preview {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .bom-preview .material-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .bom-preview .material-item:last-child {
            border-bottom: none;
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
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Production <span class="accent">{{ __('ui.order') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-boxes me-1"></i>
                        {{ $productionOrder->order_number }}
                        <span class="status-badge {{ $productionOrder->status }} ms-2">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            {{ $productionOrder->status_label }}
                        </span>
                    </p>
                </div>
                <div>
                    <a href="{{ route('production-orders.show', $productionOrder) }}" class="btn btn-light">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view') }}
                    </a>
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
                <form action="{{ route('production-orders.update', $productionOrder) }}" method="POST" id="productionOrderForm">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.product') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-product @error('product_id') is-invalid @enderror"
                                    name="product_id" id="product_id" required>
                                <option value="">{{ __('ui.select_product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ old('product_id', $productionOrder->product_id) == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->category->name ?? 'Uncategorized' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.bom') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-bom @error('bom_id') is-invalid @enderror"
                                    name="bom_id" id="bom_id" required>
                                <option value="">Select BOM...</option>
                                @foreach($boms as $bom)
                                    <option value="{{ $bom->id }}" {{ old('bom_id', $productionOrder->bom_id) == $bom->id ? 'selected' : '' }}>
                                        {{ $bom->code }} - {{ $bom->name }} (v{{ $bom->version }})
                                    </option>
                                @endforeach
                            </select>
                            @error('bom_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantity Ordered <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('quantity_ordered') is-invalid @enderror"
                                   name="quantity_ordered" id="quantity_ordered"
                                   value="{{ old('quantity_ordered', $productionOrder->quantity_ordered) }}" min="1" step="1" required>
                            @error('quantity_ordered')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantity Produced</label>
                            <input type="number" class="form-control"
                                   name="quantity_produced" id="quantity_produced"
                                   value="{{ old('quantity_produced', $productionOrder->quantity_produced) }}" min="0" step="1" readonly>
                            <small class="text-muted">Read-only - updated during production</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('ui.status') }}</label>
                            <select class="form-select" name="status" id="status">
                                <option value="pending" {{ $productionOrder->status == 'pending' ? 'selected' : '' }}>{{ __('ui.pending') }}</option>
                                <option value="in_progress" {{ $productionOrder->status == 'in_progress' ? 'selected' : '' }}>{{ __('ui.in_progress') }}</option>
                                <option value="completed" {{ $productionOrder->status == 'completed' ? 'selected' : '' }}>{{ __('ui.completed') }}</option>
                                <option value="cancelled" {{ $productionOrder->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                            <small class="text-muted">Changing status manually is not recommended</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.start_date') }} <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                   name="start_date" id="start_date"
                                   value="{{ old('start_date', $productionOrder->start_date ? $productionOrder->start_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.completion_date') }}</label>
                            <input type="date" class="form-control"
                                   name="completion_date" id="completion_date"
                                   value="{{ old('completion_date', $productionOrder->completion_date ? $productionOrder->completion_date->format('Y-m-d') : '') }}" readonly>
                            <small class="text-muted">Auto-set when production completes</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      name="notes" id="notes" rows="3"
                                      placeholder="{{ __('ui.production_order_notes') }}">{{ old('notes', $productionOrder->notes) }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- BOM Preview --}}
                    <div id="bomPreview" class="bom-preview">
                        <h6 class="mb-2">
                            <i class="bi bi-list-ul me-2"></i> Material Requirements
                            <span id="bomName" class="badge bg-primary ms-2">{{ $productionOrder->bom->name ?? 'N/A' }}</span>
                        </h6>
                        <div id="bomMaterials">
                            @foreach($productionOrder->materials as $material)
                                <div class="material-item">
                                    <span>
                                        @if($material->shortage_quantity > 0)
                                            <i class="bi bi-exclamation-triangle text-danger"></i>
                                        @else
                                            <i class="bi bi-check-circle text-success"></i>
                                        @endif
                                        {{ $material->product->name ?? 'Unknown' }}
                                        <small class="text-muted">({{ $material->unit }})</small>
                                    </span>
                                    <span>
                                        {{ number_format($material->required_quantity, 2) }} {{ $material->unit }}
                                        <span class="text-muted">(Stock: {{ number_format($material->available_quantity, 2) }})</span>
                                        <span class="fw-semibold">${{ number_format($material->total_cost, 2) }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div id="bomSummary" class="mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Total Material Cost:</span>
                                <span id="bomTotalCost" class="fw-semibold text-primary">${{ number_format($productionOrder->total_material_cost, 2) }}</span>
                            </div>
                            @if($productionOrder->materials->contains(fn($m) => $m->shortage_quantity > 0))
                                <div class="shortage-danger mt-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Some materials have shortages. Please purchase more stock before starting production.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Update Production Order
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
            $('.select2-product').select2({
                placeholder: 'Select Product...',
                allowClear: true
            });

            $('.select2-bom').select2({
                placeholder: 'Select BOM...',
                allowClear: true
            });

            // When BOM changes, fetch new details
            $('#bom_id').on('change', function() {
                const bomId = $(this).val();
                const quantity = $('#quantity_ordered').val() || 1;

                if (bomId) {
                    fetchBomDetails(bomId, quantity);
                }
            });

            // When quantity changes, recalculate
            $('#quantity_ordered').on('keyup change', function() {
                const bomId = $('#bom_id').val();
                const quantity = $(this).val() || 1;

                if (bomId && quantity > 0) {
                    fetchBomDetails(bomId, quantity);
                }
            });

            function fetchBomDetails(bomId, quantity) {
                const $materials = $('#bomMaterials');
                const $totalCost = $('#bomTotalCost');

                $materials.html('<div class="text-muted"><i class="bi bi-hourglass-split me-1"></i> {{ __('ui.loading') }}</div>');

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
                            const bomName = $('#bom_id option:selected').text();
                            $('#bomName').text(bomName);

                            let html = '';
                            response.requirements.forEach(function(req) {
                                const statusIcon = req.available_stock >= req.total_required
                                    ? '<i class="bi bi-check-circle text-success"></i>'
                                    : '<i class="bi bi-exclamation-triangle text-danger"></i>';

                                html += `
                                    <div class="material-item">
                                        <span>
                                            ${statusIcon}
                                            ${req.material_name}
                                            <small class="text-muted">(${req.category})</small>
                                        </span>
                                        <span>
                                            ${req.total_required.toFixed(4)} ${req.unit}
                                            <span class="text-muted">(Stock: ${req.available_stock})</span>
                                            <span class="fw-semibold">$${req.total_cost.toFixed(2)}</span>
                                        </span>
                                    </div>
                                `;
                            });
                            $materials.html(html);

                            $totalCost.text('$' + response.summary.total_cost.toFixed(2));

                            // Check for shortages
                            if (response.has_shortage) {
                                const shortages = response.requirements.filter(r => r.available_stock < r.total_required);
                                let message = '⚠️ Material shortages detected:<br>';
                                shortages.forEach(s => {
                                    message += `- ${s.material_name}: Need ${s.total_required.toFixed(4)} ${s.unit}, Available: ${s.available_stock} ${s.unit}<br>`;
                                });
                                $('#bomSummary').append(`
                                    <div class="shortage-danger mt-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        ${message}
                                    </div>
                                `);
                            }
                        }
                    },
                    error: function() {
                        $materials.html('<div class="text-danger"><i class="bi bi-exclamation-circle me-1"></i> Error loading BOM details.</div>');
                    }
                });
            }
        });
    </script>
@endsection
