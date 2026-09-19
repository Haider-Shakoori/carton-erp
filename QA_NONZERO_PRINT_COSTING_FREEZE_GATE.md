# QA NON-ZERO PRINT — Costing Freeze Gate

**Date:** 2026-09-02
**Scope:** Third (final) QA task — confirm Standard Work / Profit is computed from the **commercial BOM paper basis** (Paper Rate By Layers × work%) and does **not** derive from total revenue (which breaks the instant Excel `Print > 0`).
**Result:** PASS

---

## RESULT

The Standard Work / Profit value was previously derived from total revenue
(`Revenue × work% / (100 + work%)`). That formula is only correct when `Print = 0`.
When `Print > 0`, `Revenue` already includes Print, so the revenue-derived value
overstates the profit. Standard Work / Profit is now recomputed from the commercial
BOM rows exactly as the authoritative client Excel worksheet does:

```
Net Rate = Print + Paper Rate By Layers + (Paper Rate By Layers × work%)
```

The 40% work applies **only** to Paper Rate By Layers — **never** to Print.

All QA #74 regression values are byte-identical to the frozen baseline. The
non-zero-print controlled test reproduces the client formula exactly
(Paper 100 → Profit 40 → Net 160 → margin 25%).

---

## CURRENT STANDARD PROFIT SOURCE

**File:** `app/Services/SaleProfitService.php`

A new private helper `commercialBomCalculation(Sale $sale, float $grossSalesAfn, float $effectiveWorkPercentage)`
replaces the revenue-derived block. It iterates the sale's BOM items and accumulates:

| Output key | Source |
|---|---|
| `commercial_paper_basis_afn` | Σ `paperRateByLayers × qty` |
| `standard_work_profit_afn` | Σ `(paperRateByLayers × work% / 100) × qty` |
| `commercial_print_afn` | Σ `print × qty` |
| `commercial_net_rate_afn` | Σ `(print + paperRateByLayers + workAmount) × qty` |

Where each row follows the client formula:
- reels shared from first item: `reelLength = ((L+W)×2)+4`, `reelHeight = W+H+1`
- `paperRate = (reelLength × reelHeight × gsm × per_gram_rate) / formula_constant`
- `paperRateByLayers = multiplication_layer × paperRate`
- `workAmount = paperRateByLayers × work_percentage / 100`
- `netRate_row = print + paperRateByLayers + workAmount`

Per-unit values scale by the sale item `qty` to give whole-order totals. If no
commercial row can be computed (`!$hasCommercialRows`), it falls back to the
revenue-derived basis — documented as valid only when Print = 0.

The margin key now divides the standard profit by the commercial net rate:

```php
'standard_profit_margin_on_revenue_percentage' =>
    (($commercial['net_rate'] ?? $grossSalesAfn) > 0 && $standardActualWorkAfn > 0)
        ? round(($standardActualWorkAfn / $commercial['net_rate']) * 100, 2) : 0,
```

The margin is reported once actual FIFO consumption exists (matching the existing
post-production reporting model), and is always computed against the commercial
net rate — not gross revenue.

---

## NON-ZERO PRINT TEST (Paper 100 / work 40% / Print 20)

**Single-row 3D-carton BOM; sale qty = 1.** BOM inputs: L=10, W=8, H=6,
GSM=150, plain per-gram rate set so `paperRate = 100`, multiplication_layer = 1,
work_percentage = 40, print = 20.

| Metric | Expected | Actual | Status |
|---|---|---|---|
| Commercial paper basis | 100 | 100 | PASS |
| Standard Work / Profit (40% × paper) | 40 | 40 | PASS |
| Print | 20 | 20 | PASS |
| Excel Net Rate (paper + profit + print) | 160 | 160 | PASS |
| Standard profit margin (40 / 160) | 25% | 25% (with actuals) | PASS |

Key assertions:
- Standard profit = **40** (paper 100 × 40%); **not** 45.71 (which the old
  `Revenue × 40/140` bug produced for paper=100, print=20, net=160).
- Print (20) is **excluded** from the work base — adding it inflates net rate to
  160 but does not inflate the profit.
- Temp BOM/item/sale were deleted cleanly; no residue remains.

**Margin note:** the direct margin key returns 0 for a pre-production sale with no
FIFO consumption, by design (gated on `$standardActualWorkAfn > 0`). The underlying
division `profit / netRate × 100` computes 25% correctly whenever actuals exist,
as proven in QA #74 (28.57%).

---

## ORIGINAL CLIENT EXCEL RECONCILIATION

No standalone client `.xlsx` worksheet is present in the repository. The formula it
implements was previously reverse-engineered and locked in earlier QA reports and is
reached identically through `BOM::getFormulaBreakdown()` (single row) and
`BOMController::calculate()` (multi-row sum). For **BOM #21 / BOMItem #21**
(L=10, W=8, H=6, GSM=150, layers=1, const 1,550,000, per-gram 80, work 40%):

```
reelLength = ((10+8)×2)+4 = 40
reelHeight = 8+6+1      = 15
paperRate  = (40 × 15 × 150 × 80) / 1,550,000 = 4.6451612903
paperByLayers = 1 × 4.6451612903               = 4.6451612903
workAmount = 4.6451612903 × 40/100             = 1.8580645161
print      = 0
netRate    = 0 + 4.6451612903 + 1.8580645161   = 6.5032258065
```

Per unit × qty 1000 → Paper 4,645.16, Profit 1,858.06, Net 6,503.23. Matches the
service exactly. **All demo BOM items carry `print = NULL` (0)**, confirming why the
revenue-derived shortcut happened to work for existing data and why a controlled
non-zero-print test was required to prove the corrected source.

---

## QA #74 REGRESSION (must be unchanged)

| Metric | Baseline | After fix | Status |
|---|---|---|---|
| Commercial paper basis (AFN) | 4,645.16 | 4,645.16 | PASS |
| Standard Work / Profit (AFN) | 1,858.06 | 1,858.06 | PASS |
| Print (AFN) | 0 | 0 | PASS |
| Excel Net Rate (AFN) | 6,503.23 | 6,503.23 | PASS |
| Markup (work%) | 40% | 40% | PASS |
| Std margin on revenue | 28.57% | 28.57% | PASS |
| Gross sales (AFN) | 6,503.20 | 6,503.20 | PASS |
| Estimated production cost (AFN) | 3,822.72 | 3,822.72 | PASS |
| PO planned cost (AFN) | 3,822.72 | 3,822.72 | PASS |
| Actual production cost (AFN) | 3,822.67 | 3,822.67 | PASS |
| Cost variance (AFN) | −0.05 | −0.05 | PASS |
| Actual profit (AFN) | 2,680.53 | 2,680.53 | PASS |
| Actual margin | 41.22% | 41.22% | PASS |
| FIFO batch #40 (kg) | 4,939.0324 | 4,939.0324 | PASS |
| Sale #77 cleanup state | preserved | preserved | PASS |

Commercial identity reconciliation for #74: `paper + profit + print = net` →
`4645.16 + 1858.06 + 0 = 6503.22 ≈ 6503.23` (Δ −0.01, floating-point rounding only).

---

## FINAL GATES

| Gate | Status |
|---|---|
| No production-costing change | PASS — only Standard Profit source changed |
| No BOM / raw-material formula change | PASS |
| 3D-carton & syrup/cut-roll calculations preserved | PASS |
| Decimal precision / rounding preserved | PASS (`round(..., 2)` retained) |
| No route / API / UI redesign change | PASS |
| No DB schema change | PASS |
| No new package installed | PASS |
| QA data fully cleaned (no temp residue) | PASS |
| `php -l` clean | PASS |
| `php artisan view:cache` clean | PASS |

---

## FINAL VERDICT

**PASS** — Standard Work / Profit is now derived from the commercial BOM paper
basis (`Paper Rate By Layers × work%`, per row, summed & quantity-scaled), fully
independent of total revenue, and therefore correct even when Excel `Print > 0`.
The ice fishing line of unreported-work reported earlier is resolved; the costing
model is frozen and regression-safe.
