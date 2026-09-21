<?php

namespace Database\Seeders;

use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\BoardProfile;
use App\Models\FinishedGoodSpecification;
use App\Models\User;
use App\Services\CartonSpecificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerCartonBomSeeder extends Seeder
{
    private const PROFILE_BY_PLY = [
        3 => 'STD-3PLY',
        5 => 'STD-5PLY-125-145',
    ];

    public function run(): void
    {
        $specifications = FinishedGoodSpecification::query()
            ->importedClientCartons()
            ->with(['product', 'customer'])
            ->orderBy('id')
            ->get();

        if ($specifications->isEmpty()) {
            $this->command?->warn(
                'No imported customer carton specifications were found; BOM seed skipped.'
            );

            return;
        }

        $service = app(CartonSpecificationService::class);
        $creatorId = $this->resolveCreatorId();

        $created = 0;
        $skippedExistingBom = 0;
        $skippedMissingDimensions = 0;
        $skippedUnsupportedPly = 0;

        foreach ($specifications as $specification) {
            if (
                ! $specification->product
                || (float) ($specification->length ?? 0) <= 0
                || (float) ($specification->width ?? 0) <= 0
                || (float) ($specification->height ?? 0) <= 0
            ) {
                $skippedMissingDimensions++;
                continue;
            }

            $ply = (int) ($specification->ply ?? 0);
            $profileCode = self::PROFILE_BY_PLY[$ply] ?? null;

            if (! $profileCode) {
                $skippedUnsupportedPly++;
                continue;
            }

            $profile = BoardProfile::query()
                ->where('code', $profileCode)
                ->where('is_active', true)
                ->first();

            if (! $profile) {
                throw new RuntimeException(
                    "Required board profile [{$profileCode}] was not found."
                );
            }

            // Preserve any BOM already created or curated by an operator.
            if (
                BOM::withTrashed()
                    ->where('product_id', $specification->product_id)
                    ->exists()
            ) {
                $skippedExistingBom++;
                continue;
            }

            $code = $this->codeFor($specification->source_key);

            if (BOM::withTrashed()->where('code', $code)->exists()) {
                $skippedExistingBom++;
                continue;
            }

            $result = $service->calculate([
                'box_style' => 'RSC',
                'length' => (float) $specification->length,
                'width' => (float) $specification->width,
                'height' => (float) $specification->height,
                'dimension_unit' => 'mm',
                'board_profile_id' => $profile->id,
                'ply' => $ply,
                'printing_option' => $this->printingOption($specification),
                'print_cost_afn' => 0,

                // Client defaults; historical workbook rates are never used
                // as current quotation/BOM prices.
                'quantity' => 1,
                'wastage_percentage' => 5,
                'work_percentage' => 40,
                'profit_margin_percentage' => 0,
            ]);

            DB::transaction(function () use (
                $service,
                $specification,
                $profile,
                $result,
                $code,
                $creatorId
            ): void {
                $bom = BOM::create([
                    'name' => $specification->product->name.' - Client Master BOM',
                    'code' => $code,
                    'product_id' => $specification->product_id,
                    'version' => $profile->version ?: '1.0',
                    'status' => 'draft',
                    'description' => sprintf(
                        'Seeded from %s row %s for customer %s. Formula/material structure only; review landed costs and activate before commercial use.',
                        CustomerCartonSizeSeeder::SOURCE_FILE,
                        $specification->source_row ?? '?',
                        $specification->customer?->name ?? $specification->source_customer_label ?? 'Unknown'
                    ),
                    'work_percentage' => 40,
                    'profit_margin_percentage' => 0,
                    'exchange_rate' => (float) $result['exchange_rate'],
                    'is_active' => false,
                    'created_by' => $creatorId,
                ]);

                foreach ($result['rows'] as $row) {
                    BOMItem::create(
                        $service->bomItemAttributes($bom->id, $row)
                    );
                }

                $bom->unsetRelation('items');
                $bom->load('items.material');
                $bom->calculateTotals();
                $bom->saveQuietly();
            });

            $created++;
        }

        $this->command?->info(sprintf(
            'Customer carton BOM seed complete: %d draft BOMs created; %d existing BOMs preserved; %d skipped for missing dimensions; %d skipped for missing/unsupported ply.',
            $created,
            $skippedExistingBom,
            $skippedMissingDimensions,
            $skippedUnsupportedPly
        ));
    }

    private function resolveCreatorId(): int
    {
        $existing = User::query()->orderBy('id')->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $system = User::firstOrCreate(
            ['username' => 'system-carton-seeder'],
            [
                'name' => 'System Carton Seeder',
                'account_type' => 'admin',
                'is_active' => false,
                'password' => Hash::make(Str::random(64)),
            ]
        );

        return (int) $system->id;
    }

    private function codeFor(?string $sourceKey): string
    {
        if (! $sourceKey) {
            throw new RuntimeException(
                'Imported carton specification is missing its immutable source key.'
            );
        }

        return 'BOM-CLIENT-'.strtoupper(
            substr(hash('sha256', $sourceKey), 0, 16)
        );
    }

    private function printingOption(
        FinishedGoodSpecification $specification
    ): string {
        if (strcasecmp((string) $specification->printing_type, 'Flexo') === 0) {
            return 'flexo';
        }

        $colors = (int) ($specification->color_count ?? 0);

        if ($colors > 1) {
            return 'multi_color';
        }

        if ($colors === 1) {
            return 'single_color';
        }

        return 'none';
    }
}
