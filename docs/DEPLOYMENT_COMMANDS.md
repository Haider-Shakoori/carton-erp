# DEPLOYMENT COMMANDS — Carton ERP (Qadir)

Annotated runbook for a fresh production deploy. Companion docs: `FINAL_DEPLOYMENT_CHECKLIST.md`, `ROLLBACK_GUIDE.md`.

## Phase 0 — Pre-flight (on the app host)
```bash
php -v                      # 8.2+; PHP intl recommended but not required
composer --version           # 2.x
npm --version                # 10.x
```

## Phase 1 — Code
```bash
git pull origin main                    # bring the release
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## Phase 2 — Environment
```bash
cp .env.example .env                    # template already ships APP_ENV=production + APP_DEBUG=false (safe)
# then edit .env:
#   APP_KEY                                 # run php artisan key:generate
#   APP_URL=https://<your-domain>           # REQUIRED — replace the placeholder; keep APP_DEBUG=false
#   DB_* / default mailer
php artisan key:generate
```

## Phase 3 — Database (two allowed paths)
**A. Existing production DB (normal upgrades):**
```bash
php artisan migrate --force
php artisan db:seed --class=HRPermissionSeeder        # idempotent backfill (41 HR perms)
php artisan db:seed --class=ShareholderPermissionSeeder # idempotent backfill (16 perms)
```
**B. Brand-new empty DB:**
```bash
php artisan migrate --force                            # 94 migrations, GREEN (fixed in this release)
php artisan db:seed --force                            # all permission seeders (incl. HR + shareholder)
# (the two per-class backfills on the left are only needed to upgrade an already-seeded DB)
```
> NEVER run `migrate:fresh` against a live DB. On a brand-new install you may use `--fresh` once before go-live, then proceed with B.

## Phase 4 — Storage + optional queue worker
```bash
php artisan storage:link
php artisan queue:restart                              # if a worker is already running
# long-running host:
nohup php artisan queue:work --tries=3 --timeout=60 > storage/logs/queue.log 2>&1 &
# shared hosting / limited: use cron below instead
```

## Phase 5 — Caches
```bash
php artisan config:cache
php artisan route:cache          # VERIFIED OK on this app (Laravel 12 serializes its closures)
php artisan view:cache
php artisan event:cache          # optional
php artisan optimize
```

## Phase 6 — Cron (optional but recommended)
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/app && php artisan queue:run-once >> /dev/null 2>&1   # if no persistent worker
0 3 * * *  mysqldump -u <user> -p<pass> product | gzip > /backup/product_$(date +\%F_\%H\%M).sql.gz
```
If you do not use `schedule:run`, run the monthly profit / recalc commands manually:
```bash
php artisan distribute:monthly-profit
php artisan distribute:profit-loss
php artisan recalculate:balances
```

## Phase 7 — Post-deploy smoke
```bash
curl -I https://<domain>/up
php artisan about
php artisan test               # on a staging DB / CI; never against live data
```
Then walk the golden path from checklist §7 (login, sale confirm/deliver/delete, production start/complete, purchase arrive, return, HR + shareholder screens).

## Known release advisories
- Routes: fixed in this release — 0 broken routes remain; `admin/admin/...` double prefixes removed (see checklist §3). Confirm with `php artisan route:list` after deploy.
- `composer audit` reports advisories (dompdf, framework 12.35.1, guzzle). Plan a dependency bump cycle post-deploy.