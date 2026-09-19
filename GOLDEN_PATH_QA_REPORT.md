# Carton ERP Golden Path QA Report

## 1. Final Result

**FAIL**

One traceable transaction was executed through the normal UI. Purchase and raw-material stock posting reconciled. Sale confirmation posted the customer transaction, and production was completed using the manual production-order fallback. The golden path nevertheless fails because automatic production creation errors, production requirement displays contradict each other, actual production cost/profit is materially wrong, the known sales currency/profit defect remains, and the normal transaction Save action did not record payment.

## 2. Known Bug Retest

| Known issue | Status | Evidence |
|---|---|---|
| Sales list mixes AFN revenue with USD cost/profit | **FAIL** | SO-202608-0009 shows AFN 91.30 total, $1.38 USD total, and `$90.31 (9,122.2%)` profit. Correct estimated profit from displayed AFN revenue and AFN 65.34 COGS is AFN 25.96 (28.44%). |
| Exchange-rate direction | **FAIL** | Sale detail says `1 AFN = 66.0000 USD` in Order Details but `1 USD = 66.00 AFN` lower on the same page. |

## 3. QA Scenario

| Entity | QA selection |
|---|---|
| Supplier | Existing test supplier `Test` (`SUP-9201`) |
| Material | Kraft Paper 120 GSM, roll |
| Purchase | `PO-0017`; batch `QA_DEMO_BATCH_001`; 2 rolls × $5.00; $10.00 total; no expenses |
| BOM | Existing `BOM-DIHIIIVJ` — Excel v1.0, 3D Carton, 40% work |
| Customer | Existing test customer `Qadir` (`CUS-1328`) |
| Product | 120ml Syrup Box |
| Sale | `SO-202608-0009`; 1 box × AFN 91.2958 |
| Production | `PROD-2026-6A93E20EBA62E`; 1 ordered / 1 produced |

Only the purchase, sale, production order, stock movements and related ledger entries above were created. No important pre-existing record was edited or deleted.

## 4. Opening Values

| Area | Opening value |
|---|---:|
| Kraft Paper 120 GSM stock-in | 22,010.0000 rolls |
| Kraft Paper 120 GSM used | 382.7539 rolls |
| Kraft Paper 120 GSM available | 21,627.2461 rolls |
| Kraft Paper 120 GSM stock value | $110,898.31 |
| Finished 120ml Syrup Box stock | **NOT TESTED** — finished-goods catalog does not expose quantity and raw stock ledger does not list finished goods |
| Supplier Test balance | $116,689.95 credit/liability display |
| Customer Qadir AFN balance | AFN 2,651.32 DR |
| Current-period purchase orders | 10 total / 6 arrived |
| Current-period sale orders | 31 total |
| Production orders | 48 total / 17 completed |
| Transactions | 59 before this QA pass (60 after purchase, 61 after sale) |

The stock screen did not expose a meaningful latest-rate field. The new purchase item was explicitly posted at $5.00 per roll.

## 5. Purchase Verification

**PASS**

| Metric | Expected | Application | Difference |
|---|---:|---:|---:|
| Quantity | 2.0000 rolls | 2.0000 rolls | 0 |
| Unit cost | $5.00 | $5.00 | $0 |
| Base amount | $10.00 | $10.00 | $0 |
| Transportation | $0.00 | $0.00 | $0 |
| Other expense | $0.00 | $0.00 | $0 |
| Landed total | $10.00 | $10.00 | $0 |
| Landed cost/unit | $5.00 | $5.00 | $0 |

PO-0017 moved Draft → Shipping → Arrived through the UI. The arrival warning stated that arrival locks items and posts stock. The Purchase list shows PO-0017 as Arrived with one item, quantity 2, $10 total, and $0 expense.

Purchase effects:

- Available stock: 21,627.2461 + 2.0000 = 21,629.2461; application displayed 21,629.25 / underlying product quantity 21,629.2461.
- Supplier balance: $116,689.95 + $10.00 = $116,699.95; application $116,699.95.
- Transaction entry: `Purchase Order #PO-0017 - Credit for (1 items, 2 units)`, +$10.00 USD.

## 6. BOM Verification

**PASS** for the displayed 3D formula calculation; **PARTIAL** for downstream material displays.

Displayed inputs:

- Length 17.32 in; width 15.75 in; height 12.20 in
- Reel length 70.14; reel height 28.95
- GSM 125; per-gram rate 43.00; layers 1
- Formula constant 1,550,000; work 40%; exchange 1 USD = 65.99 AFN
- Material cost $0.98 / AFN 65.21; work AFN 26.08; selling/total AFN 91.30

Manual calculation from displayed values:

1. Reel length = `((17.32 + 15.75) × 2) + 4 = 70.14`.
2. Reel height = `15.75 + 12.20 + 1 = 28.95`.
3. Base = `70.14 × 28.95 × 125 × 43 / 1,550,000 = 7.04143306`.
4. Work = `7.04143306 × 40% = 2.81657323`.
5. Row net = `7.04143306 + 2.81657323 = 9.85800629`.
6. Application row net = 9.85800731; difference 0.00000102, consistent with hidden precision.

The production page later contradicts itself: Material Status requires 0.17194 roll for each of two rows, while Material Breakdown displays 1.0500 roll for each row.

## 7. Sale Verification

**FAIL**

SO-202608-0009 values:

| Metric | Expected | Application |
|---|---:|---:|
| Quantity | 1 | 1 |
| Unit price | AFN 91.2958 | AFN 91.2958 |
| Gross/net total | AFN 91.2958 | AFN 91.30 |
| Discount | AFN 0 | AFN 0 |
| Estimated COGS | AFN 65.34 | AFN 65.34 on detail |
| Estimated profit | 91.2958 − 65.34 = AFN 25.9558 | Header AFN 25.96; summary AFN 91.30 |
| Estimated margin | 25.9558 / 91.2958 = 28.44% | 9,122.2% |

The Sale list, Sale detail, and profit header do not agree. Invoice quantity and total agree with the sale, but do not include cost/profit.

## 8. Production Verification

**FAIL**

- Confirming SO-202608-0009 with Start Production selected committed the sale but displayed: `Sale confirmed, but production order creation failed: Call to undefined method App\Models\SaleItem::getProductionCostBreakdown()`.
- Manual fallback successfully created PROD-2026-6A93E20EBA62E for the correct sale, product, BOM and quantity.
- Start Production succeeded.
- Completion succeeded; status became Completed, ordered 1, produced 1, progress 100%.
- Expected requirement from the completion log/UI: two rows × 0.1719416440 = 0.3438832880 roll total, including total wastage 0.0163753947.
- Actual stock movement: 21,629.2461 → 21,628.9061 = 0.3400 at four-decimal display precision; underlying logged deduction was 0.3438832880.
- Production list reports total cost `$0.00`; production detail reports expected material cost AFN 65.14 / $0.99; sale detail reports actual production cost AFN 0.28.

## 9. Raw Material Reconciliation

**PASS** for quantity within display precision, but **FAIL** for requirement presentation.

| Item | Quantity (roll) |
|---|---:|
| Opening stock | 21,627.2461 |
| Purchased | +2.0000 |
| Planned consumption excluding wastage | −0.3275078934 |
| Wastage | −0.0163753947 |
| Expected closing | 21,628.9022167119 |
| Actual closing shown | 21,628.9061 |
| Difference | +0.0038832881 (caused by 4-decimal persistence/display rounding versus logged deduction) |

The application’s stock-in/used figures reconcile internally: stock-in 22,012.00 − used 383.09 = available 21,628.91. The Material Breakdown’s displayed 2.1000-roll total does not reconcile with the stock movement and must not be used for a demo calculation.

## 10. Finished Stock Reconciliation

**BLOCKED**

Production shows one finished unit produced. The finished-goods catalog lists the product but no stock quantity, and the Stock Inventory screen lists only four raw materials. No UI figure was available to prove `opening finished stock + 1 = closing finished stock`. Dispatch timing therefore also could not be independently verified.

## 11. Profit Reconciliation

**FAIL**

| Metric | Expected | Application | Difference |
|---|---:|---:|---:|
| Material cost (BOM estimate) | AFN 65.14 | AFN 65.14 production detail | 0 |
| Actual consumed cost | Must equal batch-cost valuation for 0.343883288 roll | AFN 0.28 on sale | Not reconcilable; materially inconsistent with production detail |
| Other cost | AFN 0 | AFN 0 | 0 |
| Revenue | AFN 91.2958 | AFN 91.30 | rounding only |
| Discount | AFN 0 | AFN 0 | 0 |
| Estimated profit | AFN 25.96 | Header AFN 25.96 | 0 |
| Detail/list profit | AFN 25.96 expected | AFN 91.30 / `$90.31` | materially wrong |
| Actual profit using sale-reported AFN 0.28 cost | AFN 91.02 | AFN 91.02 | calculation follows the suspect cost |
| Expected estimated margin | 28.44% | 9,122.2% | +9,093.76 percentage points |

The production list also reports $0.00 total cost for the completed order, contradicting production detail and sale actual cost.

## 12. Customer Ledger Reconciliation

**PARTIAL**

| Item | AFN |
|---|---:|
| Opening Qadir balance | 2,651.32 DR |
| Sale invoice debit | +91.2958 |
| Expected pre-payment closing | 2,742.6158 DR |
| Application pre-payment closing | 2,742.62 DR |
| Difference | rounding only |

The transaction list contains `Sale #SO-202608-0009 - Invoice amount AFN 91.30` with debit/minus direction. Payment could not be posted, so final paid-state reconciliation is blocked.

## 13. Supplier Ledger Reconciliation

**PASS**

| Item | USD |
|---|---:|
| Opening Test balance | 116,689.95 |
| PO-0017 credit | +10.00 |
| Expected closing | 116,699.95 |
| Application closing | 116,699.95 |
| Difference | 0.00 |

## 14. Payment Verification

**FAIL**

A full payment of AFN 91.2958 was prepared through Transactions for customer Qadir with credit direction and description `QA_DEMO full payment for SO-202608-0009`. The visible Save action was attempted semantically, by keyboard, and through the visible DOM action. It produced no transaction, no validation message, no navigation, and no browser console error.

- Expected paid: AFN 91.2958
- Actual paid: AFN 0.00
- Expected remaining: AFN 0.00
- Invoice remaining: AFN 91.30
- Expected post-payment customer balance: AFN 2,651.32 DR
- Actual customer balance: AFN 2,742.62 DR

## 15. Reports Verification

**PARTIAL / FAIL**

| Surface | Status | Result |
|---|---|---|
| Purchase list | PASS | PO-0017 appears Arrived, qty 2, $10.00. |
| Sales list | FAIL | SO-202608-0009 appears, but profit/margin is wrong. |
| Production list | FAIL | Order appears Completed, but total cost is $0.00. |
| Stock inventory | PASS for raw quantity | Purchase and production movements reconcile within persistence/display precision. |
| Transactions | PARTIAL | Purchase and sale entries appear; payment entry cannot be created. |
| Customer balance | PASS pre-payment | Sale effect reconciles to AFN 2,742.62 DR. |
| Supplier balance | PASS | Purchase effect reconciles to $116,699.95. |
| Dedicated profit report | NOT TESTED | No dedicated non-HR report navigation was exposed in the tested sidebar. |

## 16. Print/Invoice Verification

**PASS** for commercial values; **FAIL** for payment state because payment could not be posted.

The print view `/admin/sales/33/print` opens and shows:

- Invoice SO-202608-0009, Confirmed, August 30 2026
- Customer Qadir
- 120ml Syrup Box
- Quantity 1 box
- Unit price AFN 91.30
- Subtotal/grand total AFN 91.30
- Paid AFN 0.00; due AFN 91.30
- QA confirmation notes

These sale values match the sale detail.

## 17. Errors

- Backend/action error on sale confirmation: `Call to undefined method App\Models\SaleItem::getProductionCostBreakdown()`.
- Transaction Save: silent failure; no HTTP/console error surfaced and no record was created.
- No JavaScript ReferenceError/TypeError was observed on tested pages.
- No visible 404, 419, 422 or 500 page was returned.
- Production completion click caused the browser automation call to time out, but the server completed the action successfully; reload showed Completed.

## 18. Bugs Found

### BUG-GP-001 — CRITICAL — Sales profit mixes currencies

- **Route:** `/admin/sales`, `/admin/sales/33`
- **Steps:** Create/inspect AFN sale SO-202608-0009 with saved BOM; compare revenue, AFN COGS, profit and margin.
- **Expected:** AFN 91.30 − AFN 65.34 = AFN 25.96; margin 28.44%.
- **Actual:** Profit AFN 91.30 / `$90.31`; margin 9,122.2%.
- **Presentation impact:** Visibly impossible profitability.

### BUG-GP-002 — HIGH — Exchange-rate direction reversed

- **Route:** `/admin/sales/33`
- **Steps:** Inspect Order Details and lower Exchange Rate row.
- **Expected:** `1 USD = 66 AFN` consistently.
- **Actual:** One section says `1 AFN = 66 USD`; another says the reverse.
- **Presentation impact:** Direct financial contradiction.

### BUG-GP-003 — HIGH — Automatic production creation fails

- **Route:** sale confirmation for `/admin/sales/33`
- **Steps:** Confirm sale with Start Production checked.
- **Expected:** Confirm sale and create linked production order atomically.
- **Actual:** Sale commits; production creation fails with missing method error. Manual fallback is required.
- **Presentation impact:** Intended live golden path breaks and partially commits.

### BUG-GP-004 — CRITICAL — Production requirement displays contradict consumption

- **Route:** `/admin/production-orders/49`
- **Steps:** Inspect Material Status, Material Breakdown, then stock movement.
- **Expected:** All requirement representations equal actual planned consumption plus wastage.
- **Actual:** Status/stock uses about 0.17194 + 0.17194 rolls; breakdown shows 1.0500 + 1.0500 rolls.
- **Presentation impact:** Client cannot trust BOM-to-production quantities.

### BUG-GP-005 — CRITICAL — Actual production cost/profit is inconsistent

- **Route:** `/admin/production-orders/49`, `/admin/production-orders`, `/admin/sales/33`
- **Steps:** Complete production; compare production detail cost, list cost, and sale actual cost/profit.
- **Expected:** One consistent actual cost derived from consumed batches.
- **Actual:** Production detail AFN 65.14, production list $0.00, sale actual cost AFN 0.28, exact profit AFN 91.02.
- **Presentation impact:** Actual profit is materially unreliable.

### BUG-GP-006 — HIGH — Transaction/payment Save silently fails

- **Route:** `/admin/transactions`
- **Steps:** Select Customer → Qadir → AFN → Credit; enter 91.2958 and QA description; activate Save.
- **Expected:** Credit/payment transaction posts and customer balance returns to opening value.
- **Actual:** No record, no validation message, and no customer-balance change.
- **Presentation impact:** Payment and ledger demo cannot be completed.

## 19. Presentation-Safe Workflow

Safe read-only route using the QA identifiers:

Login → Dashboard → Purchase Orders → open PO-0017 (show 2 × $5 = $10, Arrived) → Stock Inventory (show Kraft opening/purchase/used/available arithmetic) → BOM → open BOM-DIHIIIVJ (show 3D dimensions and 40% calculation) → Customers (show Qadir balance only) → Transactions (show PO-0017 and SO-202608-0009 ledger entries) → Print Invoice SO-202608-0009.

If Production must be shown, open PROD-2026-6A93E20EBA62E and show only status/quantity (1 ordered, 1 produced, Completed). Do not discuss cost or material breakdown.

## 20. DO NOT DEMONSTRATE

- Do not demonstrate Sales list profit/margin or the Sale detail profit table.
- Do not explain the Sale exchange-rate label.
- Do not use Confirm Sale + Start Production live; it raises the missing-method error after committing the sale.
- Do not compare Production Material Status with Material Breakdown.
- Do not show production total cost, actual production cost, exact profit, or profit margin.
- Do not demonstrate customer payment from Transactions; Save silently fails.
- Do not claim finished-goods quantity was verified; no finished-stock quantity was exposed on the tested inventory/catalog screens.

## 21. Final Presentation Recommendation

**HIGH-RISK PRESENTATION**

Purchase, supplier liability, raw-material stock, BOM arithmetic, sale invoice, and pre-payment customer ledger can be demonstrated in a tightly scripted read-only route. The intended live sale-to-production path is unsafe, payment is broken, and cost/profit figures contradict each other across screens. Avoid all profitability, production-material-detail, automatic-production, and payment demonstrations.

No application source file, configuration, schema, package file, migration, seeder, or existing test was modified. Only this report was intentionally created.
