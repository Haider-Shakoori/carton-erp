{{-- resources/views/admin/hr/attendance/report.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.attendance_report'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <style>
        /* ─── Report Styles ─── */
        .report-header {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .report-header .report-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
        }
        .report-header .report-subtitle {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        .filter-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .filter-section .filter-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .summary-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            padding: 1rem;
            text-align: center;
        }
        .summary-card .number {
            font-size: 1.5rem;
            font-weight: 800;
        }
        .summary-card .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .summary-card .number.present { color: var(--success); }
        .summary-card .number.absent { color: var(--danger); }
        .summary-card .number.leave { color: var(--primary); }
        .summary-card .number.half_day { color: var(--warning); }
        .summary-card .number.holiday { color: var(--gray-400); }
        .summary-card .number.total { color: var(--gray-800); }

        .attendance-status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .attendance-status-badge.present { background: #d1fae5; color: #065f46; }
        .attendance-status-badge.absent { background: #fee2e2; color: #991b1b; }
        .attendance-status-badge.half_day { background: #fef3c7; color: #92400e; }
        .attendance-status-badge.leave { background: #dbeafe; color: #1e40af; }
        .attendance-status-badge.holiday { background: #f3f4f6; color: #6b7280; }

        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background: var(--gray-200);
            overflow: hidden;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .progress-bar-custom .progress-fill.high { background: var(--success); }
        .progress-bar-custom .progress-fill.medium { background: var(--warning); }
        .progress-bar-custom .progress-fill.low { background: var(--danger); }

        .report-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .report-actions .btn {
            padding: 0.4rem 1rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.8rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        .report-actions .btn-primary {
            background: var(--primary);
            color: white;
        }
        .report-actions .btn-success {
            background: var(--success);
            color: white;
        }
        .report-actions .btn-outline-secondary {
            background: transparent;
            color: var(--gray-600);
            border: 1.5px solid var(--gray-200);
        }

        .employee-name-cell {
            font-weight: 600;
            color: var(--gray-800);
        }
        .employee-code-cell {
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
                        <i class="bi bi-file-earmark-bar-graph me-2"></i> {{ __('ui.attendance') }} <span class="accent">Report</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-range me-1"></i> {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
                    </p>
                </div>
                <div class="report-actions">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.attendance.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.start_date') }}</div>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.end_date') }}</div>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.department') }}</div>
                    <select name="department_id" class="form-select">
                        <option value="">{{ __('ui.all_departments') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> {{ __('ui.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="number total">{{ $totalEmployees }}</div>
                <div class="label">{{ __('ui.total_employees') }}</div>
            </div>
            <div class="summary-card">
                <div class="number present">{{ $totalPresent }}</div>
                <div class="label">Total Present</div>
            </div>
            <div class="summary-card">
                <div class="number absent">{{ $totalAbsent }}</div>
                <div class="label">Total Absent</div>
            </div>
            <div class="summary-card">
                <div class="number leave">{{ $totalLeave }}</div>
                <div class="label">Total Leave</div>
            </div>
            <div class="summary-card">
                <div class="number half_day">{{ $totalHalfDay }}</div>
                <div class="label">Total Half Day</div>
            </div>
            <div class="summary-card">
                <div class="number holiday">{{ $totalHoliday }}</div>
                <div class="label">Total Holiday</div>
            </div>
        </div>

        <!-- Report Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> Attendance Details
            </span>
                <span class="header-badge">
                <i class="bi bi-calendar-range me-1"></i>
                {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="reportTable">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th class="text-center">{{ __('ui.present') }}</th>
                            <th class="text-center">{{ __('ui.absent') }}</th>
                            <th class="text-center">{{ __('ui.half_day') }}</th>
                            <th class="text-center">Leave</th>
                            <th class="text-center">Holiday</th>
                            <th class="text-center">{{ __('ui.total_days') }}</th>
                            <th class="text-center">{{ __('ui.rate') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reportData as $data)
                            @php
                                $totalDays = $data['present'] + $data['absent'] + $data['half_day'] + $data['leave'] + $data['holiday'];
                                $rate = $totalDays > 0 ? round(($data['present'] / $totalDays) * 100, 2) : 0;
                                $rateClass = $rate >= 90 ? 'high' : ($rate >= 70 ? 'medium' : 'low');
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="employee-name-cell">{{ $data['employee']->first_name }} {{ $data['employee']->last_name }}</div>
                                    <div class="employee-code-cell">{{ $data['employee']->employee_code }}</div>
                                </td>
                                <td>{{ $data['employee']->department->name ?? 'N/A' }}</td>
                                <td class="text-center"><span class="fw-bold text-success">{{ $data['present'] }}</span></td>
                                <td class="text-center"><span class="fw-bold text-danger">{{ $data['absent'] }}</span></td>
                                <td class="text-center"><span class="fw-bold text-warning">{{ $data['half_day'] }}</span></td>
                                <td class="text-center"><span class="fw-bold text-primary">{{ $data['leave'] }}</span></td>
                                <td class="text-center"><span class="fw-bold text-secondary">{{ $data['holiday'] }}</span></td>
                                <td class="text-center"><span class="fw-bold">{{ $totalDays }}</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold">{{ $rate }}%</span>
                                        <div class="progress-bar-custom flex-grow-1">
                                            <div class="progress-fill {{ $rateClass }}" style="width: {{ $rate }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No attendance records found for the selected period.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        @if(count($reportData) > 0)
                            <tfoot>
                            <tr class="fw-bold" style="background: var(--gray-50);">
                                <td colspan="3" class="text-end">{{ __('ui.totals_colon') }}</td>
                                <td class="text-center text-success">{{ $totalPresent }}</td>
                                <td class="text-center text-danger">{{ $totalAbsent }}</td>
                                <td class="text-center text-warning">{{ $totalHalfDay }}</td>
                                <td class="text-center text-primary">{{ $totalLeave }}</td>
                                <td class="text-center text-secondary">{{ $totalHoliday }}</td>
                                <td class="text-center">{{ $totalEmployees * $daysInMonth }}</td>
                                <td></td>
                            </tr>
                            </tfoot>
                        @endif
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
            $('#reportTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                columnDefs: [
                    { orderable: false, targets: [9] }
                ]
            });
        });
    </script>
@endsection
