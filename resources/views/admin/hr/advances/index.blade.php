{{-- resources/views/admin/hr/advances/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.advances_loans'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .advance-status {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .advance-status.pending { background: var(--warning-bg); color: var(--warning); }
        .advance-status.approved { background: var(--info-bg); color: var(--info); }
        .advance-status.rejected { background: var(--danger-bg); color: var(--danger); }
        .advance-status.paid { background: var(--success-bg); color: var(--success); }

        .advance-type {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .advance-type.advance { background: var(--primary-bg); color: var(--primary); }
        .advance-type.loan { background: var(--warning-bg); color: var(--warning); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-coin me-2"></i> {{ __('ui.advances') }} <span class="accent">& Loans</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-wallet me-1"></i> Manage employee advances and loans
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.advances.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> New Advance/Loan
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.pending') }}</div>
                <div class="stat-value">{{ $pending }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.approved') }}</div>
                <div class="stat-value">{{ $approved }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-coin"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_amount') }}</div>
                <div class="stat-value">{{ $defaultCurrency->symbol }}{{ number_format($totalAmount, 0) }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-wallet"></i>
                    </div>
                </div>
                <div class="stat-label">Paid</div>
                <div class="stat-value">{{ $paid }}</div>
            </div>
        </div>

        <!-- Advance Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> All Advances & Loans
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="advancesTable">
                        <thead>
                        <tr>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.amount') }}</th>
                            <th>{{ __('ui.remaining') }}</th>
                            <th>{{ __('ui.request_date') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($advances as $advance)
                            <tr>
                                <td class="fw-semibold text-dark">
                                    {{ $advance->employee->first_name }} {{ $advance->employee->last_name }}
                                </td>
                                <td>
                                <span class="advance-type {{ $advance->type }}">
                                    {{ ucfirst($advance->type) }}
                                </span>
                                </td>
                                <td class="num-cell">{{ $defaultCurrency->symbol }}{{ number_format($advance->amount, 2) }}</td>
                                <td class="num-cell">{{ $defaultCurrency->symbol }}{{ number_format($advance->remaining_amount, 2) }}</td>
                                <td>{{ $advance->request_date->format('M d, Y') }}</td>
                                <td>
                                <span class="advance-status {{ $advance->status }}">
                                    {{ ucfirst($advance->status) }}
                                </span>
                                </td>
                                <td>
                                    <div class="action-buttons justify-content-end">
                                        @if($advance->status == 'pending')
                                            <button class="action-btn approve-advance" data-id="{{ $advance->id }}" title="{{ __('ui.approve') }}">
                                                <i class="bi bi-check-circle text-success"></i>
                                            </button>
                                            <button class="action-btn reject-advance" data-id="{{ $advance->id }}" title="{{ __('ui.reject') }}">
                                                <i class="bi bi-x-circle text-danger"></i>
                                            </button>
                                        @endif
                                        @if(in_array($advance->status, ['approved', 'paid']) && $advance->remaining_amount > 0)
                                            <button class="action-btn deduct-advance"
                                                    data-id="{{ $advance->id }}"
                                                    data-remaining="{{ $advance->remaining_amount }}"
                                                    title="{{ __('ui.deduct') }}">
                                                <i class="bi bi-arrow-down-circle text-warning"></i>
                                            </button>
                                        @endif
                                        <button class="action-btn edit-advance"
                                                data-id="{{ $advance->id }}"
                                                data-employee_id="{{ $advance->employee_id }}"
                                                data-amount="{{ $advance->amount }}"
                                                data-type="{{ $advance->type }}"
                                                data-request_date="{{ $advance->request_date->format('Y-m-d') }}"
                                                data-deduction_start_date="{{ $advance->deduction_start_date?->format('Y-m-d') }}"
                                                data-deduction_end_date="{{ $advance->deduction_end_date?->format('Y-m-d') }}"
                                                data-deduction_amount="{{ $advance->deduction_amount }}"
                                                data-reason="{{ $advance->reason }}"
                                                title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn text-danger delete-advance"
                                                data-id="{{ $advance->id }}"
                                                data-name="{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}"
                                                title="{{ __('ui.delete') }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $advances->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#advancesTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[4, 'desc']],
                columnDefs: [{ orderable: false, targets: [6] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search advances...',
                }
            });

            // Approve Advance
            $(document).on('click', '.approve-advance', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Approve Advance/Loan?',
                    text: 'Are you sure you want to approve this request?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
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
                        $.ajax({
                            url: `/admin/hr/advances/${id}/reject`,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
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
                    <input type="number" class="form-control" id="deductionAmount" step="0.01" min="0.01" max="${remaining}">
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
                        if (amount > remaining) {
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
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Advance/Loan?',
                    text: `Are you sure you want to delete "${name}"'s advance/loan?`,
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
                                        location.reload();
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
