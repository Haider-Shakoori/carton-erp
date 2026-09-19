<?php
// app/Http/Controllers/Admin/HR/LeaveController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Display a listing of leave requests.
     */
    public function index(Request $request)
    {
        $query = Leave::with(['employee', 'leaveType']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leaves = $query->latest()->paginate(15);
        $employees = Employee::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();

        // Statistics
        $pending = Leave::where('status', 'pending')->count();
        $approved = Leave::where('status', 'approved')->count();
        $rejected = Leave::where('status', 'rejected')->count();

        return view('admin.hr.leaves.index', compact(
            'leaves', 'employees', 'leaveTypes', 'pending', 'approved', 'rejected'
        ));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('admin.hr.leaves.create', compact('employees', 'leaveTypes'));
    }

    /**
     * Store a newly created leave request.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $days = $startDate->diffInDays($endDate) + 1;

            $validated['days'] = $days;
            $validated['status'] = 'pending';

            Leave::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully.'
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
     * Show the form for editing the specified leave request.
     */
    public function edit(Leave $leave)
    {
        $employees = Employee::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('admin.hr.leaves.edit', compact('leave', 'employees', 'leaveTypes'));
    }

    /**
     * Update the specified leave request.
     */
    public function update(Request $request, Leave $leave)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'leave_type_id' => 'required|exists:leave_types,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'reason' => 'nullable|string',
            ]);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $validated['days'] = $startDate->diffInDays($endDate) + 1;

            $leave->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Leave request updated successfully.'
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
     * Approve a leave request.
     */
    public function approve(Leave $leave)
    {
        try {
            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This leave request has already been processed.'
                ], 422);
            }

            $leave->update([
                'status' => 'approved',
                'approved_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a leave request.
     */
    public function reject(Request $request, Leave $leave)
    {
        try {
            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This leave request has already been processed.'
                ], 422);
            }

            $validated = $request->validate([
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            $leave->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected successfully.'
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
     * Remove the specified leave request.
     */
    public function destroy(Leave $leave)
    {
        try {
            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display leave types management.
     */
    public function types()
    {
        $leaveTypes = LeaveType::all();
        return view('admin.hr.leaves.types', compact('leaveTypes'));
    }

    /**
     * Store a new leave type.
     */
    public function storeType(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:leave_types,name',
                'days_allowed' => 'required|integer|min:1',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            LeaveType::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Leave type created successfully.'
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
     * Update a leave type.
     */
    public function updateType(Request $request, LeaveType $leaveType)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:leave_types,name,' . $leaveType->id,
                'days_allowed' => 'required|integer|min:1',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $validated['is_active'] = $request->has('is_active');

            $leaveType->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Leave type updated successfully.'
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
     * Delete a leave type.
     */
    public function destroyType(LeaveType $leaveType)
    {
        try {
            // Check if leave type is being used
            if ($leaveType->leaves()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this leave type because it has associated leave requests.'
                ], 422);
            }

            $leaveType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave type deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the leave balance for an employee on a given leave type.
     */
    public function getBalance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);

        $used = (int) Leave::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('status', 'approved')
            ->sum('days');

        $available = $leaveType->days_allowed - $used;

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $available,
                'used' => $used,
                'total_allowed' => $leaveType->days_allowed,
            ],
        ]);
    }
}
