<?php
// app/Http/Controllers/Admin/HR/DesignationController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use App\Models\Department;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of designations.
     */
    public function index()
    {
        $designations = Designation::with('department')
            ->latest()
            ->paginate(15);

        $departments = Department::where('is_active', true)->get();

        return view('admin.hr.designations.index', compact('designations', 'departments'));
    }

    /**
     * Display the specified designation.
     */
    public function show(Designation $designation)
    {
        $designation->load(['department', 'employees.department']);

        return view('admin.hr.designations.show', compact('designation'));
    }

    /**
     * Show the form for editing the specified designation.
     */
    public function edit(Designation $designation)
    {
        $designation->load('department');

        $departments = Department::where('is_active', true)->get();

        return view('admin.hr.designations.edit', compact('designation', 'departments'));
    }

    /**
     * Store a newly created designation.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:designations,name',
                'description' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            Designation::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Designation created successfully.'
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
     * Update the specified designation.
     */
    public function update(Request $request, Designation $designation)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:designations,name,' . $designation->id,
                'description' => 'nullable|string',
                'department_id' => 'nullable|exists:departments,id',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            $designation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Designation updated successfully.'
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
     * Remove the specified designation.
     */
    public function destroy(Designation $designation)
    {
        try {
            // Check if designation has employees
            if ($designation->employees()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete designation with assigned employees.'
                ], 422);
            }

            $designation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Designation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle designation status.
     */
    public function toggleStatus(Designation $designation)
    {
        try {
            $designation->update(['is_active' => !$designation->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Designation status updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
