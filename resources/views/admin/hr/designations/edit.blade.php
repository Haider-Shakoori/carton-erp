{{-- resources/views/admin/hr/designations/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Designation')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .badge-status {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.designation') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-briefcase me-1"></i> Update designation information
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.designations.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.hr.designations.show', $designation) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> View Designation
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle fs-4"></i>
                <div>
                    <strong>{{ __('ui.current_status') }}</strong>
                    <span class="badge-status {{ $designation->is_active ? 'active' : 'inactive' }} ms-2">
                    {{ $designation->is_active ? 'Active' : 'Inactive' }}
                </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-1"></i> Designation Information
                    </div>
                    <div class="card-body">
                        <form id="designationForm" method="POST" action="{{ route('admin.hr.designations.update', $designation) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">{{ __('ui.designation_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name', $designation->name) }}" required placeholder="{{ __('ui.enter_designation_name') }}">
                                @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('ui.department') }}</label>
                                <select class="form-control select2 @error('department_id') is-invalid @enderror"
                                        name="department_id">
                                    <option value="">{{ __('ui.select_department') }}</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $designation->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('ui.description') }}</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description" rows="4" placeholder="{{ __('ui.designation_description') }}">{{ old('description', $designation->description) }}</textarea>
                                @error('description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ $designation->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2 me-1"></i> Update Designation
                                </button>
                                <a href="{{ route('admin.hr.designations.index') }}" class="btn btn-light">{{ __('ui.cancel') }}</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card-modern">
                    <div class="card-header">
                        <i class="bi bi-info-circle me-1"></i> Designation Info
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">{{ __('ui.created_colon') }}</span>
                                <span>{{ $designation->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                                <span>{{ $designation->updated_at->format('M d, Y h:i A') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">{{ __('ui.department_colon') }}</span>
                                <span class="fw-bold">{{ $designation->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">{{ __('ui.employees_colon') }}</span>
                                <span class="fw-bold">{{ $designation->employees()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select Department...',
                allowClear: true
            });

            $('#designationForm').on('submit', function(e) {
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
                );
            });
        });
    </script>
@endsection
