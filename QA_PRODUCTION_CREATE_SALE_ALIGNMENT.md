# QA — Production Order Create now aligns with Sale Order Costing (SO-202609-0006)

Date: 2026-09-04
Scope: `Production Order -> Create` preview + persisted quantities/cost for a **sale-linked** production order.
Goal: The Production Create page must show the **same authoritative physical material costing** the linked Sale uses — no forced/special-cased UI values, no hard-coded numbers. Sale and Production share ONE underlying calculation path.

---

## 1. Result (summary)

| Metric | Sale (authoritative) | Production Create — OLD (wrong) | Production Create — NEW |
|---|---|---|---|
| Physical req row0 | 0.8597 kg (layers=5) | 1.0500 **roll** | 0.8597 **kg** |
| Physical req row1 | 0.1719 kg (layers=1) | 1.0500 **roll** | 0.1719 **kg** |
| Est. material cost | 31.19 AFN (0.4726 USD) | 65.14 AFN (roll prices) | 31.19 AFN (0.4726 USD) |
| Est. production cost | 31.19 AFN | 91.20 AFN (incl. 40% "work") | **31.19 AFN** |
| Expected profit | 28.58 AFN | −31.43 AFN (loss) | **28.58 AFN** |
| 40% Std Work/Profit added to production cost | ❌ no | ❌ yes (wrong) | ✅ no (commercial only) |
| Manual BOM snapshot used | ✅ | ❌ | ✅ |
| Persisted unit / qty | kg | roll / 1.05 | kg / 0.8597 & 0.1719 |

**MATCH: YES.** Preview == Persisted == Sale estimated cost == 31.19 AFN.

---

## 2. Frozen commercial baseline (Formula UNCHANGED — regression proof)

The commercial quotation formula was NOT touched. Verified against live `SaleProfitService::calculate()` for the frozen QA sale:

| Sale #77 (SO-202609-0000) | Frozen value | Live value | Match |
|---|---|---|---|
| Commercial Paper basis | 426.29 | 426.29 | ✅ |
| Commercial Print | 20.00 | 20.00 | ✅ |
| Net Rate (= grand total) | 616.80 | 616.80 | ✅ |
| Std Work / Profit (40%) | 170.51 | 170.51 | ✅ |

FINAL-DEMO Sale #76: estimated cost 3822.72 AFN, Std Work 1858.06 AFN — unchanged. ✅
FIFO consumption logic — unchanged. ✅
Accounting records — unchanged (no production order was created in testing; store() blocked on shortage). ✅

---

## 3. Before — the wrong path (root cause)

Production Create used two different, non-authoritative sources:

1. **AJAX live preview** hit `route('bom.calculate')` → `BOMController::calculate()`, which:
   - computed requirement as `bom_item.quantity × production_qty × 1.05` in **rolls** (0.78/0.16 roll costs),
   - priced at the template BOM roll cost, **ignoring the sale item's manual BOM snapshot**,
   - **added the 40% work/profit onto the production cost** (labelled "Work Amount").
   Result for SO-202609-0006: req `1.05 roll` + `1.05 roll`, 54.05 + 11.09 = **65.14 AFN**, work 26.06, estimate **91.20 AFN**, loss 31.43. This is what the user saw.

2. **Persistence** (`ProductionOrderController::store()`) used `checkMaterialAvailability()`:
   - for roll paper it fell back to raw **roll** quantities and zero/weighted BOM cost,
   - the roll-kg cost query filtered `purchase_items.qty_kg_available > 0`, excluding the valid arrived batch → unreliably resolved cost basis.
   So persisted `required_quantity`/`unit` would not match the Sale (`roll/1.05` vs `kg/0.8597`).

### Breakdown of the old wrong values (user-reported, reproduced)

```
Req row0: 1.0500 roll   × cost  → 54.05 AFN
Req row1: 1.0500 roll   × cost  → 11.09 AFN
Total material:                    65.14 AFN
Work (40%):                        26.06 AFN  ← WRONG: added to production cost
Est. production cost:              91.20 AFN
Expected profit (sale 59.77):     −31.43 AFN  (loss)
```

---

## 4. After — the aligned path (one shared source of truth)

Sale-linked Production Create now reuses the **same authoritative methods** the Sale uses:

- **Preview endpoint** `POST /admin/production-orders/materials` → `ProductionOrderController::getProductionMaterials()` → `SaleProfitService::productionMaterialRequirements($sale, $qty)`.
- **Persistence** `store()` uses the same method when `sale_id` is present (falls back to the (fixed) `checkMaterialAvailability` for standalone orders).
- Requirement uses the **sale item's manual BOM snapshot** (if present) → correct `layers=5` for row 0.
- Roll paper is costed on a **kg basis** at the **landed USD/kg** of the earliest arrived purchase batch (`kg_per_roll > 0`), matching the Sale's cost resolution.
- The 40% Std Work/Profit is **NOT** added to the production cost estimate — it is displayed as a **commercial** element (like the Sale).

### New calculated values (physical)

```
KG cost basis: 0.45805 USD/kg (landed, PI batch, exchange 66) = 30.2313 AFN/kg
Req row0: base 0.8188 + wastage 0.0409 = 0.8597 kg × 30.2313 = 25.9920 AFN
Req row1: base 0.1638 + wastage 0.0082 = 0.1719 kg × 30.2313 = 5.1991 AFN
Est. material cost:  0.4726 USD = 31.1911 AFN
Est. production cost: 31.1911 AFN  (material only; no explicit labor/overhead; NO 40%)
Expected profit:      59.77 − 31.19 = 28.58 AFN  (profit)
```

### Persisted values (matching the preview/sale)

`production_order_materials`: `product_id=1, required_quantity=0.8597, unit='kg', cost_per_unit=0.4580, total_cost=0.39` and `required_quantity=0.1719, unit='kg', total_cost=0.08`.

Confirmed:
- Store simulation: **Preview == Persisted == Sale (31.19 AFN)** — verified programmatically.
- Schema fit: `required_quantity decimal(15,4)`, `cost_per_unit decimal(15,4)`, `total_cost decimal(15,2)` — all values fit.
- Real HTTP store test for sale 79 returned to Create with the shortage error (0 stock_kg) and **created no record and mutated nothing**.

---

## 5. Changes applied (file references)

| File | Change |
|---|---|
| `app/Services/SaleProfitService.php` | Added `productionMaterialRequirements(Sale, qty)` — reuses authoritative `physicalCostRows()` (manual snapshot-aware) + `resolveRollCostPerKg()`/landed USD/kg. Returns per-row kg requirements with wastage, cost, availability, shortage, and `roll_weight_missing`. |
| `app/Http/Controllers/Admin/ProductionOrderController.php` | (a) New `getProductionMaterials()` AJAX endpoint returning requirements + summary (material-only cost, no 40%). (b) `store()` now uses `SaleProfitService::productionMaterialRequirements()` when `sale_id` given, else the fixed `checkMaterialAvailability()`. (c) `checkMaterialAvailability()` roll cost basis now uses ANY arrived roll batch with `kg_per_roll>0` (removed `qty_kg_available>0` filter) → matches the Sale's roll-kg resolution. (d) Phase-13 guard: blocks creation with "valid roll/KG cost basis required" when `roll_weight_basis_missing`. |
| `routes/admin.php` | Added `POST production-orders/materials` (route `production-orders.materials`). |
| `resources/views/admin/production-orders/create.blade.php` | AJAX preview now calls `production-orders.materials` with `sale_id` + `quantity`; shows kg quantities and material-only estimated cost; 40% relabelled "Standard Work / Profit (40%) — Commercial" and excluded from the production cost; shows "Unavailable — valid roll/KG cost basis required" when no kg basis; expected profit = sale total − material-only cost. |

`php -l` clean on all edited files; all admin pages still 200 after login.

**Test suite now green:** the pre-existing suite failure (SQLite `boms.formula_type` migration error) was fixed in `database/migrations/2026_08_04_111525_update_boms_table.php` by dropping the `boms_formula_type_index` index before `dropColumn('formula_type')` in `up()` (and recreating it in `down()`). SQLite refuses to drop an indexed column; MySQL did not. Final schema unchanged. Full suite: **47 passed (410 assertions)**.

---

## 6. Discrepancy note (user-reported sale values 1.25 / 58.52)

The user-reported Sale estimate of **1.25 AFN production cost** and **58.52 AFN realized profit** does **not** match the current authoritative Sale computation for SO-202609-0006, which yields **estimated_cost_afn = 31.19** and **estimated_profit_afn = 28.58** (verified directly on the Sale show page and via `SaleProfitService::calculate()`).

Both pairs sum to the same grand total (59.77), so the 1.25/58.52 split is internally consistent but reflects an earlier state of the data (e.g., before the landed kg cost basis / manual snapshot existed). We did **not** try to reproduce 1.25 in code: the shared, authoritative path was proven to produce 31.19 on both Sale and Production sides now.

If you believe 1.25/58.52 is what the Sale should still show, the underlying issue is in `SaleProfitService`'s cost resolution for that earlier data state — say STOP and we can investigate that separately.

---

## FINAL RESPONSE

- RESULT: ✅ FIXED — SO-202609-0006 Production Create now shows and persists the same physical costing as its linked Sale (material-only estimated cost; no 40% in production cost).
- SALE: SO-202609-0006 (#79), qty 1, grand total 59.77 AFN, exchange rate 66.
- SALE REVENUE: 59.77 AFN.
- SALE STANDARD WORK-PROFIT: 16.51 AFN (40% commercial, NOT a production cost).
- SALE EST. PROD COST: 31.19 AFN (0.4726 USD) — verified live (not 1.25; see discrepancy note).
- OLD REQ row0: QTY 1.0500 / roll.
- OLD REQ row1: QTY 1.0500 / roll.
- PHYSICAL REQ row0: QTY 0.8597 kg (base 0.8188 + wastage 0.0409).
- PHYSICAL REQ row1: QTY 0.1719 kg (base 0.1638 + wastage 0.0082).
- KG COST BASIS: YES — 0.45805 USD/kg landed (PI batch, kg_per_roll>0), = 30.2313 AFN/kg.
- PROD EST MAT COST: 31.19 AFN.
- PROD EST PROD COST: 31.19 AFN (material only).
- MATCH Y-N: YES — Preview == Persisted == Sale estimate == 31.19 AFN.
- 40% INCLUDED NO: ✅ (labelled commercial, like the Sale).
- EXPECTED PROFIT: 28.58 AFN.
- MANUAL SNAPSHOT USED: ✅ YES (layers=5 row0 / layers=1 row1, wastage 5%).
- PERSISTED UNIT+QTY: kg — 0.8597 and 0.1719 (matches Sale snapshot; store currently blocks on shortage because roll stock_kg = 0).
- FIFO: UNCHANGED.
- ACCOUNTING: UNCHANGED (no records touched in testing; store() blocked).
- COMMERCIAL FORMULA CHANGE NO: confirmed — frozen Sale #77 paper 426.29, print 20.00, net 616.80, std work 170.51 all intact.
- FINAL-DEMO REGRESSION: PASS — Sale #76 estimated cost 3822.72 AFN, std work 1858.06 AFN unchanged.
- FROZEN QA REGRESSION: PASS — Sale #77 all commercial values unchanged.
- SAFE TO CREATE: Only after a roll purchase batch provides KG stock — current SO-202609-0006 correctly blocked by shortage (no roll/KG stock available).
- STOP: ⛔ Do NOT ship the alternative (1.25/no-kg-basis) behavior. If the 1.25 sale figure matters, investigate the earlier-data-state inside `SaleProfitService` separately.