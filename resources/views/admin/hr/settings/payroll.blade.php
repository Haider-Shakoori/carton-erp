{{-- resources/views/admin/hr/settings/payroll.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.payroll_settings'))

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
        .allowance-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            margin-bottom: 0.75rem;
        }
        .allowance-card .allowance-name {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .allowance-card .allowance-desc {
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
                        <i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll') }} <span class="accent">{{ __('ui.settings_label') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-sliders2 me-1"></i> Configure payroll processing settings
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <form id="settingsForm" method="POST" action="{{ route('admin.hr.settings.payroll.update') }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Payroll Configuration -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-cash"></i> Payroll Configuration
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Payroll Cycle</label>
                                <select class="form-select" name="payroll_cycle">
                                    <option value="monthly" {{ old('payroll_cycle', $settings['payroll_cycle'] ?? 'monthly') == 'monthly' ? 'selected' : '' }}>{{ __('ui.monthly') }}</option>
                                    <option value="biweekly" {{ old('payroll_cycle', $settings['payroll_cycle'] ?? 'monthly') == 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                                    <option value="weekly" {{ old('payroll_cycle', $settings['payroll_cycle'] ?? 'monthly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    <option value="semimonthly" {{ old('payroll_cycle', $settings['payroll_cycle'] ?? 'monthly') == 'semimonthly' ? 'selected' : '' }}>Semi-Monthly</option>
                                </select>
                                <div class="form-help">How often payroll is processed.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method">
                                    <option value="bank_transfer" {{ old('payment_method', $settings['payment_method'] ?? 'bank_transfer') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cash" {{ old('payment_method', $settings['payment_method'] ?? 'bank_transfer') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="cheque" {{ old('payment_method', $settings['payment_method'] ?? 'bank_transfer') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="mobile_money" {{ old('payment_method', $settings['payment_method'] ?? 'bank_transfer') == 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                                </select>
                                <div class="form-help">Default payment method for payroll.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payroll Processing Date</label>
                                <select class="form-select" name="payroll_processing_date">
                                    <option value="1" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 1 ? 'selected' : '' }}>1st of Month</option>
                                    <option value="5" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 5 ? 'selected' : '' }}>5th of Month</option>
                                    <option value="10" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 10 ? 'selected' : '' }}>10th of Month</option>
                                    <option value="15" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 15 ? 'selected' : '' }}>15th of Month</option>
                                    <option value="20" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 20 ? 'selected' : '' }}>20th of Month</option>
                                    <option value="25" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 25 ? 'selected' : '' }}>25th of Month</option>
                                    <option value="last" {{ old('payroll_processing_date', $settings['payroll_processing_date'] ?? 25) == 'last' ? 'selected' : '' }}>Last Day of Month</option>
                                </select>
                                <div class="form-help">When payroll is typically processed.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payroll Currency</label>
                                <select class="form-select" name="payroll_currency">
                                    @foreach($currencies ?? [] as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ old('payroll_currency', $settings['payroll_currency'] ?? ($defaultCurrency?->id ?? '')) == $currency->id ? 'selected' : '' }}>
                                            {{ $currency->code }} - {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-help">Default currency for payroll.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Allowances Configuration -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-plus-circle"></i> Allowances Configuration
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Housing Allowance (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="housing_allowance_percent"
                                           value="{{ old('housing_allowance_percent', $settings['housing_allowance_percent'] ?? 20) }}"
                                           min="0" max="100" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-help">Percentage of basic salary for housing allowance.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transport Allowance (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="transport_allowance_percent"
                                           value="{{ old('transport_allowance_percent', $settings['transport_allowance_percent'] ?? 10) }}"
                                           min="0" max="100" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-help">Percentage of basic salary for transport allowance.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Medical Allowance (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="medical_allowance_percent"
                                           value="{{ old('medical_allowance_percent', $settings['medical_allowance_percent'] ?? 5) }}"
                                           min="0" max="100" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-help">Percentage of basic salary for medical allowance.</div>
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Allowances are calculated as a percentage of the employee's basic salary.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Configuration -->
                    <div class="settings-section">
                        <div class="section-title">
                            <i class="bi bi-dash-circle"></i> Deductions Configuration
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Tax Deduction (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="tax_deduction_percent"
                                           value="{{ old('tax_deduction_percent', $settings['tax_deduction_percent'] ?? 10) }}"
                                           min="0" max="50" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-help">Percentage of gross salary for tax deduction.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Social Security (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="social_security_percent"
                                           value="{{ old('social_security_percent', $settings['social_security_percent'] ?? 5) }}"
                                           min="0" max="20" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-help">Percentage of gross salary for social security.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Maximum Deduction Limit</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="max_deduction_limit"
                                           value="{{ old('max_deduction_limit', $settings['max_deduction_limit'] ?? 50) }}"
                                           min="0" max="100" step="0.5">
                                </div>
                                <div class="form-help">Maximum percentage of salary that can be deducted.</div>
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
                            <a href="{{ route('admin.hr.settings.general') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-gear me-2"></i> {{ __('ui.general_settings_label') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.settings.attendance') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calendar-check me-2"></i> {{ __('ui.attendance_settings') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                            <a href="{{ route('admin.hr.payroll.index') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll_list') }}</span>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> Changes to payroll settings will affect future payroll calculations.
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
            // Reset form
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
