{{-- resources/views/admin/hr/reports/payroll.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.payroll_reports'))

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
        .stat-card-modern .stat-icon.primary { background: var(--primary-bg); color: var(--primary); }
        .stat-card-modern .stat-icon.success { background: var(--success-bg); color: var(--success); }
        .stat-card-modern .stat-icon.danger { background: var(--danger-bg); color: var(--danger); }
        .stat-card-modern .stat-icon.warning { background: var(--warning-bg); color: var(--warning); }
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

        /* ─── Payroll Status Badges ─── */
        .payroll-status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .payroll-status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .payroll-status-badge.draft { background: var(--warning-bg); color: var(--warning); }
        .payroll-status-badge.draft .dot { background: var(--warning); }
        .payroll-status-badge.processed { background: var(--info-bg); color: var(--info); }
        .payroll-status-badge.processed .dot { background: var(--info); }
        .payroll-status-badge.paid { background: var(--success-bg); color: var(--success); }
        .payroll-status-badge.paid .dot { background: var(--success); }
        .payroll-status-badge.cancelled { background: var(--danger-bg); color: var(--danger); }
        .payroll-status-badge.cancelled .dot { background: var(--danger); }

        /* ─── Amount Styles ─── */
        .amount-positive {
            color: var(--gray-700);
        }
        .amount-negative {
            color: var(--danger);
        }
        .amount-net {
            font-weight: 700;
            color: var(--primary);
        }

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

        /* ─── Report Actions ─── */
        .report-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
                        <i class="bi bi-currency-dollar me-2"></i> {{ __('ui.payroll') }} <span class="accent">{{ __('ui.reports') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate and view payroll reports
                        <span class="text-muted ms-2">
                        {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                    </span>
                    </p>
                </div>
                <div class="report-actions">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.reports.export', ['type' => 'payroll', 'format' => 'csv']) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─── -->
        @if(isset($summary) && isset($payrolls) && $payrolls->count() > 0)
            <div class="stats-grid-modern">
                <div class="stat-card-modern">
                    <div class="stat-icon primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_gross_salary'] ?? 0, 0) }}</div>
                        <div class="stat-label">Total Gross</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon success">
                        <i class="bi bi-wallet"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_net_salary'] ?? 0, 0) }}</div>
                        <div class="stat-label">Total Net</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon danger">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_deductions'] ?? 0, 0) }}</div>
                        <div class="stat-label">{{ __('ui.total_deductions') }}</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon warning">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $summary['total_employees'] ?? 0 }}</div>
                        <div class="stat-label">{{ __('ui.employees') }}</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon purple">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ $summary['paid_count'] ?? 0 }}</div>
                        <div class="stat-label">Paid</div>
                    </div>
                </div>
                <div class="stat-card-modern">
                    <div class="stat-icon cyan">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number">{{ ($summary['pending_count'] ?? 0) + ($summary['processed_count'] ?? 0) }}</div>
                        <div class="stat-label">Pending/Processed</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ─── Filters ─── -->
        <div class="filter-section-modern">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.month') }}</div>
                    <select name="month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.year') }}</div>
                    <select name="year" class="form-select">
                        @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
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
                <i class="bi bi-list-ul me-1"></i> Payroll Report
                <span class="text-muted" style="font-weight: 400; font-size: 0.8rem;">
                    - {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                </span>
            </span>
                <div class="d-flex align-items-center gap-3">
                <span class="header-badge">
                    <i class="bi bi-people me-1"></i> {{ isset($payrolls) ? $payrolls->count() : 0 }} records
                </span>
                    <div class="search-wrapper">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control form-control-sm" id="reportSearch" placeholder="{{ __('ui.search_dots') }}">
                    </div>
                </div>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-report" id="payrollReportTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="min-width: 120px;">{{ __('ui.payroll_number') }}</th>
                            <th style="min-width: 160px;">{{ __('ui.employee') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.department') }}</th>
                            <th class="text-end" style="min-width: 100px;">Basic</th>
                            <th class="text-end" style="min-width: 100px;">{{ __('ui.allowances') }}</th>
                            <th class="text-end" style="min-width: 100px;">{{ __('ui.deductions') }}</th>
                            <th class="text-end" style="min-width: 100px;">Gross</th>
                            <th class="text-end" style="min-width: 100px;">Net</th>
                            <th style="min-width: 100px;">{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($payrolls as $payroll)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $payroll->payroll_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name">{{ $payroll->employee->first_name ?? '' }} {{ $payroll->employee->last_name ?? '' }}</span>
                                        <span class="employee-code">{{ $payroll->employee->employee_code ?? $payroll->employee->employee_id ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($payroll->employee && $payroll->employee->department)
                                        <span class="dept-tag">
                                            <i class="bi bi-building"></i>
                                            {{ $payroll->employee->department->name }}
                                        </span>
                                    @else
                                        <span class="dept-tag">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="amount-positive">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->basic_salary ?? 0, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-positive">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(
                                        ($payroll->housing_allowance ?? 0) +
                                        ($payroll->transport_allowance ?? 0) +
                                        ($payroll->medical_allowance ?? 0) +
                                        ($payroll->other_allowances ?? 0), 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-negative">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->total_deductions ?? 0, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-positive">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->total_earnings ?? 0, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-net">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($payroll->net_salary ?? 0, 2) }}</span>
                                </td>
                                <td>
                                    <span class="payroll-status-badge {{ $payroll->status ?? 'draft' }}">
                                        <span class="dot"></span>
                                        {{ ucfirst($payroll->status ?? 'Draft') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state-modern">
                                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="empty-title">No Payroll Records Found</div>
                                        <div class="empty-desc">No payroll records found for {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                        @if(isset($payrolls) && $payrolls->count() > 0)
                            <tfoot>
                            <tr class="fw-bold" style="background: var(--gray-50);">
                                <td colspan="4" class="text-end">{{ __('ui.totals_colon') }}</td>
                                <td class="text-end">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_basic_salary'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_allowances'] ?? 0, 2) }}</td>
                                <td class="text-end text-danger">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_deductions'] ?? 0, 2) }}</td>
                                <td class="text-end">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_gross_salary'] ?? 0, 2) }}</td>
                                <td class="text-end" style="color: var(--primary);">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_net_salary'] ?? 0, 2) }}</td>
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
            const table = $('#payrollReportTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [1, 9] }
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
