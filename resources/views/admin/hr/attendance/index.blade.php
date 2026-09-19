{{-- resources/views/admin/hr/attendance/index.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.daily_attendance'))

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
    <style>
        /* ─── Page Styles ─── */
        .attendance-container {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            overflow: hidden;
        }

        /* ─── Filters Bar ─── */
        .filters-bar {
            background: var(--gray-50);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        .filters-bar .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .filters-bar .filter-group label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .filters-bar .filter-group .form-control,
        .filters-bar .filter-group .form-select {
            min-width: 150px;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }
        .filters-bar .filter-group .form-control:focus,
        .filters-bar .filter-group .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.08);
        }
        .filters-bar .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-left: auto;
        }
        .filters-bar .filter-actions .btn {
            padding: 0.4rem 1rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.8rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        .filters-bar .filter-actions .btn-primary {
            background: var(--primary);
            color: white;
        }
        .filters-bar .filter-actions .btn-primary:hover {
            background: var(--primary-dark);
        }
        .filters-bar .filter-actions .btn-success {
            background: var(--success);
            color: white;
        }
        .filters-bar .filter-actions .btn-success:hover {
            background: #059669;
        }
        .filters-bar .filter-actions .btn-outline-secondary {
            background: transparent;
            color: var(--gray-600);
            border: 1.5px solid var(--gray-200);
        }
        .filters-bar .filter-actions .btn-outline-secondary:hover {
            background: var(--gray-50);
        }

        /* ─── Stats Bar ─── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border-bottom: 1px solid var(--gray-200);
        }
        .stats-bar .stat-item {
            text-align: center;
        }
        .stats-bar .stat-item .stat-number {
            font-size: 1.2rem;
            font-weight: 800;
        }
        .stats-bar .stat-item .stat-label {
            font-size: 0.6rem;
            color: var(--gray-500);
            text-transform: uppercase;
            font-weight: 600;
        }
        .stats-bar .stat-item .stat-number.present { color: var(--success); }
        .stats-bar .stat-item .stat-number.absent { color: var(--danger); }
        .stats-bar .stat-item .stat-number.leave { color: var(--primary); }
        .stats-bar .stat-item .stat-number.half_day { color: var(--warning); }

        /* ─── Table Styles ─── */
        .attendance-table-wrap {
            padding: 0 1.5rem 1.5rem;
            overflow-x: auto;
            position: relative;
        }
        .attendance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            font-size: 0.85rem;
        }
        .attendance-table thead th {
            padding: 0.6rem 0.5rem;
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
        .attendance-table thead th:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            text-align: left;
            padding-left: 1rem;
        }
        .attendance-table thead th:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            text-align: right;
            padding-right: 1rem;
        }
        .attendance-table tbody tr {
            background: white;
            transition: var(--transition);
            border-radius: var(--radius-sm);
        }
        .attendance-table tbody tr:hover {
            box-shadow: var(--shadow-sm);
        }
        .attendance-table tbody td {
            padding: 0.5rem 0.5rem;
            border: none;
            vertical-align: middle;
            text-align: center;
        }
        .attendance-table tbody td:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            text-align: left;
            padding-left: 1rem;
        }
        .attendance-table tbody td:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            text-align: right;
            padding-right: 1rem;
        }
        .attendance-table tbody td .employee-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .attendance-table tbody td .employee-info .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.6rem;
            flex-shrink: 0;
        }
        .attendance-table tbody td .employee-info .name {
            font-weight: 600;
            color: var(--gray-800);
        }
        .attendance-table tbody td .employee-info .code {
            font-size: 0.7rem;
            color: var(--gray-400);
        }

        /* ─── Toggle Checkbox ─── */
        .toggle-checkbox {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .toggle-checkbox input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-checkbox .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gray-300);
            transition: .3s;
            border-radius: 24px;
        }
        .toggle-checkbox .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .toggle-checkbox input:checked + .slider {
            background: var(--success);
        }
        .toggle-checkbox input:checked + .slider:before {
            transform: translateX(20px);
        }
        .toggle-checkbox input:disabled + .slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .toggle-checkbox .status-label {
            font-size: 0.6rem;
            font-weight: 600;
            margin-left: 0.3rem;
            color: var(--gray-400);
        }
        .toggle-checkbox input:checked + .slider + .status-label {
            color: var(--success);
        }

        /* ─── Day Columns ─── */
        .day-column {
            min-width: 40px;
        }
        .day-column .day-label {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--gray-400);
            display: block;
        }
        .day-column .day-label.weekend {
            color: var(--danger);
        }
        .day-column .day-label.today {
            color: var(--primary);
            font-weight: 700;
        }

        /* ─── Empty State ─── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state .empty-icon {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }
        .empty-state .empty-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--gray-700);
        }
        .empty-state .empty-desc {
            color: var(--gray-500);
            font-size: 0.9rem;
        }

        /* ─── Loading Overlay ─── */
        .loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: var(--radius);
        }
        .loading-overlay .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .status-select {
            width: 100px;
            font-size: 0.7rem;
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--gray-200);
            background: white;
        }
        .status-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filters-bar .filter-actions {
                margin-left: 0;
                flex-wrap: wrap;
            }
            .filters-bar .filter-group .form-control,
            .filters-bar .filter-group .form-select {
                min-width: 100%;
            }
            .stats-bar {
                grid-template-columns: repeat(3, 1fr);
            }
            .attendance-table-wrap {
                padding: 0 0.5rem 1rem;
            }
            .attendance-table thead th,
            .attendance-table tbody td {
                padding: 0.3rem 0.2rem;
                font-size: 0.7rem;
            }
            .status-select {
                width: 70px;
                font-size: 0.6rem;
            }
        }
        @media (max-width: 480px) {
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
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
                        <i class="bi bi-calendar-check me-2"></i> Daily <span class="accent">{{ __('ui.attendance') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-range me-1"></i> Mark attendance for employees
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success" id="markAllPresent">
                        <i class="bi bi-check-all me-1"></i> Mark All Present
                    </button>
                    <button class="btn btn-danger" id="markAllAbsent">
                        <i class="bi bi-x-circle me-1"></i> Mark All Absent
                    </button>
                    <button class="btn btn-primary" id="saveAttendance">
                        <i class="bi bi-save me-1"></i> Save Attendance
                    </button>
                </div>
            </div>
        </div>

        <!-- Attendance Container -->
        <div class="attendance-container">
            <!-- Filters Bar -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="startDate" value="{{ $startDate->format('Y-m-d') }}">
                        <span class="text-muted align-self-center">to</span>
                        <input type="date" class="form-control" id="endDate" value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="filter-group">
                    <label>{{ __('ui.department') }}</label>
                    <select class="form-select" id="departmentFilter">
                        <option value="">{{ __('ui.all_departments') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>{{ __('ui.search') }}</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="{{ __('ui.search_employee') }}">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" id="loadAttendance">
                        <i class="bi bi-arrow-clockwise me-1"></i> Load
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-bar" id="statsBar">
                <div class="stat-item">
                    <div class="stat-number" id="totalEmployees">0</div>
                    <div class="stat-label">{{ __('ui.total') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number present" id="presentCount">0</div>
                    <div class="stat-label">{{ __('ui.present') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number absent" id="absentCount">0</div>
                    <div class="stat-label">{{ __('ui.absent') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number leave" id="leaveCount">0</div>
                    <div class="stat-label">Leave</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number half_day" id="halfDayCount">0</div>
                    <div class="stat-label">{{ __('ui.half_day') }}</div>
                </div>
            </div>

            <!-- Table -->
            <div class="attendance-table-wrap" style="position: relative;">
                <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                    <div class="spinner"></div>
                </div>

                <table class="attendance-table" id="attendanceTable">
                    <thead>
                    <tr>
                        <th style="min-width: 200px;">{{ __('ui.employee') }}</th>
                        <th>{{ __('ui.department') }}</th>
                        <th class="text-center" style="min-width: 80px;">{{ __('ui.status') }}</th>
                        @foreach($dateRange as $date)
                            @php
                                $isWeekend = $date->isWeekend();
                                $isToday = $date->isToday();
                            @endphp
                            <th class="day-column text-center">
                                <span class="day-label {{ $isWeekend ? 'weekend' : '' }} {{ $isToday ? 'today' : '' }}">
                                    {{ $date->format('D') }}
                                </span>
                                <span class="day-label {{ $isWeekend ? 'weekend' : '' }} {{ $isToday ? 'today' : '' }}">
                                    {{ $date->format('d') }}
                                </span>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody id="attendanceBody">
                    <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var attendanceData = {};
            var employees = [];
            var dateRange = [];

            // ─── Helper Functions ───
            function getInitials(firstName, lastName) {
                return (firstName ? firstName.charAt(0) : '') + (lastName ? lastName.charAt(0) : '');
            }

            function showLoading(show) {
                if (show) {
                    $('#loadingOverlay').show();
                } else {
                    $('#loadingOverlay').hide();
                }
            }

            // ─── Load Attendance ───
            function loadAttendance() {
                var startDate = $('#startDate').val();
                var endDate = $('#endDate').val();
                var departmentId = $('#departmentFilter').val();
                var search = $('#searchFilter').val();

                if (!startDate || !endDate) {
                    Swal.fire('Error', 'Please select both start and end dates.', 'error');
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    Swal.fire('Error', 'Start date cannot be after end date.', 'error');
                    return;
                }

                showLoading(true);

                $.ajax({
                    url: '{{ route("admin.hr.attendance.get-data") }}',
                    method: 'GET',
                    data: {
                        start_date: startDate,
                        end_date: endDate,
                        department_id: departmentId,
                        search: search
                    },
                    success: function(res) {
                        if (res.success) {
                            employees = res.data.employees;
                            attendanceData = res.data.attendance || {};
                            dateRange = res.data.date_range || [];
                            renderTable();
                            updateStats();
                        }
                        showLoading(false);
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to load attendance data.', 'error');
                        showLoading(false);
                    }
                });
            }

            // ─── Render Table ───
            function renderTable() {
                var tbody = $('#attendanceBody');
                tbody.empty();

                if (!employees || employees.length === 0) {
                    tbody.html(`
                <tr>
                    <td colspan="${dateRange.length + 3}" class="text-center py-4">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                            <div class="empty-title">No Employees Found</div>
                            <div class="empty-desc">Try adjusting your filters or add employees to the system.</div>
                        </div>
                    </td>
                </tr>
            `);
                    return;
                }

                $.each(employees, function(index, employee) {
                    var status = 'present';
                    if (attendanceData[employee.id] && attendanceData[employee.id]._global_status) {
                        status = attendanceData[employee.id]._global_status;
                    }

                    var row = document.createElement('tr');
                    row.setAttribute('data-employee-id', employee.id);

                    // Employee Info Column
                    var td1 = document.createElement('td');
                    td1.innerHTML = `
                <div class="employee-info">
                    <div class="avatar">${getInitials(employee.first_name, employee.last_name)}</div>
                    <div>
                        <div class="name">${employee.first_name} ${employee.last_name}</div>
                        <div class="code">${employee.employee_code}</div>
                    </div>
                </div>
            `;
                    row.appendChild(td1);

                    // Department Column
                    var td2 = document.createElement('td');
                    td2.textContent = employee.department ? employee.department.name : 'N/A';
                    row.appendChild(td2);

                    // Status Column
                    var td3 = document.createElement('td');
                    td3.className = 'text-center';
                    td3.innerHTML = `
                <div class="d-flex flex-column align-items-center gap-1">
                    <select class="form-select form-select-sm status-select" data-employee-id="${employee.id}">
                        <option value="present" ${status === 'present' ? 'selected' : ''}>✅ Present</option>
                        <option value="absent" ${status === 'absent' ? 'selected' : ''}>❌ Absent</option>
                        <option value="half_day" ${status === 'half_day' ? 'selected' : ''}>🌓 Half Day</option>
                        <option value="leave" ${status === 'leave' ? 'selected' : ''}>📋 Leave</option>
                    </select>
                </div>
            `;
                    row.appendChild(td3);

                    // Day Columns
                    $.each(dateRange, function(index, date) {
                        var dateObj = new Date(date);
                        var isWeekend = dateObj.getDay() === 6 || dateObj.getDay() === 0;
                        var dateStr = date;
                        var dayStatus = 'not_marked';

                        if (attendanceData[employee.id] && attendanceData[employee.id][dateStr]) {
                            dayStatus = attendanceData[employee.id][dateStr];
                        } else if (isWeekend) {
                            dayStatus = 'weekend';
                        }

                        var isChecked = dayStatus === 'present' || dayStatus === 'half_day';
                        var isHalfDay = dayStatus === 'half_day';
                        var isWeekendDay = dayStatus === 'weekend';

                        var td = document.createElement('td');
                        td.className = 'text-center';

                        if (isWeekendDay) {
                            td.innerHTML = '<span class="text-muted" style="font-size: 0.7rem;">-</span>';
                        } else {
                            td.innerHTML = `
                        <div class="toggle-checkbox">
                            <input type="checkbox"
                                class="day-checkbox"
                                data-employee-id="${employee.id}"
                                data-date="${dateStr}"
                                ${isChecked ? 'checked' : ''}
                                ${isHalfDay ? 'data-half-day="true"' : ''}>
                            <span class="slider"></span>
                            ${isHalfDay ? '<span class="status-label">½</span>' : ''}
                        </div>
                    `;
                        }

                        row.appendChild(td);
                    });

                    tbody.append(row);
                });

                // Event Listeners
                $('.status-select').on('change', function() {
                    var employeeId = $(this).data('employee-id');
                    var status = $(this).val();
                    updateEmployeeStatus(employeeId, status);
                });

                $(document).on('change', '.day-checkbox', function() {
                    var employeeId = $(this).data('employee-id');
                    var date = $(this).data('date');
                    var checked = $(this).is(':checked');
                    var isHalfDay = $(this).data('half-day') || false;

                    if (!attendanceData[employeeId]) {
                        attendanceData[employeeId] = {};
                    }

                    if (checked) {
                        attendanceData[employeeId][date] = isHalfDay ? 'half_day' : 'present';
                    } else {
                        attendanceData[employeeId][date] = 'absent';
                    }

                    updateStats();
                });

                $(document).on('dblclick', '.day-checkbox', function() {
                    var employeeId = $(this).data('employee-id');
                    var date = $(this).data('date');
                    var currentChecked = $(this).is(':checked');

                    if (!currentChecked) {
                        $(this).prop('checked', true);
                        $(this).data('half-day', true);
                        if (!attendanceData[employeeId]) {
                            attendanceData[employeeId] = {};
                        }
                        attendanceData[employeeId][date] = 'half_day';
                        $(this).siblings('.status-label').text('½');
                        updateStats();
                    } else {
                        var isHalfDay = $(this).data('half-day') || false;
                        if (isHalfDay) {
                            $(this).data('half-day', false);
                            $(this).siblings('.status-label').text('');
                            attendanceData[employeeId][date] = 'present';
                        } else {
                            $(this).data('half-day', true);
                            $(this).siblings('.status-label').text('½');
                            attendanceData[employeeId][date] = 'half_day';
                        }
                        updateStats();
                    }
                });
            }

            // ─── Update Employee Status ───
            function updateEmployeeStatus(employeeId, status) {
                if (!attendanceData[employeeId]) {
                    attendanceData[employeeId] = {};
                }

                // Store global status
                attendanceData[employeeId]._global_status = status;

                // Update all day checkboxes for this employee
                $('input.day-checkbox[data-employee-id="' + employeeId + '"]').each(function() {
                    var date = $(this).data('date');
                    if (status === 'present') {
                        $(this).prop('checked', true);
                        $(this).data('half-day', false);
                        $(this).siblings('.status-label').text('');
                        attendanceData[employeeId][date] = 'present';
                    } else if (status === 'absent') {
                        $(this).prop('checked', false);
                        $(this).data('half-day', false);
                        $(this).siblings('.status-label').text('');
                        attendanceData[employeeId][date] = 'absent';
                    } else if (status === 'half_day') {
                        $(this).prop('checked', true);
                        $(this).data('half-day', true);
                        $(this).siblings('.status-label').text('½');
                        attendanceData[employeeId][date] = 'half_day';
                    } else if (status === 'leave') {
                        $(this).prop('checked', false);
                        $(this).data('half-day', false);
                        $(this).siblings('.status-label').text('');
                        attendanceData[employeeId][date] = 'leave';
                    }
                });

                updateStats();
            }

            // ─── Update Stats ───
            function updateStats() {
                var total = 0;
                var present = 0;
                var absent = 0;
                var leave = 0;
                var halfDay = 0;

                $.each(employees, function(index, employee) {
                    total++;
                    var empData = attendanceData[employee.id] || {};

                    // Check if there's a global status
                    if (empData._global_status) {
                        var status = empData._global_status;
                        if (status === 'present') present++;
                        else if (status === 'absent') absent++;
                        else if (status === 'leave') leave++;
                        else if (status === 'half_day') halfDay++;
                    } else {
                        // Count from individual days
                        var hasPresent = false;
                        var hasHalfDay = false;
                        var hasLeave = false;
                        var hasAbsent = false;

                        $.each(dateRange, function(index, date) {
                            var dayStatus = empData[date];
                            if (dayStatus === 'present') hasPresent = true;
                            else if (dayStatus === 'half_day') hasHalfDay = true;
                            else if (dayStatus === 'leave') hasLeave = true;
                            else if (dayStatus === 'absent') hasAbsent = true;
                        });

                        if (hasPresent) present++;
                        else if (hasHalfDay) halfDay++;
                        else if (hasLeave) leave++;
                        else absent++;
                    }
                });

                $('#totalEmployees').text(total);
                $('#presentCount').text(present);
                $('#absentCount').text(absent);
                $('#leaveCount').text(leave);
                $('#halfDayCount').text(halfDay);
            }

            // ─── Mark All Present ───
            $('#markAllPresent').on('click', function() {
                $.each(employees, function(index, employee) {
                    if (!attendanceData[employee.id]) {
                        attendanceData[employee.id] = {};
                    }
                    attendanceData[employee.id]._global_status = 'present';
                    $('select.status-select[data-employee-id="' + employee.id + '"]').val('present');
                    $('input.day-checkbox[data-employee-id="' + employee.id + '"]').each(function() {
                        $(this).prop('checked', true);
                        $(this).data('half-day', false);
                        $(this).siblings('.status-label').text('');
                    });
                });
                updateStats();
                Swal.fire('Success', 'All employees marked as present.', 'success');
            });

            // ─── Mark All Absent ───
            $('#markAllAbsent').on('click', function() {
                $.each(employees, function(index, employee) {
                    if (!attendanceData[employee.id]) {
                        attendanceData[employee.id] = {};
                    }
                    attendanceData[employee.id]._global_status = 'absent';
                    $('select.status-select[data-employee-id="' + employee.id + '"]').val('absent');
                    $('input.day-checkbox[data-employee-id="' + employee.id + '"]').each(function() {
                        $(this).prop('checked', false);
                        $(this).data('half-day', false);
                        $(this).siblings('.status-label').text('');
                    });
                });
                updateStats();
                Swal.fire('Success', 'All employees marked as absent.', 'success');
            });

            // ─── Save Attendance ───
            // ─── Save Attendance ───
            $('#saveAttendance').on('click', function() {
                var startDate = $('#startDate').val();
                var endDate = $('#endDate').val();

                if (!startDate || !endDate) {
                    Swal.fire('Error', 'Please select a date range.', 'error');
                    return;
                }

                // Build attendance data
                var attendancePayload = {};

                $.each(employees, function(index, employee) {
                    var empId = employee.id;
                    var empData = attendanceData[empId] || {};
                    var days = {};

                    // Get all day-specific statuses
                    $.each(empData, function(key, value) {
                        // Skip global status
                        if (key !== '_global_status') {
                            // Only include valid dates (YYYY-MM-DD format)
                            if (key.match(/^\d{4}-\d{2}-\d{2}$/)) {
                                days[key] = value;
                            }
                        }
                    });

                    // If there are day-specific statuses, use them
                    if (Object.keys(days).length > 0) {
                        attendancePayload[empId] = days;
                    } else {
                        // Otherwise use global status for all days in range
                        var globalStatus = empData._global_status || 'present';
                        attendancePayload[empId] = {};
                        $.each(dateRange, function(idx, date) {
                            attendancePayload[empId][date] = globalStatus;
                        });
                    }
                });

                // Check if any data to save
                var totalEntries = 0;
                $.each(attendancePayload, function(empId, days) {
                    totalEntries += Object.keys(days).length;
                });

                if (totalEntries === 0) {
                    Swal.fire('Info', 'No attendance data to save.', 'info');
                    return;
                }

                showLoading(true);

                $.ajax({
                    url: '{{ route("admin.hr.attendance.save-bulk") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        start_date: startDate,
                        end_date: endDate,
                        attendance: attendancePayload
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Success!', res.message, 'success');
                            // Reload data to reflect changes
                            loadAttendance();
                        } else {
                            Swal.fire('Error', res.message || 'Failed to save attendance.', 'error');
                        }
                        showLoading(false);
                    },
                    error: function(xhr) {
                        var errorMessage = 'Failed to save attendance.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                        showLoading(false);
                    }
                });
            });

            // ─── Load Attendance ───
            $('#loadAttendance').on('click', function() {
                loadAttendance();
            });

            // ─── Enter key on search ───
            $('#searchFilter').on('keypress', function(e) {
                if (e.which === 13) {
                    loadAttendance();
                }
            });

            // ─── Initialize ───
            loadAttendance();

            // ─── Auto-load on filter change ───
            var debounceTimer;
            $('#departmentFilter').on('change', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    loadAttendance();
                }, 300);
            });

            $('#startDate, #endDate').on('change', function() {
                loadAttendance();
            });
        });
    </script>
@endsection
