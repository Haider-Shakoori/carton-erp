{{-- resources/views/admin/hr/attendance/edit.blade.php --}}
{{-- This is essentially the same as create but pre-populated with existing data --}}
@extends('layouts.admin.base')

@section('title', 'Edit Attendance')

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
        }
        .employee-card.selected {
            background: var(--primary-bg);
            border-color: var(--primary);
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
        .existing-record {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 0.5rem 1rem;
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
                        <i class="bi bi-pencil me-2"></i> {{ __('ui.edit') }} <span class="accent">{{ __('ui.attendance') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-date me-1"></i> {{ $date->format('l, F j, Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('admin.hr.attendance.show', $attendance) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> {{ __('ui.view_details') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-info-circle fs-4"></i>
                <div>
                    <strong>Editing attendance for:</strong>
                    {{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}
                    <span class="text-muted ms-2">({{ $attendance->employee->employee_id }})</span>
                    <span class="attendance-status-badge {{ $attendance->status }} ms-2">
                    Current: {{ ucfirst($attendance->status) }}
                </span>
                    @if($attendance->check_in)
                        <span class="badge bg-success ms-1">
                        <i class="bi bi-box-arrow-in-right"></i> {{ $attendance->check_in->format('h:i A') }}
                    </span>
                    @endif
                    @if($attendance->check_out)
                        <span class="badge bg-warning ms-1">
                        <i class="bi bi-box-arrow-left"></i> {{ $attendance->check_out->format('h:i A') }}
                    </span>
                    @endif
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

        <!-- Edit Form -->
        <form id="attendanceForm" method="POST" action="{{ route('admin.hr.attendance.bulk-store') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">

            <div class="row">
                <div class="col-12">
                    <div class="employee-item" data-employee-id="{{ $attendance->employee_id }}">
                        <div class="employee-card selected">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="employee-name">
                                        {{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}
                                    </div>
                                    <div class="employee-detail">
                                        {{ $attendance->employee->employee_id }}
                                        @if($attendance->employee->department)
                                            · {{ $attendance->employee->department->name }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="status-indicator {{ $attendance->status }}"></div>
                                    <span class="attendance-status-badge {{ $attendance->status }}" style="font-size: 0.6rem; display: block; margin-top: 0.25rem;">
                                    {{ ucfirst($attendance->status) }}
                                </span>
                                    @if($attendance->check_in)
                                        <div class="text-success" style="font-size: 0.6rem;">
                                            <i class="bi bi-box-arrow-in-right"></i> {{ $attendance->check_in->format('h:i A') }}
                                        </div>
                                    @endif
                                    @if($attendance->check_out)
                                        <div class="text-warning" style="font-size: 0.6rem;">
                                            <i class="bi bi-box-arrow-left"></i> {{ $attendance->check_out->format('h:i A') }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Selector -->
                            <div class="mt-2">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendances[{{ $attendance->employee_id }}][status]"
                                           id="present_{{ $attendance->employee_id }}" value="present"
                                        {{ $attendance->status == 'present' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-success" for="present_{{ $attendance->employee_id }}">{{ __('ui.present') }}</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $attendance->employee_id }}][status]"
                                           id="late_{{ $attendance->employee_id }}" value="late"
                                        {{ $attendance->status == 'late' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-warning" for="late_{{ $attendance->employee_id }}">Late</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $attendance->employee_id }}][status]"
                                           id="absent_{{ $attendance->employee_id }}" value="absent"
                                        {{ $attendance->status == 'absent' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-danger" for="absent_{{ $attendance->employee_id }}">{{ __('ui.absent') }}</label>

                                    <input type="radio" class="btn-check" name="attendances[{{ $attendance->employee_id }}][status]"
                                           id="leave_{{ $attendance->employee_id }}" value="leave"
                                        {{ $attendance->status == 'leave' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary" for="leave_{{ $attendance->employee_id }}">Leave</label>
                                </div>
                                <input type="hidden" name="attendances[{{ $attendance->employee_id }}][employee_id]" value="{{ $attendance->employee_id }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check In/Out Time Override -->
            <div class="form-section mt-3">
                <div class="section-title">
                    <i class="bi bi-clock me-1"></i> Time Override (Optional)
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Check In Time</label>
                        <input type="time" class="form-control" name="check_in_time"
                               value="{{ $attendance->check_in ? $attendance->check_in->format('H:i') : '' }}">
                        <small class="text-muted">Leave empty to keep existing or auto-set</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Check Out Time</label>
                        <input type="time" class="form-control" name="check_out_time"
                               value="{{ $attendance->check_out ? $attendance->check_out->format('H:i') : '' }}">
                        <small class="text-muted">Leave empty to keep existing or auto-set</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Hours</label>
                        <input type="number" class="form-control" name="total_hours"
                               step="0.5" min="0" value="{{ $attendance->total_hours ?? '' }}"
                               placeholder="{{ __('ui.auto_calculated_empty') }}">
                        <small class="text-muted">Leave empty for auto-calculation</small>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                    <i class="bi bi-save me-1"></i> Update Attendance
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
                    window.location.href = window.location.pathname + '?date=' + date + '&employee={{ $attendance->employee_id }}';
                }
            });

            // Enter key on date input
            $('#attendanceDate').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#loadDate').click();
                }
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

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Updating...'
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
                                '<i class="bi bi-save me-1"></i> Update Attendance'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update attendance.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Update Attendance'
                        );
                    }
                });
            });
        });
    </script>
@endsection
