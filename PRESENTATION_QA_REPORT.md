# Carton ERP Presentation QA Report

## Executive Summary

- **Presentation readiness status:** HIGH-RISK PRESENTATION
- **Critical bugs:** 1
- **High bugs:** 1
- **Medium bugs:** 0 confirmed
- **Low bugs:** 0 confirmed
- **Major workflows tested:** 9 core screens/workflow segments (login/dashboard, customers, suppliers, products/materials, purchases, BOM, sales, production, stock, transactions/ledger)
- Browser QA was performed against the running Laravel application at `http://127.0.0.1:8000` using existing records. No application code or configuration was changed. No destructive command was run.
- Because financial output was already materially inconsistent between the Sales list and Sale detail, no new purchase/production chain was posted; mutation-heavy checks are therefore marked PARTIAL or BLOCKED rather than inferred as passing.

## Presentation Readiness

**HIGH-RISK PRESENTATION**

The application loads, login works, dashboard and core list/detail pages open, and no browser console errors were observed on the tested screens. However, Sales Orders presents materially wrong profit/margin figures on its main list by mixing AFN sales values with USD costs. This is highly visible in a client demo and conflicts with the corresponding sale detail.

## Critical Demo Workflow

| Step | Result | Evidence / limitation |
|---|---|---|
| Purchase | PARTIAL | Purchase list and Create Purchase action load; existing arrived orders show quantities, expenses and totals. No new purchase posted. |
| Raw Material Stock | PASS (read-only) | Inventory page loads and exposes stock-in, used, wasted, available and valuation. |
| BOM | PARTIAL | List and BOM detail load; one 3D carton BOM independently checked. Create/edit persistence was not posted. |
| Sale Order | FAIL | Detail loads, but the Sales list profit/margin conflicts materially with detail calculations. |
| Production | PARTIAL | Production list loads and shows pending/completed quantities and progress. No new completion posted. |
| Material Consumption | PARTIAL | Existing inventory usage is visible, but no new end-to-end consumption was posted. |
| Finished Stock | BLOCKED | Not safely reconciled for a newly created QA chain. |
| Ledger | PARTIAL | Transactions page loads and existing sale/purchase entries are present. No new chain posted. |
| Profit | FAIL | Main Sales list mixes currencies and displays implausible profit/margins. |
| Reports | BLOCKED | Dedicated report permutations were not reached in the time-box after the profit defect was confirmed. |

## BOM Verification

Tested existing BOM: **BOM-DIHIIIVJ — Excel v1.0**, product **120ml Syrup Box**, formula type **3D Carton**.

Inputs shown by the application:

- Length: 17.32 in
- Width: 15.75 in
- Height: 12.20 in
- Reel length: 70.14 in
- Reel height: 28.95 in
- GSM: 125
- Per gram rate: 43.00
- Layers: 1
- Formula constant: 1,550,000
- Work percentage: 40.00%
- Exchange rate: 1 USD = 65.99 AFN
- Materials: $0.82 + $0.16 = $0.98 (display-rounded)

Manual formula check using displayed inputs:

1. Reel length = `((17.32 + 15.75) × 2) + 4 = 70.14`.
2. Reel height = `15.75 + 12.20 + 1 = 28.95`.
3. Division value = `70.14 × 28.95 × 125 × 43 = 10,914,221.25` (application displays 10,914,222.38; difference 1.13, consistent with hidden input precision).
4. Paper rate = `10,914,221.25 / 1,550,000 = 7.04143306` (application 7.04143379; difference 0.00000073).
5. Work amount = `7.04143306 × 40% = 2.81657323`.
6. Row net rate = `7.04143306 + 2.81657323 = 9.85800629` (application 9.85800731; difference 0.00000102).
7. Material cost shown: $0.98 / AFN 65.21; work cost AFN 26.08; total AFN 91.30.

**Result:** PASS for the displayed 3D formula within hidden-precision/rounding tolerance. The implemented default 40% behavior is visible. Syrup-specific formula creation was not independently posted, so it remains unverified.

## Sale Order Verification

Tested existing sale **SO-202608-0008**, customer **Qadir**, currency **AFN**, one unit of **120ml Syrup Box**, manual-BOM quotation based on BOM-DIHIIIVJ.

- Sale total: AFN 120.99
- COGS: AFN 120.78 ($1.83 reference)
- Expected profit from displayed rounded totals: `120.99 - 120.78 = AFN 0.21`
- Detail header estimated profit: AFN 0.21
- Detail summary profit: AFN 0.00 / 0.0% (higher precision apparently rounds the result differently)
- Sales list for other AFN orders displays USD cost against AFN selling total and calculates implausible margins, e.g. SO-202608-0005: AFN 91.30, cost $1.38, displayed profit $90.31 and 9,122.2%.

**Result:** FAIL due to inconsistent currency treatment and profit output between list and detail.

## Production Verification

The Production Orders page loaded with 48 orders. Existing records displayed correct-looking progress relationships such as 100 ordered / 100 produced / 100% / Completed and 100 ordered / 0 produced / 0% / Pending. JavaScript console warnings/errors were not observed on page load.

No new production order was started or completed, so material requirements, actual batch consumption, completion action and resulting finished inventory are **PARTIAL/BLOCKED** rather than passed.

## Stock Reconciliation

Read-only inventory identities shown by the application reconcile arithmetically:

| Material | Opening/Stock In | Purchased in this QA | Consumed/Used | Wasted | Expected closing | Application closing |
|---|---:|---:|---:|---:|---:|---:|
| Kraft Paper 120 GSM | 22,010.0000 | 0 | 382.7539 | 0 | 21,627.2461 | 21,627.2461 |
| Kraft Paper 150 GSM | 10.0000 | 0 | 0 | 0 | 10.0000 | 10.0000 |
| Hot Melt Glue | 400.0000 | 0 | 399.0000 | 0 | 1.0000 | 1.0000 |
| Testliner Paper | 1,000.0000 | 0 | 0 | 0 | 1,000.0000 | 1,000.0000 |

No new QA purchase/production posting was made, so cross-module reconciliation for a new chain is BLOCKED.

## Profit Verification

For SO-202608-0008:

| Metric | Expected | Application |
|---|---:|---:|
| Cost | AFN 120.78 | AFN 120.78 ($1.83) |
| Selling price | AFN 120.99 | AFN 120.99 |
| Profit | AFN 0.21 from rounded displayed values | AFN 0.21 in header; AFN 0.00 in summary |
| Difference | — | AFN 0.21 internal display discrepancy |

The more dangerous issue is the Sales list, where an AFN selling amount is visibly compared directly with a USD cost, producing margins above 9,000%. **FAIL.**

## Financial/Ledger Verification

Transactions loads and shows existing linked records, including sale invoice amounts, cash receipts, supplier purchase credit, and purchase expenses. PO-0016 appears with a supplier credit and separate purchase-related expense entries. Customer and supplier list balances load.

Focused consistency status: **PARTIAL**. No QA-created transaction was posted, and running-balance/paid/remaining values for a new chain were not independently reconciled.

## Presentation Buttons

- Confirmed present/loadable: Login, dashboard navigation links, New Customer, New Supplier, Add Raw Material, Add Finished Good, New Purchase Order, Create BOM, BOM View, BOM Edit link, Sale View, Print Invoice button presence, production-order detail links, inventory Search/IN/OUT actions, transactions Filter/Export/Print buttons.
- Not executed because of data mutation/destructive impact: Delete, Archive BOM, Confirm Sale, Complete Production, inventory IN/OUT posting.
- No button was confirmed to do nothing during the executed navigation checks.

## JavaScript / HTTP Errors

- No JavaScript `ReferenceError` or `TypeError` was observed in browser logs on the tested pages.
- No visible 404, 419, 422 or 500 response occurred during tested navigation.
- Configuration risk observed outside the browser: the application reports **production environment with debug mode enabled**. This did not cause a tested failure but could expose detailed errors if a demo action fails.

## Bugs

### BUG-001

- **Severity:** CRITICAL
- **Module:** Sales / Profit
- **Page:** `/admin/sales`
- **Steps:** Log in; open Sales Orders; inspect an AFN order such as SO-202608-0005 or SO-202608-0006; compare its total, cost and profit/margin with the detail page.
- **Expected:** Convert cost and revenue to one currency before subtracting and calculating margin; list and detail must agree.
- **Actual:** The list presents AFN sale totals beside USD costs and computes profit as though currencies were identical. Example: AFN 91.30, cost $1.38, profit $90.31, margin 9,122.2%. Detail pages use converted COGS and show approximately 0% for comparable values.
- **Error:** Financial calculation/display currency mismatch; no console exception.
- **Presentation impact:** A client will immediately see impossible margins and lose confidence in all costing/profit reports.

### BUG-002

- **Severity:** HIGH
- **Module:** Sales / Currency
- **Page:** `/admin/sales/32` (SO-202608-0008 detail)
- **Steps:** Open sale SO-202608-0008; inspect Order Details exchange-rate label and compare with the AFN & Profit section.
- **Expected:** Consistent label: `1 USD = 66.00 AFN`.
- **Actual:** Order Details says `1 AFN = 66.0000 USD`; lower on the same page the correct direction is shown as `1 USD = 66.00 AFN`.
- **Error:** Reversed exchange-rate label.
- **Presentation impact:** Highly visible financial contradiction during a sale-order demonstration.

## What NOT to Demonstrate

- **Avoid the Sales Orders list profit and margin columns.** BUG-001 shows implausible margins above 9,000% for AFN sales.
- **Avoid explaining exchange rates from Sale Order detail.** BUG-002 reverses the currency direction in Order Details.
- Avoid claiming exact profit is final before production consumption; the application itself marks it estimated/pending.
- Avoid live Confirm Sale, Complete Production, material IN/OUT, Archive BOM or Delete actions; these were not executed in this read-only risk-focused pass.
- Avoid claiming a complete purchase-to-production-to-ledger reconciliation was QA-passed; it was not safely posted end to end.

## Recommended Demo Route

Safest exact tested sequence:

Login → Dashboard → Customers list → Suppliers list → Products (Raw Materials tab) → Purchase Orders list → open BOM-DIHIIIVJ details and explain the 3D formula/40% work calculation → Production Orders list (show existing progress only) → Stock Inventory (show stock-in/used/available arithmetic) → Transactions (show existing linked sale/purchase descriptions).

Do **not** open or dwell on Sales Orders profit/margin columns. If a sale must be shown, keep the discussion to customer/product/quantity and explicitly avoid the exchange-rate and profit blocks.

## Final Presentation Assessment

Basic health, authentication, navigation, master-data lists, BOM detail, production overview, stock ledger and transaction list are safe for a controlled read-only demonstration. The presentation is high risk if sales profitability is shown: the main Sales list visibly mixes AFN and USD and conflicts with detail output. Keep the demo tightly scripted along the recommended route, avoid profit/exchange-rate claims, and do not perform unverified mutation actions live.
