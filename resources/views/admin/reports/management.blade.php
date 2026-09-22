@extends('layouts.admin.base')

@section('title', 'Management Reporting')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h3 class="mb-1"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Management Reporting</h3>
            <div class="text-muted small">
                {{ $consolidated ? 'Consolidated 3D Carton + Syrup Pack' : 'Active business workspace' }}
                · {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}
            </div>
        </div>

        <form class="d-flex flex-wrap gap-2 align-items-end" method="GET">
            <div>
                <label class="form-label small mb-1">From</label>
                <input type="date" class="form-control" name="from" value="{{ $from->toDateString() }}">
            </div>
            <div>
                <label class="form-label small mb-1">To</label>
                <input type="date" class="form-control" name="to" value="{{ $to->toDateString() }}">
            </div>
            <div>
                <label class="form-label small mb-1">Scope</label>
                <select class="form-select" name="scope">
                    <option value="active" @selected(!$consolidated)>Active Business</option>
                    @can('consolidated management reporting')
                        <option value="consolidated" @selected($consolidated)>Consolidated</option>
                    @endcan
                </select>
            </div>
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
        </form>
    </div>

    @php
        $financial = $report['financial'];
        $production = $report['production'];
        $inventory = $report['inventory'];
    @endphp

    <div class="row g-3 mb-4">
        @foreach([
            ['Revenue', 'USD '.number_format($financial['revenue_usd'], 2), 'bi-cash-stack'],
            ['Gross Profit', 'USD '.number_format($financial['gross_profit_usd'], 2), 'bi-graph-up'],
            ['Gross Margin', number_format($financial['gross_margin_percentage'], 2).'%', 'bi-percent'],
            ['Inventory Value', 'USD '.number_format($inventory['value_usd'], 2), 'bi-box-seam'],
            ['Production Yield', number_format($production['yield_percentage'], 2).'%', 'bi-speedometer2'],
            ['Slow-moving Stock', 'USD '.number_format($inventory['slow_moving_value_usd'], 2), 'bi-hourglass-split'],
        ] as [$label, $value, $icon])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi {{ $icon }} text-primary fs-5"></i>
                        <div class="text-muted small mt-2">{{ $label }}</div>
                        <div class="fw-bold fs-5">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-building-gear me-2"></i>Production & Cost Variance</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        @foreach([
                            ['Completed Orders', $production['completed_orders']],
                            ['Planned Output', number_format($production['planned_output'], 2)],
                            ['Manufactured', number_format($production['manufactured_output'], 2)],
                            ['Good Output', number_format($production['good_output'], 2)],
                            ['Rejected', number_format($production['rejected_output'], 2)],
                        ] as [$label, $value])
                            <div class="col-6 col-md">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">{{ $label }}</div>
                                    <div class="fw-bold">{{ $value }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Variance</th><th class="text-end">USD</th><th>Meaning</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>Material Usage</td>
                                    <td class="text-end fw-semibold">{{ number_format($production['material_usage_variance_usd'], 2) }}</td>
                                    <td class="small text-muted">Actual quantity vs BOM/planned quantity at planned rate</td>
                                </tr>
                                <tr>
                                    <td>Material Rate</td>
                                    <td class="text-end fw-semibold">{{ number_format($production['material_rate_variance_usd'], 2) }}</td>
                                    <td class="small text-muted">Actual FIFO landed rate vs planned material rate</td>
                                </tr>
                                <tr>
                                    <td>Total Material Cost</td>
                                    <td class="text-end fw-semibold">{{ number_format($production['material_cost_variance_usd'], 2) }}</td>
                                    <td class="small text-muted">Usage + rate effect</td>
                                </tr>
                                <tr>
                                    <td>Total Production Cost</td>
                                    <td class="text-end fw-bold">{{ number_format($production['total_production_cost_variance_usd'], 2) }}</td>
                                    <td class="small text-muted">Material plus conversion absorption variance</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-text mt-2">Positive variance = higher actual cost than plan; negative = favorable.</div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-boxes me-2"></i>Inventory Health</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Available inventory quantity</span><strong>{{ number_format($inventory['available_quantity'], 4) }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Inventory value</span><strong>USD {{ number_format($inventory['value_usd'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between py-2 border-bottom"><span>Slow-moving batches (90+ days)</span><strong>{{ $inventory['slow_moving_batches'] }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span>Slow-moving stock value</span><strong>USD {{ number_format($inventory['slow_moving_value_usd'], 2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    @if($consolidated && count($report['business_breakdown']))
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom"><h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Business Unit Breakdown</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Business</th><th class="text-end">Revenue USD</th><th class="text-end">Sales Orders</th><th class="text-end">Completed Production</th><th class="text-end">Good Output</th></tr></thead>
                    <tbody>
                        @foreach($report['business_breakdown'] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['revenue_usd'], 2) }}</td>
                                <td class="text-end">{{ $row['sales_orders'] }}</td>
                                <td class="text-end">{{ $row['completed_production_orders'] }}</td>
                                <td class="text-end">{{ number_format($row['good_output'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h6 class="mb-0"><i class="bi bi-people me-2"></i>Customer Profitability</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Orders</th><th class="text-end">Revenue</th><th class="text-end">Cost</th><th class="text-end">Profit</th><th class="text-end">Margin</th></tr></thead>
                        <tbody>
                            @forelse($report['customer_profitability'] as $row)
                                <tr>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td class="text-end">{{ $row['orders'] }}</td>
                                    <td class="text-end">{{ number_format($row['revenue_usd'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['cost_usd'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['profit_usd'], 2) }}</td>
                                    <td class="text-end">{{ number_format($row['margin_percentage'], 2) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h6 class="mb-0"><i class="bi bi-truck me-2"></i>Supplier Performance</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Supplier</th><th class="text-end">POs</th><th class="text-end">Spend</th><th class="text-end">Avg. Lead</th></tr></thead>
                        <tbody>
                            @forelse($report['supplier_performance'] as $row)
                                <tr>
                                    <td>{{ $row['supplier_name'] }}</td>
                                    <td class="text-end">{{ $row['purchase_orders'] }}</td>
                                    <td class="text-end">{{ number_format($row['spend_usd'], 2) }}</td>
                                    <td class="text-end">{{ $row['average_receipt_lead_days'] === null ? '—' : number_format($row['average_receipt_lead_days'], 1).' d' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No purchases in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
