{{-- resources/views/admin/work-orders/index.blade.php --}}

@extends('layouts.admin.base')

@section('title', __('ui.work_orders'))

@section('css')
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
                        <i class="bi bi-tools me-2"></i>
                        Work <span class="accent">{{ __('ui.orders') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-list-check me-1"></i>
                        Manage production tasks and operations
                    </p>
                </div>
                <div>
                    <a href="{{ route('work-orders.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> New Work Order
                    </a>
                </div>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e0e7ff; color: #4f46e5;">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                        <div class="stat-label">Total Work Orders</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.pending') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.completed') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-card">
            <div class="card-header-custom">
                <h6 class="mb-0">
                    <i class="bi bi-table me-2"></i> Work Orders List
                    <span class="header-badge ms-2">{{ $workOrders->total() }} orders</span>
                </h6>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-ledger mb-0">
                        <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Work Order #</th>
                            <th>{{ __('ui.production_order') }}</th>
                            <th>{{ __('ui.operation') }}</th>
                            <th>{{ __('ui.assigned_to') }}</th>
                            <th class="text-center">{{ __('ui.status') }}</th>
                            <th class="text-end">Est. Time</th>
                            <th class="text-end">Actual Time</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($workOrders as $workOrder)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('work-orders.show', $workOrder) }}" class="fw-semibold text-primary text-decoration-none">
                                        {{ $workOrder->work_order_number }}
                                    </a>
                                </td>
                                <td>
                                        <span class="fw-semibold">
                                            {{ $workOrder->productionOrder->order_number ?? 'N/A' }}
                                        </span>
                                    <br>
                                    <small class="text-muted">{{ $workOrder->productionOrder->product->name ?? 'N/A' }}</small>
                                </td>
                                <td>
                                        <span class="operation-badge {{ $workOrder->operation_type }}">
                                            {{ $workOrder->operation_label }}
                                        </span>
                                </td>
                                <td>{{ $workOrder->assignedTo->name ?? 'Unassigned' }}</td>
                                <td class="text-center">
                                        <span class="status-badge {{ $workOrder->status }}">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                            {{ $workOrder->status_label }}
                                        </span>
                                </td>
                                <td class="text-end">{{ number_format($workOrder->estimated_time, 1) }} hrs</td>
                                <td class="text-end">{{ $workOrder->actual_time > 0 ? number_format($workOrder->actual_time, 1) . ' hrs' : '-' }}</td>
                                <td class="text-end">
                                    <div class="action-buttons justify-content-end">
                                        <a href="{{ route('work-orders.show', $workOrder) }}" class="action-btn" title="{{ __('ui.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('work-orders.edit', $workOrder) }}" class="action-btn" title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($workOrder->status === 'pending')
                                            <form action="{{ route('work-orders.start', $workOrder) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn text-success" title="{{ __('ui.start') }}" onclick="return confirm('Start this work order?')">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($workOrder->status === 'in_progress')
                                            <button type="button" class="action-btn text-primary" title="{{ __('ui.complete') }}" data-bs-toggle="modal" data-bs-target="#completeModal{{ $workOrder->id }}">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        @endif
                                        @if($workOrder->status === 'pending')
                                            <form action="{{ route('work-orders.destroy', $workOrder) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn text-danger" title="{{ __('ui.delete') }}" onclick="return confirm('Delete this work order?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state text-center py-4">
                                        <i class="bi bi-inboxes" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                        <p class="mt-2 text-muted">No work orders found</p>
                                        <a href="{{ route('work-orders.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-plus-circle me-1"></i> Create First Work Order
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination --}}
            @if($workOrders->hasPages())
                <div class="p-3 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted" style="font-size: 0.8rem;">
                            Showing {{ $workOrders->firstItem() ?? 0 }} to {{ $workOrders->lastItem() ?? 0 }} of {{ $workOrders->total() }} entries
                        </div>
                        {{ $workOrders->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Complete Work Order Modals --}}
    @foreach($workOrders as $workOrder)
        @if($workOrder->status === 'in_progress')
            <div class="modal fade" id="completeModal{{ $workOrder->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('work-orders.complete', $workOrder) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('ui.complete_work_order') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>Complete work order <strong>{{ $workOrder->work_order_number }}</strong>?</p>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('ui.actual_time_hours') }}</label>
                                    <input type="number" name="actual_time" class="form-control" step="0.1" min="0.1" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.cancel') }}</button>
                                <button type="submit" class="btn btn-success">{{ __('ui.complete') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
