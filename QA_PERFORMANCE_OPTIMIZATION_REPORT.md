# QA: PERFORMANCE OPTIMIZATION REPORT

**Task:** Project-wide performance optimization — behavior preserving only.
**Status:** PASS

All optimizations reduce redundant database work while leaving calculations, rounding,
financial values, database semantics, permissions, localization, and UI output identical.

---

## 1. BASELINE FINDINGS

Query counts are measured per page via Laravel `DB::listen` in an **isolated**
single-request process on the live MySQL DB (`product`). Isolation was required: an earlier
batch profiler accumulated listeners and inflated counts.

| PAGE / ENDPOINT | BEFORE (isolated) | AFTER (isolated) | MAIN QUERY PATTERN | KNOWN N+1 | FIXED |
|---|---|---|---|---|---|
| /admin/products | **215** | **32** | 6 product collections + low-stock filter | ~180 dead `current_stock` queries + 30 category count queries | YES |
| /admin/stock | **44** | **17** | per-product purchase-items + expenses sum | 1 items query + 2 lazy (purchase, expenses) per product | YES |
| /admin/sales/77 | **38** | **33** | SaleProfitService per-material lookups; per-material/Per-product loops | Product::find + PurchaseItem query per BOM row | YES |
| /admin/dashboard | — | 8 | pre-aggregated | (already optimized in prior pass) | — |
| /admin/purchase-orders (idx) | — | 20 | eager `with` + stats | none | — |
| /admin/purchase-orders/37 | — | 18 | eager loads | none | — |
| /admin/sales (idx) | — | 17 | eager `with` | none | — |
| /admin/sales/create | — | 12 | eager loads | none | — |
| /admin/production-orders | — | 17 | eager `with` + status counts | none | — |
| /admin/expenses | — | 14 | batch balance + stats | none (batch balance query) | — |
| /admin/transactions | — | 16 | eager `with` | none | — |
| /admin/customers | — | 14 | batch balance | none (batch balance query) | — |
| /admin/suppliers | — | 13 | addSelect subquery balance | none | — |
| /admin/journal | — | 9 | DataTables eager loads | none | — |

---

## 2. FILES CHANGED

| File | Change |
|---|---|
| `app/Http/Controllers/Admin/ProductController.php` | Removed dead `$products` and `$lowStockProducts` collections; simplified `$allProducts`; removed unused `products_with_purchase_items_count` subquery |
| `app/Models/Product.php` | Request-scoped memoization of DB-backed accessors (`current_stock`, `current_stock_kg`, `is_roll_based`, `weighted_avg_cost`, `batch_breakdown`); clear cache on save |
| `app/Models/Category.php` | `getProductsCountAttribute` now prefers the `withCount`-loaded value; falls back to a count query when not loaded |
| `app/Services/SaleProfitService.php` | Request-scoped memoization of per-material lookups in `rollWeightBasisMissing` / `resolveRollCostPerKg` / material `Product::find` |
| `app/Http/Controllers/Admin/SaleController.php` | Eager-load `category` for `$products`; pre-fetch latest arrived purchase item per raw material in one query; batch-load active BOMs grouped by product |
| `app/Http/Controllers/Admin/StockController.php` | Pre-fetch all arrived purchase items (+`purchase.expenses`) for the paginated products in one query |
| `database/migrations/2026_09_04_100000_add_performance_indexes_batch_05.php` | **NEW** — two indexes (see section 7) |

---

## 3. EACH OPTIMIZATION + BEFORE/AFTER

### 3a. Products index — remove dead work + memoization
- **Before:** 215 queries; ~180 were the never-used `$lowStockProducts` collection whose
  `filter()` ran a `current_stock` SUM query per active low-stock product, plus ~30 category
  `products_count` queries from the accessor overriding `withCount`.
- **After:** 32 queries.
- **Behavior:** identical — `$lowStockProducts` and `$products` were never referenced in the
  Blade (verified); the low-stock notification is populated by `/admin/products/low-stock`
  AJAX (unchanged). Category counts identical (accessor A/B verified).
- **Evidence:** isolated 215 → 32; all values identical (category count 29/24/17 match raw
  count; product `current_stock` matches direct query).

### 3b. Stock index — batch pre-fetch of items + expenses
- **Before:** 44 queries; per product: 1 items query, then 2 lazy queries (`purchase`,
  `expenses`) per item.
- **After:** 17 queries.
- **Behavior:** identical — same item rows, same `SUM`, same `usd_amount`;
  `purchase.expenses` eagerly loaded yields identical sums. `whereHas(purchase arrived)` in
  the main query guarantees no empty groups.

### 3c. Sale show — profit-service memoization + controller eager loads
- **Before:** per material row: `Product::find` (twice) + up to 3 PurchaseItem queries,
  repeated for every BOM row across every item. Plus per-finished-good BOM query and
  per-raw-material latest-purchase query.
- **After:** memoized per-material lookups; BOMs batch-loaded; latest purchase items
  pre-fetched; `category` eager-loaded.
- **Behavior:** identical — memo ON vs OFF A/B returned identical full result arrays for
  Sales 77/76/73; materials map A/B and BOMs map A/B both identical.
- **Evidence:** isolated 38 → 33 for the small current dataset; the win scales with BOM
  row/item count (memo removes all repeated per-material queries).

---

## 4. BEHAVIOR-PRESERVATION EVIDENCE

Representative flows checked before vs after — all identical:

| Flow | Check | Result |
|---|---|---|
| PURCHASE | PO 37 FINAL-DEMO-PO-001 (status arrived, grand 313500, item 41 roll / kg_per_roll 500 / total_weight 5000 / landed 0.95) | IDENTICAL |
| SALE | Sale 76 est_profit 22680.51, std work 1858.06 | IDENTICAL |
| SALE | Sale 73 est_profit 4306.31, std work 1858.06 | IDENTICAL |
| SALE | Sale 77 commercial: paper 426.29, print 20.00, net 616.80, std work 170.51 | IDENTICAL |
| PROFIT SERVICE | memo ON vs OFF full-array diff for sales 77/76/73 | IDENTICAL |
| SALE CONTROLLER | `$materials` latest-purchase map (new vs old) | IDENTICAL |
| SALE CONTROLLER | `$productBoms` grouped (new vs old) | IDENTICAL |
| STOCK | batch totals recomputed with eager loads | IDENTICAL |
| ACCOUNTING | render 200; account balances untouched | PASS |
| PERMISSIONS | /admin/users render 200 (admin gate) | PASS |
| LOCALIZATION | en/fa fallback intact (real key resolves) | PASS |
| RENDER | products, stock, sales index/create/show, purchase show, dashboard all 200 | PASS |

**Note on Sale 77 estimated cost:** `estimated_cost_unavailable` flipped from true→false and
`estimated_material_cost_afn` is now 320.20. This is **data-driven**, not caused by this
optimization. The database now contains a genuine `unit='roll'` Kraft Paper batch carrying a
`kg_per_roll` (and product `default_kg_per_roll` is 50), which the existing roll-weight
estimation logic correctly resolves. This state pre-dated the code changes in this session
(verified: the availability flipped before the service/controller edits were applied). The
frozen **commercial** figures for Sale 77 are unchanged.

---

## 5. MEMOIZATION DESIGN (SAFE)

Memoization is **request-scoped per model/service instance**:
- `Product` caches computed accessor values in an instance property, cleared on `saved` /
  `updating` so fresh data is never masked.
- `SaleProfitService` caches per-material lookups keyed by `material_id` for the life of the
  `calculate()` call.
- No cross-request cache, no persistent cache of financial/inventory balances. Results are
  deterministic within a request, so repeated access yields identical values while removing
  duplicate queries. Verified bit-identical via A/B.

---

## 7. INDEXES ADDED

Migration `2026_09_04_100000_add_performance_indexes_batch_05` (applied):

| Table | Index | Matches query pattern |
|---|---|---|
| `purchase_expenses` | `purchase_expenses_purchase_id_index` | frequent `SUM(usd_amount) WHERE purchase_id = ?` (stock/sale/batch costing) |
| `sale_items` | `sale_items_product_id_index` | frequent `WHERE product_id = ?` (stock-out / product movement) |

Both are non-unique, non-redundant, index real frequent WHERE/JOIN patterns, and do not
alter data semantics. Used `Schema::hasIndex`/`SHOW INDEX` to confirm they did not previously
exist, and verified they now exist.

---

## 8. CACHE CHANGES

None. No global/persistent caching was added. Financial, inventory, FIFO, and live totals
remain uncached per the task's safety rule. Only request-scoped in-memory memoization was
added (section 5), which requires no invalidation because it never crosses requests.

---

## 9. SKIPPED RISKY OPTIMIZATIONS

- **Converting BOM/quotation/report SQL to raw queries** — skipped (risk to formulas).
- **Persistent caching of sale totals / ledger balances / production cost** — skipped (stale-data risk).
- **Broad eager-load changes to ignore scopes/soft-deletes** — avoided; each eager load was
  verified scope/soft-delete-equivalent (BOM has `SoftDeletes`; grouped query matches the
  original `where()` default which also excludes trashed).
- **Removing observers/events** — none removed.
- **Denormalizing stock/balance columns** — skipped.
- Additional speculative indexes (e.g., broad multi-column) — skipped; only two
  high-confidence patterns were indexed.

---

## 10. REGRESSIONS CHECKED

- Purchase 37 show/edit values — PASS.
- Sale 76 / 73 frozen estimated profit + standard work — PASS.
- Sale 77 frozen commercial figures (paper/print/net/work) — PASS.
- SaleProfitService memo A/B — IDENTICAL.
- SaleController materials/BOMs A/B — IDENTICAL.
- Category & Product accessor values — IDENTICAL.
- All touched pages render HTTP 200.
- `php -l` clean on all changed files; `php artisan view:clear` + `view:cache` applied.
- Migration applied cleanly; `migrate:status` shows batch 05 Ran.

*(Full Laravel test suite not run per project note: a pre-existing SQLite
`boms.formula_type` migration issue blocks the suite. Live DB is MySQL; targeted tinker /
HTTP / A/B checks were used instead, per the task's lean-testing strategy.)*

---

## 11. REMAINING BOTTLENECKS

- `/admin/products` (32 q) is the highest remaining page; it still renders 4 paginated
  collections (3 product tabs + categories), each 15 rows with distinct stock queries that
  are already memoized per row. Could be reduced further only by merging the collection
  queries, which risks altering the tab UX — left unchanged per behavior-first rule.
- `SaleProfitService` still runs a handful of bounded single queries per sale (consumption
  sums/exists on `production_material_consumptions`); these are constant, not N+1.
- Data volumes are currently small; wall-clock gains will be most visible as data grows
  (N+1 removal scales linearly).
- The Sidebar renders ~30 `@can` directives per layout; for non-admin users this hits the
  Spatie permission cache once, for admin it is short-circuited by `Gate::before` — already
  optimal.

---

## FINAL RESPONSE

**RESULT:** PASS

**FILES CHANGED:**
- `app/Http/Controllers/Admin/ProductController.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Services/SaleProfitService.php`
- `app/Http/Controllers/Admin/SaleController.php`
- `app/Http/Controllers/Admin/StockController.php`
- `database/migrations/2026_09_04_100000_add_performance_indexes_batch_05.php` (new)

**HIGH-IMPACT OPTIMIZATIONS:**
- Products index: removed dead un-rendered collections/filter (~180 + 30 queries gone).
- Sale show: request-scoped memoization of per-material profit-service lookups + batch
  BOM/latest-purchase eager loads.
- Stock index: pre-fetched all items + `purchase.expenses` in one batch query.
- Category `withCount` accessor honored; request-scoped product accessor memoization.

**QUERY REDUCTIONS (isolated):**
- `/admin/products`: 215 → 32
- `/admin/stock`: 44 → 17
- `/admin/sales/77`: 38 → 33 (win scales with BOM rows/items)
- All other pages already 9–20 (verified, no degradation).

**INDEXES ADDED:**
- `purchase_expenses.purchase_id` (`purchase_expenses_purchase_id_index`)
- `sale_items.product_id` (`sale_items_product_id_index`)

**CACHE CHANGES:**
- None (request-scoped memoization only; no persistent cache of financial/inventory data).

**BUSINESS LOGIC CHANGED:** NO
**DATABASE SEMANTICS CHANGED:** NO
**UI/WORKFLOW CHANGED:** NO
**FORMULAS CHANGED:** NO
**ACCOUNTING CHANGED:** NO
**FIFO/COSTING CHANGED:** NO

**REGRESSION RESULT:** PASS
(Sales 76/73 frozen values + Sale 77 commercial figures identical; PO 37 intact; all touched
pages render 200; memo/eager A/B comparisons bit-identical.)

**TOP REMAINING BOTTLENECKS:**
- `/admin/products` still builds 4 paginated collections (32 q) — kept as-is to preserve tab UX.
- Bounded, non-N+1 single queries in `SaleProfitService` (consumption stats).
- Gains scale as data grows.

**SAFE FOR CLIENT USE:** YES

STOP.
