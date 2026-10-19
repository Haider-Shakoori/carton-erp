<?php

/*
|--------------------------------------------------------------------------
| Factory carton weight reference (customer photograph, 19 Oct 2026)
|--------------------------------------------------------------------------
|
| The rightmost numeric column supplies NET FINISHED CARTON grams for some
| dimensions/ply combinations. These are transcriptions of a low-resolution
| handwritten source, NOT approved material-by-material recipes.
|
| The other columns contain paper / manufacturing figures but the headings
| and their units cannot be read reliably enough to assign them to stock
| materials. Do NOT infer a GSM, wastage rate, glue recipe, kg-per-roll or
| stock issue from net weight. Only the readings below are used for an
| indicative weight audit. All values need the factory's confirmation.
|
| Dimensions are centimetres as printed on the sheet; match only the
| original client-carton master, by exact physical dimensions AND ply.
| They are never used to overwrite products, BOMs or historical stock.
*/
return [
    'source' => 'Factory handwritten carton weight sheet — image supplied 2026-10-19',
    'status' => 'transcribed_requires_review',
    'weights' => [
        ['dimensions_cm' => [53, 27, 27], 'ply' => 5, 'net_weight_g' => 590],
        ['dimensions_cm' => [53, 27, 27], 'ply' => 3, 'net_weight_g' => 300],
        ['dimensions_cm' => [57, 24, 26], 'ply' => 5, 'net_weight_g' => 560],
        ['dimensions_cm' => [57, 24, 26], 'ply' => 3, 'net_weight_g' => 280],
        ['dimensions_cm' => [30, 25.5, 23], 'ply' => 3, 'net_weight_g' => 240],
        ['dimensions_cm' => [41, 32.5, 30.5], 'ply' => 5, 'net_weight_g' => 600],
        ['dimensions_cm' => [41, 32.5, 30.5], 'ply' => 3, 'net_weight_g' => 300],
        ['dimensions_cm' => [51, 40, 13], 'ply' => 5, 'net_weight_g' => 500],
        ['dimensions_cm' => [51, 40, 13], 'ply' => 3, 'net_weight_g' => 250],
        ['dimensions_cm' => [42, 25.5, 26], 'ply' => 5, 'net_weight_g' => 460],
        ['dimensions_cm' => [42, 25.5, 26], 'ply' => 3, 'net_weight_g' => 230],
        ['dimensions_cm' => [58, 46, 36.5], 'ply' => 5, 'net_weight_g' => 1160],
        ['dimensions_cm' => [58, 46, 36.5], 'ply' => 3, 'net_weight_g' => 580],
        ['dimensions_cm' => [58, 46, 38], 'ply' => 5, 'net_weight_g' => 1190],
        ['dimensions_cm' => [58, 46, 38], 'ply' => 3, 'net_weight_g' => 595],
        ['dimensions_cm' => [61.5, 37, 48], 'ply' => 5, 'net_weight_g' => 1170],
        ['dimensions_cm' => [61.5, 37, 48], 'ply' => 3, 'net_weight_g' => 585],
        ['dimensions_cm' => [31.5, 26.5, 26], 'ply' => 3, 'net_weight_g' => 245],
    ],
];
