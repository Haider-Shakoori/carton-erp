# CLIENT PRESENTATION READINESS

## RESULT
**READY WITH NOTES**

The FINAL-DEMO screens are presentable and consistent. A small number of safe,
pure-presentation fixes were applied (labels, units, currency–kg display). One
inherited data-state item and the controlled-demo's high profit margin are the
notes to be aware of for the live session (see Presentation Risks and the Demo
Note below).

---

## UI FIXES MADE

1. **Purchase Order #37 — Local-currency expenses shown as 0 (bug).**
   `purchase-orders/show.blade.php` hard-coded `$totalExpenseAmountLocal = 0` and
   built the local grand total without expenses. The "In ؋ (Order Currency)" card
   thus contradicted the USD card (0 vs $750). Now computed from
   `expenses->sum('amount')` and included in the local grand total. Display-only;
   stored `grand_total` untouched.

2. **Purchase Order #37 — Roll → kg / landed cost not visible (Part 8).**
   Added read-only weight metadata under the purchase item: `10 rolls × 500 kg =
   5,000 kg` and `Landed $0.95/kg` (roll-based batches only), using existing model
   accessors. Helps the client follow "we buy rolls, we track kg".

3. **Production Order #79 — Misleading header (Part 7).**
   When actual consumption exists, the material table header read
   "Estimated BOM Material Breakdown" while the footer said "Actual FIFO Material
   Cost" (contradictory). Changed the actual-consumption header to
   "Material Requirement & Actual FIFO Cost". Label-only.

4. **Raw Material Stock screen — Roll-only stats omitted kg (Part 4/5/11).**
   `StockController::batchMetrics` map did not expose kg. Added (additive, no logic
   change) `unit`, `is_roll_batch`, `kg_per_roll`, `total_weight_kg`,
   `qty_kg_available`, `landed_cost_per_kg`. `products/show.blade.php` batch table
   now shows, for the FINAL-DEMO Kraft batch: `10 × 500 kg = 5,000 kg`,
   `4,939.0324 kg` remaining, and `= $0.95/kg` landed. Non-roll materials unaffected.

---

## FILES CHANGED

- `resources/views/admin/purchase-orders/show.blade.php` (FIX 1, 2)
- `resources/views/admin/production-orders/show.blade.php` (FIX 3)
- `app/Http/Controllers/Admin/StockController.php` (FIX 4 — additive kg fields in
  the batchMetrics display array only; no business/calculation logic changed)
- `resources/views/admin/products/show.blade.php` (FIX 4 — kg display)

Verification performed: `php -l` on StockController (no errors), `php artisan
view:cache` (all templates compiled), and direct view rendering of the changed
screens (purchase show OK, PO show OK, products/stock show OK with the new kg
values present). **No translation files needed changes** — existing `__('ui.*')`
keys already cover the demo screens; the few hard-coded English labels on these
pages were already correct.

---

## BUSINESS LOGIC CHANGED
**NO**

All changes are display-only (presentation labels, decimal/unit formatting, view
computed totals for display, and additive read-only fields in a view-support
array). **Costing, BOM, FIFO, landed-cost, profit, and inventory calculations are
untouched.**

---

## FINAL-DEMO REGRESSION
**PASS**

Read-only verification after UI fixes (Sale #76, PO #79, POM, PMC, batch #41):

| Metric | Value |
|---|---|
| Sale #76 revenue | 26,503.23 |
| SO estimated production | 3,822.72 |
| PO #79 planned (57.92 USD) | 3,822.72 |
| Actual production (PMC) | 3,822.67 |
| Actual realized profit | 22,680.56 |
| FIFO actual | 60.9676 kg |
| Raw remaining (batch #41) | 4,939.0324 kg |

---

## QA #74 REGRESSION
**PASS**

Read-only verification (sale #73, batch #40) — all byte-exact:

| Metric | Value |
|---|---|
| Paper | 4,645.16 |
| Standard Profit | 1,858.06 |
| Net | 6,503.23 |
| SO estimated | 3,822.72 |
| POM required (batch #40) | 60.9676 kg |
| Batch #40 remaining | 4,939.0324 kg |

---

## PRESENTATION RISKS

1. **High controlled-demo profit (85.58%)** — mathematically correct for the
   FINAL-DEMO data (Print revenue 20,000 with no production printing expense),
   but may look unrealistic. See "IMPORTANT DEMO NOTE" section below.
   **Impact: MEDIUM. Mitigation:** explain the scenario scripted for the demo.
2. **Unrelated/legacy test data** — dashboard and reports also contain older QA and
   demo transactions (e.g., the cancelled QA#74 PO #77). **Impact: MEDIUM.
   Mitigation:** use report date filters / the FINAL-DEMO references, and stay on
   the specific FINAL-DEMO records during the presentation.
3. **Login roles/permissions** — if the presenter logs in with a limited role,
   some FINAL-DEMO screens (reports, stock) may be hidden. **Impact: LOW/MEDIUM.
   Mitigation:** demo with an account that has the "view sales / purchase / bom /
   production / stock / customers / suppliers / reports" permissions (Super Admin
   id=1 already qualifies).
4. **Stale browser cache** — after the UI fixes the presenter must hard-refresh to
   see changes. **Impact: LOW. Mitigation:** Ctrl/Cmd+Shift+R before the demo.
5. **Report date filters** — the Sales/Production/Stock reports default to data
   ranges; ensure the FINAL-DEMO records fall inside the chosen range or clear the
   filter. **Impact: LOW. Mitigation:** clear date filters.

---

## FINAL RECOMMENDATION

The application **is ready to present** using the retained FINAL-DEMO chain. For
the live session, do **NOT** create a new transaction during the presentation —
use the already-verified FINAL-DEMO records. Optionally prepare a **separate,
later presentation-only scenario** with a commercially realistic printing expense
(see IMPORTANT DEMO NOTE) if the client questions the 85.58% margin; but the
current FINAL-DEMO correctly proves the non-zero-Print costing path and is the
recommended primary demo.

---

## IMPORTANT DEMO NOTE (Print / Profit realism)

The FINAL-DEMO scenario intentionally has:
- **Print Revenue: AFN 20,000**
- **Actual Printing Production Expense: AFN 0**

Therefore:
- **Actual profit: AFN 22,680.56**
- **Actual margin: 85.58%**

This is mathematically correct for the controlled QA data (Print is a commercial
sales item; the app does not currently record a genuine printing production
expense for this chain), but the high margin may look unrealistic to the client.

**DO NOT alter the transaction or costing to hide this.**

**Recommendation:**
- **Option A (recommended for this demo):** Keep FINAL-DEMO. It cleanly proves
  that non-zero Print flows correctly into the Net Rate without being multiplied
  into the 40% work basis, and that actual production costing (FIFO material) is
  separate from commercial Print.
- **Option B (later, do NOT build now):** Create a separate presentation-only
  scenario with commercially realistic Print and a genuine production printing
  expense **if** the application already supports that genuine expense workflow.

---

## SCREEN MAP (routes/views for the demo)

| # | Screen | Route | View |
|---|---|---|---|
| 1 | Dashboard | `admin.dashboard` | `admin/dashboard/index.blade.php` |
| 2 | Raw Materials | `admin.products.index` | `admin/products/index.blade.php` |
| 3 | Supplier #20 | `admin.suppliers.show` | `admin/suppliers/show.blade.php` |
| 4 | Purchase #37 | `admin.purchase-orders.show` | `admin/purchase-orders/show.blade.php` |
| 5 | Purchase Batch #41 / stock | `admin.products.show` (StockController) | `admin/products/show.blade.php` |
| 6 | BOM #23 | `admin.bom.show` | `admin/bom/show.blade.php` |
| 7 | Customer #21 | `admin.customers.show` | `admin/customers/show.blade.php` |
| 8 | Product #166 | `admin.products.show` | `admin/products/show.blade.php` |
| 9 | Sale #76 | `admin.sales.show` | `admin/sales/show.blade.php` |
| 10 | Production Order #79 | `admin.production-orders.show` | `admin/production-orders/show.blade.php` |
| 11 | Raw Material Stock | `admin.products.show` (#165) | `admin/products/show.blade.php` |
| 12 | Finished Product Stock | `admin.products.show` (#166) | `admin/products/show.blade.php` |
| 13 | Customer Ledger | `admin.customers.show` (#21) | `admin/customers/show.blade.php` |
| 14 | Supplier Ledger | `admin.suppliers.show` (#20) | `admin/suppliers/show.blade.php` |
| 15 | Sales Report | `admin.sales.index` | `admin/sales/index.blade.php` |
| 16 | Production Report | `admin.production-orders.index` | `admin/production-orders/index.blade.php` |
| 17 | Profit Report | `admin.sales.show` (#76) profit card | `admin/sales/show.blade.php` |
| 18 | Stock Report | `admin.stock.index` / `admin.products.show` | `admin/stock/index.blade.php` / `admin/products/show.blade.php` |

*Note:* Reports/Screens 15–17 are surfaced through the module list pages and the
sale/production detail screens (there is no separate "Profit Report" route; profit
lives in the Sale Order detail card and the profit-distribution module).
