@extends('layouts.admin.base')

@section('title', 'Stock Reconciliation')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-clipboard-check me-2"></i>Stock Reconciliation</h1>
            <p class="text-muted mb-0">Cycle counts compare ERP batch balances with physical warehouse stock.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.stock-reconciliations.planning') }}" class="btn btn-outline-dark">
                <i class="bi bi-calendar-check me-1"></i> ABC Planner
            </a>
            <a href="{{ route('admin.stock-reconciliations.trends') }}" class="btn btn-outline-info">
                <i class="bi bi-graph-up-arrow me-1"></i> Variance Trends
            </a>
            <a href="{{ route('admin.stock-reconciliations.control-analysis') }}" class="btn btn-outline-secondary">
                <i class="bi bi-diagram-3 me-1"></i> Production → Physical
            </a>
            <a href="{{ route('admin.stock-reconciliations.investigations.index') }}" class="btn btn-outline-warning">
                <i class="bi bi-search me-1"></i> Investigations
            </a>
            <a href="{{ route('admin.stock-reconciliations.report') }}" class="btn btn-outline-primary">
                <i class="bi bi-bar-chart me-1"></i> Variance Report
            </a>
            @can('create stock reconciliations')
                <a href="{{ route('admin.stock-reconciliations.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> New Cycle Count
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Counting In Progress</div>
            <div class="fs-3 fw-bold">{{ $stats['counting'] }}</div>
        </div></div></div>
        <div class="col-lg-3 col-md-6"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Awaiting Approval</div>
            <div class="fs-3 fw-bold text-warning">{{ $stats['awaiting_approval'] }}</div>
        </div></div></div>
        <div class="col-lg-3 col-md-6"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Approved / Awaiting Post</div>
            <div class="fs-3 fw-bold text-primary">{{ $stats['awaiting_post'] }}</div>
        </div></div></div>
        <div class="col-lg-3 col-md-6"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">30-Day Negative Variance</div>
            <div class="fs-3 fw-bold text-danger">${{ number_format($stats['negative_variance_30d_usd'], 2) }}</div>
            <div class="small text-muted">Last posted: {{ $stats['last_posted_at'] ? \Carbon\Carbon::parse($stats['last_posted_at'])->format('d M Y H:i') : 'Never' }}</div>
        </div></div></div>
        <div class="col-lg-3 col-md-6"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Unresolved Investigations</div>
            <div class="fs-3 fw-bold text-warning">{{ $stats['unresolved_count'] }}</div>
            <div class="small text-muted">${{ number_format($stats['unresolved_value_usd'], 2) }} absolute variance value</div>
        </div></div></div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        @foreach(['counting','submitted','approved','posted','rejected','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-primary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                    <a href="{{ route('admin.stock-reconciliations.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Count Date</th>
                        <th>Snapshot</th>
                        <th>Status</th>
                        <th class="text-end">Lines</th>
                        <th>Created By</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $reconciliation)
                        @php
                            $badge = match($reconciliation->status) {
                                'posted' => 'success',
                                'approved' => 'primary',
                                'submitted' => 'warning',
                                'rejected' => 'danger',
                                'cancelled' => 'secondary',
                                default => 'info',
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $reconciliation->reconciliation_no }}</td>
                            <td>{{ $reconciliation->count_date?->format('d M Y') }}</td>
                            <td>{{ $reconciliation->snapshot_at?->format('d M Y H:i') }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ ucfirst($reconciliation->status) }}</span></td>
                            <td class="text-end">{{ $reconciliation->items_count }}</td>
                            <td>{{ $reconciliation->creator?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.stock-reconciliations.show', $reconciliation) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No stock reconciliations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reconciliations->hasPages())
            <div class="card-footer bg-white">{{ $reconciliations->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
