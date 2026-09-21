@extends('layouts.admin.base')

@section('title', 'Production to Physical Inventory Analysis')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">Production → Physical Inventory Analysis</h1>
            <p class="text-muted mb-0">Connect frozen production plans, actual FIFO consumption, and warehouse physical counts.</p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.trends') }}" class="btn btn-outline-primary">
            <i class="bi bi-graph-up me-1"></i> Variance Trends
        </a>
    </div>

    <div class="alert alert-info small">
        <strong>How to read this:</strong>
        Planned = frozen production-order material requirement for orders that had real consumption in the selected period.
        Actual = real FIFO material consumption.
        Physical = the latest posted cycle count for that material in the period.
        Reconciliation Difference = Physical Qty − ERP System Qty at that count.
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3"><label class="form-label">From</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date', $control['from_date']) }}"></div>
            <div class="col-lg-3"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date', $control['to_date']) }}"></div>
            <div class="col-lg-4"><label class="form-label">Material</label>
                <select name="product_id" class="form-select">
                    <option value="">All raw materials</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Analyze</button></div>
        </form>
    </div></div>

    <div class="row g-3 mb-4">
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Materials</div><div class="fs-4 fw-bold">{{ $control['summary']['materials'] }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Production Variance</div><div class="fs-4 fw-bold text-warning">{{ $control['summary']['materials_with_production_variance'] }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Inventory Variance</div><div class="fs-4 fw-bold text-danger">{{ $control['summary']['materials_with_inventory_variance'] }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Planned Material Cost</div><div class="fs-5 fw-bold">&#36;{{ number_format($control['summary']['planned_material_cost_usd'], 2) }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Actual Material Cost</div><div class="fs-5 fw-bold">&#36;{{ number_format($control['summary']['actual_material_cost_usd'], 2) }}</div></div></div></div>
        <div class="col-lg-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Physical Adjustments</div><div class="fs-5 fw-bold">&#36;{{ number_format($control['summary']['reconciliation_absolute_value_usd'], 2) }}</div></div></div></div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th class="text-end">Planned</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Prod. Variance</th>
                        <th class="text-end">Declared Waste</th>
                        <th>Latest Physical Count</th>
                        <th class="text-end">System @ Count</th>
                        <th class="text-end">Physical Qty</th>
                        <th class="text-end">Recon. Difference</th>
                        <th class="text-end">Posted Adj. Value</th>
                        <th>Signal</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($control['rows'] as $row)
                    <tr>
                        <td><div class="fw-semibold">{{ $row['material_name'] }}</div><small class="text-muted">{{ $row['unit'] }}</small></td>
                        <td class="text-end">{{ number_format($row['planned_quantity'], 4) }}</td>
                        <td class="text-end">{{ number_format($row['actual_quantity'], 4) }}</td>
                        <td class="text-end {{ $row['production_variance_quantity'] > 0 ? 'text-danger' : ($row['production_variance_quantity'] < 0 ? 'text-success' : '') }}">{{ $row['production_variance_quantity'] > 0 ? '+' : '' }}{{ number_format($row['production_variance_quantity'], 4) }}</td>
                        <td class="text-end">{{ number_format($row['production_wastage_quantity'], 4) }}</td>
                        <td>{{ $row['latest_count_date'] ? \Carbon\Carbon::parse($row['latest_count_date'])->format('d M Y') : 'No posted count' }}</td>
                        <td class="text-end">{{ $row['system_quantity_at_count'] !== null ? number_format($row['system_quantity_at_count'], 4) : '—' }}</td>
                        <td class="text-end">{{ $row['physical_quantity'] !== null ? number_format($row['physical_quantity'], 4) : '—' }}</td>
                        <td class="text-end {{ $row['physical_variance_quantity'] < 0 ? 'text-danger' : ($row['physical_variance_quantity'] > 0 ? 'text-success' : '') }}">{{ $row['physical_variance_quantity'] > 0 ? '+' : '' }}{{ number_format($row['physical_variance_quantity'], 4) }}</td>
                        <td class="text-end {{ $row['posted_adjustment_value_usd'] < 0 ? 'text-danger' : ($row['posted_adjustment_value_usd'] > 0 ? 'text-success' : '') }}">{{ $row['posted_adjustment_value_usd'] >= 0 ? '+' : '-' }}&#36;{{ number_format(abs($row['posted_adjustment_value_usd']), 2) }}</td>
                        <td>
                            @if($row['control_signal'] === 'inventory_variance')
                                <span class="badge bg-danger">Inventory variance</span>
                            @elseif($row['control_signal'] === 'production_variance')
                                <span class="badge bg-warning text-dark">Production variance</span>
                            @else
                                <span class="badge bg-success">Aligned</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center py-5 text-muted">No production consumption or posted physical counts exist in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
