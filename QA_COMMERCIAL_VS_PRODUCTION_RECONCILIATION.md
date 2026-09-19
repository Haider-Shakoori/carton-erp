# FINAL COMMERCIAL VS PRODUCTION COST RECONCILIATION

## RESULT: PASS

The commercial quotation is mathematically correct. `per_gram_rate` is a legitimate
**AFN-per-kg commercial paper rate** (the constant 1,550,000 exactly normalises the
"per gram" name to per-kg). The ~63× gap versus production is **not a unit bug** — it is
the difference between a **commercial market rate (AFN 1,000/kg)** and the QA's
deliberately low **controlled landed cost (USD 0.24/kg = AFN 15.84/kg)**.

---

## ROOT CAUSE

The constant `1,550,000` equals `1000 ÷ 0.00064516` (the in²→m² and grams→kg combined
factor). Therefore the quotation formula resolves to:

```
Paper (AFN/carton) = sheet_grams × (per_gram_rate ÷ 1000) = sheet_kg × per_gram_rate
```

So **`per_gram_rate` is a price per kilogram**, and the quotation output is directly in
**AFN** (confirmed by the `؋` quotation UI and `final_rate_afn`). The QA value
`per_gram_rate = 1000` means **AFN 1,000/kg = USD 15.15/kg** — a coherent commercial rate.
The production side uses landed **USD 0.24/kg**. Ratio ≈ **63×**, explained fully by the QA's
intentionally low purchase ($100/roll), not by any conversion/multiplication error.

The **only genuine defect** is presentation: `bom/show.blade.php` printed `per_gram_rate`
with a `$` (USD) prefix while the quotation applies it as AFN, and there was no unit/currency
label on the field. That is fixed as a **label-only clarification** (no arithmetic/path change).

---

## CLASSIFICATION
- [x] Bad QA data *(none — `per_gram_rate=1000 AFN/kg` is coherent)*
- [ ] Quotation unit bug *(none — arithmetic correct)*
- [ ] Currency conversion bug *(none)*
- [x] Per-gram/per-kg mismatch *(presentation label only; formula already per-kg via constant)*
- [ ] Production costing bug *(none)*
- [ ] Multiple issues *(no)*

---

## QUOTATION TRACE (BOMItem #20 / Sale #72)
- Reel Length: `((10+8)×2)+4 = 40 in`
- Reel Height: `8+6+1 = 15 in`
- GSM: `150`
- Per Gram Rate stored: **NULL on BOMItem #20** (transient sale-time `formula_snapshot` = **1000**)
- Per Gram Rate intended unit: **AFN / kg** (proven: constant 1,550,000 = 1000/0.00064516)
- Multiplication: `40 × 15 × 150 × 1000 = 90,000,000`
- Paper Rate: `90,000,000 / 1,550,000 = 58.0645`
- Layers: `× 1 = 58.0645`
- Commercial Paper/Carton: **AFN 58.0645**
- Work: `58.0645 × 40% = 23.2258`
- Net Rate: **AFN 81.2903 / carton**
- Qty 1,000 → quotation base **AFN 81,290.32**

---

## EFFECTIVE RATE
- Commercial material total: **AFN 58,064.52** (paper only, 1,000 cartons)
- Physical base: **58.0644 kg**
- Effective commercial: **AFN 1,000 / kg**
- Effective commercial: **USD 15.1515 / kg**
- Actual landed: **USD 0.24 / kg**
- Ratio: **63.13×**
- Explanation: intentional commercial markup basis vs the QA's deliberately low controlled
  landed cost (USD 0.24/kg); not a units/conversion error.

---

## ROOT CAUSE PROOF
```
sheet_grams = 40 × 15 × 0.00064516 × 150 = 58.0644 g/sheet
quotation_paper_AFN = sheet_grams × (per_gram_rate / 1000)
                    = 58.0644 × (1000 / 1000) = 58.0644 AFN/carton   (Excel: 58.0645, rounding)
∴ per_gram_rate is AFN per kg; 1000 = AFN 1,000/kg.

production material AFN/1000 = sheet_kg × landed × 66
                               = 60.9676 kg × 0.24 USD/kg × 66 = AFN 965.73
∴ gap = AFN 1,000 vs AFN ~15.84 per kg = ~63× — pure basis difference.
```

---

## FIX
- Code changed (math/formula/production path): **NO**
- Data changed: **NO** (BOMItem #20 `per_gram_rate` is NULL; sale value 1000 correct)
- Files changed (label-only clarity, all locales + BOM show):
  - `resources/lang/en/ui.php`
  - `resources/lang/ps/ui.php`
  - `resources/lang/fa/ui.php`
  - `resources/views/admin/bom/show.blade.php` (4 × `$` → `؋` on per_gram_rate)
- Explanation: made the unit/currency explicit ("Per Gram Rate (AFN / kg)"); aligned the
  BOM show currency prefix with the quotation's AFN semantics. Arithmetic and production
  architecture untouched; no schema/route/package changes.

---

## POST-FIX QA
- Quotation/carton: **AFN 81.2903**
- Quotation total: **AFN 81,290.32**
- Actual material: **AFN 965.73**
- Actual work: **AFN 386.29**
- Actual production: **AFN 1,352.02**
- Revenue: **AFN 101,612.90**
- Exact profit: **AFN 100,260.88**
- Margin: **98.67%**
- Mathematical profit (2-dp: 101,612.90 − 1,352.02 = 100,260.88): **PASS**
- Unit consistency: **PASS** (quotation AFN-per-kg output in AFN; production USD/kg × 66 into AFN; work 40% applied once each)
- Commercial rate semantics: **PASS** (AFN / kg, now explicit)

---

## PRODUCTION REGRESSION
- Roll → kg: **PASS**
- Snapshot kg (60.9676): **PASS**
- FIFO kg (60.9676): **PASS**
- Landed cost/kg ($0.24): **PASS**
- 5% wastage once: **PASS**
- 40% work once: **PASS**
- Profit arithmetic: **PASS**

No reintroduction of the 2.1-roll bug.

---

## FINAL VERDICT
- Quotation unit problem resolved: **YES** (clarified, not a math bug)
- Production costing preserved: **YES**
- Commercial quotation trustworthy: **YES**
- Actual production cost trustworthy: **YES**
- Profit trustworthy: **YES**
- SAFE FOR CLIENT DEMONSTRATION: **YES**

## Testing performed
- `php -l` on changed lang files && blade view compiled (`php artisan view:cache`)
- Direct DB inspection of BOMItem #20, Sale #72, SaleItem #72, PO #76, POM #90, PMC #27, batch #39
- Reproduction of the quotation formula and effective-rate/ratio via script
- `SaleProfitService::calculate(Sale 72)` for exact profit and PASS/FAIL flags
- Historical records untouched; QA transaction retained.