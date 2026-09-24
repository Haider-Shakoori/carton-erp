<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoardProfile;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CartonSpecificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Simple carton quotation flow.
 *
 * The operator enters dimensions, ply, board profile, flute, printing and
 * quantity. The canonical CartonSpecificationService builds the technical BOM,
 * commercial price and stock shortage view; the accepted specification and
 * price are frozen on the sale item.
 */
class CartonQuotationController extends Controller
{
    public function __construct(
        private readonly CartonSpecificationService $specification
    ) {
    }

    /**
     * Reference data for the simple quotation form.
     */
    public function options(Request $request)
    {
        $profiles = BoardProfile::active()
            ->with('layers.material')
            ->orderBy('name')
            ->get()
            ->map(function (BoardProfile $profile) {
                return [
                    'id' => (int) $profile->id,
                    'name' => $profile->name,
                    'code' => $profile->code,
                    'ply' => (int) $profile->ply,
                    'flute_type' => $profile->flute_type,
                    'wastage_percentage' => (float) $profile->wastage_percentage,
                    'version' => $profile->version,
                    'layers' => $profile->layers->map(fn ($layer) => [
                        'role' => $layer->role,
                        'material_name' => $layer->material?->name,
                        'gsm' => $layer->gsm !== null ? (int) $layer->gsm : null,
                        'multiplication_layer' => (float) $layer->multiplication_layer,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'profiles' => $profiles,
                'box_styles' => $this->optionList((array) config('carton.box_styles', [])),
                'units' => array_keys((array) config('carton.length_units', ['inch' => 1])),
                'flutes' => $this->optionList((array) config('carton.flutes', [])),
                'printing' => $this->optionList((array) config('carton.printing', [])),
                'lamination' => (array) config('carton.lamination', []),
                'default_box_style' => config('carton.default_box_style', 'RSC'),
                'default_wastage_percentage' => (float) config('carton.default_wastage_percentage', 5),
            ],
        ]);
    }

    /**
     * Pure preview: resolve the specification without writing anything.
     */
    public function calculate(Request $request, Sale $sale)
    {
        $validated = $this->validateSpec($request);

        try {
            $result = $this->specification->calculate($validated, (float) $sale->exchange_rate);

            return response()->json([
                'success' => true,
                'data' => $this->previewPayload($result, $sale),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview several carton sizes under one shared board/printing specification.
     */
    public function calculateMany(Request $request, Sale $sale)
    {
        $validated = $this->validateMultiSpec($request);

        try {
            $previews = collect($validated['sizes'])->map(function (array $size) use ($validated, $sale) {
                $spec = $this->multiSpecPayload($validated, $size);
                $result = $this->specification->calculate($spec, (float) $sale->exchange_rate);
                $preview = $this->previewPayload($result, $sale);

                $standardPrice = $this->calculatedSaleCurrencyPrice($result, $sale);
                $quotedPrice = filled($size['quoted_unit_price'] ?? null)
                    ? (float) $size['quoted_unit_price']
                    : null;
                $effectivePrice = $quotedPrice !== null && $quotedPrice > 0
                    ? $quotedPrice
                    : $standardPrice;

                $preview['size_name'] = $size['name'] ?? null;
                $preview['standard_unit_price'] = round($standardPrice, 4);
                $preview['quoted_unit_price'] = $quotedPrice !== null ? round($quotedPrice, 4) : null;
                $preview['effective_unit_price'] = round($effectivePrice, 4);
                $preview['price_override'] = round($effectivePrice - $standardPrice, 4);

                return $preview;
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'previews' => $previews,
                    'count' => $previews->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add several size-specific carton lines to a draft sale in one transaction.
     */
    public function addMany(Request $request, Sale $sale)
    {
        $validated = $this->validateMultiSpec($request);

        if ($sale->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to a confirmed sale.',
            ], 422);
        }

        $product = Product::find((int) $validated['product_id']);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'The selected finished product was not found.',
            ], 422);
        }

        try {
            $created = DB::transaction(function () use ($validated, $sale, $product) {
                return collect($validated['sizes'])->map(function (array $size) use ($validated, $sale, $product) {
                    $spec = $this->multiSpecPayload($validated, $size);
                    $result = $this->specification->calculate($spec, (float) $sale->exchange_rate);

                    $saleItem = $this->persistQuotationLine(
                        $sale,
                        $product,
                        $spec,
                        $result,
                        $size['quoted_unit_price'] ?? null,
                        $size['description'] ?? null,
                        $size['name'] ?? null
                    );

                    return [
                        'sale_item_id' => (int) $saleItem->id,
                        'bom_id' => (int) $saleItem->bom_id,
                        'name' => $size['name'] ?? null,
                        'quantity' => (float) $saleItem->qty,
                        'standard_unit_price' => round((float) $saleItem->base_price, 4),
                        'effective_unit_price' => round((float) $saleItem->unit_price, 4),
                        'price_override' => round(
                            (float) $saleItem->unit_price - (float) $saleItem->base_price,
                            4
                        ),
                    ];
                })->values()->all();
            });

            $sale->refresh();
            $sale->recalculateTotals();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' carton size'
                    . (count($created) === 1 ? '' : 's')
                    . ' added to the quotation.',
                'data' => [
                    'items' => $created,
                    'sale_total' => (float) $sale->fresh()->grand_total,
                    'usd_total' => (float) $sale->fresh()->usd_grand_total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Multi-size carton quotation failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding carton sizes: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Persist the generated technical BOM, freeze the specification on a sale
     * item and use the frozen accepted price for the quotation line.
     */
    public function add(Request $request, Sale $sale)
    {
        $validated = $this->validateSpec($request);

        if ($sale->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to a confirmed sale.',
            ], 422);
        }

        $product = Product::find((int) $validated['product_id']);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'The selected finished product was not found.',
            ], 422);
        }

        try {
            $result = $this->specification->calculate($validated, (float) $sale->exchange_rate);

            $saleCurrencyCode = strtoupper((string) ($sale->currency?->code ?? 'AFN'));
            $isUsd = $saleCurrencyCode === 'USD';
            $rate = max((float) $result['exchange_rate'], 0.000001);

            $calculatedUnitPrice = $isUsd
                ? (float) $result['commercial']['selling_price_usd_per_unit']
                : (float) $result['commercial']['selling_price_afn_per_unit'];

            $quotedUnitPrice = isset($validated['quoted_unit_price']) && $validated['quoted_unit_price'] !== null
                ? (float) $validated['quoted_unit_price']
                : 0.0;

            $unitPrice = $quotedUnitPrice > 0 ? $quotedUnitPrice : $calculatedUnitPrice;
            $unitPrice = round($unitPrice, 4);

            if ($unitPrice <= 0) {
                throw new \RuntimeException(
                    'Could not determine a selling price. Configure landed material rates or enter a price.'
                );
            }

            $quantity = (float) $result['quantity'];

            DB::beginTransaction();

            $bom = $this->specification->persistTechnicalBom(
                $result,
                (int) $product->id,
                Auth::id()
            );

            $accepted = [
                'unit_price' => $unitPrice,
                'total' => round($unitPrice * $quantity, 2),
                'currency' => $saleCurrencyCode,
                'exchange_rate' => $rate,
                'quantity' => $quantity,
                'is_manual_price' => $quotedUnitPrice > 0,
            ];

            $snapshot = $this->specification->snapshot($result, $accepted, 'quotation');

            $totalCostUsd = (float) $result['physical']['material_cost_usd_total'];
            $usdUnitPrice = $isUsd ? $unitPrice : $unitPrice / $rate;
            $usdTotal = $usdUnitPrice * $quantity;
            $profitUsd = $usdTotal - $totalCostUsd;

            $saleItem = SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $product->id,
                'bom_id' => $bom->id,
                'purchase_item_id' => null,
                'sale_currency_id' => $sale->currency_id,
                'qty' => $quantity,
                'ordered_qty' => $quantity,
                'cost_per_unit_usd' => $quantity > 0 ? $totalCostUsd / $quantity : 0,
                'total_cost_usd' => $totalCostUsd,
                'unit_price' => $unitPrice,
                'base_price' => $calculatedUnitPrice,
                'original_unit_price' => $calculatedUnitPrice,
                'final_price' => $unitPrice,
                'price_adjustment_type' => $quotedUnitPrice > 0 ? 'manual' : 'none',
                'total' => $accepted['total'],
                'discount' => 0,
                'tax' => 0,
                'usd_unit_price' => $usdUnitPrice,
                'usd_total' => $usdTotal,
                'usd_discount' => 0,
                'usd_tax' => 0,
                'rate' => $rate,
                'profit_usd' => $profitUsd,
                'profit_afn' => $profitUsd * $rate,
                'profit_percentage' => $usdTotal > 0 ? ($profitUsd / $usdTotal) * 100 : 0,
                'remarks' => sprintf(
                    'Carton specification quotation | Box style: %s | Profile: %s v%s | Ply: %d | Materials: %d',
                    $result['spec']['box_style'],
                    $result['profile']['name'] ?? '',
                    $result['profile']['version'] ?? '1.0',
                    $result['spec']['ply'],
                    count($result['rows'])
                ),
                'quotation_description' => $validated['quotation_description'] ?? null,
                'carton_spec_snapshot' => $snapshot,
            ]);

            $sale->recalculateTotals();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Carton specification added to the sale.',
                'data' => [
                    'sale_item_id' => $saleItem->id,
                    'bom_id' => $bom->id,
                    'bom_code' => $bom->code,
                    'preview' => $this->previewPayload($result, $sale),
                    'sale_total' => (float) $sale->grand_total,
                    'usd_total' => (float) $sale->usd_grand_total,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Carton specification quotation failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding carton specification: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validateMultiSpec(Request $request): array
    {
        return $request->validate([
            'product_id' => 'required|exists:products,id',
            'box_style' => 'nullable|string|max:20',
            'dimension_unit' => ['required', 'string', Rule::in(array_keys((array) config('carton.length_units', ['inch' => 1])))],
            'board_profile_id' => 'required|exists:board_profiles,id',
            'ply' => 'nullable|integer|min:1|max:11',
            'flute_type' => 'nullable|string|max:20',
            'printing_option' => 'nullable|string|max:40',
            'print_cost_afn' => 'nullable|numeric|min:0',
            'lamination_enabled' => 'nullable|boolean',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'work_percentage' => 'nullable|numeric|min:0|max:100',
            'profit_margin_percentage' => 'nullable|numeric|min:0|max:1000',
            'sizes' => 'required|array|min:1|max:25',
            'sizes.*.name' => 'nullable|string|max:255',
            'sizes.*.length' => 'required|numeric|gt:0',
            'sizes.*.width' => 'required|numeric|gt:0',
            'sizes.*.height' => 'required|numeric|gt:0',
            'sizes.*.quantity' => 'required|numeric|gt:0',
            'sizes.*.quoted_unit_price' => 'nullable|numeric|min:0',
            'sizes.*.description' => 'nullable|string|max:2000',
        ]);
    }

    private function multiSpecPayload(array $validated, array $size): array
    {
        return [
            'product_id' => $validated['product_id'],
            'box_style' => $validated['box_style'] ?? null,
            'length' => $size['length'],
            'width' => $size['width'],
            'height' => $size['height'],
            'dimension_unit' => $validated['dimension_unit'],
            'board_profile_id' => $validated['board_profile_id'],
            'ply' => $validated['ply'] ?? null,
            'flute_type' => $validated['flute_type'] ?? null,
            'printing_option' => $validated['printing_option'] ?? null,
            'print_cost_afn' => $validated['print_cost_afn'] ?? null,
            'lamination_enabled' => (bool) ($validated['lamination_enabled'] ?? false),
            'quantity' => $size['quantity'],
            'wastage_percentage' => $validated['wastage_percentage'] ?? null,
            'work_percentage' => $validated['work_percentage'] ?? 40,
            'profit_margin_percentage' => $validated['profit_margin_percentage'] ?? 0,
            'quoted_unit_price' => $size['quoted_unit_price'] ?? null,
            'quotation_description' => $size['description'] ?? null,
        ];
    }

    private function calculatedSaleCurrencyPrice(array $result, Sale $sale): float
    {
        $isUsd = strtoupper((string) ($sale->currency?->code ?? 'AFN')) === 'USD';

        return $isUsd
            ? (float) $result['commercial']['selling_price_usd_per_unit']
            : (float) $result['commercial']['selling_price_afn_per_unit'];
    }

    private function persistQuotationLine(
        Sale $sale,
        Product $product,
        array $validated,
        array $result,
        mixed $quotedUnitPrice = null,
        ?string $description = null,
        ?string $sizeName = null
    ): SaleItem {
        $saleCurrencyCode = strtoupper((string) ($sale->currency?->code ?? 'AFN'));
        $isUsd = $saleCurrencyCode === 'USD';
        $rate = max((float) $result['exchange_rate'], 0.000001);
        $calculatedUnitPrice = $this->calculatedSaleCurrencyPrice($result, $sale);

        $quoted = filled($quotedUnitPrice) ? (float) $quotedUnitPrice : 0.0;
        $unitPrice = round($quoted > 0 ? $quoted : $calculatedUnitPrice, 4);

        if ($unitPrice <= 0) {
            throw new \RuntimeException(
                'Could not determine a selling price. Configure landed material rates or enter a customer price.'
            );
        }

        $quantity = (float) $result['quantity'];
        $bom = $this->specification->persistTechnicalBom(
            $result,
            (int) $product->id,
            Auth::id()
        );

        if (filled($sizeName)) {
            $bom->forceFill(['name' => trim((string) $sizeName)])->saveQuietly();
        }

        $accepted = [
            'unit_price' => $unitPrice,
            'total' => round($unitPrice * $quantity, 2),
            'currency' => $saleCurrencyCode,
            'exchange_rate' => $rate,
            'quantity' => $quantity,
            'is_manual_price' => $quoted > 0,
        ];

        $snapshot = $this->specification->snapshot($result, $accepted, 'quotation');
        $totalCostUsd = (float) $result['physical']['material_cost_usd_total'];
        $usdUnitPrice = $isUsd ? $unitPrice : $unitPrice / $rate;
        $usdTotal = $usdUnitPrice * $quantity;
        $profitUsd = $usdTotal - $totalCostUsd;

        return SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'bom_id' => $bom->id,
            'purchase_item_id' => null,
            'sale_currency_id' => $sale->currency_id,
            'qty' => $quantity,
            'ordered_qty' => $quantity,
            'cost_per_unit_usd' => $quantity > 0 ? $totalCostUsd / $quantity : 0,
            'total_cost_usd' => $totalCostUsd,
            'unit_price' => $unitPrice,
            'base_price' => $calculatedUnitPrice,
            'original_unit_price' => $calculatedUnitPrice,
            'final_price' => $unitPrice,
            'price_adjustment_type' => $quoted > 0 ? 'manual' : 'none',
            'total' => $accepted['total'],
            'discount' => 0,
            'tax' => 0,
            'usd_unit_price' => $usdUnitPrice,
            'usd_total' => $usdTotal,
            'usd_discount' => 0,
            'usd_tax' => 0,
            'rate' => $rate,
            'profit_usd' => $profitUsd,
            'profit_afn' => $profitUsd * $rate,
            'profit_percentage' => $usdTotal > 0 ? ($profitUsd / $usdTotal) * 100 : 0,
            'remarks' => sprintf(
                'Carton specification quotation | Size: %s | Box style: %s | Profile: %s v%s | Ply: %d | Materials: %d',
                filled($sizeName) ? trim((string) $sizeName) : sprintf(
                    '%s×%s×%s %s',
                    $result['spec']['length'],
                    $result['spec']['width'],
                    $result['spec']['height'],
                    $result['spec']['dimension_unit']
                ),
                $result['spec']['box_style'],
                $result['profile']['name'] ?? '',
                $result['profile']['version'] ?? '1.0',
                $result['spec']['ply'],
                count($result['rows'])
            ),
            'quotation_description' => $description,
            'carton_spec_snapshot' => $snapshot,
        ]);
    }

    private function validateSpec(Request $request): array
    {
        return $request->validate([
            'product_id' => 'required|exists:products,id',
            'box_style' => 'nullable|string|max:20',
            'length' => 'required|numeric|gt:0',
            'width' => 'required|numeric|gt:0',
            'height' => 'required|numeric|gt:0',
            'dimension_unit' => ['required', 'string', Rule::in(array_keys((array) config('carton.length_units', ['inch' => 1])))],
            'board_profile_id' => 'required|exists:board_profiles,id',
            'ply' => 'nullable|integer|min:1|max:11',
            'flute_type' => 'nullable|string|max:20',
            'printing_option' => 'nullable|string|max:40',
            'print_cost_afn' => 'nullable|numeric|min:0',
            'lamination_enabled' => 'nullable|boolean',
            'quantity' => 'required|numeric|gt:0',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'work_percentage' => 'nullable|numeric|min:0|max:100',
            'profit_margin_percentage' => 'nullable|numeric|min:0|max:1000',
            'quoted_unit_price' => 'nullable|numeric|min:0',
            'quotation_description' => 'nullable|string|max:2000',
        ]);
    }

    /**
     * The preview intentionally exposes the technical BOM rows for the
     * Advanced/Technical section but keeps the top summary simple.
     */
    private function previewPayload(array $result, Sale $sale): array
    {
        $isUsd = strtoupper((string) ($sale->currency?->code ?? 'AFN')) === 'USD';
        $rate = max((float) $result['exchange_rate'], 0.000001);

        return [
            'box_style' => $result['spec']['box_style'],
            'box_style_label' => $result['spec']['box_style_label'],
            'dimensions' => [
                'length' => (float) $result['spec']['length'],
                'width' => (float) $result['spec']['width'],
                'height' => (float) $result['spec']['height'],
                'unit' => $result['spec']['dimension_unit'],
                'reel_length_inch' => (float) $result['spec']['reel_length_inch'],
                'reel_height_inch' => (float) $result['spec']['reel_height_inch'],
                'board_area_m2' => (float) $result['spec']['board_area_m2'],
            ],
            'ply' => (int) $result['spec']['ply'],
            'flute_type' => $result['spec']['flute_type'],
            'printing' => [
                'option' => $result['spec']['printing_option'],
                'label' => $result['spec']['printing_label'],
                'cost_afn' => (float) $result['spec']['print_cost_afn'],
            ],
            'profile' => [
                'id' => $result['profile']['id'] ?? null,
                'name' => $result['profile']['name'] ?? null,
                'code' => $result['profile']['code'] ?? null,
                'version' => $result['profile']['version'] ?? null,
            ],
            'quantity' => (float) $result['quantity'],
            'wastage_percentage' => (float) $result['spec']['wastage_percentage'],
            'paper' => $result['paper'],
            'adhesive' => $result['adhesive'],
            'lamination' => $result['lamination'] ?? [
                'enabled' => false,
                'kg_per_unit' => 0,
                'kg_total' => 0,
                'base_cost_afn_per_unit' => 0,
            ],
            'physical' => $result['physical'],
            'commercial' => [
                'paper_basis_afn' => (float) $result['commercial']['paper_basis_afn_per_unit'],
                'work_profit_afn' => (float) $result['commercial']['work_profit_afn_per_unit'],
                'print_afn' => (float) $result['commercial']['print_cost_afn_per_unit'],
                'lamination_afn' => (float) ($result['commercial']['lamination_cost_afn_per_unit'] ?? 0),
                'net_rate_afn' => (float) $result['commercial']['net_rate_afn_per_unit'],
                'work_percentage' => (float) $result['commercial']['work_percentage'],
                'profit_margin_percentage' => (float) $result['commercial']['profit_margin_percentage'],
                'selling_price_afn' => (float) $result['commercial']['selling_price_afn_per_unit'],
                'selling_price_usd' => (float) $result['commercial']['selling_price_usd_per_unit'],
                'order_total_afn' => (float) $result['commercial']['order_total_afn'],
                'order_total_usd' => (float) $result['commercial']['order_total_usd'],
            ],
            'expected_profit_afn' => (float) $result['commercial']['order_total_afn']
                - ((float) $result['physical']['material_cost_afn_per_unit'] * (float) $result['quantity']),
            'currency' => $isUsd ? 'USD' : 'AFN',
            'exchange_rate' => $rate,
            'shortages' => $result['shortages'],
            'rows' => collect($result['rows'])->map(fn (array $row) => [
                'material_id' => $row['material_id'],
                'material_name' => $row['material_name'],
                'component_type' => $row['component_type'],
                'role' => $row['role'],
                'formula_type' => $row['formula_type'],
                'paper_gsm' => $row['paper_gsm'],
                'multiplication_layer' => $row['multiplication_layer'],
                'kg_per_unit' => round((float) $row['kg_per_unit'], 6),
                'kg_with_wastage' => round((float) $row['kg_with_wastage'], 6),
                'wastage_percentage' => (float) $row['wastage_percentage'],
                'work_percentage' => (float) $row['work_percentage'],
                'apply_work_percentage' => (bool) $row['apply_work_percentage'],
                'landed_cost_usd_per_kg' => round((float) $row['cost_per_unit_usd'], 6),
                'landed_cost_afn_per_kg' => round((float) $row['cost_per_unit_afn'], 4),
                'line_physical_cost_afn' => round((float) $row['physical_cost_afn_per_unit'], 4),
                'row_net_rate_afn' => round((float) $row['row_net_rate_afn'], 4),
            ])->values(),
        ];
    }

    private function optionList(array $options): array
    {
        $list = [];

        foreach ($options as $key => $option) {
            $list[] = [
                'value' => (string) $key,
                'label' => is_array($option) ? ($option['label'] ?? (string) $key) : (string) $option,
            ];
        }

        return $list;
    }
}
