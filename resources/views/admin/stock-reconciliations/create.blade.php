@extends('layouts.admin.base')

@section('title', 'New Stock Reconciliation')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="mb-4">
        <a href="{{ route('admin.stock-reconciliations.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Stock Reconciliation
        </a>
        <h1 class="h3 mt-2 mb-1">Start Cycle Count</h1>
        <p class="text-muted">The ERP will freeze the current available quantity of every arrived inventory batch as the counting baseline.</p>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card shadow-sm border-0" style="max-width: 760px;">
        <div class="card-body p-4">
            <div class="alert alert-info">
                <strong>No stock is changed at this stage.</strong> Creating the count only captures a timestamped batch-level snapshot.
                Roll-based paper is counted in kilograms; other materials use their native inventory unit.
            </div>

            <form method="POST" action="{{ route('admin.stock-reconciliations.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="count_date" class="form-label fw-semibold">Count Date</label>
                    <input type="date" id="count_date" name="count_date"
                           value="{{ old('count_date', now()->toDateString()) }}"
                           class="form-control @error('count_date') is-invalid @enderror" required>
                    @error('count_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label for="notes" class="form-label fw-semibold">Notes</label>
                    <textarea id="notes" name="notes" rows="4"
                              class="form-control @error('notes') is-invalid @enderror"
                              placeholder="Example: Weekly warehouse count">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.stock-reconciliations.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-camera me-1"></i> Create Snapshot & Start Count
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
