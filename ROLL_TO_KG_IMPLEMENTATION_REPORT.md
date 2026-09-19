# ROLL→KG INVENTORY CONVERSION — IMPLEMENTATION REPORT

**Verdict: PASS** — Roll-based paper purchases now convert to physical weight (kg) for inventory, FIFO consumption, and production costing. Direct-kg and all other units unchanged. Historical data untouched.

---

## ARCHITECTURE
Live `D:\Projects\Qadir` (Laravel 12, PHP 8.2). Inspected end-to-end before changing anything:
- `Purchase` model — header (currency, exchange_rate, status), `recalculateTotals()`, `distributeExpenses()` (proportional by USD line total).
- `PurchaseItem` model — the FIFO batch record. `qty` (decimal 12,2) is unitless; originally held rolls, direct kg, or piece/box/litre/meter/pack. `booted()` `saving` event recomputes `qty_available`. `qty_available` column is a generated column `qty - qty_sold + qty_returned - qty_wasted` (does NOT include qty_used; the event overrides it each save).
- `PurchaseItemController::store()/update()` — submits items, computes usd totals; `distributeExpenses()` allocates freight/expenses.
- `StockDeductionService` — the authoritative FIFO consumer used by `SaleController::startProductionFromSale()`. Orders batches by purchase_date, deducts from `qty_available`, resolves `usd_cost_per_item`, writes `ProductionMaterialConsumption`.
- `ProductionCostRecorder::consumeBatch()` — alternate costing path.
- `ProductionService` — `createProductionOrderFromSaleItem` builds the `production_order_materials` snapshot (kg `required_quantity` via `calculateStockKgPerUnit`); `consumeMaterial` legacy FIFO; `checkMaterialAvailability`.
- `BOMItem` — `calculateStockKgPerUnit()`/`calculateStockRequirement()` produce **kg** physical consumption; also the separate 3D-carton and syrup-box (`cut_roll`) formulas (untouched).
- `Product` — `current_stock` sums `qty_available`; supplies availability to `checkMaterialAvailability`.

**Core mismatch resolved:** BOM consumption is kg, but inventory was tracked only in unitless/roll quantities. Roll batches now carry a parallel kg ledger.

---

## SCHEMA
New migration `database/migrations/2026_09_02_000000_add_roll_kg_conversion_to_purchase_items.php` (applied, `--force`, production). All new columns nullable/default-0 so historical rows are unaffected:
- `unit` (string, nullable)                — purchase unit, e.g. `roll` / `kg` / `piece`
- `kg_per_roll` (decimal, nullable)        — physical weight of one roll; per-batch authoritative
- `total_weight_kg` (decimal, nullable)    — explicit total weight when given
- `qty_kg` (decimal)                       — total kg purchased
- `qty_kg_sold` / `qty_kg_used` / `qty_kg_wasted` (decimal) — kg operational tracking
- `qty_kg_available` (decimal)             — remaining kg
- `landed_cost_per_kg` (decimal)           — landed USD cost per kg

No existing table modified structurally beyond these additions; no columns dropped; existing rows keep NULL/0 default → **legacy roll/direct-kg data preserved exactly**.

---

## ROLL PURCHASE VERIFICATION (A)
Tinker-verified (10 rolls × 500 kg @ $100/roll + $250 freight, rate=1):
- `total_weight_kg` = **5000.0000**, `qty_kg` = **5000**, `qty_kg_available` = **5000**
- `landed_cost_per_kg` = **0.250000** → `landedCostPerKg()` = **0.25** ✓
- Derived: base $1000 + allocated $250 = $1250 / 5000 kg = $0.25/kg (matches target example exactly).
- Source of truth is the **purchase item/batch** (`kg_per_roll`/`total_weight_kg`); NO global "1 roll = 500 kg" default.
- Validation implemented: `qty > 0 AND (kg_per_roll > 0 OR total_weight_kg > 0)`; roll without conversion is rejected with HTTP 422 "Roll purchases require a conversion."

## FIFO VERIFICATION (B)
`StockDeductionService::deductMaterials` (the authoritative `startProductionFromSale` path):
- Consumed **100 kg** (planned 95, wastage 5): `total_cost_usd` = **$25.00** (100 × 0.25), `cost_per_unit_usd` = **0.25** (per kg), `unit` = **kg**.
- Remaining `qty_kg_available` = **4900 kg**, remaining `qty_available` = **9.8 rolls**. ✓
- FIFO ordering preserved (purchase_date ASC), locking preserved, consumption history written per batch.

`ProductionCostRecorder::consumeBatch` alternate path: same result (cost $25, remaining 4900/9.8 rolls).

## CARTON VERIFICATION (C)
BOM **BOM-JP1GPHMU** (150 ml) per finished carton (incl. 5% wastage), via `calculateStockRequirement(1, true)`:
- **0.371394 kg** → at $0.25/kg landed = **$0.092848**. ✓
- This is the kg requirement that will now consume kg inventory correctly (previously mis-costed at ~$10/roll FIFO).

## KG PURCHASE REGRESSION (D)
Direct-kg purchase (5000 kg @ $0.90/kg + $750, rate=1):
- `is_roll_batch` = **false**, `qty_kg_available` = **0** (kg ledger stays zero; not a roll conversion).
- Legacy landed $/kg = `usd_unit_price + usd_expense_per_item` = **0.90 + 0.15 = $1.05** ✓. Behavior unchanged.

## TRACEABILITY (E)
Production consumption records for a roll batch carry full lineage: `purchase_item_id`, `material_id`, `unit=kg`, `actual_quantity=100`, `cost_per_unit_usd=0.25`, `total_cost_usd=25.00`, `wastage_cost_usd=1.25` (5 kg × 0.25). ✓

---

## FILES CHANGED
- `database/migrations/2026_09_02_000000_add_roll_kg_conversion_to_purchase_items.php` (new)
- `app/Models/PurchaseItem.php` — fillable/casts + `isRollBatch()`, `totalKg()`, `availableKg()`, `landedCostPerKg()`; `booted()` recomputes kg ledger + landed $/kg on save.
- `app/Http/Controllers/Admin/PurchaseItemController.php` — validation + roll conversion in `store()` and `update()`.
- `app/Services/StockDeductionService.php` — kg-aware availability, FIFO consumption, and stock restore for roll batches.
- `app/Services/ProductionCostRecorder.php` — kg-aware consumeBatch.
- `app/Services/ProductionService.php` — kg-aware `consumeMaterial` + `calculateWeightedAverageCost`; roll-aware stock check in `checkMaterialAvailability`.
- `app/Models/Product.php` — `current_stock_kg`, `is_roll_based` accessors (no `$appends`, no broad change).

No views changed (per approved scope: backend fields + tinker verification only). No routes/API changed. No BOM/raw-material formulas changed.

---

## BUSINESS LOGIC (MUST REMAIN UNCHANGED)
- **FIFO ordering**: unchanged (purchase_date → id ASC).
- **5% wastage**: applied exactly once (base kg × 5% = wastage kg) via `calculateStockRequirement(includeWastage=true)`; confirmed per-carton 0.371394 kg incl. 5%.
- **40% work rule**: `SaleProfitService` work-allocation logic untouched.
- **Profit formula**: Revenue − Actual Production Cost; actual production cost now reads kg-correct `total_cost_usd` from consumptions → unchanged formula, correct inputs.
- **Separate 3D-carton and syrup-box (cut_roll) calculations**: untouched in `BOMItem`.
- **Decimal precision/rounding**: retained (`usd_cost_per_item` decimal:4; new kg fields decimal:4; landed decimal:6).

---

## NOTE RE: PRIOR DIAGNOSED BUG (report, not redesign)
The separate quantity-source bug from the diagnosis is **already fixed** in the current code and was NOT touched:
`SaleController::startProductionFromSale()` (lines ~2413–2442) uses the `production_order_materials` snapshot (`$snapshot->required_quantity` ≈ 0.371394 kg physical req) as the authoritative consumption quantity — NOT `bom_items.quantity × quantity`. This is correct behavior and preserved as-is.
The prior `-AFN 1,871.38` actual loss stemmed from FIFO costing roll stock at ~$10/roll against a kg BOM requirement — the exact gap this Roll→KG feature now closes (a kg-deducted roll batch costs at landed $0.25/kg instead of ~$10/roll).

---

## FINAL VERDICT: **PASS**
Roll purchases convert to kg for inventory, FIFO, and costing; direct-kg and non-roll units regress cleanly; landed $/kg matches the specification example; historical SO-202609-0005 / PROD-2026-S7RUBLIT / PO-008 data verified untouched (new columns NULL, row/material counts unchanged, no existing row flagged as `roll`). QA verification data was created and fully removed after testing.