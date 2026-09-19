# CLIENT DEMO SCRIPT — CARTON ERP (10–15 minutes)

**Presenter note:** Log in as **Super Admin** (id=1) so all permissions are
available. **Hard-refresh** (Ctrl/Cmd+Shift+R) once before starting so the UI fixes
are visible. Use the retained FINAL-DEMO records only — **do not create a new
transaction** during this session.

Legend: 👉 = what to click; 🗣 = what to say; ⭐ = value to point out; ✅ = what the
client should understand.

---

## 1. DASHBOARD — (1 minute)

**SCREEN:** Dashboard (`admin.dashboard`)
**ACTION:** Open Dashboard after logging in.
**SAY:** "This is the overall business view — today's sales, revenue, low stock and
recent activity all in one place."
**POINT OUT:** ⭐ Total revenue / recent sale cards.
**EXPECTED VALUE:** Recent activity includes Sale FINAL-DEMO-SO-001; revenue in AFN.
**UNDERSTAND:** ✅ The ERP gives a single live picture of money + stock + orders.

---

## 2. SUPPLIER — (45 seconds)

**SCREEN:** Supplier #20 (FINAL-DEMO Paper Supplier) — `admin.suppliers.show`
**ACTION:** 👉 Menu → Suppliers → open "FINAL-DEMO Paper Supplier".
**SAY:** "Suppliers are tracked as accounts, with their balance and transaction
history."
**POINT OUT:** ⭐ Supplier details + outstanding balance.
**EXPECTED VALUE:** Purchase #FINAL-DEMO-PO-001 linked; balance = AFN 264,000 credit.
**UNDERSTAND:** ✅ Every purchase and payment hits the supplier's ledger.

---

## 3. RAW MATERIAL — (1 minute)

**SCREEN:** Raw Material #165 (FINAL-DEMO Kraft Paper 150 GSM) — `admin.products.show`
**ACTION:** 👉 Menu → Products/Raw Materials → open "FINAL-DEMO Kraft Paper 150 GSM".
**SAY:** "This is the raw paper. We buy it in rolls, but production consumes it by
weight."
**POINT OUT:** ⭐ The batch line: `10 rolls × 500 kg = 5,000 kg`; remaining
`4,939.0324 kg`; landed `= $0.95/kg`.
**EXPECTED VALUE:** 4,939.0324 kg remaining; $0.95/kg landed.
**UNDERSTAND:** ✅ Same material shown in **rolls** (procurement) and **kg**
(production/inventory).

---

## 4. PURCHASE ORDER #37 — (1.5 minutes)

**SCREEN:** Purchase #37 (FINAL-DEMO-PO-001) — `admin.purchase-orders.show`
**ACTION:** 👉 Menu → Purchase Orders → open #FINAL-DEMO-PO-001.
**SAY:** "We received 10 rolls of paper. The base cost plus freight and customs give
us the true 'landed' cost per kilogram."
**POINT OUT:** ⭐ Base **$4,000**; Freight **$500** + Customs **$250** = expenses
**$750**; total landed **$4,750**; ⭐ landed **$0.95/kg**; item line shows
`10 rolls × 500 kg = 5,000 kg`.
**EXPECTED VALUE:** Base $4,000 · Expenses $750 · Landed $4,750 · $0.95/kg.
**UNDERSTAND:** ✅ The real cost of the paper is base price **plus** freight and
customs, divided to per-kg.

---

## 5. BOM #23 — (1.5 minutes)

**SCREEN:** BOM #23 (FINAL-DEMO 200ml Carton BOM) — `admin.bom.show`
**ACTION:** 👉 Menu → BOM → open #23; view the Commercial calculation section.
**SAY:** "The Bill of Materials converts the carton's size and paper into a
**commercial rate** per carton."
**POINT OUT:** ⭐ Paper **4.6452** + Standard Work/Profit **1.8581** + **Print 20.00**
= Net/Selling Rate **26.50 AFN/carton**. (40% is applied to Paper only, **not** to
Print.)
**EXPECTED VALUE:** Paper + Work + Print = Net ≈ 26.50 AFN/carton.
**UNDERSTAND:** ✅ Selling price = Paper + 40% work margin + Print, and the 40% is
**not** applied to Print.

---

## 6. CUSTOMER / PRODUCT — (45 seconds)

**SCREEN:** Customer #21 (FINAL-DEMO Pharma Customer) — `admin.customers.show`; then
Product #166 (FINAL-DEMO 200ml Medicine Carton) — `admin.products.show`
**ACTION:** 👉 Customers → #21; then Products → #166.
**SAY:** "Here is our pharma customer and the finished carton we produce for them."
**POINT OUT:** ⭐ Customer profile; finished carton with its BOM.
**EXPECTED VALUE:** Customer #21; Product #166 (finished good, carton).
**UNDERSTAND:** ✅ We already saw the customer, the raw material, and the BOM — now
they meet in the sale.

---

## 7. SALE ORDER #76 — (2 minutes)

**SCREEN:** Sale #76 (FINAL-DEMO-SO-001) — `admin.sales.show`
**ACTION:** 👉 Menu → Sales → open #FINAL-DEMO-SO-001; scroll to the item + profit card.
**SAY:** "The sale combines the commercial price and the production cost. Two
different things meet here."
**POINT OUT:**
- Commercial/Selling total ⭐ **AFN 26,503.23** (1,000 cartons × 26.50).
- Estimated Production Cost ⭐ **AFN 3,822.72**.
- (Actual production, after completion) ⭐ **AFN 3,822.67**.
- Actual Realized Profit ⭐ **AFN 22,680.56**; margin ⭐ **85.58%**.
**EXPECTED VALUE:** Selling 26,503.23 · Est. 3,822.72 · Actual 3,822.67 · Profit
22,680.56 · 85.58%.
**UNDERSTAND:** ✅ Revenue (commercial) is separated from production cost (actual
FIFO material); the app shows estimated vs actual and the resulting profit.

---

## 8. PRODUCTION ORDER #79 — (2 minutes)

**SCREEN:** Production Order #79 — `admin.production-orders.show`
**ACTION:** 👉 Menu → Production Orders → open #79.
**SAY:** "This is where raw paper becomes finished cartons."
**POINT OUT:** ⭐ Production qty **1,000 cartons**; Planned Material **60.9676 kg**;
Planned Production ⭐ **AFN 3,822.72**; Actual FIFO **60.9676 kg**; Actual Production
⭐ **AFN 3,822.67**; Variance ≈ **−0.05 AFN**; status **Completed**.
**EXPECTED VALUE:** 1,000 cartons · 60.9676 kg · 3,822.72 planned · 3,822.67 actual
· variance −0.05 · Completed.
**UNDERSTAND:** ✅ Planned vs actual matches (tiny variance from rounding); the 40%
is clearly labelled as commercial profit, **not** production labour.

---

## 9. FIFO / STOCK — (1.5 minutes)

**SCREEN:** Raw material #165 stock — `admin.products.show`; and PO #79 material
status/PMC.
**ACTION:** 👉 Re-open #165 (or Stock → #165).
**SAY:** "The system consumed 60.9676 kg from our oldest batch first — FIFO — at the
batch's landed cost."
**POINT OUT:** ⭐ Batch #41: consumed 60.9676 kg; remaining **4,939.0324 kg**; landed
**$0.95/kg**.
**EXPECTED VALUE:** Remaining 4,939.0324 kg at $0.95/kg.
**UNDERSTAND:** ✅ Stock decreases correctly and costing uses the exact batch the
material came from.

---

## 10. PROFIT — (1 minute)

**SCREEN:** Sale #76 profit card — `admin.sales.show`
**ACTION:** 👉 Reopen the Sale #76 profit card.
**SAY:** "Here's the bottom line: standard margin from the 40% work is 7.01% of
revenue; after real production cost, actual profit and margin are shown."
**POINT OUT:** ⭐ Standard margin **7.01%**; Actual profit **22,680.56**; Actual
margin **85.58%**.
**EXPECTED VALUE:** 7.01% / 22,680.56 / 85.58%.
**UNDERSTAND:** ✅ The app distinguishes the standard commercial markup from the
actual realized profit.

---

## 11. ACCOUNTING — (1 minute)

**SCREEN:** Customer #21 ledger + Supplier #20 ledger — `admin.customers.show`,
`admin.suppliers.show`
**ACTION:** 👉 Customers → #21 ledger; Suppliers → #20 ledger.
**SAY:** "Every sale and purchase is automatically posted to customer and supplier
accounts."
**POINT OUT:** ⭐ Customer #21 debit **26,503.23**; Supplier #20 credit **264,000**
(+ expenses to agent account).
**EXPECTED VALUE:** Customer −26,503.23 · Supplier +264,000.
**UNDERSTAND:** ✅ Accounting entries are generated automatically from orders — no
double entry hand-work.

---

## 12. REPORTS — (2 minutes)

**SCREEN:** Sales / Production reports, Stock report — `admin.sales.index`,
`admin.production-orders.index`, stock screens
**ACTION:** 👉 Menu → Sales/Production/Stock reports; **clear date filters**; find
the FINAL-DEMO records.
**SAY:** "Reports pull the same verified records we just walked through."
**POINT OUT:** ⭐ Sales report shows SO #FINAL-DEMO-SO-001 (26,503.23); Production
shows PO #79 (3,822.72/3,822.67); Stock shows Kraft at 4,939.0324 kg remaining.
**EXPECTED VALUE:** The exact FINAL-DEMO figures appear, with no duplicate rows.
**UNDERSTAND:** ✅ All screens and reports are consistent — one source of truth.

---

## WRAP-UP — (30 seconds)

**SAY:** "We followed one transaction from buying paper to delivering cartons — the
cost, stock, FIFO, profit, and accounts all update automatically and tie out."
**EXPECTED VALUE:** A single coherent story end-to-end.
**UNDERSTAND:** ✅ The ERP connects purchase → BOM → sale → production → stock →
profit → accounting.
