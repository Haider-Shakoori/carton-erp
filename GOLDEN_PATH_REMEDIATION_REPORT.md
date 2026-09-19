# GOLDEN PATH REMEDIATION REPORT

**Project:** Carton ERP  
**Repository:** `Haider-Shakoori/carton-erp`  
**Remediation branch:** `fix/golden-path-remediation-20260919`  
**Verified code SHA:** `9d4cdde48d90fc015aca6184a6904cd39c9a2163`  
**Verification date:** 2026-09-19  
**Final status:** **READY FOR MERGE / RELEASE CANDIDATE**

---

## 1. Executive Summary

The real-world carton workflow was re-audited and remediated end to end on a separate branch.

The original audit exposed several connected defects rather than one isolated calculation bug:

- mixed-currency purchase expenses could corrupt purchase-currency totals;
- roll stock was not consistently costed in the physical inventory consumption unit (kg);
- a clean database did not reliably contain all BOM formula columns expected by the application;
- BOM cost fields did not retain enough precision for landed cost and physical consumption;
- BOM commercial pricing and physical production cost were mixed together;
- wastage could leak into quotation/commercial calculations;
- sale quotation logic duplicated BOM calculations and could diverge from the saved BOM;
- the legacy sale item endpoint could commit a row and then fail while building its response;
- profit reporting independently reconstructed commercial BOM calculations and could drift from BOM/sale calculations;
- several older tests were SQLite/local-environment specific and were not reliable release gates on MySQL;
- the Batch 04 index rollback was unsafe on MySQL because a composite reporting index could become the supporting index for a foreign key.

These issues have been corrected and the production workflow now uses a canonical costing model.

---

## 2. Canonical Costing Rules After Remediation

### 2.1 Purchase and landed cost

Purchase expenses are normalized through USD before they are aggregated.

For a purchase whose order currency is USD:

```
purchase subtotal USD
+ normalized purchase expenses USD
= purchase grand total USD
```

A mixed USD + AFN expense set is therefore never added as raw currency amounts.

For roll-based raw material, the authoritative inventory cost basis is:

```
USD / kg
```

For non-roll inventory, the authoritative basis remains the native purchase/inventory unit.

A central `BOMCostingService` now resolves the latest arrived landed inventory cost and exposes the basis unit explicitly.

### 2.2 Physical BOM consumption

For 3D carton paper rows:

```
Reel Length  = ((Length + Width) × 2) + 4
Reel Height  = Width + Height + 1

Base kg / finished unit
= Reel Length × Reel Height × GSM × Layers ÷ Formula Constant
```

The configured formula constant is honored. The default remains `1,550,000`.

For production:

```
Physical kg including wastage
= Base kg × (1 + Wastage %)
```

Wastage belongs to physical production consumption/cost.

### 2.3 Commercial BOM pricing

Commercial pricing and production cost are now intentionally separate.

Per BOM:

```
Base Material Cost
+ Standard Work / Profit
+ Print Cost
= Commercial Base Rate

Commercial Base Rate
+ Additional Markup
= Final Selling Price
```

Wastage is **not** added to the commercial quotation basis.

Physical production material cost is:

```
Base Material Cost + Wastage Cost
```

The Standard Work / Profit percentage is a commercial component. It is not automatically treated as actual production labour or overhead.

### 2.4 Sale and profit consistency

Saved-BOM sales use the BOM's canonical commercial calculation and physical material cost.

Manual BOM snapshots retain the landed USD/kg rate used at quotation time so later inventory repricing does not silently rewrite the historical manual quotation basis.

Profit reporting now reuses the canonical BOM commercial calculation instead of rebuilding the formula independently.

Actual production profit continues to reconcile against realized FIFO material consumption.

---

## 3. Issues Fixed

### A. Mixed-currency purchase grand total

**Before:** raw expense amounts from different currencies could be added together.

In the real-world QA case:

- Purchase subtotal: USD 23,600
- Freight: USD 1,500
- Customs/transport: AFN 56,100 = USD 850 at 66 AFN/USD
- Correct purchase total: USD 25,950

The old mixed-currency path could produce a meaningless raw-currency grand total.

**After:** all purchase expenses are normalized through their USD amount, then converted back to purchase currency when required.

### B. Roll inventory cost basis

**Before:** some code paths could expose or consume a roll-level purchase value while the BOM/production formula consumed kilograms.

**After:** roll-based costing is explicitly landed **USD/kg** throughout inventory → BOM → sale → production.

### C. Fresh-schema BOM formula fields

**Before:** clean MySQL installations could be missing formula fields expected by the current BOM application path.

**After:** an idempotent schema repair migration guarantees the required BOM item formula columns exist.

### D. BOM costing precision

**Before:** low decimal precision could round a valid landed rate such as approximately `1.005082 USD/kg` enough to create measurable reconciliation differences.

**After:** BOM quantity and costing fields use higher precision for physical consumption and landed cost calculations.

### E. Physical quantity calculation

**Before:** formula calculations were implemented in multiple forms and could disagree about quantity, rate basis, or configurable formula constant.

**After:** BOM physical requirements are calculated consistently and the configured formula constant is honored.

### F. Wastage placement

**Before:** wastage could influence quotation/commercial net rates in some flows.

**After:** wastage affects physical inventory consumption and production cost only. It does not inflate the commercial base quotation.

### G. BOM vs sale pricing divergence

**Before:** BOM creation, saved sale pricing, manual sale pricing, and profit reporting had duplicated formulas.

**After:** saved BOM pricing, sale quotation, and profit reporting share canonical costing logic.

### H. Sale add-item commit/response crash

**Before:** the legacy add-item flow could commit a sale item and then fail while constructing its response because an array was treated as a collection.

**After:** response data is safely built and the transaction boundary prevents a committed item from being left behind by a response-construction failure.

### I. Profit-report formula drift

**Before:** `SaleProfitService` independently reconstructed commercial BOM rates, including legacy assumptions.

**After:** saved BOM commercial figures are obtained from `BOMCostingService`; manual snapshots retain their historical landed rate.

### J. Batch 04 MySQL rollback

**Before:** rolling back a reporting composite index could fail with MySQL error 1553 because InnoDB was using that index to support a foreign key.

**After:** rollback first ensures a narrow conventional FK-supporting index exists, then safely removes the performance index. Foreign keys remain enabled and intact.

---

## 4. BOM Creation UI Adjustments

The BOM creation screen was redesigned around the corrected costing model.

It now presents these values separately:

- **Landed Inventory Cost** — displayed with an explicit basis such as `USD / kg`
- **Consumption / Finished Unit** — physical formula quantity
- **Base Material** — physical cost before wastage
- **Wastage Cost** — extra production material cost only
- **Physical Material Cost** — base + wastage
- **Standard Work / Profit** — commercial component
- **Print Cost**
- **Additional Markup**
- **Commercial Base Rate**
- **Final Selling Price / Unit**

Additional UI safeguards:

- formula paper rate is synchronized from landed inventory cost instead of being an independent editable source of truth;
- 3D/cut-roll paper formulas require a kg cost basis;
- invalid dimensions/GSM are blocked before submission;
- exchange-rate changes update AFN equivalents without rewriting the authoritative USD inventory cost;
- formula calculations no longer overwrite landed cost with a quotation rate;
- the UI explicitly explains that wastage belongs to physical production cost.

A dedicated UI contract test protects these labels and costing distinctions against accidental regression.

---

## 5. Main Implementation Changes

### New

- `app/Services/BOMCostingService.php`
- `database/migrations/2026_09_19_210000_repair_bom_item_formula_schema.php`
- `database/migrations/2026_09_19_211000_increase_bom_cost_precision.php`
- `tests/Feature/GoldenPath/RealWorldCartonWorkflowRemediationTest.php`
- `tests/Feature/GoldenPath/BOMCreateUiContractTest.php`
- `.github/workflows/carton-remediation-qa.yml`

### Major updated areas

- `BOMController`
- `SaleController`
- `PurchaseExpenseController`
- `BOM`
- `BOMItem`
- `Purchase`
- `PurchaseItem`
- `Product`
- `SaleItem`
- `SaleProfitService`
- BOM create Blade/JavaScript
- Batch 04 performance-index migration
- legacy MySQL/CI portability tests

---

## 6. Real-World Golden Path Verified

The strict remediation scenario now uses the real formula BOM path. No downstream fixed-BOM bypass is used.

Verified sequence:

```
Supplier purchase order
→ purchase items
→ USD freight expense
→ AFN customs/transport expense
→ purchase arrival
→ roll-to-kg stock availability
→ landed USD/kg calculation
→ real 5-layer formula BOM creation
→ canonical BOM selling price
→ 100-carton sale quotation
→ sale confirmation
→ production-order creation
→ material requirement snapshot
→ FIFO material consumption
→ production completion
→ actual production cost
→ actual profit reconciliation
→ linked customer payment
→ reduced sale due balance
```

Focused remediation result:

```
9 passed
121 assertions
```

This includes eight real-world workflow scenarios plus the BOM creation UI contract.

---

## 7. Full Release Verification

GitHub Actions workflow:

`Carton ERP Remediation QA`

Successful verification run:

`35458100691`

Verified code SHA:

`9d4cdde48d90fc015aca6184a6904cd39c9a2163`

### Release gate results

| Gate | Result |
|---|---|
| PHP 8.3 environment | PASS |
| Node 22 environment | PASS |
| Composer install | PASS |
| Frontend asset build | PASS |
| Changed PHP files syntax check | PASS |
| Fresh MySQL 8 migration | PASS |
| Database seed | PASS |
| Laravel view compilation | PASS |
| Real-world remediation golden path | PASS |
| BOM create UI contract | PASS |
| Complete PHP test suite | PASS |
| Composer security audit | PASS |

Complete PHP suite:

```
61 passed
628 assertions
```

Composer audit:

```
No security vulnerability advisories found.
```

Frontend build:

```
vite build
✓ built successfully
```

---

## 8. CI/Test Infrastructure Repairs

The remediation also removed false release failures caused by environment-specific test assumptions:

- Vite assets are built before view-rendering tests;
- SQLite-specific `PRAGMA` index inspection was replaced with Laravel schema APIs;
- SQLite-specific `sqlite_master` inspection was replaced with portable schema inspection;
- query-string assertions now tolerate MySQL identifier quoting;
- decimal assertions normalize database-driver return types;
- Batch 03 performance tests no longer depend on an uncommitted local `storage/app/optimization-backup` copy;
- Batch 04 rollback is now actually safe on MySQL rather than merely being made test-compatible.

These changes make the release gate reproducible on the same database family used by the application.

---

## 9. Deployment Notes

Before production deployment:

1. Take a verified database backup.
2. Deploy application code.
3. Run:
   `php artisan migrate --force`
4. Clear/rebuild Laravel caches as appropriate for the server.
5. Build/deploy frontend assets.
6. Confirm queue/scheduler configuration if the production environment uses them.
7. Smoke-test one existing BOM and one newly created formula BOM before allowing normal production activity.

The two new BOM migrations are additive/precision-increasing. Their `down()` methods intentionally avoid destructive narrowing/removal that could discard valid costing data.

---

## 10. Final Verdict

**READY FOR MERGE / RELEASE CANDIDATE**

All blockers identified by the real-world carton golden-path audit have been remediated.

The following are green on MySQL 8:

- clean installation;
- BOM schema;
- mixed-currency landed costing;
- kg-based paper consumption;
- BOM commercial pricing;
- sale quotation consistency;
- FIFO production;
- actual profit reconciliation;
- customer payment linkage;
- BOM creation UI contract;
- full regression suite;
- dependency security audit;
- frontend compilation.

The remediation remains isolated on:

`fix/golden-path-remediation-20260919`

It has **not** been merged into `main` by this remediation task.
