# QA_START_PRODUCTION_FK_COST_FIX

## SCRATCHPAD

### Investigation
- PO 80 (`PROD-2026-6A9B019DA68D4`) is **standalone** — no row in `sales.production_order_id` references it. `SO-202609-0006` (sale 79) is linked to **PO 81** (`PROD-2026-6A9B0245451E5`), not 80.
- `production_orders` has **no `sale_id` column**; the relationship lives on `sales.production_order_id`. So `production_orders.sale_id` semantics map to that relation and report NULL/standalone for PO 80.
- Root of `sale_id = 0`: `ProductionOrderController::startProduction` STEP 4 did `$saleId = $sale ? $sale->id : 0;`. For the standalone order `$sale` is `null` → `$saleId = 0`. It flowed through `StockDeductionService::deductMaterials()` into `ProductionMaterialConsumption::create(['sale_id' => 0, ...])`. The `pmc_sale_fk` FK (`sale_id` nullable, `ON DELETE SET NULL`) rejects `0` because `sales.id = 0` does not exist → `SQLSTATE[23000] 1452`.
- Log lines `"Deducting materials from stock"`, `sale_id: 0` and `StockDeductionService::deductMaterials` `sale_id: 0` confirmed the bad value was produced upstream, not at the INSERT.
- Batch #13 (`PO-0011`, kg_per_roll 2.0, landed 0.458050 USD/kg) is USD-denominated: `rate = 1`, `purchase.exchange_rate = 1`, `currency.usd`. Because the old AFN fields multiplied by `batch->rate`, the failed INSERT stored `cost_per_unit_afn = 0.45805` (= raw USD). The authoritative display conversion is the linked Sale rate, else the default currency rate (AFN @ 66) — the failure log's AFN columns were therefore wrong.

### Fixes applied (PHP syntax re-verified; full suite green)
1. **NULL sale_id semantics (source fix, not INSERT patch)**
   - `ProductionOrderController::startProduction`: `$saleId = $sale ? $sale->id : 0;` → `$saleId = $sale?->id;` → real Sale ID when sale-linked, otherwise `NULL`, never `0`.
   - `StockDeductionService::deductMaterials` + `deductMaterialWithinTransaction`: `int $saleId` → `?int $saleId` (PHP 8 typed-param would TypeError otherwise); PMC `sale_id` gets `$saleId`.
   - `ProductionCostRecorder::consumeBatch`: `int $saleId` → `?int $saleId` (same NULL semantics, consistent with phase 3).
2. **AFN costing (dynamic, no hard-coded 66)**
   - New `StockDeductionService::resolveConsumptionExchangeRate(?int $saleId, PurchaseItem $batch)`: linked `Sale.exchange_rate` > 0 → that; else default currency (`is_default=true`) rate; else legacy `resolveExchangeRate($batch)` → 1. Used for `cost_per_unit_afn` / `total_cost_afn` / `wastage_cost_afn`. USD landed cost per kg (FIFO) untouched.
3. **Transaction atomicity**: verified as already correct — `startProduction` wraps everything in `DB::beginTransaction()...commit/catch→rollBack`; `StockDeductionService::deductMaterials` uses a nested transaction (savepoint). The failed attempt rolled ALL writes back.
4. **Verification (real HTTP path)**
   - Before retry: batch #13 `qty_kg_available = 23998.9684` → the failed deduction DID roll back (log's 23997.9368 was an intermediate in-transaction value).
   - Retried Start Production for PO 80 through the real `/admin/production-orders/80/start` form POST → success.

## FINAL RESPONSE

RESULT:
FIXED

ROOT CAUSE OF sale_id=0:
`ProductionOrderController::startProduction` computed `$saleId = $sale ? $sale->id : 0;` — for a standalone production order (no `sales.production_order_id` entry) this produced integer `0`, which was passed through `StockDeductionService::deductMaterials()` into `production_material_consumptions.sale_id`. The FK `pmc_sale_fk` (nullable, `ON DELETE SET NULL`) has no `sales.id = 0`, so the INSERT failed with `SQLSTATE[23000] 1452`. Fixed at the source: real Sale ID or `NULL` — never `0`.

ORDER #80 DB sale_id BEFORE:
No `sale_id` column exists on `production_orders`; relationship is stored on `sales.production_order_id`. No sales row references `production_order_id = 80` → standalone (effectively NULL).

ACTUAL LINKED SALE:
None for PO 80. Sale `SO-202609-0006` (id 79, exchange_rate 66, currency AFN) is linked to production order 81 (`PROD-2026-6A9B0245451E5`), not 80. PO 80 `PROD-2026-6A9B019DA68D4` is a standalone QA order.

ORDER #80 sale_id AFTER:
Unchanged — still standalone (no sale link). NULL semantics applied to the PMC record instead of `0`.

PMC sale_id:
NULL (verified: `production_material_consumptions.id = 42`, `sale_id = NULL`, `sale_item_id = NULL`)

STANDALONE NULL SEMANTICS:
PASS

FAILED STOCK DEDUCTION ROLLED BACK:
YES

BATCH #13 qty_kg_available BEFORE FAILED ATTEMPT:
23998.9684

BATCH #13 qty_kg_available AFTER FAILED ATTEMPT:
23998.9684 (transient log value during the failed attempt: 23997.9368; rollback restored it)

TRANSACTION ATOMIC:
YES

EXCHANGE RATE USED:
66 (derived dynamically: linked Sale.exchange_rate when sale-linked, else default currency AFN rate — not hard-coded)

PMC cost_per_unit_usd:
0.458050

PMC cost_per_unit_afn:
30.231300

PMC total_cost_usd:
0.4725

PMC total_cost_afn:
31.1866

POM ROW COUNT:
2

POM #94:
product_id=1, required=0.8597 kg, unit=kg, cost_per_unit=0.4581, total_cost=0.3938 (unchanged)

POM #95:
product_id=1, required=0.1719 kg, unit=kg, cost_per_unit=0.4581, total_cost=0.0788 (unchanged)

POM SNAPSHOT UNCHANGED:
YES

SUCCESSFUL FIFO QUANTITY:
1.0316 kg (aggregated from the two planned rows, same raw material)

SUCCESSFUL STOCK DEDUCTION:
One FIFO consumption from batch #13; `qty_kg_available` 23998.9684 → 23997.9368 (−1.0316 kg), `qty_kg_used` 1.0316 → 2.0632

STATUS BEFORE:
pending

STATUS AFTER:
in_progress

SECOND START BLOCKED:
YES (flash: "Failed to start production: Production order must be in \"pending\" status. Current status: in_progress"; no duplicate PMC, batch #13 unchanged at 23997.9368)

FOREIGN KEY REMOVED:
NO

FIFO FORMULA CHANGED:
NO

BOM FORMULA CHANGED:
NO

COMMERCIAL FORMULA CHANGED:
NO

40% ADDED TO PRODUCTION COST:
NO

REGRESSION:
PASS — 52 tests / 463 assertions green, incl. 4 new `ProductionOrderStartProductionFkCostRegressionTest` cases (standalone NULL sale_id + AFN 66 conversion, sale-linked real sale_id, double-start idempotency, legacy BOM-template fallback)

SAFE TO CONTINUE PRODUCTION:
YES

---
Note: pre-existing PMC rows written before this fix (e.g. PO 81 id=36) keep their historical values (`afn == usd`); only new consumption records use the corrected authoritative-rate conversion.