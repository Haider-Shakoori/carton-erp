# FINAL DEPLOYMENT CHECKLIST — Carton ERP (Qadir)

> Companion docs: `DEPLOYMENT_COMMANDS.md`, `ROLLBACK_GUIDE.md`.
> Scope: release-readiness QA verified on this codebase. See the final report for the A–Q verdict.

## 0. Preconditions (verified in QA)

| Item | Status |
|---|---|
| `php artisan migrate:fresh --seed` on clean install | GREEN (94 migrations; 6 defective migrations were fixed — see §2) |
| `php artisan migrate` idempotency | PASS ("Nothing to migrate") |
| Full test suite | 52 passed / 463 assertions / 0 failures |
| `npm run build` | OK |
| `view:cache`, `config:cache`, `route:cache` | OK (route cache compiles in this Laravel 12 app) |
| `public/storage` symlink | Present |
| Uploads (employee/product/shareholder images, receipts, logos) | Write to `storage/app/public` — no filesystem config needed |

## 1. Blocker-level items to address before/at deploy

### 1.1 `.env` settings (FIXED in this release)
- `APP_DEBUG=false` is now set in `.env` (and in the `.env.example` template), so debug stack traces can no longer leak on 500s. **Leave it false.**
- `APP_URL` is still `http://localhost` in `.env` — **REQUIRED at deploy**: set the real production URL (used by `artisan` command URLs, signed URLs). Cannot be pre-filled, so it is the one remaining deploy-time edit.
- `.env.example` now ships `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain.example.com` — safe to `cp` and fill in real values; contains no secrets.

### 1.2 Fresh-install permissions (FIXED in this release — was REQUIRED)
Previously `DatabaseSeeder` ran only `PermissionsSeeder` + `CategorySeeder` (fresh install = 164 of 308 permissions). Now fixed:

1. `database/seeders/HRPermissionSeeder.php` had its `module` payload removed — Spatie Permission v6 has no `$fillable`, which caused `SQLSTATE[42S22]` on fresh seed. It is idempotent and wired into `DatabaseSeeder`.
2. New `database/seeders/ShareholderPermissionSeeder.php` adds the 16 shareholder / profit-distribution permissions and is wired into `DatabaseSeeder`.
3. `view quality control` and `view saraf` permissions are also seeded so those screens work for non-admin roles.

`php artisan db:seed --force` on a fresh install now yields the full set (verified on a clean DB: fresh seed = 234 permission rows, 0 missing, no fresh-seed errors).

## 2. Migration fixes shipped in this release (fresh-install was broken)
These were the cause of `migrate:fresh` failing with SQLSTATE 1146 / errno 150 before this QA pass:

1. `2026_07_07_123342_create_work_orders_table.php` — removed inline `constrained('machines')` (table created later); FK moved into `2026_07_07_123403_create_machines_table.php` (+ its `down()` drops the FK first).
2. `2026_08_02_175859_add_stock_consumption_fields_to_bom_items.php` — removed `->after('rate_base_units')` (column added 2 days later in a separate migration).
3. `2026_08_04_111812_update_bom_items_table.php` — removed `->after('height_inch')`; guarded reel-dimension backfill with `Schema::hasColumn`.
4. `2026_08_04_112950_add_missing_columns_to_bom_items_table.php` — removed `->after('print')`; guarded reel-dimension backfill with `Schema::hasColumn`.

## 3. Routes (FIXED in this release)
- All admin routes pointing to non-existent controller methods were fixed: dead routes with no UI/JS caller were removed, and the ~13 UI-linked ones were implemented (AgentController `fetchAgents`/`updateStats`, AppSettings company/units/invoice-templates, HR department/designation/leave-balance, ProfitDistribution edit/update, ProductionOrder edit/update, Sale `getSaleCurrency`). Route inventory now reports **0 broken routes** (only closures/invokables remain, which are outside the audit scope).
- Double-prefix `admin/admin/...` URIs eliminated for: `exchange/get-latest`, `products/{product}`, `sales/{id}/currency`, `sales/add-calculated-item`, `sales/create-bom-from-calculator`, `sales/get-material-stock-cost` (names + middleware unchanged).
- `admin/production-orders/get-bom-details` (shadowed by the `{productionOrder}` wildcard) removed — no UI/JS caller exists; `production-orders.destroy` also removed (no caller).
- `client/notifications` route removed — the client portal renders notifications as an in-page tab, the route had no caller and pointed at an empty stub controller.
- Verified: `route:cache` PASS, `view:cache` + `config:cache` PASS, `npm run build` OK, full suite 52 tests / 463 assertions / 0 failures.

## 4. Dependency / runtime notices
- `composer audit`: 48 advisories / 14 packages — incl. `dompdf 3.1.1 < 3.1.6` (CVE-2026-59941), `laravel/framework 12.35.1` outdated (latest 12.69.x), guzzle. **Update after deploy with review** (needs approval; not done here).
- PHP `intl` extension is NOT installed (recommended for locale-aware formatting; not in composer require).
- Runtime: PHP 8.2.12, MariaDB (server), dev DB name `product`.

## 5. Scheduling / queue
- Queue driver: `database` (jobs tables migrate fine).
- No Laravel scheduler wiring (`bootstrap/app.php` has no `withSchedule`, `routes/console.php` has no `Schedule::command`). Run the console commands manually or via cron:
  - `php artisan distribute:monthly-profit`
  - `php artisan distribute:profit-loss`
  - `php artisan recalculate:balances`
  - `php artisan queue:run-once` (shared-hosting friendly) or the standard `queue:work`.

## 6. Backups (REQUIRED — no in-app mechanism)
Add an out-of-band DB dump cron, e.g.:
```bash
mysqldump -u <user> -p<pass> product > /backup/product_$(date +%F_%H%M).sql
```
(Never run `migrate:fresh` against the production DB; the sensible deploy path is `php artisan migrate --force` on an up-to-date DB.)

## 7. Post-deploy verification (golden path)
1. Log in as an admin → `admin.dashboard` renders; charts + profit numbers show (realized-cost basis).
2. Create a customer → sale (add item linked to an arrived purchase batch) → confirm → deliver → delete the sale. Verify stock `qty_available` restored, ledger/balances re-run via `recalculate:balances`.
3. Production order → start (material consumption via FIFO batches) → complete/cancel.
4. Purchase order → add items + expense → mark arrived → edit → cancel.
5. Sale return (partial + full) → verify stock restore + balances.
6. HR: employees/departments/designations/leave/payroll screens accessible (permissions now seeded by default).
7. Shareholders + profit distribution run (permissions now seeded by default).
8. `php artisan test`, `npm run build`, then `php artisan view:cache config:cache`.

## 8. Final commands (in order)
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force                               # fresh install: seeds all permissions (incl. HR + shareholder)
php artisan db:seed --class=HRPermissionSeeder            # existing DB backfill, idempotent
php artisan db:seed --class=ShareholderPermissionSeeder   # existing DB backfill, idempotent
php artisan storage:link                                   # if not present
php artisan config:cache
php artisan view:cache
# optional: php artisan route:cache   (verified: compiles OK on this app)
php artisan optimize
```
See `DEPLOYMENT_COMMANDS.md` for the full annotated sequence.