{{-- resources/views/admin/hr/designations/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Create Designation')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-briefcase me-2"></i> {{ __('ui.create') }} <span class="accent">{{ __('ui.designation') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-plus-circle me-1"></i> Add a new job designation
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.designations.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
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
                        <form id="designationForm" method="POST" action="{{ route('admin.hr.designations.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">{{ __('ui.designation_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       name="name" value="{{ old('name') }}" required placeholder="{{ __('ui.enter_designation_name') }}">
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
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                                          name="description" rows="4" placeholder="{{ __('ui.designation_description') }}">{{ old('description') }}</textarea>
                                @error('description')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2 me-1"></i> Create Designation
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
                        <i class="bi bi-info-circle me-1"></i> Information
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-lightbulb me-1"></i>
                            <strong>Tip:</strong> Designations define job roles and positions within the organization. Each designation can be assigned to a department.
                        </div>
                        <ul class="list-unstyled">
                            <li class="py-1"><i class="bi bi-check-circle text-success me-1"></i> Create designations for each job role</li>
                            <li class="py-1"><i class="bi bi-check-circle text-success me-1"></i> Assign designations to employees</li>
                            <li class="py-1"><i class="bi bi-check-circle text-success me-1"></i> Link designations to departments</li>
                        </ul>
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
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
                );
            });
        });
    </script>
@endsection
