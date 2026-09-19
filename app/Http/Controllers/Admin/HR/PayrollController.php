<?php
// app/Http/Controllers/Admin/HR/PayrollController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Advance;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    /**
     * Display a listing of payrolls.
     */
    public function index(Request $request)
    {
        $query = Payroll::with(['employee', 'creator']);

        // Filter by month/year using period_start
        if ($request->month) {
            $query->whereMonth('period_start', $request->month);
        }
        if ($request->year) {
            $query->whereYear('period_start', $request->year);
        }

        // Filter by employee
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payrolls = $query->latest()->paginate(15);
        $employees = Employee::where('is_active', true)->get();

        // Summary
        $totalPayroll = Payroll::where('status', 'paid')->sum('net_salary');
        $pendingPayroll = Payroll::where('status', 'draft')->count();
        $processedPayroll = Payroll::where('status', 'processed')->count();
        $paidPayroll = Payroll::where('status', 'paid')->count();

        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.hr.payroll.index', compact(
            'payrolls', 'employees', 'totalPayroll', 'pendingPayroll',
            'processedPayroll', 'paidPayroll', 'defaultCurrency'
        ));
    }

    public function generate(Request $request)
    {
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $employees = Employee::where('is_active', true)->get();
        $payrollData = [];

        // Calculate period dates
        $periodStart = Carbon::create($year, $month, 1);
        $periodEnd = $periodStart->copy()->endOfMonth();

        foreach ($employees as $employee) {
            // Check if payroll already exists for this period
            $existingPayroll = Payroll::where('employee_id', $employee->id)
                ->whereYear('period_start', $year)
                ->whereMonth('period_start', $month)
                ->first();

            // Get attendance summary
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();

            $presentDays = $attendances->where('status', 'present')->count();
            $lateDays = $attendances->where('status', 'late')->count();
            $absentDays = $attendances->where('status', 'absent')->count();
            $leaveDays = $attendances->where('status', 'leave')->count();
            $totalHours = $attendances->sum('working_hours');

            // Calculate salary
            $basicSalary = $employee->basic_salary ?? 0;

            // Calculate allowances
            $housingAllowance = $basicSalary * 0.20;
            $transportAllowance = $basicSalary * 0.10;
            $medicalAllowance = $basicSalary * 0.05;

            // Calculate overtime
            $overtimePay = 0;
            if ($attendances->sum('overtime_hours') > 0) {
                $hourlyRate = $basicSalary / (30 * 8);
                $overtimePay = $attendances->sum('overtime_hours') * $hourlyRate * 1.5;
            }

            // Calculate deductions
            $taxDeduction = $basicSalary * 0.10;
            $socialSecurity = $basicSalary * 0.05;

            // Calculate advances/loans deductions
            $advanceDeduction = $this->getAdvanceDeductions($employee->id);
            $loanDeduction = $this->getLoanDeductions($employee->id);

            // Calculate totals
            $totalEarnings = $basicSalary + $housingAllowance + $transportAllowance + $medicalAllowance + $overtimePay;
            $totalDeductions = $taxDeduction + $socialSecurity + $advanceDeduction + $loanDeduction;
            $netSalary = $totalEarnings - $totalDeductions;

            // Get attendance summary
            $attendanceSummary = [
                'present' => $presentDays,
                'late' => $lateDays,
                'absent' => $absentDays,
                'leave' => $leaveDays,
                'total_hours' => $totalHours,
            ];

            if ($existingPayroll) {
                // Use existing payroll data
                $payrollData[] = [
                    'employee' => $employee,
                    'basic_salary' => $existingPayroll->basic_salary ?? $basicSalary,
                    'gross_salary' => $existingPayroll->total_earnings ?? $totalEarnings,
                    'deductions' => $existingPayroll->total_deductions ?? $totalDeductions,
                    'net_salary' => $existingPayroll->net_salary ?? $netSalary,
                    'attendance_summary' => $attendanceSummary,
                    'existing' => true,
                    'payroll_id' => $existingPayroll->id,
                ];
            } else {
                // Calculate new payroll
                $payrollData[] = [
                    'employee' => $employee,
                    'basic_salary' => $basicSalary,
                    'gross_salary' => $totalEarnings,
                    'deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'attendance_summary' => $attendanceSummary,
                    'existing' => false,
                ];
            }
        }

        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.hr.payroll.generate', compact('payrollData', 'month', 'year', 'periodStart', 'periodEnd', 'defaultCurrency'));
    }



    /**
     * Get loan deductions for an employee.
     */

    public function store(Request $request)
    {
        try {
            // Log the entire request
            \Log::info('Payroll request data:', $request->all());

            $validated = $request->validate([
                'payrolls' => 'required|array',
                'payrolls.*.employee_id' => 'required|exists:employees,id',
                'payrolls.*.basic_salary' => 'required|numeric|min:0',
                'payrolls.*.gross_salary' => 'required|numeric|min:0',
                'payrolls.*.deductions' => 'numeric|min:0',
                'payrolls.*.net_salary' => 'required|numeric|min:0',
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|min:2000',
            ]);

            // Log validated data
            \Log::info('Validated payroll data:', $validated);

            $month = $validated['month'];
            $year = $validated['year'];
            $created = 0;
            $updated = 0;

            $periodStart = Carbon::create($year, $month, 1);
            $periodEnd = $periodStart->copy()->endOfMonth();
            $paymentDate = Carbon::now();

            foreach ($validated['payrolls'] as $key => $payrollData) {
                // Log each payroll item
                \Log::info("Processing payroll item $key:", $payrollData);

                // Always process - ignore selected flag if it's not set
                // This ensures we process all payroll items
                $employeeId = $payrollData['employee_id'];

                // Check if payroll already exists
                $existing = Payroll::where('employee_id', $employeeId)
                    ->whereYear('period_start', $year)
                    ->whereMonth('period_start', $month)
                    ->first();

                $data = [
                    'basic_salary' => $payrollData['basic_salary'],
                    'total_earnings' => $payrollData['gross_salary'],
                    'total_deductions' => $payrollData['deductions'] ?? 0,
                    'net_salary' => $payrollData['net_salary'],
                    'period_end' => $periodEnd,
                    'payment_date' => $paymentDate,
                    'housing_allowance' => 0,
                    'transport_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowances' => 0,
                    'overtime_pay' => 0,
                    'bonus' => 0,
                    'commission' => 0,
                    'other_earnings' => 0,
                    'tax_deduction' => 0,
                    'social_security' => 0,
                    'advance_deduction' => 0,
                    'loan_deduction' => 0,
                    'penalty_deduction' => 0,
                    'other_deductions' => $payrollData['deductions'] ?? 0,
                ];

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                    \Log::info("Updated payroll for employee $employeeId");
                } else {
                    Payroll::create(array_merge($data, [
                        'employee_id' => $employeeId,
                        'payroll_number' => Payroll::generatePayrollNumber(),
                        'period_start' => $periodStart,
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                    ]));
                    $created++;
                    \Log::info("Created payroll for employee $employeeId");
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$created} created and {$updated} updated payroll records successfully.",
                'debug' => [
                    'total_items' => count($validated['payrolls']),
                    'created' => $created,
                    'updated' => $updated
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Payroll store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified payroll.
     */
    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'creator', 'processor']);
        $defaultCurrency = Currency::where('is_default', true)->first();
        return view('admin.hr.payroll.show', compact('payroll', 'defaultCurrency'));
    }

    /**
     * Show the form for editing the specified payroll.
     */
    public function edit(Payroll $payroll)
    {
        $payroll->load('employee');
        $defaultCurrency = Currency::where('is_default', true)->first();

        if ($payroll->status == 'paid') {
            return redirect()->route('admin.hr.payroll.show', $payroll)
                ->with('error', 'Paid payroll cannot be edited.');
        }

        return view('admin.hr.payroll.edit', compact('payroll', 'defaultCurrency'));
    }

    /**
     * Update the specified payroll.
     */
    public function update(Request $request, Payroll $payroll)
    {
        try {
            if ($payroll->status == 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Paid payroll cannot be edited.'
                ], 422);
            }

            $validated = $request->validate([
                'basic_salary' => 'required|numeric|min:0',
                'housing_allowance' => 'nullable|numeric|min:0',
                'transport_allowance' => 'nullable|numeric|min:0',
                'medical_allowance' => 'nullable|numeric|min:0',
                'other_allowances' => 'nullable|numeric|min:0',
                'overtime_pay' => 'nullable|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'commission' => 'nullable|numeric|min:0',
                'other_earnings' => 'nullable|numeric|min:0',
                'tax_deduction' => 'nullable|numeric|min:0',
                'social_security' => 'nullable|numeric|min:0',
                'advance_deduction' => 'nullable|numeric|min:0',
                'loan_deduction' => 'nullable|numeric|min:0',
                'penalty_deduction' => 'nullable|numeric|min:0',
                'other_deductions' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
            ]);

            // Calculate totals
            $totalEarnings = ($validated['basic_salary'] ?? 0) +
                ($validated['housing_allowance'] ?? 0) +
                ($validated['transport_allowance'] ?? 0) +
                ($validated['medical_allowance'] ?? 0) +
                ($validated['other_allowances'] ?? 0) +
                ($validated['overtime_pay'] ?? 0) +
                ($validated['bonus'] ?? 0) +
                ($validated['commission'] ?? 0) +
                ($validated['other_earnings'] ?? 0);

            $totalDeductions = ($validated['tax_deduction'] ?? 0) +
                ($validated['social_security'] ?? 0) +
                ($validated['advance_deduction'] ?? 0) +
                ($validated['loan_deduction'] ?? 0) +
                ($validated['penalty_deduction'] ?? 0) +
                ($validated['other_deductions'] ?? 0);

            $netSalary = $totalEarnings - $totalDeductions;

            $validated['total_earnings'] = $totalEarnings;
            $validated['total_deductions'] = $totalDeductions;
            $validated['net_salary'] = $netSalary;

            $payroll->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Payroll updated successfully.'
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
     * Process payroll (mark as processed).
     */
    public function process(Payroll $payroll)
    {
        try {
            if ($payroll->status == 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payroll has already been paid.'
                ], 422);
            }

            if ($payroll->status == 'processed') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payroll is already processed.'
                ], 422);
            }

            $payroll->update([
                'status' => 'processed',
                'processed_by' => Auth::id(),
                'processed_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll processed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark payroll as paid.
     */
    public function markAsPaid(Payroll $payroll)
    {
        try {
            if ($payroll->status == 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This payroll is already marked as paid.'
                ], 422);
            }

            $payroll->update([
                'status' => 'paid',
                'payment_date' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll marked as paid successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified payroll.
     */
    public function destroy(Payroll $payroll)
    {
        try {
            if ($payroll->status == 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a paid payroll record.'
                ], 422);
            }

            $payroll->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payroll deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download payroll as PDF.
     */
    public function download(Payroll $payroll)
    {
        // Implement PDF download logic
        return response()->json([
            'message' => 'PDF download functionality coming soon'
        ]);
    }

    /**
     * Get attendance summary for an employee.
     */
    private function getAttendanceSummary($employeeId, $month, $year)
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        return [
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'total_hours' => $attendances->sum('total_hours'),
        ];
    }

    /**
     * Get advance deductions for an employee.
     */
    private function getAdvanceDeductions($employeeId)
    {
        return Advance::where('employee_id', $employeeId)
            ->where('type', 'advance')
            ->where('status', 'approved')
            ->sum('deduction_amount') ?? 0;
    }

    /**
     * Get loan deductions for an employee.
     */
    private function getLoanDeductions($employeeId)
    {
        return Advance::where('employee_id', $employeeId)
            ->where('type', 'loan')
            ->where('status', 'approved')
            ->sum('deduction_amount') ?? 0;
    }
}
