<?php
// app/Http/Controllers/Admin/HR/HRSettingsController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;

class HRSettingsController extends Controller
{
    /**
     * Display general settings.
     */
    public function general()
    {
        $settings = $this->getSettings('general');
        return view('admin.hr.settings.general', compact('settings'));
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request)
    {
        try {
            $validated = $request->validate([
                'company_name' => 'nullable|string|max:255',
                'company_code' => 'nullable|string|max:50',
                'company_address' => 'nullable|string|max:500',
                'company_phone' => 'nullable|string|max:20',
                'company_email' => 'nullable|email|max:100',
                'company_website' => 'nullable|url|max:255',
                'employee_id_prefix' => 'nullable|string|max:10',
                'employee_id_length' => 'nullable|integer|min:4|max:10',
                'auto_generate_employee_id' => 'nullable|boolean',
                'allow_multiple_employees' => 'nullable|boolean',
            ]);

            $this->saveSettings('general', $validated);

            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display attendance settings.
     */
    public function attendance()
    {
        $settings = $this->getSettings('attendance');
        return view('admin.hr.settings.attendance', compact('settings'));
    }

    /**
     * Update attendance settings.
     */
    public function updateAttendance(Request $request)
    {
        try {
            $validated = $request->validate([
                'office_start_time' => 'nullable|date_format:H:i',
                'office_end_time' => 'nullable|date_format:H:i',
                'late_threshold' => 'nullable|integer|min:1|max:120',
                'early_leave_threshold' => 'nullable|integer|min:1|max:120',
                'working_days' => 'nullable|string',
                'auto_mark_absent' => 'nullable|boolean',
                'require_check_in' => 'nullable|boolean',
                'allow_overtime' => 'nullable|boolean',
                'overtime_threshold' => 'nullable|integer|min:1|max:120',
                'break_duration' => 'nullable|integer|min:0|max:120',
                'break_start_time' => 'nullable|date_format:H:i',
                'allow_break_tracking' => 'nullable|boolean',
            ]);

            $this->saveSettings('attendance', $validated);

            return response()->json([
                'success' => true,
                'message' => 'Attendance settings updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display payroll settings.
     */
    public function payroll()
    {
        $settings = $this->getSettings('payroll');
        $currencies = Currency::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();
        return view('admin.hr.settings.payroll', compact('settings', 'currencies', 'defaultCurrency'));
    }

    /**
     * Update payroll settings.
     */
    public function updatePayroll(Request $request)
    {
        try {
            $validated = $request->validate([
                'payroll_cycle' => 'nullable|string|in:monthly,biweekly,weekly,semimonthly',
                'payment_method' => 'nullable|string|in:bank_transfer,cash,cheque,mobile_money',
                'payroll_processing_date' => 'nullable|string',
                'payroll_currency' => 'nullable|exists:currencies,id',
                'housing_allowance_percent' => 'nullable|numeric|min:0|max:100',
                'transport_allowance_percent' => 'nullable|numeric|min:0|max:100',
                'medical_allowance_percent' => 'nullable|numeric|min:0|max:100',
                'tax_deduction_percent' => 'nullable|numeric|min:0|max:50',
                'social_security_percent' => 'nullable|numeric|min:0|max:20',
                'max_deduction_limit' => 'nullable|numeric|min:0|max:100',
            ]);

            $this->saveSettings('payroll', $validated);

            return response()->json([
                'success' => true,
                'message' => 'Payroll settings updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings for a specific module.
     */
    private function getSettings($module)
    {
        // This should be replaced with your actual settings storage logic
        // For now, returning default values
        $defaults = [
            'general' => [
                'company_name' => 'My Company',
                'company_code' => 'MC001',
                'company_address' => '123 Main Street',
                'company_phone' => '+1234567890',
                'company_email' => 'info@company.com',
                'company_website' => 'https://company.com',
                'employee_id_prefix' => 'EMP-',
                'employee_id_length' => 6,
                'auto_generate_employee_id' => true,
                'allow_multiple_employees' => true,
            ],
            'attendance' => [
                'office_start_time' => '09:00',
                'office_end_time' => '18:00',
                'late_threshold' => 15,
                'early_leave_threshold' => 15,
                'working_days' => '1,2,3,4,5',
                'auto_mark_absent' => true,
                'require_check_in' => true,
                'allow_overtime' => true,
                'overtime_threshold' => 30,
                'break_duration' => 30,
                'break_start_time' => '13:00',
                'allow_break_tracking' => false,
            ],
            'payroll' => [
                'payroll_cycle' => 'monthly',
                'payment_method' => 'bank_transfer',
                'payroll_processing_date' => 25,
                'payroll_currency' => null,
                'housing_allowance_percent' => 20,
                'transport_allowance_percent' => 10,
                'medical_allowance_percent' => 5,
                'tax_deduction_percent' => 10,
                'social_security_percent' => 5,
                'max_deduction_limit' => 50,
            ]
        ];

        return $defaults[$module] ?? [];
    }

    /**
     * Save settings for a specific module.
     */
    private function saveSettings($module, $data)
    {
        // This should be replaced with your actual settings storage logic
        // For now, we'll just return true
        return true;
    }
}
