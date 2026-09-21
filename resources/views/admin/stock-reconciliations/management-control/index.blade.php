@extends('layouts.admin.base')

@section('title', 'Stock Management Control')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">Stock Management Control</h1>
            <p class="text-muted mb-0">Escalate recurring inventory-control signals and complete a documented weekly management review.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.stock-reconciliations.investigations.intelligence') }}" class="btn btn-outline-success">
                <i class="bi bi-shield-check me-1"></i> Prevention Intelligence
            </a>
            @can('escalate stock reconciliations')
            <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.sync') }}">
                @csrf
                <button class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync Signals
                </button>
            </form>
            @endcan
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card border-primary shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
            <div>
                <strong>Current Weekly Control Review</strong>
                <div class="small text-muted">
                    {{ $review->week_start->format('d M Y') }} – {{ $review->week_end->format('d M Y') }}
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($review->is_overdue)
                    <span class="badge bg-danger">Overdue</span>
                @endif
                <span class="badge bg-{{ $review->status === 'completed' ? 'success' : 'warning' }}">
                    {{ ucfirst($review->status) }}
                </span>
                <a href="{{ route('admin.stock-reconciliations.management-control.reviews.show', $review) }}"
                   class="btn btn-sm btn-outline-primary">
                    Open Review
                </a>
            </div>
        </div>
        <div class="card-body">
            @php $snapshot = $review->summary_snapshot ?? []; @endphp
            <div class="row g-3">
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Active Escalations</div>
                    <div class="fs-4 fw-bold">{{ $snapshot['active_escalations'] ?? 0 }}</div>
                </div></div>
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Level 3</div>
                    <div class="fs-4 fw-bold text-danger">{{ $snapshot['level_3'] ?? 0 }}</div>
                </div></div>
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Level 2</div>
                    <div class="fs-4 fw-bold text-warning">{{ $snapshot['level_2'] ?? 0 }}</div>
                </div></div>
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Overdue</div>
                    <div class="fs-4 fw-bold">{{ $snapshot['overdue'] ?? 0 }}</div>
                </div></div>
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Unassigned</div>
                    <div class="fs-4 fw-bold">{{ $snapshot['unassigned'] ?? 0 }}</div>
                </div></div>
                <div class="col-lg-2 col-md-4"><div class="border rounded p-3 h-100">
                    <div class="text-muted small">Variance Value</div>
                    <div class="fs-5 fw-bold">&#36;{{ number_format($snapshot['absolute_value_usd'] ?? 0, 2) }}</div>
                </div></div>
            </div>
            <div class="mt-3 small text-muted">
                Owner: {{ $review->owner?->name ?? 'Unassigned' }} · Due {{ $review->due_date->format('d M Y') }}
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Active</div><div class="fs-3 fw-bold">{{ $stats['active'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-danger shadow-sm"><div class="card-body">
            <div class="text-muted small">Level 3</div><div class="fs-3 fw-bold text-danger">{{ $stats['level_3'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Level 2</div><div class="fs-3 fw-bold text-warning">{{ $stats['level_2'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Overdue</div><div class="fs-3 fw-bold">{{ $stats['overdue'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Unassigned</div><div class="fs-3 fw-bold">{{ $stats['unassigned'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Active Value</div><div class="fs-5 fw-bold">&#36;{{ number_format($stats['active_value_usd'], 2) }}</div>
        </div></div></div>
    </div>

    <div class="alert alert-secondary small">
        <strong>Escalation levels:</strong>
        Level 3 = critical management attention (default due in 1 day),
        Level 2 = high-priority review (3 days),
        Level 1 = routine management follow-up (7 days).
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['open' => 'Open', 'acknowledged' => 'Acknowledged', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Level</label>
                <select name="level" class="form-select">
                    <option value="">All</option>
                    @foreach([3,2,1] as $level)
                        <option value="{{ $level }}" @selected((string) request('level') === (string) $level)>Level {{ $level }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-check mt-4 pt-2">
                    <input class="form-check-input" type="checkbox" name="overdue" value="1" id="mc-overdue" @checked(request()->boolean('overdue'))>
                    <label class="form-check-label" for="mc-overdue">Overdue only</label>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-check mt-4 pt-2">
                    <input class="form-check-input" type="checkbox" name="unassigned" value="1" id="mc-unassigned" @checked(request()->boolean('unassigned'))>
                    <label class="form-check-label" for="mc-unassigned">Unassigned only</label>
                </div>
            </div>
            <div class="col-lg-4 col-md-8 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('admin.stock-reconciliations.management-control.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Management Escalations</strong></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Level</th><th>Signal</th><th>Material</th><th>Owner</th><th>Review Due</th><th>Status</th><th class="text-end">Value</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($escalations as $row)
                    @php
                        $levelBadge = match((int) $row->level) { 3 => 'danger', 2 => 'warning', default => 'secondary' };
                        $statusBadge = match($row->status) { 'closed' => 'success', 'acknowledged' => 'primary', default => 'warning' };
                    @endphp
                    <tr class="{{ $row->is_overdue ? 'table-danger' : '' }}">
                        <td><span class="badge bg-{{ $levelBadge }}">L{{ $row->level }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $row->title }}</div>
                            <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $row->source_type)) }} · {{ $row->occurrences }} occurrence(s)</small>
                        </td>
                        <td>{{ $row->product?->name ?? '—' }}</td>
                        <td>{{ $row->manager?->name ?? 'Unassigned' }}</td>
                        <td>
                            {{ $row->review_due_date?->format('d M Y') ?? '—' }}
                            @if($row->is_overdue)<br><span class="badge bg-danger">Overdue</span>@endif
                        </td>
                        <td><span class="badge bg-{{ $statusBadge }}">{{ ucfirst($row->status) }}</span></td>
                        <td class="text-end">&#36;{{ number_format((float) $row->absolute_value_usd, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.stock-reconciliations.management-control.escalations.show', $row) }}"
                               class="btn btn-sm btn-outline-primary">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No management escalations match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($escalations->hasPages())
            <div class="card-footer bg-white">{{ $escalations->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Recent Weekly Reviews</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Week</th><th>Status</th><th>Owner</th><th>Due</th><th>Completed</th><th></th></tr></thead>
                <tbody>
                @foreach($recentReviews as $past)
                    <tr>
                        <td>{{ $past->week_start->format('d M') }} – {{ $past->week_end->format('d M Y') }}</td>
                        <td><span class="badge bg-{{ $past->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($past->status) }}</span></td>
                        <td>{{ $past->owner?->name ?? 'Unassigned' }}</td>
                        <td>{{ $past->due_date->format('d M Y') }}</td>
                        <td>{{ $past->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-end"><a href="{{ route('admin.stock-reconciliations.management-control.reviews.show', $past) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
