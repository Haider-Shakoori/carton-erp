<?php

namespace Database\Seeders;

use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\BoardProfile;
use App\Models\FinishedGoodSpecification;
use App\Models\Product;
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

        $createdCalculated = 0;
        $createdReviewRequired = 0;
        $skippedExistingBom = 0;

        foreach ($specifications as $specification) {
            if (! $specification->product) {
                throw new RuntimeException(
                    "Imported carton specification #{$specification->id} has no finished-good product."
                );
            }

            $code = $this->codeFor($specification->source_key);

            // Preserve any BOM already curated by an operator. For BOMs seeded
            // by this importer, keep the generated BOM name synchronized with
            // the now customer-free finished-good product name.
            $existingBom = BOM::withTrashed()
                ->where('product_id', $specification->product_id)
                ->first();

            if ($existingBom) {
                if (
                    str_starts_with((string) $existingBom->code, 'BOM-CLIENT-')
                    && str_ends_with((string) $existingBom->name, ' - Client Master BOM')
                ) {
                    $existingBom->name = $specification->product->name.' - Client Master BOM';
                    $existingBom->saveQuietly();
                }

                $skippedExistingBom++;
                continue;
            }

            if (BOM::withTrashed()->where('code', $code)->exists()) {
                $skippedExistingBom++;
                continue;
            }

            $completeDimensions = (float) ($specification->length ?? 0) > 0
                && (float) ($specification->width ?? 0) > 0
                && (float) ($specification->height ?? 0) > 0;

            $ply = (int) ($specification->ply ?? 0);
            $profileCode = self::PROFILE_BY_PLY[$ply] ?? null;

            if ($completeDimensions && $profileCode) {
                $profile = BoardProfile::query()
                    ->where('code', $profileCode)
                    ->where('is_active', true)
                    ->first();

                if (! $profile) {
                    throw new RuntimeException(
                        "Required board profile [{$profileCode}] was not found."
                    );
                }

                $this->createCalculatedBom(
                    $service,
                    $specification,
                    $profile,
                    $ply,
                    $code,
                    $creatorId
                );
                $createdCalculated++;

                continue;
            }

            // The source workbook does not give enough verified structure for
            // these rows. Still create a BOM for every finished good, but do
            // not guess ply, dimensions or paper recipe. Instead create an
            // inactive draft review template containing only the exact ten raw
            // materials supplied by the client, all at zero quantity.
            $this->createReviewRequiredBom(
                $specification,
                $code,
                $creatorId,
                $this->reviewReason($specification)
            );
            $createdReviewRequired++;
        }

        $this->command?->info(sprintf(
            'Customer carton BOM seed complete: %d calculated draft BOMs + %d review-required draft BOMs = %d total; %d existing BOMs preserved.',
            $createdCalculated,
            $createdReviewRequired,
            $createdCalculated + $createdReviewRequired,
            $skippedExistingBom
        ));
    }

    private function createCalculatedBom(
        CartonSpecificationService $service,
        FinishedGoodSpecification $specification,
        BoardProfile $profile,
        int $ply,
        string $code,
        int $creatorId
    ): void {
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
                    'Seeded from %s row %s. Formula/material structure uses only client-approved raw materials; review landed costs and activate before commercial use.',
                    CustomerCartonSizeSeeder::SOURCE_FILE,
                    $specification->source_row ?? '?'
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
    }

    private function createReviewRequiredBom(
        FinishedGoodSpecification $specification,
        string $code,
        int $creatorId,
        string $reason
    ): void {
        $materials = Product::query()
            ->whereIn(
                'name',
                collect(ClientCartonRawMaterialSeeder::MATERIALS)->pluck('name')->all()
            )
            ->get()
            ->keyBy('name');

        $missing = collect(ClientCartonRawMaterialSeeder::MATERIALS)
            ->pluck('name')
            ->reject(fn (string $name) => $materials->has($name))
            ->values();

        if ($missing->isNotEmpty()) {
            throw new RuntimeException(
                'Review-required BOM cannot be created because client raw materials are missing: '
                .$missing->implode(', ')
            );
        }

        DB::transaction(function () use (
            $specification,
            $code,
            $creatorId,
            $reason,
            $materials
        ): void {
            $bom = BOM::create([
                'name' => $specification->product->name.' - Client Master BOM',
                'code' => $code,
                'product_id' => $specification->product_id,
                'version' => '1.0-review',
                'status' => 'draft',
                'description' => sprintf(
                    '[REVIEW REQUIRED] Seeded from %s row %s. %s No material quantity or board formula has been guessed. Fill the actual BOM before activation.',
                    CustomerCartonSizeSeeder::SOURCE_FILE,
                    $specification->source_row ?? '?',
                    $reason
                ),
                'work_percentage' => 40,
                'profit_margin_percentage' => 0,
                'exchange_rate' => 0,
                'is_active' => false,
                'created_by' => $creatorId,
            ]);

            foreach (ClientCartonRawMaterialSeeder::MATERIALS as $index => $definition) {
                $material = $materials->get($definition['name']);

                BOMItem::create([
                    'bom_id' => $bom->id,
                    'material_id' => $material->id,
                    'quantity' => 0,
                    'unit' => 'kg',
                    'wastage_percentage' => 0,
                    'cost_per_unit_usd' => 0,
                    'cost_per_unit_afn' => 0,
                    'total_cost_usd' => 0,
                    'total_cost_afn' => 0,
                    'notes' => 'Review-required placeholder from the client-approved raw-material master. Set actual usage before activation.',
                    'sort_order' => $index,
                    'is_formula_based' => false,
                    'formula_type' => 'fixed',
                    'component_type' => $definition['category'] === 'mixing' ? 'adhesive' : 'paper',
                    'apply_work_percentage' => false,
                    'work_percentage' => 0,
                    'print' => 0,
                ]);
            }

            $bom->unsetRelation('items');
            $bom->load('items.material');
            $bom->calculateTotals();
            $bom->saveQuietly();
        });
    }

    private function reviewReason(
        FinishedGoodSpecification $specification
    ): string {
        $reasons = [];

        if (
            (float) ($specification->length ?? 0) <= 0
            || (float) ($specification->width ?? 0) <= 0
            || (float) ($specification->height ?? 0) <= 0
        ) {
            $reasons[] = 'The source row does not contain complete positive dimensions.';
        }

        $ply = (int) ($specification->ply ?? 0);

        if ($ply <= 0) {
            $reasons[] = 'The source row does not specify a verified ply.';
        } elseif (! isset(self::PROFILE_BY_PLY[$ply])) {
            $reasons[] = "The source row specifies {$ply}-ply, but no client-verified {$ply}-ply board profile exists.";
        }

        return implode(' ', $reasons)
            ?: 'The source row requires manual BOM review.';
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
