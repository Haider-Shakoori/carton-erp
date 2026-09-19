{{-- resources/views/admin/hr/reports/attendance.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.attendance_reports'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Stats Cards ─── */
        .stats-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }
        .stat-card-modern:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .stat-card-modern .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-card-modern .stat-icon.success { background: var(--success-bg); color: var(--success); }
        .stat-card-modern .stat-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .stat-card-modern .stat-icon.danger { background: var(--danger-bg); color: var(--danger); }
        .stat-card-modern .stat-icon.primary { background: var(--primary-bg); color: var(--primary); }
        .stat-card-modern .stat-icon.purple { background: #f3e8ff; color: #7c3aed; }
        .stat-card-modern .stat-icon.cyan { background: #e0f7fa; color: #06b6d4; }

        .stat-card-modern .stat-info .stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gray-800);
            line-height: 1.2;
        }
        .stat-card-modern .stat-info .stat-label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        /* ─── Filter Section ─── */
        .filter-section-modern {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .filter-section-modern .filter-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }
        .filter-section-modern .form-control,
        .filter-section-modern .form-select {
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
            background: white;
        }
        .filter-section-modern .form-control:focus,
        .filter-section-modern .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
            outline: none;
        }

        /* ─── Table Enhancements ─── */
        .table-report {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            font-size: 0.85rem;
        }
        .table-report thead th {
            padding: 0.6rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: none;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .table-report thead th:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            text-align: left;
            padding-left: 1rem;
        }
        .table-report thead th:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            text-align: right;
            padding-right: 1rem;
        }
        .table-report tbody tr {
            background: white;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }
        .table-report tbody tr:hover {
            box-shadow: var(--shadow-sm);
            background: var(--gray-50);
        }
        .table-report tbody td {
            padding: 0.6rem 1rem;
            border: none;
            vertical-align: middle;
        }
        .table-report tbody td:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            text-align: left;
            padding-left: 1rem;
        }
        .table-report tbody td:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            text-align: right;
            padding-right: 1rem;
        }

        /* ─── Employee Info ─── */
        .employee-info {
            display: flex;
            flex-direction: column;
        }
        .employee-info .employee-name {
            font-weight: 600;
            color: var(--gray-800);
        }
        .employee-info .employee-code {
            font-size: 0.7rem;
            color: var(--gray-400);
        }

        /* ─── Department Tag ─── */
        .dept-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.15rem 0.6rem;
            border-radius: 12px;
            font-size: 0.6rem;
            font-weight: 500;
            background: var(--gray-100);
            color: var(--gray-600);
        }
        .dept-tag i {
            font-size: 0.5rem;
        }

        /* ─── Status Badges ─── */
        .attendance-status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .attendance-status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .attendance-status-badge.present { background: var(--success-bg); color: var(--success); }
        .attendance-status-badge.present .dot { background: var(--success); }
        .attendance-status-badge.late { background: var(--warning-bg); color: var(--warning); }
        .attendance-status-badge.late .dot { background: var(--warning); }
        .attendance-status-badge.absent { background: var(--danger-bg); color: var(--danger); }
        .attendance-status-badge.absent .dot { background: var(--danger); }
        .attendance-status-badge.leave { background: var(--primary-bg); color: var(--primary); }
        .attendance-status-badge.leave .dot { background: var(--primary); }

        /* ─── Progress Bar ─── */
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background: var(--gray-200);
            overflow: hidden;
            min-width: 80px;
        }
        .progress-bar-custom .progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .progress-bar-custom .progress-fill.high { background: var(--success); }
        .progress-bar-custom .progress-fill.medium { background: var(--warning); }
        .progress-bar-custom .progress-fill.low { background: var(--danger); }

        .rate-display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .rate-display .rate-value {
            font-weight: 700;
            font-size: 0.85rem;
            min-width: 44px;
        }
        .rate-display .rate-value.high { color: var(--success); }
        .rate-display .rate-value.medium { color: var(--warning); }
        .rate-display .rate-value.low { color: var(--danger); }

        /* ─── Stats Numbers ─── */
        .stat-number-cell {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .stat-number-cell.success { color: var(--success); }
        .stat-number-cell.warning { color: var(--warning); }
        .stat-number-cell.danger { color: var(--danger); }
        .stat-number-cell.primary { color: var(--primary); }

        /* ─── Search Wrapper ─── */
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.8rem;
        }
        .search-wrapper .form-control {
            padding-left: 30px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            font-size: 0.8rem;
            padding: 0.3rem 0.75rem 0.3rem 30px;
            width: 200px;
            background: var(--gray-50);
            transition: var(--transition);
        }
        .search-wrapper .form-control:focus {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }

        /* ─── Empty State ─── */
        .empty-state-modern {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state-modern .empty-icon {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .empty-state-modern .empty-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-700);
        }
        .empty-state-modern .empty-desc {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .stats-grid-modern {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-section-modern .row {
                gap: 0.5rem;
            }
            .table-report {
                font-size: 0.75rem;
            }
            .table-report thead th,
            .table-report tbody td {
                padding: 0.4rem 0.5rem;
            }
            .search-wrapper .form-control {
                width: 140px;
            }
            .rate-display {
                gap: 0.4rem;
                flex-wrap: wrap;
            }
            .progress-bar-custom {
                min-width: 50px;
            }
        }
        @media (max-width: 480px) {
            .stats-grid-modern {
                grid-template-columns: 1fr;
            }
            .stat-card-modern {
                padding: 0.75rem;
            }
            .stat-card-modern .stat-number {
                font-size: 1.2rem;
            }
            .search-wrapper .form-control {
                width: 100%;
            }
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid px-3 px-md-4">
        <!-- ─── Page Header ─── -->
        <div class="page-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h1>
                        <i class="bi bi-calendar-check me-2"></i> {{ __('ui.attendance') }} <span class="accent">{{ __('ui.reports') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate and view attendance reports
                    </p>
                </div>
                <div class="report-actions d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.reports.export', ['type' => 'attendance', 'format' => 'csv']) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─── -->
        @if(isset($reportData) && count($reportData) > 0)
            <div class="stats-grid-modern">
                <div class="stat-card-modern">
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ collect($reportData)->sum('present') }}</div>
                        <div class="stat-label">Present Days</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon warning">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ collect($reportData)->sum('late') }}</div>
                        <div class="stat-label">Late Days</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ collect($reportData)->sum('absent') }}</div>
                        <div class="stat-label">Absent Days</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon primary">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ collect($reportData)->sum('leave') }}</div>
                        <div class="stat-label">Leave Days</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon purple">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ number_format(collect($reportData)->avg('attendance_rate') ?? 0, 1) }}%</div>
                        <div class="stat-label">Avg Attendance Rate</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon cyan">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ number_format(collect($reportData)->sum('total_hours'), 1) }}</div>
                        <div class="stat-label">Total Hours</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ─── Filters ─── -->
        <div class="filter-section-modern">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.start_date') }}</div>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.end_date') }}</div>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <div class="filter-label">{{ __('ui.employee') }}</div>
                    <select name="employee_id" class="form-select select2">
                        <option value="">{{ __('ui.all_employees') }}</option>
                        @if(isset($employees))
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_code ?? $employee->employee_id }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> {{ __('ui.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- ─── Report Table ─── -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> {{ __('ui.attendance_report') }}
            </span>
                <div class="d-flex align-items-center gap-3">
                <span class="header-badge">
                    <i class="bi bi-calendar-range me-1"></i>
                    {{ $startDate->format('M d, Y') }} - {{ $endDate->format('M d, Y') }}
                </span>
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control form-control-sm" id="reportSearch" placeholder="{{ __('ui.search_dots') }}">
                    </div>
                </div>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-report" id="attendanceReportTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="min-width: 160px;">{{ __('ui.employee') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.department') }}</th>
                            <th class="text-center" style="width: 80px;">{{ __('ui.present') }}</th>
                            <th class="text-center" style="width: 80px;">Late</th>
                            <th class="text-center" style="width: 80px;">{{ __('ui.absent') }}</th>
                            <th class="text-center" style="width: 80px;">Leave</th>
                            <th class="text-center" style="width: 100px;">Hours</th>
                            <th style="min-width: 150px;">{{ __('ui.attendance_rate') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($reportData as $data)
                            @php
                                $rate = $data['attendance_rate'];
                                $rateClass = $rate >= 90 ? 'high' : ($rate >= 70 ? 'medium' : 'low');
                                $rateColor = $rate >= 90 ? 'high' : ($rate >= 70 ? 'medium' : 'low');
                            @endphp
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name">{{ $data['employee']->first_name }} {{ $data['employee']->last_name }}</span>
                                        <span class="employee-code">{{ $data['employee']->employee_code ?? $data['employee']->employee_id }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($data['employee']->department)
                                        <span class="dept-tag">
                                            <i class="bi bi-building"></i>
                                            {{ $data['employee']->department->name }}
                                        </span>
                                    @else
                                        <span class="dept-tag">N/A</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="stat-number-cell success">{{ $data['present'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="stat-number-cell warning">{{ $data['late'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="stat-number-cell danger">{{ $data['absent'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="stat-number-cell primary">{{ $data['leave'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="stat-number-cell">{{ number_format($data['total_hours'], 1) }}</span>
                                </td>
                                <td>
                                    <div class="rate-display">
                                        <span class="rate-value {{ $rateColor }}">{{ $rate }}%</span>
                                        <div class="progress-bar-custom flex-grow-1">
                                            <div class="progress-fill {{ $rateClass }}" style="width: {{ $rate }}%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state-modern">
                                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="empty-title">No Attendance Records Found</div>
                                        <div class="empty-desc">No attendance records found for the selected period.</div>
                                    </div>
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
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Select2 ───
            $('.select2').select2({
                width: '100%',
                placeholder: 'Search employee...',
                allowClear: true
            });

            // ─── DataTable ───
            const table = $('#attendanceReportTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [3, 4, 5, 6, 7, 8] }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                },
                dom: 't'
            });

            // ─── Custom Search ───
            $('#reportSearch').on('keyup', function() {
                table.search($(this).val()).draw();
            });
        });
    </script>
@endsection
