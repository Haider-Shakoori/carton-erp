{{-- resources/views/admin/hr/reports/advances.blade.php --}}
@extends('layouts.admin.base')

@section('title', 'Advances & Loans Reports')

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Stats Cards ─── */
        .stats-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
        .stat-card-modern .stat-icon.warning { background: var(--warning-bg); color: var(--warning); }
        .stat-card-modern .stat-icon.success { background: var(--success-bg); color: var(--success); }
        .stat-card-modern .stat-icon.danger { background: var(--danger-bg); color: var(--danger); }
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

        /* ─── Type Badges ─── */
        .advance-type-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .advance-type-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .advance-type-badge.advance { background: var(--primary-bg); color: var(--primary); }
        .advance-type-badge.advance .dot { background: var(--primary); }
        .advance-type-badge.loan { background: var(--warning-bg); color: var(--warning); }
        .advance-type-badge.loan .dot { background: var(--warning); }

        /* ─── Status Badges ─── */
        .advance-status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .advance-status-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .advance-status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .advance-status-badge.pending .dot { background: var(--warning); }
        .advance-status-badge.approved { background: var(--info-bg); color: var(--info); }
        .advance-status-badge.approved .dot { background: var(--info); }
        .advance-status-badge.rejected { background: var(--danger-bg); color: var(--danger); }
        .advance-status-badge.rejected .dot { background: var(--danger); }
        .advance-status-badge.paid { background: var(--success-bg); color: var(--success); }
        .advance-status-badge.paid .dot { background: var(--success); }

        /* ─── Amount Styles ─── */
        .amount-positive {
            color: var(--gray-700);
        }
        .amount-remaining {
            font-weight: 600;
        }
        .amount-remaining.has-remaining {
            color: var(--warning);
        }
        .amount-remaining.fully-paid {
            color: var(--success);
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
                        <i class="bi bi-coin me-2"></i> {{ __('ui.advances_loans') }} <span class="accent">{{ __('ui.reports') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate and view advances & loans reports
                    </p>
                </div>
                <div class="report-actions">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.reports.export', ['type' => 'advances', 'format' => 'csv']) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- ─── Stats ─── -->
        <div class="stats-grid-modern">
            <div class="stat-card-modern">
                <div class="stat-icon primary">
                    <i class="bi bi-list-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $summary['total'] }}</div>
                    <div class="stat-label">{{ __('ui.total_requests') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon warning">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $summary['pending'] }}</div>
                    <div class="stat-label">{{ __('ui.pending') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $summary['approved'] }}</div>
                    <div class="stat-label">{{ __('ui.approved') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $summary['rejected'] }}</div>
                    <div class="stat-label">{{ __('ui.rejected') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon purple">
                    <i class="bi bi-cash"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $summary['paid'] }}</div>
                    <div class="stat-label">Paid</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon cyan">
                    <i class="bi bi-coin"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['total_amount'], 0) }}</div>
                    <div class="stat-label">{{ __('ui.total_amount') }}</div>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="bi bi-arrow-left"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($summary['remaining_amount'], 0) }}</div>
                    <div class="stat-label">{{ __('ui.remaining') }}</div>
                </div>
            </div>
        </div>

        <!-- ─── Filters ─── -->
        <div class="filter-section-modern">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.start_date') }}</div>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.end_date') }}</div>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <div class="filter-label">{{ __('ui.type') }}</div>
                    <select name="type" class="form-select">
                        <option value="">{{ __('ui.all_types') }}</option>
                        <option value="advance" {{ request('type') == 'advance' ? 'selected' : '' }}>{{ __('ui.advance') }}</option>
                        <option value="loan" {{ request('type') == 'loan' ? 'selected' : '' }}>Loan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="filter-label">{{ __('ui.status') }}</div>
                    <select name="status" class="form-select">
                        <option value="">{{ __('ui.all_status') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('ui.pending') }}</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('ui.approved') }}</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('ui.rejected') }}</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-12">
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
                <i class="bi bi-list-ul me-1"></i> Advances & Loans Report
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
                    <table class="table-report" id="advanceReportTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="min-width: 160px;">{{ __('ui.employee') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.department') }}</th>
                            <th style="min-width: 100px;">{{ __('ui.type') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.amount') }}</th>
                            <th class="text-end" style="min-width: 120px;">{{ __('ui.remaining') }}</th>
                            <th style="min-width: 120px;">{{ __('ui.request_date') }}</th>
                            <th style="min-width: 110px;">{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($advances as $advance)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="employee-info">
                                        <span class="employee-name">{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}</span>
                                        <span class="employee-code">{{ $advance->employee->employee_code ?? $advance->employee->employee_id }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($advance->employee->department)
                                        <span class="dept-tag">
                                            <i class="bi bi-building"></i>
                                            {{ $advance->employee->department->name }}
                                        </span>
                                    @else
                                        <span class="dept-tag">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="advance-type-badge {{ $advance->type }}">
                                        <span class="dot"></span>
                                        {{ ucfirst($advance->type) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-positive">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($advance->amount, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="amount-remaining {{ $advance->remaining_amount > 0 ? 'has-remaining' : 'fully-paid' }}">
                                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($advance->remaining_amount, 2) }}
                                    </span>
                                </td>
                                <td>{{ $advance->request_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="advance-status-badge {{ $advance->status }}">
                                        <span class="dot"></span>
                                        {{ ucfirst($advance->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state-modern">
                                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                                        <div class="empty-title">No Records Found</div>
                                        <div class="empty-desc">No advances or loans found for the selected period.</div>
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
            const table = $('#advanceReportTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [3, 7] }
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
