@extends('layouts.admin.base')

@section('title', 'Financial Accounting')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h3 class="mb-1"><i class="bi bi-journal-check me-2 text-primary"></i>Financial Accounting</h3>
            <div class="text-muted small">
                Double-entry statements · {{ $consolidated ? 'Consolidated businesses' : 'Active business workspace' }}
            </div>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <div><label class="form-label small mb-1">From</label><input class="form-control" type="date" name="from" value="{{ $from->toDateString() }}"></div>
            <div><label class="form-label small mb-1">To</label><input class="form-control" type="date" name="to" value="{{ $to->toDateString() }}"></div>
            <div><label class="form-label small mb-1">As of</label><input class="form-control" type="date" name="as_of" value="{{ $asOf->toDateString() }}"></div>
            <div>
                <label class="form-label small mb-1">Scope</label>
                <select class="form-select" name="scope">
                    <option value="active" @selected(!$consolidated)>Active Business</option>
                    @can('consolidated management reporting')
                        <option value="consolidated" @selected($consolidated)>Consolidated</option>
                    @endcan
                </select>
            </div>
            <button class="btn btn-primary">Apply</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Revenue', 'USD '.number_format($profitAndLoss['revenue_usd'], 2), 'bi-graph-up-arrow'],
            ['Expenses', 'USD '.number_format($profitAndLoss['expenses_usd'], 2), 'bi-receipt'],
            ['Net Profit', 'USD '.number_format($profitAndLoss['net_profit_usd'], 2), 'bi-cash-coin'],
            ['Assets', 'USD '.number_format($balanceSheet['assets_usd'], 2), 'bi-bank'],
            ['Liabilities', 'USD '.number_format($balanceSheet['liabilities_usd'], 2), 'bi-credit-card'],
            ['Net Cash Flow', 'USD '.number_format($cashFlow['net_cash_flow_usd'], 2), 'bi-arrow-left-right'],
        ] as [$label,$value,$icon])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <i class="bi {{ $icon }} text-primary fs-5"></i>
                        <div class="small text-muted mt-2">{{ $label }}</div>
                        <div class="fw-bold">{{ $value }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#trial">Trial Balance</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pnl">Profit & Loss</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#balance">Balance Sheet</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cashflow">Cash Flow</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#periods">Fiscal Periods</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="trial">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Trial Balance as of {{ $trialBalance['as_of'] }}</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                        <tbody>
                            @foreach($trialBalance['rows'] as $row)
                                <tr>
                                    <td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ ucfirst($row['type']) }}</td>
                                    <td class="text-end">{{ number_format($row['debit_usd'],2) }}</td>
                                    <td class="text-end">{{ number_format($row['credit_usd'],2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['balance_usd'],2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr class="fw-bold"><td colspan="3">Totals</td><td class="text-end">{{ number_format($trialBalance['debit_usd'],2) }}</td><td class="text-end">{{ number_format($trialBalance['credit_usd'],2) }}</td><td></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="pnl">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Profit & Loss · {{ $profitAndLoss['from'] }} — {{ $profitAndLoss['to'] }}</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Amount USD</th></tr></thead>
                        <tbody>@foreach($profitAndLoss['rows'] as $row)<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ ucfirst($row['type']) }}</td><td class="text-end">{{ number_format($row['amount_usd'],2) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="balance">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Balance Sheet as of {{ $balanceSheet['as_of'] }}</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Amount USD</th></tr></thead>
                        <tbody>@foreach($balanceSheet['rows'] as $row)<tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ ucfirst($row['type']) }}</td><td class="text-end">{{ number_format($row['amount_usd'],2) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="cashflow">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Cash Flow · {{ $cashFlow['from'] }} — {{ $cashFlow['to'] }}</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Source</th><th class="text-end">Cash In</th><th class="text-end">Cash Out</th><th class="text-end">Net</th></tr></thead>
                        <tbody>@foreach($cashFlow['rows'] as $row)<tr><td>{{ ucwords(str_replace('_',' ',$row['source_type'])) }}</td><td class="text-end">{{ number_format($row['cash_in_usd'],2) }}</td><td class="text-end">{{ number_format($row['cash_out_usd'],2) }}</td><td class="text-end fw-semibold">{{ number_format($row['net_usd'],2) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="periods">
            <div class="row g-4">
                @can('manage-periods financial accounting')
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white"><strong>Create Fiscal Period</strong></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.accounting.periods.store') }}" class="row g-3">
                                @csrf
                                <div class="col-12"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
                                <div class="col-6"><label class="form-label">Starts</label><input class="form-control" type="date" name="starts_on" required></div>
                                <div class="col-6"><label class="form-label">Ends</label><input class="form-control" type="date" name="ends_on" required></div>
                                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes"></textarea></div>
                                <div class="col-12"><button class="btn btn-primary">Create Period</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                @endcan
                <div class="@can('manage-periods financial accounting') col-lg-8 @else col-12 @endcan">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white"><strong>Fiscal Periods</strong></div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Period</th><th>Dates</th><th>Status</th><th>Control</th></tr></thead>
                                <tbody>
                                    @foreach($periods as $period)
                                        <tr>
                                            <td>{{ $period->name }}</td>
                                            <td>{{ $period->starts_on->format('d M Y') }} — {{ $period->ends_on->format('d M Y') }}</td>
                                            <td><span class="badge {{ $period->status === 'open' ? 'bg-success' : ($period->status === 'closed' ? 'bg-warning text-dark' : 'bg-dark') }}">{{ ucfirst($period->status) }}</span></td>
                                            <td>
                                                @can('manage-periods financial accounting')
                                                    @if($period->status === 'open')
                                                        <form class="d-flex gap-2" method="POST" action="{{ route('admin.accounting.periods.close',$period) }}">@csrf<input class="form-control form-control-sm" name="reason" placeholder="Close reason" required><button class="btn btn-sm btn-warning">Close</button></form>
                                                    @elseif($period->status === 'closed')
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            <form class="d-flex gap-2" method="POST" action="{{ route('admin.accounting.periods.reopen',$period) }}">@csrf<input class="form-control form-control-sm" name="reason" placeholder="Reopen reason" required><button class="btn btn-sm btn-outline-primary">Reopen</button></form>
                                                            <form class="d-flex gap-2" method="POST" action="{{ route('admin.accounting.periods.lock',$period) }}">@csrf<input class="form-control form-control-sm" name="reason" placeholder="Lock reason" required><button class="btn btn-sm btn-dark">Lock</button></form>
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">Permanent lock</span>
                                                    @endif
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
