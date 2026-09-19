# ROLLBACK GUIDE — Carton ERP (Qadir)

Companion docs: `FINAL_DEPLOYMENT_CHECKLIST.md`, `DEPLOYMENT_COMMANDS.md`.

Goal: return to the previously released code + database in the least risky way.

## 1. Code rollback
```bash
git checkout <previous-release-tag-or-sha>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear && php artisan route:clear && php artisan view:clear
php artisan optimize:clear
php artisan storage:link        # re-verify the symlink
```
Restart PHP-FPM / web server.

## 2. Database rollback
Two tiers, in order of preference:

### Tier 1 — reverse the schema (use only innermost migrations of THIS deploy)
```bash
php artisan migrate:rollback --step=<N> --force
```
- `N` = number of migrations that ran during this release. Verify first:
  ```bash
  php artisan migrate:status | tail -n 30
  ```
- Caution: `down()` only reverses the migrated deltas. Any **data** written by this release (new sales, productions, ledgers) is NOT reverted.

### Tier 2 — restore a backup (data loss recovery)
```bash
# 1. Restore the most recent pre-deploy dump
mysql -u <user> -p<pass> product < /backup/product_<pre-deploy-timestamp>.sql
# 2. Re-run migrations to the previous release level if the dump is older than the last release's schema
php artisan migrate --force
# 3. If stale caches are possible:
php artisan optimize:clear
```
> Ensure a DB dump is taken immediately BEFORE every deploy (see checklist §6). Without a dump, Tier 2 is impossible.

## 3. Post-rollback verification
1. `php artisan migrate:status` — confirm no "pending" rows that the rollback expects.
2. Login → admin dashboard renders, profit numbers consistent.
3. Sample recent sale + production order + shareholder balances; re-run `php artisan recalculate:balances` and compare.
4. Rebuild public assets/caches (`npm run build`, `view:cache`).

## 4. Never do this in production
- `php artisan migrate:fresh` (destroys all data)
- `composer update` on the live host
- `php artisan key:generate` after go-live (invalidates all sessions/encrypted values)
- Deleting `storage/app/public/**` (user uploads live there)

## 5. Rolling back FROM the route/permission-fix release
This release contains no DB migrations — its DB-side changes are **additive permission rows** (created by seeders, not migrations):
- If you roll the code back, the extra `permissions` / `model_has_permissions` rows seeded by `HRPermissionSeeder` and `ShareholderPermissionSeeder` are harmless leftovers. They are idempotent and re-apply cleanly on the next forward deploy; you do not need to delete them.
- `.env` / `.env.example` changes (`APP_DEBUG=false`, placeholder `APP_URL`) are independent of app code and safe to keep after a rollback.
- The `phpunit.xml` `APP_DEBUG=true` pin only affects the local/CI test harness; it has no effect on production runtime.
- Removed dead routes (`production-orders.destroy`, `production-orders.get-bom-details`, `client/notifications`, the six double-prefix `admin/admin/...` URIs) do not exist in older code, so a rollback restores them automatically.

No `migrate:rollback` steps are required for this release.