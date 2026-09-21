@extends('layouts.admin.base')

@section('title', 'Inventory Prevention Intelligence')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.investigations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Variance Investigations
            </a>
            <h1 class="h3 mt-2 mb-1">Inventory Prevention Intelligence</h1>
            <p class="text-muted mb-0">
                Detect recurring root causes, material hotspots and observed post-corrective variance patterns without changing historical records.
            </p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.trends') }}" class="btn btn-outline-primary">
            <i class="bi bi-graph-up-arrow me-1"></i> Variance Trends
        </a>
    </div>

    <div class="alert alert-info small">
        <strong>Interpretation rule:</strong>
        a recurrence is confirmed only when the same material receives the same investigated root-cause classification again.
        Corrective-action results are observational signals based on variance before and after resolution; they do not prove causation.
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" class="form-control"
                           value="{{ request('from_date', $analysis['from_date']) }}">
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" class="form-control"
                           value="{{ request('to_date', $analysis['to_date']) }}">
                </div>
                <div class="col-xl-3 col-md-4">
                    <label class="form-label">Material</label>
                    <select name="product_id" class="form-select">
                        <option value="">All raw materials</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                @selected((string) request('product_id') === (string) $product->id)>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-3 col-md-6">
                    <label class="form-label">Root Cause</label>
                    <select name="root_cause_code" class="form-select">
                        <option value="">All classified root causes</option>
                        @foreach($rootCauseCodes as $code => $label)
                            <option value="{{ $code }}"
                                @selected(request('root_cause_code') === $code)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-2 col-md-6 d-flex gap-2">
                    <button class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel me-1"></i> Analyze
                    </button>
                    <a href="{{ route('admin.stock-reconciliations.investigations.intelligence') }}"
                       class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Variance Lines</div>
                <div class="fs-4 fw-bold">{{ $analysis['summary']['variance_lines'] }}</div>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Abs. Variance Value</div>
                <div class="fs-5 fw-bold">&#36;{{ number_format($analysis['summary']['absolute_variance_value_usd'], 2) }}</div>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Investigations</div>
                <div class="fs-4 fw-bold">{{ $analysis['summary']['investigations'] }}</div>
                <small class="text-muted">{{ $analysis['summary']['classified_investigations'] }} classified</small>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card border-warning shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Recurring Patterns</div>
                <div class="fs-4 fw-bold text-warning">{{ $analysis['summary']['recurring_patterns'] }}</div>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card border-danger shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Critical Flags</div>
                <div class="fs-4 fw-bold text-danger">{{ $analysis['summary']['critical_flags'] }}</div>
            </div></div>
        </div>
        <div class="col-xl-2 col-md-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-body">
                <div class="text-muted small">Post-Action Recurrence</div>
                <div class="fs-4 fw-bold">{{ $analysis['summary']['post_corrective_recurrences'] }}</div>
            </div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-flag me-2"></i>Management Flags</strong>
            <span class="badge bg-light text-dark border">{{ $analysis['summary']['management_flags'] }} flags</span>
        </div>
        <div class="card-body">
            @forelse($analysis['flags'] as $flag)
                @php
                    $alertClass = match($flag['severity']) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        default => 'secondary',
                    };
                @endphp
                <div class="alert alert-{{ $alertClass }} d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <div class="fw-semibold">{{ $flag['title'] }} · {{ $flag['material_name'] }}</div>
                        <div class="small">{{ $flag['message'] }}</div>
                    </div>
                    <span class="badge bg-{{ $alertClass }}">{{ ucfirst($flag['severity']) }}</span>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    No management prevention flags were generated for the selected period.
                </div>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>
                <strong><i class="bi bi-arrow-repeat me-2"></i>Recurring Root-Cause Patterns</strong>
                <div class="small text-muted">
                    Default recurrence threshold: {{ $analysis['thresholds']['recurrence_count'] }} confirmed cases.
                    Critical threshold: {{ $analysis['thresholds']['critical_count'] }} cases.
                </div>
            </div>
            <div class="small text-muted">
                High-value signal: &#36;{{ number_format($analysis['thresholds']['high_value_usd'], 2) }}
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Root Cause</th>
                        <th class="text-end">Occurrences</th>
                        <th>First / Last</th>
                        <th class="text-end">Avg. Days Between</th>
                        <th class="text-end">Abs. Value</th>
                        <th class="text-end">Active</th>
                        <th>Signal</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($analysis['patterns'] as $row)
                    @php
                        $badge = match($row['severity']) {
                            'critical' => 'danger',
                            'high' => 'warning',
                            'watch' => 'info',
                            default => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $row['material_name'] }}</td>
                        <td>{{ $row['root_cause_label'] }}</td>
                        <td class="text-end fw-semibold">{{ $row['occurrences'] }}</td>
                        <td>
                            {{ $row['first_seen'] ? CarbonCarbon::parse($row['first_seen'])->format('d M Y') : '—' }}
                            <br>
                            <small class="text-muted">
                                to {{ $row['last_seen'] ? CarbonCarbon::parse($row['last_seen'])->format('d M Y') : '—' }}
                            </small>
                        </td>
                        <td class="text-end">
                            {{ $row['average_days_between'] !== null ? number_format($row['average_days_between'], 1) : '—' }}
                        </td>
                        <td class="text-end">&#36;{{ number_format($row['absolute_value_usd'], 2) }}</td>
                        <td class="text-end">{{ $row['active_cases'] }}</td>
                        <td>
                            <span class="badge bg-{{ $badge }}">
                                {{ $row['is_recurring'] ? 'Recurring' : ucfirst($row['severity']) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            No classified investigation patterns exist in this period.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong><i class="bi bi-box-seam me-2"></i>Material Variance Hotspots</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th class="text-end">Lines</th>
                                <th class="text-end">Short / Surplus</th>
                                <th class="text-end">Abs. Value</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($analysis['material_hotspots'] as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row['material_name'] }}</div>
                                    <small class="text-muted">{{ $row['investigated_lines'] }} investigated</small>
                                </td>
                                <td class="text-end">{{ $row['variance_lines'] }}</td>
                                <td class="text-end">{{ $row['shortage_lines'] }} / {{ $row['surplus_lines'] }}</td>
                                <td class="text-end">&#36;{{ number_format($row['absolute_value_usd'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No variance hotspots in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <strong><i class="bi bi-shield-check me-2"></i>Corrective-Action Follow-up</strong>
                    <div class="small text-muted">
                        Compares {{ $analysis['thresholds']['effectiveness_pre_days'] }} days before resolution with
                        {{ $analysis['thresholds']['effectiveness_post_days'] }} days after resolution.
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Case / Material</th>
                                <th>Root Cause</th>
                                <th class="text-end">Before</th>
                                <th class="text-end">After</th>
                                <th class="text-end">Confirmed Repeat</th>
                                <th>Signal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($analysis['effectiveness'] as $row)
                            @php
                                $signalBadge = match($row['signal']) {
                                    'no_recurrence' => 'success',
                                    'improved' => 'success',
                                    'recurrent' => 'danger',
                                    'needs_review' => 'warning',
                                    default => 'secondary',
                                };
                                $signalLabel = match($row['signal']) {
                                    'no_recurrence' => 'No recurrence observed',
                                    'improved' => 'Improved signal',
                                    'recurrent' => 'Confirmed recurrence',
                                    'needs_review' => 'Needs review',
                                    default => 'Monitoring',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.stock-reconciliations.investigations.show', $row['investigation_id']) }}"
                                       class="fw-semibold text-decoration-none">
                                        INV-{{ str_pad($row['investigation_id'], 6, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <div>{{ $row['material_name'] }}</div>
                                    <small class="text-muted">Resolved {{ CarbonCarbon::parse($row['resolved_at'])->format('d M Y') }}</small>
                                </td>
                                <td>{{ $row['root_cause_label'] }}</td>
                                <td class="text-end">
                                    <div>{{ $row['pre_lines'] }} lines</div>
                                    <small>&#36;{{ number_format($row['pre_absolute_value_usd'], 2) }}</small>
                                </td>
                                <td class="text-end">
                                    <div>{{ $row['post_lines'] }} lines</div>
                                    <small>&#36;{{ number_format($row['post_absolute_value_usd'], 2) }}</small>
                                </td>
                                <td class="text-end">{{ $row['confirmed_recurrences'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $signalBadge }}">{{ $signalLabel }}</span>
                                    @if(! $row['post_window_complete'])
                                        <div class="small text-muted mt-1">Post window still open</div>
                                    @elseif($row['value_change_percentage'] !== null)
                                        <div class="small text-muted mt-1">
                                            {{ $row['value_change_percentage'] > 0 ? '+' : '' }}{{ number_format($row['value_change_percentage'], 1) }}% variance-value change
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No resolved investigations are available for corrective-action follow-up.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
