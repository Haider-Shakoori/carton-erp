{{-- resources/views/admin/hr/advances/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Advance / Loan Details')

@section('css')
    <style>
        .detail-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .detail-card .detail-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .detail-card .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        .detail-card .detail-value.large {
            font-size: 1.5rem;
        }
        .status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge.approved { background: var(--info-bg); color: var(--info); }
        .status-badge.rejected { background: var(--danger-bg); color: var(--danger); }
        .status-badge.paid { background: var(--success-bg); color: var(--success); }

        .type-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .type-badge.advance { background: var(--primary-bg); color: var(--primary); }
        .type-badge.loan { background: var(--warning-bg); color: var(--warning); }

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
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-info-circle me-2"></i> {{ __('ui.advance_loan') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Request #{{ $advance->id }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.advances.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    @if($advance->status == 'pending')
                        <button class="btn btn-success approve-advance" data-id="{{ $advance->id }}">
                            <i class="bi bi-check-circle me-1"></i> {{ __('ui.approve') }}
                        </button>
                        <button class="btn btn-danger reject-advance" data-id="{{ $advance->id }}">
                            <i class="bi bi-x-circle me-1"></i> {{ __('ui.reject') }}
                        </button>
                    @endif
                    @if(in_array($advance->status, ['approved', 'paid']) && $advance->remaining_amount > 0)
                        <button class="btn btn-warning deduct-advance" data-id="{{ $advance->id }}" data-remaining="{{ $advance->remaining_amount }}">
                            <i class="bi bi-arrow-down-circle me-1"></i> {{ __('ui.deduct') }}
                        </button>
                    @endif
                    <a href="{{ route('admin.hr.advances.edit', $advance) }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="detail-card">
            <div class="row g-4">
                <div class="col-md-8">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-1"></i> Status Timeline
                    </h6>
                    <div class="timeline">
                        <div class="timeline-item {{ $advance->status != 'pending' ? 'completed' : 'active' }}">
                            <div class="timeline-title">{{ __('ui.request_submitted') }}</div>
                            <div class="timeline-date">{{ $advance->created_at->format('M d, Y h:i A') }}</div>
                        </div>

                        @if($advance->status != 'pending')
                            <div class="timeline-item {{ $advance->status == 'approved' || $advance->status == 'paid' ? 'completed' : 'active' }}">
                                <div class="timeline-title">
                                    {{ $advance->status == 'approved' || $advance->status == 'paid' ? 'Approved' : 'Rejected' }}
                                </div>
                                <div class="timeline-date">
                                    {{ $advance->approved_at ? $advance->approved_at->format('M d, Y h:i A') : 'N/A' }}
                                </div>
                                @if($advance->rejection_reason)
                                    <div class="mt-1 text-danger small">
                                        Reason: {{ $advance->rejection_reason }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($advance->status == 'paid')
                            <div class="timeline-item completed">
                                <div class="timeline-title">Fully Paid</div>
                                <div class="timeline-date">{{ $advance->paid_at ? $advance->paid_at->format('M d, Y h:i A') : 'N/A' }}</div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 bg-light rounded">
                        <div class="text-muted small text-uppercase">Current Status</div>
                        <div class="status-badge {{ $advance->status }} mt-1">
                            {{ ucfirst($advance->status) }}
                        </div>
                        <div class="mt-3">
                            <div class="text-muted small text-uppercase">{{ __('ui.type') }}</div>
                            <div class="type-badge {{ $advance->type }} mt-1">
                                {{ ucfirst($advance->type) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="detail-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-info-circle me-1"></i> Request Information
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-label">{{ __('ui.employee') }}</div>
                            <div class="detail-value">
                                {{ $advance->employee->first_name }} {{ $advance->employee->last_name }}
                                <span class="text-muted" style="font-size: 0.8rem; font-weight: 400;">
                                ({{ $advance->employee->employee_id }})
                            </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">{{ __('ui.department') }}</div>
                            <div class="detail-value">{{ $advance->employee->department->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Amount Requested</div>
                            <div class="detail-value large">${{ number_format($advance->amount, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Remaining Balance</div>
                            <div class="detail-value large {{ $advance->remaining_amount > 0 ? 'text-warning' : 'text-success' }}">
                                ${{ number_format($advance->remaining_amount, 2) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">{{ __('ui.request_date') }}</div>
                            <div class="detail-value">{{ $advance->request_date->format('M d, Y') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">{{ __('ui.reason') }}</div>
                            <div class="detail-value" style="font-weight: 400;">
                                {{ $advance->reason ?? 'No reason provided.' }}
                            </div>
                        </div>
                    </div>
                </div>

                @if($advance->deduction_start_date || $advance->deduction_end_date || $advance->deduction_amount)
                    <div class="detail-card">
                        <h6 class="fw-bold mb-3">
                            <i class="bi bi-calculator me-1"></i> Deduction Details
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="detail-label">{{ __('ui.start_date') }}</div>
                                <div class="detail-value">{{ $advance->deduction_start_date ? $advance->deduction_start_date->format('M d, Y') : 'N/A' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label">{{ __('ui.end_date') }}</div>
                                <div class="detail-value">{{ $advance->deduction_end_date ? $advance->deduction_end_date->format('M d, Y') : 'N/A' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="detail-label">{{ __('ui.deduction_amount') }}</div>
                                <div class="detail-value">${{ $advance->deduction_amount ? number_format($advance->deduction_amount, 2) : 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar Actions -->
            <div class="col-lg-4">
                <div class="detail-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock me-1"></i> Quick Actions
                    </h6>

                    @if($advance->status == 'pending')
                        <div class="d-grid gap-2">
                            <button class="btn btn-success approve-advance" data-id="{{ $advance->id }}">
                                <i class="bi bi-check-circle me-1"></i> Approve Request
                            </button>
                            <button class="btn btn-danger reject-advance" data-id="{{ $advance->id }}">
                                <i class="bi bi-x-circle me-1"></i> Reject Request
                            </button>
                        </div>
                    @endif

                    @if(in_array($advance->status, ['approved', 'paid']) && $advance->remaining_amount > 0)
                        <div class="d-grid gap-2 mt-2">
                            <button class="btn btn-warning deduct-advance" data-id="{{ $advance->id }}" data-remaining="{{ $advance->remaining_amount }}">
                                <i class="bi bi-arrow-down-circle me-1"></i> Process Deduction
                            </button>
                        </div>
                    @endif

                    <hr>

                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.hr.advances.edit', $advance) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Edit Request
                        </a>
                        <button class="btn btn-outline-danger delete-advance" data-id="{{ $advance->id }}">
                            <i class="bi bi-trash me-1"></i> Delete Request
                        </button>
                    </div>
                </div>

                <div class="detail-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-person me-1"></i> Employee Information
                    </h6>
                    <div class="small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.email_colon') }}</span>
                            <span>{{ $advance->employee->email }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.phone_colon') }}</span>
                            <span>{{ $advance->employee->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.designation_colon') }}</span>
                            <span>{{ $advance->employee->designation->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Basic Salary:</span>
                            <span>${{ number_format($advance->employee->basic_salary ?? 0, 2) }}</span>
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
            // Approve Advance
            $(document).on('click', '.approve-advance', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Approve Advance/Loan?',
                    text: 'Are you sure you want to approve this request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, approve!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/advances/${id}/approve`,
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
                                Swal.fire('Error', 'Failed to approve.', 'error');
                            }
                        });
                    }
                });
            });

            // Reject Advance
            $(document).on('click', '.reject-advance', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Reject Advance/Loan?',
                    text: 'Are you sure you want to reject this request?',
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
                                    url: `/admin/hr/advances/${id}/reject`,
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
                                        Swal.fire('Error', 'Failed to reject.', 'error');
                                    }
                                });
                            }
                        });
                    }
                });
            });

            // Deduct Advance
            $(document).on('click', '.deduct-advance', function() {
                const id = $(this).data('id');
                const remaining = $(this).data('remaining');

                Swal.fire({
                    title: 'Process Deduction',
                    html: `
                <div class="text-start">
                    <p>Remaining balance: <strong>$${remaining.toFixed(2)}</strong></p>
                    <label class="form-label">{{ __('ui.deduction_amount') }}</label>
                    <input type="number" class="form-control" id="deductionAmount" step="0.01" min="0.01" max="${remaining}" placeholder="{{ __('ui.enter_amount') }}">
                    <label class="form-label mt-2">{{ __('ui.deduction_date') }}</label>
                    <input type="date" class="form-control" id="deductionDate" value="{{ date('Y-m-d') }}">
                </div>
            `,
                    showCancelButton: true,
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Process Deduction',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const amount = document.getElementById('deductionAmount').value;
                        const date = document.getElementById('deductionDate').value;
                        if (!amount || amount <= 0) {
                            Swal.showValidationMessage('Please enter a valid amount');
                            return false;
                        }
                        if (parseFloat(amount) > remaining) {
                            Swal.showValidationMessage('Amount cannot exceed remaining balance');
                            return false;
                        }
                        if (!date) {
                            Swal.showValidationMessage('Please select a date');
                            return false;
                        }
                        return { amount: amount, date: date };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/advances/${id}/deduct`,
                            method: 'POST',
                            data: {
                                deduction_amount: result.value.amount,
                                deduction_date: result.value.date,
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
                                Swal.fire('Error', 'Failed to process deduction.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete Advance
            $(document).on('click', '.delete-advance', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Advance/Loan?',
                    text: 'Are you sure you want to delete this request? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/advances/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        window.location.href = '{{ route("admin.hr.advances.index") }}';
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
