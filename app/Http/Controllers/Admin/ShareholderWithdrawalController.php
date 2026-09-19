<?php
// app/Http/Controllers/Admin/ShareholderWithdrawalController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShareholderWithdrawal;
use App\Models\Shareholder;
use App\Models\Currency;
use Illuminate\Http\Request;

class ShareholderWithdrawalController extends Controller
{
    /**
     * Display a listing of withdrawals.
     */
    public function index(Request $request)
    {
        $query = ShareholderWithdrawal::with(['shareholder', 'createdBy', 'approvedBy']);

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by shareholder
        if ($request->shareholder_id) {
            $query->where('shareholder_id', $request->shareholder_id);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('withdrawal_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('withdrawal_date', '<=', $request->end_date);
        }

        $withdrawals = $query->latest()->paginate(15);
        $shareholders = Shareholder::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.shareholder-withdrawals.index', compact(
            'withdrawals',
            'shareholders',
            'defaultCurrency'
        ));
    }

    /**
     * Display pending withdrawals.
     */
    public function pending()
    {
        $withdrawals = ShareholderWithdrawal::with(['shareholder', 'createdBy'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        $shareholders = Shareholder::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.shareholder-withdrawals.pending', compact(
            'withdrawals',
            'shareholders',
            'defaultCurrency'
        ));
    }

    /**
     * Display the specified withdrawal.
     */
    public function show(ShareholderWithdrawal $withdrawal)
    {
        $withdrawal->load(['shareholder', 'createdBy', 'approvedBy', 'transaction']);
        $defaultCurrency = Currency::where('is_default', true)->first();
        $balance = $withdrawal->shareholder->getBalanceAttribute();

        return view('admin.shareholder-withdrawals.show', compact(
            'withdrawal',
            'defaultCurrency',
            'balance'
        ));
    }

    /**
     * Approve a withdrawal request.
     */
    public function approve(ShareholderWithdrawal $withdrawal)
    {
        try {
            if ($withdrawal->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending withdrawals can be approved.'
                ], 422);
            }

            $withdrawal->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject a withdrawal request.
     */
    public function reject(Request $request, ShareholderWithdrawal $withdrawal)
    {
        try {
            if ($withdrawal->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending withdrawals can be rejected.'
                ], 422);
            }

            $validated = $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $withdrawal->update([
                'status' => 'rejected',
                'notes' => $validated['reason'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal rejected successfully.'
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
     * Process a withdrawal (create transaction).
     */
    public function process(ShareholderWithdrawal $withdrawal)
    {
        try {
            if ($withdrawal->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved withdrawals can be processed.'
                ], 422);
            }

            // Check if shareholder has sufficient balance
            $balance = $withdrawal->shareholder->getBalanceAttribute();
            if ($balance < $withdrawal->amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance for this withdrawal.'
                ], 422);
            }

            $withdrawal->createTransaction();

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal processed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
