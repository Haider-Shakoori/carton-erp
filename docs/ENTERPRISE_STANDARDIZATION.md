# Enterprise Standardization Roadmap

This roadmap hardens the carton ERP without adding factory scheduling/machine planning or quality-control modules.

## Scope being applied

1. Production control, approvals, close/reopen/reversal, and immutable transition history.
2. Inventory control with warehouse/location foundations, transfers, negative-stock prevention, and existing cycle-count/reconciliation controls retained.
3. Purchase-to-pay controls: approval, receipt, supplier invoice, and matching controls on top of the current PO/landed-cost workflow.
6. Accounting integration: formal journal/period controls while retaining the existing customer/supplier sub-ledgers.
7. Cost accounting: planned/BOM vs actual FIFO, usage/waste and purchase-price variance.
8. Master-data governance: version/effective-date controls, duplicate protection, and controlled deactivation.
9. Security, roles and audit: granular permissions, reason-required sensitive actions, immutable control events, and non-destructive audit retention.
10. Management reporting/KPIs: production yield, material variance, inventory valuation, purchasing performance, customer profitability and margin.
11. Enterprise hardening: transactional/idempotent services, locking, indexes, health/readiness checks, security audit, full regression coverage and deployment checks.

## Explicit exclusions

- Production scheduling, machine/work-centre/shift planning (former option 4).
- Quality-control workflows (former option 5).

## Architectural rule

Existing source-of-truth rules remain unchanged:
- BOM is planned/theoretical consumption.
- production completion captures actual material consumption;
- FIFO purchase batches and landed cost are authoritative for actual production cost;
- waste is a subset of actual consumption and is never added twice;
- historical sale/BOM/production snapshots must remain reproducible;
- USD and AFN remain the supported currencies;
- no barcode/scanner workflow is introduced.

## Deferred business-unit requirement

The client also requested two operational businesses using the same customers and employees: 3D Carton and Syrup Pack. This should be implemented as shared master data plus separate business-unit dashboards, permissions, production/inventory/profitability views and management responsibility, with a consolidated owner dashboard. This is intentionally deferred until the enterprise-control batches above are complete.
