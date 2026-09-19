{{-- resources/views/admin/work-orders/create.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Create Work Order')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .operation-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .operation-badge.printing {
            background: #e0e7ff;
            color: #4f46e5;
        }
        .operation-badge.cutting {
            background: #fce7f3;
            color: #be185d;
        }
        .operation-badge.gluing {
            background: #d1fae5;
            color: #065f46;
        }
        .operation-badge.folding {
            background: #fef3c7;
            color: #92400e;
        }
        .operation-badge.lamination {
            background: #ede9fe;
            color: #6d28d9;
        }
        .operation-badge.quality_check {
            background: #f1f5f9;
            color: #475569;
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
                        Create Work <span class="accent">{{ __('ui.order') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-list-check me-1"></i>
                        Assign a new task for production
                    </p>
                </div>
                <div>
                    <a href="{{ route('work-orders.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-info-circle me-2"></i> Work Order Information
                </h6>
            </div>
            <div class="p-4">
                <form action="{{ route('work-orders.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.production_order') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2-production-order @error('production_order_id') is-invalid @enderror"
                                    name="production_order_id" id="production_order_id" required>
                                <option value="">{{ __('ui.select_production_order') }}</option>
                                @foreach($productionOrders as $order)
                                    <option value="{{ $order->id }}" {{ old('production_order_id', $selectedProductionOrder->id ?? '') == $order->id ? 'selected' : '' }}>
                                        {{ $order->order_number }} - {{ $order->product->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('production_order_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.operation_type') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('operation_type') is-invalid @enderror"
                                    name="operation_type" id="operation_type" required>
                                <option value="">{{ __('ui.select_operation') }}</option>
                                <option value="printing" {{ old('operation_type') == 'printing' ? 'selected' : '' }}>🖨️ Printing</option>
                                <option value="cutting" {{ old('operation_type') == 'cutting' ? 'selected' : '' }}>✂️ Cutting</option>
                                <option value="gluing" {{ old('operation_type') == 'gluing' ? 'selected' : '' }}>🧪 Gluing</option>
                                <option value="folding" {{ old('operation_type') == 'folding' ? 'selected' : '' }}>📄 Folding</option>
                                <option value="lamination" {{ old('operation_type') == 'lamination' ? 'selected' : '' }}>📋 Lamination</option>
                                <option value="quality_check" {{ old('operation_type') == 'quality_check' ? 'selected' : '' }}>✅ Quality Check</option>
                            </select>
                            @error('operation_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.assigned_to') }}</label>
                            <select class="form-select select2-user @error('assigned_to') is-invalid @enderror"
                                    name="assigned_to" id="assigned_to">
                                <option value="">{{ __('ui.unassigned') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->username ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('ui.estimated_time_hours') }}</label>
                            <input type="number" class="form-control @error('estimated_time') is-invalid @enderror"
                                   name="estimated_time" id="estimated_time"
                                   value="{{ old('estimated_time', 0) }}" step="0.1" min="0">
                            @error('estimated_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">{{ __('ui.notes') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                      name="notes" id="notes" rows="3"
                                      placeholder="{{ __('ui.work_order_notes') }}">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i> Create Work Order
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('work-orders.index') }}'">
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
            $('.select2-production-order').select2({
                placeholder: 'Select Production Order...',
                allowClear: true
            });

            $('.select2-user').select2({
                placeholder: 'Select User...',
                allowClear: true
            });

            // Preview operation type badge
            $('#operation_type').on('change', function() {
                const val = $(this).val();
                const badge = $('#operation_preview');
                if (val) {
                    const labels = {
                        'printing': '🖨️ Printing',
                        'cutting': '✂️ Cutting',
                        'gluing': '🧪 Gluing',
                        'folding': '📄 Folding',
                        'lamination': '📋 Lamination',
                        'quality_check': '✅ Quality Check'
                    };
                    badge.text(labels[val] || val);
                    badge.show();
                } else {
                    badge.hide();
                }
            });
        });
    </script>
@endsection
