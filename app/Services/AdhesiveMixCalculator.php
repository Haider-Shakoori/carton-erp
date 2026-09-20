<?php

namespace App\Services;

/**
 * Dimension-driven adhesive (glue mixing) calculation.
 *
 * BoardAreaM2  = ((L + W) * 2 + 4) * (W + H + 1) * 0.00064516
 * DryGlueGrams = BoardAreaM2 * GlueLines * DryGlueGsmPerLine
 * WetGlueKg    = (DryGlueGrams * (1 + GlueWastage% / 100) / 1000) / (Solids% / 100)
 * IngredientKg = WetGlueKg * RecipeFraction
 *
 * All defaults live in config/carton.php. Effective parameters are snapshotted
 * into the BOM row's formula_data so historical rows stay reproducible.
 */
class AdhesiveMixCalculator
{
    /**
     * Factory defaults. Keys intentionally match the per-row override keys.
     */
    public function defaults(): array
    {
        return [
            'sq_inch_to_m2' => (float) config('carton.sq_inch_to_m2', 0.00064516),
            'glue_lines' => (float) config('carton.adhesive.glue_lines', 4),
            'dry_glue_gsm_per_line' => (float) config('carton.adhesive.dry_glue_gsm_per_line', 5.5),
            'glue_wastage_percentage' => (float) config('carton.adhesive.glue_wastage_percentage', 5),
            'adhesive_solids_percentage' => (float) config('carton.adhesive.adhesive_solids_percentage', 35),
        ];
    }

    /**
     * Merge overrides over the factory defaults and keep them physically sane.
     */
    public function parameters(array $overrides = []): array
    {
        $parameters = $this->defaults();

        foreach (array_keys($parameters) as $key) {
            if (array_key_exists($key, $overrides) && $overrides[$key] !== null && $overrides[$key] !== '') {
                $parameters[$key] = (float) $overrides[$key];
            }
        }

        $parameters['sq_inch_to_m2'] = max($parameters['sq_inch_to_m2'], 0.0);
        $parameters['glue_lines'] = max($parameters['glue_lines'], 0.0);
        $parameters['dry_glue_gsm_per_line'] = max($parameters['dry_glue_gsm_per_line'], 0.0);
        $parameters['glue_wastage_percentage'] = min(max($parameters['glue_wastage_percentage'], 0.0), 100.0);
        $parameters['adhesive_solids_percentage'] = min(max($parameters['adhesive_solids_percentage'], 0.0), 100.0);

        return $parameters;
    }

    /**
     * Map a raw-material product name to its recipe key.
     */
    public function resolveRecipeKey(?string $materialName): ?string
    {
        $normalizedName = strtolower(trim((string) $materialName));

        if ($normalizedName === '') {
            return null;
        }

        $map = (array) config('carton.adhesive.materials', []);

        if (isset($map[$normalizedName])) {
            return (string) $map[$normalizedName];
        }

        foreach ($map as $name => $key) {
            if ($name !== '' && str_contains($normalizedName, (string) $name)) {
                return (string) $key;
            }
        }

        return null;
    }

    /**
     * Ingredient fraction of the wet glue (kg ingredient / kg wet glue).
     */
    public function recipeFraction(?string $recipeKey, array $overrides = []): float
    {
        if (array_key_exists('recipe_percentage', $overrides)
            && $overrides['recipe_percentage'] !== null
            && $overrides['recipe_percentage'] !== '') {
            return max((float) $overrides['recipe_percentage'], 0.0);
        }

        if ($recipeKey === null) {
            return 0.0;
        }

        $recipe = (array) config('carton.adhesive.recipe', []);

        return max((float) ($recipe[$recipeKey] ?? 0), 0.0);
    }

    public function reelLength(float $lengthIn, float $widthIn): float
    {
        return (($lengthIn + $widthIn) * 2) + 4;
    }

    public function reelHeight(float $widthIn, float $heightIn): float
    {
        return $widthIn + $heightIn + 1;
    }

    public function boardAreaM2(float $lengthIn, float $widthIn, float $heightIn, array $parameters = []): float
    {
        if ($lengthIn <= 0 || $widthIn <= 0 || $heightIn <= 0) {
            return 0.0;
        }

        $parameters = $parameters ?: $this->parameters();

        return $this->reelLength($lengthIn, $widthIn)
            * $this->reelHeight($widthIn, $heightIn)
            * (float) $parameters['sq_inch_to_m2'];
    }

    public function dryGlueGrams(float $boardAreaM2, array $parameters): float
    {
        return $boardAreaM2
            * (float) $parameters['glue_lines']
            * (float) $parameters['dry_glue_gsm_per_line'];
    }

    public function dryGlueWithWastageGrams(float $dryGlueGrams, array $parameters): float
    {
        return $dryGlueGrams * (1 + ((float) $parameters['glue_wastage_percentage'] / 100));
    }

    public function wetGlueKg(float $dryGlueWithWastageGrams, array $parameters): float
    {
        $solids = (float) $parameters['adhesive_solids_percentage'];

        if ($solids <= 0) {
            return 0.0;
        }

        return ($dryGlueWithWastageGrams / 1000) / ($solids / 100);
    }

    /**
     * Complete calculation trace for one finished carton.
     */
    public function breakdown(float $lengthIn, float $widthIn, float $heightIn, ?string $recipeKey, array $overrides = []): array
    {
        $parameters = $this->parameters($overrides);
        $boardAreaM2 = $this->boardAreaM2($lengthIn, $widthIn, $heightIn, $parameters);
        $dryGlueGrams = $this->dryGlueGrams($boardAreaM2, $parameters);
        $dryGlueWithWastageGrams = $this->dryGlueWithWastageGrams($dryGlueGrams, $parameters);
        $wetGlueKg = $this->wetGlueKg($dryGlueWithWastageGrams, $parameters);
        $fraction = $this->recipeFraction($recipeKey, $overrides);

        return [
            'reel_length_inch' => $this->reelLength($lengthIn, $widthIn),
            'reel_height_inch' => $this->reelHeight($widthIn, $heightIn),
            'board_area_m2' => $boardAreaM2,
            'dry_glue_grams' => $dryGlueGrams,
            'dry_glue_with_wastage_grams' => $dryGlueWithWastageGrams,
            'wet_glue_kg' => $wetGlueKg,
            'recipe_key' => $recipeKey,
            'recipe_fraction' => $fraction,
            'ingredient_kg' => $wetGlueKg * $fraction,
            'parameters' => $parameters,
        ];
    }

    /**
     * Physical kg of one adhesive ingredient per finished carton.
     */
    public function perCartonKg(float $lengthIn, float $widthIn, float $heightIn, ?string $recipeKey, array $overrides = []): float
    {
        return (float) $this->breakdown($lengthIn, $widthIn, $heightIn, $recipeKey, $overrides)['ingredient_kg'];
    }
}
