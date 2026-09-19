{{-- resources/views/admin/hr/dashboard.blade.php --}}
@extends('layouts.admin.base')

@section('title', __('ui.hr_dashboard'))

@section('css')
    <style>
        .hr-stat-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }
        .hr-stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        .hr-stat-card .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--gray-800);
        }
        .hr-stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .hr-stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .hr-stat-card .stat-icon.blue { background: var(--info-bg); color: var(--info); }
        .hr-stat-card .stat-icon.green { background: var(--success-bg); color: var(--success); }
        .hr-stat-card .stat-icon.yellow { background: var(--warning-bg); color: var(--warning); }
        .hr-stat-card .stat-icon.red { background: var(--danger-bg); color: var(--danger); }
        .hr-stat-card .stat-icon.purple { background: var(--primary-bg); color: var(--primary); }
        .hr-stat-card .stat-icon.orange { background: #fff7ed; color: #f97316; }

        .status-badge {
            padding: 0.15rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .status-badge.pending { background: var(--warning-bg); color: var(--warning); }
        .status-badge.approved { background: var(--success-bg); color: var(--success); }
        .status-badge.rejected { background: var(--danger-bg); color: var(--danger); }
        .status-badge.paid { background: var(--success-bg); color: var(--success); }
        .status-badge.processed { background: var(--info-bg); color: var(--info); }

        .attendance-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.1rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        .attendance-status.present { background: var(--success-bg); color: var(--success); }
        .attendance-status.late { background: var(--warning-bg); color: var(--warning); }
        .attendance-status.absent { background: var(--danger-bg); color: var(--danger); }
        .attendance-status.leave { background: var(--primary-bg); color: var(--primary); }

        .chart-container {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
        }
        .chart-container .chart-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-700);
            margin-bottom: 1rem;
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
                        <i class="bi bi-speedometer2 me-2"></i> HR <span class="accent">{{ __('ui.dashboard') }}</span>
                    </h1>
                    <p class="subtitle">
                        <i class="bi bi-calendar-check me-1"></i>
                        {{ Carbon::now()->format('l, F j, Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> {{ __('ui.refresh') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Row 1: Employee Overview -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">{{ __('ui.total_employees') }}</div>
                            <div class="stat-number">{{ $totalEmployees }}</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $activeEmployees }} Active</span>
                        <span class="text-muted ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $inactiveEmployees }} Inactive</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Today's Attendance</div>
                            <div class="stat-number">{{ $totalAttendanceToday }}</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $presentToday }} Present</span>
                        <span class="text-warning ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $lateToday }} Late</span>
                        <span class="text-danger ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $absentToday }} Absent</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Pending Requests</div>
                            <div class="stat-number">{{ $pendingLeaves + $pendingAdvances }}</div>
                        </div>
                        <div class="stat-icon yellow">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-warning"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $pendingLeaves }} Leaves</span>
                        <span class="text-primary ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $pendingAdvances }} Advances</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Monthly Payroll</div>
                            <div class="stat-number">${{ number_format($payrollTotal, 0) }}</div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted">{{ $payrollCount }} employees</span>
                        <span class="text-success ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $paidPayroll }} Paid</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Row 2: Detailed Stats -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">{{ __('ui.attendance_rate') }}</div>
                            <div class="stat-number">{{ $attendanceRate['rate'] }}%</div>
                        </div>
                        <div class="stat-icon green">
                            <i class="bi bi-percent"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted">{{ $attendanceRate['present'] }} / {{ $attendanceRate['total'] }} present</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">Leaves This Month</div>
                            <div class="stat-number">{{ $monthlyLeaves }}</div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-success"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $approvedLeaves }} Approved</span>
                        <span class="text-danger ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> {{ $rejectedLeaves }} Rejected</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">{{ __('ui.advances_loans') }}</div>
                            <div class="stat-number">${{ number_format($totalAdvanceAmount, 0) }}</div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="bi bi-coin"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted">{{ $totalAdvances }} total</span>
                        <span class="text-warning ms-2"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> ${{ number_format($remainingAdvanceAmount, 0) }} remaining</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="hr-stat-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stat-label">New Employees</div>
                            <div class="stat-number">{{ $newEmployeesThisMonth }}</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="bi bi-person-plus"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="text-muted">This month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-8">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="bi bi-bar-chart-line me-1"></i> Attendance Trends (This Month)
                    </div>
                    <canvas id="attendanceChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="bi bi-pie-chart me-1"></i> Department Distribution
                    </div>
                    <canvas id="departmentChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity Row -->
        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="bi bi-calendar-event me-1"></i> Upcoming Leaves
                    </div>
                    @if($upcomingLeaves->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($upcomingLeaves as $leave)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <span class="fw-semibold">{{ $leave->employee->full_name ?? 'N/A' }}</span>
                                        <span class="text-muted ms-2" style="font-size: 0.8rem;">
                                        {{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d, Y') }}
                                    </span>
                                        <span class="badge-status {{ $leave->status }} ms-2">{{ ucfirst($leave->status) }}</span>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.75rem;">
                                    {{ $leave->days }} day(s)
                                </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                            No upcoming leaves in the next 7 days
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="chart-container">
                    <div class="chart-title">
                        <i class="bi bi-clock-history me-1"></i> Recent Attendance
                    </div>
                    @if($recentAttendance->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentAttendance as $attendance)
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <span class="fw-semibold">{{ $attendance->employee->full_name ?? 'N/A' }}</span>
                                        <span class="text-muted ms-2" style="font-size: 0.8rem;">
                                        {{ $attendance->date->format('M d, Y') }}
                                    </span>
                                    </div>
                                    <div>
                                    <span class="attendance-status {{ $attendance->status }}">
                                        <i class="bi bi-circle-fill" style="font-size: 0.4rem;"></i>
                                        {{ ucfirst($attendance->status) }}
                                    </span>
                                        @if($attendance->check_in)
                                            <span class="text-muted ms-2" style="font-size: 0.7rem;">
                                            {{ $attendance->check_in->format('h:i A') }}
                                                @if($attendance->check_out)
                                                    - {{ $attendance->check_out->format('h:i A') }}
                                                @endif
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                            No recent attendance records
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('vendor/chartjs/chart-4.5.1.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ─── Attendance Chart ───
            $.ajax({
                url: '{{ route("admin.hr.attendance.chart") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const ctx = document.getElementById('attendanceChart').getContext('2d');
                        const dates = response.data.map(item => item.date);

                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: dates,
                                datasets: [
                                    {
                                        label: 'Present',
                                        data: response.data.map(item => item.present),
                                        backgroundColor: '#10b981',
                                        borderRadius: 4,
                                    },
                                    {
                                        label: 'Late',
                                        data: response.data.map(item => item.late),
                                        backgroundColor: '#f59e0b',
                                        borderRadius: 4,
                                    },
                                    {
                                        label: 'Absent',
                                        data: response.data.map(item => item.absent),
                                        backgroundColor: '#ef4444',
                                        borderRadius: 4,
                                    },
                                    {
                                        label: 'Leave',
                                        data: response.data.map(item => item.leave),
                                        backgroundColor: '#4f46e5',
                                        borderRadius: 4,
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 20,
                                            font: { size: 11 }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: { stepSize: 1 }
                                    },
                                    x: {
                                        ticks: {
                                            maxRotation: 45,
                                            font: { size: 10 }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            });

            // ─── Department Chart ───
            $.ajax({
                url: '{{ route("admin.hr.departments.chart") }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const ctx = document.getElementById('departmentChart').getContext('2d');
                        const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#ec4899'];

                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: response.data.map(item => item.label),
                                datasets: [{
                                    data: response.data.map(item => item.value),
                                    backgroundColor: colors.slice(0, response.data.length),
                                    borderWidth: 2,
                                    borderColor: '#ffffff',
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 15,
                                            font: { size: 11 }
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            });
        });
    </script>
@endsection
