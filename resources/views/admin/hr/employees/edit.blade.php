{{-- resources/views/admin/hr/employees/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Employee')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .form-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .image-upload-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--gray-50);
            padding: 0.5rem;
            border-radius: var(--radius-xs);
            border: 1.5px dashed var(--gray-300);
        }
        .image-upload-wrapper .form-control {
            border: none !important;
            background: transparent !important;
            padding: 0.25rem 0 !important;
        }
        .employee-avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gray-200);
        }
        .employee-avatar-preview.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
        }
        .current-image-thumb {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
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
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.employee') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> {{ $employee->employee_id }} - {{ $employee->first_name }} {{ $employee->last_name }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> View Profile
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
                    <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }} ms-2">
                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                </span>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="employeeForm" method="POST" action="{{ route('admin.hr.employees.update', $employee) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-person me-1"></i> Personal Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.first_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required>
                                @error('first_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.last_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required>
                                @error('last_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="{{ old('email', $employee->email) }}" required>
                                @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone', $employee->phone) }}">
                                @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.date_of_birth') }}</label>
                                <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                                @error('date_of_birth')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.hire_date') }}</label>
                                <input type="date" class="form-control" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}">
                                @error('hire_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" rows="2">{{ old('address', $employee->address) }}</textarea>
                                @error('address')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Employment Information -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-briefcase me-1"></i> Employment Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.department') }}</label>
                                <select class="form-select select2-department" name="department_id">
                                    <option value="">{{ __('ui.select_department') }}</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id', $employee->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.designation') }}</label>
                                <select class="form-select select2-designation" name="designation_id">
                                    <option value="">{{ __('ui.select_designation') }}</option>
                                    @foreach($designations as $designation)
                                        <option value="{{ $designation->id }}" {{ old('designation_id', $employee->designation_id) == $designation->id ? 'selected' : '' }}>
                                            {{ $designation->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('designation_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.employee_id') }}</label>
                                <input type="text" class="form-control" value="{{ $employee->employee_id }}" disabled readonly>
                                <small class="text-muted">Employee ID cannot be changed</small>
                                <input type="hidden" name="employee_id" value="{{ $employee->employee_id }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.basic_salary') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="basic_salary"
                                           step="0.01" min="0" value="{{ old('basic_salary', $employee->basic_salary) }}"
                                           placeholder="0.00">
                                </div>
                                <small class="text-muted">{{ $defaultCurrency?->name ?? 'Default currency' }}</small>
                                @error('basic_salary')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Profile Image -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-image me-1"></i> Profile Image
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="image-upload-wrapper">
                                    <input type="file" class="form-control form-control-sm" name="profile_image" id="profile_image" accept="image/*">
                                    <div id="currentImageContainer" class="{{ $employee->profile_image ? '' : 'd-none' }}">
                                        <img id="currentImage" class="current-image-thumb" src="{{ $employee->profile_image ? Storage::url($employee->profile_image) : '#' }}">
                                        <small class="text-muted">{{ __('ui.current') }}</small>
                                    </div>
                                    <div id="imagePreviewWrapper" class="d-none">
                                        <img id="imagePreview" class="employee-avatar-preview" src="#">
                                    </div>
                                </div>
                                <small class="text-muted">Allowed: JPG, PNG, WebP. Max: 2MB. Leave empty to keep current image.</small>
                                @error('profile_image')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Summary
                        </div>

                        <div id="summaryContent">
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.name_colon') }}</span>
                                <span class="fw-semibold" id="summaryName">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.email_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmail">{{ $employee->email }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.department_colon') }}</span>
                                <span class="fw-semibold" id="summaryDepartment">{{ $employee->department->name ?? 'Not set' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.designation_colon') }}</span>
                                <span class="fw-semibold" id="summaryDesignation">{{ $employee->designation->name ?? 'Not set' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.salary_colon') }}</span>
                                <span class="fw-semibold" id="summarySalary">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($employee->basic_salary ?? 0, 2) }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                                {{ $employee->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" {{ $employee->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Update Employee
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> {{ __('ui.required_fields_notice') }} <span class="text-danger">*</span> are required.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2-department, .select2-designation').select2({
                width: '100%',
                placeholder: 'Search...',
                allowClear: true
            });

            // Image Preview
            $('#profile_image').on('change', function(e) {
                const wrapper = $('#imagePreviewWrapper');
                const currentContainer = $('#currentImageContainer');
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#imagePreview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                        currentContainer.addClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
                    if ($('#currentImage').attr('src') && $('#currentImage').attr('src') !== '#') {
                        currentContainer.removeClass('d-none');
                    }
                }
            });

            // Summary Updates
            $('input[name="first_name"], input[name="last_name"]').on('input', function() {
                const firstName = $('input[name="first_name"]').val() || '';
                const lastName = $('input[name="last_name"]').val() || '';
                $('#summaryName').text(firstName + ' ' + lastName || 'Not set');
            });

            $('input[name="email"]').on('input', function() {
                $('#summaryEmail').text($(this).val() || 'Not set');
            });

            $('.select2-department').on('change', function() {
                const text = $(this).find('option:selected').text();
                $('#summaryDepartment').text(text || 'Not set');
            });

            $('.select2-designation').on('change', function() {
                const text = $(this).find('option:selected').text();
                $('#summaryDesignation').text(text || 'Not set');
            });

            $('input[name="basic_salary"]').on('input', function() {
                const salary = parseFloat($(this).val()) || 0;
                $('#summarySalary').text('{{ $defaultCurrency?->symbol ?? '$' }}' + salary.toFixed(2));
            });

            // Status toggle update
            $('#is_active').on('change', function() {
                const isActive = $(this).is(':checked');
                $('.badge-status').removeClass('active inactive')
                    .addClass(isActive ? 'active' : 'inactive')
                    .text(isActive ? 'Active' : 'Inactive');
            });

            // Form submit
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');
                const formData = new FormData(this);

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.hr.employees.show", $employee) }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Update Employee'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update employee.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Update Employee'
                        );
                    }
                });
            });
        });
    </script>
@endsection
