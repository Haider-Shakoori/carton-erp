# QA — Client 40% Work/Profit Workflow Alignment

**Date:** 2026-09-02
**Scope:** Align ERP to the client's authoritative Excel workflow where the **40% standard work IS the client's profit**, the **Excel Net Rate is the final default selling rate** (no extra % markup), and the standard 40% is **not** re-injected as a fabricated actual production expense.
**Method:** Code inspection, DB inspection, Tinker/service/controller route, `php -l`, `php artisan view:cache`. No browser automation, no full suite, no broad refactor. Historical records untouched. QA test data recreated only.

## VERDICT

```
RESULT:                      PASS
ROOT CAUSE:                  40% (profit_margin_percentage=25%) markup applied ON TOP of the Excel Net Rate in the
                             quotation sale path, AND the same 40% was re-injected as an "actual work" cost when no
                             real labour/overhead was recorded (semantic double-use of the client's profit).
EXTRA 25% REMOVED:           YES
40% COMMERCIAL APPLICATION:  Applied ONCE inside the Excel Net Rate (Paper Rate by Layers × 40%); redundant margin removed.
40% ACTUAL-COST APPLICATION BEFORE:  materialAfn × 40% injected as actual "labor" when no real labour/overhead recorded
                                     (Sale #73: 1,529.07 → actual cost 5,351.73).
40% ACTUAL-COST APPLICATION AFTER:   NOT counted as actual cost. Actual cost = genuine FIFO material + real labour/
                                     overhead/other only. Standard 40% reported separately as profit (actual cost 3,822.67).
EXCEL NET RATE:             AFN 6,503.23 / 1,000 cartons (6.503226 / carton)
SALE REVENUE:               AFN 6,503.23 (stored 6,503.20 at 4-dp UI precision); was 8,129.03 (×1.25)
STANDARD PROFIT:            AFN 1,858.06 (Excel 40% Work on commercial paper; on landed material = AFN 1,529.07)
ACTUAL PRODUCTION COST:     AFN 3,822.67 (FIFO material only; real labour/overhead/other = 0)
ACTUAL REALIZED PROFIT:     AFN 2,680.53
STANDARD MARKUP:            40%
STANDARD PROFIT MARGIN:     28.57%  (= 40/140, matching client example)
ACTUAL REALIZED MARGIN:     41.22%  (commercial 80 AFN/kg vs landed 62.70 AFN/kg gives extra realized profit)
FILES CHANGED:              app/Http/Controllers/Admin/SaleController.php
                            app/Services/SaleProfitService.php
                            app/Http/Controllers/Admin/ProductionOrderController.php
                            resources/views/admin/sales/show.blade.php
                            resources/lang/{en,ps,fa}/ui.php
PRODUCTION REGRESSION:      PASS — landed $0.95/kg, roll→kg 5,000, FIFO 5,000−2×60.9676=4,878.0648 kg, 5% wastage once,
                            snapshot 60.9676 kg all unchanged.
SAFE FOR CLIENT PRESENTATION: YES
```

---

## Stop-condition analysis

The code's prior comments treated the 40% as a "standard labour/overhead allowance" (`ProductionOrderController.show` and `SaleProfitService`), and applied `profit_margin_percentage=25%` after the Excel Net Rate. The client's authoritative Excel workflow explicitly states **40% = their profit** and the **Net Rate is the final selling basis**. No conflicting established business meaning overrides this — the fix was applied systemically (no QA-ID special-casing).

---

## Phase 1 — Proved current behaviour (exact code paths)

| Concept | File : line | Equation (as-was) |
|---|---|---|
| A. BOM commercial quotation (Net Rate only) | `BOMController::calculate` L139-170; `SaleController::addItemWithBOM` L1396-1405 | ReelLen=((L+W)×2)+4; ReelH=W+H+1; PaperRate=(ReelLen×ReelH×GSM×PerGramRate)/1,550,000; PaperByLayers=×Layer; Work=PaperByLayers×40%; **NetRate=Print+PaperByLayers+Work** (correct) |
| B. Sale unit price (duplicate 25%) | `show.blade.php` L3396 (`sellingPrice=finalUnit×(1+margin/100)`), `SaleController::addItemWithBOM` L1434 (`×(1+profitMarginPercent/100)`) | **NetRate × 1.25** → 6,503.23 × 1.25 = **8,129.03** |
| C. The 25% uplift | `profit_margin_percentage=25` on BOM 21, applied in the two B points | — |
| D. Estimated production work | `SaleProfitService` L62-63,82 | plannedMaterial × (work%/100) — left unchanged (out of scope) |
| E. Actual production work (fake) | `SaleProfitService` L128-139; `ProductionOrderController::show` L415-418 | when no real labour/overhead: actualWork = actualMaterial×40% → 1,529.07 |
| F. Exact profit | `SaleProfitService::calculate` (authoritative) | revenue − actualCost |

### Proof Sale #73: 6,503.23 × 1.25 = 8,129.03
BOM 21: `work_percentage=40`, `profit_margin_percentage=25`, `labor_cost_per_unit=NULL`, `overhead_cost_per_unit=NULL`. Stored `SaleItem#73` `unit_price=8.1290`, `total=8,129.03`. Exact Net Rate computation = 6.503226/carton; ×1.25 = 8.1290. Confirmed.

---

## Phase 2 — Client Excel is authoritative (preserved exactly)
Reel Length `=((L+W)×2)+4`, Reel Height `=W+H+1`, Division `=ReelLen×ReelH×GSM×PerGramRate`, Paper Rate `=Division/1,550,000`, Paper Rate By Layers `=Paper×Layer`, Standard Work/Profit `=PaperByLayers×40%`, Net Rate `=Print+PaperByLayers+Work/Profit`. **Net Rate is the final selling rate — no extra markup after it.** All verified against BOMItem 21.

## Phase 3 — 40% defined correctly
**Semantic double-use confirmed and corrected.** A) Standard commercial work/profit (Excel 40%) is part of the quotation/selling price — kept. B) Actual production costs = real FIFO material + real recorded labour + real recorded overhead + real other direct costs — the automatic 40% is **no longer** added as an actual expense when no real labour/overhead exists.

## Phase 4 — Two profit metrics now reported
1. **Standard / quotation profit** = PaperRateByLayers × work% = AFN 1,858.06; markup 40%; margin on revenue 28.57%.
2. **Actual realized profit** = revenue − genuine actual costs = AFN 2,680.53 (differs from standard because quote paper rate 80 AFN/kg ≠ landed 62.70 AFN/kg).

## Phase 5 — Extra sale markup removed (systemic)
- Default sale price = **Excel Net Rate** (server fallback `addItemWithBOM` and frontend `recalculateLiveEstimate`).
- Manual price override (`quoted_unit_price>0`) still respected; no QA-ID special-casing.

## Phase 6 — profit_margin_percentage
No longer adds a markup after Net Rate for Excel/BOM quotation. Column kept (used by other modules / BOM detail display); smallest semantic correction applied at the price-computation sites.

## Phase 7 — Labels
`work_percentage_*` labels renamed to **"Standard Work / Profit (%)"** across `en`,`ps`,`fa` language files (with the same helper phrasing; no schema change). No 40% displayed as a pure expense.

## Phase 8 — Rechecked QA demo numbers
```
Commercial paper   = 58.0644 kg × 80 AFN/kg = AFN 4,645.16
Standard 40% work  = AFN 1,858.06
Excel Net Rate     = AFN 6,503.23   (final default sale; was 8,129.03)
Actual FIFO material = 60.9676 kg × $0.95 × 66 = AFN 3,822.67  (no 40% added as expense)
Actual production cost = AFN 3,822.67
```

## Phase 9 — Expected profit reporting
```
COMMERCIAL PAPER BASIS:      AFN 4,645.16
STANDARD WORK/PROFIT 40%:    AFN 1,858.06   (on landed material basis: AFN 1,529.07)
EXCEL NET SELLING RATE:      AFN 6,503.23
SALE REVENUE:                AFN 6,503.23 (stored 6,503.20, 4-dp)
ACTUAL FIFO MATERIAL:        AFN 3,822.67
ACTUAL LABOUR:               AFN 0.00
ACTUAL OVERHEAD:             AFN 0.00
ACTUAL OTHER:                AFN 0.00
ACTUAL PRODUCTION COST:      AFN 3,822.67
STANDARD PROFIT:             AFN 1,858.06
STANDARD MARKUP:             40%
STANDARD PROFIT MARGIN ON REVENUE: 28.57%
ACTUAL REALIZED PROFIT:      AFN 2,680.53
ACTUAL REALIZED MARGIN:      41.22%
```

## Phase 10 — Regression
```
Client Excel formula:                    PASS
40% applied once commercially:           PASS
Net Rate final default selling price:    PASS
No additional 25% markup:                PASS
40% not duplicated as fake actual exp:   PASS
Roll -> kg:                              PASS (5,000 kg)
5% wastage once:                         PASS (2.9032 kg; planned 60.9676)
Snapshot kg:                             PASS (60.9676 kg)
Landed cost/kg:                          PASS ($0.95)
FIFO:                                    PASS (5,000−2×60.9676=4,878.0648 kg remaining)
Actual genuine costs:                    PASS (material only; real labour/overhead/other preserved)
Standard profit:                         PASS (AFN 1,858.06 / 28.57%)
Actual realized profit:                  PASS (AFN 2,680.53 / 41.22%)
Traceability:                            PASS (Material 162 → batch 40 → BOM 21 → Sale 74 → PO 78 → PMC)
```

## Notes
- `php -l` clean on all changed PHP files; `php artisan view:cache` clean; routes load.
- Estimated/planned internals intentionally left unchanged per approved scope ("exclude 40% from actual cost only").
- Both QA sales are test data recreated (#73 intentionally shows prior behavior for contrast; #74 shows corrected behavior). Both retained; no cleanup.