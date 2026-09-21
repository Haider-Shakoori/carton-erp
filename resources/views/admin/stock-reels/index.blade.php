@extends('layouts.admin.base')

@section('title', 'Physical Reel Tracking')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Inventory Ledger
            </a>
            <h1 class="h3 mt-2 mb-1">Physical Reel Tracking</h1>
            <p class="text-muted mb-0">Track individual paper reels, production usage and measured remnants while keeping batch kg as the authoritative FIFO inventory balance.</p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-clipboard-check me-1"></i> Stock Reconciliation
        </a>
    </div>

    <div class="alert alert-info small">
        <strong>Control rule:</strong> warehouse reel measurements are observational. They never overwrite inventory directly.
        If physical reel totals differ from the ERP batch balance, post the normal approved stock reconciliation first, then re-baseline the reel records.
    </div>

    <div class="card border-primary shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.stock-reels.scan') }}" class="row g-2 align-items-end">
                <div class="col-lg-8">
                    <label class="form-label fw-semibold">Scan / Enter Reel Code</label>
                    <input type="text" name="code" class="form-control" maxlength="160"
                           placeholder="Scan barcode, enter REEL-00000001, or type the printed reel code"
                           autocomplete="off" inputmode="text">
                    <div class="form-text">Scanner input is identification-only. It does not change stock, status, or measured weight.</div>
                </div>
                <div class="col-lg-4">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-upc-scan me-1"></i> Find Reel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Roll Batches</div><div class="fs-3 fw-bold">{{ $stats['batches'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-success shadow-sm"><div class="card-body">
            <div class="text-muted small">Tracked</div><div class="fs-3 fw-bold text-success">{{ $stats['tracked'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-danger shadow-sm"><div class="card-body">
            <div class="text-muted small">Out of Sync</div><div class="fs-3 fw-bold text-danger">{{ $stats['out_of_sync'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Partial Reels</div><div class="fs-3 fw-bold text-warning">{{ $stats['open_reels'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-danger shadow-sm"><div class="card-body">
            <div class="text-muted small">Blocked Reels</div><div class="fs-3 fw-bold text-danger">{{ $stats['blocked_reels'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Roll Inventory Value</div><div class="fs-5 fw-bold">&#36;{{ number_format($stats['inventory_value_usd'], 2) }}</div>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Tracking Status</label>
                <select name="tracking" class="form-select">
                    <option value="">All roll batches</option>
                    <option value="tracked" @selected(request('tracking') === 'tracked')>Tracked</option>
                    <option value="untracked" @selected(request('tracking') === 'untracked')>Untracked</option>
                    <option value="out_of_sync" @selected(request('tracking') === 'out_of_sync')>Out of sync</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('admin.stock-reels.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div></div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material / Batch</th>
                        <th>Purchase</th>
                        <th class="text-end">Batch Available</th>
                        <th class="text-end">Tracked Reels</th>
                        <th class="text-end">Tracked System Kg</th>
                        <th class="text-end">Latest Measured Kg</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batches as $batch)
                    @php $s = $summaries[$batch->id]; @endphp
                    <tr class="{{ $s['tracked'] && ! $s['healthy'] ? 'table-danger' : '' }}">
                        <td>
                            <div class="fw-semibold">{{ $batch->product?->name ?? 'Unknown material' }}</div>
                            <small class="text-muted">{{ $batch->batch_no ?: 'Batch #'.$batch->id }}</small>
                        </td>
                        <td>
                            {{ $batch->purchase?->purchase_no ?? '—' }}
                            <br><small class="text-muted">{{ $batch->purchase?->purchase_date ?? $batch->created_at?->toDateString() }}</small>
                            <br><small class="text-muted">Age: {{ $s['age_days'] !== null ? $s['age_days'].' days' : '—' }}</small>
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold">{{ number_format($s['batch_available_kg'], 4) }} kg</div>
                            <small class="text-muted">{{ number_format((float) $batch->qty_available, 4) }} roll-equiv.</small>
                            <br><small class="text-muted">Value: &#36;{{ number_format($s['inventory_value_usd'], 2) }}</small>
                        </td>
                        <td class="text-end">
                            @if($s['tracked'])
                                {{ $s['reel_count'] }}
                                <br><small class="text-muted">{{ $s['open_count'] }} partial / {{ $s['sealed_count'] }} full</small>
                                @if($s['blocked_count'] > 0)
                                    <br><small class="text-danger">{{ $s['blocked_count'] }} blocked · {{ number_format($s['blocked_kg'], 4) }} kg</small>
                                @endif
                            @else
                                <span class="text-muted">Not initialized</span>
                            @endif
                        </td>
                        <td class="text-end">
                            {{ $s['tracked'] ? number_format($s['tracked_system_kg'], 4).' kg' : '—' }}
                        </td>
                        <td class="text-end">
                            @if($s['measured_count'] > 0)
                                {{ number_format($s['latest_measured_total_kg'], 4) }} kg
                                <br><small class="text-muted">{{ $s['measured_count'] }}/{{ $s['reel_count'] }} measured</small>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if(! $s['tracked'])
                                <span class="badge bg-secondary">Untracked</span>
                            @elseif($s['healthy'])
                                <span class="badge bg-success">Aligned</span>
                            @else
                                <span class="badge bg-danger">Out of sync</span>
                                <div class="small text-danger mt-1">{{ number_format($s['difference_kg'], 4) }} kg reel-vs-batch</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.stock-reels.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-minecart-loaded me-1"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-muted">No arrived roll-based inventory batches found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white">{{ $batches->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
