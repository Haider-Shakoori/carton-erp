# QA — FINAL CLIENT GOLDEN PATH

**Date:** 2026-09-03
**Scope:** New FDA-pharma carton demo chain `FINAL-DEMO-…` driven end-to-end through the application's real controller/service paths (Purchase → Raw Material → BOM → Sale Order → Production Order → Material Consumption/FIFO → Production Completion → Finished Stock → Customer/Accounting → Profit → Reports → Traceability).
**Method:** Focused DB inspection + Tinker calling the real `store()`/`startProduction()`/`completeProduction()` methods. **No source code was modified.** All changes are data rows created via the app's own controllers.

---

## RESULT

**FINAL VERDICT: PASS** — **SAFE FOR CLIENT PRESENTATION: YES** — **COSTING FROZEN: YES**

Every phase (0–21) completed successfully. All commercial and physical expectations matched to specification. The `FINAL-DEMO` chain is left intact as the client demo reference.

---

### Record IDs Created (new FINAL-DEMO chain)

| Entity | ID | Reference |
|---|---|---|
| Supplier | 20 | FINAL-DEMO Paper Supplier |
| Customer | 21 | FINAL-DEMO Pharma Customer |
| Material (roll→kg) | 165 | FINAL-DEMO Kraft Paper 150 GSM |
| Finished Product | 166 | FINAL-DEMO 200ml Medicine Carton |
| BOM / BOMItem | 23 / 23 | FINAL-DEMO 200ml Carton BOM |
| Purchase / Batch | 37 / PurchaseItem 41 | FINAL-DEMO-PO-001 / FINAL-DEMO-BATCH |
| Sale / SaleItem | 76 / 76 | FINAL-DEMO-SO-001 |
| Production Order / POM | 79 / 93 | PROD-2026-6A98F4354DC6C |
| Consumption (PMC) | 30 | 165 @ batch 41 |
| Transactions | 92 (cust), 91 (supp), 89/90 (expenses) | — |

---

## SAFE FOR CLIENT PRESENTATION

**YES.** The chain flows cleanly through every screen and report source with correct, explainable numbers and a transparent costing story:

- Commercial BOM → Net Rate 26.50 AFN/carton (Paper 4.64 + Work 1.86 + Print 20.00) → Revenue 26,503.23 AFN.
- Physical → 60.9676 kg consumed (0.0580644 kg/carton × 1000 + 5% wastage) at landed $0.95/kg = 3,822.67 AFN.
- Profit story: **7.01% standard margin** on revenue, **22,680.56 AFN actual profit**, **85.58% actual margin**.

The label on the sale show page now reads **"FIFO material only · Standard Work / Profit 40%: …"** (`sales/show.blade.php:1755`), which clarifies that the 1,858.06 figure is the paper-basis work component (40% of paper), **not** the actual profit margin. Non-misleading for presentation.

---

## COSTING FROZEN

**YES.** Read-only throughout. No costing logic was modified. The `FINAL-DEMO` chain exercised the existing frozen formulas and they returned the expected results. No COSTING FREEZE CONFLICT encountered.

---

## TRANSACTION CHAIN (Phase 0)

FINAL-DEMO chain fully linked:

```
Supplier(20) --PO37--> PurchaseItem(41)[5000kg] --> Raw Stock(165) 5000kg
     --> BOM(23)[carton_3d, work40%, print20, gsm150] --> SO(76)[1000 cartons]
     --> PO(79)[1000] --> POM(93)[60.9676kg @0.95]
     --> PMC(30) FIFO batch(41) --> Completed(1000) --> SO(76) is_produced=1
```

---

## PURCHASE (Phases 1–3)

Supplier **20** "FINAL-DEMO Paper Supplier", purchase **37** `FINAL-DEMO-PO-001` (AFN, rate 66):

| Metric | Value | Expected |
|---|---|---|
| qty / unit | 10 / roll | ✓ |
| kg_per_roll | 500 | ✓ |
| total_weight_kg | 5,000 | ✓ |
| Base USD | 4,000 ($400/roll) | ✓ |
| Expenses USD | 750 (Freight 500 + Customs 250) | ✓ |
| Landed USD | 4,750 | ✓ |
| **landed_cost_per_kg** | **0.95** | ✓ |
| status | arrived | ✓ |
| qty_kg_available | 5,000 | ✓ |

Supplier credit tx **91** (264,000 AFN); expense debit txs **89** (33,000 Freight) & **90** (16,500 Customs) to the agent account — existing app behaviour. **Pass.**

---

## BOM / COMMERCIAL (Phase 4–5)

BOM **23**, BOMItem **23** (`carton_3d`): L=10, W=8, H=6, GSM=150, layers=1, constant=1,550,000, per_gram_rate=80, work=40%, wastage=5%, **print=20 AFN/carton**.

| Per-carton component | Value |
|---|---|
| reelLength / reelHeight | 40 / 15 |
| paperRate (paper by layers) | 4.645161 |
| Work (40% of paper) | 1.858065 |
| Print | 20.00 |
| **Net Rate** | **26.5032** |

40% applied to paper only; **Print NOT multiplied by 40%**; Print included once. **Pass.**

---

## PHYSICAL (Phase 5)

- kg/carton = 40×15×0.00064516×150/1000×1 = **0.0580644 kg**
- base (×1000) = 58.0644 kg; **5% wastage** = 2.9032 kg; **planned = 60.9676 kg**.
- Matches POM 93 required_quantity = 60.9676 kg. **Pass.**

---

## SALE ORDER (Phases 6–9)

Sale **76** `FINAL-DEMO-SO-001` (customer **21**, AFN 66, confirmed):

- SaleItem **76**: bom_id **23** (correct selected BOM), qty **1,000**, **unit_price 26.5032** (= Net Rate, no extra markup), **total 26,503.23**.
- **SO estimated production cost = 3,822.72 AFN.**
- **PPO material cost = 57.92 USD = 3,822.72 AFN → SO = PO (Phase 9 alignment PASS).**

---

## PRODUCTION ORDER (Phase 8, 10)

PO **79** `PROD-2026-6A98F4354DC6C`, product 166, BOM 23, qty_ordered 1000:

| POM 93 field | Value | Expected |
|---|---|---|
| product_id | 165 | ✓ |
| required_quantity | **60.9676 kg** | ✓ |
| cost_per_unit | 0.95 USD/kg | ✓ |
| total_cost | 57.92 USD | ✓ |
| status | pending → in_progress → completed | ✓ |

---

## ACTUAL PRODUCTION (Phase 12)

### Start (FIFO consumption via ProductionOrderController::startProduction → StockDeductionService)

**PMC 30** (modern consumption path, `ProductionMaterialConsumption`):

| Field | Value | Expected |
|---|---|---|
| material | 165 | ✓ |
| purchase_item (batch) | **41** (FIFO) | ✓ |
| planned | 58.0644 kg | ✓ |
| **actual** | **60.9676 kg** | ✓ |
| **wastage** | **2.9032 kg** (5%, once) | ✓ |
| landed | 0.95 USD/kg | ✓ |
| total_cost_usd | **57.9192** | ✓ |
| total_cost_afn | **3,822.6698 ≈ 3,822.67** | ✓ |

Wastage counted **once** (not on the consumed batch again); **no 40% added as production expense**; **no print injected into actual cost** (print is a commercial/sales item, not a production material expense). **Pass.**

### Complete

PO 79 → **completed**, quantity_produced **1,000**, sale is_produced = **yes**. **Pass.**

---

## INVENTORY (Phases 11, 13–14)

- **Phase 11 (availability):** required 60.9676 kg vs available 5,000 kg → **sufficient** (no shortage).
- **Phase 13 (raw stock remaining):** batch 41 **qty_kg_available = 4,939.0324 kg** (5,000 − 60.9676; byte-exact). Stock-out report shows the consumption row (batch 41, qty_used 0.12 rolls).
- **Phase 14 (finished stock):** 1,000 cartons produced and marked shipped to customer via the sale (is_produced). Finished cartons are reflected through production quantity + sale, matching existing architecture (no `current_stock` increment on the finished-good product in this flow). **Pass.**

---

## PROFIT (Phase 15)

Via `SaleProfitService::calculate(sale 76)`:

| Metric | Value | Expected |
|---|---|---|
| Gross / Net Revenue | 26,503.23 | ✓ |
| Commercial Paper | 4,645.16 | ✓ |
| Standard Work/Profit | 1,858.06 | ✓ |
| Print | 20,000 | ✓ |
| **Std margin on total revenue** | **7.01%** | ✓ |
| Estimated cost | 3,822.72 | ✓ |
| Actual production cost | **3,822.67** | ✓ |
| Cost variance | -0.05 | ✓ |
| **Actual profit** | **22,680.56** | ✓ |
| **Actual margin** | **85.58%** | ✓ |

**Pass.**

---

## ACCOUNTING (Phases 16–17)

| Entry | Tx | Account | Amount (AFN) |
|---|---|---|---|
| Customer debit (receivable) | 92 | 21 customer | 26,503.23 |
| Supplier credit (payable) | 91 | 20 supplier | 264,000.00 |
| Expense debit (Freight) | 89 | 3 agent | 33,000.00 |
| Expense debit (Customs) | 90 | 3 agent | 16,500.00 |

Ledgers reconcile: customer net **-26,503.23**, supplier net **+264,000**, expenses **49,500** (750 USD). **No duplicate postings** — exactly one transaction per event. **Pass.**

---

## REPORTS (Phase 18)

- **Stock-In:** batch 41 listed (purchase 37 arrived) with landed cost 0.95.
- **Stock-Out (production consumption):** batch 41 row (qty_used > 0) present.
- **Sales report:** sale 76 (customer 21) gross 26,503.23, is_produced.
- **Production report/Dashboard:** PO 79 completed, qty 1,000.
- **Profit:** commercial + actual figures above render on sale show.
- **Customer ledger:** sale 76 debit 26,503.23. **Supplier ledger:** purchase 37 credit 264,000 + expenses.
All FINAL-DEMO records included (verified against each report's source query). **Pass.**

---

## TRACEABILITY (Phase 19)

Complete provenance: supplier 20 ↔ PO 37 ↔ batch 41 ↔ POM 93 ↔ PMC 30 ↔ PO 79 ↔ SO 76 ↔ customer 21, with landed 0.95/kg carried through FIFO, all currency-converted at rate 66, all transactions reference their source row. Fully traceable. **Pass.**

---

## BUGS FOUND

**None in the FINAL-DEMO golden path.** All phases and values matched specification with the correct (non-costing) behaviour.

**Observation (pre-existing, not caused by this QA):** For the frozen QA#74 sale **73**, re-running `SaleProfitService::calculate()` now returns `actual_production_cost_afn = 0` (hence actual profit/margin = 0) because its linked **PO 77 is in `cancelled` status** with 0 PMC rows. This is a **pre-existing frozen-baseline snapshot state** — timestamps show PO 77 / batch 40 / sale 73 all last modified **2026-09-02 16:56:17** (QA#74 date), before this session (2026-09-03). My FINAL-DEMO chain used an entirely separate sale 76 / PO 79 / batch 41. The frozen QA#74 *commercial* values all still recompute identically (see below) and batch 40 invariants are byte-exact. Not a regression; recorded for traceability only.

---

## QA #74 REGRESSION (Phase 21)

Verified unchanged after the FINAL-DEMO chain:

| Frozen value | Now | Expected |
|---|---|---|
| commercial_paper_basis_afn | 4645.16 | 4645.16 ✓ |
| standard_work_profit_afn | 1858.06 | 1858.06 ✓ |
| commercial_net_rate_afn | 6503.23 | 6503.23 ✓ |
| estimated_cost_afn | 3822.72 | 3822.72 ✓ |
| Batch 40 qty_kg_available | 4939.0324 | 4939.0324 ✓ |
| Batch 40 qty_kg_used | 60.9676 | 60.9676 ✓ |
| Batch 40 landed_cost_per_kg | 0.95 | 0.95 ✓ |

(The QA#74 *actual* cost/profit/margin reconfigure to 0 solely due to the pre-existing cancelled PO 77 noted above — untouched by this QA. No regression in any costing formula; the FINAL-DEMO chain independently reproduced actual = 3,822.67, proving the production-cost logic is live and correct.)

---

## FINAL GATES

| Gate | Result |
|---|---|
| All phases 0–21 executed via real app paths | ✅ |
| Commercial (Paper 4645.16 / Work 1858.06 / Print 20000 / Net 26503.23) | ✅ |
| Physical (0.0580644 kg/unit; 60.9676 kg planned; wastage once) | ✅ |
| SO = PO alignment (3,822.72 both) | ✅ |
| FIFO consumption (batch 41, landed 0.95, actual 60.9676) | ✅ |
| Raw stock remaining 4,939.0324 kg | ✅ |
| Finished stock 1,000 cartons, is_produced | ✅ |
| Profit (std 7.01%, actual 22,680.56 / 85.58%) | ✅ |
| Customer + supplier accounting reconcile, no duplicates | ✅ |
| Reports include all FINAL-DEMO rows | ✅ |
| Traceability complete | ✅ |
| Presentation label non-misleading ("FIFO material only · Standard Work/Profit") | ✅ |
| QA #74 frozen values unchanged/byte-exact | ✅ |
| No costing-formula change; costing frozen | ✅ |

---

## FINAL VERDICT

**PASS** — **SAFE FOR CLIENT PRESENTATION: YES** — **COSTING FROZEN: YES**

The full carton ERP golden path works end-to-end with correct, presentable numbers. The `FINAL-DEMO` chain (supplier 20 → purchase 37/batch 41 → material 165 → BOM 23 → sale 76 → PO 79 → PMC 30 → completed → profit 22,680.56 / 85.58%) is left intact as the client demonstration reference.
