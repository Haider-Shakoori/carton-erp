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
];
