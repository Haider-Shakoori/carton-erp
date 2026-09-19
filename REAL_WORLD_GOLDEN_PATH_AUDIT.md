# Real-World Carton ERP Golden-Path Audit

**Date:** 2026-09-19  
**Repository:** Haider-Shakoori/carton-erp  
**Audited base:** `main` @ `4834524c07721b1c04244d74c6e099feffd80a30`  
**QA branch:** `qa/real-world-golden-path-20260919`  
**Production/main changes:** None

## Executive Result

**STATUS: BLOCKED / NOT READY FOR A CLEAN RELEASE**

The application can boot, migrate, seed, build, authenticate, create real purchase orders, maintain roll-to-kg stock, create production orders, consume stock by FIFO, reconcile actual production cost/profit, and apply sale-linked customer payments.

However, the clean real-world path exposed several release-blocking defects in purchase currency aggregation, BOM schema/migrations, BOM material costing, sale-item response handling, and quotation pricing consistency.

## Real-World Scenario Used

### Parties

- Supplier: **Zhejiang Golden Paper Co., Ltd.**
- Freight agent: **Silk Road Freight Services**
- Customs / inland transport agent: **Kabul Customs & Inland Transport**
- Customer: **Aryana Pharmaceutical Industries**

### Currency

- USD = 1
- AFN = 66
- Test exchange rate: **1 USD = 66 AFN**

### Purchase Order

**PO-RW-20260919-001**

| Material | Qty | Unit | Kg/Roll | Unit Price USD | Base Total USD |
|---|---:|---|---:|---:|---:|
| Kraft Liner 150 GSM | 40 | roll | 320 | 292.50 | 11,700.00 |
| Fluting Paper 120 GSM | 50 | roll | 300 | 238.00 | 11,900.00 |
| **Subtotal** | | | | | **23,600.00** |

Expenses:

- Freight: **USD 1,500.00**
- Customs / inland transport: **AFN 56,100.00 = USD 850.00**
- Correct USD expense total: **USD 2,350.00**
- Correct USD landed grand total: **USD 25,950.00**

Expected physical stock after arrival:

- Kraft: **12,800 kg**
- Fluting: **15,000 kg**

### Intended BOM

Finished good: **120ml Syrup Carton - 5 Layer - QA**

- Length: 17.32 in
- Width: 15.75 in
- Height: 12.20 in
- Wastage: 5%
- Kraft 150 GSM: 2 layers
- Fluting 120 GSM: 3 layers
- Work percentage: 40%
- Profit margin: 15%
- Exchange rate: 66 AFN/USD

Physical formula gives approximately:

- Kraft: **0.393009 kg/carton before wastage**
- Kraft: **0.412660 kg/carton with 5% wastage**
- Fluting: **0.471611 kg/carton before wastage**
- Fluting: **0.495192 kg/carton with 5% wastage**

## Smoke-Test Baseline

Fresh GitHub runner results:

- PHP 8.3.33: PASS
- Composer validate/install: PASS
- Laravel boot / route loading: PASS
- Config cache: PASS
- View cache: PASS
- PHP syntax lint: PASS
- Fresh MySQL migration: PASS after allowing trigger creators
- Database seed: PASS
- npm install/build: PASS
- HTTP root/login smoke: PASS
- Existing automated suite: **51 passed, 1 failed, 439 assertions**
- Existing failure is a test portability issue: it expects a local-only optimization backup under `storage/app/optimization-backup/batch-03/...`

MySQL deployment note:

`log_bin_trust_function_creators=1` (or equivalent privileges/configuration) is required for the current trigger migrations on servers with binary logging enabled.

## Real-World Golden-Path Results

Latest focused audit: **8 scenarios, 4 pass, 4 fail, 88 assertions**.

### PASS — Purchase Order, Arrival, and Physical Stock

The application successfully:

- created the USD purchase order;
- added both imported roll materials;
- added USD and AFN expenses;
- transitioned draft -> shipping -> arrived;
- generated the supplier purchase transaction;
- preserved USD subtotal/expense/grand-total fields correctly;
- converted rolls to kg correctly;
- made arrived stock available.

Verified:

- `usd_subtotal = 23,600.00`
- `usd_expense_total = 2,350.00`
- `usd_grand_total = 25,950.00`
- Kraft available = 40 rolls / 12,800 kg
- Fluting available = 50 rolls / 15,000 kg

### FAIL — Mixed-Currency Purchase `grand_total`

**Severity: HIGH**

Expected PO-currency grand total:

- USD 23,600 + USD 1,500 + (AFN 56,100 / 66)
- **USD 25,950**

Actual `purchases.grand_total`:

- **81,200**

Cause:

`PurchaseExpenseController::recalculateExpensePerItem()` and `Purchase::recalculateTotals()` add raw `purchase_expenses.amount` values together even when expenses use different currencies.

This mathematically mixes:

- 1,500 USD
- 56,100 AFN

as though they are the same unit.

The dedicated USD fields are correct, but `grand_total` is not currency-safe.

### FAIL — Latest Roll Material Cost Uses Per-Roll Price Instead of Landed USD/Kg

**Severity: CRITICAL FOR BOM COSTING**

For the Kraft batch:

- purchase price = **USD 292.50 per roll**
- expected landed price is about **USD 1.00508 per kg**

`BOMController::getMaterialCost()` returned the roll-level `cost_per_unit / usd_unit_price` as the latest material cost instead of `PurchaseItem::landedCostPerKg()`.

The audit difference was:

- **291.494918 USD** between returned latest cost and the expected landed USD/kg rate.

This can overstate BOM material cost dramatically when the BOM consumes roll-based paper in kg.

### FAIL — Clean Database Cannot Create a Formula-Based BOM

**Severity: CRITICAL / DEPLOYMENT BLOCKER**

On a completely fresh MySQL database, `BOMController::store()` fails with:

`Unknown column 'length_inch' in 'field list'`

The controller/model expects BOM item fields including:

- `length_inch`
- `width_inch`
- `height_inch`
- `paper_gsm`
- `layers`
- `division_factor`
- `cut_length_inch`
- `cut_width_inch`
- `grh`
- `ply`
- `print`

but the final clean migration chain does not create the complete expected BOM-item schema.

The August migration removes formula columns from `boms` with the intention of moving them to `bom_items`, but the subsequent BOM-item migrations do not add the full set.

Result: an existing upgraded database may work because of historical schema state, while `migrate:fresh` produces a schema that cannot execute the current BOM creation code.

### FAIL — BOM Price and Sale Quotation Price Do Not Reconcile

**Severity: HIGH / COMMERCIAL COSTING**

Using the same materials, landed costs, wastage, exchange rate, and 100-carton sale, the per-unit sale quotation differed from the BOM selling price by:

- **24.9268 AFN per carton**

Root cause:

- `BOM::calculateTotals()` applies `work_percentage` (default 40%) to material cost and then the profit margin.
- `SaleController::addItem()` calculates material cost + explicit labor/overhead + profit margin, but does not apply the BOM's 40% commercial work component.

Thus two authoritative application paths calculate a different selling price from the same BOM.

### CONFIRMED — Sale Item Is Committed, Then JSON Response Crashes

**Severity: HIGH / DATA-INTEGRITY UX RISK**

`SaleController::addItem()` initializes:

`$itemsAdded = [];`

and later calls:

`$itemsAdded->map(...)`

This raises:

`Call to a member function map() on array`

Critically, the controller calls `DB::commit()` before building that response.

Therefore:

1. the sale item is successfully inserted;
2. the transaction is committed;
3. the request then crashes while producing its JSON response.

A user may see an error and retry, even though the item was already added, creating a duplicate-entry risk.

The QA continuation intentionally catches this response error only so downstream production/payment behavior can still be evaluated. No application code was changed.

### PASS — Production Order / FIFO / Completion

After using a QA-only operational BOM fixture to bypass the broken formula-BOM creation schema, the real imported stock successfully continued through:

- sale confirmation;
- production-order creation;
- frozen production-material requirements;
- production start;
- FIFO stock deduction;
- roll kg-stock reduction;
- `production_material_consumptions` recording;
- production completion;
- produced quantity update;
- sale marked produced.

This confirms that the newer production/FIFO path is materially healthier than the BOM/quotation entry path.

### PASS — Actual Production Cost and Profit Reconciliation

After production:

- FIFO consumption rows were present;
- actual material cost from `SaleProfitService` reconciled to the recorded production consumptions;
- actual production cost reconciled where no explicit labor/overhead/other direct cost was entered;
- actual profit reconciled to revenue minus actual production cost.

This is an important positive result: the newer realized-cost path uses physical/FIFO costing coherently.

### PASS — Linked Customer Payment

A confirmed AFN sale with an advance payment was given an additional linked payment through `TransactionsController`.

Verified:

- the payment was linked to the exact sale;
- customer and currency validation passed;
- the sale due amount decreased by exactly the payment;
- advance payment increased by exactly the payment;
- the corresponding credit transaction exists.

## Architectural Finding

The application currently has **multiple independent costing implementations**:

1. BOM creation / `BOM::calculateTotals()`
2. BOM material-cost AJAX lookup
3. Sale quotation / `SaleController::addItem()`
4. Production planning / `ProductionService`
5. FIFO actual consumption
6. Profit reporting / `SaleProfitService`

The production and actual-profit paths have newer physical-kg/FIFO logic, while parts of BOM creation and sale quotation still use older unit-based/commercial logic.

This duplication is the main reason the same order can have different costs or prices depending on which screen/service calculates it.

## Recommended Fix Order

### P0 — Must Fix Before Further Acceptance Testing

1. Repair final BOM-item migration schema so `migrate:fresh` matches `BOMItem` and `BOMController::store()`.
2. Make roll-material BOM lookup return landed USD/kg when consumption unit is kg.
3. Fix mixed-currency purchase `grand_total`; all expenses must first be normalized to the purchase order currency.
4. Fix `SaleController::addItem()` response collection bug and move commit/response handling into a safe transaction boundary.
5. Establish one canonical commercial quotation calculation so BOM and Sale use the same work/profit rules.

### P1 — Then Re-run the Golden Path

Re-test without QA bypasses:

Purchase Order -> Expenses -> Shipping -> Arrival -> Stock -> BOM creation -> Sale quotation -> Sale confirmation -> Production order -> FIFO consumption -> Completion -> Invoice -> Payment -> Customer ledger -> Profit report.

### P2 — Hardening

- Add the golden-path test to permanent CI after defects are fixed.
- Replace the local-only Batch 03 performance baseline dependency with a committed fixture or deterministic assertion.
- Add a clean-install CI job using MySQL, not only SQLite.
- Document MySQL trigger requirements or replace trigger dependence where practical.
- Replace the stock Laravel README with project-specific setup/deployment documentation.

## Release Gate

**Current gate: FAIL**

The underlying production/FIFO/payment core can complete a realistic workflow once the BOM entry blockers are bypassed, but a clean deployment cannot create the intended formula BOM and the purchase/BOM/sale commercial figures are not yet guaranteed to agree.

No fixes from this report have been merged into `main`. This report is diagnostic evidence only.
