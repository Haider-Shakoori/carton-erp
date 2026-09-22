# ROLLBACK GUIDE — Carton ERP

Prepare and verify a pre-deploy database backup before every release. The current enterprise release includes schema migrations and data-governance changes, so rollback must be planned per deployed migration set.

## 1. Stop new writes

Put the application in maintenance mode (or otherwise drain traffic) before a rollback that changes code/schema:

```bash
php artisan down
```

## 2. Code rollback

Select the last known-good release SHA/tag, then restore its exact dependencies and assets:

```bash
git fetch origin
git checkout <previous-release-tag-or-sha>
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Do not regenerate `APP_KEY`.

## 3. Database rollback

Prefer restoring the verified pre-deploy database backup when a release has written data that cannot safely be represented by the previous application version.

If, and only if, the exact migrations from the failed deployment have safe `down()` implementations and no incompatible production data has been written, you may roll back those exact migrations:

```bash
php artisan migrate:status
php artisan migrate:rollback --step=<exact-number-from-this-deploy> --force
```

Do not guess the step count.

Backup restore example:

```bash
gunzip -c /backup/<verified-predeploy-backup>.sql.gz | mysql -u <user> -p <database>
```

After restoring, ensure the code revision and database revision match.

## 4. Post-rollback checks

```bash
php artisan migrate:status
php artisan erp:readiness --strict
curl -fsS https://<domain>/up
```

Verify authentication, active business mode, 3D Carton/Syrup Pack visibility, a representative sale, purchase, production/FIFO record, inventory balances and accounting totals before re-enabling writes.

```bash
php artisan up
```

## 5. Never do this in production

- `php artisan migrate:fresh`
- `composer update` directly on the host
- `npm audit fix` directly on the host
- regenerate `APP_KEY` on an existing installation
- delete `storage/app/public`
- roll back an unknown number of migrations without a verified backup
