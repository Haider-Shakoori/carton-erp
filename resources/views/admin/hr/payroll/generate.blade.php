{{-- resources/views/admin/hr/payroll/generate.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.generate_payroll'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        .form-section {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section .section-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-100);
        }
        .payroll-summary {
            background: var(--primary-bg);
            border-radius: var(--radius-sm);
            padding: 1rem;
        }
        .payroll-summary .amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        .payroll-summary .label {
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .employee-payroll-row {
            transition: var(--transition);
        }
        .employee-payroll-row:hover {
            background: var(--gray-50);
        }
        .employee-payroll-row .employee-name {
            font-weight: 600;
        }
        .employee-payroll-row .employee-detail {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .salary-input {
            max-width: 150px;
            display: inline-block;
        }
        .salary-input input {
            text-align: right;
        }
        .status-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge.processed { background: var(--info-bg); color: var(--info); }
        .status-badge.paid { background: var(--success-bg); color: var(--success); }

        .month-selector {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .month-selector .form-select,
        .month-selector .form-control {
            width: auto;
            min-width: 150px;
        }
        .attendance-summary {
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .attendance-summary .badge {
            font-size: 0.6rem;
        }
        .select-all-checkbox {
            cursor: pointer;
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

        /* ─── Table Enhancements ─── */
        .table-payroll {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            font-size: 0.85rem;
        }
        .table-payroll thead th {
            padding: 0.6rem 1rem;
            background: var(--gray-50);
            color: var(--gray-500);
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: none;
            white-space: nowrap;
        }
        .table-payroll thead th:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-payroll thead th:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }
        .table-payroll tbody tr {
            background: white;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }
        .table-payroll tbody tr:hover {
            box-shadow: var(--shadow-sm);
            background: var(--gray-50);
        }
        .table-payroll tbody td {
            padding: 0.6rem 1rem;
            border: none;
            vertical-align: middle;
        }
        .table-payroll tbody td:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }
        .table-payroll tbody td:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

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

        .salary-amount {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.85rem;
        }

        .net-salary-amount {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .table-payroll {
                font-size: 0.75rem;
            }
            .table-payroll thead th,
            .table-payroll tbody td {
                padding: 0.4rem 0.5rem;
            }
            .salary-input {
                max-width: 100px;
            }
            .salary-input input {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }
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
                        <i class="bi bi-plus-circle me-2"></i> Generate <span class="accent">{{ __('ui.payroll') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-month me-1"></i> Generate payroll for {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.hr.payroll.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

        <!-- Month Selector -->
        <div class="card-modern mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.hr.payroll.generate') }}" class="month-selector">
                    <div>
                        <label class="form-label mb-0">{{ __('ui.month') }}</label>
                        <select name="month" class="form-select">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-0">{{ __('ui.year') }}</label>
                        <select name="year" class="form-select">
                            @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div style="padding-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Load
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid mb-4">
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.employees') }}</div>
                <div class="stat-value">{{ count($payrollData) }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon green">
                        <i class="bi bi-cash"></i>
                    </div>
                </div>
                <div class="stat-label">Total Net Salary</div>
                <div class="stat-value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(collect($payrollData)->sum('net_salary'), 0) }}</div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-top">
                    <div class="stat-icon red">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                </div>
                <div class="stat-label">{{ __('ui.total_deductions') }}</div>
                <div class="stat-value">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(collect($payrollData)->sum('deductions'), 0) }}</div>
            </div>
        </div>

        <!-- Payroll Form -->
        <form id="payrollForm" method="POST" action="{{ route('admin.hr.payroll.store') }}">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="form-section">
                <div class="section-title">
                    <i class="bi bi-list-ul me-1"></i> Employee Payroll Details
                    <span class="ms-3 text-muted" style="font-size: 0.7rem; font-weight: 400;">
                    <i class="bi bi-info-circle"></i> Review and adjust employee salaries
                </span>
                </div>

                <div class="table-responsive">
                    <table class="table-payroll" id="payrollTable">
                        <thead>
                        <tr>
                            <th style="width: 30px;">
                                <input type="checkbox" class="select-all-checkbox" id="selectAll">
                            </th>
                            <th>{{ __('ui.employee') }}</th>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.attendance') }}</th>
                            <th class="text-end">{{ __('ui.basic_salary') }}</th>
                            <th class="text-end">{{ __('ui.deductions') }}</th>
                            <th class="text-end">{{ __('ui.net_salary') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($payrollData as $index => $data)
                            <tr class="employee-payroll-row">
                                <td>
                                    <input type="checkbox" name="payrolls[{{ $index }}][selected]" value="1" checked class="employee-checkbox">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($data['employee']->profile_image)
                                            <img src="{{ Storage::url($data['employee']->profile_image) }}" class="employee-avatar-small">
                                        @else
                                            <div class="employee-avatar-small placeholder">
                                                {{ strtoupper(substr($data['employee']->first_name, 0, 1)) }}{{ strtoupper(substr($data['employee']->last_name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="employee-name">{{ $data['employee']->first_name }} {{ $data['employee']->last_name }}</div>
                                            <div class="employee-detail">{{ $data['employee']->employee_code ?? $data['employee']->employee_id }}</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="payrolls[{{ $index }}][employee_id]" value="{{ $data['employee']->id }}">
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
                                <td>
                                    <div class="attendance-summary">
                                        <span class="badge bg-success">P: {{ $data['attendance_summary']['present'] ?? 0 }}</span>
                                        <span class="badge bg-warning">L: {{ $data['attendance_summary']['late'] ?? 0 }}</span>
                                        <span class="badge bg-danger">A: {{ $data['attendance_summary']['absent'] ?? 0 }}</span>
                                        <span class="badge bg-primary">V: {{ $data['attendance_summary']['leave'] ?? 0 }}</span>
                                        <div class="mt-1 text-muted">
                                            <i class="bi bi-clock"></i> {{ number_format($data['attendance_summary']['total_hours'] ?? 0, 1) }} hrs
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="salary-amount">{{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($data['basic_salary'] ?? 0, 2) }}</span>
                                    <input type="hidden" name="payrolls[{{ $index }}][basic_salary]" value="{{ $data['basic_salary'] ?? 0 }}">
                                </td>
                                <td class="text-end">
                                    <div class="salary-input">
                                        <input type="number" class="form-control form-control-sm deduction-amount"
                                               name="payrolls[{{ $index }}][deductions]"
                                               value="{{ $data['deductions'] ?? 0 }}"
                                               step="0.01" min="0" data-index="{{ $index }}">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="net-salary-amount net-salary-{{ $index }}">
                                        {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format($data['net_salary'] ?? 0, 2) }}
                                    </span>
                                    <input type="hidden" name="payrolls[{{ $index }}][net_salary]" class="net-salary-input" value="{{ $data['net_salary'] ?? 0 }}">
                                    <input type="hidden" name="payrolls[{{ $index }}][gross_salary]" value="{{ $data['net_salary'] ?? 0 }}">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                        <i class="bi bi-save me-1"></i> {{ __('ui.generate_payroll') }}
                    </button>
                </div>
                <div class="col-lg-4">
                    <div class="payroll-summary text-center">
                        <div class="label">Total Net Payroll</div>
                        <div class="amount" id="totalNetPayroll">
                            {{ $defaultCurrency?->symbol ?? '$' }}{{ number_format(collect($payrollData)->sum('net_salary'), 2) }}
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-secondary" id="selectedCount">{{ count($payrollData) }}</span>
                            <span class="text-muted" style="font-size: 0.7rem;"> employees selected</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#payrollTable').DataTable({
                pageLength: 15,
                lengthChange: true,
                ordering: true,
                searching: true,
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 3, 4, 5, 6] }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search employees...',
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });

            // Select All
            $('#selectAll').on('change', function() {
                $('.employee-checkbox').prop('checked', $(this).is(':checked'));
                updateSummary();
            });

            $('.employee-checkbox').on('change', function() {
                updateSummary();
            });

            // Calculate Net Salary
            function calculateNetSalary(index) {
                const deductions = parseFloat($(`input[name="payrolls[${index}][deductions]"]`).val()) || 0;
                const basicSalary = parseFloat($(`input[name="payrolls[${index}][basic_salary]"]`).val()) || 0;
                const net = basicSalary - deductions;

                $(`.net-salary-${index}`).text('{{ $defaultCurrency?->symbol ?? '$' }}' + net.toFixed(2));
                $(`input[name="payrolls[${index}][net_salary]"]`).val(net);
                $(`input[name="payrolls[${index}][gross_salary]"]`).val(net);

                return net;
            }

            // Update total summary
            function updateSummary() {
                let total = 0;
                let selected = 0;

                $('.employee-checkbox:checked').each(function() {
                    const row = $(this).closest('tr');
                    const index = row.find('.deduction-amount').data('index');
                    if (index !== undefined) {
                        const net = calculateNetSalary(index);
                        total += net;
                        selected++;
                    }
                });

                $('#totalNetPayroll').text('{{ $defaultCurrency?->symbol ?? '$' }}' + total.toFixed(2));
                $('#selectedCount').text(selected);
            }

            // Recalculate on input change
            $(document).on('input', '.deduction-amount', function() {
                const index = $(this).data('index');
                calculateNetSalary(index);
                updateSummary();
            });

            // Form submit
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();

                const selectedCount = $('.employee-checkbox:checked').length;
                if (selectedCount === 0) {
                    Swal.fire('Error', 'Please select at least one employee for payroll.', 'error');
                    return;
                }

                const form = $(this);
                const submitBtn = $('#submitBtn');

                // Enable all inputs before submit
                $('.employee-checkbox').prop('disabled', false);
                $('.employee-checkbox:not(:checked)').each(function() {
                    $(this).closest('tr').find('input, select').prop('disabled', true);
                });

                // Ensure selected checkboxes are included
                $('.employee-checkbox:checked').each(function() {
                    $(this).val('1');
                    $(this).prop('disabled', false);
                });

                // Validate deductions don't exceed basic salary
                let hasError = false;
                $('.employee-checkbox:checked').each(function() {
                    const row = $(this).closest('tr');
                    const index = row.find('.deduction-amount').data('index');
                    const basicSalary = parseFloat($(`input[name="payrolls[${index}][basic_salary]"]`).val()) || 0;
                    const deductions = parseFloat(row.find('.deduction-amount').val()) || 0;

                    if (deductions > basicSalary) {
                        hasError = true;
                        row.find('.deduction-amount').addClass('is-invalid');
                    } else {
                        row.find('.deduction-amount').removeClass('is-invalid');
                    }
                });

                if (hasError) {
                    Swal.fire('Error', 'Deductions cannot exceed basic salary.', 'error');
                    return;
                }

                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Generating...'
                );

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: form.serialize(),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success').then(() => {
                                window.location.href = '{{ route("admin.hr.payroll.index") }}';
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Something went wrong.', 'error');
                            submitBtn.prop('disabled', false).html(
                                '<i class="bi bi-save me-1"></i> Generate Payroll'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to generate payroll.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        submitBtn.prop('disabled', false).html(
                            '<i class="bi bi-save me-1"></i> Generate Payroll'
                        );
                    }
                });
            });

            // Initial summary calculation
            updateSummary();
        });
    </script>
@endsection
