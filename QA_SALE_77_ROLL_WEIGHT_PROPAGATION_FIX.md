# QA: SALE #77 ROLL WEIGHT PROPAGATION FIX

**Status:** FIXED (safe estimation fallback added)
**Result for Sale #77:** STILL UNAVAILABLE — historically correct, not guessable.

---

## ROOT CAUSE

Sale #77 kept reporting **"Estimated cost unavailable — roll weight required"** even after
`Product.default_kg_per_roll` was entered because `SaleProfitService` only ever resolved the
KG basis from **inventory batches**, never from the product default:

- `rollWeightBasisMissing()` (SaleProfitService.php) checked ONLY for an arrived `PurchaseItem`
  with `unit='roll'` AND `kg_per_roll > 0`.
- `resolveRollCostPerKg()` (SaleProfitService.php) likewise only returned `landedCostPerKg()` for
  such a batch, otherwise returned `0.0`.
- Neither method consulted `Product.default_kg_per_roll` at all.

The product default is a **forward-looking default for future purchases** — it was never meant to
be retroactive. Because no historical Kraft Paper batch carries a KG snapshot, the estimator had no
valid KG basis and (correctly) refused to compute a monetary estimate.

**The estimate for Sale #77 must NOT be resolved by guessing.** All historical Kraft Paper batches
have `unit=NULL`, `kg_per_roll=NULL`, `total_weight_kg=NULL`, `landed_cost_per_kg=NULL` and
ambiguous `qty` values, so inventing weights would be unsafe.

## PHASE 1 — PRODUCT FIELD

| Material | ID | Name | Unit | default_kg_per_roll (DB) | Fillable | Cast |
|---|---|---|---|---|---|---|
| Material 1 row 1 | 1 | Kraft Paper 120 GSM | roll | **100.0000** | YES | decimal:4 |
| Material 1 row 2 | 1 | Kraft Paper 120 GSM | roll | **100.0000** | YES | decimal:4 |

The update **persisted correctly** (verified raw + attribute). Product field: **PASS**.

## PHASE 2 — SALE #77 MATERIAL MAPPING

Sale #77 = single item (id 78, product 95 "120ml Syrup Box", BOM 1, qty 10, total 616.80 AFN).
Manual BOM snapshot has **two rows, BOTH mapping to material_id = 1 (Kraft Paper 120 GSM)** — the
exact product that was edited. The sale reads the same edited product. Mapping: **PASS**.

## PHASE 3 — HISTORICAL PURCHASE BATCHES (product 1)

| Batch | PO | Status | qty | unit | kg_per_roll | total_weight_kg | available | landed_cost_per_kg | landedCostPerKg() | usd_total | expense |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 2 | PO-008 | arrived | 10 | NULL | NULL | NULL | 0 (consumed) | NULL | 0 | 100.00 | 0 |
| 13 | PO-0011 | arrived | 12000 | NULL | NULL | NULL | 11622.79 | NULL | 0 | 9600.00 | 1393.03 |
| 15 | PO-0014 | arrived | 10000 | NULL | NULL | NULL | 10000 | NULL | 0 | 100000.00 | 240.00 |
| 17 | PO-0017 | arrived | 2 | NULL | NULL | NULL | 2 | NULL | 0 | 10.00 | 0 |

**All have `unit=NULL`** (never stored as `roll`), so `qty` is ambiguous — values like 12000/10000
do not look like physical roll counts. The service intentionally refuses to cost them. Historical
batch weight: **MISSING** (legacy, unmeasurable).

## PHASE 4 — "ROLL WEIGHT REQUIRED" CONDITION

- File: `app/Services/SaleProfitService.php`
- Methods: `rollWeightBasisMissing(int $materialId)` / `resolveRollCostPerKg(int $materialId, float $bomCostUsd)`
- Condition: product is `unit=roll` AND no arrived batch exists with `unit=roll` AND `kg_per_roll>0`
  → returns `true` / yields `0.0`; the `roll_weight_missing` flag sets `estimated_cost_unavailable`.

For each Sale #77 row: material 1 Kraft Paper; **all batches `kg_per_roll=NULL` → invalid**; product
default present but never consulted → invalid. So both rows flagged.

## PHASE 5/6 — FALLBACK POLICY (implemented, safe)

Implemented a **guarded estimation-only fallback** to `Product.default_kg_per_roll`:

1. Prefer a valid batch `kg_per_roll` / `landedCostPerKg()` (unchanged).
2. Only if NO such batch, look for an explicit **`unit='roll'`** arrived batch lacking a weight AND
   with a positive product `default_kg_per_roll`. Then (ESTIMATION only):
   - est. total kg = batch roll qty × product default kg/roll
   - est. landed USD/kg = (batch usd_total + expense) / est. total kg
3. Actual FIFO costing still **requires a real batch kg_per_roll snapshot** — the fallback never
   materializes batch metadata and never feeds FIFO consumption.

**No historical backfill was run**: batches are `unit=NULL`/ambiguous, and some are consumed; a
backfill would be unsafe. This complies with "do NOT invent weights for historical batches."

## PHASE 7 — BOTH MANUAL BOM ROWS

| Row | Product | default kg/roll | batch kg/roll | rate source | estimate | cost basis |
|---|---|---|---|---|---|---|
| row 1 | Kraft Paper 120 GSM | 100 | NULL | no unit='roll' batch | NO | unavailable |
| row 2 | Kraft Paper 120 GSM | 100 | NULL | no unit='roll' batch | NO | unavailable |

Because neither row has an explicit `unit='roll'` historical batch, Sale #77 **must remain
unavailable** (no safe basis). The fallback only activates for explicit roll batches.

## PHASE 8 — REBUILD ESTIMATED COST

Cannot rebuild for Sale #77: no trustworthy KG basis on historical batches. When a future purchase
records a roll item (`unit=roll`) with roll qty, the fallback will derive a valid estimate. Sales
that already carry an explicit `unit='roll'` batch WITHOUT a weight now resolve automatically.

## PHASE 9 — UI BEHAVIOR

Sale #77 continues to show **"Estimated cost unavailable — roll weight required"** (no `0.00`, no
false dollar figure). Material requirement (physical) is displayed from the frozen formula. No
database table names exposed. When an estimate becomes available it replaces the warning.

## PHASE 10 — REGRESSION (all verified)

| Check | Result |
|---|---|
| Sale #77 commercial (paper 426.29 / work 170.51 / print 20 / net 616.80) | Unchanged |
| Sale #77 Standard Work / Profit | Unchanged |
| Manual BOM snapshot | Unchanged |
| Sale #77 physical kg (row1 8.59708 + row2 1.99452 ≈ 10.5916 kg) | Unchanged |
| FINAL-DEMO #76 (3822.72 / 22680.51 / 22680.56 / 1858.06) | PASS |
| Frozen QA #73 (3822.72 / 4306.31 / 1858.06) | PASS |
| Accounting mutation | None |
| FIFO historical mutation | None |

---

## FINAL RESPONSE

**RESULT:** FIXED (safe estimation fallback added; Sale #77 estimate remains unavailable by design)

**SALE:** 77

**MATERIAL 1:** Kraft Paper 120 GSM (id 1) — both rows
**PRODUCT KG/ROLL:** 100.0000
**BATCH KG/ROLL:** NULL (all 4 arrived batches unit=NULL/kg NULL)
**COST BASIS:** UNAVAILABLE (no explicit `unit='roll'` historical batch)

**MATERIAL 2:** Kraft Paper 120 GSM (id 1) — same product, second BOM row
**PRODUCT KG/ROLL:** 100.0000
**BATCH KG/ROLL:** NULL
**COST BASIS:** UNAVAILABLE

**ROOT CAUSE:** `SaleProfitService` resolved KG only from inventory batches and never consulted
`Product.default_kg_per_roll`; no historical Kraft batch has `unit='roll'`/`kg_per_roll`, so no safe
KG basis existed and the estimate was (correctly) blocked.

**FIX:** Added a **guarded estimation-only fallback** in `SaleProfitService` (`resolveRollCostPerKg`
and `rollWeightBasisMissing`) that uses `Product.default_kg_per_roll` only for an explicit
`unit='roll'` arrived batch lacking a weight. Actual FIFO still requires a real batch snapshot.

**HISTORICAL BATCH MUTATED:** NO
**ACCOUNTING CHANGED:** NO
**FIFO HISTORY CHANGED:** NO

**ESTIMATED PRODUCTION COST:** Unavailable — roll weight required (Sale #77)
**MATERIAL REQUIREMENT KG:** ≈ 10.5916 kg (row1 8.59708 + row2 1.99452), unchanged
**ROLL WEIGHT WARNING:** STILL REQUIRED for Sale #77 (no explicit roll batch exists)

**FINAL-DEMO REGRESSION:** PASS
**FROZEN QA REGRESSION:** PASS
**SALE #77 SAFE TO CONFIRM:** YES (commercial quotation intact; estimate honestly unavailable until a
`unit='roll'` batch with weight exists)
