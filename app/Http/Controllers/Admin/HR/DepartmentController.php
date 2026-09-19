<?php
// app/Http/Controllers/Admin/HR/DepartmentController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index()
    {
        $departments = Department::withCount('employees')
            ->latest()
            ->paginate(15);

        return view('admin.hr.departments.index', compact('departments'));
    }

    /**
     * Show the specified department.
     * There is no dedicated show view, so redirect to the index.
     */
    public function show(Department $department)
    {
        return redirect()->route('admin.hr.departments.index');
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:departments,name',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            Department::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Department created successfully.'
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
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:departments,name,' . $department->id,
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            $department->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully.'
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
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        try {
            // Check if department has employees
            if ($department->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete department with assigned employees.'
                ], 422);
            }

            $department->delete();

            return response()->json([
                'success' => true,
                'message' => 'Department deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle department status.
     */
    public function toggleStatus(Department $department)
    {
        try {
            $department->update(['is_active' => !$department->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Department status updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
