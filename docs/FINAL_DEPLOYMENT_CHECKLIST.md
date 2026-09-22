# FINAL DEPLOYMENT CHECKLIST — Carton ERP

This checklist is the production handoff for the current Carton ERP release. The automated GitHub Actions enterprise QA workflow is the source of truth for code-level release gates.

## 1. Mandatory CI release gate

Do not deploy a revision unless its exact commit passes all of these checks:

- PHP dependency installation
- `npm ci`
- `npm run build`
- `npm audit --audit-level=high`
- PHP syntax checks for release-critical services, controllers, seeders and migrations
- fresh MySQL 8 install with `php artisan migrate:fresh --seed --force` in CI only
- `php artisan view:cache`
- `php artisan erp:readiness`
- focused production, FIFO, carton, reel, sale-order, business-unit and enterprise regression suites
- complete `php artisan test` suite
- `composer audit --no-interaction`

Never run `migrate:fresh` against production.

## 2. Production environment

Before enabling traffic, verify:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` is the real HTTPS production URL
- `APP_KEY` is stable and already provisioned
- for a brand-new seeded installation, `INITIAL_ADMIN_PASSWORD` is set to a strong one-time value
- production database credentials are correct
- `QUEUE_CONNECTION` is a persistent production-capable driver (the supplied example uses `database`)
- session/cache configuration is appropriate for the host
- storage is writable and `public/storage` is linked
- TLS/HTTPS is enforced by the web server or reverse proxy

Run `php artisan erp:readiness --strict` against the production environment after deployment. Resolve every failure before enabling traffic.

## 3. Data protection

Take and verify a database backup immediately before every production migration. Store backups outside the application directory and test restore procedures periodically.

For an existing database, the only normal schema command is:

```bash
php artisan migrate --force
```

Do not regenerate `APP_KEY` on an existing production installation.

## 4. Scheduler and queue

Laravel scheduling is active in `routes/console.php`. Current scheduled controls include:

- stock-control synchronization daily (default 08:15)
- stock-control weekly review (default Monday 08:30)
- stock-notification synchronization every 15 minutes
- queued stock-notification drain every minute when enabled and a persistent queue driver is configured

The host must execute Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Use a supervised `php artisan queue:work` process where possible. On constrained/shared hosting, the application's scheduled queue-drain command provides the supported fallback.

## 5. Functional post-deploy smoke

Verify the following with controlled test records or staging data:

1. Authentication, admin dashboard, navigation and permissions.
2. Settings can switch business separation off (one unified company) or on.
3. With separation enabled, the top navigation switches between **3D Carton** and **Syrup Pack**.
4. Customers and employees/HR remain shared, while operational records and dashboards remain separated by active business unit.
5. Per-user business-unit access restricts users to 3D Carton, Syrup Pack or both as configured; authorized management reporting can consolidate allowed units.
6. Purchase/order/receipt flow updates inventory correctly and preserves landed-cost/FIFO behavior.
7. Sale order, quotation/BOM breakdown, confirmation, delivery and returns work without changing BOM cost semantics.
8. Production start/completion accepts actual produced quantity and actual material consumption, updates inventory, and preserves FIFO costing and reel/remnant controls.
9. Production approval/close/reopen/reversal controls and immutable event history work as authorized.
10. Warehouse/location/bin balances and stock transfers reconcile with authoritative batch stock.
11. Procure-to-Pay controls (request, RFQ/comparison, PO, receipt, supplier invoice/payment) enforce approvals and matching.
12. Accounting postings balance, fiscal-period controls work, and management financial reports render.
13. BOM revisions, effective dates, approval and locked-history governance operate correctly.
14. Public self-registration is unavailable, client-portal access requires an active linked client account, and one client cannot access another client's account-scoped data.
15. `GET /up` returns healthy after caches are rebuilt.

Production scheduling/machine planning and QC/quality-control modules are intentionally outside this release scope.

## 6. Release commands

Use the annotated sequence in `docs/DEPLOYMENT_COMMANDS.md`. Keep `docs/ROLLBACK_GUIDE.md` available before starting the deploy.
