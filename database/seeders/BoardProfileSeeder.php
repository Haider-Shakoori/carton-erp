<?php

namespace Database\Seeders;

use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Client baseline board profiles.
 *
 * The "Standard 5 Ply 125/145" profile preserves the verified client source
 * of truth exactly:
 *   125 GSM x multiplication_layer 5
 *   145 GSM x multiplication_layer 1
 *
 * Idempotent: existing profiles and their layers are never overwritten, so
 * re-running the seeder cannot destroy operator edits or historical meaning.
 */
class BoardProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('board_profiles') || ! Schema::hasTable('board_profile_layers')) {
            return;
        }

        foreach ($this->profiles() as $profile) {
            $boardProfile = BoardProfile::firstOrCreate(
                ['code' => $profile['code']],
                [
                    'name' => $profile['name'],
                    'description' => $profile['description'],
                    'ply' => $profile['ply'],
                    'flute_type' => $profile['flute_type'] ?? null,
                    'wastage_percentage' => $profile['wastage_percentage'] ?? 5,
                    'is_active' => true,
                    'version' => '1.0',
                ]
            );

            if ($boardProfile->layers()->exists()) {
                continue;
            }

            foreach ($profile['layers'] as $index => $layer) {
                BoardProfileLayer::create([
                    'board_profile_id' => $boardProfile->id,
                    'sort_order' => $index,
                    'role' => $layer['role'],
                    'component_type' => 'paper',
                    'material_id' => $this->materialId($layer['material'] ?? null),
                    'gsm' => $layer['gsm'],
                    'multiplication_layer' => $layer['multiplication_layer'],
                    'flute_type' => $layer['flute_type'] ?? $profile['flute_type'] ?? null,
                    'commercial_work_enabled' => true,
                    'notes' => $layer['notes'] ?? null,
                ]);
            }
        }
    }

    private function materialId(?string $name): ?int
    {
        if ($name === null || ! Schema::hasTable('products')) {
            return null;
        }

        $id = Product::where('name', $name)->value('id');

        return $id ? (int) $id : null;
    }

    private function profiles(): array
    {
        return [
            [
                'code' => 'STD-3PLY',
                'name' => 'Standard 3 Ply',
                'description' => 'Baseline 3 ply board used for light cartons.',
                'ply' => 3,
                'flute_type' => 'B',
                'layers' => [
                    [
                        'role' => 'outer_liner',
                        'material' => 'Kraft Liner',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'flute',
                        'material' => 'Fluting',
                        'gsm' => 120,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'inner_liner',
                        'material' => 'Test Liner',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                ],
            ],
            [
                'code' => 'STD-5PLY-125-145',
                'name' => 'Standard 5 Ply 125/145',
                'description' => 'Client-verified 5 ply recipe: 125 GSM x 5 and 145 GSM x 1.',
                'ply' => 5,
                'flute_type' => 'BC',
                'layers' => [
                    [
                        'role' => 'generic_client_formula',
                        'material' => 'Fluting',
                        'gsm' => 125,
                        'multiplication_layer' => 5,
                        'notes' => 'Client source of truth: 125 GSM x multiplication layer 5.',
                    ],
                    [
                        'role' => 'generic_client_formula',
                        'material' => 'Kraft Liner',
                        'gsm' => 145,
                        'multiplication_layer' => 1,
                        'notes' => 'Client source of truth: 145 GSM x multiplication layer 1.',
                    ],
                ],
            ],
            [
                'code' => 'HD-5PLY',
                'name' => 'Heavy Duty 5 Ply',
                'description' => 'Heavier liners for export and stacking strength.',
                'ply' => 5,
                'flute_type' => 'BC',
                'layers' => [
                    [
                        'role' => 'outer_liner',
                        'material' => 'Kraft Liner',
                        'gsm' => 200,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'flute',
                        'material' => 'Fluting',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'center_liner',
                        'material' => 'Semi Kraft',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'flute',
                        'material' => 'Fluting',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'inner_liner',
                        'material' => 'Test Liner',
                        'gsm' => 150,
                        'multiplication_layer' => 1,
                    ],
                ],
            ],
            [
                'code' => 'WHITE-5PLY',
                'name' => 'White Liner 5 Ply',
                'description' => 'White outer liner board for printed retail cartons.',
                'ply' => 5,
                'flute_type' => 'BC',
                'layers' => [
                    [
                        'role' => 'outer_liner',
                        'material' => 'White Liner',
                        'gsm' => 170,
                        'multiplication_layer' => 1,
                    ],
                    [
                        'role' => 'flute',
                        'material' => 'Fluting',
                        'gsm' => 125,
                        'multiplication_layer' => 3,
                    ],
                    [
                        'role' => 'inner_liner',
                        'material' => 'Test Liner',
                        'gsm' => 145,
                        'multiplication_layer' => 1,
                    ],
                ],
            ],
        ];
    }
}
