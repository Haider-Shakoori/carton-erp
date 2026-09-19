<?php
// app/Http/Controllers/Admin/ShareholderSettingsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shareholder;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShareholderSettingsController extends Controller
{
    /**
     * Display shareholder settings page.
     */
    public function index()
    {
        $shareholders = Shareholder::all();
        $totalPercentage = $shareholders->sum('share_percentage');
        $totalCapital = $shareholders->sum('capital_contribution');
        $defaultCurrency = Currency::where('is_default', true)->first();

        return view('admin.shareholders.settings', compact(
            'shareholders',
            'totalPercentage',
            'totalCapital',
            'defaultCurrency'
        ));
    }

    /**
     * Update shareholder percentages.
     */
    public function updatePercentages(Request $request)
    {
        try {
            $validated = $request->validate([
                'percentages' => 'required|array',
                'percentages.*' => 'required|numeric|min:0|max:100',
                'shareholder_ids' => 'required|array',
                'shareholder_ids.*' => 'exists:shareholders,id',
            ]);

            $totalPercentage = array_sum($validated['percentages']);

            // Check if total is 100%
            if (round($totalPercentage, 2) != 100) {
                return response()->json([
                    'success' => false,
                    'message' => "Total share percentage must equal 100%. Current total: {$totalPercentage}%"
                ], 422);
            }

            DB::beginTransaction();

            // Update each shareholder
            foreach ($validated['shareholder_ids'] as $index => $id) {
                $shareholder = Shareholder::find($id);
                if ($shareholder) {
                    $shareholder->update([
                        'share_percentage' => $validated['percentages'][$index]
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Share percentages updated successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-distribute shares equally.
     */
    public function autoDistribute(Request $request)
    {
        try {
            DB::beginTransaction();

            $shareholders = Shareholder::all();
            $count = $shareholders->count();

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shareholders found.'
                ], 422);
            }

            // Calculate equal share
            $equalShare = round(100 / $count, 2);

            // Adjust the last shareholder to make exact 100%
            $totalAfterEqual = $equalShare * $count;
            $adjustment = 100 - $totalAfterEqual;

            $shareholdersArray = $shareholders->values()->toArray();

            foreach ($shareholdersArray as $index => $shareholderData) {
                $shareholder = Shareholder::find($shareholderData['id']);
                $percentage = $equalShare;

                // Add adjustment to the last shareholder
                if ($index === count($shareholdersArray) - 1) {
                    $percentage += $adjustment;
                }

                $shareholder->update([
                    'share_percentage' => round($percentage, 2)
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shares distributed equally.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate current percentages.
     */
    public function validatePercentages(Request $request)
    {
        try {
            $shareholders = Shareholder::all();
            $totalPercentage = $shareholders->sum('share_percentage');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_percentage' => $totalPercentage,
                    'is_valid' => round($totalPercentage, 2) == 100,
                    'message' => round($totalPercentage, 2) == 100
                        ? 'All good! Total is 100%.'
                        : "Total is {$totalPercentage}%. Please adjust to make it 100%."
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
