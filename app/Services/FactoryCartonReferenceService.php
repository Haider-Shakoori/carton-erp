<?php

namespace App\Services;

use App\Models\Product;

/**
 * Read-only factory sheet reference: a net carton weight is not a BOM.
 *
 * We match by exact original customer-source dimensions + ply; name-only
 * matches or dimensions without a known ply are deliberately rejected.
 * Existing per-product recorded weights always take precedence.
 */
class FactoryCartonReferenceService
{
    public function match(Product $product): ?array
    {
        if (! $product->isFinishedGood()) {
            return null;
        }

        $references = (array) config('carton_factory_reference.weights', []);

        if ($references === []) {
            return null;
        }

        $product->loadMissing('finishedGoodSpecifications');

        $matches = [];

        foreach ($product->finishedGoodSpecifications as $specification) {
            if (! str_starts_with((string) $specification->source_key, 'client-carton-order:')) {
                continue;
            }

            $ply = (int) ($specification->ply ?? 0);
            $dimensions = [
                (float) $specification->length,
                (float) $specification->width,
                (float) $specification->height,
            ];

            if ($ply <= 0 || min($dimensions) <= 0) {
                continue;
            }

            foreach ($references as $reference) {
                if ($ply !== (int) ($reference['ply'] ?? 0)) {
                    continue;
                }

                $size = $reference['dimensions_cm'] ?? [];
                if (count($size) !== 3) {
                    continue;
                }

                // The source uses centimetres; original master stores mm.
                // Tolerance only absorbs storage rounding, not size guesses.
                $isExact = true;
                foreach ($dimensions as $index => $millimetres) {
                    if (abs($millimetres - ((float) $size[$index] * 10)) > 0.11) {
                        $isExact = false;
                        break;
                    }
                }

                if ($isExact && (float) ($reference['net_weight_g'] ?? 0) > 0) {
                    $key = implode('x', array_map('strval', $size)).':'.$ply;
                    $matches[$key] = $reference;
                }
            }
        }

        // Conflicting matches must be resolved with the factory, never averaged.
        return count($matches) === 1 ? array_values($matches)[0] : null;
    }
}
