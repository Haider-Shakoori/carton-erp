# CLIENT DEMO QA — LIKELY CLIENT QUESTIONS & ANSWERS

Short, non-technical answers for the live demo. Where a question touches capability
we have NOT verified in the app, it is marked:

**VERIFY BEFORE ANSWERING CLIENT**

---

## 1. "Where does the 40% come from?"
The Standard Work / Profit is a **commercial markup of 40% on the paper's
commercial rate** (Paper Rate × 40%). It is how we build profit into the selling
price, not a factory wage figure. On the BOM #23 screen you can see it as
"Standard Work / Profit 40%" between Paper and Net Rate.

## 2. "Why is Print not included in the 40%?"
The 40% markup is applied to the **paper material** cost, and Print is a separate,
per-carton charge. We do not multiply profit on top of print — print is added to the
rate as its own line. So Paper + 40% work + Print = Net Rate.

## 3. "Why do we purchase in rolls but production shows kg?"
Rolls are the buying unit (10 rolls × 500 kg). The system converts roll weight into
kilograms so production consumes an exact weight (60.9676 kg), and costing and
remaining stock are tracked by kg. Both views are the same paper, just different
units.

## 4. "Where are freight/customs included?"
They are added to the purchase order as expenses. The base cost plus freight and
customs gives the **landed cost** — e.g. $4,000 + $750 = $4,750, which becomes
$0.95/kg. Production and FIFO costing use this landed per-kg figure.

## 5. "Why are SO and PO costs the same?"
The Sale Order's estimated production cost and the Production Order's planned cost
are **both derived from the same BOM material requirement** (60.9676 kg × landed
rate). Because they use identical inputs, they match (both ≈ AFN 3,822.72).

## 6. "Why is actual cost slightly different from estimated?"
Estimated uses the rounded/planned quantities; actual uses the **real kilograms
consumed from the exact FIFO batch**. The small difference (≈ −0.05) is rounding
between planned and actual; the system reports it as Cost Variance.

## 7. "How does FIFO select the purchase batch?"
First-In-First-Out: the system consumes from the **oldest arriving batch first** at
that batch's stored landed cost. For the FINAL-DEMO the only consuming batch is
#41, so it was used at $0.95/kg.

## 8. "Where can I see remaining material?"
On the **Raw Material** detail (product #165) — the batch table shows remaining
quantity and, for roll-based paper, the remaining **kg** (4,939.0324 kg) and landed
cost per kg. The Production Order also shows material availability before start.

## 9. "How is actual profit calculated?"
Actual Realized Profit = Selling Total (26,503.23) − Actual Production Cost
(3,822.67) = 22,680.56. The margin is that profit divided by the selling total
(85.58%). Print revenue is commercial; there is no production printing expense in
this chain, which is why the margin is high (see note in
CLIENT_PRESENTATION_READINESS.md).

## 10. "Can I manually change the selling price?"
Yes — the sale item has pricing controls (apply discount / reset price) and a BOM
can carry a selling price. In this demo we left the price at the **Net Rate** from
the BOM (26.50 AFN/carton) with no extra markup.

---

## Questions marked VERIFY BEFORE ANSWERING CLIENT

- **"Can we record an actual printing/printing-shop expense against a production
  order?"**
  The FINAL-DEMO chain has Print as a **commercial** sales item with no real
  production printing expense. Whether the app can currently post a genuine
  printing production expense (and have it flow into Actual Production Cost) was
  **not** exercised in this QA.
  **VERIFY BEFORE ANSWERING CLIENT** (recommend a later option-B scenario).

- **"Can I post a customer payment / settle the receivable within the demo?"**
  Payments/receipts settle the customer debit (26,503.23) and supplier credit
  (264,000). The posting UI/workflow was not exercised in this QA.
  **VERIFY BEFORE ANSWERING CLIENT.**

- **"Which reports can I export as PDF?"**
  Some list pages expose export actions (e.g. customers, exchange, remittance,
  debtors/creditors). The precise export availability for each module was not
  exhaustively tested in this QA.
  **VERIFY BEFORE ANSWERING CLIENT.**

- **"Is this the real live data?"**
  The FINAL-DEMO records are demonstration/test data created for the client
  walk-through, kept intact as the reference chain. Explain that the numbers are a
  controlled scenario to show the full flow.
  **(Disclose as demo data, not a question to hide.)**

---

## Quick reference — the numbers to know

| Item | Value |
|---|---|
| Purchase base | $4,000 |
| Expenses (freight + customs) | $750 |
| Landed | $4,750 · $0.95/kg |
| Rolls / weight | 10 rolls × 500 kg = 5,000 kg |
| Paper / Work / Print | 4,645.16 / 1,858.06 / 20,000 |
| Net / Selling | 26,503.23 (26.50/carton × 1,000) |
| SO estimated / PO planned | 3,822.72 / 3,822.72 |
| FIFO | 60.9676 kg @ $0.95/kg |
| Actual production | 3,822.67 |
| Remaining kraft | 4,939.0324 kg |
| Actual profit / margin | 22,680.56 / 85.58% |
| Standard margin | 7.01% |
