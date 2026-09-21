@extends('layouts.admin.base')

@section('title', 'Stock Variance Trends')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">Variance Trends</h1>
            <p class="text-muted mb-0">Posted reconciliation differences over time, valued at the affected batch's landed cost.</p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.control-analysis') }}" class="btn btn-outline-primary">
            <i class="bi bi-diagram-3 me-1"></i> Production → Physical Analysis
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date', $trend['from_date']) }}"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date', $trend['to_date']) }}"></div>
            <div class="col-md-3"><label class="form-label">Grouping</label>
                <select name="interval" class="form-select">
                    <option value="week" @selected($trend['interval'] === 'week')>Weekly</option>
                    <option value="month" @selected($trend['interval'] === 'month')>Monthly</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Refresh Trend</button></div>
        </form>
    </div></div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Reconciliations</div><div class="fs-4 fw-bold">{{ $trend['summary']['reconciliations'] }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Shortage Value</div><div class="fs-4 fw-bold text-danger">-&#36;{{ number_format($trend['summary']['shortage_value_usd'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Surplus Value</div><div class="fs-4 fw-bold text-success">+&#36;{{ number_format($trend['summary']['surplus_value_usd'], 2) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Absolute Variance</div><div class="fs-4 fw-bold">&#36;{{ number_format($trend['summary']['absolute_value_usd'], 2) }}</div><small class="text-muted">Net {{ $trend['summary']['net_value_usd'] >= 0 ? '+' : '-' }}&#36;{{ number_format(abs($trend['summary']['net_value_usd']), 2) }}</small></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>{{ ucfirst($trend['interval']) }} Variance Trend</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Period</th><th class="text-end">Counts</th><th class="text-end">Shortage</th><th class="text-end">Surplus</th><th class="text-end">Net</th><th class="text-end">Absolute</th></tr></thead>
                        <tbody>
                        @forelse($trend['periods'] as $period)
                            <tr>
                                <td class="fw-semibold">{{ $period['label'] }}</td>
                                <td class="text-end">{{ $period['reconciliations'] }}</td>
                                <td class="text-end text-danger">-&#36;{{ number_format($period['shortage_value_usd'], 2) }}</td>
                                <td class="text-end text-success">+&#36;{{ number_format($period['surplus_value_usd'], 2) }}</td>
                                <td class="text-end {{ $period['net_value_usd'] < 0 ? 'text-danger' : 'text-success' }}">{{ $period['net_value_usd'] >= 0 ? '+' : '-' }}&#36;{{ number_format(abs($period['net_value_usd']), 2) }}</td>
                                <td class="text-end">&#36;{{ number_format($period['absolute_value_usd'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">No posted reconciliation variances exist in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Top Variance Reasons</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Reason</th><th class="text-end">Lines</th><th class="text-end">Abs. Value</th></tr></thead>
                        <tbody>
                        @forelse($trend['top_reasons'] as $reason)
                            <tr><td>{{ $reason['label'] }}</td><td class="text-end">{{ $reason['lines'] }}</td><td class="text-end">&#36;{{ number_format($reason['absolute_value_usd'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No variance reasons yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
