# QA — SALE #77 MANUAL-BOM PROFIT TRACE

## RESULT
**NEEDS UI FIX** (label/helper-text fixes only — **NO calculation bug**, **NO costing change**)

## SALE
**77**

## PRICING MODE
**MANUAL** (Manual BOM quotation using template `BOM-DIHIIIVJ` = Excel (v1.0))
- Sale status: **draft**, `is_produced = 0`, no production order, no actual consumption.
- Item #77: product 95, BOM #1 (Excel), qty = **10**, currency AFN (id 3), rate = **66.0000**.

---

## SOURCE TRACE

> Source of truth = `app/Services/SaleProfitService.php::calculate()` (called by
> `SaleController::show()` and passed to the view as `$profitSummary`).
> Sale-item fields set at creation in `SaleController::addItemWithBOM()` (MANUAL branch).

### 581.46 — Quotation / BOM Total
- File/method: `SaleProfitService::calculate()` lines 27–28.
- Value: `quotation_bom_cost_afn = sum(SaleItem.total_cost_usd) × exchangeRate`
  = `8.81 × 66` = **581.46**. Computed, not stored on the sale row.
- Meaning: the **commercial Excel Net Rate total** for the order (see MANUAL branch:
  `totalCostPerUnitAfn = Σ (printCost + paperRateByLayers + workAmount)`; `total_cost_usd =
  netRate/rate × qty`). It **already includes the 40% standard work/profit** and any print —
  it is a selling/quotation basis, **NOT** production cost.

### 106.67 — Estimated Production Cost
- File/method: `SaleProfitService::calculate()` lines 44–97.
- Value: `estimated_cost_afn = planned_material × rate` = `(1.54 + 0.08) × 66` ≈ **106.67**.
  Planned base material AFN 101.59 + planned wastage AFN 5.08 (5%) = 106.67. Computed.
- Meaning: **estimated physical production cost** (material requirement incl. 5% wastage at
  landed cost). The frozen 40% work/profit is deliberately **excluded** (work is commercial,
  not a production expense). Correct.

### 475.01 — Estimated Profit
- File/method: `SaleProfitService::calculate()` lines 98–99.
- Value: `estimated_profit_afn = gross_sales_afn − estimated_cost_afn = 581.68 − 106.67` = **475.01**. Computed.
- Formula confirmed: **Sale Revenue − Estimated Production Cost**. Mathematically correct.

### 0.00 — Standard Work / Profit (in the Estimated Production Cost card)
- File/method: view `sales/show.blade.php` line ~1735 rendered `estimated_work_cost_afn`
  (`plannedWorkUsd`), which is **always 0** (work is never added to production cost —
  `SaleProfitService.php` lines 30–36, 83–85). Displayed, not stored.
- Meaning: **production work component = 0** (correct by design — the 40% is commercial).
- IMPORTANT: the **commercial** Standard Work / Profit for this order is NOT 0 — the service
  computes `standard_work_profit_afn = 56.33` (= commercial paper basis 140.83 × 40%).
  It is simply not shown inside the production card. The old helper text
  “Standard Work / Profit 40%: 0.00” was therefore **misleading**.

### 58.1658 — Quotation Cost / Unit (Excel Net Rate / Unit)
- File/method: item table (`show.blade.php` lines 2163–2172); `cost_per_unit_usd` stored on
  SaleItem (set in `SaleController.php` line 1575).
- Value: `item->cost_per_unit_usd (0.8813) × rate (66)` = **58.1658**. Persisted (0.8813) then
  scaled × rate. This is the **commercial Excel Net Rate per unit in AFN** (0.8813 USD/unit).

### 581.46 — Quotation Cost Total (Excel Quotation Total)
- File/method: item table lines 2164–2173; `item->total_cost_usd (8.81) × rate (66)` = **581.46**.
- Same commercial quotation (Net Rate) total as the top card; the per-unit (58.1658) and total
  (581.46) are derived from separately-rounded USD fields (0.8813 vs 8.81) and therefore do not
  factor exactly (58.1658 × 10 = 581.658 ≠ 581.46).

### 58.17 — Unit Price
- File/method: item table line 2206; stored `SaleItem.unit_price`.
- Value: **58.1677** displayed as `58.17`. Set in MANUAL mode equal to the Excel Net Rate per unit
  (AFN). Persisted. Correct (selling price per unit).

### 581.68 — Sale Total
- File/method: stored `Sale.grand_total` (recalculated); shown in item table line 2207.
- Value: `unit_price 58.1677 × qty 10 = 581.68`. Persisted. Correct (order total).

### 0.22 — Quotation Profit (→ Price Variance)
- File/method: item table lines 2167–2168.
- Value: `profitAfn = itemRevenueAfn − costTotalAfn = 581.68 − 581.46 = 0.22`. Computed.
- Semantics (see below): **NOT profit.** Both numbers represent the SAME commercial Net Rate —
  581.68 from the AFN unit price (58.1677 × 10) and 581.46 from the USD-rounded total
  (8.81 × 66). The 0.22 is a **rounding/precision variance** between two representations of the
  same rate. Label changed to **Price Variance**.

### 0.0% — Quotation Margin (→ Price Variance %)
- File/method: item table line 2169.
- Value: `profitAfn / itemRevenueAfn × 100 = 0.22 / 581.68 × 100 = 0.0378%` → shown `0.0%`.
- Same semantics as 0.22. Label changed to **Price Variance %**.

---

## SEMANTIC FINDINGS

- **Quotation/BOM Total (581.46) means:** the **commercial Excel Net Rate total** for the order
  (paper + 40% standard work/profit + print, × qty). It is the client-quotation selling basis,
  **NOT** production cost and **NOT** raw paper cost. It correctly does not flow into estimated
  production cost.
- **Estimated Profit (475.01) means:** `Sale Revenue − Estimated Production Cost` — the **profit
  relative to estimated production cost**. The label is accurate as an estimated (pre-actual)
  profit; for precision it can be read as “Estimated Realized Profit” (kept unchanged to preserve
  the shared label).
- **Quotation Profit (0.22) means:** `Sale Total − Quotation/BOM Total` — a **selling-price /
  rounding variance** between the AFN unit price and the USD-reconstructed commercial total. It is
  **NOT a profit figure** (the “cost” subtracted already contains the 40% profit markup).
- **Quotation Margin (0.0%) means:** the above variance as a percent of revenue. **NOT a margin.**
- **Standard Work / Profit source:** computed live by `SaleProfitService::commercialBomCalculation()`
  = commercial paper basis × 40% (print excluded) = **56.33 AFN** for this order. It is reported as a
  commercial figure, not as a production cost. The `0.00` shown in the production card is the
  **production** work component (always 0), not the commercial standard profit.

---

## CLASSIFICATION

| Display | Current Value | Source | Formula | Classification | Recommended Action |
|---|---:|---|---|---|---|
| Quotation/BOM Total | 581.46 | sell summary `quotation_bom_cost_afn` | Σ total_cost_usd × rate | CORRECT | Keep (label + “Client Excel quotation basis” subtext are accurate) |
| Estimated Production Cost | 106.67 | `estimated_cost_afn` | (base + 5% wastage) × rate | CORRECT | Keep |
| Estimated Profit | 475.01 | `estimated_profit_afn` | Revenue − Est. production cost | CORRECT | Keep (could read “Estimated Realized Profit”; shared label left unchanged) |
| Standard Work / Profit | 0.00 (production) | `estimated_work_cost_afn` (always 0) | plannedWorkUsd (0 by design) | MISLEADING LABEL | Helper text clarified: 40% is commercial and excluded from production cost (commercial value 56.33 is reported separately) |
| Quotation Cost/Unit | 58.1658 | item `cost_per_unit_usd` × rate | Net Rate / unit | MISLEADING LABEL | Renamed → “Excel Net Rate/Unit” |
| Quotation Cost Total | 581.46 | item `total_cost_usd` × rate | Net Rate × qty | MISLEADING LABEL | Renamed → “Excel Quotation Total” |
| Unit Price | 58.17 | stored `unit_price` | Excel Net Rate/unit (AFN) | CORRECT | Keep |
| Sale Total | 581.68 | stored `grand_total` | unit_price × qty | CORRECT | Keep |
| Quotation Profit | 0.22 | item `profitAfn` | Revenue − Quotation total | MISLEADING LABEL | Renamed → “Price Variance” |
| Quotation Margin | 0.0% | item `rowMargin` | Variance / Revenue | MISLEADING LABEL | Renamed → “Price Variance %” |
| AFN rendering | `؋ 581.68` | `currency.symbol` prefix | n/a | DISPLAY ISSUE (none in code) | No code change — glyph is clean (`؋ ` prefix, no `?`/`؋?`); any odd look is presenter-machine font/RTL |

---

## UI FIXES

Safe, presentation-only label/helper-text changes (no calculation, no stored value, no formula):

1. Sale Items table (pre-production / quotation case) headers renamed across **en / fa / ps**:
   - `quotation_cost_unit` → **Excel Net Rate/Unit** (was “Quotation Cost/Unit”)
   - `quotation_cost_total` → **Excel Quotation Total** (was “Quotation Cost Total”)
   - `quotation_profit` → **Price Variance** (was “Quotation Profit”)
   - `quotation_margin` → **Price Variance %** (was “Quotation Margin”)
   - The `actual_*` labels (Actual Cost/Unit, Actual Profit, …) are untouched.
2. Estimated Production Cost card helper: replaced the misleading
   “Standard Work / Profit 40%: 0.00” with a clarifying note that the 40% is a
   **commercial markup in the quotation Net Rate**, held separately and NOT an additional
   production cost (production work component = 0). No invented value injected.

Files changed:
- `resources/views/admin/sales/show.blade.php` (helper text)
- `resources/lang/en/ui.php`, `resources/lang/fa/ui.php`, `resources/lang/ps/ui.php` (labels)

Verified: `php -l` all 3 lang files clean; `php artisan view:cache` succeeded; sale #77
still renders 581.68 / 581.46 / 106.67 / 475.01 / 58.17 / 58.1658 / 0.22, with the new labels and
removed misleading text. **AFN symbol confirmed clean** in rendered output (no `؋?`, no `?؋`,
no Arabic `؟`); the 94 `?` in HTML are all JS ternaries / URLs / regex, not glyph corruption.

---

## FROZEN COSTING
Changed:
**YES / NO** → **NO**

Must remain:
**NO** ✓ (no BOM / Excel Net Rate / Standard Work / Profit formula, print, kg, wastage, landed
cost, FIFO, SO/PO estimate, actual costing, or actual-profit formula was touched — display only)

---

## REGRESSION
Sale #77: **PASS**
FINAL-DEMO: **PASS** (Sale #76 service rerun: 26503.23 / 3822.72 / 3822.67 / 22680.56 / 85.58 /
1858.06 — all byte-exact)
QA #74: **PASS** (Sale #73 service rerun: est 3822.72 / standard work/profit 1858.06 — unchanged;
sale 73’s multi-item grand total 8129.03 vs commercial net 6502.98 is pre-existing data, not a
regression and unaffected by these label edits)

---

## FINAL VERDICT
Main issue: the confusing figures were **not wrong calculations** — they were **misleading labels**.
- 581.46 is the **commercial Excel Net Rate** (quotation) total, correctly kept separate from
  the 106.67 production estimate.
- 475.01 is genuinely Revenue − Estimated Production Cost.
- The “Quotation Profit” 0.22 / “0.0%” are a **price/rounding variance**, not profit.
- The “Standard Work / Profit 40%: 0.00” helper implied the commercial 40% was zero; it is
  actually AFN 56.33 (the 40% is a commercial markup, not a production cost).

Calculation bug: **NO**
Labeling bug: **YES** (Quotation Profit/Margin → Price Variance; Quotation Cost → Excel Net Rate;
Standard Work/Profit production helper) — all fixed as display-only.
Display bug: **NO** in code (AFN glyph renders as a clean `؋ ` prefix)
Manual-BOM Standard Profit issue: **YES — INSUFFICIENT DATA at the point of display** (the
commercial Standard Work/Profit = 56.33 is computed correctly by the service, but the production
card only ever shows the 0 production-work component; there is no dedicated commercial Standard
Work/Profit readout on the sale page). No formula change made.

SAFE TO FIX WITHOUT COSTING CHANGE: **YES** (fixes applied are label/helper-only and are within the
frozen-costing guard).
