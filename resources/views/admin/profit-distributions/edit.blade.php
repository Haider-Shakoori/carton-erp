{{-- resources/views/admin/profit-distributions/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Distribution - ' . $distribution->distribution_number)

@section('css')
    <style>
        .badge-status.draft { background: var(--gray-200); color: var(--gray-600); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil me-2"></i> Edit <span class="accent">Distribution</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $distribution->distribution_number }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.profit-distributions.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-1"></i> Distribution Information
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.profit-distributions.update', $distribution) }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Period Start <span class="text-danger">*</span></label>
                                    <input type="date" name="period_start"
                                           class="form-control @error('period_start') is-invalid @enderror"
                                           value="{{ old('period_start', $distribution->period_start->format('Y-m-d')) }}">
                                    @error('period_start')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Period End <span class="text-danger">*</span></label>
                                    <input type="date" name="period_end"
                                           class="form-control @error('period_end') is-invalid @enderror"
                                           value="{{ old('period_end', $distribution->period_end->format('Y-m-d')) }}">
                                    @error('period_end')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="4" class="form-control">{{ old('notes', $distribution->notes) }}</textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2 me-1"></i> Update Distribution
                                </button>
                                <a href="{{ route('admin.profit-distributions.index') }}" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-modern">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-1"></i> Summary
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Status</span>
                                <span class="badge-status draft">{{ $distribution->status_label }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Total Profit</span>
                                <span class="fw-bold">{{ number_format((float) $distribution->total_profit, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Distributed</span>
                                <span class="fw-bold">{{ number_format((float) $distribution->distributed_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Created</span>
                                <span>{{ $distribution->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection