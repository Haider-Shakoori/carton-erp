# QA_SALE_77_COMPLETE_MANUAL_BOM_RECONCILIATION

**Sale:** #77 (draft) — Product: 120ml Syrup Box (product 95), qty 10, AFN (currency id 3), rate 66
**BOM:** #1 `BOM-DIHIIIVJ` v1.0 (template), but priced as a **MANUAL COMMERCIAL BOM**
**Scope:** commercial Excel reconciliation, manual snapshot persistence, Standard Work/Profit, estimated (physical) production cost, price precision, COG terminology
**Date:** 2026-09-03

## RESULT: FIXED

The manual commercial BOM now displays and cost-reconciles correctly. Sold cost basis, paper + 40% work + print, and the physical requirement are all rebuilt from the **saved manual snapshot** instead of falling back to stale template rows.

| Display | Before | After | Verdict |
|---|---|---|---|
| Standard Work / Profit (card) | 56.33 | **170.51** | FIXED |
| Excel Quotation Total (card + items) | 617.10 | **616.80** | FIXED (canonical) |
| Estimated Production Cost (card) | 106.67 | **463.64** | FIXED (manual rows) |
| Estimated Realized Profit (card) | (510.13 old artifact) | **153.16** | FIXED |
| Price variance (phantom) | -0.30 | **0.00** | FIXED |
| Material Cost (REF) | 51.7547 / 10.3512 | 51.7547 / 10.3512 | CORRECT (unchanged) |

### Frozen regressions (must not move)
- **Sale 76 (FINAL-DEMO):** quotation 26,503.23; estimated_cost 3,822.72; estimated_profit 22,680.56; actual_profit 22,680.56; actual_margin 85.58; standard_work_profit 1,858.06. **PASS — unchanged.**
- **Sale 73 (QA frozen):** estimated_cost 3,822.72; estimated_profit 8,129.03; standard_work_profit 1,858.06; quotation_bom 6,502.98. **PASS — unchanged.**

---

## BUGS FOUND

### A. Standard Work / Profit — WRONG (56.33) BEFORE — **FIXED**
- **Formula (frozen, verified):** `Paper Rate = Reel L × Reel H × GSM × PerGramRate / FormulaConstant`, `FormulaConstant = 1,550,000`, `Reel L = ((L+W)*2)+4 = 70.14`, `Reel H = W+H+1 = 28.95`. `PaperRateByLayers = PaperRate × Layers`. `StandardWorkProfit = PaperRateByLayers × 40%` (work applies to **paper only**).
- **Root cause:** `addItemWithBOM()` computed the manual net rate correctly from the client-provided `formula_snapshot` but persisted **only the aggregate net rate** (`cost_per_unit_usd = 0.9345 USD = 61.68 AFN/unit`). The per-row manual inputs (gsm / layers / rate / work% / print / wastage) were **not persisted**. Later, `commercialBomCalculation()` had nothing to work from and **fell back to the stale template BOM #1** (both rows gsm125 / layers 1 / rate 43), recomputing **56.33** instead of the manual **170.51**.
- **Fix (approved):** new nullable `sale_items.manual_bom_snapshot` (longText) column + model cast; controller persists the manual breakdown on manual items; `SaleProfitService` reads the snapshot and runs the **exact Excel formula** from the saved rows.
- Manual rows now reflected:
  - Row 1: gsm 125, layers 5, rate 40 → Paper 32.7509, Work 13.1003, Net 45.8512
  - Row 2: gsm 145, layers 1, rate 52, print 2 → Paper 9.8777, Work 3.9511, Net 15.8287
  - Unit net **61.6799**; Standard Work **17.0514/unit × 10 = 170.51**; Paper basis **426.29**; Print **20.00**.

### B. Sale snapshot persistence — **FIXED**
- Before: only `cost_per_unit_usd` (aggregate net) persisted; per-row manual inputs lost.
- After: full `manual_bom_snapshot` persisted and read back; template fallback no longer occurs for manual items.

### C. Price precision — Excel total vs sale total — **FIXED (canonical 616.80)**
- Manual combined net **61.6799/unit × 10 = 616.80** (canonical total, matches `sale.total`).
- The item's stored USD `cost_per_unit_usd 0.9345` → `total_cost_usd 9.35` (rounded to 2dp). The old display rebuilt AFN as `9.35 × 66 = 617.10` and reported a phantom **-0.30** price variance.
- **Fix:** for manual-snapshot items the Excel quotation now uses the canonical AFN `unit_price` / `total` (616.80) → variance **0.00**.
- Classification: avoidable early USD rounding artifact; no formula change.

### D. Estimated (physical) production cost — quantity FIXED; landed rate unit = **COSTING FREEZE CONFLICT**
- The physical requirement uses the **existing 3D carton kg formula** on the **manual rows** (was: stale template rows gsm125/layers1):
  - Row 1: gsm 125, layers 5 → base 8.18774 kg + 5% waste 0.40939 → **8.59713 kg**
  - Row 2: gsm 145, layers 1 → base 1.89956 kg + 5% waste 0.09498 → **1.99454 kg**
  - Planned total base 10.0873 kg, with waste **10.5917 kg**
  - Estimated cost AFN: base **441.56** + waste **22.08** = **463.64** (7.02 USD). `estimated_work_cost_afn = 0` by design (work is a commercial paper markup, not a physical costing line).
- **CONFLICT:** costing pairs `kg quantity × BOM row cost_per_unit_usd`, but the BOM cost `0.78 / 0.16` is a **per-ROLL** landed rate (inventory has no `kg_per_roll`, no `qty_kg`, no `landed_cost_per_kg`). `kg × USD/roll` is a **unit mismatch**. Per directive this is a **COSTING FREEZE CONFLICT**: **NOT patched**, reported for owner decision.

### E. Roll → kg conversion / landed cost — **COSTING FREEZE CONFLICT** (not patched)
- Kraft Paper (product 1, unit = roll) purchases: PO-0011 (12,000 @ $0.80/roll), PO-0014 (10,000 @ $10/roll), QA batch ($5/roll). All `kg_per_roll = NULL`, `qty_kg = 0`, `landed_cost_per_kg = NULL`.
- No kg-per-roll / per-kg landed rate exists in inventory to convert rolls → kg for costing. Documenting; **no formula invented**.

### F. Material Cost (REF) 51.7547 / 10.3512 — **CORRECT**
- Column = BOM row `cost_per_unit_afn` (USD 0.78 / 0.16 per roll × AFN rate). Unit label `/roll` **CORRECT**; AFN currency **CORRECT**; `reference_only` accurate — not used in costing.

### G. COG terminology — **CORRECT**
- Pre-production card header is **"Estimated Production Cost"** (not "actual COGS") → 463.64. Post-production shows "Actual Production Cost" + "Actual Realized Profit". Correct per lifecycle.

## COMMERCIAL vs COG RECONCILIATION (Phase 11)
- **Commercial quotation = 616.80** (Paper 426.29 + Work 170.51 + Print 20.00) = sale total.
- **Estimated physical cost = 463.64** (manual kg × frozen landed rate; unit-frozen conflict noted in D/E).
- **Estimated realized profit = 616.80 − 463.64 = 153.16.**
- Why they differ: commercial Excel is **AFN price-based** (paper/work/print per unit); estimated production cost is **physical kg × landed rate**. Different bases — they only coincide if the quoted commercial price happens to equal the landed physical cost per unit. Not a defect.

## FILES CHANGED
- `database/migrations/2026_09_03_134109_add_manual_bom_snapshot_to_sale_items_table.php` — NEW nullable `sale_items.manual_bom_snapshot`.
- `app/Models/SaleItem.php` — `manual_bom_snapshot` in `$fillable` + `array` cast.
- `app/Http/Controllers/Admin/SaleController.php` (`addItemWithBOM`) — persists manual snapshot on manual items.
- `app/Services/SaleProfitService.php` — reads snapshot for commercial (170.51 / 616.80) and physical rows; `quotation_bom_cost_afn` override.
- `resources/views/admin/sales/show.blade.php` — profit card section headers; canonical commercial total for manual items (616.80, removes phantom -0.30).
- Lang: `en/fa/ps` `ui.php` — added reporting keys.

## VERDICT
- **RESULT FIXED** for manual-snapshot persistence → Standard Work/Profit 170.51, commercial total 616.80, physical estimate 463.64, realized profit 153.16; phantom variance removed; regressions (76/73) intact.
- **COSTING LOGIC CHANGED: YES** — the *source of the commercial/physical reporting data* changed from stale template rows to the persisted manual snapshot; the **formulas themselves are untouched** (Excel commercial formula, 40% work definition, kg formula, wastage, landing convention all unchanged).
- **OPEN (owner action):** roll→kg / kg×USD-per-roll landed-rate unit mismatch = **COSTING FREEZE CONFLICT** (D/E). Not patched.
- **Test note:** the automated suite fails globally in the SQLite test environment on a pre-existing `boms.formula_type` drop-column migration (affects even `ExampleTest`); this predates and is unrelated to the `sale_items.manual_bom_snapshot` change. HTTP-level verification of sales 77/76/73 all pass.
