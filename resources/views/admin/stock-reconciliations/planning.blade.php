@extends('layouts.admin.base')

@section('title', 'ABC Cycle Count Planner')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">ABC Cycle Count Planner</h1>
            <p class="text-muted mb-0">Higher-value materials are scheduled more frequently while all materials remain available for manual weekly counts.</p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.create') }}" class="btn btn-outline-primary">
            <i class="bi bi-clipboard-plus me-1"></i> Count All Inventory
        </a>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Plan As Of</label>
                    <input type="date" name="as_of" class="form-control" value="{{ request('as_of', $plan['as_of_date']) }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary"><i class="bi bi-arrow-repeat me-1"></i> Recalculate Plan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Materials</div><div class="fs-4 fw-bold">{{ $plan['summary']['materials'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Due Now</div><div class="fs-4 fw-bold text-warning">{{ $plan['summary']['due_materials'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Class A</div><div class="fs-4 fw-bold text-danger">{{ $plan['summary']['class_a'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Class B</div><div class="fs-4 fw-bold text-primary">{{ $plan['summary']['class_b'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Class C</div><div class="fs-4 fw-bold text-secondary">{{ $plan['summary']['class_c'] }}</div>
        </div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Inventory Value</div><div class="fs-5 fw-bold">&#36;{{ number_format($plan['summary']['inventory_value_usd'], 2) }}</div>
        </div></div></div>
    </div>

    <div class="alert alert-info small">
        <strong>Planning rule:</strong> ABC class is recalculated from current landed inventory value.
        Class A covers the highest-value ~80% and defaults to a 7-day frequency; B to 14 days; C to 30 days.
        These frequencies are configurable and do not replace management's ability to run a full weekly count.
    </div>

    @can('create stock reconciliations')
    <form method="POST" action="{{ route('admin.stock-reconciliations.planning.start') }}">
        @csrf
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Count Date</label>
                    <input type="date" name="count_date" class="form-control" value="{{ old('count_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" maxlength="2000"
                           value="{{ old('notes', 'ABC / targeted cycle count') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="selectDue">Due</button>
                    <button type="button" class="btn btn-outline-secondary" id="selectAll">All</button>
                    <button class="btn btn-success flex-fill" @disabled($plan['rows']->isEmpty())>
                        <i class="bi bi-play-circle me-1"></i> Start Selected Count
                    </button>
                </div>
            </div>
        </div>
    @endcan

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @can('create stock reconciliations')<th style="width:48px;"></th>@endcan
                        <th>Material</th>
                        <th class="text-center">ABC</th>
                        <th class="text-end">Available</th>
                        <th class="text-end">Inventory Value</th>
                        <th class="text-end">Value Share</th>
                        <th class="text-center">Frequency</th>
                        <th>Last Count</th>
                        <th>Next Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plan['rows'] as $row)
                        @php
                            $classBadge = match($row['abc_class']) {
                                'A' => 'danger',
                                'B' => 'primary',
                                default => 'secondary',
                            };
                        @endphp
                        <tr>
                            @can('create stock reconciliations')
                            <td>
                                <input class="form-check-input planner-check"
                                       type="checkbox"
                                       name="product_ids[]"
                                       value="{{ $row['product_id'] }}"
                                       data-due="{{ $row['is_due'] ? '1' : '0' }}"
                                       @checked($row['is_due'])>
                            </td>
                            @endcan
                            <td>
                                <div class="fw-semibold">{{ $row['material_name'] }}</div>
                                <small class="text-muted">{{ $row['batch_count'] }} active batch(es)</small>
                            </td>
                            <td class="text-center"><span class="badge bg-{{ $classBadge }}">{{ $row['abc_class'] }}</span></td>
                            <td class="text-end">{{ number_format($row['inventory_quantity'], 4) }} {{ $row['inventory_unit'] }}</td>
                            <td class="text-end">&#36;{{ number_format($row['inventory_value_usd'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['inventory_value_share_percentage'], 2) }}%</td>
                            <td class="text-center">Every {{ $row['frequency_days'] }} days</td>
                            <td>{{ $row['last_count_date'] ? \Carbon\Carbon::parse($row['last_count_date'])->format('d M Y') : 'Never' }}</td>
                            <td>{{ \Carbon\Carbon::parse($row['next_due_date'])->format('d M Y') }}</td>
                            <td>
                                @if($row['is_due'])
                                    <span class="badge bg-warning text-dark">Due{{ $row['overdue_days'] > 0 ? ' · '.$row['overdue_days'].'d overdue' : '' }}</span>
                                @else
                                    <span class="badge bg-success">On schedule</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-5 text-muted">No active raw-material inventory is available for cycle-count planning.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('create stock reconciliations')</form>@endcan
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checks = Array.from(document.querySelectorAll('.planner-check'));
    document.getElementById('selectDue')?.addEventListener('click', () => {
        checks.forEach(cb => cb.checked = cb.dataset.due === '1');
    });
    document.getElementById('selectAll')?.addEventListener('click', () => {
        const allSelected = checks.length > 0 && checks.every(cb => cb.checked);
        checks.forEach(cb => cb.checked = !allSelected);
    });
});
</script>
@endsection
