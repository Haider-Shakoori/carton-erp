<?php
// app/Http/Controllers/Admin/HR/AdvanceController.php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Currency;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdvanceController extends Controller
{
    /**
     * Display a listing of advances.
     */
    public function index(Request $request)
    {
        $query = Advance::with(['employee']);
        $defaultCurrency = Currency::where('is_default','1')->first();

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by employee
        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $advances = $query->latest()->paginate(15);
        $employees = Employee::where('is_active', true)->get();

        // Statistics
        $pending = Advance::where('status', 'pending')->count();
        $approved = Advance::where('status', 'approved')->count();
        $paid = Advance::where('status', 'paid')->count();
        $totalAmount = Advance::where('status', 'approved')->sum('amount') + Advance::where('status', 'paid')->sum('amount');

        return view('admin.hr.advances.index', compact(
            'advances', 'employees', 'pending', 'approved', 'paid', 'totalAmount','defaultCurrency'
        ));
    }

    /**
     * Show the form for creating a new advance.
     */
    public function create()
    {
        $employees = Employee::where('is_active', true)->get();
        return view('admin.hr.advances.create', compact('employees'));
    }

    /**
     * Store a newly created advance.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'amount' => 'required|numeric|min:1',
                'type' => 'required|in:advance,loan',
                'request_date' => 'required|date',
                'deduction_start_date' => 'nullable|date',
                'deduction_end_date' => 'nullable|date|after_or_equal:deduction_start_date',
                'deduction_amount' => 'nullable|numeric|min:1',
                'reason' => 'nullable|string',
            ]);

            $validated['status'] = 'pending';
            $validated['remaining_amount'] = $validated['amount'];

            Advance::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Advance request submitted successfully.'
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
     * Show the form for editing the specified advance.
     */
    public function edit(Advance $advance)
    {
        $employees = Employee::where('is_active', true)->get();
        return view('admin.hr.advances.edit', compact('advance', 'employees'));
    }

    /**
     * Update the specified advance.
     */
    public function update(Request $request, Advance $advance)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'amount' => 'required|numeric|min:1',
                'type' => 'required|in:advance,loan',
                'request_date' => 'required|date',
                'deduction_start_date' => 'nullable|date',
                'deduction_end_date' => 'nullable|date|after_or_equal:deduction_start_date',
                'deduction_amount' => 'nullable|numeric|min:1',
                'reason' => 'nullable|string',
            ]);

            $advance->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Advance updated successfully.'
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
     * Approve an advance request.
     */
    public function approve(Advance $advance)
    {
        try {
            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This advance has already been processed.'
                ], 422);
            }

            $advance->update([
                'status' => 'approved',
                'approved_at' => Carbon::now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Advance approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an advance request.
     */
    public function reject(Request $request, Advance $advance)
    {
        try {
            if ($advance->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This advance has already been processed.'
                ], 422);
            }

            $advance->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Advance rejected successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a deduction for an advance.
     */
    public function deduct(Request $request, Advance $advance)
    {
        try {
            $validated = $request->validate([
                'deduction_amount' => 'required|numeric|min:1',
                'deduction_date' => 'required|date',
            ]);

            if ($advance->status !== 'approved' && $advance->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'This advance is not approved yet.'
                ], 422);
            }

            if ($advance->remaining_amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This advance has already been fully paid.'
                ], 422);
            }

            if ($validated['deduction_amount'] > $advance->remaining_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deduction amount cannot exceed remaining balance.',
                    'data' => ['remaining_amount' => $advance->remaining_amount]
                ], 422);
            }

            $newRemaining = $advance->remaining_amount - $validated['deduction_amount'];
            $status = $newRemaining <= 0 ? 'paid' : 'approved';

            $advance->update([
                'remaining_amount' => $newRemaining,
                'status' => $status,
                'paid_at' => $newRemaining <= 0 ? Carbon::now() : $advance->paid_at,
            ]);

            // Record deduction in a separate table (optional)
            // DeductionLog::create([...]);

            return response()->json([
                'success' => true,
                'message' => 'Deduction processed successfully.',
                'data' => [
                    'remaining_amount' => $newRemaining,
                    'status' => $status
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
     * Remove the specified advance.
     */
    public function destroy(Advance $advance)
    {
        try {
            if ($advance->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a fully paid advance.'
                ], 422);
            }

            $advance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Advance deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
