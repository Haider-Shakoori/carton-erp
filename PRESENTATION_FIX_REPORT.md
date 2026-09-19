# Carton ERP Presentation Fix Report

## Executive Summary

- Presentation-blocking QA issues addressed: **6 of 6** (3 Critical, 3 High).
- Focused verification result: **PASS with one unrelated automated-test infrastructure limitation**.
- Golden-path sales figures now use one currency basis and revenue-based margin.
- Automatic production creation no longer calls the missing `SaleItem::getProductionCostBreakdown()` method.
- Production material requirements, actual FIFO cost, payment persistence, and profit conversion were verified.
- No database schema, migration, package, environment, or configuration changes were made.

## Fix Status

| Bug | Severity | Result | Verification |
|---|---|---|---|
| BUG-GP-001 | Critical | FIXED | SO-202608-0009 displays $1.38 revenue, $0.99 cost, $0.39 profit, 28.3% margin. |
| BUG-GP-002 | High | FIXED | Sale detail displays `1 USD = 66.0000 AFN`. |
| BUG-GP-003 | High | FIXED | Rollback-isolated automatic production creation succeeded with material/total cost $0.16 before consumption. |
| BUG-GP-004 | Critical | FIXED | Production #49 Material Status now shows 0.17 per BOM layer, matching the 0.1719 breakdown. |
| BUG-GP-005 | Critical | FIXED | Rollback-isolated production start reached `in_progress` and stored actual FIFO material/total cost $0.28. Golden-path actual cost converts to 18.16 AFN. |
| BUG-GP-006 | High | FIXED | A 0.01 AFN `QA_FIX payment persistence verification` credit saved, redirected, and appeared as the newest transaction. |

## Files Changed

- `app/Models/SaleItem.php`
- `app/Http/Controllers/Admin/SaleController.php`
- `app/Services/ProductionService.php`
- `app/Http/Controllers/Admin/ProductionOrderController.php`
- `app/Services/SaleProfitService.php`
- `resources/views/admin/sales/index.blade.php`
- `resources/views/admin/sales/show.blade.php`
- `resources/views/admin/transactions/create.blade.php`
- `PRESENTATION_FIX_REPORT.md`

## Sales and Currency Verification

Golden-path sale: **SO-202608-0009**, AFN sale currency, exchange rate **1 USD = 66 AFN**.

| Metric | Expected | Application after fix | Result |
|---|---:|---:|---|
| Revenue USD | 91.30 / 66 = $1.38 | $1.38 | PASS |
| Estimated cost USD | $0.99 | $0.99 | PASS |
| Estimated profit USD | $1.38 - $0.99 = $0.39 | $0.39 | PASS |
| Margin | $0.39 / $1.38 = 28.3% | 28.3% | PASS |
| Exchange label | 1 USD = 66 AFN | 1 USD = 66.0000 AFN | PASS |

## Automatic Production Verification

- Reused clearly identifiable QA sale **SO-202608-0008** in a database transaction that was explicitly rolled back.
- `ProductionService::createProductionFromSale()` completed without the prior missing-method exception.
- A temporary production order was created with calculated material cost **$0.16**.
- Starting the temporary order completed with status **in_progress**.
- Actual FIFO consumption cost was written back as material cost **$0.28** and total cost **$0.28**.
- The verification transaction was rolled back; no temporary production order or stock deduction was retained.

## Material and Stock Consistency

Production order #49 now presents the same quantity basis in both sections:

- Material Status: **0.17 roll** per BOM layer.
- Material Breakdown: **0.1719 roll** per BOM layer (higher precision display).
- Two layers total: **0.343883 roll**.
- Actual FIFO cost verified in the isolated start-production run: **$0.2751**, displayed/stored at field precision as **$0.28**.

## Profit Verification

For golden-path sale SO-202608-0009, the corrected actual-cost conversion produced:

| Metric | Expected | Application | Result |
|---|---:|---:|---|
| Actual material cost USD | $0.2751 (~$0.28) | $0.28 | PASS |
| Actual material cost AFN | $0.2751 × 66 = 18.16 AFN | 18.16 AFN | PASS |
| Gross sale AFN | 91.30 AFN | 91.30 AFN | PASS |
| Actual profit AFN | 91.30 - 18.16 = 73.14 AFN | 73.14 AFN | PASS |
| Actual margin | 73.14 / 91.30 = 80.11% | 80.11% | PASS |

## Payment Verification

- Account: **Qadir (CUS-1328)**
- Currency: **AFN**
- Type: **Credit**
- Amount: **0.01 AFN**
- Description: **QA_FIX payment persistence verification**
- Result: normal POST redirect to Transactions and the saved row appeared first in the list at 12:46 PM.

## Focused Test Results

- PHP syntax checks for all modified PHP files: **PASS**.
- Browser sales list and detail verification: **PASS**.
- Browser production material-status verification: **PASS**.
- Browser payment save and persistence verification: **PASS**.
- Rollback-isolated automatic production creation/start: **PASS**.
- SaleProfitService golden-path calculation: **PASS**.
- Existing `ProductionMaterialConsumptionMigrationTest`: **BLOCKED before assertions** by an unrelated pre-existing SQLite migration error: `error in index boms_formula_type_index after drop column: no such column: formula_type`. No migration or test was changed.

## Remaining Presentation Risk

- Existing historical records keep their originally stored rounded values; corrected views and profit calculations normalize the golden-path figures at display/calculation time.
- The broad automated migration test remains unavailable until the unrelated SQLite migration compatibility issue is handled outside this urgent six-bug scope.

## Presentation Assessment

**READY WITH CAUTION.** The six reported presentation blockers are corrected and their principal paths are verified. The safest route is Dashboard → Sales Orders → SO-202608-0009 → Production Order #49 → Stock → Transactions → Profit/Reports. Avoid demonstrating migration/test tooling; it is unrelated to the client UI and remains blocked by the pre-existing SQLite migration issue.
