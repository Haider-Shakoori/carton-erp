<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Carton blank formulas — canonical configuration
    |--------------------------------------------------------------------------
    |
    | All carton/corrugated calculations must resolve their constants and
    | defaults from here. Do not duplicate these values in controllers,
    | services, blades or JavaScript.
    |
    */

    // Client Excel formula constant used by every carton_3d paper row:
    //   PaperKg = ReelLength x ReelHeight x GSM x MultiplicationLayer / 1,550,000
    'formula_constant' => 1550000,

    // Square inch => square metre conversion used by the carton blank area.
    'sq_inch_to_m2' => 0.00064516,

    // Default row wastage (%), applied on top of the physical paper kg.
    // The commercial quotation basis deliberately excludes wastage.
    'default_wastage_percentage' => 5,

    /*
    |--------------------------------------------------------------------------
    | Standard production consumption policy
    |--------------------------------------------------------------------------
    |
    | Formula-based BOM rows use their frozen production-order requirement as
    | the default actual consumption at completion, scaled to the ACTUAL
    | manufactured quantity. This lets inventory stay accurate without asking
    | operators to guess paper/glue quantities. Measured actuals can still
    | override non-roll formula materials such as adhesive ingredients.
    |
    */
    'standard_consumption' => [
        'version' => '1.0',
        'name' => 'Factory Standard Consumption',
        'automatic_formula_types' => [
            'carton_3d',
            'cut_roll',
            'adhesive_mix',
            'fixed_percentage',
            'fixed_rate',
        ],
        'paper_formula' => 'ReelLength × ReelHeight × GSM × LayerMultiplier ÷ FormulaConstant × ManufacturedQty × (1 + Waste%)',
        'adhesive_formula' => '((BoardAreaM² × GlueLines × DryGlueGSM/line × (1 + GlueWaste%)) ÷ 1000 ÷ SolidsFraction) × RecipeFraction × ManufacturedQty',
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional lamination finishing
    |--------------------------------------------------------------------------
    |
    | Lamination is not a permanent part of every master BOM. When enabled on
    | a sale line, the system creates an order-specific hidden BOM row using
    | the carton blank area. These defaults are factory configuration and can
    | be calibrated later without changing historical order-specific BOMs.
    |
    */
    'lamination' => [
        'material_name' => 'Lamination Plastic',
        'film_gsm' => 20.0,
        'sides' => 1,
        'wastage_percentage' => 5.0,
        'formula' => 'BoardAreaM² × FilmGSM × Sides ÷ 1000 × (1 + Waste%)',
    ],

    // Supported dimension units => inches.
    'length_units' => [
        'inch' => 1.0,
        'in' => 1.0,
        'cm' => 0.3937007874015748,
        'mm' => 0.03937007874015748,
    ],

    /*
    |--------------------------------------------------------------------------
    | Box styles
    |--------------------------------------------------------------------------
    |
    | RSC keeps the verified client source-of-truth blank formulas. Additional
    | styles can be registered here without rewriting the BOM calculation flow.
    | The service resolves the style through box_style strategy methods.
    |
    */
    'box_styles' => [
        'RSC' => [
            'label' => 'Regular Slotted Carton (RSC)',
            'reel_length' => '((L + W) x 2) + 4',
            'reel_height' => 'W + H + 1',
        ],
    ],

    'default_box_style' => 'RSC',

    /*
    |--------------------------------------------------------------------------
    | Flutes
    |--------------------------------------------------------------------------
    |
    | Metadata only. Take-up factors are future-ready physical parameters and
    | are NOT applied to the verified client 125x5 + 145x1 formula unless the
    | client explicitly confirms it.
    |
    */
    'flutes' => [
        'A' => ['label' => 'A Flute', 'take_up_factor' => 1.54],
        'B' => ['label' => 'B Flute', 'take_up_factor' => 1.36],
        'C' => ['label' => 'C Flute', 'take_up_factor' => 1.45],
        'E' => ['label' => 'E Flute', 'take_up_factor' => 1.27],
        'BC' => ['label' => 'BC Double Flute', 'take_up_factor' => 2.81],
    ],

    /*
    |--------------------------------------------------------------------------
    | Printing options
    |--------------------------------------------------------------------------
    |
    | A simple printing selection. print_cost_afn is a per finished carton
    | commercial cost; it may be overridden per quotation.
    |
    */
    'printing' => [
        'none' => ['label' => 'No Printing', 'print_cost_afn' => 0],
        'single_color' => ['label' => 'Single Color', 'print_cost_afn' => 0],
        'multi_color' => ['label' => 'Multi Color', 'print_cost_afn' => 0],
        'flexo' => ['label' => 'Flexo Print', 'print_cost_afn' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adhesive Mix (mixing materials) — dimension driven formula
    |--------------------------------------------------------------------------
    |
    | The four carton mixing materials (corn flour, seligate/sodium silicate,
    | caustic soda and borax) are calculated from the carton board blank area
    | instead of being stored as fixed per-carton quantities. These values are
    | factory defaults: a BOM row may override them, and the effective values
    | are snapshotted into the BOM row's formula_data so historical BOMs and
    | manual sale quotations stay reproducible if the defaults change later.
    |
    */

    'adhesive' => [

        // Number of glue lines applied to the board blank.
        'glue_lines' => 4,

        // Dry glue applied per line, in grams per square metre.
        'dry_glue_gsm_per_line' => 5.5,

        // Glue preparation/application losses, in percent.
        'glue_wastage_percentage' => 5,

        // Solids fraction of the prepared wet adhesive, in percent.
        'adhesive_solids_percentage' => 35,

        // Wet adhesive recipe fractions (kg of ingredient per kg of wet glue).
        // Provisional source-of-truth defaults until the client confirms the
        // exact factory recipe.
        'recipe' => [
            'corn_flour' => 0.265,
            'seligate' => 0.189,
            'caustic_soda' => 0.012,
            'borax' => 0.0037,
        ],

        // Raw-material product name (lowercased) => recipe key. Aliases are
        // allowed so renamed or alternate spellings keep resolving.
        'materials' => [
            'corn flour' => 'corn_flour',
            'seligate' => 'seligate',
            'seligate (glue)' => 'seligate',
            'sodium silicate' => 'seligate',
            'caustic soda' => 'caustic_soda',
            'borax' => 'borax',
        ],

        // Preferred raw-material product per recipe key. Exact name first,
        // then the name-alias map above as fallback.
        'recipe_materials' => [
            'corn_flour' => 'Corn Flour',
            'seligate' => 'Seligate (Glue)',
            'caustic_soda' => 'Caustic Soda',
            'borax' => 'Borax',
        ],
    ],
];
