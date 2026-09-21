@extends('layouts.admin.base')

@section('title', 'Variance Investigations')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">Variance Investigations</h1>
            <p class="text-muted mb-0">Assign, investigate, correct and close posted inventory discrepancies without changing their original audit record.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.stock-reconciliations.investigations.intelligence') }}" class="btn btn-primary">
                <i class="bi bi-shield-check me-1"></i> Prevention Intelligence
            </a>
            <a href="{{ route('admin.stock-reconciliations.report', ['unresolved' => 1]) }}" class="btn btn-outline-warning">
                <i class="bi bi-exclamation-diamond me-1"></i> Variance Report
            </a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Open</div><div class="fs-3 fw-bold text-warning">{{ $stats['open'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Investigating</div><div class="fs-3 fw-bold text-primary">{{ $stats['investigating'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-danger shadow-sm"><div class="card-body">
            <div class="text-muted small">Overdue</div><div class="fs-3 fw-bold text-danger">{{ $stats['overdue'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Unassigned</div><div class="fs-3 fw-bold">{{ $stats['unassigned'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Resolved 30d</div><div class="fs-3 fw-bold text-success">{{ $stats['resolved_30d'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Active Variance Value</div><div class="fs-5 fw-bold">&#36;{{ number_format($stats['active_value_usd'], 2) }}</div>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['open' => 'Open', 'investigating' => 'Investigating', 'resolved' => 'Resolved'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-4">
                <label class="form-label">Assigned To</label>
                <select name="assigned_to" class="form-select">
                    <option value="">All</option>
                    <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label">Age</label>
                <select name="age_bucket" class="form-select">
                    <option value="">All</option>
                    @foreach(['0-7' => '0–7 days', '8-14' => '8–14 days', '15-30' => '15–30 days', '31+' => '31+ days'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('age_bucket') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <div class="form-check mt-4 pt-2">
                    <input class="form-check-input" type="checkbox" name="overdue" value="1" id="overdue" @checked(request()->boolean('overdue'))>
                    <label class="form-check-label" for="overdue">Overdue only</label>
                </div>
            </div>
            <div class="col-lg-3 col-md-8 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('admin.stock-reconciliations.investigations.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Case</th>
                        <th>Material / Count</th>
                        <th class="text-end">Variance</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Age</th>
                        <th>Due</th>
                        <th>Root Cause</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($investigations as $case)
                    @php
                        $line = $case->adjustmentItem;
                        $statusBadge = match($case->status) {
                            'resolved' => 'success',
                            'investigating' => 'primary',
                            default => 'warning',
                        };
                    @endphp
                    <tr class="{{ $case->is_overdue ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">INV-{{ str_pad($case->id, 6, '0', STR_PAD_LEFT) }}</div>
                            <small class="text-muted">{{ $case->opened_at?->format('d M Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $line?->product?->name ?? 'Unknown material' }}</div>
                            <small>
                                @if($line?->adjustment?->reconciliation)
                                    <a href="{{ route('admin.stock-reconciliations.show', $line->adjustment->reconciliation) }}">
                                        {{ $line->adjustment->reconciliation->reconciliation_no }}
                                    </a>
                                @else
                                    Adjustment #{{ $line?->stock_adjustment_id }}
                                @endif
                            </small>
                        </td>
                        <td class="text-end {{ (float) ($line?->adjustment_quantity ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                            <div>{{ (float) ($line?->adjustment_quantity ?? 0) > 0 ? '+' : '' }}{{ number_format((float) ($line?->adjustment_quantity ?? 0), 4) }} {{ $line?->inventory_unit }}</div>
                            <small>{{ (float) ($line?->adjustment_value_usd ?? 0) >= 0 ? '+' : '-' }}&#36;{{ number_format(abs((float) ($line?->adjustment_value_usd ?? 0)), 2) }}</small>
                        </td>
                        <td><span class="badge bg-{{ $statusBadge }}">{{ ucfirst($case->status) }}</span></td>
                        <td>{{ $case->assignee?->name ?? 'Unassigned' }}</td>
                        <td>{{ $case->age_days }} days<br><small class="text-muted">{{ $case->aging_bucket }}</small></td>
                        <td>
                            {{ $case->due_date?->format('d M Y') ?? '—' }}
                            @if($case->is_overdue)<br><span class="badge bg-danger">Overdue</span>@endif
                        </td>
                        <td>{{ $case->root_cause_code ? str_replace('_', ' ', ucfirst($case->root_cause_code)) : 'Pending' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.stock-reconciliations.investigations.show', $case) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-search me-1"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-5 text-muted">No variance investigations match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($investigations->hasPages())
            <div class="card-footer bg-white">{{ $investigations->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
