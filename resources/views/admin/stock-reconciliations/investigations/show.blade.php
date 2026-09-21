@extends('layouts.admin.base')

@section('title', 'Variance Investigation')

@section('content')
<div class="container-fluid px-3 px-md-4">
    @php
        $line = $investigation->adjustmentItem;
        $adjustment = $line?->adjustment;
        $reconciliation = $adjustment?->reconciliation;
        $statusBadge = match($investigation->status) {
            'resolved' => 'success',
            'investigating' => 'primary',
            default => 'warning',
        };
    @endphp

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.investigations.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Variance Investigations
            </a>
            <h1 class="h3 mt-2 mb-1">INV-{{ str_pad($investigation->id, 6, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-muted mb-0">Root-cause and corrective-action record for a posted stock variance.</p>
        </div>
        <span class="badge bg-{{ $statusBadge }} fs-6">{{ ucfirst($investigation->status) }}</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Variance Source</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="text-muted small">Material</div><div class="fw-semibold">{{ $line?->product?->name }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">Batch</div><div>{{ $line?->purchaseItem?->batch_no ?: '#'.$line?->purchase_item_id }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">Unit</div><div>{{ $line?->inventory_unit }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">Before</div><div>{{ number_format((float) $line?->before_quantity, 4) }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">Adjustment</div><div class="{{ (float) $line?->adjustment_quantity < 0 ? 'text-danger' : 'text-success' }}">{{ (float) $line?->adjustment_quantity > 0 ? '+' : '' }}{{ number_format((float) $line?->adjustment_quantity, 4) }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">After</div><div>{{ number_format((float) $line?->after_quantity, 4) }}</div></div>
                        <div class="col-md-3"><div class="text-muted small">Variance Value</div><div class="fw-semibold">{{ (float) $line?->adjustment_value_usd >= 0 ? '+' : '-' }}&#36;{{ number_format(abs((float) $line?->adjustment_value_usd), 2) }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Original Reason</div><div>{{ config('stock_reconciliation.reason_codes.'.$line?->reason_code, $line?->reason_code ?: '—') }}</div></div>
                        <div class="col-md-6"><div class="text-muted small">Original Notes</div><div>{{ $line?->notes ?: '—' }}</div></div>
                        @if($reconciliation)
                        <div class="col-12">
                            <a href="{{ route('admin.stock-reconciliations.show', $reconciliation) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-clipboard-check me-1"></i> {{ $reconciliation->reconciliation_no }}
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Ownership & Aging</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Opened</dt><dd class="col-7">{{ $investigation->opened_at?->format('d M Y H:i') }}</dd>
                        <dt class="col-5">Opened By</dt><dd class="col-7">{{ $investigation->opener?->name ?? 'System' }}</dd>
                        <dt class="col-5">Assigned To</dt><dd class="col-7">{{ $investigation->assignee?->name ?? 'Unassigned' }}</dd>
                        <dt class="col-5">Age</dt><dd class="col-7">{{ $investigation->age_days }} days</dd>
                        <dt class="col-5">Due</dt><dd class="col-7">{{ $investigation->due_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-5">Overdue</dt><dd class="col-7">{{ $investigation->is_overdue ? 'Yes' : 'No' }}</dd>
                        @if($investigation->resolved_at)
                            <dt class="col-5">Resolved</dt><dd class="col-7">{{ $investigation->resolved_at->format('d M Y H:i') }}</dd>
                            <dt class="col-5">Resolved By</dt><dd class="col-7">{{ $investigation->resolver?->name ?? '—' }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if($investigation->status !== 'resolved')
        @can('investigate stock reconciliations')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Investigation Work</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.stock-reconciliations.investigations.update', $investigation) }}">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Responsible Person</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('assigned_to', $investigation->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $investigation->due_date?->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Root Cause Category</label>
                            <select name="root_cause_code" class="form-select">
                                <option value="">Not determined</option>
                                @foreach($rootCauseCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('root_cause_code', $investigation->root_cause_code) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Update / Event Note</label>
                            <input type="text" name="event_note" maxlength="2000" class="form-control" placeholder="What changed in this update?">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Root Cause Details</label>
                            <textarea name="root_cause_details" rows="3" class="form-control">{{ old('root_cause_details', $investigation->root_cause_details) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Investigation Notes</label>
                            <textarea name="investigation_notes" rows="3" class="form-control">{{ old('investigation_notes', $investigation->investigation_notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Corrective Action</label>
                            <textarea name="corrective_action" rows="3" class="form-control">{{ old('corrective_action', $investigation->corrective_action) }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Investigation</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        @can('resolve stock reconciliations')
        <div class="card border-success shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Resolve Investigation</strong></div>
            <div class="card-body">
                <div class="alert alert-success small">
                    Resolution requires a documented root cause, corrective action and final resolution note. The original stock adjustment remains immutable.
                </div>
                <form method="POST" action="{{ route('admin.stock-reconciliations.investigations.resolve', $investigation) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Root Cause <span class="text-danger">*</span></label>
                            <select name="root_cause_code" class="form-select" required>
                                <option value="">Select root cause</option>
                                @foreach($rootCauseCodes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('root_cause_code', $investigation->root_cause_code) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Root Cause Details <span class="text-danger">*</span></label>
                            <textarea name="root_cause_details" rows="3" class="form-control" required>{{ old('root_cause_details', $investigation->root_cause_details) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Corrective Action <span class="text-danger">*</span></label>
                            <textarea name="corrective_action" rows="3" class="form-control" required>{{ old('corrective_action', $investigation->corrective_action) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Resolution Notes <span class="text-danger">*</span></label>
                            <textarea name="resolution_notes" rows="3" class="form-control" required>{{ old('resolution_notes') }}</textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Mark Resolved</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    @else
        <div class="card border-success shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Resolution</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">Root Cause</div><div class="fw-semibold">{{ $rootCauseCodes[$investigation->root_cause_code] ?? $investigation->root_cause_code }}</div></div>
                    <div class="col-md-8"><div class="text-muted small">Root Cause Details</div><div>{{ $investigation->root_cause_details }}</div></div>
                    <div class="col-12"><div class="text-muted small">Corrective Action</div><div>{{ $investigation->corrective_action }}</div></div>
                    <div class="col-12"><div class="text-muted small">Resolution Notes</div><div>{{ $investigation->resolution_notes }}</div></div>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Audit Timeline</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Event</th><th>User</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>
                @forelse($investigation->events as $event)
                    <tr>
                        <td>{{ $event->created_at?->format('d M Y H:i:s') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</span></td>
                        <td>{{ $event->user?->name ?? 'System' }}</td>
                        <td>{{ $event->from_status ?: '—' }} → {{ $event->to_status ?: '—' }}</td>
                        <td>{{ $event->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No investigation events recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
