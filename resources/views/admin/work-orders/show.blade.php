{{-- resources/views/admin/work-orders/show.blade.php --}}

@extends('layouts.admin.base')

@section('title', 'Work Order Details')

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
        .operation-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
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
        .action-btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item {
            position: relative;
            padding: 0.5rem 0;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.75rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4f46e5;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #4f46e5;
        }
        .timeline-item.completed::before {
            background: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }
        .timeline-item.pending::before {
            background: #f59e0b;
            box-shadow: 0 0 0 2px #f59e0b;
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
                        Work Order <span class="accent">#{{ $workOrder->work_order_number }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-list-check me-1"></i>
                        {{ $workOrder->operation_label }}
                        <span class="status-badge {{ $workOrder->status }} ms-2">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            {{ $workOrder->status_label }}
                        </span>
                    </p>
                </div>
                <div class="action-btn-group">
                    <a href="{{ route('work-orders.index') }}" class="btn btn-light">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>

                    @if($workOrder->status === 'pending')
                        <form action="{{ route('work-orders.start', $workOrder) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success" onclick="return confirm('Start this work order?')">
                                <i class="bi bi-play-fill me-1"></i> {{ __('ui.start') }}
                            </button>
                        </form>
                    @endif

                    @if($workOrder->status === 'in_progress')
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.complete') }}
                        </button>
                    @endif

                    @if($workOrder->status === 'pending')
                        <form action="{{ route('work-orders.destroy', $workOrder) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this work order?')">
                                <i class="bi bi-trash me-1"></i> {{ __('ui.delete') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Work Order Information --}}
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i> Work Order Information
                        </h6>
                    </div>
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="detail-label">Work Order Number</div>
                            <div class="detail-value">{{ $workOrder->work_order_number }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.production_order') }}</div>
                            <div class="detail-value">
                                <a href="{{ route('production-orders.show', $workOrder->productionOrder) }}" class="text-primary text-decoration-none">
                                    {{ $workOrder->productionOrder->order_number ?? 'N/A' }}
                                </a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.product') }}</div>
                            <div class="detail-value">{{ $workOrder->productionOrder->product->name ?? 'N/A' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.operation_type') }}</div>
                            <div class="detail-value">
                                <span class="operation-badge {{ $workOrder->operation_type }}">
                                    {{ $workOrder->operation_label }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.status') }}</div>
                            <div class="detail-value">
                                <span class="status-badge {{ $workOrder->status }}">
                                    {{ $workOrder->status_label }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">{{ __('ui.assigned_to') }}</div>
                            <div class="detail-value">{{ $workOrder->assignedTo->name ?? 'Unassigned' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="detail-label">Estimated Time</div>
                            <div class="detail-value">{{ number_format($workOrder->estimated_time, 1) }} hours</div>
                        </div>
                        @if($workOrder->actual_time > 0)
                            <div class="mb-3">
                                <div class="detail-label">Actual Time</div>
                                <div class="detail-value">{{ number_format($workOrder->actual_time, 1) }} hours</div>
                            </div>
                        @endif
                        @if($workOrder->notes)
                            <div>
                                <div class="detail-label">{{ __('ui.notes') }}</div>
                                <div class="detail-value">{{ $workOrder->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="col-md-6">
                <div class="table-card">
                    <div class="card-header-custom">
                        <h6 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i> Timeline
                        </h6>
                    </div>
                    <div class="p-3">
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <div class="fw-semibold">{{ __('ui.created') }}</div>
                                <div class="text-muted" style="font-size: 0.85rem;">
                                    {{ $workOrder->created_at->format('d M Y, h:i A') }}
                                </div>
                            </div>

                            @if($workOrder->started_at)
                                <div class="timeline-item {{ $workOrder->status === 'pending' ? 'pending' : 'completed' }}">
                                    <div class="fw-semibold">Started</div>
                                    <div class="text-muted" style="font-size: 0.85rem;">
                                        {{ $workOrder->started_at->format('d M Y, h:i A') }}
                                    </div>
                                </div>
                            @endif

                            @if($workOrder->completed_at)
                                <div class="timeline-item completed">
                                    <div class="fw-semibold">{{ __('ui.completed') }}</div>
                                    <div class="text-muted" style="font-size: 0.85rem;">
                                        {{ $workOrder->completed_at->format('d M Y, h:i A') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Complete Modal --}}
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('work-orders.complete', $workOrder) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-check2-circle me-2"></i> {{ __('ui.complete_work_order') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Complete work order <strong>{{ $workOrder->work_order_number }}</strong>?</p>
                        <div class="mb-3">
                            <label class="form-label">{{ __('ui.actual_time_hours') }} <span class="text-danger">*</span></label>
                            <input type="number" name="actual_time" class="form-control" step="0.1" min="0.1" required>
                            <small class="text-muted">Enter the actual time taken to complete this work order.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check2 me-1"></i> {{ __('ui.complete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
