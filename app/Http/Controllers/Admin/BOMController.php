<?php
// app/Http/Controllers/Admin/BOMController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BOM;
use App\Models\BOMItem;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BOMController extends Controller
{
    /**
     * Display a listing of BOMs.
     */
    public function index()
    {
        // Use paginate() instead of get() to get a LengthAwarePaginator
        $boms = BOM::with(['product', 'createdBy', 'items'])
            ->latest()
            ->paginate(15); // Changed from get() to paginate(15)

        return view('admin.bom.index', compact('boms'));
    }
    /**
     * Show the form for creating a new BOM.
     */
    public function create()
    {
        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->get();

        // ─── FIX: Get materials with purchase currency information ───
        $materials = Product::rawMaterials()
            ->where('is_active', true)
            ->with(['category'])
            ->get()
            ->map(function($material) {
                // Get the latest purchase for this material to determine currency
                $latestPurchaseItem = $material->purchaseItems()
                    ->whereHas('purchase', function($q) {
                        $q->where('status', 'arrived');
                    })
                    ->with(['purchase.currency'])
                    ->latest('id')
                    ->first();

                if ($latestPurchaseItem && $latestPurchaseItem->purchase) {
                    $material->purchase_currency = $latestPurchaseItem->purchase->currency->code ?? 'AFN';
                    $material->purchase_currency_id = $latestPurchaseItem->purchase->currency_id ?? null;
                } else {
                    // Default to AFN if no purchase exists
                    $material->purchase_currency = 'AFN';
                    $material->purchase_currency_id = null;
                }

                return $material;
            });

        $categories = Category::where('is_active', true)->get();

        // Get system currency
        $defaultCurrency = Currency::where('is_default', 1)->first();
        $afnCurrency = Currency::where('code', 'AFN')->first();
        $usdCurrency = Currency::where('code', 'USD')->first();

        $currencySymbol = $defaultCurrency ? $defaultCurrency->symbol : '؋';
        $exchangeRate = 1;

        if ($afnCurrency && $usdCurrency && $usdCurrency->exchange_rate > 0) {
            $exchangeRate = $afnCurrency->exchange_rate / $usdCurrency->exchange_rate;
        } else {
            $exchangeRate = 85; // Default fallback
        }

        return view('admin.bom.create', compact(
            'products',
            'materials',
            'categories',
            'currencySymbol',
            'exchangeRate',
            'defaultCurrency',
            'afnCurrency',
            'usdCurrency'
        ));
    }

    /**
     * Display the existing BOM cost calculator.
     */
    public function calculator()
    {
        $boms = BOM::with('product')->latest()->get();
        $defaultCurrency = Currency::where('is_default', 1)->first();
        $currencySymbol = $defaultCurrency?->symbol ?? '؋';
        $currencyCode = $defaultCurrency?->code ?? 'AFN';

        return view('admin.bom.calculator', compact('boms', 'currencySymbol', 'currencyCode'));
    }

    /**
     * Get material cost and batch breakdown with currency info
     */

    // app/Http/Controllers/Admin/BOMController.php

    /**
     * Calculate BOM costs (AJAX endpoint for the calculate button)
     */
    public function calculate(Request $request)
    {
        try {
            $bom = BOM::with('items.material')->findOrFail($request->bom_id);
            $quantity = $request->quantity ?? 1;

            // ─── GET EXCHANGE RATE ───
            $exchangeRate = $request->exchange_rate ?? $bom->exchange_rate ?? 85;

            // ─── GET DIMENSIONS FROM FIRST BOM ITEM ───
            $firstItem = $bom->items->first();
            $length = $firstItem ? ($firstItem->length_inch ?? 0) : 0;
            $width = $firstItem ? ($firstItem->width_inch ?? 0) : 0;
            $height = $firstItem ? ($firstItem->height_inch ?? 0) : 0;

            // ─── Step 1: Reel Length ───
            $reelLength = (($length + $width) * 2) + 4;

            // ─── Step 2: Reel Height ───
            $reelHeight = $width + $height + 1;

            // ─── CALCULATE EACH MATERIAL ROW ───
            $rowNetRates = [];
            $totalCartonNetRate = 0;
            $totalMaterialCostUsd = 0;
            $requirements = [];
            $hasShortage = false;

            foreach ($bom->items as $item) {
                // Get material-specific values
                $gsm = $item->paper_gsm ?? 0;
                $perGramRate = $item->per_gram_rate ?? 0;
                $multiplicationLayer = $item->multiplication_layer ?? 1;
                $printCost = $item->print ?? 0;
                $constant = $item->formula_constant ?? 1550000;
                $workPercentage = $item->work_percentage ?? 40;

                // ─── Step 3: Division Value ───
                $divisionValue = $reelLength * $reelHeight * $gsm * $perGramRate;

                // ─── Step 4: Paper Rate ───
                $paperRate = $constant > 0 ? $divisionValue / $constant : 0;

                // ─── Step 5: Paper Rate × Layers ───
                $paperRateByLayers = $multiplicationLayer * $paperRate;

                // ─── Step 6: Work Amount ───
                $workAmount = $paperRateByLayers * ((float) $workPercentage / 100);

                // ─── Step 7: Row Net Rate ───
                $rowNetRate = $printCost + $paperRateByLayers + $workAmount;

                $rowNetRates[] = [
                    'material_name' => $item->material->name ?? 'Unknown',
                    'reel_length' => $reelLength,
                    'reel_height' => $reelHeight,
                    'division_value' => $divisionValue,
                    'paper_rate' => $paperRate,
                    'paper_rate_by_layers' => $paperRateByLayers,
                    'work_amount' => $workAmount,
                    'row_net_rate' => $rowNetRate,
                    'gsm' => $gsm,
                    'per_gram_rate' => $perGramRate,
                    'multiplication_layer' => $multiplicationLayer,
                    'print_cost' => $printCost,
                    'constant' => $constant,
                    'work_percentage' => $workPercentage,
                ];

                // ─── SUM all rows for final carton net rate ───
                $totalCartonNetRate += $rowNetRate;

                // ─── Material Requirements for Stock Check ───
                $requiredQty = $item->quantity * (1 + ($item->wastage_percentage / 100));

                $purchaseCurrency = $item->purchase_currency ?? 'AFN';
                $costPerUnitUsd = $item->cost_per_unit_usd ?? 0;

                if ($purchaseCurrency === 'AFN' && $item->cost_per_unit_afn > 0) {
                    $costPerUnitUsd = $item->cost_per_unit_afn / $exchangeRate;
                }

                $totalCostUsd = $requiredQty * $costPerUnitUsd * $quantity;
                $totalMaterialCostUsd += $totalCostUsd;

                $availableStock = $item->material->current_stock ?? 0;
                $shortage = max(0, $totalCostUsd - $availableStock);

                if ($shortage > 0) {
                    $hasShortage = true;
                }

                $requirements[] = [
                    'material_id' => $item->material_id,
                    'material_name' => $item->material->name ?? 'Unknown',
                    'category' => $item->material->category->name ?? 'Uncategorized',
                    'total_required' => $requiredQty * $quantity,
                    'unit' => $item->unit,
                    'cost_per_unit_usd' => $costPerUnitUsd,
                    'cost_per_unit_afn' => $costPerUnitUsd * $exchangeRate,
                    'total_cost_usd' => $totalCostUsd,
                    'total_cost_afn' => $totalCostUsd * $exchangeRate,
                    'available_stock' => $availableStock,
                    'shortage' => $shortage,
                    'is_available' => $shortage == 0,
                    'purchase_currency' => $purchaseCurrency,
                    'row_net_rate' => $rowNetRate,
                ];
            }

            // ─── WORK PERCENTAGE COST ───
            $workPercentage = ($bom->work_percentage ?? 40) / 100;
            $workCostUsd = $totalMaterialCostUsd * $workPercentage;
            $workCostAfn = $workCostUsd * $exchangeRate;

            // ─── TOTAL COST ───
            $totalCostUsd = $totalMaterialCostUsd + $workCostUsd;
            $totalCostAfn = $totalCostUsd * $exchangeRate;

            // ─── SELLING PRICE ───
            $profitMargin = ($bom->profit_margin_percentage ?? 0) / 100;
            $sellingPriceUsd = $totalCostUsd * (1 + $profitMargin);
            $sellingPriceAfn = $sellingPriceUsd * $exchangeRate;
            $profitAfn = $sellingPriceAfn - $totalCostAfn;

            return response()->json([
                'success' => true,
                'requirements' => $requirements,
                'has_shortage' => $hasShortage,
                'row_net_rates' => $rowNetRates,
                'total_carton_net_rate' => $totalCartonNetRate,
                'reel_length' => $reelLength,
                'reel_height' => $reelHeight,
                'summary' => [
                    'material_cost_usd' => $totalMaterialCostUsd,
                    'material_cost_afn' => $totalMaterialCostUsd * $exchangeRate,
                    'work_cost_usd' => $workCostUsd,
                    'work_cost_afn' => $workCostAfn,
                    'total_cost_usd' => $totalCostUsd,
                    'total_cost_afn' => $totalCostAfn,
                    'selling_price_usd' => $sellingPriceUsd,
                    'selling_price_afn' => $sellingPriceAfn,
                    'profit_usd' => $sellingPriceUsd - $totalCostUsd,
                    'profit_afn' => $profitAfn,
                    'profit_margin_percentage' => $bom->profit_margin_percentage ?? 0,
                    'work_percentage' => $bom->work_percentage ?? 40,
                    'exchange_rate' => $exchangeRate,
                    'cost_per_unit_usd' => $totalCostUsd / $quantity,
                    'cost_per_unit_afn' => $totalCostAfn / $quantity,
                    'carton_net_rate' => $totalCartonNetRate,
                    'reel_length' => $reelLength,
                    'reel_height' => $reelHeight,
                    'row_count' => count($rowNetRates),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('BOM calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error calculating BOM: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getMaterialCost($materialId)
    {
        try {
            $material = Product::with('category')->find($materialId);

            if (! $material) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found',
                ], 404);
            }

            if (! $material->isRawMaterial()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product is not a raw material',
                ], 422);
            }

            $exchangeRate = $this->getDefaultExchangeRate();
            $costing = app(\App\Services\BOMCostingService::class);

            $purchaseItems = PurchaseItem::query()
                ->where('product_id', $material->id)
                ->whereHas('purchase', fn ($q) => $q->where('status', 'arrived'))
                ->with(['purchase.currency'])
                ->get()
                ->filter(fn (PurchaseItem $item) => $item->availableInventoryQuantity() > 0)
                ->sortByDesc(function (PurchaseItem $item) {
                    $date = $item->purchase?->arrival_date
                        ?? $item->purchase?->purchase_date
                        ?? $item->created_at;

                    return sprintf(
                        '%010d-%020d',
                        $date ? $date->getTimestamp() : 0,
                        (int) $item->id
                    );
                })
                ->values();

            $latest = $costing->latestInventoryCost((int) $material->id, $exchangeRate, true);
            $latestCostUsd = (float) ($latest['cost_usd'] ?? 0);
            $latestCostAfn = (float) ($latest['cost_afn'] ?? 0);
            $basisUnit = $latest['basis_unit']
                ?? ($material->is_roll_based ? 'kg' : ($material->unit ?: 'unit'));

            $batchDetails = [];
            $totalCostUsd = 0.0;
            $totalBasisQty = 0.0;

            foreach ($purchaseItems as $item) {
                $basisQty = $item->availableInventoryQuantity();
                $costUsd = $item->landedCostPerInventoryUnitUsd();

                $totalBasisQty += $basisQty;
                $totalCostUsd += $basisQty * $costUsd;

                $batchDetails[] = [
                    'id' => $item->id,
                    'batch_no' => $item->batch_no ?? 'N/A',
                    'purchase_no' => $item->purchase?->purchase_no ?? 'N/A',
                    'purchase_date' => $item->purchase?->arrival_date
                        ?? $item->purchase?->purchase_date
                        ?? $item->created_at,
                    'qty_available' => $basisQty,
                    'stock_qty_available' => (float) ($item->qty_available ?? 0),
                    'cost_basis_unit' => $item->inventoryCostBasisUnit(),
                    'cost_per_unit' => $costUsd,
                    'cost_per_unit_usd' => $costUsd,
                    'cost_per_unit_afn' => $costUsd * $exchangeRate,
                    'total_cost' => $basisQty * $costUsd,
                    'currency' => 'USD',
                    'purchase_currency' => $item->purchase?->currency?->code ?? 'USD',
                    'is_latest' => (int) $item->id === (int) ($latest['purchase_item_id'] ?? 0),
                ];
            }

            $weightedAvgCost = $totalBasisQty > 0
                ? $totalCostUsd / $totalBasisQty
                : 0.0;

            $purchaseCurrency = $latest['purchase_currency'] ?? 'AFN';
            $currencySymbol = Setting::first()?->currency ?? '؋';

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'unit' => $basisUnit,
                    'stock_unit' => $material->unit,
                    'cost_basis_unit' => $basisUnit,
                    'current_stock' => $material->is_roll_based
                        ? (float) $material->current_stock_kg
                        : (float) $material->current_stock,
                    'latest_cost' => $latestCostUsd,
                    'latest_cost_usd' => $latestCostUsd,
                    'latest_cost_afn' => $latestCostAfn,
                    'weighted_avg_cost' => $weightedAvgCost,
                    'purchase_currency' => $purchaseCurrency,
                    'purchase_currency_id' => $latest['purchase_currency_id'] ?? null,
                    'exchange_rate' => $exchangeRate,
                    'batch_breakdown' => [
                        'batches' => $batchDetails,
                        'total_qty' => $totalBasisQty,
                        'total_cost' => $totalCostUsd,
                        'weighted_avg' => $weightedAvgCost,
                        'latest_cost' => $latestCostUsd,
                        'latest_cost_usd' => $latestCostUsd,
                        'latest_cost_afn' => $latestCostAfn,
                        'purchase_currency' => $purchaseCurrency,
                        'cost_basis_unit' => $basisUnit,
                    ],
                    'suggested_cost' => $latestCostUsd > 0 ? $latestCostUsd : $weightedAvgCost,
                    'category' => $material->category?->name ?? 'Uncategorized',
                    'currency_symbol' => $currencySymbol,
                    'latest_batch_date' => $latest['purchase_date'] ?? null,
                    'latest_batch_no' => $latest['batch_no'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in getMaterialCost', [
                'material_id' => $materialId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching material cost: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created BOM in storage.
     */
    public function store(Request $request)
    {
        Log::info('BOM Store - Request Data', [
            'all' => $request->all(),
            'items_count' => count($request->items ?? []),
            'has_items' => $request->has('items'),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'description' => 'nullable|string',
            'work_percentage' => 'nullable|numeric|min:0',
            'profit_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.cost_per_unit_usd' => 'nullable|numeric|min:0',
            'items.*.cost_per_unit_afn' => 'nullable|numeric|min:0',
            'items.*.purchase_currency' => 'nullable|string|in:USD,AFN',
            'items.*.purchase_currency_id' => 'nullable|exists:currencies,id',
            'items.*.roll_weight' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.formula_type' => 'nullable|string|in:fixed,carton_3d,cut_roll,fixed_percentage,fixed_rate',
            'items.*.is_formula_based' => 'nullable|boolean',
            // 3D Carton fields
            'items.*.length_inch' => 'nullable|numeric|min:0',
            'items.*.width_inch' => 'nullable|numeric|min:0',
            'items.*.height_inch' => 'nullable|numeric|min:0',
            'items.*.reel_length_inch' => 'nullable|numeric|min:0',
            'items.*.reel_height_inch' => 'nullable|numeric|min:0',
            'items.*.paper_gsm' => 'nullable|integer|min:0',
            'items.*.layers' => 'nullable|integer|min:0',
            'items.*.division_factor' => 'nullable|numeric|min:0',
            // Cut/Roll fields
            'items.*.cut_length_inch' => 'nullable|numeric|min:0',
            'items.*.cut_width_inch' => 'nullable|numeric|min:0',
            'items.*.grh' => 'nullable|integer|min:0',
            'items.*.ply' => 'nullable|integer|min:0',
            'items.*.print' => 'nullable|numeric|min:0',
            // Common fields
            'items.*.per_gram_rate' => 'nullable|numeric|min:0',
            'items.*.multiplication_layer' => 'nullable|integer|min:0',
            'items.*.formula_constant' => 'nullable|numeric|min:0.00000001',
            'items.*.work_percentage' => 'nullable|numeric|min:0',
            'items.*.multiplication_method' => 'nullable|string|in:multiply,divide',
            // Fixed Percentage fields
            'items.*.base_material_id' => 'nullable|exists:products,id',
            'items.*.percentage_of_base' => 'nullable|numeric|min:0',
            // Fixed Rate fields
            'items.*.rate_per_unit' => 'nullable|numeric|min:0',
            'items.*.rate_base_units' => 'nullable|integer|min:1',
        ]);

        Log::info('BOM Store - Validation Passed');

        try {
            DB::beginTransaction();
            Log::info('BOM Store - Transaction Started');

            $exchangeRate = $request->exchange_rate ?? $this->getDefaultExchangeRate();
            Log::info('BOM Store - Exchange Rate', ['exchange_rate' => $exchangeRate]);

            // ─── CREATE BOM ───
            $bomData = [
                'name' => $request->name,
                'product_id' => $request->product_id,
                'version' => '1.0',
                'status' => $request->status ?? 'draft',
                'description' => $request->description,
                'profit_margin_percentage' => $request->profit_margin_percentage ?? 0,
                'work_percentage' => $request->work_percentage ?? 40,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_updated_at' => now(),
                'is_active' => $request->has('is_active'),
                'created_by' => Auth::id(),
                'total_material_cost_usd' => 0,
            ];

            Log::info('BOM Store - Creating BOM', $bomData);

            $bom = BOM::create($bomData);

            if (!$bom) {
                throw new \Exception('Failed to create BOM record');
            }

            Log::info('BOM Store - BOM Created', ['bom_id' => $bom->id, 'bom_code' => $bom->code]);

            $totalMaterialCostUsd = 0;
            $itemCount = 0;

            // ─── CREATE BOM ITEMS WITH ALL FORMULA FIELDS ───
            foreach ($request->items as $index => $itemData) {
                $itemCount++;
                Log::info("BOM Store - Processing Item #{$itemCount}", [
                    'index' => $index,
                    'material_id' => $itemData['material_id'] ?? null,
                    'quantity' => $itemData['quantity'] ?? null,
                    'formula_type' => $itemData['formula_type'] ?? 'fixed',
                ]);

                try {
                    $material = Product::find($itemData['material_id']);

                    if (!$material) {
                        throw new \Exception("Material not found for ID: {$itemData['material_id']}");
                    }

                    Log::info("BOM Store - Material Found", [
                        'material_id' => $material->id,
                        'material_name' => $material->name,
                        'unit' => $material->unit,
                    ]);

                    // ─── Handle currency-aware cost ───
                    $purchaseCurrency = $itemData['purchase_currency'] ?? 'AFN';
                    $purchaseCurrencyId = $itemData['purchase_currency_id'] ?? null;
                    $costPerUnitUsd = 0;
                    $costPerUnitAfn = 0;

                    Log::info("BOM Store - Currency Processing", [
                        'purchase_currency' => $purchaseCurrency,
                        'purchase_currency_id' => $purchaseCurrencyId,
                        'exchange_rate' => $exchangeRate,
                    ]);

                    if ($purchaseCurrency === 'USD') {
                        $costPerUnitUsd = $itemData['cost_per_unit_usd'] ?? ($material->weighted_avg_cost ?? 0);
                        $costPerUnitAfn = $costPerUnitUsd * $exchangeRate;
                    } else {
                        // AFN
                        $costPerUnitAfn = $itemData['cost_per_unit_afn'] ?? ($material->weighted_avg_cost ?? 0);
                        $costPerUnitUsd = $costPerUnitAfn / $exchangeRate;
                    }

                    Log::info("BOM Store - Costs Calculated", [
                        'cost_per_unit_usd' => $costPerUnitUsd,
                        'cost_per_unit_afn' => $costPerUnitAfn,
                    ]);

                    $quantity = floatval($itemData['quantity'] ?? 0);
                    $wastage = floatval($itemData['wastage_percentage'] ?? 5);
                    $quantityWithWastage = $quantity * (1 + ($wastage / 100));
                    $totalCostUsd = $quantityWithWastage * floatval($costPerUnitUsd);
                    $totalCostAfn = $quantityWithWastage * floatval($costPerUnitAfn);

                    Log::info("BOM Store - Item Costs", [
                        'quantity' => $quantity,
                        'wastage' => $wastage,
                        'quantity_with_wastage' => $quantityWithWastage,
                        'total_cost_usd' => $totalCostUsd,
                        'total_cost_afn' => $totalCostAfn,
                    ]);

                    $totalMaterialCostUsd += $totalCostUsd;

                    // ─── Check if formula-based ───
                    $isFormulaBased = isset($itemData['is_formula_based']) && $itemData['is_formula_based'];
                    $formulaType = $isFormulaBased ? ($itemData['formula_type'] ?? 'fixed') : 'fixed';

                    Log::info("BOM Store - Formula Check", [
                        'is_formula_based' => $isFormulaBased,
                        'formula_type' => $formulaType,
                    ]);

                    // ─── Calculate roll weight based on formula type ───
                    $rollWeight = $this->calculateRollWeight($itemData, $formulaType, $quantity);
                    Log::info("BOM Store - Roll Weight Calculated", ['roll_weight' => $rollWeight]);

                    // ─── Prepare formula data based on type ───
                    $formulaData = $isFormulaBased ? $this->prepareFormulaData($itemData, $formulaType) : null;
                    Log::info("BOM Store - Formula Data", ['formula_data' => $formulaData]);

                    // ─── CREATE BOM ITEM ───
                    $bomItemData = [
                        'bom_id' => $bom->id,
                        'material_id' => $itemData['material_id'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? ($material->unit ?? 'Unit'),
                        'wastage_percentage' => $wastage,
                        'cost_per_unit_usd' => $costPerUnitUsd,
                        'cost_per_unit_afn' => $costPerUnitAfn,
                        'total_cost_usd' => $totalCostUsd,
                        'total_cost_afn' => $totalCostAfn,
                        'purchase_currency' => $purchaseCurrency,
                        'purchase_currency_id' => $purchaseCurrencyId,
                        'roll_weight' => $rollWeight,
                        'notes' => $itemData['notes'] ?? null,
                        'sort_order' => $index,
                        // ─── Formula fields ───
                        'is_formula_based' => $isFormulaBased,
                        'formula_type' => $formulaType,
                        'formula_data' => $formulaData,
                        // ─── 3D Carton ───
                        'length_inch' => $itemData['length_inch'] ?? null,
                        'width_inch' => $itemData['width_inch'] ?? null,
                        'height_inch' => $itemData['height_inch'] ?? null,
                        'reel_length_inch' => $itemData['reel_length_inch'] ?? null,
                        'reel_height_inch' => $itemData['reel_height_inch'] ?? null,
                        'paper_gsm' => $itemData['paper_gsm'] ?? null,
                        'layers' => $itemData['layers'] ?? null,
                        'division_factor' => $itemData['division_factor'] ?? null,
                        // ─── Cut/Roll ───
                        'cut_length_inch' => $itemData['cut_length_inch'] ?? null,
                        'cut_width_inch' => $itemData['cut_width_inch'] ?? null,
                        'grh' => $itemData['grh'] ?? null,
                        'ply' => $itemData['ply'] ?? null,
                        'print' => $itemData['print'] ?? null,
                        // ─── Common ───
                        'per_gram_rate' => $itemData['per_gram_rate'] ?? null,
                        'multiplication_layer' => $itemData['multiplication_layer'] ?? null,
                        'formula_constant' => $itemData['formula_constant'] ?? null,
                        'work_percentage' => $itemData['work_percentage'] ?? ($request->work_percentage ?? 40),
                        'multiplication_method' => $itemData['multiplication_method'] ?? 'multiply',
                        // ─── Fixed Percentage ───
                        'base_material_id' => $itemData['base_material_id'] ?? null,
                        'percentage_of_base' => $itemData['percentage_of_base'] ?? null,
                        // ─── Fixed Rate ───
                        'rate_per_unit' => $itemData['rate_per_unit'] ?? null,
                        'rate_base_units' => $itemData['rate_base_units'] ?? 100,
                    ];

                    Log::info("BOM Store - Creating BOM Item", [
                        'bom_item_data' => array_merge($bomItemData, ['formula_data' => '...']), // Hide formula_data content for readability
                    ]);

                    $bomItem = BOMItem::create($bomItemData);

                    if (!$bomItem) {
                        throw new \Exception("Failed to create BOM item for material ID: {$itemData['material_id']}");
                    }

                    Log::info("BOM Store - BOM Item Created", [
                        'item_id' => $bomItem->id,
                        'material_id' => $bomItem->material_id,
                        'quantity' => $bomItem->quantity,
                    ]);

                    // ─── Log formula details ───
                    if ($isFormulaBased) {
                        Log::info('BOM Store - Formula Item Created', [
                            'item_id' => $bomItem->id,
                            'formula_type' => $formulaType,
                            'formula_data' => $formulaData,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error("BOM Store - Error Processing Item #{$itemCount}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'item_data' => $itemData,
                    ]);
                    throw $e;
                }
            }

            Log::info("BOM Store - All Items Processed", [
                'total_items' => $itemCount,
                'total_material_cost_usd' => $totalMaterialCostUsd,
            ]);

            // ─── UPDATE BOM TOTALS ───
            $bom->total_material_cost_usd = $totalMaterialCostUsd;
            $bom->unsetRelation('items');
            $bom->load('items');

            Log::info("BOM Store - Updating BOM Totals", [
                'bom_id' => $bom->id,
                'items_loaded' => $bom->items->count(),
                'total_material_cost_usd' => $totalMaterialCostUsd,
            ]);

            // Calculate totals
            $bom->calculateTotals();
            $bom->saveQuietly();

            Log::info("BOM Store - Totals Calculated", [
                'total_material_cost_usd' => $bom->total_material_cost_usd,
                'total_material_cost_afn' => $bom->total_material_cost_afn,
                'total_cost_afn' => $bom->total_cost_afn,
                'selling_price_afn' => $bom->selling_price_afn,
            ]);

            // Recalculate AFN values
            $bom->recalculateAfnValues();

            Log::info("BOM Store - AFN Values Recalculated");

            DB::commit();
            Log::info("BOM Store - Transaction Committed Successfully", [
                'bom_id' => $bom->id,
                'bom_code' => $bom->code,
                'items_count' => $bom->items->count(),
            ]);

            return redirect()->route('bom.index')
                ->with('success', 'BOM created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BOM Store - Transaction Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return back()->with('error', 'Failed to create BOM: ' . $e->getMessage())->withInput();
        }
    }
    /**
     * Calculate roll weight based on formula type
     */
    private function calculateRollWeight($itemData, $formulaType, $quantity)
    {
        $rollWeight = 0;

        if ($formulaType === 'carton_3d') {
            $length = floatval($itemData['length_inch'] ?? 0);
            $width = floatval($itemData['width_inch'] ?? 0);
            $height = floatval($itemData['height_inch'] ?? 0);
            $gsm = floatval($itemData['paper_gsm'] ?? 0);

            if ($length > 0 && $width > 0 && $height > 0 && $gsm > 0) {
                $reelLength = (($length + $width) * 2) + 4;
                $reelHeight = $width + $height + 1;
                $rollWeight = $quantity * ($reelLength * $reelHeight * $gsm) / 1000;
            }
        } elseif ($formulaType === 'cut_roll') {
            $cutLength = floatval($itemData['cut_length_inch'] ?? 0);
            $cutWidth = floatval($itemData['cut_width_inch'] ?? 0);
            $grh = floatval($itemData['grh'] ?? 0);

            if ($cutLength > 0 && $cutWidth > 0 && $grh > 0) {
                $rollWeight = $quantity * ($cutLength * $cutWidth * $grh) / 1000;
            }
        } else {
            $rollWeight = $quantity;
        }

        return $rollWeight;
    }

    /**
     * Prepare formula data based on formula type
     */
    private function prepareFormulaData($itemData, $formulaType)
    {
        $data = [];

        switch ($formulaType) {
            case 'carton_3d':
                $data = [
                    'length_inch' => $itemData['length_inch'] ?? null,
                    'width_inch' => $itemData['width_inch'] ?? null,
                    'height_inch' => $itemData['height_inch'] ?? null,
                    'reel_length_inch' => $itemData['reel_length_inch'] ?? null,
                    'reel_height_inch' => $itemData['reel_height_inch'] ?? null,
                    'paper_gsm' => $itemData['paper_gsm'] ?? null,
                    'layers' => $itemData['layers'] ?? null,
                    'division_factor' => $itemData['division_factor'] ?? null,
                    'per_gram_rate' => $itemData['per_gram_rate'] ?? null,
                    'multiplication_layer' => $itemData['multiplication_layer'] ?? null,
                    'formula_constant' => $itemData['formula_constant'] ?? null,
                    'work_percentage' => $itemData['work_percentage'] ?? null,
                ];
                break;

            case 'cut_roll':
                $data = [
                    'cut_length_inch' => $itemData['cut_length_inch'] ?? null,
                    'cut_width_inch' => $itemData['cut_width_inch'] ?? null,
                    'grh' => $itemData['grh'] ?? null,
                    'ply' => $itemData['ply'] ?? null,
                    'print' => $itemData['print'] ?? null,
                    'per_gram_rate' => $itemData['per_gram_rate'] ?? null,
                    'multiplication_layer' => $itemData['multiplication_layer'] ?? null,
                    'formula_constant' => $itemData['formula_constant'] ?? null,
                    'work_percentage' => $itemData['work_percentage'] ?? null,
                    'multiplication_method' => $itemData['multiplication_method'] ?? null,
                ];
                break;

            case 'fixed_percentage':
                $data = [
                    'base_material_id' => $itemData['base_material_id'] ?? null,
                    'percentage_of_base' => $itemData['percentage_of_base'] ?? null,
                ];
                break;

            case 'fixed_rate':
                $data = [
                    'rate_per_unit' => $itemData['rate_per_unit'] ?? null,
                    'rate_base_units' => $itemData['rate_base_units'] ?? null,
                ];
                break;

            default:
                $data = ['quantity' => $itemData['quantity'] ?? 0];
        }

        return $data;
    }

    /**
     * Get default exchange rate
     */
    private function getDefaultExchangeRate()
    {
        $afn = Currency::where('code', 'AFN')->first();
        $usd = Currency::where('code', 'USD')->first();

        if ($afn && $usd && $usd->exchange_rate > 0) {
            return $afn->exchange_rate / $usd->exchange_rate;
        }

        // Fallback to settings
        $setting = Setting::first();
        return $setting->default_exchange_rate ?? 85;
    }

    /**
     * Display the specified BOM.
     */
    public function show(BOM $bom)
    {
        $bom->load(['product', 'items.material', 'createdBy', 'updatedBy']);

        // Calculate totals
        $bom->calculateTotals();
        $bom->saveQuietly();
        $bom->refresh()->load(['product', 'items.material', 'createdBy', 'updatedBy']);

        return view('admin.bom.show', compact('bom'));
    }

    /**
     * Show the form for editing the specified BOM.
     */
    public function edit(BOM $bom)
    {
        $bom->load('items');

        $products = Product::finishedGoods()
            ->where('is_active', true)
            ->get();

        // ─── Get materials with purchase currency information ───
        $materials = Product::rawMaterials()
            ->where('is_active', true)
            ->with(['category'])
            ->get()
            ->map(function($material) {
                $latestPurchaseItem = $material->purchaseItems()
                    ->whereHas('purchase', function($q) {
                        $q->where('status', 'arrived');
                    })
                    ->with(['purchase.currency'])
                    ->latest('id')
                    ->first();

                if ($latestPurchaseItem && $latestPurchaseItem->purchase) {
                    $material->purchase_currency = $latestPurchaseItem->purchase->currency->code ?? 'AFN';
                    $material->purchase_currency_id = $latestPurchaseItem->purchase->currency_id ?? null;
                } else {
                    $material->purchase_currency = 'AFN';
                    $material->purchase_currency_id = null;
                }

                return $material;
            });

        $categories = Category::where('is_active', true)->get();

        $defaultCurrency = Currency::where('is_default', 1)->first();
        $afnCurrency = Currency::where('code', 'AFN')->first();
        $usdCurrency = Currency::where('code', 'USD')->first();

        $currencySymbol = $defaultCurrency ? $defaultCurrency->symbol : '؋';
        $defaultExchangeRate = 1;

        if ($afnCurrency && $usdCurrency && $usdCurrency->exchange_rate > 0) {
            $defaultExchangeRate = $afnCurrency->exchange_rate / $usdCurrency->exchange_rate;
        } else {
            $defaultExchangeRate = 85;
        }

        return view('admin.bom.edit', compact(
            'bom',
            'products',
            'materials',
            'categories',
            'currencySymbol',
            'defaultExchangeRate',
            'defaultCurrency',
            'afnCurrency',
            'usdCurrency'
        ));
    }

    /**
     * Update the specified BOM in storage.
     */
    public function update(Request $request, BOM $bom)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:products,id',
            'description' => 'nullable|string',
            'work_percentage' => 'nullable|numeric|min:0',
            'profit_margin_percentage' => 'nullable|numeric|min:0|max:100',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:bom_items,id',
            'items.*.material_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.cost_per_unit_usd' => 'nullable|numeric|min:0',
            'items.*.cost_per_unit_afn' => 'nullable|numeric|min:0',
            'items.*.purchase_currency' => 'nullable|string|in:USD,AFN',
            'items.*.purchase_currency_id' => 'nullable|exists:currencies,id',
            'items.*.roll_weight' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string',
            'items.*.formula_type' => 'nullable|string|in:fixed,carton_3d,cut_roll,fixed_percentage,fixed_rate',
            'items.*.is_formula_based' => 'nullable|boolean',
            'items.*.length_inch' => 'nullable|numeric|min:0',
            'items.*.width_inch' => 'nullable|numeric|min:0',
            'items.*.height_inch' => 'nullable|numeric|min:0',
            'items.*.reel_length_inch' => 'nullable|numeric|min:0',
            'items.*.reel_height_inch' => 'nullable|numeric|min:0',
            'items.*.paper_gsm' => 'nullable|integer|min:0',
            'items.*.layers' => 'nullable|integer|min:0',
            'items.*.division_factor' => 'nullable|numeric|min:0',
            'items.*.cut_length_inch' => 'nullable|numeric|min:0',
            'items.*.cut_width_inch' => 'nullable|numeric|min:0',
            'items.*.grh' => 'nullable|integer|min:0',
            'items.*.ply' => 'nullable|integer|min:0',
            'items.*.print' => 'nullable|numeric|min:0',
            'items.*.per_gram_rate' => 'nullable|numeric|min:0',
            'items.*.multiplication_layer' => 'nullable|integer|min:0',
            'items.*.formula_constant' => 'nullable|numeric|min:0.00000001',
            'items.*.work_percentage' => 'nullable|numeric|min:0',
            'items.*.multiplication_method' => 'nullable|string|in:multiply,divide',
            'items.*.base_material_id' => 'nullable|exists:products,id',
            'items.*.percentage_of_base' => 'nullable|numeric|min:0',
            'items.*.rate_per_unit' => 'nullable|numeric|min:0',
            'items.*.rate_base_units' => 'nullable|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $exchangeRate = $request->exchange_rate ?? $bom->exchange_rate ?? $this->getDefaultExchangeRate();

            $bom->update([
                'name' => $request->name,
                'product_id' => $request->product_id,
                'description' => $request->description,
                'profit_margin_percentage' => $request->profit_margin_percentage ?? 0,
                'work_percentage' => $request->work_percentage ?? 40,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_updated_at' => now(),
                'is_active' => $request->has('is_active'),
                'updated_by' => Auth::id()
            ]);

            $existingItemIds = $bom->items->pluck('id')->toArray();
            $updatedItemIds = [];
            $totalMaterialCostUsd = 0;

            foreach ($request->items as $index => $itemData) {
                $material = Product::find($itemData['material_id']);

                // ─── Handle currency-aware cost ───
                $purchaseCurrency = $itemData['purchase_currency'] ?? 'AFN';
                $purchaseCurrencyId = $itemData['purchase_currency_id'] ?? null;
                $costPerUnitUsd = 0;
                $costPerUnitAfn = 0;

                if ($purchaseCurrency === 'USD') {
                    $costPerUnitUsd = $itemData['cost_per_unit_usd'] ?? ($material->weighted_avg_cost ?? 0);
                    $costPerUnitAfn = $costPerUnitUsd * $exchangeRate;
                } else {
                    $costPerUnitAfn = $itemData['cost_per_unit_afn'] ?? ($material->weighted_avg_cost ?? 0);
                    $costPerUnitUsd = $costPerUnitAfn / $exchangeRate;
                }

                $quantity = floatval($itemData['quantity']);
                $wastage = floatval($itemData['wastage_percentage'] ?? 5);
                $quantityWithWastage = $quantity * (1 + ($wastage / 100));
                $totalCostUsd = $quantityWithWastage * floatval($costPerUnitUsd);
                $totalCostAfn = $quantityWithWastage * floatval($costPerUnitAfn);

                $isFormulaBased = isset($itemData['is_formula_based']) && $itemData['is_formula_based'];
                $formulaType = $isFormulaBased ? ($itemData['formula_type'] ?? 'fixed') : 'fixed';
                $rollWeight = $this->calculateRollWeight($itemData, $formulaType, $quantity);

                if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
                    // Update existing item
                    $item = BOMItem::find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'material_id' => $itemData['material_id'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'] ?? null,
                            'wastage_percentage' => $wastage,
                            'cost_per_unit_usd' => $costPerUnitUsd,
                            'cost_per_unit_afn' => $costPerUnitAfn,
                            'total_cost_usd' => $totalCostUsd,
                            'total_cost_afn' => $totalCostAfn,
                            'purchase_currency' => $purchaseCurrency,
                            'purchase_currency_id' => $purchaseCurrencyId,
                            'roll_weight' => $rollWeight,
                            'notes' => $itemData['notes'] ?? null,
                            'sort_order' => $index,
                            'is_formula_based' => $isFormulaBased,
                            'formula_type' => $formulaType,
                            // 3D Carton
                            'length_inch' => $itemData['length_inch'] ?? null,
                            'width_inch' => $itemData['width_inch'] ?? null,
                            'height_inch' => $itemData['height_inch'] ?? null,
                            'reel_length_inch' => $itemData['reel_length_inch'] ?? null,
                            'reel_height_inch' => $itemData['reel_height_inch'] ?? null,
                            'paper_gsm' => $itemData['paper_gsm'] ?? null,
                            'layers' => $itemData['layers'] ?? null,
                            'division_factor' => $itemData['division_factor'] ?? null,
                            // Cut/Roll
                            'cut_length_inch' => $itemData['cut_length_inch'] ?? null,
                            'cut_width_inch' => $itemData['cut_width_inch'] ?? null,
                            'grh' => $itemData['grh'] ?? null,
                            'ply' => $itemData['ply'] ?? null,
                            'print' => $itemData['print'] ?? null,
                            // Common
                            'per_gram_rate' => $itemData['per_gram_rate'] ?? null,
                            'multiplication_layer' => $itemData['multiplication_layer'] ?? null,
                            'formula_constant' => $itemData['formula_constant'] ?? null,
                            'work_percentage' => $itemData['work_percentage'] ?? ($request->work_percentage ?? 40),
                            'multiplication_method' => $itemData['multiplication_method'] ?? 'multiply',
                            // Fixed Percentage
                            'base_material_id' => $itemData['base_material_id'] ?? null,
                            'percentage_of_base' => $itemData['percentage_of_base'] ?? null,
                            // Fixed Rate
                            'rate_per_unit' => $itemData['rate_per_unit'] ?? null,
                            'rate_base_units' => $itemData['rate_base_units'] ?? 100,
                        ]);
                        $updatedItemIds[] = $item->id;
                    }
                } else {
                    // Create new item
                    $item = BOMItem::create([
                        'bom_id' => $bom->id,
                        'material_id' => $itemData['material_id'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'] ?? null,
                        'wastage_percentage' => $wastage,
                        'cost_per_unit_usd' => $costPerUnitUsd,
                        'cost_per_unit_afn' => $costPerUnitAfn,
                        'total_cost_usd' => $totalCostUsd,
                        'total_cost_afn' => $totalCostAfn,
                        'purchase_currency' => $purchaseCurrency,
                        'purchase_currency_id' => $purchaseCurrencyId,
                        'roll_weight' => $rollWeight,
                        'notes' => $itemData['notes'] ?? null,
                        'sort_order' => $index,
                        'is_formula_based' => $isFormulaBased,
                        'formula_type' => $formulaType,
                        'length_inch' => $itemData['length_inch'] ?? null,
                        'width_inch' => $itemData['width_inch'] ?? null,
                        'height_inch' => $itemData['height_inch'] ?? null,
                        'reel_length_inch' => $itemData['reel_length_inch'] ?? null,
                        'reel_height_inch' => $itemData['reel_height_inch'] ?? null,
                        'paper_gsm' => $itemData['paper_gsm'] ?? null,
                        'layers' => $itemData['layers'] ?? null,
                        'division_factor' => $itemData['division_factor'] ?? null,
                        'cut_length_inch' => $itemData['cut_length_inch'] ?? null,
                        'cut_width_inch' => $itemData['cut_width_inch'] ?? null,
                        'grh' => $itemData['grh'] ?? null,
                        'ply' => $itemData['ply'] ?? null,
                        'print' => $itemData['print'] ?? null,
                        'per_gram_rate' => $itemData['per_gram_rate'] ?? null,
                        'multiplication_layer' => $itemData['multiplication_layer'] ?? null,
                        'formula_constant' => $itemData['formula_constant'] ?? null,
                        'work_percentage' => $itemData['work_percentage'] ?? ($request->work_percentage ?? 40),
                        'multiplication_method' => $itemData['multiplication_method'] ?? 'multiply',
                        'base_material_id' => $itemData['base_material_id'] ?? null,
                        'percentage_of_base' => $itemData['percentage_of_base'] ?? null,
                        'rate_per_unit' => $itemData['rate_per_unit'] ?? null,
                        'rate_base_units' => $itemData['rate_base_units'] ?? 100,
                    ]);
                    $updatedItemIds[] = $item->id;
                }

                $totalMaterialCostUsd += $totalCostUsd;
            }

            // Delete removed items
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (!empty($itemsToDelete)) {
                BOMItem::whereIn('id', $itemsToDelete)->delete();
            }

            // ─── UPDATE BOM TOTALS ───
            $bom->total_material_cost_usd = $totalMaterialCostUsd;
            $bom->unsetRelation('items');
            $bom->load('items');

            $bom->calculateTotals();
            $bom->saveQuietly();
            $bom->recalculateAfnValues();

            DB::commit();

            return redirect()->route('bom.index')
                ->with('success', 'BOM updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BOM update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to update BOM: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified BOM from storage.
     */
    public function destroy(BOM $bom)
    {
        try {
            // Check if BOM is used in production orders
            if ($bom->productionOrders()->exists()) {
                return back()->with('error', 'Cannot delete BOM as it is used in production orders.');
            }

            $bom->delete();
            return redirect()->route('bom.index')
                ->with('success', 'BOM deleted successfully!');
        } catch (\Exception $e) {
            Log::error('BOM deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to delete BOM: ' . $e->getMessage());
        }
    }

    /**
     * Clone an existing BOM.
     */
    public function clone(BOM $bom)
    {
        try {
            DB::beginTransaction();

            $newBom = $bom->replicate();
            $newBom->name = $bom->name . ' (Copy)';
            $newBom->code = 'BOM-' . strtoupper(uniqid());
            $newBom->status = 'draft';
            $newBom->is_active = false;
            $newBom->created_by = Auth::id();
            $newBom->save();

            foreach ($bom->items as $item) {
                $newItem = $item->replicate();
                $newItem->bom_id = $newBom->id;
                $newItem->save();
            }

            DB::commit();

            return redirect()->route('bom.edit', $newBom)
                ->with('success', 'BOM cloned successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BOM clone failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to clone BOM: ' . $e->getMessage());
        }
    }

    /**
     * Activate/Deactivate BOM.
     */
    public function toggleStatus(BOM $bom)
    {
        try {
            if ($bom->status === 'draft' && $bom->items()->count() === 0) {
                return back()->with('error', 'Cannot activate BOM with no items.');
            }

            $bom->update([
                'status' => $bom->status === 'active' ? 'archived' : 'active',
                'is_active' => $bom->status === 'active' ? false : true,
                'updated_by' => Auth::id()
            ]);

            $status = $bom->status === 'active' ? 'activated' : 'archived';
            return back()->with('success', "BOM {$status} successfully!");

        } catch (\Exception $e) {
            Log::error('BOM toggle status failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to toggle BOM status: ' . $e->getMessage());
        }
    }

    /**
     * Get BOMs for a specific product (for AJAX)
     */
    public function getByProduct(Request $request)
    {
        $productId = $request->product_id;

        $boms = BOM::where('product_id', $productId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->get()
            ->map(function($bom) {
                return [
                    'id' => $bom->id,
                    'code' => $bom->code,
                    'name' => $bom->name,
                    'version' => $bom->version,
                ];
            });

        return response()->json([
            'success' => true,
            'boms' => $boms,
            'bom_id' => $boms->first()->id ?? null,
        ]);
    }

    /**
     * Get BOM by product ID (for sale order)
     */
    public function getBOMByProduct(Request $request)
    {
        $productId = $request->product_id;

        $bom = BOM::where('product_id', $productId)
            ->where('status', 'active')
            ->where('is_active', true)
            ->with('items.material')
            ->first();

        if (!$bom) {
            return response()->json([
                'success' => false,
                'message' => 'No active BOM found for this product'
            ]);
        }

        $materials = [];
        foreach ($bom->items as $item) {
            $materials[] = [
                'material_name' => $item->material->name ?? 'Unknown',
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'wastage_percentage' => $item->wastage_percentage,
            ];
        }

        return response()->json([
            'success' => true,
            'materials' => $materials,
            'work_percentage' => $bom->work_percentage,
            'profit_margin_percentage' => $bom->profit_margin_percentage,
        ]);
    }
}
