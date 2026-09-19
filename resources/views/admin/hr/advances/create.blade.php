{{-- resources/views/admin/hr/advances/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Create Advance / Loan')

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
        .advance-type-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .advance-type-badge.advance { background: var(--primary-bg); color: var(--primary); }
        .advance-type-badge.loan { background: var(--warning-bg); color: var(--warning); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-plus-circle me-2"></i> {{ __('ui.create') }} <span class="accent">{{ __('ui.advance_loan') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-coin me-1"></i> Create a new advance or loan request for an employee
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.advances.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="advanceForm" method="POST" action="{{ route('admin.hr.advances.store') }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Main Form -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Advance Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.employee') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-employee" name="employee_id" required>
                                    <option value="">{{ __('ui.select_employee') }}</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->employee_id }} - {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.type') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="type" id="advanceType" required>
                                    <option value="">{{ __('ui.select_type') }}</option>
                                    <option value="advance" {{ old('type') == 'advance' ? 'selected' : '' }}>{{ __('ui.advance') }}</option>
                                    <option value="loan" {{ old('type') == 'loan' ? 'selected' : '' }}>Loan</option>
                                </select>
                                @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="mt-1">
                                    <span class="advance-type-badge advance" id="typeBadgeAdvance" style="display: none;">{{ __('ui.advance') }}</span>
                                    <span class="advance-type-badge loan" id="typeBadgeLoan" style="display: none;">Loan</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="amount" id="amount"
                                           step="0.01" min="1" value="{{ old('amount') }}" required
                                           placeholder="{{ __('ui.enter_amount') }}">
                                </div>
                                @error('amount')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.request_date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="request_date"
                                       value="{{ old('request_date', date('Y-m-d')) }}" required>
                                @error('request_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.reason') }}</label>
                                <textarea class="form-control" name="reason" rows="3"
                                          placeholder="{{ __('ui.advance_reason_placeholder') }}">{{ old('reason') }}</textarea>
                                @error('reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Deduction Settings -->
                    <div class="form-section" id="deductionSection">
                        <div class="section-title">
                            <i class="bi bi-calculator me-1"></i> Deduction Settings
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Set up deduction details for this advance/loan. If left empty, deductions will not be scheduled automatically.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.deduction_start_date') }}</label>
                                <input type="date" class="form-control" name="deduction_start_date"
                                       value="{{ old('deduction_start_date') }}">
                                @error('deduction_start_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.deduction_end_date') }}</label>
                                <input type="date" class="form-control" name="deduction_end_date"
                                       value="{{ old('deduction_end_date') }}">
                                @error('deduction_end_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.deduction_amount_period') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" name="deduction_amount"
                                           step="0.01" min="0" value="{{ old('deduction_amount') }}"
                                           placeholder="{{ __('ui.leave_empty_full_amount') }}">
                                </div>
                                <small class="text-muted">Leave empty to deduct the full remaining amount at once.</small>
                                @error('deduction_amount')
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
                                <span class="text-muted">{{ __('ui.employee_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmployee">{{ __('ui.not_selected') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Type:</span>
                                <span class="fw-semibold" id="summaryType">{{ __('ui.not_selected') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.amount_colon') }}</span>
                                <span class="fw-semibold" id="summaryAmount">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.request_date_colon') }}</span>
                                <span class="fw-semibold" id="summaryDate">{{ __('ui.not_selected') }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="badge bg-warning">{{ __('ui.pending') }}</span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                <i class="bi bi-check2 me-1"></i> Create Advance / Loan
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> This request will be submitted as pending and will require approval before it becomes active.
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
            $('.select2-employee').select2({
                width: '100%',
                placeholder: 'Search employee...',
                allowClear: true
            });

            // Type change handler
            $('#advanceType').on('change', function() {
                const type = $(this).val();
                $('#typeBadgeAdvance').hide();
                $('#typeBadgeLoan').hide();

                if (type === 'advance') {
                    $('#typeBadgeAdvance').show();
                    $('#summaryType').text('Advance');
                } else if (type === 'loan') {
                    $('#typeBadgeLoan').show();
                    $('#summaryType').text('Loan');
                } else {
                    $('#summaryType').text('Not selected');
                }
            });

            // Employee selection handler
            $('.select2-employee').on('change', function() {
                const selected = $(this).find('option:selected');
                if (selected.val()) {
                    $('#summaryEmployee').text(selected.text());
                } else {
                    $('#summaryEmployee').text('Not selected');
                }
            });

            // Amount change handler
            $('#amount').on('input', function() {
                const amount = parseFloat($(this).val()) || 0;
                $('#summaryAmount').text('$' + amount.toFixed(2));
            });

            // Request date change handler
            $('input[name="request_date"]').on('change', function() {
                const date = $(this).val();
                if (date) {
                    const formatted = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    $('#summaryDate').text(formatted);
                } else {
                    $('#summaryDate').text('Not selected');
                }
            });

            // Form submit
            $('#advanceForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Creating...'
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
                                window.location.href = '{{ route("admin.hr.advances.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-check2 me-1"></i> Create Advance / Loan'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create advance/loan.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-check2 me-1"></i> Create Advance / Loan'
                        );
                    }
                });
            });

            // Trigger initial summary updates
            if ($('#advanceType').val()) {
                $('#advanceType').trigger('change');
            }
            if ($('#amount').val()) {
                $('#amount').trigger('input');
            }
            if ($('input[name="request_date"]').val()) {
                $('input[name="request_date"]').trigger('change');
            }
        });
    </script>
@endsection
