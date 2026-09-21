@extends('layouts.admin.base')

@section('title', 'Physical Reel Batch')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reels.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Physical Reel Tracking
            </a>
            <h1 class="h3 mt-2 mb-1">{{ $purchaseItem->product?->name ?? 'Roll Material' }}</h1>
            <p class="text-muted mb-0">
                {{ $purchaseItem->batch_no ?: 'Batch #'.$purchaseItem->id }}
                · {{ $purchaseItem->purchase?->purchase_no ?? 'Purchase unavailable' }}
                · Age {{ $summary['age_days'] !== null ? $summary['age_days'].' days' : '—' }}
            </p>
        </div>
        <a href="{{ route('admin.stock-reconciliations.create') }}" class="btn btn-outline-primary">
            <i class="bi bi-clipboard-plus me-1"></i> Start Physical Count
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @php
        $readiness = $summary['measurement_readiness'];
        $readinessAlert = match($readiness['state']) {
            'aligned' => 'success',
            'reconcile' => 'primary',
            'stale' => 'danger',
            'incomplete' => 'warning',
            default => 'secondary',
        };
    @endphp

    @if($summary['tracked'] && ! $summary['healthy'])
        <div class="alert alert-danger">
            <strong>Reel tracking is out of sync with the authoritative batch balance.</strong>
            Production consumption from this tracked batch will be blocked until the reels are measured and safely re-baselined.
            Current difference: {{ number_format($summary['difference_kg'], 4) }} kg.
        </div>
    @endif

    @if($summary['blocked_count'] > 0)
        <div class="alert alert-warning">
            <strong>{{ $summary['blocked_count'] }} reel(s) are under warehouse control hold.</strong>
            {{ number_format($summary['blocked_kg'], 4) }} kg remains in inventory and valuation but is not production-eligible until an audited release is recorded.
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Batch Available</div><div class="fs-5 fw-bold">{{ number_format($summary['batch_available_kg'], 4) }} kg</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Physical Reels</div><div class="fs-4 fw-bold">{{ $summary['reel_count'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-warning shadow-sm"><div class="card-body">
            <div class="text-muted small">Partial Reels</div><div class="fs-4 fw-bold text-warning">{{ $summary['open_count'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-success shadow-sm"><div class="card-body">
            <div class="text-muted small">Production Eligible</div><div class="fs-5 fw-bold text-success">{{ number_format($summary['eligible_kg'], 4) }} kg</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-danger shadow-sm"><div class="card-body">
            <div class="text-muted small">Blocked Reels</div><div class="fs-4 fw-bold text-danger">{{ $summary['blocked_count'] }}</div>
        </div></div></div>
        <div class="col-xl-2 col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Inventory Value</div>
            <div class="fs-5 fw-bold">&#36;{{ number_format($summary['inventory_value_usd'], 2) }}</div>
            <small class="text-muted">&#36;{{ number_format($purchaseItem->landedCostPerKg(), 6) }}/kg landed</small>
        </div></div></div>
    </div>

    <div class="alert alert-info small">
        <strong>System Remaining</strong> decreases through actual production consumption.
        <strong>Measured Remnant</strong> is the latest warehouse scale reading and does not change stock by itself.
        <strong>Damaged / Quarantined</strong> are production-control states only; their weight remains in authoritative inventory until the normal approval-based reconciliation posts an adjustment.
    </div>

    @if($summary['tracked'])
        <div class="alert alert-{{ $readinessAlert }}">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                <div>
                    <strong>Measurement Readiness: {{ $readiness['label'] }}</strong>
                    <div>{{ $readiness['message'] }}</div>
                </div>
                @if($readiness['active_count'] > 0)
                    <div class="text-lg-end small">
                        <div>{{ $readiness['fresh_count'] }}/{{ $readiness['active_count'] }} active reels fresh</div>
                        <div>
                            Measured {{ number_format($readiness['measured_total_kg'], 4) }} kg
                            · ERP {{ number_format($readiness['batch_available_kg'], 4) }} kg
                        </div>
                        @if($readiness['complete'])
                            <div class="fw-semibold">
                                Variance {{ $readiness['variance_kg'] > 0 ? '+' : '' }}{{ number_format($readiness['variance_kg'], 4) }} kg
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if(! $summary['tracked'])
        @can('update stock')
        <div class="card border-primary shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Initialize Physical Reel Tracking</strong></div>
            <div class="card-body">
                <p class="small text-muted">
                    For a new untouched batch, leave the field blank and the ERP will create one reel per purchased roll using {{ number_format((float) $purchaseItem->kg_per_roll, 4) }} kg each.
                    For a partially used/legacy batch, enter the current ERP system weight of each remaining reel separated by commas or new lines.
                    The total must equal {{ number_format($summary['batch_available_kg'], 4) }} kg.
                </p>
                <form method="POST" action="{{ route('admin.stock-reels.initialize', $purchaseItem) }}">
                    @csrf
                    <label class="form-label">Current Reel System Weights (kg)</label>
                    <textarea name="current_reel_weights" rows="5" class="form-control mb-3"
                              placeholder="Example: 627.4&#10;1148.0&#10;1150.0">{{ old('current_reel_weights') }}</textarea>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">If the physical total differs from ERP, reconcile the batch first—do not force the numbers here.</small>
                        <button class="btn btn-primary"><i class="bi bi-play-circle me-1"></i> Initialize Reels</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    @else
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                <div>
                    <strong>Physical Reels & Remnant Weights</strong>
                    <div class="small text-muted">Full/partial identity, measured remainder and warehouse control state are independent but fully traceable.</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @can('create stock reconciliations')
                    <form method="POST" action="{{ route('admin.stock-reels.reconciliation', $purchaseItem) }}">
                        @csrf
                        <button class="btn btn-outline-primary"
                                @disabled(! $readiness['needs_reconciliation'])
                                title="{{ $readiness['needs_reconciliation'] ? 'Create a controlled draft reconciliation from the fresh measured variance.' : $readiness['message'] }}"
                                onclick="return confirm('Create a draft stock reconciliation from the latest reel measurements? Inventory will remain unchanged until normal approval and posting.')">
                            <i class="bi bi-clipboard-check me-1"></i> Reconcile Measured Total
                        </button>
                    </form>
                    @endcan
                    @can('update stock')
                    <form method="POST" action="{{ route('admin.stock-reels.rebaseline', $purchaseItem) }}">
                        @csrf
                        <button class="btn btn-outline-danger"
                                @disabled(! $readiness['can_rebaseline'])
                                title="{{ $readiness['can_rebaseline'] ? 'Align reel system weights to the fresh measurements without changing batch stock.' : $readiness['message'] }}"
                                onclick="return confirm('Re-baseline reel system weights to the latest measurements? This does not change the batch stock total or release control holds.')">
                            <i class="bi bi-arrow-repeat me-1"></i> Re-baseline From Measurements
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reel</th>
                            <th>Reel Control Status</th>
                            <th class="text-end">Original / Registered</th>
                            <th class="text-end">System Remaining</th>
                            <th class="text-end">Measured Remnant</th>
                            <th class="text-end">Variance @ Measurement</th>
                            <th>Measured</th>
                            @can('update stock')<th style="min-width:260px;">Record Measurement</th>@endcan
                            @can('update stock')<th style="min-width:320px;">Warehouse Control</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($summary['reels'] as $reel)
                        @php
                            $statusBadge = match($reel->status) {
                                'sealed' => 'success',
                                'open' => 'warning',
                                'damaged' => 'danger',
                                'quarantined' => 'dark',
                                'consumed' => 'secondary',
                                default => 'secondary',
                            };
                            $statusLabel = match($reel->status) {
                                'sealed' => 'Full / Available',
                                'open' => 'Partial / Available',
                                'damaged' => 'Damaged',
                                'quarantined' => 'Quarantined',
                                'consumed' => 'Consumed',
                                default => ucfirst($reel->status),
                            };
                            $variance = $reel->measurement_variance_kg !== null
                                ? (float) $reel->measurement_variance_kg
                                : null;
                            $isUnmeasured = in_array(
                                (int) $reel->id,
                                $readiness['unmeasured_reel_ids'],
                                true
                            );
                            $isStale = in_array(
                                (int) $reel->id,
                                $readiness['stale_reel_ids'],
                                true
                            );
                            $isFresh = in_array(
                                (int) $reel->id,
                                $readiness['fresh_reel_ids'],
                                true
                            );
                        @endphp
                        <tr class="{{ $reel->isBlockedFromProduction() ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $reel->reel_code }}</div>
                                <small class="text-muted">#{{ $reel->sequence_no }}</small>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                @if($reel->status_reason)
                                    <div class="small text-muted mt-1">{{ $reel->status_reason }}</div>
                                @endif
                                @if($reel->status_changed_at)
                                    <div class="small text-muted">
                                        {{ $reel->status_changed_at->format('d M Y H:i') }}
                                        · {{ $reel->statusChangedBy?->name ?? 'Unknown user' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $reel->registered_weight_kg, 4) }} kg</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $reel->system_remaining_weight_kg, 4) }} kg</td>
                            <td class="text-end">
                                {{ $reel->last_measured_weight_kg !== null ? number_format((float) $reel->last_measured_weight_kg, 4).' kg' : '—' }}
                            </td>
                            <td class="text-end {{ $variance !== null && abs($variance) > 0.05 ? 'text-danger' : '' }}">
                                @if($variance !== null)
                                    {{ $variance > 0 ? '+' : '' }}{{ number_format($variance, 4) }} kg
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($isStale)
                                    <span class="badge bg-danger mb-1">Stale · re-weigh</span><br>
                                @elseif($isUnmeasured)
                                    <span class="badge bg-warning text-dark mb-1">Measurement required</span><br>
                                @elseif($isFresh)
                                    <span class="badge bg-success mb-1">Fresh</span><br>
                                @endif
                                @if($reel->last_measured_at)
                                    {{ $reel->last_measured_at->format('d M Y H:i') }}
                                    <br><small class="text-muted">{{ $reel->measuredBy?->name ?? 'Unknown user' }} · {{ $reel->measurement_age_days }}d ago</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            @can('update stock')
                            <td>
                                <form method="POST" action="{{ route('admin.stock-reels.measure', $reel) }}" class="row g-1">
                                    @csrf
                                    <div class="col-5">
                                        <input type="number" name="measured_weight_kg" step="0.0001" min="0"
                                               class="form-control form-control-sm" placeholder="kg" required>
                                    </div>
                                    <div class="col-5">
                                        <input type="text" name="notes" maxlength="5000"
                                               class="form-control form-control-sm" placeholder="Scale / note">
                                    </div>
                                    <div class="col-2">
                                        <button class="btn btn-sm btn-outline-primary w-100" title="Record measurement">
                                            <i class="bi bi-weight-scale"></i>
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                @if($reel->status !== 'consumed')
                                    <form method="POST" action="{{ route('admin.stock-reels.status', $reel) }}" class="row g-1">
                                        @csrf
                                        @method('PATCH')
                                        <div class="col-5">
                                            <select name="action" class="form-select form-select-sm" required>
                                                <option value="">Action…</option>
                                                @if($reel->isBlockedFromProduction())
                                                    <option value="release">Release Hold</option>
                                                @endif
                                                @if($reel->status !== 'damaged')
                                                    <option value="damaged">Mark Damaged</option>
                                                @endif
                                                @if($reel->status !== 'quarantined')
                                                    <option value="quarantined">Quarantine</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-5">
                                            <input type="text" name="reason" maxlength="5000"
                                                   class="form-control form-control-sm" placeholder="Required reason" required>
                                        </div>
                                        <div class="col-2">
                                            <button class="btn btn-sm btn-outline-secondary w-100" title="Apply control status">
                                                <i class="bi bi-shield-check"></i>
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-muted small">No control action</span>
                                @endif
                            </td>
                            @endcan
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Recent Production Reel Lineage</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Production</th><th>Reel</th><th>Allocation</th><th class="text-end">Consumed</th><th class="text-end">Before</th><th class="text-end">After</th><th class="text-end">Measured Final</th></tr>
                    </thead>
                    <tbody>
                    @forelse($recentConsumptions as $row)
                        <tr>
                            <td>{{ $row->consumed_at?->format('d M Y H:i') }}</td>
                            <td>
                                @if($row->materialConsumption?->productionOrder)
                                    {{ $row->materialConsumption->productionOrder->order_number }}
                                @else
                                    Production #{{ $row->materialConsumption?->production_order_id }}
                                @endif
                            </td>
                            <td>{{ $row->reel?->reel_code ?? '—' }}</td>
                            <td>
                                @if($row->allocation_method === 'operator_selected')
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Operator selected</span>
                                    <div class="small text-muted mt-1">
                                        {{ $row->selectedBy?->name ?? 'Unknown user' }}
                                        @if($row->selection_note)
                                            · {{ $row->selection_note }}
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-light text-dark border">FIFO</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $row->quantity_kg, 4) }} kg</td>
                            <td class="text-end">{{ number_format((float) $row->before_weight_kg, 4) }} kg</td>
                            <td class="text-end">{{ number_format((float) $row->after_weight_kg, 4) }} kg</td>
                            <td class="text-end">
                                {{ $row->declared_final_weight_kg !== null ? number_format((float) $row->declared_final_weight_kg, 4).' kg' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No production has consumed a tracked reel from this batch yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Status History</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Reel</th><th>From</th><th>To</th><th>User / Reason</th></tr></thead>
                    <tbody>
                    @php
                        $statusHistory = $summary['reels']
                            ->flatMap(fn($reel) => $reel->statusEvents->map(fn($event) => ['reel' => $reel, 'event' => $event]))
                            ->sortByDesc(fn($row) => $row['event']->changed_at)
                            ->take(50);
                    @endphp
                    @forelse($statusHistory as $row)
                        @php $event = $row['event']; @endphp
                        <tr>
                            <td>{{ $event->changed_at?->format('d M Y H:i') }}</td>
                            <td>{{ $row['reel']->reel_code }}</td>
                            <td>{{ $event->from_status ? ucfirst($event->from_status) : '—' }}</td>
                            <td>{{ ucfirst($event->to_status) }}</td>
                            <td>{{ $event->changedBy?->name ?? 'Unknown user' }} · {{ $event->reason }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No reel control-status changes recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Measurement History</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Reel</th><th class="text-end">System Snapshot</th><th class="text-end">Measured</th><th class="text-end">Variance</th><th>User / Note</th></tr></thead>
                    <tbody>
                    @php
                        $history = $summary['reels']
                            ->flatMap(fn($reel) => $reel->measurements->map(fn($m) => ['reel' => $reel, 'measurement' => $m]))
                            ->sortByDesc(fn($row) => $row['measurement']->measured_at)
                            ->take(50);
                    @endphp
                    @forelse($history as $row)
                        @php $m = $row['measurement']; @endphp
                        <tr>
                            <td>{{ $m->measured_at?->format('d M Y H:i') }}</td>
                            <td>{{ $row['reel']->reel_code }}</td>
                            <td class="text-end">{{ number_format((float) $m->system_weight_snapshot_kg, 4) }} kg</td>
                            <td class="text-end">{{ number_format((float) $m->measured_weight_kg, 4) }} kg</td>
                            <td class="text-end {{ abs((float) $m->variance_kg) > 0.05 ? 'text-danger' : '' }}">
                                {{ (float) $m->variance_kg > 0 ? '+' : '' }}{{ number_format((float) $m->variance_kg, 4) }} kg
                            </td>
                            <td>{{ $m->measurer?->name ?? 'Unknown user' }}{{ $m->notes ? ' · '.$m->notes : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No reel measurements recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
