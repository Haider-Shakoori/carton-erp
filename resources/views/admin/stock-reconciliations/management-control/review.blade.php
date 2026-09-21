@extends('layouts.admin.base')

@section('title', 'Weekly Stock Control Review')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="{{ route('admin.stock-reconciliations.management-control.index') }}" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Stock Management Control
            </a>
            <h1 class="h3 mt-2 mb-1">Weekly Control Review</h1>
            <p class="text-muted mb-0">{{ $review->week_start->format('d M Y') }} – {{ $review->week_end->format('d M Y') }}</p>
        </div>
        <span class="badge bg-{{ $review->status === 'completed' ? 'success' : 'warning' }} fs-6">{{ ucfirst($review->status) }}</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @php $snapshot = $review->summary_snapshot ?? []; @endphp
    <div class="row g-3 mb-4">
        @foreach([
            ['Active Escalations', $snapshot['active_escalations'] ?? 0],
            ['Level 3', $snapshot['level_3'] ?? 0],
            ['Level 2', $snapshot['level_2'] ?? 0],
            ['Unassigned', $snapshot['unassigned'] ?? 0],
            ['Overdue', $snapshot['overdue'] ?? 0],
        ] as [$label, $value])
            <div class="col-lg col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
                <div class="text-muted small">{{ $label }}</div><div class="fs-4 fw-bold">{{ $value }}</div>
            </div></div></div>
        @endforeach
        <div class="col-lg col-md-4"><div class="card border-0 shadow-sm"><div class="card-body">
            <div class="text-muted small">Variance Value</div><div class="fs-5 fw-bold">&#36;{{ number_format($snapshot['absolute_value_usd'] ?? 0, 2) }}</div>
        </div></div></div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Review Details</strong></div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4"><div class="text-muted small">Owner</div><div>{{ $review->owner?->name ?? 'Unassigned' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Due Date</div><div>{{ $review->due_date->format('d M Y') }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Generated</div><div>{{ $review->generated_at?->format('d M Y H:i') }}</div></div>
            </div>

            @if($review->status !== 'completed')
                @can('review stock reconciliations')
                <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.reviews.update', $review) }}">
                    @csrf
                    @method('PATCH')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Review Owner</label>
                            <select name="owner_id" class="form-select">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('owner_id', $review->owner_id) === (string) $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Working Review Notes</label>
                            <textarea name="review_notes" rows="3" class="form-control">{{ old('review_notes', $review->review_notes) }}</textarea>
                        </div>
                        <div class="col-12 text-end"><button class="btn btn-primary">Save Review</button></div>
                    </div>
                </form>
                @endcan
            @else
                <div class="row g-3">
                    <div class="col-12"><div class="text-muted small">Review Notes</div><div>{{ $review->review_notes }}</div></div>
                    <div class="col-12"><div class="text-muted small">Management Decisions</div><div>{{ $review->decisions }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Completed By</div><div>{{ $review->completer?->name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Completed At</div><div>{{ $review->completed_at?->format('d M Y H:i') }}</div></div>
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><strong>Escalations Included in This Review</strong></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Level @ Review</th><th>Signal</th><th>Material</th><th>Status @ Review</th><th>Current Owner</th><th></th></tr></thead>
                <tbody>
                @forelse($review->items as $item)
                    <tr>
                        <td><span class="badge bg-{{ $item->level_snapshot === 3 ? 'danger' : ($item->level_snapshot === 2 ? 'warning' : 'secondary') }}">L{{ $item->level_snapshot }}</span></td>
                        <td>{{ $item->escalation?->title ?? 'Deleted escalation' }}</td>
                        <td>{{ $item->escalation?->product?->name ?? '—' }}</td>
                        <td>{{ ucfirst($item->status_snapshot) }}</td>
                        <td>{{ $item->escalation?->manager?->name ?? 'Unassigned' }}</td>
                        <td class="text-end">
                            @if($item->escalation)
                                <a href="{{ route('admin.stock-reconciliations.management-control.escalations.show', $item->escalation) }}" class="btn btn-sm btn-outline-secondary">Open</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">No active escalations were captured for this review.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($review->status !== 'completed')
        @can('review stock reconciliations')
        <div class="card border-success shadow-sm">
            <div class="card-header bg-white"><strong>Complete Weekly Review</strong></div>
            <div class="card-body">
                <div class="alert alert-info small">
                    Completing the review freezes its weekly management notes, decisions and summary. Later escalation changes do not rewrite this completed review record.
                </div>
                <form method="POST" action="{{ route('admin.stock-reconciliations.management-control.reviews.complete', $review) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Final Review Notes</label>
                            <textarea name="review_notes" rows="4" class="form-control" required>{{ old('review_notes', $review->review_notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Management Decisions / Actions</label>
                            <textarea name="decisions" rows="4" class="form-control" required>{{ old('decisions', $review->decisions) }}</textarea>
                        </div>
                        <div class="col-12 text-end"><button class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Complete Weekly Review</button></div>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    @endif
</div>
@endsection
