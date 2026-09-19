{{-- resources/views/admin/hr/reports/leaves.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.leave_reports'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .report-filters {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .report-summary .summary-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
        }
        .report-summary .summary-card .number {
            font-size: 1.5rem;
            font-weight: 800;
        }
        .report-summary .summary-card .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .report-summary .summary-card .number.warning { color: var(--warning); }
        .report-summary .summary-card .number.success { color: var(--success); }
        .report-summary .summary-card .number.danger { color: var(--danger); }
        .report-summary .summary-card .number.primary { color: var(--primary); }
        .report-summary .summary-card .number.purple { color: #8b5cf6; }

        .leave-status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .leave-status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .leave-status-badge.approved { background: var(--success-bg); color: var(--success); }
        .leave-status-badge.rejected { background: var(--danger-bg); color: var(--danger); }

        .leave-type-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .leave-type-badge.annual { background: #dbeafe; color: #2563eb; }
        .leave-type-badge.sick { background: #fce4ec; color: #dc2626; }
        .leave-type-badge.casual { background: #fef3c7; color: #d97706; }
        .leave-type-badge.maternity { background: #f3e8ff; color: #7c3aed; }
        .leave-type-badge.paternity { background: #d1fae5; color: #059669; }
        .leave-type-badge.other { background: #f3f4f6; color: #6b7280; }

        .report-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
                        <i class="bi bi-clock-history me-2"></i> Leave <span class="accent">{{ __('ui.reports') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate and view leave reports
                    </p>
                </div>
                <div class="report-actions">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.reports.export', ['type' => 'leaves', 'format' => 'csv']) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="report-filters">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.start_date') }}</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.end_date') }}</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('ui.all_status') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('ui.pending') }}</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('ui.approved') }}</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('ui.rejected') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="report-summary">
            <div class="summary-card">
                <div class="number primary">{{ $summary['total'] }}</div>
                <div class="label">{{ __('ui.total_requests') }}</div>
            </div>
            <div class="summary-card">
                <div class="number warning">{{ $summary['pending'] }}</div>
                <div class="label">{{ __('ui.pending') }}</div>
            </div>
            <div class="summary-card">
                <div class="number success">{{ $summary['approved'] }}</div>
                <div class="label">{{ __('ui.approved') }}</div>
            </div>
            <div class="summary-card">
                <div class="number danger">{{ $summary['rejected'] }}</div>
                <div class="label">{{ __('ui.rejected') }}</div>
            </div>
            <div class="summary-card">
                <div class="number purple">{{ $summary['total_days'] }}</div>
                <div class="label">{{ __('ui.total_days') }}</div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Leave Report
            </span>
                <span class="header-badge">
                <i class="bi bi-calendar-range me-1"></i>
                {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="leaveReportTable">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.type') }}</th>
                            <th>{{ __('ui.start_date') }}</th>
                            <th>{{ __('ui.end_date') }}</th>
                            <th>Days</th>
                            <th>{{ __('ui.reason') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($leaves as $leave)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $leave->employee->first_name }} {{ $leave->employee->last_name }}
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $leave->employee->employee_id }}</div>
                                </td>
                                <td>{{ $leave->employee->department->name ?? 'N/A' }}</td>
                                <td>
            <span class="leave-type-badge {{ strtolower($leave->leaveType->name) }}">
                {{ $leave->leaveType->name }}
            </span>
                                </td>
                                <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                <td class="text-center">{{ $leave->days }}</td>
                                <td class="text-truncate" style="max-width: 150px;" title="{{ $leave->reason }}">
                                    {{ $leave->reason ?? '-' }}
                                </td>
                                <td>
            <span class="leave-status-badge {{ $leave->status }}">
                {{ ucfirst($leave->status) }}
            </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-calendar-x fs-4 d-block mb-2"></i>
                                    No leave records found for the selected period.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#leaveReportTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        });
    </script>
@endsection
