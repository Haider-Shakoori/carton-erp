@extends('layouts.admin.base')

@section('title', 'Stock Reconciliation Variance Report')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">Variance Report</h1>
            <p class="text-muted mb-0">Posted, auditable inventory differences only.</p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.report.export-csv', request()->query()) }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label">Material</label>
                    <select name="product_id" class="form-select">
                        <option value="">All materials</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label class="form-label">Direction</label>
                    <select name="direction" class="form-select">
                        <option value="">All</option>
                        <option value="shortage" @selected(request('direction') === 'shortage')>Shortage</option>
                        <option value="surplus" @selected(request('direction') === 'surplus')>Surplus</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-8">
                    <label class="form-label">Reason</label>
                    <select name="reason_code" class="form-select">
                        <option value="">All reasons</option>
                        @foreach($reasonCodes as $code => $label)
                            <option value="{{ $code }}" @selected(request('reason_code') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" name="unresolved" value="1" id="unresolvedOnly" @checked(request()->boolean('unresolved'))>
                        <label class="form-check-label" for="unresolvedOnly">
                            Unresolved investigations only
                        </label>
                    </div>
                </div>
                <div class="col-lg-9 col-md-6 d-flex gap-2 justify-content-end mt-3">
                    <a href="{{ route('admin.stock-reconciliations.report') }}" class="btn btn-light">Reset</a>
                    <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    @if(request()->boolean('unresolved'))
        <div class="alert alert-warning d-flex align-items-center justify-content-between gap-3">
            <div>
                <strong>Unresolved variance queue:</strong>
                showing posted adjustments whose reason still requires investigation.
                Stock has already been corrected operationally; these lines remain visible until management resolves the root cause.
            </div>
            <a href="{{ route('admin.stock-reconciliations.report') }}" class="btn btn-sm btn-outline-dark">Show all</a>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Variance Lines</div><div class="fs-4 fw-bold">{{ $summary['lines'] }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Surplus Value</div><div class="fs-4 fw-bold text-success">+${{ number_format($summary['positive_value_usd'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Shortage Value</div><div class="fs-4 fw-bold text-danger">-${{ number_format(abs($summary['negative_value_usd']), 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Net Variance Value</div>
            <div class="fs-4 fw-bold {{ $summary['net_value_usd'] < 0 ? 'text-danger' : 'text-success' }}">
                {{ $summary['net_value_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($summary['net_value_usd']), 2) }}
            </div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Unresolved Investigation</div>
            <div class="fs-4 fw-bold text-warning">{{ $summary['unresolved_lines'] }}</div>
            <div class="small text-muted">${{ number_format($summary['unresolved_value_usd'], 2) }} absolute value</div>
        </div></div></div>
    </div>

    @if($topMaterials->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Most Repeated / Highest-Value Variances</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Material</th><th class="text-end">Occurrences</th><th class="text-end">Absolute Value</th><th class="text-end">Net Value</th></tr></thead>
                    <tbody>
                        @foreach($topMaterials as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="text-end">{{ $row['occurrences'] }}</td>
                                <td class="text-end">${{ number_format($row['absolute_value_usd'], 2) }}</td>
                                <td class="text-end {{ $row['net_value_usd'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $row['net_value_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($row['net_value_usd']), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date / Reference</th>
                        <th>Material</th>
                        <th class="text-end">Before</th>
                        <th class="text-end">Adjustment</th>
                        <th class="text-end">After</th>
                        <th class="text-end">Value USD</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <div>{{ $row->adjustment?->adjustment_date?->format('d M Y') }}</div>
                                <small>
                                    <a href="{{ route('admin.stock-reconciliations.show', $row->adjustment->reconciliation) }}">
                                        {{ $row->adjustment?->reconciliation?->reconciliation_no }}
                                    </a>
                                    · {{ $row->adjustment?->adjustment_no }}
                                </small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $row->product?->name }}</div>
                                <small class="text-muted">Batch #{{ $row->purchase_item_id }} · {{ $row->inventory_unit }}</small>
                            </td>
                            <td class="text-end">{{ number_format((float) $row->before_quantity, 4) }}</td>
                            <td class="text-end {{ (float) $row->adjustment_quantity < 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float) $row->adjustment_quantity > 0 ? '+' : '' }}{{ number_format((float) $row->adjustment_quantity, 4) }}
                            </td>
                            <td class="text-end">{{ number_format((float) $row->after_quantity, 4) }}</td>
                            <td class="text-end {{ (float) $row->adjustment_value_usd < 0 ? 'text-danger' : 'text-success' }}">
                                {{ (float) $row->adjustment_value_usd >= 0 ? '+' : '-' }}${{ number_format(abs((float) $row->adjustment_value_usd), 2) }}
                            </td>
                            <td>
                                {{ $reasonCodes[$row->reason_code] ?? ($row->reason_code ?: '—') }}
                                @if(in_array($row->reason_code, $unresolvedReasonCodes, true))
                                    <span class="badge bg-warning text-dark ms-1">Unresolved</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No posted reconciliation variances match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="card-footer bg-white">{{ $rows->links('pagination::bootstrap-5') }}</div>@endif
    </div>
</div>
@endsection
