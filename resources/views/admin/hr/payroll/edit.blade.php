{{-- resources/views/admin/hr/payroll/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Payroll')

@section('css')
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
        .status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge.processed { background: var(--info-bg); color: var(--info); }
        .status-badge.paid { background: var(--success-bg); color: var(--success); }
        .breakdown-summary {
            background: var(--gray-50);
            border-radius: var(--radius-sm);
            padding: 1rem;
        }
        .breakdown-summary .item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        .breakdown-summary .item:last-child {
            border-bottom: none;
        }
        .breakdown-summary .item .label {
            color: var(--gray-500);
        }
        .breakdown-summary .item .value {
            font-weight: 600;
        }
        .breakdown-summary .total {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .employee-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gray-200);
        }
        .employee-avatar-small.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
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
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.payroll') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Payroll #{{ $payroll->id }} - {{ $payroll->payroll_month->format('F Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.hr.payroll.show', $payroll) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view_details') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="alert {{ $payroll->status == 'pending' ? 'alert-warning' : ($payroll->status == 'processed' ? 'alert-info' : 'alert-success') }} mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle fs-4"></i>
                <div>
                    <strong>{{ __('ui.current_status') }}</strong>
                    <span class="status-badge {{ $payroll->status }} ms-2">
                    {{ ucfirst($payroll->status) }}
                </span>
                    @if($payroll->status == 'paid')
                        <span class="text-muted ms-2">(Paid payroll cannot be edited)</span>
                    @else
                        <span class="text-muted ms-2">(You can modify the salary details below)</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="payrollForm" method="POST" action="{{ route('admin.hr.payroll.update', $payroll) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Employee Info -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-person me-1"></i> Employee Information
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if ($payroll->employee->profile_image)
                                <img src="{{ Storage::url($payroll->employee->profile_image) }}" class="employee-avatar-small" style="width: 60px; height: 60px;">
                            @else
                                <div class="employee-avatar-small placeholder" style="width: 60px; height: 60px; font-size: 1.2rem;">
                                    {{ strtoupper(substr($payroll->employee->first_name, 0, 1)) }}{{ strtoupper(substr($payroll->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <h5 class="fw-bold mb-0">{{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</h5>
                                <div class="text-muted">{{ $payroll->employee->employee_id }}</div>
                                <div class="text-muted small">{{ $payroll->employee->department->name ?? 'N/A' }} · {{ $payroll->employee->designation->name ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <input type="hidden" name="employee_id" value="{{ $payroll->employee_id }}">
                    </div>

                    <!-- Salary Details -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-cash-stack me-1"></i> Salary Details
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.basic_salary') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="basic_salary"
                                           id="basicSalary" step="0.01" min="0"
                                           value="{{ old('basic_salary', $payroll->basic_salary) }}"
                                        {{ $payroll->status == 'paid' ? 'readonly' : '' }}>
                                </div>
                                @error('basic_salary')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.allowances') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="allowances"
                                           id="allowances" step="0.01" min="0"
                                           value="{{ old('allowances', $payroll->allowances) }}"
                                        {{ $payroll->status == 'paid' ? 'readonly' : '' }}>
                                </div>
                                @error('allowances')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.deductions') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="deductions"
                                           id="deductions" step="0.01" min="0"
                                           value="{{ old('deductions', $payroll->deductions) }}"
                                        {{ $payroll->status == 'paid' ? 'readonly' : '' }}>
                                </div>
                                @error('deductions')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.net_salary') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $defaultCurrency?->symbol ?? '$' }}</span>
                                    <input type="number" class="form-control" name="net_salary"
                                           id="netSalary" step="0.01" min="0"
                                           value="{{ old('net_salary', $payroll->net_salary) }}"
                                        {{ $payroll->status == 'paid' ? 'readonly' : '' }}>
                                </div>
                                <small class="text-muted">Will be auto-calculated from gross and deductions</small>
                                @error('net_salary')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Auto-calculated:</strong> Gross Salary = Basic + Allowances. Net Salary = Gross - Deductions.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Summary -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Summary
                        </div>

                        <div class="breakdown-summary">
                            <div class="item">
                                <span class="label">{{ __('ui.basic_salary') }}</span>
                                <span class="value" id="summaryBasic">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->basic_salary, 2) }}</span>
                            </div>
                            <div class="item">
                                <span class="label">{{ __('ui.allowances') }}</span>
                                <span class="value" id="summaryAllowances">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->allowances, 2) }}</span>
                            </div>
                            <div class="item">
                                <span class="label">{{ __('ui.gross_salary') }}</span>
                                <span class="value fw-bold" id="summaryGross">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->gross_salary, 2) }}</span>
                            </div>
                            <div class="item">
                                <span class="label text-danger">{{ __('ui.deductions') }}</span>
                                <span class="value text-danger" id="summaryDeductions">-{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->deductions, 2) }}</span>
                            </div>
                            <div class="item" style="border-bottom: 2px solid var(--gray-300); padding-bottom: 0.75rem;">
                                <span class="label fw-bold">{{ __('ui.net_salary') }}</span>
                                <span class="value total" id="summaryNet">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->net_salary, 2) }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="status-badge {{ $payroll->status }}">
                                {{ ucfirst($payroll->status) }}
                            </span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Month:</span>
                                <span class="fw-semibold">{{ $payroll->payroll_month->format('F Y') }}</span>
                            </div>
                        </div>

                        @if($payroll->status != 'paid')
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                    <i class="bi bi-save me-1"></i> Update Payroll
                                </button>
                            </div>
                        @else
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-check-circle me-1"></i>
                                This payroll has been paid and cannot be modified.
                            </div>
                        @endif
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> Changes to payroll will be saved immediately. Please review all amounts before submitting.
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
            // Calculate amounts
            function calculateAmounts() {
                const basic = parseFloat($('#basicSalary').val()) || 0;
                const allowances = parseFloat($('#allowances').val()) || 0;
                const deductions = parseFloat($('#deductions').val()) || 0;
                const gross = basic + allowances;
                const net = gross - deductions;

                // Update summary
                $('#summaryBasic').text('{{ $defaultCurrency?->symbol ?? '$' }}' + basic.toFixed(2));
                $('#summaryAllowances').text('{{ $defaultCurrency?->symbol ?? '$' }}' + allowances.toFixed(2));
                $('#summaryGross').text('{{ $defaultCurrency?->symbol ?? '$' }}' + gross.toFixed(2));
                $('#summaryDeductions').text('-{{ $defaultCurrency?->symbol ?? '$' }}' + deductions.toFixed(2));
                $('#summaryNet').text('{{ $defaultCurrency?->symbol ?? '$' }}' + net.toFixed(2));

                // Update net salary field
                $('#netSalary').val(net.toFixed(2));
            }

            // Event listeners
            @if($payroll->status != 'paid')
            $('#basicSalary, #allowances, #deductions').on('input', calculateAmounts);
            @endif

            // Form submit
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');

                @if($payroll->status == 'paid')
                Swal.fire('Error', 'Paid payroll cannot be edited.', 'error');
                return;
                @endif

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
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
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.hr.payroll.show", $payroll) }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-save me-1"></i> Update Payroll'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update payroll.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Update Payroll'
                        );
                    }
                });
            });
        });
    </script>
@endsection
