<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BOM;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\BOMCostingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClientCartonOpeningStockSeeder extends Seeder
{
    public const PURCHASE_NO = 'PO-OPENING-001';

    /**
     * Bootstrap reference purchase values used only so a fresh installation has
     * usable stock and non-zero BOM costing. These are system seed values, not
     * client-supplied commercial prices; operators can replace them with actual
     * supplier purchases at any time.
     */
    private const STOCK = [
        'Test Liner' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 400.00,
        ],
        'Fluting' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 425.00,
        ],
        'Kraft Liner' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 460.00,
        ],
        'Semi Kraft' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 440.00,
        ],
        'White Liner' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 500.00,
        ],
        'Box Board' => [
            'qty' => 20, 'unit' => 'roll', 'kg_per_roll' => 500, 'unit_price_usd' => 525.00,
        ],
        'Seligate (Glue)' => [
            'qty' => 1000, 'unit' => 'kg', 'unit_price_usd' => 1.30,
        ],
        'Corn Flour' => [
            'qty' => 2000, 'unit' => 'kg', 'unit_price_usd' => 0.55,
        ],
        'Borax' => [
            'qty' => 500, 'unit' => 'kg', 'unit_price_usd' => 1.65,
        ],
        'Caustic Soda' => [
            'qty' => 500, 'unit' => 'kg', 'unit_price_usd' => 1.75,
        ],
        'Lamination Plastic' => [
            'qty' => 1000, 'unit' => 'kg', 'unit_price_usd' => 2.25,
        ],
    ];

    public function run(): void
    {
        $usd = Currency::query()->where('code', 'USD')->first();

        if (! $usd) {
            throw new RuntimeException('USD currency must exist before opening stock is seeded.');
        }

        $supplier = Account::firstOrCreate(
            ['code' => 'SUP-OPENING-001'],
            [
                'name' => 'Opening Stock Supplier',
                'account_type' => Account::TYPE_SUPPLIER,
                'is_active' => true,
                'notes' => 'System-created supplier for bootstrap raw-material opening stock.',
            ]
        );

        if ((int) ($supplier->account_sub_category_id ?? 0) !== 2) {
            $supplier->account_sub_category_id = 2;
            $supplier->saveQuietly();
        }

        $purchase = Purchase::firstOrCreate(
            ['purchase_no' => self::PURCHASE_NO],
            [
                'supplier_id' => $supplier->id,
                'currency_id' => $usd->id,
                'exchange_rate' => 1,
                'purchase_date' => now()->toDateString(),
                'arrival_date' => now()->toDateString(),
                'status' => 'arrived',
                'notes' => 'Bootstrap opening stock for the exact client-approved raw materials. Seed reference prices only; replace with actual supplier invoices for live commercial costing.',
            ]
        );

        foreach (self::STOCK as $materialName => $definition) {
            $material = Product::query()
                ->where('name', $materialName)
                ->where('type', Product::TYPE_RAW_MATERIAL)
                ->first();

            if (! $material) {
                throw new RuntimeException("Opening stock material [{$materialName}] was not found.");
            }

            if (PurchaseItem::query()
                ->where('purchase_id', $purchase->id)
                ->where('product_id', $material->id)
                ->exists()) {
                continue;
            }

            $qty = (float) $definition['qty'];
            $unitPriceUsd = (float) $definition['unit_price_usd'];
            $totalUsd = $qty * $unitPriceUsd;
            $unit = (string) $definition['unit'];
            $kgPerRoll = $unit === 'roll'
                ? (float) ($definition['kg_per_roll'] ?? 0)
                : null;

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $material->id,
                'purchase_currency_id' => $usd->id,
                'qty' => $qty,
                'unit' => $unit,
                'kg_per_roll' => $kgPerRoll,
                'total_weight_kg' => $kgPerRoll ? $qty * $kgPerRoll : null,
                'unit_price' => $unitPriceUsd,
                'total' => $totalUsd,
                'rate' => 1,
                'usd_unit_price' => $unitPriceUsd,
                'usd_total' => $totalUsd,
                'usd_expense' => 0,
                'usd_expense_per_item' => 0,
                'usd_total_cost' => $totalUsd,
                'usd_cost_per_item' => $unitPriceUsd,
                'cost_per_unit' => $unitPriceUsd,
                'total_cost' => $totalUsd,
                'batch_no' => 'OPENING-'.$material->id,
                'remarks' => 'Seeded opening stock; replace seed reference rate with real supplier purchase when available.',
            ]);
        }

        $purchase->refresh();
        $purchase->recalculateTotals();

        $costing = app(BOMCostingService::class);

        BOM::query()
            ->where('code', 'like', 'BOM-CLIENT-%')
            ->with('items.material')
            ->orderBy('id')
            ->each(function (BOM $bom) use ($costing): void {
                // REVIEW REQUIRED BOMs intentionally keep zero usage until the
                // missing source structure is manually completed.
                $isReviewRequired = str_starts_with(
                    (string) $bom->description,
                    '[REVIEW REQUIRED]'
                );

                if (! $isReviewRequired) {
                    foreach ($bom->items as $item) {
                        $baseUsage = $item->calculateStockRequirement(1, false);

                        $item->updateQuietly([
                            'quantity' => $baseUsage,
                            'unit' => 'kg',
                        ]);
                    }
                }

                $costing->refreshBomMaterialCosts($bom);
            });

        $this->command?->info(
            'Opening stock purchase '.self::PURCHASE_NO.' seeded and client BOM material usage/prices refreshed.'
        );
    }
}
