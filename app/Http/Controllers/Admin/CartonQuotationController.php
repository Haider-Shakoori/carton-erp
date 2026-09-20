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
            'physical' => $result['physical'],
            'commercial' => [
                'paper_basis_afn' => (float) $result['commercial']['paper_basis_afn_per_unit'],
                'work_profit_afn' => (float) $result['commercial']['work_profit_afn_per_unit'],
                'print_afn' => (float) $result['commercial']['print_cost_afn_per_unit'],
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
