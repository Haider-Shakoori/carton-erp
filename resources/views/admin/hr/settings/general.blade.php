{{-- resources/views/admin/hr/settings/general.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'HR General Settings')

@section('css')
    <style>
        .settings-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .settings-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .settings-section .section-title i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        .form-help {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-top: 0.25rem;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gray-300);
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background: white;
            transition: .4s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background: var(--primary);
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        .setting-group {
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .setting-group:last-child {
            border-bottom: none;
        }
        .setting-group .setting-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--gray-700);
        }
        .setting-group .setting-description {
            font-size: 0.75rem;
            color: var(--gray-500);
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
                        <i class="bi bi-gear me-2"></i> HR <span class="accent">{{ __('ui.general_settings') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-sliders2 me-1"></i> Configure general human resources settings
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <form id="settingsForm" method="POST" action="{{ route('admin.hr.settings.general.update') }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Company Information -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-building"></i> Company Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.company_name') }}</label>
                                <input type="text" class="form-control" name="company_name"
                                       value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                                       placeholder="Enter company name">
                                <div class="form-help">This will appear on employee documents and reports.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company Code</label>
                                <input type="text" class="form-control" name="company_code"
                                       value="{{ old('company_code', $settings['company_code'] ?? '') }}"
                                       placeholder="Enter company code">
                                <div class="form-help">Unique identifier for your company.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Company Address</label>
                                <textarea class="form-control" name="company_address" rows="2"
                                          placeholder="Enter company address">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.phone') }}</label>
                                <input type="text" class="form-control" name="company_phone"
                                       value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                                       placeholder="Enter phone number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('ui.email') }}</label>
                                <input type="email" class="form-control" name="company_email"
                                       value="{{ old('company_email', $settings['company_email'] ?? '') }}"
                                       placeholder="Enter email address">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Website</label>
                                <input type="url" class="form-control" name="company_website"
                                       value="{{ old('company_website', $settings['company_website'] ?? '') }}"
                                       placeholder="{{ __('ui.enter_website') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Employee Settings -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-people"></i> Employee Settings
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Employee ID Prefix</label>
                                <input type="text" class="form-control" name="employee_id_prefix"
                                       value="{{ old('employee_id_prefix', $settings['employee_id_prefix'] ?? 'EMP-') }}"
                                       placeholder="e.g., EMP-">
                                <div class="form-help">Prefix for auto-generated employee IDs.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Employee ID Length</label>
                                <input type="number" class="form-control" name="employee_id_length"
                                       value="{{ old('employee_id_length', $settings['employee_id_length'] ?? 6) }}"
                                       min="4" max="10" placeholder="6">
                                <div class="form-help">Number of digits for employee ID.</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Auto-Generate Employee ID</div>
                                        <div class="setting-description">Automatically generate employee IDs when creating new employees</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="auto_generate_employee_id" id="auto_generate_employee_id"
                                               value="1" {{ old('auto_generate_employee_id', $settings['auto_generate_employee_id'] ?? false) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="setting-group">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="setting-label">Allow Multiple Employees per Department</div>
                                        <div class="setting-description">Allow assigning multiple employees to the same department</div>
                                    </div>
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="allow_multiple_employees" id="allow_multiple_employees"
                                               value="1" {{ old('allow_multiple_employees', $settings['allow_multiple_employees'] ?? true) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle"></i> Quick Actions
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Settings
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> {{ __('ui.reset') }}
                            </button>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-clock"></i> Quick Links
                        </div>

                        <div class="list-group list-group-flush">
                            <a href="{{ route('admin.hr.settings.attendance') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar-check me-2"></i> {{ __('ui.attendance_settings') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.settings.payroll') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll_settings') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.dashboard') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-speedometer2 me-2"></i> {{ __('ui.hr_dashboard') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> Changes to settings will be applied immediately.
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Reset form to initial values
            window.resetForm = function() {
                Swal.fire({
                    title: 'Reset Settings?',
                    text: 'Are you sure you want to reset to the last saved values?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#6B7280',
                    cancelButtonColor: '#4f46e5',
                    confirmButtonText: 'Yes, reset!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            };

            // Form submit
            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success');
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                        }
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save Settings'
                        );
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save settings.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save Settings'
                        );
                    }
                });
            });
        });
    </script>
@endsection
