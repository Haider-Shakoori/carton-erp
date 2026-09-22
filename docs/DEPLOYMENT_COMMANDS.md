# DEPLOYMENT COMMANDS — Carton ERP

Use this runbook only after the exact release commit has passed the enterprise GitHub Actions QA gate.

## Phase 0 — pre-flight and backup

```bash
php -v
composer --version
node --version
npm --version

# Take an external pre-deploy database backup using your production DB credentials.
# Example only:
mysqldump -u <user> -p <database> | gzip > /backup/carton_erp_predeploy_$(date +%F_%H%M).sql.gz
```

Verify that the backup exists and is non-empty before continuing.

## Phase 1 — code and dependencies

```bash
git fetch origin
git checkout main
git pull --ff-only origin main

composer install --no-dev --optimize-autoloader --no-interaction
composer audit --no-interaction

npm ci
npm audit --audit-level=high
npm run build
```

Do not use `composer update` or `npm audit fix` interactively on the production host. Dependency changes belong in a reviewed, CI-tested commit.

## Phase 2 — environment

For a new installation only, create `.env` from `.env.example`, set real production values and generate the key once.

For an existing installation, preserve the existing `APP_KEY`.

Required production values include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-real-domain.example
```

Configure the production `DB_*`, mail, queue, cache and session values for the host.

## Phase 3 — database

Existing production database:

```bash
php artisan migrate --force
```

Brand-new empty database:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Never run `php artisan migrate:fresh` against live data.

## Phase 4 — storage and caches

```bash
php artisan storage:link || true
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Phase 5 — queue and scheduler

Restart supervised workers after the new code is active:

```bash
php artisan queue:restart
```

Run a persistent `queue:work` process under Supervisor/systemd where possible.

The server must execute:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

This scheduler drives the current stock-control review/synchronization and stock-notification jobs defined in `routes/console.php`.

## Phase 6 — production readiness and smoke

```bash
php artisan erp:readiness --strict
php artisan about
curl -fsS https://<domain>/up
```

Then perform the functional smoke in `docs/FINAL_DEPLOYMENT_CHECKLIST.md`, including unified/separate business mode, 3D Carton/Syrup Pack switching, shared customer/HR data, business-unit isolation, BOM/FIFO/actual-consumption production flow, warehouse controls, P2P and accounting.

Run the full automated suite on CI/staging, not against live production data.
