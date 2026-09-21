<?php

return [
    'reason_codes' => [
        'production_waste_unrecorded' => 'Production Waste Not Recorded',
        'damaged_material' => 'Damaged Material',
        'measurement_difference' => 'Measurement Difference',
        'reel_weight_difference' => 'Reel Weight Difference',
        'receiving_difference' => 'Receiving Difference',
        'counting_error' => 'Counting Error',
        'material_found' => 'Material Found',
        'data_entry_error' => 'Data Entry Error',
        'unknown' => 'Unknown / Investigation Required',
        'other' => 'Other',
    ],

    // Foundation defaults. Approval/posting thresholds will be made configurable
    // in the approval batch rather than hard-coded into stock mutation logic.
    'reason_required_absolute_tolerance' => 0.01,
    'reason_required_percentage_tolerance' => 0.10,

    // Large discrepancies require an independent approver. The threshold is
    // based on the sum of absolute variance values so shortages and surpluses
    // cannot cancel each other out.
    'independent_approval_required_above_usd' => 100.00,

    // Reasons that remain operationally unresolved even after stock is posted.
    // Posting corrects stock for operations; investigation remains visible.
    'unresolved_reason_codes' => [
        'unknown',
    ],

    // ABC cycle-count planning by cumulative current inventory value.
    // A materials carry the highest value and are counted most frequently.
    'abc' => [
        'a_cumulative_percentage' => 80.0,
        'b_cumulative_percentage' => 95.0,
        'frequency_days' => [
            'A' => 7,
            'B' => 14,
            'C' => 30,
        ],
    ],

    'trend_default_days' => 90,
];
