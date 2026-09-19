{{-- resources/views/admin/hr/payroll/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Payroll Management')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .payroll-status {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .payroll-status.pending { background: var(--warning-bg); color: var(--warning); }
        .payroll-status.processed { background: var(--info-bg); color: var(--info); }
        .payroll-status.paid { background: var(--success-bg); color: var(--success); }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-wallet2 me-2"></i> {{ __('ui.payroll') }} <span class="accent">{{ __('ui.management') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-cash me-1"></i> Manage employee payroll
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.payroll.generate') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('ui.generate_payroll') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-cash"></i>
                    </div>
                </div>
                <div class="stat-label">Total Payroll</div>
                <div class="stat-value">${{ number_format($totalPayroll, 0) }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon yellow">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.pending') }}</div>
                <div class="stat-value">{{ $pendingPayroll }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <i class="bi bi-gear"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.processed') }}</div>
                <div class="stat-value">{{ $processedPayroll }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-label">Paid</div>
                <div class="stat-value">{{ $paidPayroll }}</div>
            </div>
        </div>

        <!-- Payroll Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Payroll Records
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="payrollTable">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.payroll_number') }}</th>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.period') }}</th>
                            <th>Total Earnings</th>
                            <th>{{ __('ui.total_deductions') }}</th>
                            <th>{{ __('ui.net_salary') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>

                        <!-- Update the table rows -->
                        <tbody>
                        @foreach($payrolls as $payroll)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>{{ $payroll->payroll_number }}</td>
                                <td class="fw-semibold text-dark">
                                    {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}
                                </td>
                                <td>{{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</td>
                                <td class="num-cell">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->total_earnings, 2) }}</td>
                                <td class="num-cell text-danger">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->total_deductions, 2) }}</td>
                                <td class="num-cell fw-bold">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->net_salary, 2) }}</td>
                                <td>
            <span class="payroll-status-badge {{ $payroll->status }}">
                {{ ucfirst($payroll->status) }}
            </span>
                                </td>
                                <td>
                                    <div class="action-buttons justify-content-end">
                                        @if($payroll->status == 'draft')
                                            <button class="action-btn process-payroll" data-id="{{ $payroll->id }}" title="{{ __('ui.process') }}">
                                                <i class="bi bi-gear text-primary"></i>
                                            </button>
                                        @endif
                                        @if($payroll->status == 'processed')
                                            <button class="action-btn pay-payroll" data-id="{{ $payroll->id }}" title="{{ __('ui.mark_paid') }}">
                                                <i class="bi bi-cash text-success"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.hr.payroll.show', $payroll) }}" class="action-btn" title="{{ __('ui.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if($payroll->status != 'paid')
                                            <button class="action-btn text-danger delete-payroll" data-id="{{ $payroll->id }}" title="{{ __('ui.delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $payrolls->links() }}
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
            $('#payrollTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[1, 'desc']],
                columnDefs: [{ orderable: false, targets: [8] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search payroll...',
                }
            });

            // Process Payroll
            $(document).on('click', '.process-payroll', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Process Payroll?',
                    text: 'Are you sure you want to process this payroll?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
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

            // Delete Payroll
            $(document).on('click', '.delete-payroll', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Payroll Record?',
                    text: 'Are you sure you want to delete this payroll record?',
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
                                        location.reload();
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
