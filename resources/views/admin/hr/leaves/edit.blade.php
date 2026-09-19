{{-- resources/views/admin/hr/leaves/edit.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Edit Leave Request')

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
        .status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge.approved { background: var(--success-bg); color: var(--success); }
        .status-badge.rejected { background: var(--danger-bg); color: var(--danger); }
        .leave-type-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .leave-type-badge.annual { background: #dbeafe; color: #2563eb; }
        .leave-type-badge.sick { background: #fce4ec; color: #dc2626; }
        .leave-type-badge.casual { background: #fef3c7; color: #d97706; }
        .leave-type-badge.maternity { background: #f3e8ff; color: #7c3aed; }
        .leave-type-badge.paternity { background: #d1fae5; color: #059669; }
        .leave-type-badge.other { background: #f3f4f6; color: #6b7280; }

        .day-calculator {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
        }
        .day-calculator .result {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .readonly-field {
            background: var(--gray-50);
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
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.leave_request') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Request #{{ $leave->id }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.leaves.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                    <a href="{{ route('admin.hr.leaves.show', $leave) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view_details') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="alert {{ $leave->status == 'pending' ? 'alert-warning' : ($leave->status == 'approved' ? 'alert-success' : 'alert-danger') }} mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle fs-4"></i>
                <div>
                    <strong>{{ __('ui.current_status') }}</strong>
                    <span class="status-badge {{ $leave->status }} ms-2">
                    {{ ucfirst($leave->status) }}
                </span>
                    @if($leave->status == 'approved')
                        <span class="text-muted ms-2">(Approved requests cannot be edited)</span>
                    @elseif($leave->status == 'rejected')
                        <span class="text-muted ms-2">(Rejected requests can be edited and resubmitted)</span>
                    @else
                        <span class="text-muted ms-2">(Pending requests can be edited)</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Form -->
        <form id="leaveForm" method="POST" action="{{ route('admin.hr.leaves.update', $leave) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Main Form -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-info-circle me-1"></i> Leave Information
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.employee') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2-employee" name="employee_id" required
                                    {{ $leave->status == 'approved' ? 'disabled' : '' }}>
                                    <option value="">{{ __('ui.select_employee') }}</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}"
                                            {{ old('employee_id', $leave->employee_id) == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->employee_id }} - {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($leave->status == 'approved')
                                    <input type="hidden" name="employee_id" value="{{ $leave->employee_id }}">
                                    <small class="text-muted">Employee cannot be changed for approved requests.</small>
                                @endif
                                @error('employee_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.leave_type') }} <span class="text-danger">*</span></label>
                                <select class="form-select" name="leave_type_id" id="leaveType" required
                                    {{ $leave->status == 'approved' ? 'disabled' : '' }}>
                                    <option value="">{{ __('ui.select_leave_type') }}</option>
                                    @foreach($leaveTypes as $type)
                                        <option value="{{ $type->id }}"
                                            {{ old('leave_type_id', $leave->leave_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }} ({{ $type->days_allowed }} days)
                                        </option>
                                    @endforeach
                                </select>
                                @if($leave->status == 'approved')
                                    <input type="hidden" name="leave_type_id" value="{{ $leave->leave_type_id }}">
                                @endif
                                @error('leave_type_id')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.start_date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" id="startDate"
                                       value="{{ old('start_date', $leave->start_date->format('Y-m-d')) }}" required
                                    {{ $leave->status == 'approved' ? 'readonly' : '' }}>
                                @error('start_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ __('ui.end_date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="end_date" id="endDate"
                                       value="{{ old('end_date', $leave->end_date->format('Y-m-d')) }}" required
                                    {{ $leave->status == 'approved' ? 'readonly' : '' }}>
                                @error('end_date')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('ui.reason') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="reason" rows="3"
                                          placeholder="{{ __('ui.leave_reason_placeholder') }}"
                                          {{ $leave->status == 'approved' ? 'readonly' : '' }}
                                          required>{{ old('reason', $leave->reason) }}</textarea>
                                @error('reason')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
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

                        <div id="summaryContent">
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.employee_colon') }}</span>
                                <span class="fw-semibold" id="summaryEmployee">
                                {{ $leave->employee->first_name }} {{ $leave->employee->last_name }}
                            </span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.leave_type_colon') }}</span>
                                <span class="fw-semibold" id="summaryType">
                                <span class="leave-type-badge {{ strtolower($leave->leaveType->name) }}">
                                    {{ $leave->leaveType->name }}
                                </span>
                            </span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.start_date_colon') }}</span>
                                <span class="fw-semibold" id="summaryStart">
                                {{ $leave->start_date->format('M d, Y') }}
                            </span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.end_date_colon') }}</span>
                                <span class="fw-semibold" id="summaryEnd">
                                {{ $leave->end_date->format('M d, Y') }}
                            </span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Total Days:</span>
                                <span class="fw-bold text-primary" id="summaryDays">{{ $leave->days }}</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">{{ __('ui.status_colon') }}</span>
                                <span class="status-badge {{ $leave->status }}">
                                {{ ucfirst($leave->status) }}
                            </span>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100" id="submitBtn"
                                {{ $leave->status == 'approved' ? 'disabled' : '' }}>
                                <i class="bi bi-save me-1"></i> Update Request
                            </button>
                            @if($leave->status == 'approved')
                                <small class="text-muted d-block text-center mt-1">Approved requests cannot be edited</small>
                            @endif
                        </div>
                    </div>

                    <!-- Day Calculator -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="bi bi-calculator me-1"></i> Day Calculator
                        </div>
                        <div class="day-calculator text-center">
                            <div class="result" id="calculatedDays">{{ $leave->days }}</div>
                            <div class="text-muted small">{{ __('ui.total_working_days') }}</div>
                            <div class="mt-2">
                                <span class="badge bg-secondary">{{ __('ui.excludes_weekends') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>{{ __('ui.note_colon') }}</strong> Changes will update the leave request. If the request was already approved, you cannot make changes.
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

            // Calculate working days (excluding weekends)
            function calculateWorkingDays(startDate, endDate) {
                if (!startDate || !endDate) return 0;

                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T00:00:00');

                if (start > end) return 0;

                let count = 0;
                const current = new Date(start);

                while (current <= end) {
                    const dayOfWeek = current.getDay();
                    if (dayOfWeek !== 6 && dayOfWeek !== 0) {
                        count++;
                    }
                    current.setDate(current.getDate() + 1);
                }

                return count;
            }

            // Format date for display
            function formatDate(dateStr) {
                if (!dateStr) return 'Not selected';
                const date = new Date(dateStr + 'T00:00:00');
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            // Update summary
            function updateSummary() {
                const employeeText = $('.select2-employee').find('option:selected').text();
                const leaveTypeText = $('#leaveType').find('option:selected').text();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                if (employeeText) $('#summaryEmployee').text(employeeText);
                if (leaveTypeText) {
                    const type = leaveTypeText.split(' ')[0];
                    const badgeClass = type.toLowerCase();
                    $('#summaryType').html(`<span class="leave-type-badge ${badgeClass}">${leaveTypeText}</span>`);
                }
                if (startDate) $('#summaryStart').text(formatDate(startDate));
                if (endDate) $('#summaryEnd').text(formatDate(endDate));

                const days = calculateWorkingDays(startDate, endDate);
                $('#summaryDays').text(days);
                $('#calculatedDays').text(days);
            }

            // Event listeners (only if not approved)
            @if($leave->status != 'approved')
            $('.select2-employee').on('change', updateSummary);
            $('#leaveType').on('change', updateSummary);
            $('#startDate').on('change', updateSummary);
            $('#endDate').on('change', updateSummary);
            @endif

            // Validate dates
            $('#startDate, #endDate').on('change', function() {
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                if (startDate && endDate && new Date(startDate + 'T00:00:00') > new Date(endDate + 'T00:00:00')) {
                    Swal.fire({
                        title: 'Invalid Date Range',
                        text: 'Start date cannot be after end date.',
                        icon: 'warning',
                        confirmButtonColor: '#4f46e5'
                    });
                    $(this).val('');
                    updateSummary();
                }
            });

            // Form submit
            $('#leaveForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');

                @if($leave->status == 'approved')
                Swal.fire('Error', 'Approved requests cannot be edited.', 'error');
                return;
                @endif

                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();

                if (new Date(startDate + 'T00:00:00') > new Date(endDate + 'T00:00:00')) {
                    Swal.fire('Error', 'Start date cannot be after end date.', 'error');
                    return;
                }

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
                                window.location.href = '{{ route("admin.hr.leaves.show", $leave) }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-save me-1"></i> Update Request'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update leave request.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Update Request'
                        );
                    }
                });
            });
        });
    </script>
@endsection
