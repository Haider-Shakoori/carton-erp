<?php
// app/Http/Controllers/Admin/HR/DashboardController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Advance;
use Carbon\Carbon;

class HRDashboardController extends Controller
{
    public function index()
    {
        // Employee Statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();

        // Today's Attendance
        $today = Carbon::today();
        $todayAttendance = Attendance::whereDate('date', $today)->get();
        $presentToday = $todayAttendance->where('status', 'present')->count();
        $lateToday = $todayAttendance->where('status', 'late')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();
        $leaveToday = $todayAttendance->where('status', 'leave')->count();
        $totalAttendanceToday = $todayAttendance->count();

        // Monthly Attendance Summary
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $monthlyAttendance = Attendance::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();
        $monthlyPresent = $monthlyAttendance->where('status', 'present')->count();
        $monthlyLate = $monthlyAttendance->where('status', 'late')->count();
        $monthlyAbsent = $monthlyAttendance->where('status', 'absent')->count();
        $monthlyLeave = $monthlyAttendance->where('status', 'leave')->count();

        // Leave Statistics
        $pendingLeaves = Leave::where('status', 'pending')->count();
        $approvedLeaves = Leave::where('status', 'approved')->count();
        $rejectedLeaves = Leave::where('status', 'rejected')->count();
        $totalLeaves = Leave::count();

        // Leave requests this month
        $monthlyLeaves = Leave::whereMonth('start_date', $month)
            ->whereYear('start_date', $year)
            ->count();

        // Advance & Loan Statistics
        $pendingAdvances = Advance::where('status', 'pending')->count();
        $approvedAdvances = Advance::where('status', 'approved')->count();
        $paidAdvances = Advance::where('status', 'paid')->count();
        $rejectedAdvances = Advance::where('status', 'rejected')->count();
        $totalAdvances = Advance::count();
        $totalAdvanceAmount = Advance::whereIn('status', ['approved', 'paid'])->sum('amount');
        $remainingAdvanceAmount = Advance::whereIn('status', ['approved'])->sum('remaining_amount');

        // Payroll Statistics
        $currentMonthPayroll = Payroll::whereMonth('payroll_month', $month)
            ->whereYear('payroll_month', $year)
            ->get();
        $payrollTotal = $currentMonthPayroll->sum('net_salary');
        $payrollCount = $currentMonthPayroll->count();
        $pendingPayroll = $currentMonthPayroll->where('status', 'pending')->count();
        $processedPayroll = $currentMonthPayroll->where('status', 'processed')->count();
        $paidPayroll = $currentMonthPayroll->where('status', 'paid')->count();

        // Upcoming Leave Requests (next 7 days)
        $upcomingLeaves = Leave::with('employee')
            ->where('status', 'approved')
            ->where('start_date', '>=', $today)
            ->where('start_date', '<=', $today->copy()->addDays(7))
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        // Recent Attendance (last 7 days)
        $recentAttendance = Attendance::with('employee')
            ->whereDate('date', '>=', $today->copy()->subDays(7))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Department-wise Employee Distribution
        $departmentDistribution = Employee::with('department')
            ->where('is_active', true)
            ->get()
            ->groupBy('department.name')
            ->map(function ($employees, $department) {
                return [
                    'department' => $department ?? 'Unassigned',
                    'count' => $employees->count()
                ];
            })
            ->values();

        // Attendance Rate (last 30 days)
        $attendanceRate = $this->calculateAttendanceRate();

        // New Employees this month
        $newEmployeesThisMonth = Employee::whereMonth('hire_date', $month)
            ->whereYear('hire_date', $year)
            ->count();

        return view('admin.hr.dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'inactiveEmployees',
            'presentToday',
            'lateToday',
            'absentToday',
            'leaveToday',
            'totalAttendanceToday',
            'monthlyPresent',
            'monthlyLate',
            'monthlyAbsent',
            'monthlyLeave',
            'pendingLeaves',
            'approvedLeaves',
            'rejectedLeaves',
            'totalLeaves',
            'monthlyLeaves',
            'pendingAdvances',
            'approvedAdvances',
            'paidAdvances',
            'rejectedAdvances',
            'totalAdvances',
            'totalAdvanceAmount',
            'remainingAdvanceAmount',
            'payrollTotal',
            'payrollCount',
            'pendingPayroll',
            'processedPayroll',
            'paidPayroll',
            'upcomingLeaves',
            'recentAttendance',
            'departmentDistribution',
            'attendanceRate',
            'newEmployeesThisMonth'
        ));
    }

    /**
     * Calculate attendance rate for the current month
     */
    private function calculateAttendanceRate()
    {
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $totalEmployees = Employee::where('is_active', true)->count();

        if ($totalEmployees === 0) {
            return [
                'rate' => 0,
                'present' => 0,
                'total' => 0
            ];
        }

        $workingDays = $this->getWorkingDays(Carbon::create($year, $month, 1), Carbon::now());
        $totalPossibleAttendances = $totalEmployees * $workingDays;

        $actualAttendances = Attendance::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', 'present')
            ->count();

        $rate = $totalPossibleAttendances > 0
            ? round(($actualAttendances / $totalPossibleAttendances) * 100, 2)
            : 0;

        return [
            'rate' => $rate,
            'present' => $actualAttendances,
            'total' => $totalPossibleAttendances,
            'working_days' => $workingDays
        ];
    }

    /**
     * Get working days (excluding weekends) between two dates
     */
    private function getWorkingDays($startDate, $endDate)
    {
        $days = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Exclude Saturday and Sunday (6 and 0)
            if ($current->dayOfWeek != 6 && $current->dayOfWeek != 0) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get chart data for attendance trends
     */
    public function getAttendanceChartData()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $data = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $attendances = Attendance::whereDate('date', $current)->get();
            $data[] = [
                'date' => $current->format('Y-m-d'),
                'present' => $attendances->where('status', 'present')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'leave' => $attendances->where('status', 'leave')->count(),
            ];
            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get chart data for department distribution
     */
    public function getDepartmentChartData()
    {
        $departments = Employee::with('department')
            ->where('is_active', true)
            ->get()
            ->groupBy('department.name')
            ->map(function ($employees, $department) {
                return [
                    'label' => $department ?? 'Unassigned',
                    'value' => $employees->count()
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $departments
        ]);
    }

    /**
     * Get chart data for payroll summary
     */
    public function getPayrollChartData()
    {
        $months = collect(range(1, 12))->map(function ($month) {
            $payrolls = Payroll::whereMonth('payroll_month', $month)
                ->whereYear('payroll_month', Carbon::now()->year)
                ->get();

            return [
                'month' => Carbon::create()->month($month)->format('M'),
                'net_salary' => $payrolls->sum('net_salary'),
                'gross_salary' => $payrolls->sum('gross_salary'),
                'deductions' => $payrolls->sum('deductions'),
                'count' => $payrolls->count()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $months
        ]);
    }
}
