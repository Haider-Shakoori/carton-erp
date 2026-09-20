@extends('layouts.admin.base')

@section('title', $stockReconciliation->reconciliation_no)

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
            </a>
            <h1 class="h3 mt-2 mb-1">{{ $stockReconciliation->reconciliation_no }}</h1>
            <div class="text-muted">
                Count date {{ $stockReconciliation->count_date?->format('d M Y') }}
                · Snapshot {{ $stockReconciliation->snapshot_at?->format('d M Y H:i:s') }}
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
            @php
                $statusBadge = match($stockReconciliation->status) {
                    'posted' => 'success',
                    'approved' => 'primary',
                    'submitted' => 'warning',
                    'rejected' => 'danger',
                    'cancelled' => 'secondary',
                    default => 'info',
                };
            @endphp
            <span class="badge fs-6 bg-{{ $statusBadge }}">{{ ucfirst($stockReconciliation->status) }}</span>

            @if($stockReconciliation->status === 'counting')
                @can('submit stock reconciliations')
                    <form method="POST" action="{{ route('admin.stock-reconciliations.submit', $stockReconciliation) }}"
                          onsubmit="return confirm('Submit this count for approval? Inventory will still NOT be changed.');">
                        @csrf
                        <button class="btn btn-success"><i class="bi bi-send-check me-1"></i> Submit for Approval</button>
                    </form>
                @endcan
            @elseif($stockReconciliation->status === 'submitted')
                @can('approve stock reconciliations')
                    <form method="POST" action="{{ route('admin.stock-reconciliations.approve', $stockReconciliation) }}"
                          onsubmit="return confirm('Approve this reconciliation? Stock will still not change until it is posted.');">
                        @csrf
                        <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Approve</button>
                    </form>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectReconciliationModal">
                        <i class="bi bi-x-circle me-1"></i> Reject
                    </button>
                @endcan
            @elseif($stockReconciliation->status === 'approved')
                @can('post stock reconciliations')
                    <form method="POST" action="{{ route('admin.stock-reconciliations.post', $stockReconciliation) }}"
                          onsubmit="return confirm('POST this reconciliation? This will create immutable stock adjustments and change FIFO batch balances.');">
                        @csrf
                        <button class="btn btn-danger"><i class="bi bi-journal-check me-1"></i> Post Stock Adjustment</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Check the count entries.</strong> {{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Inventory Lines</div><div class="fs-4 fw-bold">{{ $summary['lines'] }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Counted</div><div class="fs-4 fw-bold">{{ $summary['counted'] }} / {{ $summary['lines'] }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Negative Variance Value</div><div class="fs-4 fw-bold text-danger">${{ number_format(abs($summary['negative_variance_usd']), 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Net Variance Value</div><div class="fs-4 fw-bold {{ $summary['net_variance_usd'] < 0 ? 'text-danger' : 'text-success' }}">
                {{ $summary['net_variance_usd'] >= 0 ? '+' : '-' }}${{ number_format(abs($summary['net_variance_usd']), 2) }}
            </div>
        </div></div></div>
    </div>

    <div class="alert alert-secondary small">
        <i class="bi bi-shield-lock me-1"></i>
        <strong>Snapshot rule:</strong> System Qty is frozen and never recalculated on this document.
        Saving counts and submitting for approval do not change purchase batches or FIFO stock.
    </div>

    @if($stockReconciliation->status === 'approved')
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Approved, not posted:</strong> inventory is still unchanged. Posting will apply each stored variance as a signed batch adjustment while preserving stock movements that occurred after the snapshot.
        </div>
    @elseif($stockReconciliation->status === 'rejected')
        <div class="alert alert-danger small">
            <strong>Rejected:</strong> {{ $stockReconciliation->rejection_reason ?: 'No reason recorded.' }}
        </div>
    @elseif($stockReconciliation->status === 'posted')
        <div class="alert alert-success small">
            <i class="bi bi-check-circle me-1"></i>
            <strong>Posted:</strong> FIFO batch balances were adjusted through an immutable stock-adjustment ledger.
        </div>
    @endif

    @if($stockReconciliation->status === 'counting')
        <form method="POST" action="{{ route('admin.stock-reconciliations.counts.update', $stockReconciliation) }}" id="countForm">
            @csrf
            @method('PATCH')
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:220px;">Material / Batch</th>
                        <th class="text-end">System Qty</th>
                        <th style="min-width:150px;">Physical Qty</th>
                        <th class="text-end">Variance</th>
                        <th class="text-end">Variance Value</th>
                        <th style="min-width:210px;">Reason</th>
                        <th style="min-width:220px;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockReconciliation->items as $item)
                        <tr data-system="{{ (float) $item->system_quantity }}" data-cost="{{ (float) $item->cost_per_unit_usd }}">
                            <td>
                                <div class="fw-semibold">{{ $item->product?->name ?? 'Unknown Material' }}</div>
                                <small class="text-muted">
                                    {{ $item->purchase_no ?: 'PO N/A' }} · Batch {{ $item->batch_no ?: '#'.$item->purchase_item_id }}
                                    · {{ $item->inventory_unit }}
                                </small>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->system_quantity, 4) }}</td>
                            <td>
                                @if($stockReconciliation->status === 'counting')
                                    <input type="number" min="0" step="0.000001"
                                           name="items[{{ $item->id }}][physical_quantity]"
                                           value="{{ old('items.'.$item->id.'.physical_quantity', $item->physical_quantity) }}"
                                           class="form-control form-control-sm physical-input">
                                @else
                                    <span class="fw-semibold">{{ $item->physical_quantity !== null ? number_format((float) $item->physical_quantity, 4) : '—' }}</span>
                                @endif
                            </td>
                            <td class="text-end variance-cell {{ (float) $item->variance_quantity < 0 ? 'text-danger' : ((float) $item->variance_quantity > 0 ? 'text-success' : '') }}">
                                {{ $item->variance_quantity !== null ? (($item->variance_quantity > 0 ? '+' : '').number_format((float) $item->variance_quantity, 4)) : '—' }}
                            </td>
                            <td class="text-end variance-value-cell">
                                {{ $item->variance_value_usd !== null ? (($item->variance_value_usd >= 0 ? '+' : '-').'$'.number_format(abs((float) $item->variance_value_usd), 2)) : '—' }}
                            </td>
                            <td>
                                @if($stockReconciliation->status === 'counting')
                                    <select name="items[{{ $item->id }}][reason_code]" class="form-select form-select-sm">
                                        <option value="">Select if variance exists</option>
                                        @foreach($reasonCodes as $code => $label)
                                            <option value="{{ $code }}" @selected(old('items.'.$item->id.'.reason_code', $item->reason_code) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $reasonCodes[$item->reason_code] ?? ($item->reason_code ?: '—') }}
                                @endif
                            </td>
                            <td>
                                @if($stockReconciliation->status === 'counting')
                                    <input type="text" name="items[{{ $item->id }}][notes]"
                                           value="{{ old('items.'.$item->id.'.notes', $item->notes) }}"
                                           class="form-control form-control-sm" maxlength="1000">
                                @else
                                    {{ $item->notes ?: '—' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($stockReconciliation->status === 'counting')
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="text-muted small">You can save a partial count and continue later.</span>
                @can('update stock reconciliations')
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Counts</button>
                @endcan
            </div>
        @endif
    </div>

    @if($stockReconciliation->status === 'counting')</form>@endif

    @if($stockReconciliation->adjustment)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Posted Adjustment Ledger</h5>
                    <small class="text-muted">{{ $stockReconciliation->adjustment->adjustment_no }}</small>
                </div>
                <span class="badge bg-success">Immutable / Posted</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Material / Batch</th>
                            <th class="text-end">Before</th>
                            <th class="text-end">Adjustment</th>
                            <th class="text-end">After</th>
                            <th class="text-end">Value USD</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockReconciliation->adjustment->items as $line)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $line->product?->name ?? 'Unknown Material' }}</div>
                                    <small class="text-muted">Batch #{{ $line->purchase_item_id }} · {{ $line->inventory_unit }}</small>
                                </td>
                                <td class="text-end">{{ number_format((float) $line->before_quantity, 4) }}</td>
                                <td class="text-end {{ (float) $line->adjustment_quantity < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ (float) $line->adjustment_quantity > 0 ? '+' : '' }}{{ number_format((float) $line->adjustment_quantity, 4) }}
                                </td>
                                <td class="text-end fw-semibold">{{ number_format((float) $line->after_quantity, 4) }}</td>
                                <td class="text-end {{ (float) $line->adjustment_value_usd < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ (float) $line->adjustment_value_usd >= 0 ? '+' : '-' }}${{ number_format(abs((float) $line->adjustment_value_usd), 2) }}
                                </td>
                                <td>{{ $reasonCodes[$line->reason_code] ?? ($line->reason_code ?: '—') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No quantity adjustment was necessary; physical stock matched the snapshot.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($stockReconciliation->status === 'submitted')
        @can('approve stock reconciliations')
            <div class="modal fade" id="rejectReconciliationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('admin.stock-reconciliations.reject', $stockReconciliation) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Reject Stock Reconciliation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" rows="4" class="form-control" maxlength="2000" required></textarea>
                                <div class="form-text">Rejection never changes inventory.</div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-danger">Reject</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.physical-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const row = input.closest('tr');
            const system = Number(row.dataset.system || 0);
            const cost = Number(row.dataset.cost || 0);
            const raw = input.value.trim();

            if (raw === '') {
                row.querySelector('.variance-cell').textContent = '—';
                row.querySelector('.variance-value-cell').textContent = '—';
                return;
            }

            const variance = Number(raw) - system;
            const value = variance * cost;
            const varianceCell = row.querySelector('.variance-cell');
            varianceCell.textContent = (variance > 0 ? '+' : '') + variance.toFixed(4);
            varianceCell.classList.toggle('text-danger', variance < 0);
            varianceCell.classList.toggle('text-success', variance > 0);

            row.querySelector('.variance-value-cell').textContent =
                (value >= 0 ? '+' : '-') + '$' + Math.abs(value).toFixed(2);
        });
    });
});
</script>
@endsection
