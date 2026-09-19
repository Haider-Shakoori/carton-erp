# QA: ROLL→KG Product Weight Implementation

**Status:** PASS
**Fix required:** No

## RESULT

**PASS** — ROLL→KG product-weight implementation is verified correct and safe.

- **Estimated production cost** for Sale #77 correctly shows **"Estimated cost unavailable — roll weight required"** instead of a wrong `463.64` or a misleading `0` treated as authoritative.
- **Commercial quotation** (BOM/Excel formula) is **unaffected** — Sale #77 Standard Work remains `170.51`, Print `20.00`, commercial total `616.80`.
- **Frozen regressions** (Sale #76 FINAL-DEMO and Sale #73 QA) are **byte-for-byte unchanged**.

Roll products now carry a `default_kg_per_roll`, purchases snapshot per-batch `kg_per_roll`/`total_weight_kg`, and every kg-based view (purchase, stock, sale) is aligned. No fabrication of missing historical weights.

---

## PRODUCT

### Create / Edit / Index / Show — PASS

- **Schema:** nullable decimal `default_kg_per_roll` (10,4) added to `products` (after `unit`). Migration:
  `database/migrations/2026_09_03_140000_add_default_kg_per_roll_to_products_table.php` (applied, `php artisan migrate` OK).
- **Model:** `Product::fillable` + `decimal:4` cast for `default_kg_per_roll`.
- **Controller** (`ProductController@store` / `@update`):
  - `default_kg_per_roll` validated `nullable|numeric|min:0.0001`.
  - For **non-roll** units the value is set to `null` (never stored on kg/box/tablet products).
  - For **roll** units the user-supplied value is kept; a supplied `<= 0` value is rejected.
  - **No retroactive** change: existing roll products (e.g. Kraft Paper) with no default are left untouched — no invented weight.
- **Create/Edit modal:** `default_kg_per_roll` field shown (via `#product_kg_per_roll_wrapper`) only when **unit = roll**; JS `toggleProductKgPerRoll()` toggles visibility; the reset clears it; edit loads the stored value from `data-default_kg_per_roll`.
- **Index table:** roll products display a `bi-minecart-loaded` roll icon and `X.XX kg/roll` subtext.
- **Show page:** header badge `X.XX kg / roll` shown for roll products with a default.

## PURCHASE

### Create / Edit / Show — PASS

- **Add-item form** now sends hidden `unit` (`#item_unit`) and, when a **roll** product is selected:
  - `kg_per_roll` field is shown, **prefilled from the product's `default_kg_per_roll`**,
  - the value is **editable per line** (actual shipment may differ from default),
  - `#item_total_weight_display` shows `roll_qty × kg_per_roll` total kg as you type.
- **Per-batch snapshot** is stored on `purchase_items.kg_per_roll` + `total_weight_kg`; product default only seeds future lines — **never retroactive**.
- **Edit-item modal:** shows read-only roll info (`rolls × kg/roll = total kg`, or **"Roll weight missing"** when the batch lacks a weight). Hidden `unit` / `kg_per_roll` inputs are now sent so editing an existing roll line preserves its snapshot; **`min:0`** enforced server-side; an edit that would leave a roll line with neither `kg_per_roll` nor `total_weight_kg` is rejected.
- **Show table:** roll batches render `N rolls × X.XX kg = Y.YY kg` plus `Landed $Z.ZZ/kg`; historical roll batches with no weight display a warning icon **"Roll weight missing"** instead of a fabricated conversion.

## LANDED COST

### Formula — PASS (no code change required)

- Roll purchase landed total (USD incl. expenses) is already allocated **per kilogram**:
  `Landed USD/kg = total landed USD / (roll_qty × kg_per_roll)`.
- Computed in `PurchaseItem::booted()` / `landedCostPerKg()` — already correct; no patch needed.
- **Safety rule enforced:** a roll batch with **no valid kg basis** does **not** produce a `USD/kg`; nothing is divided by zero and nothing is fabricated.

## PRODUCTION

### Costing unit / rate / FIFO — PASS (no code change required)

- Production material costing already consumes **kg** and uses `landedCostPerKg()` when the batch is kg-carrying (roll) — unit kg, rate `USD/kg`.
- **FIFO** (`StockDeductionService` / `ProductionService`) consumes **kg** matched FIFO against batches and costs `consumed kg × batch landed USD/kg` — already correct.
- **Non-roll / direct-KG** products are unaffected (no roll indirection).

## SALE #77

### Commercial + physical + estimate — PASS

Target: draft, qty 10, AFN, rate 66, product `120ml Syrup Box` (95), BOM#1 `BOM-DIHIIIVJ`, Kraft Paper 120 GSM (roll) with `cost_per_unit_usd` 0.78/0.16, no production order, batches `kg_per_roll` NULL.

| Check | Expected | Actual | Result |
|---|---|---|---|
| Paper (commercial) | 426.29 | 426.29 | PASS |
| Standard Work / Profit | 170.51 | 170.51 | PASS |
| Print | 20.00 | 20.00 | PASS |
| Commercial total | 616.80 | 616.80 | PASS |
| Physical kg row1 | 8.59713 | 8.59713 | PASS |
| Physical kg row2 | 1.99454 | 1.99454 | PASS |
| Physical total | ~10.5917 | ~10.5917 | PASS |
| Estimated production cost | **Unavailable — roll weight required** | `estimated_cost_afn = 0`, `estimated_cost_unavailable = true`, UI shows "unavailable" | PASS (not 463.64, not 0-as-authoritative) |
| Status | stays **draft** | draft | PASS |

## SALE UI

### Create / Show / Edit / Index — PASS

- **Create/Show:** when `estimated_cost_unavailable`, the Estimated Production Cost, Estimated Realized Profit, and the USD & Profit card COGS/profit cells show "Estimated cost unavailable — roll weight required" (no misleading dollar amount).
- **Edit:** **Not available** — there is **no** sale edit route (only index/create/store/show/destroy). No change needed.
- **Index:** the Profit column is the **commercial quotation** view (`USD revenue − quotation unit cost`), which is intentionally distinct from production cost. Left as the existing commercial indicator — not redesigned (per scope guard).
- Sales **76/73** (production-order snapshot-driven) are **not** affected by the roll resolution and keep `unavailable = false`, `est_cost_afn = 3822.72`.

## HISTORICAL

### Don't-guess policy — PASS

- No `kg_per_roll` exists on any Kraft Paper batch → by design the estimate is **unavailable**, not guessed.
- Products/stock/purchase views show **"Roll weight missing"** / no kg without inventing a value.
- Product defaults apply **only to future purchases**, never retroactively.
- The user must supply real roll weights on future Kraft Paper purchases before a roll-material production estimate becomes available.

## REGRESSION

### FINAL-DEMO / Frozen-QA / direct-KG / non-roll — PASS

| Sale | est_cost | est_profit | act_profit | std_work | quotation | Status |
|---|---|---|---|---|---|---|
| 76 FINAL-DEMO | 3822.72 | 22680.51 | 22680.56 | 1858.06 | 26502.96 | Unchanged |
| 73 Frozen-QA | 3822.72 | 4306.31 | 0* | 1858.06 | 6502.98 | Unchanged |
| 77 (target) | 0/unavail | 616.80** | — | 170.51 | 616.80 | Correct |

\* actual profit derived from production orders only; 73/76 have production orders → snapshot-driven, independent of new roll resolution.
\*\* est_profit for 77 reflects the commercial value; production-cost estimate intentionally unavailable.

Direct-KG (73) and non-roll products unaffected.

## PROFIT REQUIREMENT

### Scope guard recorded — not implemented

The client's **40% Standard Work / Profit** covers work, salaries, operating expenses, overhead and expected return. Final company profit is residual after real company expenses are paid. Invoice-level realized profit is **not** intended to replace this 40% commercial rule. Recorded as a future task — no changes made.

## FINAL VERDICT

- **SAFE TO CONFIRM Sale #77:** YES — commercial quotation is intact and the roll-material production estimate is transparently marked unavailable rather than wrong.
- **BUSINESS PROFIT LOGIC CHANGED:** NO.

**Open item:** Kraft Paper has no `kg_per_roll` on any batch. Sale #77's Estimated Production Cost remains "unavailable — roll weight required" until a kg-carrying Kraft Paper purchase exists (or the user assigns roll weights on future purchases).
