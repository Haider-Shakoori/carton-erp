# QA — PERSISTENCE / SHOW DISCREPANCY FIX

## Scope
Reported: the Production Order **Create preview** shows the correct planned
material plan (0.8597 kg + 0.1719 kg = 31.19 AFN) but the **Show** page for the
created order displayed different quantities, units and costs (0.1719 roll /
0.1719 roll at $0.78 / $0.16 → total 10.67 AFN).

Linked sale: `SO-202609-0006` → Production Order `PROD-2026-6A9B0245451E5`
(PO 81). Production quantity: 1. Secondary orphan `PROD-2026-6A9B019DA68D4`
(PO 80) left untouched (per user).

## Phase 1 — Where the failure actually was
Raw `production_order_materials` for PO 81 (before any change):

| id | production_order_id | product_id | required_quantity | unit | cost_per_unit | total_cost | batch_id | consumed |
|----|--------------------|------------|-------------------|------|---------------|-----------|----------|----------|
| 96 | 81                 | 1          | 0.8597            | kg   | 0.4581        | 0.3938    | NULL     | 0        |
| 97 | 81                 | 1          | 0.1719            | kg   | 0.4581        | 0.0788    | NULL     | 0        |

`SaleProfitService::productionMaterialRequirements(sale 79, 1)` returned exactly
the same two rows (dict_key 0 → 0.8597 kg / 0.3938 USD / 25.99 AFN; dict_key 1 →
0.1719 kg / 0.0788 USD / 5.198 AFN).

**Conclusion: persistence (C) was already correct.** The corruption lived in the
Show layer: `ProductionOrderController::show()` (D) + the Blade render (E).
The `production_order_materials` table has **no `bom_item_id`/source-row key
column**, but `store()` never keys by `product_id` — it writes each element of
the authoritative requirements array as its own independent row, so same-product
rows survive untouched. No schema change was required.

## Phase 2–5 — Duplicate `product_id` handling and the "Row 1 became Row 2" bug
`store()` (sale-linked path) uses one `foreach ($materialRows as $r)` and
`$order->materials()->create([...])` — no `keyBy`/`groupBy`/`firstWhere('product_id')`
collision. The two rows are written independently.

The visible "ROW 1 == ROW 2 (0.1719)" collapse happened in
`show()`/`ProductionOrderController` lines 430–449: it rebuilt planned rows from
the **saved BOM template** (`$bomItem->calculateStockRequirement(qty, true)`,
`$bomItem->cost_per_unit_usd`, `$bomItem->unit`). The template `BOM-DIHIIIVJ`
has **both items at `layers = 1`** → both compute the same 0.1719 kg, with
`unit = 'roll'` and legacy `cost_per_unit_usd = 0.78 / 0.16`. This was NOT a
last-loop-value leak and NOT a persistence merge — it was the Show page
reconstructing from a different (template) source than the frozen manual
snapshot (layers 5 / 1).

## Phase 6–7 — Unit and cost basis
- Show now reads **`production_order_materials.unit`** (`kg`), never
  `Product.unit` / `BOMItem.unit` / `PurchaseItem.unit` (`roll`).
- Show now reads **`production_order_materials.cost_per_unit`** (landed USD/kg,
  0.4581) and `total_cost` (frozen USD), never legacy BOM USD/roll rates.

## Phase 8–10 — Show data sources
`show()` now builds `$materialDetails` and the planned totals from the frozen
`production_order_materials` snapshot (rows kept independent; no `firstWhere('product_id')`
matching against BOM items). Material Status, Material Breakdown and Cost
Summary all agree and all use the snapshot. Historical POs are not
recomputed from the current Product/BOM.

## Phase 11–13 — Cost summary
Planned Material Cost = Σ persisted material row costs. Estimated Production
Cost = Material Cost + explicit configured labour/overhead (none here) — the
commercial 40% Standard Work / Profit is reported only as commercial
information, never added to production cost. When actual FIFO consumption
exists the Cost Summary still switches to actual totals as before, leaving the
planned snapshot rows untouched (no overwrite of planned with actual).

## Phase 14–15 — Manual snapshot preserved / QA order
`sale_items.manual_bom_snapshot` for SO-202609-0006 preserves ROW1
(`multiplication_layer = 5`, wastage 5%, per_gram_rate 40, print 0) and ROW2
(`multiplication_layer = 1`, wastage 5%, per_gram_rate 52, print 2). The
authoritative store path uses it. PO 81 is the QA order produced by the real
flow; the render is verified below. No recreation was necessary (persistence
was correct all along; the Show fix applies immediately).

## Phase 16 — Focused regression
Added `tests/Feature/Production/ProductionOrderDuplicateMaterialRegressionTest.php`:
one production order, two manual-BOM rows, same `product_id`, different kg —
asserts row count = 2, both `unit = kg`, distinct required quantities
(0.8597 / 0.1719), landed cost 0.45805, row costs 0.3938 / 0.0788, and that the
Show HTML renders both rows (0.8597 / 0.1719 / $0.4581 / ؋25.99 / ؋5.20 /
؋31.19) and never the legacy collapse values ($0.7800, $0.1600, 8.85, 1.82).

## Phase 17 — Regression safety
Full suite: **48 passed / 431 assertions** (was 47 / 410). All Production Order
show pages render HTTP 200 (PO 62, 76, 78, 79, 80, 81). Completed orders keep
their actual-FIFO totals; pending PO 80/81 show the frozen planned snapshot.
No changes to: sale quotation, Excel commercial formula, manual BOM snapshot,
40% logic, physical kg formula, 5% wastage, roll/kg architecture, landed-cost
formula, FIFO, accounting, or permissions. No DB schema/migration changes in
this task.

## Files changed
- `app/Http/Controllers/Admin/ProductionOrderController.php` — `show()`:
  planned Material Breakdown + Cost Summary from the frozen
  `production_order_materials` snapshot; explicit labour/overhead only;
  per-unit normalization against `quantity_ordered`.
- `tests/Feature/Production/ProductionOrderDuplicateMaterialRegressionTest.php`
  — new focused Gauss regression (Phase 16).

---

# FINAL RESPONSE

RESULT:
**FIXED**

ROOT CAUSE:
`ProductionOrderController::show()` rebuilt the planned Material Breakdown and
Cost Summary from the CURRENT BOM template (`BOM-DIHIIIVJ`: both items
`layers=1`, `unit='roll'`, legacy `cost_per_unit_usd` 0.78 / 0.16) instead of the
frozen `production_order_materials` snapshot. Template rows both computed
0.1719 "roll" → the template collapse produced the identical 0.1719 quantity
for row 1 and row 2, at $0.78 / $0.16 → 8.85 / 1.82 AFN, total 10.67 AFN.
Persistence (`store()`) was already correct and never keyed rows by
`product_id`.

PRODUCTION ORDER:
`PROD-2026-6A9B0245451E5` (PO 81, linked to sale `SO-202609-0006`); orphan
`PROD-2026-6A9B019DA68D4` (PO 80) untouched.

DUPLICATE PRODUCT_ID COLLAPSE:
**NO** (DB rows were never merged; the template-based display collapsed)

COLLAPSING LOCATION:
`ProductionOrderController::show()` — Material Breakdown reconstruction from
BOM template (not in `store()` / persistence)

ROW IDENTITY BEFORE:
Two `production_order_materials` rows persisted correctly (ids 96/97 on PO 81,
94/95 on PO 80); Show display both showed 0.1719 "roll" from template layers=1

ROW IDENTITY AFTER:
Rows remain independent (id 96 → 0.8597 kg; id 97 → 0.1719 kg), each rendered
on Show with its own quantity, unit and cost

CREATE ROW 1:
quantity 0.8597, unit kg, USD/kg 0.45805, AFN 25.99

DATABASE ROW 1:
quantity 0.8597, unit kg, USD/kg 0.4581, AFN 25.9901 ($0.3938)

SHOW ROW 1:
quantity 0.8597, unit kg, USD/kg $0.4581, AFN ؋25.99

CREATE ROW 2:
quantity 0.1719, unit kg, USD/kg 0.45805, AFN 5.20

DATABASE ROW 2:
quantity 0.1719, unit kg, USD/kg 0.4581, AFN 5.1980 ($0.0788)

SHOW ROW 2:
quantity 0.1719, unit kg, USD/kg $0.4581, AFN ؋5.20

CREATE TOTAL:
31.19 AFN ($0.4726 USD)

DATABASE TOTAL:
31.19 AFN ($0.4726 USD)

SHOW TOTAL:
؋31.19 ($0.4726 USD, displayed as $0.47 at 2-decimal formatting — identical
underlying value, harmless display rounding)

CREATE == DATABASE:
**YES**

DATABASE == SHOW:
**YES**

SHOW USES PRODUCT.UNIT:
**NO** (uses persisted `production_order_materials.unit` = kg)

SHOW USES LEGACY BOM COST:
**NO** (previously YES — persisted landed USD/kg used now)

40% INCLUDED IN PRODUCTION COST:
**NO**

MANUAL SNAPSHOT PRESERVED:
**YES** (layers 5 / 1, wastage 5%, per_gram_rate 40 / 52, print 0 / 2)

DUPLICATE SAME-PRODUCT ROWS PRESERVED:
**YES** (2 rows, distinct quantities, both kg, neither overwrites the other)

FIFO CHANGED:
**NO**

ACCOUNTING CHANGED:
**NO**

COMMERCIAL FORMULA CHANGED:
**NO**

REGRESSION:
**PASS** (48 tests / 431 assertions; new same-product regression included; all
PO show pages 200)

SAFE TO CONTINUE PRODUCTION:
**YES**

STOP.