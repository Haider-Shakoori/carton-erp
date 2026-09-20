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
        <div class="d-flex gap-2 align-items-center">
            <span class="badge fs-6 bg-{{ $stockReconciliation->status === 'submitted' ? 'warning' : ($stockReconciliation->status === 'posted' ? 'success' : 'info') }}">
                {{ ucfirst($stockReconciliation->status) }}
            </span>
            @if($stockReconciliation->status === 'counting')
                @can('submit stock reconciliations')
                    <form method="POST" action="{{ route('admin.stock-reconciliations.submit', $stockReconciliation) }}"
                          onsubmit="return confirm('Submit this count for approval? Inventory will still NOT be changed.');">
                        @csrf
                        <button class="btn btn-success"><i class="bi bi-send-check me-1"></i> Submit for Approval</button>
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
