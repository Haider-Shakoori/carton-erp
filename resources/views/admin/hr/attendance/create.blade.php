{{-- resources/views/admin/hr/attendance/create.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Mark Attendance')

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
        .attendance-status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .attendance-status-badge.present { background: var(--success-bg); color: var(--success); }
        .attendance-status-badge.late { background: var(--warning-bg); color: var(--warning); }
        .attendance-status-badge.absent { background: var(--danger-bg); color: var(--danger); }
        .attendance-status-badge.leave { background: var(--primary-bg); color: var(--primary); }

        .employee-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            transition: var(--transition);
            cursor: pointer;
        }
        .employee-card:hover {
            background: var(--primary-bg);
            border-color: var(--primary);
        }
        .employee-card.selected {
            background: var(--primary-bg);
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }
        .employee-card .employee-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
        }
        .employee-card .employee-detail {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .employee-card .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .employee-card .status-indicator.present { background: var(--success); }
        .employee-card .status-indicator.late { background: var(--warning); }
        .employee-card .status-indicator.absent { background: var(--danger); }
        .employee-card .status-indicator.leave { background: var(--primary); }
        .employee-card .status-indicator.not-marked { background: var(--gray-300); }

        .bulk-actions {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            position: sticky;
            bottom: 0;
            z-index: 10;
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
                        <i class="bi bi-calendar-plus me-2"></i> Mark <span class="accent">{{ __('ui.attendance') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-date me-1"></i> {{ $date->format('l, F j, Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Daily Attendance
                    </a>
                    <a href="{{ route('admin.hr.attendance.monthly') }}" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-month me-1"></i> Monthly View
                    </a>
                </div>
            </div>
        </div>

        <!-- Date Selector -->
        <div class="card-modern mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('ui.date') }}</label>
                        <input type="date" class="form-control" id="attendanceDate" value="{{ $date->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary" id="loadDate">
                            <i class="bi bi-arrow-clockwise me-1"></i> Load Date
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-semibold me-2">Bulk Mark:</span>
                <button class="btn btn-sm btn-success bulk-status" data-status="present">
                    <i class="bi bi-check-circle me-1"></i> {{ __('ui.present') }}
                </button>
                <button class="btn btn-sm btn-warning bulk-status" data-status="late">
                    <i class="bi bi-clock me-1"></i> Late
                </button>
                <button class="btn btn-sm btn-danger bulk-status" data-status="absent">
                    <i class="bi bi-x-circle me-1"></i> {{ __('ui.absent') }}
                </button>
                <button class="btn btn-sm btn-primary bulk-status" data-status="leave">
                    <i class="bi bi-calendar-check me-1"></i> Leave
                </button>
                <span class="text-muted ms-3" style="font-size: 0.8rem;">
                <i class="bi bi-info-circle me-1"></i> Click a status to mark all visible employees
            </span>
            </div>
        </div>

        <!-- Employee List -->
        <form id="attendanceForm" method="POST" action="{{ route('admin.hr.attendance.bulk-store') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">

            <div class="row" id="employeeList">
                @foreach($employees as $employee)
                    @php
                        $existingAttendance = $attendances->where('employee_id', $employee->id)->first();
                        $currentStatus = $existingAttendance ? $existingAttendance->status : null;
                        $checkedIn = $existingAttendance && $existingAttendance->check_in;
                        $checkedOut = $existingAttendance && $existingAttendance->check_out;
                    @endphp
                    <div class="col-12 col-md-6 col-lg-4 employee-item" data-employee-id="{{ $employee->id }}">
                        <div class="employee-card {{ $currentStatus ? 'selected' : '' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="employee-name">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </div>
                                    <div class="employee-detail">
                                        {{ $employee->employee_id }}
                                        @if($employee->department)
                                            · {{ $employee->department->name }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="status-indicator {{ $currentStatus ? $currentStatus : 'not-marked' }}"></div>
                                    @if($currentStatus)
                                        <span class="attendance-status-badge {{ $currentStatus }}" style="font-size: 0.6rem; display: block; margin-top: 0.25rem;">
                                        {{ ucfirst($currentStatus) }}
                                    </span>
                                    @else
                                        <span class="text-muted" style="font-size: 0.6rem; display: block; margin-top: 0.25rem;">Not marked</span>
                                    @endif
                                    @if($checkedIn)
                                        <div class="text-success" style="font-size: 0.6rem;">
                                            <i class="bi bi-box-arrow-in-right"></i> {{ $existingAttendance->check_in->format('h:i A') }}
                                        </div>
                                    @endif
                                    @if($checkedOut)
                                        <div class="text-warning" style="font-size: 0.6rem;">
                                            <i class="bi bi-box-arrow-left"></i> {{ $existingAttendance->check_out->format('h:i A') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Selector -->
                            <div class="mt-2">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendances[{{ $employee->id }}][status]"
                                           id="present_{{ $employee->id }}" value="present"
                                        {{ $currentStatus == 'present' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success" for="present_{{ $employee->id }}">P</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $employee->id }}][status]"
                                           id="late_{{ $employee->id }}" value="late"
                                        {{ $currentStatus == 'late' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-warning" for="late_{{ $employee->id }}">L</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $employee->id }}][status]"
                                           id="absent_{{ $employee->id }}" value="absent"
                                        {{ $currentStatus == 'absent' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger" for="absent_{{ $employee->id }}">A</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $employee->id }}][status]"
                                           id="leave_{{ $employee->id }}" value="leave"
                                        {{ $currentStatus == 'leave' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="leave_{{ $employee->id }}">V</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $employee->id }}][status]"
                                           id="clear_{{ $employee->id }}" value=""
                                        {{ !$currentStatus ? 'checked' : '' }}>
                                    <label class="btn btn-outline-secondary" for="clear_{{ $employee->id }}">✕</label>
                                </div>
                                <input type="hidden" name="attendances[{{ $employee->id }}][employee_id]" value="{{ $employee->id }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Submit -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                    <i class="bi bi-save me-1"></i> Save Attendance
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Date change handler
            $('#loadDate').on('click', function() {
                const date = $('#attendanceDate').val();
                if (date) {
                    window.location.href = window.location.pathname + '?date=' + date;
                }
            });

            // Enter key on date input
            $('#attendanceDate').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#loadDate').click();
                }
            });

            // Bulk status application
            $('.bulk-status').on('click', function() {
                const status = $(this).data('status');
                const employeeItems = $('.employee-item:visible');

                if (employeeItems.length === 0) {
                    Swal.fire('No Employees', 'No employees are visible to mark.', 'info');
                    return;
                }

                Swal.fire({
                    title: `Mark all as ${status.charAt(0).toUpperCase() + status.slice(1)}?`,
                    text: `This will mark ${employeeItems.length} employee(s) as ${status}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: status === 'present' ? '#10b981' :
                        status === 'late' ? '#f59e0b' :
                            status === 'absent' ? '#ef4444' : '#4f46e5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: `Yes, mark as ${status}!`,
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        employeeItems.each(function() {
                            const employeeId = $(this).data('employee-id');
                            $(`input[name="attendances[${employeeId}][status]"]`).prop('checked', false);
                            $(`#${status}_${employeeId}`).prop('checked', true).trigger('change');

                            // Update visual indicator
                            const card = $(this).find('.employee-card');
                            card.addClass('selected');
                            const indicator = card.find('.status-indicator');
                            indicator.removeClass('present late absent leave not-marked').addClass(status);
                            card.find('.attendance-status-badge').removeClass('present late absent leave')
                                .addClass(status).text(status.charAt(0).toUpperCase() + status.slice(1));
                        });

                        Swal.fire('Updated!', `${employeeItems.length} employee(s) marked as ${status}.`, 'success');
                    }
                });
            });

            // Individual status change - update visual indicator
            $(document).on('change', 'input[type="radio"][name^="attendances"]', function() {
                const employeeId = $(this).closest('.employee-item').data('employee-id');
                const card = $(this).closest('.employee-card');
                const status = $(this).val();
                const indicator = card.find('.status-indicator');
                const badge = card.find('.attendance-status-badge');

                if (status) {
                    card.addClass('selected');
                    indicator.removeClass('present late absent leave not-marked').addClass(status);
                    badge.removeClass('present late absent leave').addClass(status).text(status.charAt(0).toUpperCase() + status.slice(1));
                } else {
                    card.removeClass('selected');
                    indicator.removeClass('present late absent leave').addClass('not-marked');
                    badge.removeClass('present late absent leave').text('Not marked');
                }
            });

            // Form submit
            $('#attendanceForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = $('#submitBtn');
                const formData = form.serialize();

                // Count how many employees have been marked
                const markedCount = $('input[type="radio"][name^="attendances"]:checked').length;

                if (markedCount === 0) {
                    Swal.fire('No Attendance Marked', 'Please mark attendance for at least one employee.', 'warning');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.hr.attendance.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-save me-1"></i> Save Attendance'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to save attendance.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Save Attendance'
                        );
                    }
                });
            });
        });
    </script>
@endsection
