<?php
// app/Http/Controllers/Admin/HR/AttendanceController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\Department;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display today's attendance.
     */
    public function index(Request $request)
    {
        // Get date range from request or use current month
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        // Get departments for filter
        $departments = Department::where('is_active', true)->get();

        // Generate date range for the table headers
        $dateRange = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateRange[] = $current->copy();
            $current->addDay();
        }

        // Get employees (will be loaded via AJAX, but we need to pass some data)
        $employees = Employee::with('department')
            ->where('is_active', true)
            ->limit(0) // Don't load employees here, they will be loaded via AJAX
            ->get();

        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.hr.attendance.index', compact(
            'startDate',
            'endDate',
            'dateRange',
            'departments',
            'employees',
            'defaultCurrency'
        ));
    }

    /**
     * Display monthly attendance view.
     */
    public function monthly(Request $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $employees = Employee::where('is_active', true)->get();
        $attendanceData = [];

        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->format('Y-m-d');
                });

            $attendanceData[$employee->id] = [
                'employee' => $employee,
                'attendances' => $attendances,
                'summary' => [
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'leave' => $attendances->where('status', 'leave')->count(),
                    'total_hours' => $attendances->sum('total_hours'),
                ]
            ];
        }

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $monthName = Carbon::create($year, $month)->format('F Y');

        return view('admin.hr.attendance.monthly', compact(
            'attendanceData', 'month', 'year', 'daysInMonth', 'monthName'
        ));
    }

    /**
     * Check in an employee.
     */
    public function checkIn(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
            ]);

            $employee = Employee::find($request->employee_id);
            $today = Carbon::today();

            // Check if already checked in today
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already checked in today.'
                ], 422);
            }

            $checkInTime = Carbon::now();
            $status = 'present';

            // Check if late (after 9:00 AM)
            $officeStart = Carbon::today()->setHour(9)->setMinute(0);
            if ($checkInTime->gt($officeStart)) {
                $status = 'late';
            }

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $today],
                [
                    'check_in' => $checkInTime,
                    'status' => $status,
                    'date' => $today,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully.',
                'data' => [
                    'status' => $status,
                    'check_in' => $checkInTime->format('h:i A'),
                    'attendance' => $attendance
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check out an employee.
     */
    public function checkOut(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
            ]);

            $employee = Employee::find($request->employee_id);
            $today = Carbon::today();

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in found for today.'
                ], 422);
            }

            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked out.'
                ], 422);
            }

            $checkOutTime = Carbon::now();
            $hoursWorked = $attendance->check_in->diffInHours($checkOutTime);

            $attendance->update([
                'check_out' => $checkOutTime,
                'total_hours' => round($hoursWorked, 2)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully.',
                'data' => [
                    'check_out' => $checkOutTime->format('h:i A'),
                    'total_hours' => $hoursWorked,
                    'attendance' => $attendance
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk store/update attendance.
     */
    public function bulkStore(Request $request)
    {
        try {
            $request->validate([
                'attendances' => 'required|array',
                'attendances.*.employee_id' => 'required|exists:employees,id',
                'attendances.*.status' => 'required|in:present,absent,leave,late',
                'date' => 'required|date',
            ]);

            $date = Carbon::parse($request->date);
            $updated = 0;

            foreach ($request->attendances as $attendanceData) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $attendanceData['employee_id'],
                        'date' => $date,
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'date' => $date,
                    ]
                );
                $updated++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$updated} attendance records updated successfully."
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance to CSV.
     */
    public function export(Request $request)
    {
        try {
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

            $attendances = Attendance::with('employee')
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="attendance_' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($attendances) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Employee', 'Date', 'Check In', 'Check Out', 'Total Hours', 'Status']);

                foreach ($attendances as $attendance) {
                    fputcsv($file, [
                        $attendance->employee->full_name ?? 'N/A',
                        $attendance->date->format('Y-m-d'),
                        $attendance->check_in ? $attendance->check_in->format('h:i A') : 'N/A',
                        $attendance->check_out ? $attendance->check_out->format('h:i A') : 'N/A',
                        $attendance->total_hours ?? 0,
                        ucfirst($attendance->status ?? 'N/A')
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance data for date range (AJAX).
     */
    public function getData(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $departmentId = $request->department_id;
            $search = $request->search;

            // Get employees
            $query = Employee::with('department')
                ->where('is_active', true);

            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }

            $employees = $query->get();

            // Get attendance for the date range
            $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
                ->get()
                ->groupBy('employee_id')
                ->map(function ($items) {
                    return $items->keyBy(function ($item) {
                        return $item->date->format('Y-m-d');
                    });
                });

            // Build attendance data
            $attendanceData = [];
            foreach ($employees as $employee) {
                $employeeAttendances = $attendances->get($employee->id, collect());
                $attendanceData[$employee->id] = [];

                // Add all dates in range
                $current = $startDate->copy();
                while ($current <= $endDate) {
                    $dateStr = $current->format('Y-m-d');
                    $attendance = $employeeAttendances->get($dateStr);

                    if ($attendance) {
                        $attendanceData[$employee->id][$dateStr] = $attendance->status;
                    }
                    $current->addDay();
                }
            }

            // Generate date range for the table headers
            $dateRange = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dateRange[] = $current->format('Y-m-d');
                $current->addDay();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'employees' => $employees->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'employee_code' => $employee->employee_code,
                            'first_name' => $employee->first_name,
                            'last_name' => $employee->last_name,
                            'department' => $employee->department ? [
                                'id' => $employee->department->id,
                                'name' => $employee->department->name
                            ] : null,
                        ];
                    }),
                    'attendance' => $attendanceData,
                    'date_range' => $dateRange,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Attendance data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save bulk attendance (AJAX).
     */
    public function saveBulk(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $attendanceData = $request->attendance;

            \Log::info('Attendance save request', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'attendance_data' => $attendanceData
            ]);

            if (empty($attendanceData) || !is_array($attendanceData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance data provided.'
                ], 422);
            }

            $saved = 0;
            $errors = [];

            foreach ($attendanceData as $employeeId => $days) {
                // Skip if not an array or empty
                if (!is_array($days) || empty($days)) {
                    continue;
                }

                foreach ($days as $date => $status) {
                    try {
                        // Skip if status is not valid
                        if (!in_array($status, ['present', 'absent', 'half_day', 'leave', 'holiday'])) {
                            continue;
                        }

                        $dateObj = Carbon::parse($date);

                        // Skip if date is outside range
                        if ($dateObj < $startDate || $dateObj > $endDate) {
                            continue;
                        }

                        // Check if employee exists
                        $employee = Employee::find($employeeId);
                        if (!$employee) {
                            continue;
                        }

                        // Update or create attendance
                        $attendance = Attendance::updateOrCreate(
                            [
                                'employee_id' => $employeeId,
                                'date' => $dateObj->format('Y-m-d'),
                            ],
                            [
                                'status' => $status,
                                'updated_by' => Auth::id(),
                                'created_by' => Auth::id(),
                            ]
                        );

                        $saved++;
                        \Log::info('Attendance saved', [
                            'employee_id' => $employeeId,
                            'date' => $dateObj->format('Y-m-d'),
                            'status' => $status,
                            'id' => $attendance->id
                        ]);

                    } catch (\Exception $e) {
                        $errors[] = "Error for employee {$employeeId} on {$date}: " . $e->getMessage();
                        \Log::error('Attendance save error', [
                            'employee_id' => $employeeId,
                            'date' => $date,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Attendance saved successfully! {$saved} records updated.",
                'errors' => $errors,
                'saved_count' => $saved
            ]);

        } catch (\Exception $e) {
            \Log::error('Save attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display attendance report.
     */
    public function report(Request $request)
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $departmentId = $request->department_id;

        // Get employees
        $query = Employee::with('department')
            ->where('is_active', true);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->get();

        // Get departments for filter
        $departments = Department::where('is_active', true)->get();

        // Calculate days in month
        $daysInMonth = $startDate->diffInDays($endDate) + 1;

        // Build report data
        $reportData = [];
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalHalfDay = 0;
        $totalLeave = 0;
        $totalHoliday = 0;

        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $present = $attendances->where('status', 'present')->count();
            $absent = $attendances->where('status', 'absent')->count();
            $halfDay = $attendances->where('status', 'half_day')->count();
            $leave = $attendances->where('status', 'leave')->count();
            $holiday = $attendances->where('status', 'holiday')->count();

            $reportData[] = [
                'employee' => $employee,
                'present' => $present,
                'absent' => $absent,
                'half_day' => $halfDay,
                'leave' => $leave,
                'holiday' => $holiday,
                'total_days' => $daysInMonth,
            ];

            $totalPresent += $present;
            $totalAbsent += $absent;
            $totalHalfDay += $halfDay;
            $totalLeave += $leave;
            $totalHoliday += $holiday;
        }

        $totalEmployees = $employees->count();

        return view('admin.hr.attendance.report', compact(
            'reportData',
            'startDate',
            'endDate',
            'departments',
            'totalEmployees',
            'totalPresent',
            'totalAbsent',
            'totalHalfDay',
            'totalLeave',
            'totalHoliday',
            'daysInMonth'
        ));
    }
}
