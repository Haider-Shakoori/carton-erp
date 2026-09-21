@extends('layouts.admin.base')

@section('title', 'Management Escalation')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.management-control.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Management Control
            </a>
            <h1 class="h3 mt-2 mb-1">ESC-{{ str_pad($escalation->id, 6, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-muted mb-0">{{ $escalation->title }}</p>
        </div>
        <span class="badge bg-{{ $escalation->level === 3 ? 'danger' : ($escalation->level === 2 ? 'warning' : 'secondary') }} fs-6">
            Level {{ $escalation->level }}
        </span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Management Signal</strong></div>
                <div class="card-body">
                    <p>{{ $escalation->message }}</p>
                    <div class="row g-3">
                        <div class="col-md-4"><div class="text-muted small">Material</div><div class="fw-semibold">{{ $escalation->product?->name ?? '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Root Cause</div><div>{{ $escalation->root_cause_code ? str_replace('_', ' ', ucfirst($escalation->root_cause_code)) : '—' }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Occurrences</div><div>{{ $escalation->occurrences }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Severity</div><div>{{ ucfirst($escalation->severity) }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Absolute Value</div><div>&#36;{{ number_format((float) $escalation->absolute_value_usd, 2) }}</div></div>
                        <div class="col-md-4"><div class="text-muted small">Last Detected</div><div>{{ $escalation->last_detected_at?->format('d M Y H:i') }}</div></div>
                    </div>

                    @if($escalation->investigation)
                        <div class="mt-3">
                            <a href="{{ route('admin.stock-reconciliations.investigations.show', $escalation->investigation) }}" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-search me-1"></i> Open Source Investigation
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Control Status</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Status</dt><dd class="col-7">{{ ucfirst($escalation->status) }}</dd>
                        <dt class="col-5">Owner</dt><dd class="col-7">{{ $escalation->manager?->name ?? 'Unassigned' }}</dd>
                        <dt class="col-5">Due</dt><dd class="col-7">{{ $escalation->review_due_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-5">Overdue</dt><dd class="col-7">{{ $escalation->is_overdue ? 'Yes' : 'No' }}</dd>
                        <dt class="col-5">Opened</dt><dd class="col-7">{{ $escalation->opened_at?->format('d M Y H:i') }}</dd>
                        <dt class="col-5">Acknowledged</dt><dd class="col-7">{{ $escalation->acknowledged_at?->format('d M Y H:i') ?? '—' }}</dd>
                        <dt class="col-5">Closed</dt><dd class="col-7">{{ $escalation->closed_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if($escalation->status !== 'closed')
        @can('escalate stock reconciliations')
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><strong>Ownership & Review Deadline</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.escalations.assign', $escalation) }}">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Responsible Manager</label>
                            <select name="escalated_to" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('escalated_to', $escalation->escalated_to) === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Review Due</label>
                            <input type="date" name="review_due_date" class="form-control" value="{{ old('review_due_date', $escalation->review_due_date?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Audit Note</label>
                            <input type="text" name="notes" class="form-control" maxlength="5000">
                        </div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Save</button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Acknowledge</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.escalations.acknowledge', $escalation) }}">
                            @csrf
                            <textarea name="notes" rows="3" class="form-control mb-3" placeholder="Management acknowledgement note"></textarea>
                            <button class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Acknowledge Escalation</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-header bg-white"><strong>Close Management Escalation</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.escalations.close', $escalation) }}">
                            @csrf
                            <textarea name="resolution_notes" rows="3" class="form-control mb-3" required placeholder="Management decision / closure rationale"></textarea>
                            <button class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Close Escalation</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    @else
        <div class="alert alert-success">
            <strong>Closed:</strong> {{ $escalation->resolution_notes }}
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong>Escalation Audit Timeline</strong></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Event</th><th>User</th><th>Status</th><th>Notes</th></tr></thead>
                <tbody>
                @forelse($escalation->events as $event)
                    <tr>
                        <td>{{ $event->created_at?->format('d M Y H:i:s') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</td>
                        <td>{{ $event->user?->name ?? 'System / Scheduler' }}</td>
                        <td>{{ $event->from_status ?: '—' }} → {{ $event->to_status ?: '—' }}</td>
                        <td>{{ $event->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No escalation events recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
