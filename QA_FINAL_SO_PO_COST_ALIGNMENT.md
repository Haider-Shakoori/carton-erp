# QA FINAL — SO/PO Cost Alignment Report

**Date:** 2026-09-02
**Result:** PASS

---

## ROOT CAUSE

Estimated/production planning code was adding the client's commercial 40% profit
markup (work_percentage) to the estimated production cost as if it were a genuine
labour expense. This inflated the estimated production cost and caused a mismatch
between the estimated and actual figures.

Two independent code paths both applied the40%:
1. `SaleProfitService::calculate()` — added `$plannedMaterialUsd * 40%` as
   `plannedWorkUsd`, then `estimatedCost = material + work`.
2. `ProductionOrderController::show()` — added `$itemMaterialCost * 40%` as
   `$totalLaborCostUsd` when no explicit BOM labour/overhead was configured.

**Fix:** Removed the40% from estimated production cost in both paths. The40%
is now reported separately as Standard Work / Profit (commercial profit).

---

## COMMERCIAL (Unchanged)

| Item | Value |
|------|-------|
| Commercial paper basis | AFN 4,645.16 |
| Standard Work / Profit 40% | AFN 1,858.06 |
| Excel Net Rate | AFN 6,503.23 |
| Sale Revenue (grand_total) | AFN 6,503.20 |

---

## SALE ORDER PRODUCTION ESTIMATE (Sale #74)

| Item | Value |
|------|-------|
| Material qty (incl. 5% wastage) | 60.9676 kg |
| Material cost (AFN) | 3,822.72 |
| Estimated Labour | 0 |
| Estimated Overhead | 0 |
| Estimated Other | 0 |
| **Estimated Production Cost** | **AFN 3,822.72** |

---

## PRODUCTION ORDER PLANNED (PO #78)

| Item | Value |
|------|-------|
| Planned material qty | 60.9676 kg |
| Planned material cost (AFN) | 3,822.72 |
| Planned Labour | 0 |
| Planned Overhead | 0 |
| Planned Other | 0 |
| **Planned Production Cost** | **AFN 3,822.72** |

---

## SO ↔ PO

| Field | SO | PO | Diff |
|-------|-----|-----|------|
| Material qty (kg) | 60.9676 | 60.9676 | 0 |
| Material cost (AFN) | 3,822.72 | 3,822.72 | 0 |
| Production cost (AFN) | 3,822.72 | 3,822.72 | 0 |

**SALE ORDER = PRODUCTION ORDER: PASS**

---

## ACTUAL

| Item | Value |
|------|-------|
| FIFO qty | 60.9676 kg |
| Actual Material (AFN) | 3,822.67 |
| Actual Labour | 0 |
| Actual Overhead | 0 |
| Actual Other | 0 |
| **Actual Production Cost** | **AFN 3,822.67** |

---

## ESTIMATE ↔ ACTUAL

| Item | Value |
|------|-------|
| Estimated Production | AFN 3,822.72 |
| Actual Production | AFN 3,822.67 |
| **Variance** | **AFN -0.05** (rounding only) |

---

## PROFIT

| Item | Value |
|------|-------|
| Commercial Paper Basis | AFN 4,645.16 |
| Standard Work / Profit (Paper × 40%) | AFN 1,858.06 |
| Standard Markup | 40% |
| Standard Margin (on Excel Net revenue) | 28.57% |
| Excel Net Selling Rate | AFN 6,503.23 |
| Actual Production Cost | AFN 3,822.67 |
| Actual Realized Profit | AFN 2,680.53 |
| Actual Realized Margin | 41.22% |
| Favorable Material Cost Difference (Paper − FIFO) | AFN 822.49 |

---

## QA #73 CLEANUP

| Item | Value |
|------|-------|
| Safe reversal available | YES |
| Removed/reversed | YES |
| PO #77 status | cancelled |
| Sale #73 is_produced | false |
| Consumption records deleted | 1 (PMC #28) |
| Transactions deleted | 1 (id=87) |
| Inventory before | 4,878.0648 kg |
| Inventory restored | +60.9676 kg |
| **Final remaining** | **4,939.0324 kg** |

---

## TRACEABILITY

| Step | ID/Reference |
|------|-------------|
| Sale | #74 (QADEMO2-20260902-U27N) |
| SaleItem | #74 (product=163, qty=1000, bom=21) |
| BOM | #21 (QA Demo Carton BOM, carton_3d, work% 40) |
| BOM Item | material=162, qty=1 roll, wastage=5% |
| ProductionOrder | #78 (PROD-2026-CSGL6NC4, completed) |
| ProductionOrderMaterial | #92 (60.9676 kg @ $0.95) |
| Purchase Batch | #40 (QA-DEMO-S2BF, $0.95/kg landed) |
| FIFO Consumption | PMC #29 (60.9676 kg, $57.9192) |
| Actual Production Cost | AFN 3,822.67 |

SO and PO both reference the SAME ProductionOrderMaterial snapshot (#92)
and the SAME FIFO consumption record (#29).

---

## FILES CHANGED

| File | Change |
|------|--------|
| `app/Services/SaleProfitService.php` | Removed40% work cost from estimated production cost. `plannedWorkUsd` no longer accumulated in BOM loop or snapshot fallback. Standard Work / Profit now = Commercial Paper Basis × work% (Revenue × work%/(100+work%)), from client Excel — NOT actual material × 40%. |
| `app/Http/Controllers/Admin/ProductionOrderController.php` | Removed40% work cost from planned labour when no explicit BOM labour/overhead configured. Labour stays at 0. |
| `resources/views/admin/sales/show.blade.php` | Relabelled estimated work as "Standard Work / Profit" (not included in estimated production cost). Updated actual cost display. |
| `resources/views/admin/production-orders/show.blade.php` | Relabelled "Standard Work Cost" to "Standard Work / Profit — Not a production cost". |

---

## REGRESSION GATE

| Gate | Result |
|------|--------|
| Client Excel formula | PASS |
| per_gram_rate AFN/kg | PASS |
| 40% commercial profit once | PASS |
| Excel Net Rate final selling price | PASS |
| No extra 25% | PASS |
| 40% excluded from estimated production cost | PASS |
| 40% excluded from actual production cost | PASS |
| SO estimated quantity = PO planned quantity | PASS |
| SO estimated material cost = PO planned material cost | PASS |
| SO estimated production cost = PO planned production cost | PASS |
| Roll → kg | PASS |
| 5% wastage once | PASS |
| Landed cost/kg | PASS |
| Snapshot kg | PASS |
| FIFO kg | PASS |
| Estimated vs actual (variance -0.05 AFN rounding) | PASS |
| Standard profit (paper basis × 40% = AFN 1,858.06) | PASS |
| Standard margin (28.57%) | PASS |
| Actual realized profit (AFN 2,680.53) | PASS |
| Actual realized margin (41.22%) | PASS |
| Traceability (SO→BOM→PO→POM→Batch→FIFO) | PASS |
| QA #73 safely reversed | PASS |
| Final QA inventory (4,939.0324 kg) | PASS |

---

## FINAL VERDICT

| Criterion | Result |
|-----------|--------|
| Client Excel workflow | PASS |
| Commercial pricing | PASS |
| Estimated production (material only) | PASS |
| Production planned (material only) | PASS |
| Actual production (FIFO material) | PASS |
| SO = PO | PASS |
| Estimated ≈ Actual controlled QA | PASS (variance -0.05 AFN) |
| Profit (actual realized AFN 2,680.53) | PASS |
| Units/FIFO | PASS |
| Demo data clean | PASS |
| **SAFE FOR CLIENT PRESENTATION** | **YES** |
| **COSTING LOGIC READY TO FREEZE** | **YES** |
