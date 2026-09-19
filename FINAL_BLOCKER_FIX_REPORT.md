# Final Blocker Fix Report

## Result

READY

## Blocker 1 — Sale Item Profit

**Root cause:** The sale item row calculated the correct canonical USD profit but displayed the stale persisted `profit_usd` field.

**Files changed:**

- `resources/views/admin/sales/show.blade.php`

**Expected:** Revenue $1.38 - COGS $0.99 = profit $0.39; AFN profit approximately ؋25.74; margin 28.3%.

**Actual:** Sales list and detail show $1.38 revenue, $0.99 COGS, $0.39 USD / ؋25.74 item profit, and 28.3% margin.

**PASS**

## Blocker 2 — Production Cost

**Root cause:** The production controller calculated BOM-estimated cost while the header independently read stale stored production totals. Completed production was not consistently using FIFO consumption as the displayed actual cost.

**Files changed:**

- `app/Http/Controllers/Admin/ProductionOrderController.php`
- `resources/views/admin/production-orders/show.blade.php`

**Expected:** Actual FIFO cost $0.2751 × 66 = ؋18.16 everywhere; BOM breakdown remains identified as estimated.

**Actual:** Production header, Actual FIFO Costs summary, actual material footer, and sale actual production cost show $0.28 / ؋18.16. BOM rows are labeled `Estimated BOM Material Breakdown`.

**PASS**

## Blocker 3 — Payment Application

**Root cause:** The general transaction form did not accept a sale reference and transaction storage never updated sale payment/due fields.

**Files changed:**

- `app/Http/Controllers/Admin/TransactionsController.php`
- `resources/views/admin/transactions/create.blade.php`

**Expected:** One linked 0.01 AFN payment; paid ؋0.01; remaining ؋91.29; no duplicate linked payment.

**Actual:** `QA_FIX linked payment for SO-202608-0009` appears once, references the sale internally, and the invoice shows paid ؋0.01 and due ؋91.29. The prior generic QA transaction remains unchanged and unlinked.

**PASS**

## Blocker 4 — Ledger Running Balance

**Root cause:** The controller assigned `running_balance` but the view displayed nonexistent `balance`; the calculation also processed newest-first rows instead of calculating chronologically.

**Files changed:**

- `app/Http/Controllers/Admin/CustomerController.php`
- `resources/views/admin/customers/partials/transactions-table.blade.php`

**Expected:** Credit increases and debit decreases the balance under the ERP convention; latest row reconciles to the summary.

**Actual:** Every visible row has a chronological running balance. The latest linked payment changes -2,742.61 to -2,742.60, reconciling with the displayed 2,742.60 Dr summary.

**PASS**

## Regression Check

| Area | Result |
|---|---|
| PO-0017 | PASS |
| Stock | PASS |
| BOM-DIHIIIVJ | PASS |
| Production material breakdown | PASS |
| Invoice | PASS |

No browser JavaScript errors or HTTP presentation errors were observed during focused verification.

## Remaining Presentation Issues

None confirmed in the scoped presentation route.

## Presentation Readiness

READY
