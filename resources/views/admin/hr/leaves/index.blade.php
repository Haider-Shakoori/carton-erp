{{-- resources/views/admin/hr/leaves/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.leave_management'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .leave-status {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .leave-status.pending { background: var(--warning-bg); color: var(--warning); }
        .leave-status.approved { background: var(--success-bg); color: var(--success); }
        .leave-status.rejected { background: var(--danger-bg); color: var(--danger); }

        .filter-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .filter-group .form-select,
        .filter-group .form-control {
            width: auto;
            min-width: 150px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-clock-history me-2"></i> Leave <span class="accent">{{ __('ui.management') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-list-check me-1"></i> Manage employee leave requests
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.leaves.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> {{ __('ui.new_leave_request') }}
                    </a>
                    <a href="{{ route('admin.hr.leaves.types') }}" class="btn btn-outline-primary">
                        <i class="bi bi-tags me-1"></i> {{ __('ui.leave_types') }}
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
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.approved') }}</div>
                <div class="stat-value">{{ $approved }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.rejected') }}</div>
                <div class="stat-value">{{ $rejected }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card-modern mb-4">
            <div class="card-body">
                <form method="GET" class="filter-group">
                    <select name="status" class="form-select">
                        <option value="">{{ __('ui.all_status') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('ui.pending') }}</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('ui.approved') }}</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('ui.rejected') }}</option>
                    </select>
                    <select name="employee_id" class="form-select select2">
                        <option value="">{{ __('ui.all_employees') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="{{ __('ui.start_date') }}">
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="{{ __('ui.end_date') }}">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.hr.leaves.index') }}" class="btn btn-outline-secondary">Clear</a>
                </form>
            </div>
        </div>

        <!-- Leave Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-check me-1"></i> {{ __('ui.leave_requests') }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="leavesTable">
                        <thead>
                        <tr>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.start_date') }}</th>
                            <th>{{ __('ui.end_date') }}</th>
                            <th>Days</th>
                            <th>{{ __('ui.reason') }}</th>
                            <th>{{ __('ui.status') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($leaves as $leave)
                            <tr>
                                <td class="fw-semibold text-dark">
                                    {{ $leave->employee->first_name }} {{ $leave->employee->last_name }}
                                </td>
                                <td>{{ $leave->leaveType->name ?? 'N/A' }}</td>
                                <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                <td>{{ $leave->days }}</td>
                                <td class="text-truncate" style="max-width: 150px;">{{ $leave->reason ?? '-' }}</td>
                                <td>
                                <span class="leave-status {{ $leave->status }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                                </td>
                                <td>
                                    <div class="action-buttons justify-content-end">
                                        @if($leave->status == 'pending')
                                            <button class="action-btn approve-leave" data-id="{{ $leave->id }}" title="{{ __('ui.approve') }}">
                                                <i class="bi bi-check-circle text-success"></i>
                                            </button>
                                            <button class="action-btn reject-leave" data-id="{{ $leave->id }}" title="{{ __('ui.reject') }}">
                                                <i class="bi bi-x-circle text-danger"></i>
                                            </button>
                                        @endif
                                        <button class="action-btn edit-leave"
                                                data-id="{{ $leave->id }}"
                                                data-employee_id="{{ $leave->employee_id }}"
                                                data-leave_type_id="{{ $leave->leave_type_id }}"
                                                data-start_date="{{ $leave->start_date->format('Y-m-d') }}"
                                                data-end_date="{{ $leave->end_date->format('Y-m-d') }}"
                                                data-reason="{{ $leave->reason }}"
                                                title="{{ __('ui.edit') }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="action-btn text-danger delete-leave"
                                                data-id="{{ $leave->id }}"
                                                data-name="{{ $leave->employee->first_name }} {{ $leave->employee->last_name }}"
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
                    {{ $leaves->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: 'resolve'
            });

            $('#leavesTable').DataTable({
                pageLength: 15,
                lengthChange: false,
                ordering: true,
                searching: true,
                order: [[2, 'desc']],
                columnDefs: [{ orderable: false, targets: [7] }],
                language: {
                    search: '',
                    searchPlaceholder: 'Search leaves...',
                }
            });

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
                        $.ajax({
                            url: `/admin/hr/leaves/${id}/reject`,
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
                                Swal.fire('Error', 'Failed to reject leave.', 'error');
                            }
                        });
                    }
                });
            });

            // Delete Leave
            $(document).on('click', '.delete-leave', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Delete Leave Request?',
                    text: `Are you sure you want to delete "${name}"'s leave request?`,
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
                                        location.reload();
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
