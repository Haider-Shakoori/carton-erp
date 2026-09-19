<?php
// app/Http/Controllers/Admin/HR/ReportController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Leave;
use App\Models\Advance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Employee reports.
     */
    public function employees(Request $request)
    {
        $query = Employee::with(['department', 'designation']);

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('employee_id', 'like', "%{$request->search}%");
            });
        }

        $employees = $query->get();
        $departments = \App\Models\Department::all();
        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        return view('admin.hr.reports.employees', compact('employees', 'departments', 'defaultCurrency'));
    }

    /**
     * Attendance reports.
     */
    public function attendance(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        // Get all active employees
        $employees = Employee::where('is_active', true)->get();

        // If specific employee is requested, filter
        if ($request->employee_id) {
            $employees = Employee::where('id', $request->employee_id)->where('is_active', true)->get();
        }

        $reportData = [];

        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $present = $attendances->where('status', 'present')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $late = $attendances->where('status', 'late')->count();
            $leave = $attendances->where('status', 'leave')->count();
            $totalHours = $attendances->sum('total_hours');

            $totalDays = $attendances->count();
            $attendanceRate = $totalDays > 0 ? round(($present / $totalDays) * 100, 2) : 0;

            $reportData[] = [
                'employee' => $employee,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'leave' => $leave,
                'total_hours' => $totalHours,
                'attendance_rate' => $attendanceRate,
            ];
        }

        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        return view('admin.hr.reports.attendance', compact('reportData', 'startDate', 'endDate', 'employees', 'defaultCurrency'));
    }

    /**
     * Payroll reports.
     */
    public function payroll(Request $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $query = Payroll::with(['employee.department'])
            ->whereMonth('period_start', $month)
            ->whereYear('period_start', $year);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $payrolls = $query->get();
        $employees = Employee::where('is_active', true)->get();

        $summary = [
            'total_basic_salary' => $payrolls->sum('basic_salary'),
            'total_allowances' => $payrolls->sum('housing_allowance') +
                $payrolls->sum('transport_allowance') +
                $payrolls->sum('medical_allowance') +
                $payrolls->sum('other_allowances'),
            'total_deductions' => $payrolls->sum('total_deductions'),
            'total_gross_salary' => $payrolls->sum('total_earnings'),
            'total_net_salary' => $payrolls->sum('net_salary'),
            'paid_count' => $payrolls->where('status', 'paid')->count(),
            'pending_count' => $payrolls->where('status', 'draft')->count(),
            'processed_count' => $payrolls->where('status', 'processed')->count(),
            'total_employees' => $payrolls->count(),
        ];

        $defaultCurrency = Currency::where('is_default', true)->first();

        // Return view with all required variables
        return view('admin.hr.reports.payroll', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
            'employees' => $employees,
            'defaultCurrency' => $defaultCurrency,
        ]);
    }

    /**
     * Leave reports.
     */
    public function leaves(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $query = Leave::with(['employee', 'leaveType'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaves = $query->get();

        $summary = [
            'total' => $leaves->count(),
            'pending' => $leaves->where('status', 'pending')->count(),
            'approved' => $leaves->where('status', 'approved')->count(),
            'rejected' => $leaves->where('status', 'rejected')->count(),
            'total_days' => $leaves->sum('days'),
        ];

        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        return view('admin.hr.reports.leaves', compact('leaves', 'summary', 'startDate', 'endDate', 'defaultCurrency'));
    }

    /**
     * Advances reports.
     */
    public function advances(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $query = Advance::with('employee')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $advances = $query->get();

        $summary = [
            'total' => $advances->count(),
            'pending' => $advances->where('status', 'pending')->count(),
            'approved' => $advances->where('status', 'approved')->count(),
            'paid' => $advances->where('status', 'paid')->count(),
            'rejected' => $advances->where('status', 'rejected')->count(),
            'total_amount' => $advances->sum('amount'),
            'remaining_amount' => $advances->sum('remaining_amount'),
        ];

        $defaultCurrency = \App\Models\Currency::where('is_default', true)->first();

        return view('admin.hr.reports.advances', compact('advances', 'summary', 'startDate', 'endDate', 'defaultCurrency'));
    }

    /**
     * Export reports.
     */
    public function export(Request $request)
    {
        $type = $request->type;
        $format = $request->format() ?? 'csv';

        // Implementation depends on export library used
        return response()->json([
            'message' => 'Export functionality coming soon'
        ]);
    }
}
