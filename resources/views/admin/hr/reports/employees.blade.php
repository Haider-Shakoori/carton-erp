{{-- resources/views/admin/hr/reports/employees.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.employee_reports'))

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
        .report-summary .summary-card .number.primary { color: var(--primary); }
        .report-summary .summary-card .number.success { color: var(--success); }
        .report-summary .summary-card .number.danger { color: var(--danger); }
        .report-summary .summary-card .number.warning { color: var(--warning); }

        .badge-status {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .badge-status.active { background: var(--success-bg); color: var(--success); }
        .badge-status.inactive { background: var(--danger-bg); color: var(--danger); }

        .report-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .employee-avatar-small {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
        .employee-avatar-small.placeholder {
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
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
                        <i class="bi bi-people me-2"></i> {{ __('ui.employee') }} <span class="accent">{{ __('ui.reports') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> Generate and view employee reports
                    </p>
                </div>
                <div class="report-actions">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                    <a href="{{ route('admin.hr.reports.export', ['type' => 'employees', 'format' => 'csv']) }}" class="btn btn-outline-success">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="report-summary">
            <div class="summary-card">
                <div class="number primary">{{ $employees->count() }}</div>
                <div class="label">{{ __('ui.total_employees') }}</div>
            </div>
            <div class="summary-card">
                <div class="number success">{{ $employees->where('is_active', true)->count() }}</div>
                <div class="label">{{ __('ui.active') }}</div>
            </div>
            <div class="summary-card">
                <div class="number danger">{{ $employees->where('is_active', false)->count() }}</div>
                <div class="label">{{ __('ui.inactive') }}</div>
            </div>
            <div class="summary-card">
                <div class="number warning">{{ $employees->groupBy('department_id')->count() }}</div>
                <div class="label">{{ __('ui.departments') }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="report-filters">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.department') }}</label>
                    <select name="department_id" class="form-select select2">
                        <option value="">{{ __('ui.all_departments') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('ui.all') }}</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('ui.active') }}</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('ui.inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('ui.search') }}</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('ui.search_name_id') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-1"></i> {{ __('ui.apply_filters') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Table -->
        <div class="table-card">
            <div class="card-header-custom">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-1"></i> {{ __('ui.employee_list') }}
            </span>
                <span class="header-badge">
                <i class="bi bi-database me-1"></i> Total: {{ $employees->count() }}
            </span>
            </div>
            <div class="p-3">
                <div class="table-responsive">
                    <table class="table-ledger" id="employeeReportTable">
                        <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 50px;">{{ __('ui.avatar') }}</th>
                            <th>{{ __('ui.employee_id') }}</th>
                            <th>{{ __('ui.name') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.designation') }}</th>
                            <th>{{ __('ui.email') }}</th>
                            <th>{{ __('ui.phone') }}</th>
                            <th>{{ __('ui.salary') }}</th>
                            <th>{{ __('ui.status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($employees as $employee)
                            <tr>
                                <td class="num-cell">{{ $loop->iteration }}</td>
                                <td>
                                    @if ($employee->profile_image)
                                        <img src="{{ Storage::url($employee->profile_image) }}" class="employee-avatar-small">
                                    @else
                                        <div class="employee-avatar-small placeholder">
                                            {{ strtoupper(substr($employee->first_name, 0, 1)) }}{{ strtoupper(substr($employee->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $employee->employee_id }}</td>
                                <td class="fw-semibold">{{ $employee->first_name }} {{ $employee->last_name }}</td>
                                <td>{{ $employee->department->name ?? 'N/A' }}</td>
                                <td>{{ $employee->designation->name ?? 'N/A' }}</td>
                                <td>{{ $employee->email }}</td>
                                <td>{{ $employee->phone ?? '-' }}</td>
                                <td class="num-cell">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($employee->basic_salary ?? 0, 2) }}</td>
                                <td>
                                <span class="badge-status {{ $employee->is_active ? 'active' : 'inactive' }}">
                                    {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                </td>
                            </tr>
                        @endforeach
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
            $('.select2').select2({ width: '100%' });

            $('#employeeReportTable').DataTable({
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
