<?php
// app/Http/Controllers/Admin/HR/EmployeeController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index()
    {
        $employees = Employee::with(['department', 'designation'])
            ->latest()
            ->paginate(15);
        $defaultCurrency = Currency::where('is_default','1')->first();

        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();

        return view('admin.hr.employees.index', compact('employees' ,'defaultCurrency','departments', 'designations'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();
        return view('admin.hr.employees.create', compact('departments', 'designations'));
    }

    /**
     * Store a newly created employee in storage.
     */

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:employees,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'hire_date' => 'nullable|date',
                'termination_date' => 'nullable|date',
                'supervisor_id' => 'nullable|exists:employees,id',
                'department_id' => 'nullable|exists:departments,id',
                'designation_id' => 'nullable|exists:designations,id',
                'basic_salary' => 'nullable|numeric|min:0',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'is_active' => 'boolean',
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('employee-images', 'public');
                $validated['profile_photo'] = $path;
            }

            // Set default values
            $validated['is_active'] = $request->has('is_active');
            $validated['created_by'] = auth()->id();

            // Generate employee code if not provided
            if (empty($validated['employee_code'])) {
                $validated['employee_code'] = Employee::generateEmployeeCode();
            }

            $employee = Employee::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully.',
                'data' => $employee
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
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        $employee->load(['department', 'designation', 'attendances' => function($query) {
            $query->latest()->limit(30);
        }]);

        return view('admin.hr.employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        $departments = Department::where('is_active', true)->get();
        $designations = Designation::where('is_active', true)->get();
        return view('admin.hr.employees.edit', compact('employee', 'departments', 'designations'));
    }

    /**
     * Update the specified employee in storage.
     */

    public function update(Request $request, Employee $employee)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|unique:employees,email,' . $employee->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'hire_date' => 'nullable|date',
                'termination_date' => 'nullable|date',
                'supervisor_id' => 'nullable|exists:employees,id',
                'department_id' => 'nullable|exists:departments,id',
                'designation_id' => 'nullable|exists:designations,id',
                'basic_salary' => 'nullable|numeric|min:0',
                'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'is_active' => 'boolean',
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_photo')) {
                // Delete old image
                if ($employee->profile_photo) {
                    Storage::disk('public')->delete($employee->profile_photo);
                }
                $path = $request->file('profile_photo')->store('employee-images', 'public');
                $validated['profile_photo'] = $path;
            }

            // Set default values
            $validated['is_active'] = $request->has('is_active');
            $validated['updated_by'] = auth()->id();

            $employee->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'data' => $employee
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
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee)
    {
        try {
            // Check if employee has related records
            if ($employee->attendances()->exists() ||
                $employee->leaves()->exists() ||
                $employee->payrolls()->exists() ||
                $employee->advances()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this employee because they have associated records. Please archive or reassign first.'
                ], 422);
            }

            // Delete profile image
            if ($employee->profile_image) {
                Storage::disk('public')->delete($employee->profile_image);
            }

            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle employee active status.
     */
    public function toggleStatus(Employee $employee)
    {
        try {
            $employee->update(['is_active' => !$employee->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully.',
                'data' => ['is_active' => $employee->is_active]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export employees to CSV/Excel.
     */
    public function export()
    {
        try {
            $employees = Employee::with(['department', 'designation'])->get();

            // Create CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="employees_' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($employees) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Employee ID', 'Name', 'Email', 'Phone', 'Department', 'Designation', 'Salary', 'Status']);

                foreach ($employees as $employee) {
                    fputcsv($file, [
                        $employee->employee_id,
                        $employee->full_name,
                        $employee->email,
                        $employee->phone ?? '',
                        $employee->department->name ?? 'N/A',
                        $employee->designation->name ?? 'N/A',
                        $employee->basic_salary ?? 0,
                        $employee->is_active ? 'Active' : 'Inactive'
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
}
