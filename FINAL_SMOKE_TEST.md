# Final Presentation Smoke Test

## Result

NOT READY

## Test Results

| Step | Result | Note |
|---|---|---|
| Login | PASS | Existing authenticated session reached Dashboard; no 500 or browser error. |
| Dashboard | PASS | Dashboard and widgets loaded; demo navigation worked. |
| PO-0017 | PASS | Test supplier, USD, 2 rolls at $5, $10 total, $0 expenses; page loaded cleanly. |
| Stock | PASS | Kraft Paper 120 GSM: 22,012.00 in, 383.09 used, 21,628.91 available; no negative quantity. |
| BOM-DIHIIIVJ | PASS | 40% work, formula inputs, two materials, $0.98/؋65.21 material and ؋91.30 total displayed. |
| SO-202608-0009 | FAIL | Summary is plausible, but item profit displays mixed currency: `؋25.74 ($90.31 USD)`. |
| Production | PASS | One linked completed production order exists for SO-202608-0009, product 120ml Syrup Box, quantity 1, BOM-DIHIIIVJ. |
| Material Breakdown | PASS | Two 0.1719-roll lines reconcile with Material Status rounded to 0.17 each; no undefined values. |
| Production Cost | FAIL | Same page shows top Total Cost ؋0.00, cost summary ؋10.67/$0.16, while sale actual production cost is ؋18.16. |
| Payment | FAIL | The 0.01 AFN QA transaction exists once, but it is not applied to the sale; invoice remains paid ؋0.00 and due ؋91.30. |
| Customer Ledger | FAIL | Sale debit and QA credit appear, but every transaction row shows running balance `0.00`; summary balance is 2,742.61 Dr. |
| Profit | FAIL | Sales list is $1.38 revenue, $0.99 COGS, $0.39 profit, 28.3%; sale item row still shows impossible `$90.31 USD` profit. |
| Invoice / Print | PASS | Customer, order, date, product, quantity, unit price, total, currency, paid and due fields render correctly. |

## Presentation Blockers

- SO-202608-0009 item profit mixes AFN and USD: `؋25.74 ($90.31 USD)`.
- Production cost is inconsistent across the production page and sale actual-cost summary.
- The QA payment is not linked/applied to SO-202608-0009.
- Customer ledger running balances all display `0.00` despite non-zero debits, credit, and summary balance.

## Minor Cautions

- Production Material Status shows the availability captured for the production order (21,629.25), while current Stock shows 21,628.91.
- No JavaScript errors or HTTP 404/419/422/500 responses were observed during the tested route.

## Safe Demo Route

Login → Dashboard → Purchase Orders → PO-0017 → Stock Inventory → BOM List → BOM-DIHIIIVJ → Customers → Invoice preview for SO-202608-0009

## Avoid

- Avoid the SO-202608-0009 item-level Profit (AFN) cell and any USD profit comparison.
- Avoid discussing or comparing production costs.
- Avoid demonstrating the QA payment as payment for SO-202608-0009.
- Avoid the Qadir customer ledger and running-balance column.
- Avoid presenting the full requested golden path as reconciled end to end.
