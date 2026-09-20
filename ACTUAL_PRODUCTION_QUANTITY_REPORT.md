# ACTUAL PRODUCTION QUANTITY — IMPLEMENTATION & QA REPORT

**Project:** Carton ERP  
**Branch:** `feat/actual-production-quantity-20260920`  
**Verified runtime SHA:** `36b3c578560e7dffcf8cbb02130f55b4e43a7513`  
**Verification date:** 2026-09-20  
**Status:** **READY FOR REVIEW / MERGE**

---

## 1. Client Business Rule Implemented

The production workflow no longer assumes that finished quantity must equal the customer order quantity.

The system now keeps three separate concepts:

1. **Ordered quantity** — the customer's original requested quantity.
2. **Raw-material-supported quantity at production start** — how much current stock can support.
3. **Actual quantity produced** — the real finished quantity entered by the operator when production ends.

Actual production may therefore be:

- lower than the customer order;
- exactly equal to the customer order; or
- higher than the customer order.

The final sale invoice follows the **actual quantity produced**, while the original order quantity remains preserved for traceability.

---

## 2. Production Start Behavior

At **Start Production**:

- the production order must be pending;
- the original `quantity_ordered` remains unchanged;
- the system calculates raw-material requirement per finished unit;
- current arrived stock is evaluated in the correct inventory unit;
- roll-based paper is evaluated in kilograms;
- the maximum currently producible finished quantity is calculated from the limiting raw material;
- allocation is capped at the lesser of:
  - customer ordered quantity; and
  - quantity current stock can support;
- a raw-material shortage no longer automatically blocks the production order;
- if stock can support only part of the order, production starts as a **partial production run**;
- FIFO raw-material consumption is recorded in `production_material_consumptions`;
- the frozen `production_order_materials` planning snapshot remains immutable.

This means a 10,000-carton order can validly start with raw material sufficient for only 7,500 cartons.

---

## 3. Production Completion Behavior

At **Complete Production**, the UI now requires:

`Actual Quantity Produced`

The operator enters the real finished output.

Examples:

- Ordered: 10,000 — Actual: 9,350
- Ordered: 10,000 — Actual: 10,000
- Ordered: 10,000 — Actual: 10,420

The production-order screen now shows:

- Ordered Quantity
- Produced Quantity
- Order Fulfillment
- Production Variance

Completion status is based on workflow state, not on whether produced quantity reached the ordered quantity. Therefore an under-produced order can still be validly **Completed**.

---

## 4. Raw-Material Reconciliation

### Actual output lower than allocated quantity

When actual production is lower:

- material consumption is recalculated to the real output;
- the unused portion is restored to the original purchase batches;
- roll stock restores both kg and equivalent roll counters;
- FIFO traceability remains intact;
- production-material consumption rows are reduced/deleted as required;
- material cost is recalculated from the remaining real consumption.

Example:

```
Ordered / allocated: 100 cartons
Actual produced:       80 cartons

Material consumption after completion
= 80% of the original 100-carton material requirement
```

### Actual output higher than ordered quantity

When actual output is higher:

- the system calculates additional raw material required;
- current remaining stock is checked;
- additional material is deducted using FIFO;
- if additional stock is insufficient, completion is rejected and the production order remains in progress;
- if stock is sufficient, the additional consumption becomes part of the production's actual cost.

Example:

```
Ordered:          100 cartons
Started for:      100 cartons
Actual produced:  120 cartons

Additional raw material required
= requirement for 20 extra cartons
```

---

## 5. Final Invoice Behavior

A new `sale_items.ordered_qty` field preserves the original sales-order quantity.

Existing sale items are backfilled during migration:

```
ordered_qty = existing qty
```

New sale items save `ordered_qty` immediately.

When production completes:

- `ordered_qty` stays unchanged;
- invoice `qty` becomes the actual produced quantity;
- line total is recalculated from actual quantity × sale unit price;
- USD line total is recalculated;
- line discounts/taxes are proportionally resized where present;
- actual production material cost becomes the final line COGS;
- item profit is recalculated;
- sale subtotal is recalculated;
- sale grand total is recalculated;
- due balance is recalculated;
- USD totals/due are recalculated;
- the customer invoice debit transaction is updated to the final invoice amount;
- the sale is marked produced.

### Example — under-production

```
Customer ordered: 10,000
Actual produced:    9,350

sale_items.ordered_qty = 10,000
sale_items.qty         = 9,350

Final invoice quantity = 9,350
```

### Example — over-production

```
Customer ordered: 10,000
Actual produced:   10,420

sale_items.ordered_qty = 10,000
sale_items.qty         = 10,420

Final invoice quantity = 10,420
```

---

## 6. Customer Payments / Overpayment

Existing payments are not deleted or rewritten when the invoice becomes smaller.

If prior payments exceed the revised final invoice:

- sale due is never displayed as a negative amount;
- the excess remains as customer credit in the transaction ledger.

This preserves accounting history rather than silently discarding or altering received payments.

---

## 7. Sale Production Flow Change

The old shortcut that could automatically start **and complete** production has been removed from the normal sale flow.

Production completion now requires an explicit real produced quantity.

The sale production action now:

1. creates the production order;
2. starts material allocation/consumption;
3. redirects to the production order;
4. waits for the operator to enter real output at completion.

---

## 8. UI Changes

The production-order page now includes a completion modal with:

- Customer Ordered quantity
- Actual Quantity Produced input
- explanation that output may be lower or higher
- warning that completion reconciles raw-material consumption
- warning that linked sale invoice quantity, amount, customer balance and profit are recalculated
- **Save Actual Output & Complete** action

Validation failures reopen the modal safely.

The Start Production confirmation now explicitly explains that:

- current raw material is allocated;
- partial production is allowed when stock cannot cover the full order;
- the real finished quantity is entered at the end.

---

## 9. Main Code Changes

### New service

`app/Services/ProductionQuantityService.php`

Responsibilities:

- calculate maximum producible quantity;
- allocate production from available stock;
- reconcile actual output;
- restore unused material;
- consume additional material;
- finalize actual production costs;
- resize final invoice;
- update accounting invoice transaction.

### Updated

- `StockDeductionService`
- `ProductionService`
- `ProductionOrderController`
- `SaleController`
- `ProductionOrder`
- `SaleItem`
- production-order show UI

### Database

New migration:

`2026_09_20_073000_add_ordered_qty_to_sale_items.php`

Adds and backfills:

`sale_items.ordered_qty`

---

## 10. Automated Scenarios Added

The real-world golden path now explicitly tests:

### Under-production

- order = 100
- actual output = 80
- raw material restored proportionally
- FIFO consumption reduced
- original ordered quantity remains 100
- final invoice quantity becomes 80
- invoice debit transaction matches revised invoice
- profit uses revised revenue and actual cost

### Over-production

- order = 100
- actual output = 120
- additional FIFO raw material consumed
- original ordered quantity remains 100
- final invoice quantity becomes 120
- final invoice amount increases accordingly
- accounting invoice debit follows the revised invoice

### Raw-material-limited production

- order = 100
- stock intentionally limited to support about 60
- production starts instead of being blocked
- allocated quantity is about 60
- final actual output = 58
- unused material from the allocation is restored
- invoice quantity becomes 58

### UI contract

The production-completion UI is regression-tested for:

- actual output field;
- final-invoice warning;
- production variance;
- no return to the old automatic produced=ordered behavior.

---

## 11. Final QA Evidence

GitHub Actions workflow:

`Actual Production Quantity QA`

Successful run:

`35487650929`

Verified runtime SHA:

`36b3c578560e7dffcf8cbb02130f55b4e43a7513`

### Focused workflow

```
11 passed
177 assertions
```

This includes the original real-world carton golden path plus under-production, over-production and stock-limited production.

### Complete application test suite

```
65 passed
704 assertions
```

### Other gates

- PHP syntax checks: **PASS**
- Fresh MySQL 8 migration + seed: **PASS**
- Blade view compilation: **PASS**
- Vite production build: **PASS** — built in 1.45s
- Composer security audit: **PASS**
- Security advisories: **0**

---

## 12. Release Status

**READY FOR REVIEW / MERGE**

The verified feature remains isolated on:

`feat/actual-production-quantity-20260920`

It has not been merged into `main` as part of this implementation/report step.
