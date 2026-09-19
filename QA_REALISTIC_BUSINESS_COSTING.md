# QA — Realistic End-to-End Business Costing

**Date:** 2026-09-02
**Scope:** One clean, realistic end-to-end transaction (paper purchase → roll→kg → expenses → landed cost/kg → inventory → BOM → commercial quotation → sale → production order → kg snapshot → FIFO → completion → actual production cost → exact profit → estimate vs actual → traceability).
**Method:** Normal application paths (models / controllers / services / Tinker + focused DB inspection). No production-order-material manual inserts. No code, schema, costing-formula, or historical-data changes.
**Result: `VERDICT: PASS` — all checkpoints A–J.**

---

## QA data (new, realistic; kept for client review)

| Entity | id | Details |
|---|---|---|
| Material "QA Demo Kraft Paper 150 GSM" | 162 | type `raw_material`, purchase unit `roll`, inventory/production base unit `kg` |
| Purchase PQA-D-20260902-COMU | 36 | 10 rolls × 500 kg = **5,000 kg**, $400/roll → base $4,000; Freight $500 + Customs/Other $250 = **$750 expenses**; landed $4,750 |
| Successor finished good "QA Demo Pharmaceutical Carton" | 163 | unit `carton` |
| BOM #21 (single active) / BOMItem #21 | 21 | geometry L=10, W=8, H=6, GSM=150, layers=1, form. const 1,550,000, wastage 5%, work 40%, **`per_gram_rate = 80` AFN/kg** |
| Sale QADEMO-20260902-XWXL (AFN, x-rate 66) | 73 | 1,000 cartons, `unit_price` 8.1290 AFN |
| Production order PROD-2026-Q9N6FNOZ | 77 | 1,000 cartons → completed |

---

## Checkpoints (all PASS)

### A — Purchase: roll→kg, expenses, landed cost
```
Received kg = 5000    base(subtotal) = $4000.00    expenses = $750.00    landed(grand) = $4750.00
base/kg = 0.80 USD    expense/kg = 0.15 USD    landed = 0.95 USD/kg
Inventory available kg = 5000   current_stock_kg = 5000
Landed AFN/kg = 0.95 × 66 = 62.70
```

### B — BOM physical material requirement (per 1,000 cartons)
```
base kg/carton = 0.058064   base kg/1000 = 58.0644
5% wastage     = 2.9032 kg  (applied once)
planned kg incl wastage = 60.9676 kg
```

### C — Commercial quotation (AFN) — per_gram_rate fed as-is into the client Excel formula
```
Commercial AFN/kg = 80   vs actual landed AFN/kg = 62.70   ratio = 1.276×
Paper/carton = AFN 4.6452  (= base kg × 80/kg)   Print = 0
Work 40%      = AFN 1.8581
Net quotation = AFN 6.5033/carton   →   quotation total = AFN 6503.23
```
Quotation excludes production wastage. `per_gram_rate` is dimensionally **AFN per kg** (constant 1/1,550,000 = 0.00064516/1000); no ×1000 / ×66 / gram↔kg unit bug.

### D — Sale integrity (AFN, exchange rate 66)
```
SaleItem count = 1   (exactly one line for product 163, bom 21, qty 1000)
cost_per_unit_usd = 0.0985      unit_price = 8.1290 AFN        sale_total = AFN 8129.03
Selling price = quotation net 6.5033 × 25% uplift = 8.129 AFN
```

### E — Production order snapshot (before FIFO)
```
POM: product 162   required = 60.9676 kg   unit = kg
cost_per_unit = 0.9500 USD   total = $57.92  (= 60.9676 × 0.95 = 57.9192)
```
Basis is kg × landed $/kg (not roll / $400-per-roll / 80 AFN / raw BOM quantity / zero cost).

### F — FIFO material consumption (start production)
```
Opening = 5000 kg   Consumed = 60.9676 kg   Remaining = 4939.0324 kg
Landed = $0.95/kg   Material USD = 60.9676 × 0.95 = $57.9192  (PMC total = 57.9192)
Batch #40 (QA-DEMO-S2BF) traced correctly
```

### G — Actual production cost
```
Material USD = $57.92   Material AFN = 3822.67
Labour = 0   Overhead = 0   Other = 0   (standard 40% used)
Work = materialAFN × 40% = 1529.07 AFN
Actual production = 3822.67 + 1529.07 = AFN 5351.73 (≈ expected 5351.74)
```

### H — Estimate vs actual (should be near-identical; planned kg = FIFO kg, expected = actual landed)
```
Estimated material 3822.72   Actual 3822.67      Δ −0.05
Estimated work      1529.09   Actual 1529.07      Δ −0.02
Estimated cost      5351.81   Actual 5351.73      Δ −0.07 AFN  (pure rounding → PASS)
```

### I — Commercial vs actual (two separate cost concepts, reported separately)
```
Commercial quotation = AFN 6503.23
Actual production    = AFN 5351.73
Difference explained by commercial 80 AFN/kg vs landed 62.70 AFN/kg (+5% wastage), not a costing variance.
```

### Step 9 — Exact profit
```
Revenue          = AFN 8129.03
Actual cost      = AFN 5351.73
Profit           = AFN 2777.30      (reported 2777.3)
Margin           = 34.17%
```
Coherent realistic business margin (commercial pricing 80 AFN/kg over landed 62.70 AFN/kg), **not** forced/manipulated.

### J — Traceability (full chain linked)
```
Material #162 'QA Demo Kraft Paper 150 GSM'
  → Purchase #36 'PQA-D-20260902-COMU' → batch #40 (landed 0.95 USD/kg)
  → Finished #163 → BOM #21 → BOMItem #21 (per_gram_rate 80)
  → Sale #73 (production_order_id 77, is_produced 1) → SaleItem #73
  → PO #77 'PROD-2026-Q9N6FNOZ' (completed, 1000 produced)
  → POM #91 (required 60.9676 kg, cost 0.95)
  → PMC #28 (po=77 sale=73 material=162 batch=40 actual 60.9676 total $57.9192)
Chain linked: PASS
```

---

## Final mathematical reconciliation
```
1  base/kg            = 0.80     5  actual landed AFN/kg = 62.70
2  expense/kg         = 0.15     6  BOM base kg/1000     = 58.0644
3  landed USD/kg      = 0.95     7  planned kg (+5%)     = 60.9676
4  commercial rate    = 80 AFN   8  FIFO kg              = 60.9676
9  FIFO USD           = 60.9676 × 0.95 = $57.9192   (PMC = 57.9192)
10 material AFN       = 3822.67
11 work (mat × 40%)   = 1529.07
12 actual production  = 5351.74
13 profit             = 8129.03 − 5351.73 = AFN 2777.30
14 margin             = 34.17%
```

---

## Regression gate
- `php artisan view:cache` → `Blade templates cached successfully` (prior label-only UI edits intact).
- No code, schema, costing-formula, or historical-data changes were made in this QA.
- The prior QA-Final transaction is retained; this realistic QA transaction is retained per instructions (no cleanup on PASS).

---

## Conclusion
All ten checkpoints (A–J) **PASS**. The realistic end-to-end costing is fully reconciled: landed $0.95/kg → BOM 60.9676 kg/1000 → FIFO 60.9676 kg → material $57.9192 (AFN 3,822.67) → work 1,529.07 → actual production **AFN 5,351.73** → profit **AFN 2,777.30 (34.17%)**. Estimate and actual agree to within AFN 0.07 (rounding). The earlier 98.67% margin is now replaced by a realistic ~34% margin arising from commercially correct paper pricing (80 AFN/kg vs 62.70 AFN/kg landed) — no arithmetic or unit bug.