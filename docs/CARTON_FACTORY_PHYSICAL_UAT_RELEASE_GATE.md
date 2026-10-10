# Carton Factory — Physical UAT and Financial Go-Live Gate

**Scope:** 3D Carton and Syrup Pack. **Status:** OPEN — cannot be approved by GitHub CI alone.

## 1. Incomplete customer BOMs: measured recipes required

- [ ] Open **Products → Carton Weight / BOM Audit** and download the **review-required BOM worksheet**.
- [ ] Identify all current BOMs marked `[REVIEW REQUIRED]` (original client seed: 41 of 171).
- [ ] Confirm the customer's dimensions (length, width, height in mm), ply, flute type, paper combination and GSM with the factory engineer.
- [ ] Confirm any 7-ply board profile from the client/production team. **Never substitute 3-ply/5-ply or invent raw-material split.**
- [ ] Measure representative finished-carton gross weights (g), with documented sample size and production date.
- [ ] Enter *paper-specific* net physical consumption in kg/carton and separately account for glue, ink, spoilage, and moisture. Carton gross weight alone is insufficient to infer paper-layer mass.
- [ ] Recalculate landed kg prices from actual purchase invoices, shipping and roll-weight reference; approve a versioned BOM revision only after independent review.
- [ ] Verify that no placeholder BOM with zero material quantity or review flag can be activated, **including legacy toggle endpoint**.
- [ ] Keep old source records and active BOM revisions for audit traceability.

**Required evidence:** Completed worksheet, source purchase records, engineering-approved recipe and signed weight sample sheet. Do not claim the 41 BOMs are verified until those inputs exist.

## 2. Physical raw-material inventory and production reconciliation

- [ ] Reconcile each paper type and mixing material separately by business unit and warehouse.
- [ ] For each receipt: trace PO → GRN/stock batch → actual landed price per kg (base, transport, other).
- [ ] Test one known paper roll with an estimated net mass, even if the factory cannot weigh the entire 900kg roll; reconcile from cut count/known GSM, dimensions and measured remnant. Record confidence and variance rather than inventing readings.
- [ ] Trace representative 3-ply, 5-ply, and (once certified) 7-ply carton orders through production.
- [ ] Record planned material quantities, actual usage, rejected pieces and resulting per-unit cost.
- [ ] Confirm FIFO batch and current available kg: **Opening + Receipts - Production consumption +/- Authorized adjustment = Closing**.
- [ ] Confirm completion retry does not double-deduct materials or post two GL transfers.
- [ ] Reverse a test completion and verify inventory recovery, GL reversal, and an audit log.
- [ ] Validate one syrup-pack product separately from 3D Carton so no business-unit leakage occurs.

## 3. Finance and shareholder controls

- [ ] Reconcile a realistic purchase, landed expenses, production consumption, finished goods and invoiced sale, with every posting mapped to journal entries.
- [ ] Trial balance debit = credit; balance sheet assets = liabilities + capital + retained earnings (within defined rounding tolerance).
- [ ] Confirm 40% **commercial work/profit markup** is not included as an actual production labor/overhead expense.
- [ ] Block shareholder allocation for any confirmed sale without recorded actual physical production cost.
- [ ] Block overlapping profit distribution dates to avoid double allocation.
- [ ] Check share allocation rounding to 0.01 and 100% ownership across active shareholders.
- [ ] Treat profit allocations as non-cash ledger credits, **never as paid cash**.
- [ ] Approve and post a separate shareholder withdrawal for an actual cash/bank payment. Reconcile withdrawal evidence to bank/cash account and shareholder ledger.
- [ ] Review historical distributions predating the change; an earlier `paid` flag may have been set without any cash withdrawal.
- [ ] Finance owner approves the chosen recognition policy (especially order/date boundaries and actual-vs-estimated cost) before real distributions.

## 4. Software UAT and release decision

Automated tests exercise isolated Ubuntu/MySQL/Chromium environments only. They do not substitute for physical factory UAT.

- [ ] Review latest GitHub run for all three check groups: full Laravel/security, financial/inventory, Chromium.
- [ ] Review screenshots, browser HTML report, and CSV review worksheet in **Chromium ERP Smoke → Artifacts → chromium-erp-smoke-report**.
- [ ] Confirm desktop/mobile UI at factory resolutions, operators' permissions, printer and local/network behavior.
- [ ] Back up and restore a staging copy of real data and rehearse new migrations before deployment.
- [ ] Sign off ERP financial policy, BOM recipes, inventory reference quantities, and staging/UAT defects.
- [ ] Deploy only through the approved server release process after explicit user approval.

| Gate | Owner | Evidence/date | Decision |
| --- | --- | --- | --- |
| BOM formulas, 41 review cases | Factory engineer | Pending | HOLD |
| Roll/physical inventory | Warehouse supervisor | Pending | HOLD |
| Shareholder/cash policy | Finance lead | Pending | HOLD |
| Production end-to-end | Factory operator | Pending | HOLD |
| Staging backup/restore and deploy | Deployment owner | Pending | HOLD |

**No physical measurements, production server operations or historical accounting corrections are performed by this document.**
