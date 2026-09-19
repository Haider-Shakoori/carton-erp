{{-- resources/views/admin/hr/payroll/show.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Payroll Details')

@section('css')
    <style>
        .payroll-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .payroll-header .amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        .payroll-header .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .status-badge-large {
            padding: 0.5rem 2rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
        }
        .status-badge-large.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge-large.processed { background: var(--info-bg); color: var(--info); }
        .status-badge-large.paid { background: var(--success-bg); color: var(--success); }

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
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .breakdown-item:last-child {
            border-bottom: none;
        }
        .breakdown-item .label {
            color: var(--gray-500);
        }
        .breakdown-item .value {
            font-weight: 600;
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
                        <i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll') }} <span class="accent">{{ __('ui.details') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-hash me-1"></i> Payroll #{{ $payroll->id }} - {{ $payroll->payroll_month->format('F Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    @if($payroll->status == 'pending')
                        <button class="btn btn-primary process-payroll" data-id="{{ $payroll->id }}">
                            <i class="bi bi-gear me-1"></i> {{ __('ui.process') }}
                        </button>
                    @endif
                    @if($payroll->status == 'processed')
                        <button class="btn btn-success pay-payroll" data-id="{{ $payroll->id }}">
                            <i class="bi bi-cash me-1"></i> {{ __('ui.mark_paid') }}
                        </button>
                    @endif
                    @if($payroll->status != 'paid')
                        <a href="{{ route('admin.hr.payroll.edit', $payroll) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> {{ __('ui.edit') }}
                        </a>
                    @endif
                    <button class="btn btn-outline-info download-payroll" data-id="{{ $payroll->id }}">
                        <i class="bi bi-download me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Payroll Header -->
        <div class="payroll-header">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="label">{{ __('ui.employee') }}</div>
                    <div class="d-flex align-items-center gap-3 mt-1">
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
                            <div class="text-muted small">{{ $payroll->employee->department->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="label">{{ __('ui.net_salary') }}</div>
                    <div class="amount">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->net_salary, 2) }}</div>
                    <div class="mt-1">
                    <span class="status-badge-large {{ $payroll->status }}">
                        {{ ucfirst($payroll->status) }}
                    </span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="label">Payroll Month</div>
                    <div class="fw-bold">{{ $payroll->payroll_month->format('F Y') }}</div>
                    <div class="text-muted small">
                        <i class="bi bi-calendar me-1"></i>
                        Generated: {{ $payroll->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Details -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Salary Breakdown -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-cash-stack me-1"></i> Salary Breakdown
                    </h6>
                    <div class="breakdown-item">
                        <span class="label">{{ __('ui.basic_salary') }}</span>
                        <span class="value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->basic_salary, 2) }}</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="label">{{ __('ui.allowances') }}</span>
                        <span class="value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->allowances, 2) }}</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="label">{{ __('ui.gross_salary') }}</span>
                        <span class="value fw-bold">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->gross_salary, 2) }}</span>
                    </div>
                    <div class="breakdown-item">
                        <span class="label text-danger">{{ __('ui.deductions') }}</span>
                        <span class="value text-danger">-{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->deductions, 2) }}</span>
                    </div>
                    <div class="breakdown-item" style="border-bottom: 2px solid var(--gray-200); padding-bottom: 0.75rem;">
                        <span class="label fw-bold">{{ __('ui.net_salary') }}</span>
                        <span class="value fw-bold" style="color: var(--primary); font-size: 1.2rem;">
                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->net_salary, 2) }}
                    </span>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="info-card">
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-clock-history me-1"></i> Status Timeline
                    </h6>
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-title">Payroll Generated</div>
                            <div class="timeline-date">{{ $payroll->created_at->format('M d, Y h:i A') }}</div>
                        </div>

                        @if($payroll->status != 'pending')
                            <div class="timeline-item completed">
                                <div class="timeline-title">{{ __('ui.processed') }}</div>
                                <div class="timeline-date">{{ $payroll->processed_at ? $payroll->processed_at->format('M d, Y h:i A') : 'N/A' }}</div>
                            </div>
                        @endif

                        @if($payroll->status == 'paid')
                            <div class="timeline-item completed">
                                <div class="timeline-title">Paid</div>
                                <div class="timeline-date">{{ $payroll->paid_at ? $payroll->paid_at->format('M d, Y h:i A') : 'N/A' }}</div>
                            </div>
                        @endif

                        @if($payroll->status == 'pending')
                            <div class="timeline-item active">
                                <div class="timeline-title">{{ __('ui.awaiting_processing') }}</div>
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
                        @if($payroll->status == 'pending')
                            <button class="btn btn-primary process-payroll" data-id="{{ $payroll->id }}">
                                <i class="bi bi-gear me-1"></i> Process Payroll
                            </button>
                        @endif
                        @if($payroll->status == 'processed')
                            <button class="btn btn-success pay-payroll" data-id="{{ $payroll->id }}">
                                <i class="bi bi-cash me-1"></i> {{ __('ui.mark_paid') }}
                            </button>
                        @endif
                        @if($payroll->status != 'paid')
                            <a href="{{ route('admin.hr.payroll.edit', $payroll) }}" class="btn btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Edit Payroll
                            </a>
                        @endif
                        <button class="btn btn-outline-info download-payroll" data-id="{{ $payroll->id }}">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </button>
                        @if($payroll->status != 'paid')
                            <button class="btn btn-outline-danger delete-payroll" data-id="{{ $payroll->id }}">
                                <i class="bi bi-trash me-1"></i> Delete Payroll
                            </button>
                        @endif
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
                            <span>{{ $payroll->employee->email }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.phone_colon') }}</span>
                            <span>{{ $payroll->employee->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.designation_colon') }}</span>
                            <span>{{ $payroll->employee->designation->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Hire Date:</span>
                            <span>{{ $payroll->employee->hire_date ? $payroll->employee->hire_date->format('M d, Y') : 'N/A' }}</span>
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
                            <span>{{ $payroll->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">{{ __('ui.last_updated_label') }}</span>
                            <span>{{ $payroll->updated_at->format('M d, Y h:i A') }}</span>
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
            // Process Payroll
            $(document).on('click', '.process-payroll', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Process Payroll?',
                    text: 'Are you sure you want to process this payroll?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, process!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/payroll/${id}/process`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Success!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to process payroll.', 'error');
                            }
                        });
                    }
                });
            });

            // Mark as Paid
            $(document).on('click', '.pay-payroll', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Mark as Paid?',
                    text: 'Are you sure you want to mark this payroll as paid?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, mark as paid!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/payroll/${id}/pay`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Success!', res.message, 'success').then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to mark as paid.', 'error');
                            }
                        });
                    }
                });
            });

            // Download PDF
            $(document).on('click', '.download-payroll', function() {
                const id = $(this).data('id');
                window.open(`/admin/hr/payroll/${id}/download`, '_blank');
            });

            // Delete Payroll
            $(document).on('click', '.delete-payroll', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Payroll Record?',
                    text: 'Are you sure you want to delete this payroll record? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/admin/hr/payroll/${id}`,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function(res) {
                                if (res.success) {
                                    Swal.fire('Deleted!', res.message, 'success').then(() => {
                                        window.location.href = '{{ route("admin.hr.payroll.index") }}';
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Failed to delete payroll.', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
