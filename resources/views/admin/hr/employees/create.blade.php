{{-- resources/views/admin/hr/employees/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.add_employee'))

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
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-person-plus me-2"></i> Add <span class="accent">{{ __('ui.employee') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-person-badge me-1"></i> Create a new employee record
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.employees.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="employeeForm" method="POST" action="{{ route('admin.hr.employees.store') }}" enctype="multipart/form-data">
            @csrf

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
                                <input type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.last_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.email') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.date_of_birth') }}</label>
                                <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                @error('date_of_birth')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.hire_date') }}</label>
                                <input type="date" class="form-control" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}">
                                @error('hire_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('ui.address') }}</label>
                                <textarea class="form-control" name="address" rows="2">{{ old('address') }}</textarea>
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
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                                        <option value="{{ $designation->id }}" {{ old('designation_id') == $designation->id ? 'selected' : '' }}>
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
                                <input type="text" class="form-control" name="employee_id" value="{{ old('employee_id') }}" placeholder="{{ __('ui.auto_generated_empty') }}">
                                <small class="text-muted">Leave empty to auto-generate</small>
                                @error('employee_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.basic_salary') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="basic_salary"
                                           step="0.01" min="0" value="{{ old('basic_salary') }}"
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
                                    <div id="imagePreviewWrapper" class="d-none">
                                        <img id="imagePreview" class="employee-avatar-preview" src="#">
                                    </div>
                                </div>
                                <small class="text-muted">Allowed: JPG, PNG, WebP. Max: 2MB</small>
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
                                <span class="fw-semibold" id="summaryName">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.email_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmail">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.department_colon') }}</span>
                                <span class="fw-semibold" id="summaryDepartment">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.designation_colon') }}</span>
                                <span class="fw-semibold" id="summaryDesignation">{{ __('ui.not_set') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.salary_colon') }}</span>
                                <span class="fw-semibold" id="summarySalary">{{ $defaultCurrency?->symbol ?? '$' }}0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="badge bg-success">{{ __('ui.active') }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="form-check form-switch mb-3">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="is_active">{{ __('ui.active_status') }}</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Create Employee
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
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        $('#imagePreview').attr('src', evt.target.result);
                        wrapper.removeClass('d-none');
                    }
                    reader.readAsDataURL(e.target.files[0]);
                } else {
                    wrapper.addClass('d-none');
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
                $('.badge-status').removeClass('bg-success bg-danger')
                    .addClass(isActive ? 'bg-success' : 'bg-danger')
                    .text(isActive ? 'Active' : 'Inactive');
            });

            // Form submit
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');
                const formData = new FormData(this);

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
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
                                window.location.href = '{{ route("admin.hr.employees.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Create Employee'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create employee.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Create Employee'
                        );
                    }
                });
            });

            // Trigger initial summary
            $('input[name="first_name"]').trigger('input');
        });
    </script>
@endsection
