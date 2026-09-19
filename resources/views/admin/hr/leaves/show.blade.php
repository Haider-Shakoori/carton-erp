{{-- resources/views/admin/hr/leaves/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Leave Request Details')

@section('css')
    <style>
        .leave-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .status-badge-large {
            padding: 0.5rem 2rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .status-badge-large.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge-large.approved { background: var(--success-bg); color: var(--success); }
        .status-badge-large.rejected { background: var(--danger-bg); color: var(--danger); }

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
        .timeline-item.rejected::before {
            background: var(--danger);
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
                        <i class="bi bi-clock-history me-2"></i> {{ __('ui.leave_request') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Request #{{ $leave->id }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.leaves.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    @if($leave->status == 'pending')
                        <button class="btn btn-success approve-leave" data-id="{{ $leave->id }}">
                            <i class="bi bi-check-circle me-1"></i> {{ __('ui.approve') }}
                        </button>
                        <button class="btn btn-danger reject-leave" data-id="{{ $leave->id }}">
                            <i class="bi bi-x-circle me-1"></i> {{ __('ui.reject') }}
                        </button>
                    @endif
                    @if($leave->status != 'approved')
                        <a href="{{ route('admin.hr.leaves.edit', $leave) }}" class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Header -->
        <div class="leave-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    @if ($leave->employee->profile_image)
                        <img src="{{ Storage::url($leave->employee->profile_image) }}" class="employee-avatar-small" style="width: 80px; height: 80px;">
                    @else
                        <div class="employee-avatar-small placeholder" style="width: 80px; height: 80px; font-size: 1.5rem; margin: 0 auto;">
                            {{ strtoupper(substr($leave->employee->first_name, 0, 1)) }}{{ strtoupper(substr($leave->employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-5">
                    <h4 class="fw-bold mb-0">{{ $leave->employee->first_name }} {{ $leave->employee->last_name }}</h4>
                    <div class="text-muted">{{ $leave->employee->employee_id }}</div>
                    <div class="text-muted small">
                        {{ $leave->employee->department->name ?? 'N/A' }}
                        @if($leave->employee->designation)
                            · {{ $leave->employee->designation->name }}
                        @endif
                    </div>
                </div>
                <div class="col-md-5 text-md-end">
                    <div class="status-badge-large {{ $leave->status }}">
                        <i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>
                        {{ ucfirst($leave->status) }}
                    </div>
                    <div class="mt-2">
                    <span class="leave-type-badge {{ strtolower($leave->leaveType->name) }}">
                        {{ $leave->leaveType->name }}
                    </span>
                        <span class="badge bg-secondary ms-1">{{ $leave->days }} day(s)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Leave Details -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-1"></i> Leave Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.leave_type') }}</div>
                            <div class="info-value">
                            <span class="leave-type-badge {{ strtolower($leave->leaveType->name) }}">
                                {{ $leave->leaveType->name }}
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.total_days') }}</div>
                            <div class="info-value large">{{ $leave->days }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.start_date') }}</div>
                            <div class="info-value">{{ $leave->start_date->format('l, F j, Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">{{ __('ui.end_date') }}</div>
                            <div class="info-value">{{ $leave->end_date->format('l, F j, Y') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">{{ __('ui.reason') }}</div>
                            <div class="info-value" style="font-weight: 400;">
                                {{ $leave->reason ?? 'No reason provided.' }}
                            </div>
                        </div>
                        @if($leave->rejection_reason)
                            <div class="col-12">
                                <div class="info-label text-danger">Rejection Reason</div>
                                <div class="info-value text-danger" style="font-weight: 400;">
                                    {{ $leave->rejection_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Timeline -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-1"></i> Status Timeline
                    </h6>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-title">{{ __('ui.request_submitted') }}</div>
                            <div class="timeline-date">{{ $leave->created_at->format('M d, Y h:i A') }}</div>
                        </div>

                        @if($leave->status != 'pending')
                            <div class="timeline-item {{ $leave->status == 'approved' ? 'completed' : 'rejected' }}">
                                <div class="timeline-title">
                                    {{ $leave->status == 'approved' ? 'Approved' : 'Rejected' }}
                                </div>
                                <div class="timeline-date">{{ $leave->approved_at ? $leave->approved_at->format('M d, Y h:i A') : 'N/A' }}</div>
                                @if($leave->rejection_reason)
                                    <div class="timeline-detail text-danger">
                                        Reason: {{ $leave->rejection_reason }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="timeline-item active">
                                <div class="timeline-title">Awaiting Approval</div>
                                <div class="timeline-date text-warning">{{ __('ui.pending_review') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-gear me-1"></i> {{ __('ui.actions') }}
                    </h6>
                    <div class="d-grid gap-2">
                        @if($leave->status == 'pending')
                            <button class="btn btn-success approve-leave" data-id="{{ $leave->id }}">
                                <i class="bi bi-check-circle me-1"></i> Approve Request
                            </button>
                            <button class="btn btn-danger reject-leave" data-id="{{ $leave->id }}">
                                <i class="bi bi-x-circle me-1"></i> Reject Request
                            </button>
                        @endif
                        @if($leave->status != 'approved')
                            <a href="{{ route('admin.hr.leaves.edit', $leave) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Edit Request
                            </a>
                        @endif
                        <button class="btn btn-outline-danger delete-leave" data-id="{{ $leave->id }}">
                            <i class="bi bi-trash me-1"></i> Delete Request
                        </button>
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-person me-1"></i> Employee Information
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.email_colon') }}</span>
                            <span>{{ $leave->employee->email }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.phone_colon') }}</span>
                            <span>{{ $leave->employee->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Hire Date:</span>
                            <span>{{ $leave->employee->hire_date ? $leave->employee->hire_date->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock me-1"></i> Quick Info
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.created_colon') }}</span>
                            <span>{{ $leave->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                            <span>{{ $leave->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Approve Leave
            $(document).on('click', '.approve-leave', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Approve Leave Request?',
                    text: 'Are you sure you want to approve this leave request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, approve!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/leaves/${id}/approve`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Approved!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to approve leave.', 'error');
                            }
                        });
                    }
                });
            });

            // Reject Leave
            $(document).on('click', '.reject-leave', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Reject Leave Request?',
                    text: 'Are you sure you want to reject this leave request?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, reject!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Rejection Reason',
                            input: 'textarea',
                            inputLabel: 'Please provide a reason for rejection',
                            inputPlaceholder: 'Enter reason...',
                            showCancelButton: true,
                            confirmButtonText: 'Reject',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#ef4444',
                            preConfirm: (reason) => {
                                return reason || 'No reason provided';
                            }
                        }).then((result2) => {
                            if (result2.isConfirmed) {
                                $.ajax({
                                    url: `/admin/hr/leaves/${id}/reject`,
                                    method: 'POST',
                                    data: {
                                        rejection_reason: result2.value,
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function(res) {
                                        if (res.success) {
                                            Swal.fire('Rejected!', res.message, 'success').then(() => {
                                                location.reload();
                                            });
                                        }
                                    },
                                    error: function() {
                                        Swal.fire('Error', 'Failed to reject leave.', 'error');
                                    }
                                });
                            }
                        });
                    }
                });
            });

            // Delete Leave
            $(document).on('click', '.delete-leave', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Leave Request?',
                    text: 'Are you sure you want to delete this leave request? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/leaves/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        window.location.href = '{{ route("admin.hr.leaves.index") }}';
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete leave request.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
