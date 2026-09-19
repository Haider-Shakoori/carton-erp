<?php
// app/Http/Controllers/Admin/ShareholderController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shareholder;
use App\Models\ShareholderWithdrawal;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ShareholderController extends Controller
{
    /**
     * Display a listing of shareholders.
     */
    public function index(Request $request)
    {
        $query = Shareholder::query();

        // Search filter
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%");
        }

        // Status filter
        if ($request->status !== null) {
            $query->where('is_active', $request->status);
        }

        $shareholders = $query->latest()->paginate(15);
        $defaultCurrency = Currency::where('is_default', true)->first();

        // Get balances for each shareholder
        foreach ($shareholders as $shareholder) {
            $shareholder->balance = $shareholder->getBalanceAttribute();
        }

        return view('admin.shareholders.index', compact('shareholders', 'defaultCurrency'));
    }

    /**
     * Show the form for creating a new shareholder.
     */
    public function create()
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        return view('admin.shareholders.create', compact('defaultCurrency'));
    }

    /**
     * Store a newly created shareholder.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:100',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'share_percentage' => 'required|numeric|min:0|max:100',
                'capital_contribution' => 'nullable|numeric|min:0',
                'joining_date' => 'nullable|date',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            // Generate code
            $validated['code'] = Shareholder::generateCode();
            $validated['is_active'] = $request->has('is_active');

            // Handle profile image
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('shareholder-images', 'public');
                $validated['profile_image'] = $path;
            }

            $validated['created_by'] = auth()->id();

            $shareholder = Shareholder::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Shareholder created successfully.',
                'data' => $shareholder
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
     * Display the specified shareholder.
     */
    public function show(Shareholder $shareholder)
    {
        $defaultCurrency = Currency::where('is_default', true)->first();

        // Get balance
        $balance = $shareholder->getBalanceAttribute();

        // Get distributions
        $distributions = $shareholder->profitDistributions()
            ->with(['distribution'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $distributionCount = $shareholder->profitDistributions()->count();
        $totalDistributed = $shareholder->profitDistributions()->sum('amount');

        // Get withdrawals
        $withdrawals = $shareholder->withdrawals()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $totalWithdrawn = $shareholder->withdrawals()->where('status', 'paid')->sum('amount');

        return view('admin.shareholders.show', compact(
            'shareholder',
            'defaultCurrency',
            'balance',
            'distributions',
            'distributionCount',
            'totalDistributed',
            'withdrawals',
            'totalWithdrawn'
        ));
    }

    /**
     * Show the form for editing the specified shareholder.
     */
    public function edit(Shareholder $shareholder)
    {
        $defaultCurrency = Currency::where('is_default', true)->first();
        return view('admin.shareholders.edit', compact('shareholder', 'defaultCurrency'));
    }

    /**
     * Update the specified shareholder.
     */
    public function update(Request $request, Shareholder $shareholder)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:100|unique:shareholders,email,' . $shareholder->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'share_percentage' => 'required|numeric|min:0|max:100',
                'capital_contribution' => 'nullable|numeric|min:0',
                'joining_date' => 'nullable|date',
                'is_active' => 'boolean',
                'notes' => 'nullable|string',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $validated['is_active'] = $request->has('is_active');

            // Handle profile image
            if ($request->hasFile('profile_image')) {
                // Delete old image
                if ($shareholder->profile_image) {
                    Storage::disk('public')->delete($shareholder->profile_image);
                }
                $path = $request->file('profile_image')->store('shareholder-images', 'public');
                $validated['profile_image'] = $path;
            }

            $shareholder->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Shareholder updated successfully.',
                'data' => $shareholder
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
     * Remove the specified shareholder.
     */
    public function destroy(Shareholder $shareholder)
    {
        try {
            // Check if shareholder has distributions or withdrawals
            if ($shareholder->profitDistributions()->exists() || $shareholder->withdrawals()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete shareholder with existing distributions or withdrawals.'
                ], 422);
            }

            // Delete profile image
            if ($shareholder->profile_image) {
                Storage::disk('public')->delete($shareholder->profile_image);
            }

            $shareholder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Shareholder deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle shareholder status.
     */
    public function toggleStatus(Shareholder $shareholder)
    {
        try {
            $shareholder->update(['is_active' => !$shareholder->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Shareholder status updated successfully.',
                'data' => ['is_active' => $shareholder->is_active]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shareholder balance.
     */
    public function getBalance(Shareholder $shareholder)
    {
        $balance = $shareholder->getBalanceAttribute();
        $defaultCurrency = Currency::where('is_default', true)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'shareholder_id' => $shareholder->id,
                'name' => $shareholder->name,
                'balance' => $balance,
                'currency_symbol' => $defaultCurrency?->symbol ?? '$'
            ]
        ]);
    }

    /**
     * Request a withdrawal.
     */
    public function requestWithdrawal(Request $request, Shareholder $shareholder)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'reason' => 'nullable|string',
            ]);

            // Check if amount exceeds balance
            $balance = $shareholder->getBalanceAttribute();
            if ($validated['amount'] > $balance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance for this withdrawal.'
                ], 422);
            }

            $withdrawal = ShareholderWithdrawal::create([
                'shareholder_id' => $shareholder->id,
                'amount' => $validated['amount'],
                'reason' => $validated['reason'] ?? null,
                'withdrawal_date' => now(),
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request submitted successfully.',
                'data' => $withdrawal
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
}
