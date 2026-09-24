<?php

namespace App\Services;

use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\BoardProfile;
use App\Models\BoardProfileLayer;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Canonical carton specification engine.
 *
 * One service resolves a simple user-facing carton specification
 * (box style + dimensions + ply/board profile + printing + quantity)
 * into the complete technical calculation:
 *
 *   - normalized dimensions and the RSC box-style blank
 *   - board-profile paper requirements (client formula, source of truth)
 *   - dimension-driven adhesive/mixing requirements
 *   - physical material cost (with wastage) and landed-rate basis
 *   - commercial paper basis, work/profit, print and selling price
 *   - stock shortages
 *   - persisted technical BOM rows, freed from live profiles once snapshotted
 *
 * Controllers, blades, JavaScript, sale profit reporting and production must
 * consume this path rather than re-implementing the formulas.
 */
class CartonSpecificationService
{
    public const COMPONENT_PAPER = 'paper';
    public const COMPONENT_ADHESIVE = 'adhesive';
    public const COMPONENT_PRINTING = 'printing';
    public const COMPONENT_AUXILIARY = 'auxiliary';

    public function __construct(
        private readonly AdhesiveMixCalculator $adhesive,
        private readonly BOMCostingService $costing,
        private readonly CartonLaminationService $lamination
    ) {
    }

    // ─── NORMALIZATION ───

    /**
     * Validate and normalize raw user input. Dimensions are stored in inches
     * rounded to the same 2dp precision as the bom_items columns so the
     * stored technical BOM and the snapshot always agree.
     */
    public function normalizeSpec(array $input): array
    {
        $boxStyle = strtoupper(trim((string) ($input['box_style'] ?? config('carton.default_box_style', 'RSC'))));
        $boxStyles = (array) config('carton.box_styles', []);

        if ($boxStyle === '' || ! array_key_exists($boxStyle, $boxStyles)) {
            throw new RuntimeException("Unsupported box style [{$boxStyle}].");
        }

        $unit = strtolower(trim((string) ($input['dimension_unit'] ?? $input['unit'] ?? 'inch')));
        $units = (array) config('carton.length_units', ['inch' => 1.0]);

        if (! array_key_exists($unit, $units)) {
            throw new RuntimeException("Unsupported dimension unit [{$unit}].");
        }

        $factor = (float) $units[$unit];
        $length = $this->positiveDimension($input['length'] ?? null, 'Length');
        $width = $this->positiveDimension($input['width'] ?? null, 'Width');
        $height = $this->positiveDimension($input['height'] ?? null, 'Height');

        $lengthInch = round($length * $factor, 2);
        $widthInch = round($width * $factor, 2);
        $heightInch = round($height * $factor, 2);

        if ($lengthInch <= 0 || $widthInch <= 0 || $heightInch <= 0) {
            throw new RuntimeException('Carton length, width and height must be greater than zero.');
        }

        $profile = $this->resolveProfile(
            isset($input['board_profile_id']) ? (int) $input['board_profile_id'] : null
        );

        $fluteType = $input['flute_type'] ?? $profile->flute_type;
        $fluteType = $fluteType !== null && $fluteType !== '' ? strtoupper(trim((string) $fluteType)) : null;
        $flutes = (array) config('carton.flutes', []);

        if ($fluteType !== null && ! array_key_exists($fluteType, $flutes)) {
            throw new RuntimeException("Unsupported flute type [{$fluteType}].");
        }

        $printingOption = strtolower(trim((string) ($input['printing_option'] ?? 'none')));
        $printingOptions = (array) config('carton.printing', []);
        if ($printingOption === '' || ! array_key_exists($printingOption, $printingOptions)) {
            throw new RuntimeException("Unsupported printing option [{$printingOption}].");
        }

        $printingDefaults = (array) ($printingOptions[$printingOption] ?? []);
        $printCostAfn = array_key_exists('print_cost_afn', $input) && $input['print_cost_afn'] !== null && $input['print_cost_afn'] !== ''
            ? max((float) $input['print_cost_afn'], 0.0)
            : max((float) ($printingDefaults['print_cost_afn'] ?? 0), 0.0);

        $wastage = array_key_exists('wastage_percentage', $input)
            && $input['wastage_percentage'] !== null
            && $input['wastage_percentage'] !== ''
                ? max((float) $input['wastage_percentage'], 0.0)
                : max((float) ($profile->wastage_percentage ?? config('carton.default_wastage_percentage', 5)), 0.0);

        $quantity = array_key_exists('quantity', $input) && $input['quantity'] !== null && $input['quantity'] !== ''
            ? (float) $input['quantity']
            : 1.0;

        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $workPercentage = array_key_exists('work_percentage', $input)
            && $input['work_percentage'] !== null
            && $input['work_percentage'] !== ''
                ? max((float) $input['work_percentage'], 0.0)
                : 40.0;

        $profitMargin = array_key_exists('profit_margin_percentage', $input)
            && $input['profit_margin_percentage'] !== null
            && $input['profit_margin_percentage'] !== ''
                ? max((float) $input['profit_margin_percentage'], 0.0)
                : 0.0;

        $reel = $this->reelDimensions($lengthInch, $widthInch, $heightInch, $boxStyle);
        $blankAreaM2 = $reel['reel_length'] * $reel['reel_height'] * (float) config('carton.sq_inch_to_m2', 0.00064516);
        $laminationEnabled = filter_var(
            $input['lamination_enabled'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return [
            'box_style' => $boxStyle,
            'box_style_label' => $boxStyles[$boxStyle]['label'] ?? $boxStyle,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'dimension_unit' => $unit,
            'length_inch' => $lengthInch,
            'width_inch' => $widthInch,
            'height_inch' => $heightInch,
            'reel_length_inch' => round($reel['reel_length'], 2),
            'reel_height_inch' => round($reel['reel_height'], 2),
            'board_area_m2' => $blankAreaM2,
            'lamination_enabled' => $laminationEnabled,
            'ply' => array_key_exists('ply', $input) && $input['ply'] !== null && $input['ply'] !== ''
                ? (int) $input['ply']
                : (int) $profile->ply,
            'flute_type' => $fluteType,
            'printing_option' => $printingOption,
            'printing_label' => $printingDefaults['label'] ?? ucfirst($printingOption),
            'print_cost_afn' => $printCostAfn,
            'quantity' => $quantity,
            'wastage_percentage' => $wastage,
            'work_percentage' => $workPercentage,
            'profit_margin_percentage' => $profitMargin,
            'formula_constant' => (float) config('carton.formula_constant', 1550000),
            'sq_inch_to_m2' => (float) config('carton.sq_inch_to_m2', 0.00064516),
            'board_profile' => $profile->snapshot(),
        ];
    }

    public function resolveProfile(?int $boardProfileId): BoardProfile
    {
        if (! $boardProfileId) {
            throw new RuntimeException('A board profile is required to build a carton specification.');
        }

        $profile = BoardProfile::with('layers.material')->find($boardProfileId);

        if (! $profile) {
            throw new RuntimeException("Board profile #{$boardProfileId} was not found.");
        }

        if (! $profile->isUsable()) {
            throw new RuntimeException("Board profile [{$profile->name}] is inactive and cannot be used.");
        }

        if ($profile->layers->isEmpty()) {
            throw new RuntimeException("Board profile [{$profile->name}] has no paper layers configured.");
        }

        return $profile;
    }

    // ─── BOX STYLE ───

    /**
     * Blank dimensions for a box style. RSC keeps the verified client formula.
     *
     * @return array{reel_length: float, reel_height: float}
     */
    public function reelDimensions(float $lengthInch, float $widthInch, float $heightInch, string $boxStyle = 'RSC'): array
    {
        $style = strtoupper(trim($boxStyle));
        $boxStyles = (array) config('carton.box_styles', []);

        if ($style === '' || ! array_key_exists($style, $boxStyles)) {
            throw new RuntimeException("Unsupported box style [{$boxStyle}].");
        }

        return match ($style) {
            'RSC' => [
                'reel_length' => (($lengthInch + $widthInch) * 2) + 4,
                'reel_height' => $widthInch + $heightInch + 1,
            ],
            default => throw new RuntimeException("Box style [{$style}] has no blank formula strategy."),
        };
    }

    // ─── TECHNICAL ROWS ───

    /**
     * Technical component rows for the resolved specification. Costs are
     * filled by calculate(); quantities follow the client formulas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildRows(array $spec): array
    {
        $rows = [];
        $sort = 0;

        foreach ((array) ($spec['board_profile']['layers'] ?? []) as $layer) {
            if (($layer['component_type'] ?? self::COMPONENT_PAPER) !== self::COMPONENT_PAPER) {
                continue;
            }

            $gsm = (float) ($layer['gsm'] ?? 0);
            $multiplier = (float) ($layer['multiplication_layer'] ?? 1);

            if ($gsm <= 0 || $multiplier <= 0) {
                continue;
            }

            $kgPerUnit = $this->paperKg(
                (float) $spec['reel_length_inch'],
                (float) $spec['reel_height_inch'],
                $gsm,
                $multiplier,
                (float) $spec['formula_constant']
            );

            $rows[] = [
                'sort_order' => $sort++,
                'component_type' => self::COMPONENT_PAPER,
                'role' => $layer['role'] ?? 'generic_client_formula',
                'material_id' => $layer['material_id'] !== null ? (int) $layer['material_id'] : null,
                'material_name' => $layer['material_name'] ?? null,
                'formula_type' => 'carton_3d',
                'is_formula_based' => true,
                'length_inch' => (float) $spec['length_inch'],
                'width_inch' => (float) $spec['width_inch'],
                'height_inch' => (float) $spec['height_inch'],
                'reel_length_inch' => (float) $spec['reel_length_inch'],
                'reel_height_inch' => (float) $spec['reel_height_inch'],
                'paper_gsm' => $gsm,
                'multiplication_layer' => $multiplier,
                'formula_constant' => (float) $spec['formula_constant'],
                'flute_type' => $layer['flute_type'] ?? $spec['flute_type'],
                'take_up_factor' => $layer['take_up_factor'] ?? null,
                'wastage_percentage' => (float) $spec['wastage_percentage'],
                'work_percentage' => (float) $spec['work_percentage'],
                'apply_work_percentage' => (bool) ($layer['commercial_work_enabled'] ?? true),
                'print' => 0.0,
                'kg_per_unit' => $kgPerUnit,
                'kg_with_wastage' => $kgPerUnit * (1 + ((float) $spec['wastage_percentage'] / 100)),
                'unit' => 'kg',
            ];
        }

        foreach ($this->adhesiveRequirements($spec) as $adhesiveRow) {
            $adhesiveRow['sort_order'] = $sort++;
            $rows[] = $adhesiveRow;
        }

        if ((bool) ($spec['lamination_enabled'] ?? false)) {
            $rows[] = $this->lamination->technicalRow(
                (float) $spec['board_area_m2'],
                $sort++
            );
        }

        return $rows;
    }

    /**
     * Client paper formula (source of truth):
     *   PaperKg = ReelLength x ReelHeight x GSM x MultiplicationLayer / 1,550,000
     */
    public function paperKg(
        float $reelLengthInch,
        float $reelHeightInch,
        float $gsm,
        float $multiplicationLayer,
        ?float $formulaConstant = null
    ): float {
        $constant = max((float) ($formulaConstant ?? config('carton.formula_constant', 1550000)), 0.000001);

        if ($reelLengthInch <= 0 || $reelHeightInch <= 0 || $gsm <= 0 || $multiplicationLayer <= 0) {
            return 0.0;
        }

        return $reelLengthInch * $reelHeightInch * $gsm * $multiplicationLayer / $constant;
    }

    /**
     * Dimension-driven adhesive ingredient rows for all four mixing materials.
     *
     * @return array<int, array<string, mixed>>
     */
    public function adhesiveRequirements(array $spec): array
    {
        $rows = [];
        $dims = [
            (float) $spec['length_inch'],
            (float) $spec['width_inch'],
            (float) $spec['height_inch'],
        ];

        foreach (array_keys((array) config('carton.adhesive.recipe', [])) as $recipeKey) {
            $materialId = $this->adhesiveMaterialId((string) $recipeKey);

            if (! $materialId) {
                continue;
            }

            $breakdown = $this->adhesive->breakdown(
                $dims[0],
                $dims[1],
                $dims[2],
                (string) $recipeKey
            );

            $kgPerUnit = (float) $breakdown['ingredient_kg'];

            if ($kgPerUnit <= 0) {
                continue;
            }

            $rows[] = [
                'component_type' => self::COMPONENT_ADHESIVE,
                'role' => 'adhesive',
                'material_id' => $materialId,
                'material_name' => Product::whereKey($materialId)->value('name'),
                'formula_type' => 'adhesive_mix',
                'is_formula_based' => true,
                'length_inch' => $dims[0],
                'width_inch' => $dims[1],
                'height_inch' => $dims[2],
                'reel_length_inch' => $breakdown['reel_length_inch'],
                'reel_height_inch' => $breakdown['reel_height_inch'],
                'paper_gsm' => null,
                'multiplication_layer' => 0,
                'formula_constant' => null,
                'wastage_percentage' => 0.0,
                'work_percentage' => 0.0,
                'apply_work_percentage' => false,
                'print' => 0.0,
                'kg_per_unit' => $kgPerUnit,
                'kg_with_wastage' => $kgPerUnit,
                'unit' => 'kg',
                'formula_data' => [
                    'recipe_key' => $recipeKey,
                    'recipe_percentage' => $breakdown['recipe_fraction'],
                    'glue_lines' => $breakdown['parameters']['glue_lines'],
                    'dry_glue_gsm_per_line' => $breakdown['parameters']['dry_glue_gsm_per_line'],
                    'glue_wastage_percentage' => $breakdown['parameters']['glue_wastage_percentage'],
                    'adhesive_solids_percentage' => $breakdown['parameters']['adhesive_solids_percentage'],
                    'sq_inch_to_m2' => $breakdown['parameters']['sq_inch_to_m2'],
                ],
                'adhesive_breakdown' => $breakdown,
            ];
        }

        return $rows;
    }

    /**
     * Resolve a mixing raw material product for a recipe key.
     */
    public function adhesiveMaterialId(string $recipeKey): ?int
    {
        $preferred = (array) config('carton.adhesive.recipe_materials', []);
        $name = $preferred[$recipeKey] ?? null;

        if ($name) {
            $id = Product::where('name', $name)->value('id');

            if ($id) {
                return (int) $id;
            }
        }

        $map = (array) config('carton.adhesive.materials', []);

        foreach (Product::query()->select(['id', 'name'])->get() as $product) {
            if (($map[strtolower(trim((string) $product->name))] ?? null) === $recipeKey) {
                return (int) $product->id;
            }
        }

        foreach (Product::query()->select(['id', 'name'])->get() as $product) {
            if ($this->adhesive->resolveRecipeKey($product->name) === $recipeKey) {
                return (int) $product->id;
            }
        }

        return null;
    }

    // ─── FULL CALCULATION ───

    /**
     * Resolve a specification into physical requirements, landed material
     * cost, commercial quotation rates and stock shortages.
     */
    public function calculate(array $input, ?float $exchangeRate = null): array
    {
        $spec = $this->normalizeSpec($input);
        $rate = max((float) ($exchangeRate ?? $input['exchange_rate'] ?? $this->defaultExchangeRate()), 0.000001);
        $rows = $this->buildRows($spec);

        $paperBaseAfn = 0.0;
        $paperBasisKg = 0.0;
        $paperPhysicalKg = 0.0;
        $adhesiveKg = 0.0;
        $laminationKg = 0.0;
        $laminationBaseAfn = 0.0;
        $workProfitAfn = 0.0;
        $physicalMaterialAfn = 0.0;
        $physicalMaterialUsd = 0.0;
        $baseMaterialAfn = 0.0;
        $baseMaterialUsd = 0.0;

        foreach ($rows as &$row) {
            $cost = $this->costing->latestInventoryCost((int) $row['material_id'], $rate);
            $costUsd = $cost['found'] ? (float) $cost['cost_usd'] : 0.0;
            $costAfn = $costUsd * $rate;

            $row['cost_per_unit_usd'] = $costUsd;
            $row['cost_per_unit_afn'] = $costAfn;
            $row['landed_cost_found'] = (bool) $cost['found'];
            $row['landed_basis_unit'] = $cost['basis_unit'];
            $row['purchase_item_id'] = $cost['purchase_item_id'];
            $row['purchase_date'] = $cost['purchase_date'];
            $row['batch_no'] = $cost['batch_no'];

            $kgBase = (float) $row['kg_per_unit'];
            $kgWithWastage = (float) $row['kg_with_wastage'];
            $lineBaseUsd = $kgBase * $costUsd;
            $linePhysicalUsd = $kgWithWastage * $costUsd;
            $linePhysicalAfn = $linePhysicalUsd * $rate;

            $row['physical_cost_usd_per_unit'] = $linePhysicalUsd;
            $row['physical_cost_afn_per_unit'] = $linePhysicalAfn;

            $baseMaterialUsd += $lineBaseUsd;
            $baseMaterialAfn += $lineBaseUsd * $rate;
            $physicalMaterialUsd += $linePhysicalUsd;
            $physicalMaterialAfn += $linePhysicalAfn;

            if ($row['component_type'] === self::COMPONENT_PAPER) {
                $lineBaseAfn = $kgBase * $costAfn;
                $paperBasisKg += $kgBase;
                $paperPhysicalKg += $kgWithWastage;
                $paperBaseAfn += $lineBaseAfn;

                if ($row['apply_work_percentage']) {
                    $workProfitAfn += $lineBaseAfn * ((float) $row['work_percentage'] / 100);
                }

                $row['row_net_rate_afn'] = $lineBaseAfn
                    + ($row['apply_work_percentage'] ? $lineBaseAfn * ((float) $row['work_percentage'] / 100) : 0.0);
            } elseif (($row['role'] ?? null) === 'lamination') {
                // Lamination is a real physical material and a direct quotation
                // component, but it does not receive the paper 40% work/profit.
                $lineBaseAfn = $kgBase * $costAfn;
                $laminationKg += $kgWithWastage;
                $laminationBaseAfn += $lineBaseAfn;
                $row['row_net_rate_afn'] = $lineBaseAfn;
            } else {
                $adhesiveKg += $kgWithWastage;
                $row['row_net_rate_afn'] = 0.0;
            }
        }
        unset($row);

        $printAfn = (float) $spec['print_cost_afn'];
        $commercialNetAfn = $paperBaseAfn + $workProfitAfn + $printAfn + $laminationBaseAfn;
        $sellingAfn = $commercialNetAfn * (1 + ((float) $spec['profit_margin_percentage'] / 100));

        $quantity = (float) $spec['quantity'];

        return [
            'spec' => $spec,
            'profile' => $spec['board_profile'],
            'exchange_rate' => $rate,
            'rows' => $rows,
            'paper' => [
                'basis_kg_per_unit' => $paperBasisKg,
                'physical_kg_per_unit' => $paperPhysicalKg,
                'physical_kg_total' => $paperPhysicalKg * $quantity,
                'basis_afn_per_unit' => $paperBaseAfn,
                'basis_afn_total' => $paperBaseAfn * $quantity,
                'wastage_kg_per_unit' => max($paperPhysicalKg - $paperBasisKg, 0.0),
            ],
            'adhesive' => [
                'kg_per_unit' => $adhesiveKg,
                'kg_total' => $adhesiveKg * $quantity,
                'parameters' => $this->adhesive->parameters(),
                'recipe' => (array) config('carton.adhesive.recipe', []),
            ],
            'lamination' => [
                'enabled' => (bool) ($spec['lamination_enabled'] ?? false),
                'kg_per_unit' => $quantity > 0 ? $laminationKg : 0.0,
                'kg_total' => $laminationKg * $quantity,
                'base_cost_afn_per_unit' => $laminationBaseAfn,
                'config' => (array) config('carton.lamination', []),
            ],
            'physical' => [
                'material_cost_usd_per_unit' => $physicalMaterialUsd,
                'material_cost_afn_per_unit' => $physicalMaterialAfn,
                'material_cost_usd_total' => $physicalMaterialUsd * $quantity,
                'material_cost_afn_total' => $physicalMaterialAfn * $quantity,
                'base_material_cost_afn_per_unit' => $baseMaterialAfn,
                'base_material_cost_usd_per_unit' => $baseMaterialUsd,
                'wastage_cost_afn_per_unit' => max($physicalMaterialAfn - $baseMaterialAfn, 0.0),
                'has_missing_landed_cost' => collect($rows)->contains(fn ($r) => ! $r['landed_cost_found']),
            ],
            'commercial' => [
                'paper_basis_afn_per_unit' => $paperBaseAfn,
                'work_profit_afn_per_unit' => $workProfitAfn,
                'print_cost_afn_per_unit' => $printAfn,
                'lamination_cost_afn_per_unit' => $laminationBaseAfn,
                'net_rate_afn_per_unit' => $commercialNetAfn,
                'profit_margin_percentage' => (float) $spec['profit_margin_percentage'],
                'additional_markup_afn_per_unit' => $sellingAfn - $commercialNetAfn,
                'selling_price_afn_per_unit' => $sellingAfn,
                'selling_price_usd_per_unit' => $sellingAfn / $rate,
                'order_total_afn' => $sellingAfn * $quantity,
                'order_total_usd' => ($sellingAfn * $quantity) / $rate,
                'work_percentage' => (float) $spec['work_percentage'],
            ],
            'quantity' => $quantity,
            'shortages' => $this->resolveShortages($rows, $quantity),
            'resolved_config' => $this->resolvedConfigSnapshot(),
        ];
    }

    /**
     * Aggregate physical requirements per raw material and compare them to
     * current stock. Used by the quotation UI as a shortage indicator and by
     * production planning.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{has_shortage: bool, materials: array<int, array<string, mixed>>}
     */
    public function resolveShortages(array $rows, float $quantity): array
    {
        $requirements = $this->aggregateRequirements($rows, $quantity);
        $shortages = [];
        $hasShortage = false;

        foreach ($requirements as $required) {
            $materialId = (int) $required['material_id'];
            $available = $this->availableQuantity($materialId);
            $shortage = max((float) $required['required_quantity'] - $available, 0.0);

            if ($shortage > 0.000001) {
                $hasShortage = true;
            }

            $shortages[] = [
                'material_id' => $materialId,
                'material_name' => $required['material_name'],
                'component_type' => $required['component_type'],
                'required_quantity' => round((float) $required['required_quantity'], 4),
                'available_quantity' => round($available, 4),
                'shortage_quantity' => round($shortage, 4),
                'unit' => $required['unit'],
                'is_available' => $shortage <= 0.000001,
            ];
        }

        return [
            'has_shortage' => $hasShortage,
            'materials' => $shortages,
        ];
    }

    /**
     * Aggregate technical rows into one requirement line per material.
     * Prevents duplicate deductions when several technical rows resolve to the
     * same raw material.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function aggregateRequirements(array $rows, float $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }

        return collect($rows)
            ->filter(fn (array $row) => (int) ($row['material_id'] ?? 0) > 0)
            ->groupBy('material_id')
            ->map(function ($group) use ($quantity) {
                $first = $group->first();

                return [
                    'material_id' => (int) $first['material_id'],
                    'material_name' => $first['material_name'] ?? ('Material #' . $first['material_id']),
                    'component_type' => $first['component_type'] ?? self::COMPONENT_PAPER,
                    'base_quantity' => (float) $group->sum(fn (array $row) => (float) ($row['kg_per_unit'] ?? 0)) * $quantity,
                    'required_quantity' => (float) $group->sum(fn (array $row) => (float) ($row['kg_with_wastage'] ?? 0)) * $quantity,
                    'unit' => 'kg',
                    'cost_per_unit_usd' => (float) ($first['cost_per_unit_usd'] ?? 0),
                    'cost_per_unit_afn' => (float) ($first['cost_per_unit_afn'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    // ─── PERSISTENCE ───

    /**
     * Persist the generated technical rows as an immutable, versioned BOM so
     * production and advanced users keep a real BOM document.
     */
    public function persistTechnicalBom(array $result, int $productId, ?int $userId = null): BOM
    {
        $product = Product::find($productId);

        if (! $product) {
            throw new RuntimeException("Product #{$productId} was not found.");
        }

        $userId = $userId ?? Auth::id();

        if (! $userId) {
            throw new RuntimeException('A user is required to persist a technical BOM.');
        }

        $spec = $result['spec'];
        $profile = $result['profile'];

        return DB::transaction(function () use ($result, $spec, $profile, $product, $userId) {
            $bom = BOM::create([
                'name' => sprintf(
                    '%s - %s %sx%sx%s %s',
                    $product->name,
                    $profile['name'] ?? 'Board Profile',
                    $this->trimNumber((float) $spec['length']),
                    $this->trimNumber((float) $spec['width']),
                    $this->trimNumber((float) $spec['height']),
                    $spec['dimension_unit']
                ),
                'code' => 'BOM-SPEC-' . strtoupper(Str::random(10)),
                'product_id' => $product->id,
                'version' => $profile['version'] ?? '1.0',
                'status' => 'active',
                'description' => sprintf(
                    'Auto-generated technical BOM - %s, %s ply, %s. Board profile %s v%s.',
                    $spec['box_style'],
                    $spec['ply'],
                    $spec['printing_label'] ?? $spec['printing_option'],
                    $profile['name'] ?? '',
                    $profile['version'] ?? '1.0'
                ),
                'work_percentage' => $spec['work_percentage'],
                'profit_margin_percentage' => $spec['profit_margin_percentage'],
                'exchange_rate' => $result['exchange_rate'],
                'is_active' => true,
                'created_by' => $userId,
            ]);

            foreach ($result['rows'] as $row) {
                BOMItem::create($this->bomItemAttributes($bom->id, $row));
            }

            $bom->unsetRelation('items');
            $bom->load('items.material');
            $bom->calculateTotals();
            $bom->saveQuietly();

            return $bom->fresh(['items.material']);
        });
    }

    /**
     * BOM item attributes for one technical row.
     */
    public function bomItemAttributes(int $bomId, array $row): array
    {
        $kgWithWastage = (float) ($row['kg_with_wastage'] ?? 0);
        $costUsd = (float) ($row['cost_per_unit_usd'] ?? 0);
        $costAfn = (float) ($row['cost_per_unit_afn'] ?? 0);

        return [
            'bom_id' => $bomId,
            'material_id' => (int) $row['material_id'],
            // Persist the actual base material usage per finished carton.
            // Formula rows still calculate dynamically from their technical
            // parameters; this quantity is the human-readable BOM usage value.
            'quantity' => (float) ($row['kg_per_unit'] ?? 0),
            'unit' => 'kg',
            'wastage_percentage' => (float) ($row['wastage_percentage'] ?? 0),
            'cost_per_unit_usd' => $costUsd,
            'cost_per_unit_afn' => $costAfn,
            'total_cost_usd' => $kgWithWastage * $costUsd,
            'total_cost_afn' => $kgWithWastage * $costAfn,
            'notes' => $row['role'] ?? null,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_formula_based' => (bool) ($row['is_formula_based'] ?? true),
            'formula_type' => $row['formula_type'] ?? 'carton_3d',
            'formula_data' => $row['formula_data'] ?? null,
            'component_type' => $row['component_type'] ?? self::COMPONENT_PAPER,
            'apply_work_percentage' => (bool) ($row['apply_work_percentage'] ?? true),
            'length_inch' => $row['length_inch'] ?? null,
            'width_inch' => $row['width_inch'] ?? null,
            'height_inch' => $row['height_inch'] ?? null,
            'reel_length_inch' => $row['reel_length_inch'] ?? null,
            'reel_height_inch' => $row['reel_height_inch'] ?? null,
            'paper_gsm' => $row['paper_gsm'] ?? null,
            'multiplication_layer' => (int) round((float) ($row['multiplication_layer'] ?? 1)),
            'formula_constant' => $row['formula_constant'] ? (int) round((float) $row['formula_constant']) : null,
            'per_gram_rate' => $costAfn,
            'work_percentage' => (float) ($row['work_percentage'] ?? 0),
            'print' => (float) ($row['print'] ?? 0),
            'rate_per_unit' => $row['rate_per_unit'] ?? null,
            'rate_base_units' => $row['rate_base_units'] ?? null,
            'stock_consumption_override' => $row['stock_consumption_override'] ?? null,
            'stock_consumption_unit' => isset($row['stock_consumption_override']) ? 'kg' : null,
        ];
    }

    /**
     * Freeze the complete commercial and technical assumptions into a sale
     * item snapshot. Historical orders must stay reproducible after profile,
     * config or landed-rate changes.
     */
    public function snapshot(array $result, array $accepted = [], string $status = 'quotation'): array
    {
        $spec = $result['spec'];

        $rows = collect($result['rows'])->map(function (array $row) {
            // Keep the manual-BOM compatible row shape so sale reporting and
            // production can read a frozen snapshot without any live profile.
            $snapshotRow = [
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'component_type' => $row['component_type'],
                'formula_type' => $row['formula_type'],
                'role' => $row['role'],
                'length' => $row['length_inch'],
                'width' => $row['width_inch'],
                'height' => $row['height_inch'],
                'reel_length_inch' => $row['reel_length_inch'],
                'reel_height_inch' => $row['reel_height_inch'],
                'paper_gsm' => $row['paper_gsm'],
                'multiplication_layer' => $row['multiplication_layer'],
                'formula_constant' => $row['formula_constant'],
                'wastage' => $row['wastage_percentage'],
                'work_percentage' => $row['work_percentage'],
                'apply_work_percentage' => (bool) $row['apply_work_percentage'],
                'print_cost' => (float) $row['print'],
                'kg_per_unit' => round((float) $row['kg_per_unit'], 8),
                'kg_with_wastage' => round((float) $row['kg_with_wastage'], 8),
                'per_gram_rate' => round((float) $row['cost_per_unit_afn'], 6),
                'landed_cost_usd_per_kg' => round((float) $row['cost_per_unit_usd'], 8),
                'landed_cost_afn_per_kg' => round((float) $row['cost_per_unit_afn'], 6),
                'cost_per_unit_usd' => round((float) $row['cost_per_unit_usd'], 8),
                'landed_basis_unit' => $row['landed_basis_unit'],
                'purchase_item_id' => $row['purchase_item_id'],
            ];

            if (($row['formula_type'] ?? null) === 'adhesive_mix') {
                $snapshotRow = array_merge($snapshotRow, [
                    'recipe_key' => $row['formula_data']['recipe_key'] ?? null,
                    'recipe_percentage' => $row['formula_data']['recipe_percentage'] ?? null,
                    'glue_lines' => $row['formula_data']['glue_lines'] ?? null,
                    'dry_glue_gsm_per_line' => $row['formula_data']['dry_glue_gsm_per_line'] ?? null,
                    'glue_wastage_percentage' => $row['formula_data']['glue_wastage_percentage'] ?? null,
                    'adhesive_solids_percentage' => $row['formula_data']['adhesive_solids_percentage'] ?? null,
                    'sq_inch_to_m2' => $row['formula_data']['sq_inch_to_m2'] ?? null,
                ]);
            }

            return $snapshotRow;
        })->values()->all();

        $snapshot = [
            'version' => 1,
            'status' => $status,
            'created_at' => now()->toIso8601String(),
            'frozen_at' => $status === 'accepted' ? now()->toIso8601String() : null,
            'box_style' => $spec['box_style'],
            'dimensions' => [
                'length' => $spec['length'],
                'width' => $spec['width'],
                'height' => $spec['height'],
                'unit' => $spec['dimension_unit'],
                'length_inch' => $spec['length_inch'],
                'width_inch' => $spec['width_inch'],
                'height_inch' => $spec['height_inch'],
                'reel_length_inch' => $spec['reel_length_inch'],
                'reel_height_inch' => $spec['reel_height_inch'],
                'board_area_m2' => $spec['board_area_m2'],
            ],
            'ply' => $spec['ply'],
            'flute_type' => $spec['flute_type'],
            'printing' => [
                'option' => $spec['printing_option'],
                'label' => $spec['printing_label'],
                'print_cost_afn' => $spec['print_cost_afn'],
            ],
            'quantity' => $spec['quantity'],
            'wastage_percentage' => $spec['wastage_percentage'],
            'board_profile' => $spec['board_profile'],
            'adhesive' => [
                'parameters' => $result['adhesive']['parameters'],
                'recipe' => $result['adhesive']['recipe'],
                'kg_per_unit' => round((float) $result['adhesive']['kg_per_unit'], 8),
            ],
            'lamination' => [
                'enabled' => (bool) ($result['lamination']['enabled'] ?? false),
                'kg_per_unit' => round((float) ($result['lamination']['kg_per_unit'] ?? 0), 8),
                'config' => (array) ($result['lamination']['config'] ?? []),
            ],
            'exchange_rate' => $result['exchange_rate'],
            'rows' => $rows,
            'physical' => [
                'paper_kg_per_unit' => round((float) $result['paper']['physical_kg_per_unit'], 8),
                'adhesive_kg_per_unit' => round((float) $result['adhesive']['kg_per_unit'], 8),
                'lamination_kg_per_unit' => round((float) ($result['lamination']['kg_per_unit'] ?? 0), 8),
                'material_cost_afn_per_unit' => round((float) $result['physical']['material_cost_afn_per_unit'], 4),
                'material_cost_usd_per_unit' => round((float) $result['physical']['material_cost_usd_per_unit'], 6),
            ],
            'commercial' => [
                'paper_basis_afn_per_unit' => round((float) $result['commercial']['paper_basis_afn_per_unit'], 4),
                'work_profit_afn_per_unit' => round((float) $result['commercial']['work_profit_afn_per_unit'], 4),
                'print_cost_afn_per_unit' => round((float) $result['commercial']['print_cost_afn_per_unit'], 4),
                'lamination_cost_afn_per_unit' => round((float) ($result['commercial']['lamination_cost_afn_per_unit'] ?? 0), 4),
                'net_rate_afn_per_unit' => round((float) $result['commercial']['net_rate_afn_per_unit'], 4),
                'work_percentage' => (float) $result['commercial']['work_percentage'],
                'profit_margin_percentage' => (float) $result['commercial']['profit_margin_percentage'],
                'selling_price_afn_per_unit' => round((float) $result['commercial']['selling_price_afn_per_unit'], 4),
                'order_total_afn' => round((float) $result['commercial']['order_total_afn'], 2),
            ],
            'resolved_config' => $result['resolved_config'],
            'accepted' => $accepted,
        ];

        return $snapshot;
    }

    /**
     * Freeze an existing quotation snapshot as the accepted specification.
     *
     * Called at sale confirmation. The accepted unit price and total come from
     * the sale item itself: the invoice must never reprice an accepted order
     * from today's material rates.
     */
    public function freezeAccepted(SaleItem $saleItem, ?array $acceptedOverride = null): ?array
    {
        $snapshot = $saleItem->carton_spec_snapshot;

        if (! is_array($snapshot) || empty($snapshot['rows'])) {
            return null;
        }

        if (($snapshot['status'] ?? 'quotation') === 'accepted') {
            return $snapshot;
        }

        $snapshot['status'] = 'accepted';
        $snapshot['frozen_at'] = now()->toIso8601String();
        $snapshot['accepted'] = array_merge(
            (array) ($snapshot['accepted'] ?? []),
            $acceptedOverride ?? [
                'unit_price' => (float) $saleItem->unit_price,
                'total' => (float) $saleItem->total,
                'currency' => strtoupper((string) ($saleItem->sale?->currency?->code ?? 'AFN')),
                'exchange_rate' => (float) ($saleItem->rate ?? 0),
                'quantity' => (float) $saleItem->qty,
                'is_manual_price' => $saleItem->price_adjustment_type === 'manual',
            ]
        );

        $saleItem->carton_spec_snapshot = $snapshot;
        $saleItem->saveQuietly();

        return $snapshot;
    }

    public function isFrozen(SaleItem $saleItem): bool
    {
        $snapshot = $saleItem->carton_spec_snapshot;

        return is_array($snapshot)
            && ! empty($snapshot['rows'])
            && ($snapshot['status'] ?? null) === 'accepted';
    }

    /**
     * Frozen technical rows for a sale item, or an empty array for legacy items.
     *
     * @return array<int, array<string, mixed>>
     */
    public function frozenRows(SaleItem $saleItem): array
    {
        $snapshot = $saleItem->carton_spec_snapshot;

        return is_array($snapshot) && ! empty($snapshot['rows'])
            ? (array) $snapshot['rows']
            : [];
    }

    /**
     * Rebuild physical production requirements from a frozen carton snapshot.
     * Never consults the live board profile.
     *
     * @return array<int, array<string, mixed>>
     */
    public function requirementsFromSnapshot(array $snapshot, float $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }

        $rows = (array) ($snapshot['rows'] ?? []);

        $requirements = collect($rows)
            ->filter(fn ($row) => (int) ($row['material_id'] ?? 0) > 0)
            ->map(function ($row) {
                return [
                    'material_id' => (int) $row['material_id'],
                    'material_name' => $row['material_name'] ?? ('Material #' . $row['material_id']),
                    'component_type' => $row['component_type'] ?? self::COMPONENT_PAPER,
                    'formula_type' => $row['formula_type'] ?? 'carton_3d',
                    'base_quantity_per_unit' => (float) ($row['kg_per_unit'] ?? 0),
                    'required_quantity_per_unit' => (float) ($row['kg_with_wastage'] ?? $row['kg_per_unit'] ?? 0),
                    'cost_per_unit_usd' => (float) ($row['landed_cost_usd_per_kg'] ?? $row['cost_per_unit_usd'] ?? 0),
                    'cost_per_unit_afn' => (float) ($row['landed_cost_afn_per_kg'] ?? $row['per_gram_rate'] ?? 0),
                    'unit' => 'kg',
                ];
            })
            ->groupBy('material_id')
            ->map(function ($group) use ($quantity) {
                $first = $group->first();

                return [
                    'material_id' => $first['material_id'],
                    'material_name' => $first['material_name'],
                    'component_type' => $first['component_type'],
                    'formula_type' => $first['formula_type'],
                    'base_quantity' => (float) $group->sum('base_quantity_per_unit') * $quantity,
                    'required_quantity' => (float) $group->sum('required_quantity_per_unit') * $quantity,
                    'wastage_quantity' => max(
                        ((float) $group->sum('required_quantity_per_unit') - (float) $group->sum('base_quantity_per_unit')) * $quantity,
                        0.0
                    ),
                    'unit' => 'kg',
                    'cost_per_unit_usd' => $first['cost_per_unit_usd'],
                    'cost_per_unit_afn' => $first['cost_per_unit_afn'],
                ];
            })
            ->values()
            ->all();

        return $requirements;
    }

    // ─── HELPERS ───

    public function defaultExchangeRate(): float
    {
        $afn = \App\Models\Currency::where('code', 'AFN')->first();
        $usd = \App\Models\Currency::where('code', 'USD')->first();

        if ($afn && $usd && (float) $usd->exchange_rate > 0) {
            return (float) $afn->exchange_rate / (float) $usd->exchange_rate;
        }

        return (float) (\App\Models\Setting::first()->default_exchange_rate ?? 85);
    }

    private function resolvedConfigSnapshot(): array
    {
        return [
            'formula_constant' => (float) config('carton.formula_constant', 1550000),
            'sq_inch_to_m2' => (float) config('carton.sq_inch_to_m2', 0.00064516),
            'default_wastage_percentage' => (float) config('carton.default_wastage_percentage', 5),
            'adhesive' => [
                'glue_lines' => (float) config('carton.adhesive.glue_lines', 4),
                'dry_glue_gsm_per_line' => (float) config('carton.adhesive.dry_glue_gsm_per_line', 5.5),
                'glue_wastage_percentage' => (float) config('carton.adhesive.glue_wastage_percentage', 5),
                'adhesive_solids_percentage' => (float) config('carton.adhesive.adhesive_solids_percentage', 35),
                'recipe' => (array) config('carton.adhesive.recipe', []),
            ],
            'lamination' => (array) config('carton.lamination', []),
        ];
    }

    private function availableQuantity(int $materialId): float
    {
        return app(StockDeductionService::class)->availableProductionQuantity($materialId);
    }

    private function positiveDimension($value, string $label): float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw new RuntimeException("{$label} must be a positive number.");
        }

        $number = (float) $value;

        if ($number <= 0) {
            throw new RuntimeException("{$label} must be greater than zero.");
        }

        return $number;
    }

    private function trimNumber(float $value): string
    {
        $formatted = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
