{{-- resources/views/admin/hr/attendance/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Attendance Details')

@section('css')
    <style>
        .attendance-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .attendance-status-large {
            padding: 0.5rem 2rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .attendance-status-large.present { background: var(--success-bg); color: var(--success); }
        .attendance-status-large.late { background: var(--warning-bg); color: var(--warning); }
        .attendance-status-large.absent { background: var(--danger-bg); color: var(--danger); }
        .attendance-status-large.leave { background: var(--primary-bg); color: var(--primary); }

        .info-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .info-card .info-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-card .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        .info-card .info-value.large {
            font-size: 1.5rem;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--gray-200);
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--gray-300);
            border: 2px solid white;
        }
        .timeline-item.active::before {
            background: var(--primary);
        }
        .timeline-item.completed::before {
            background: var(--success);
        }
        .timeline-item .timeline-title {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .timeline-item .timeline-date {
            font-size: 0.7rem;
            color: var(--gray-400);
        }
        .timeline-item .timeline-detail {
            font-size: 0.8rem;
            color: var(--gray-600);
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
                        <i class="bi bi-calendar-check me-2"></i> {{ __('ui.attendance') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-date me-1"></i> {{ $attendance->date->format('l, F j, Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.attendance.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <a href="{{ route('admin.hr.attendance.create', ['date' => $attendance->date->format('Y-m-d')]) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit This Day
                    </a>
                </div>
            </div>
        </div>

        <!-- Attendance Header -->
        <div class="attendance-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    @if ($attendance->employee->profile_image)
                        <img src="{{ Storage::url($attendance->employee->profile_image) }}" class="employee-avatar-small" style="width: 80px; height: 80px;">
                    @else
                        <div class="employee-avatar-small placeholder" style="width: 80px; height: 80px; font-size: 1.5rem; margin: 0 auto;">
                            {{ strtoupper(substr($attendance->employee->first_name, 0, 1)) }}{{ strtoupper(substr($attendance->employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-5">
                    <h4 class="fw-bold mb-0">{{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}</h4>
                    <div class="text-muted">{{ $attendance->employee->employee_id }}</div>
                    <div class="text-muted small">
                        {{ $attendance->employee->department->name ?? 'N/A' }}
                        @if($attendance->employee->designation)
                            · {{ $attendance->employee->designation->name }}
                        @endif
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="attendance-status-large {{ $attendance->status }}">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>
                        {{ ucfirst($attendance->status) }}
                    </div>
                    @if($attendance->check_in)
                        <div class="mt-2">
                        <span class="badge bg-success">
                            <i class="bi bi-box-arrow-in-right me-1"></i>
                            In: {{ $attendance->check_in->format('h:i A') }}
                        </span>
                            @if($attendance->check_out)
                                <span class="badge bg-warning ms-1">
                                <i class="bi bi-box-arrow-left me-1"></i>
                                Out: {{ $attendance->check_out->format('h:i A') }}
                            </span>
                            @endif
                        </div>
                    @endif
                    @if($attendance->total_hours)
                        <div class="mt-1">
                        <span class="badge bg-info">
                            <i class="bi bi-clock me-1"></i>
                            Total: {{ number_format($attendance->total_hours, 1) }} hours
                        </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Timeline -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-1"></i> Attendance Timeline
                    </h6>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-title">{{ __('ui.date') }}</div>
                            <div class="timeline-date">{{ $attendance->date->format('l, F j, Y') }}</div>
                        </div>

                        @if($attendance->check_in)
                            <div class="timeline-item completed">
                                <div class="timeline-title">{{ __('ui.check_in') }}</div>
                                <div class="timeline-date">{{ $attendance->check_in->format('h:i A') }}</div>
                                @php
                                    $officeStart = \Carbon\Carbon::parse($attendance->date->format('Y-m-d') . ' 09:00:00');
                                    if($attendance->check_in->gt($officeStart)) {
                                        $lateMinutes = $attendance->check_in->diffInMinutes($officeStart);
                                        echo '<div class="timeline-detail text-warning">⏰ Late by ' . $lateMinutes . ' minutes</div>';
                                    } else {
                                        echo '<div class="timeline-detail text-success">✅ On time</div>';
                                    }
                                @endphp
                            </div>
                        @else
                            <div class="timeline-item active">
                                <div class="timeline-title">{{ __('ui.check_in') }}</div>
                                <div class="timeline-date text-muted">Not checked in</div>
                            </div>
                        @endif

                        @if($attendance->check_out)
                            <div class="timeline-item completed">
                                <div class="timeline-title">{{ __('ui.check_out') }}</div>
                                <div class="timeline-date">{{ $attendance->check_out->format('h:i A') }}</div>
                            </div>
                        @else
                            @if($attendance->check_in)
                                <div class="timeline-item active">
                                    <div class="timeline-title">{{ __('ui.check_out') }}</div>
                                    <div class="timeline-date text-warning">Not checked out yet</div>
                                </div>
                            @endif
                        @endif

                        @if($attendance->total_hours)
                            <div class="timeline-item completed">
                                <div class="timeline-title">Total Hours Worked</div>
                                <div class="timeline-date">{{ number_format($attendance->total_hours, 1) }} hours</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Info -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-1"></i> Quick Info
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.employee_colon') }}</span>
                            <span class="fw-semibold">
                            <a href="{{ route('admin.hr.employees.show', $attendance->employee) }}" class="text-decoration-none">
                                {{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}
                            </a>
                        </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.department_colon') }}</span>
                            <span>{{ $attendance->employee->department->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.status_colon') }}</span>
                            <span class="attendance-status-badge {{ $attendance->status }}">
                            {{ ucfirst($attendance->status) }}
                        </span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Record Created:</span>
                            <span>{{ $attendance->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                            <span>{{ $attendance->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-gear me-1"></i> {{ __('ui.actions') }}
                    </h6>
                    <div class="d-grid gap-2">
                        @if(!$attendance->check_in)
                            <button class="btn btn-success check-in-btn" data-id="{{ $attendance->id }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('ui.check_in') }}
                            </button>
                        @endif
                        @if($attendance->check_in && !$attendance->check_out)
                            <button class="btn btn-warning check-out-btn" data-id="{{ $attendance->id }}">
                                <i class="bi bi-box-arrow-left me-1"></i> {{ __('ui.check_out') }}
                            </button>
                        @endif
                        <a href="{{ route('admin.hr.attendance.create', ['date' => $attendance->date->format('Y-m-d')]) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Day's Attendance
                        </a>
                    </div>
                </div>

                <!-- Related Records -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-calendar-event me-1"></i> Recent Attendance
                    </h6>
                    @if($recent->count() > 0)
                        @foreach($recent as $record)
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span>{{ $record->date->format('M d') }}</span>
                                <span class="attendance-status-badge {{ $record->status }}" style="font-size: 0.6rem;">
                                {{ ucfirst($record->status) }}
                            </span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted text-center py-2" style="font-size: 0.8rem;">
                            No other attendance records found.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Check In
            $(document).on('click', '.check-in-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Check In Employee?',
                    text: 'Are you sure you want to check in this employee?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, check in!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("admin.hr.attendance.check-in") }}',
                            method: 'POST',
                            data: {
                                employee_id: {{ $attendance->employee_id }},
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Success!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to check in.', 'error');
                            }
                        });
                    }
                });
            });

            // Check Out
            $(document).on('click', '.check-out-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Check Out Employee?',
                    text: 'Are you sure you want to check out this employee?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, check out!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("admin.hr.attendance.check-out") }}',
                            method: 'POST',
                            data: {
                                employee_id: {{ $attendance->employee_id }},
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Success!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to check out.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
