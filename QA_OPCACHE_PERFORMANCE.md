# QA: OPCACHE PERFORMANCE — BEFORE / AFTER

**Task:** Enable PHP OPcache for `php artisan serve` and benchmark the server-side impact.

No application code was changed. Only `C:\xampp\php\php.ini` was edited (OPcache section).
Business logic, formulas, FIFO, costing, accounting, BOM, permissions, UI, and DB are untouched.

---

## 1. CHANGE APPLIED

| Item | Before | After |
|---|---|---|
| `zend_extension=opcache` | commented out (`;`) | **uncommented (active)** |
| `opcache.enable` | commented out, defaults 1 | **explicitly `1`** |
| `opcache.enable_cli` | commented out, defaults 0 | **explicitly `1`** (needed for `php artisan serve` CLI SAPI) |
| `opcache.validate_timestamps` | commented out, defaults 1 | **explicitly `1`** (dev-safe: rechecks files on stat interval) |
| `opcache.revalidate_freq` | commented out, defaults 2 | **explicitly `2`** (detects changes within 2 s) |

**Backup:** `C:\xampp\php\php.ini.bak_opcache`
**php.ini location:** `C:\xampp\php\php.ini`
**PHP version:** 8.2.12 (ZTS, VC2019 x64)
**Extension confirmed:** `C:\xampp\php\ext\php_opcache.dll` (present on disk, was simply not loaded)

Post-edit CLI verification:
```
$ php --ri "Zend OPcache"
Opcode Caching => Up and Running
opcache.enable => On
opcache.enable_cli => On
opcache.validate_timestamps => On
opcache.revalidate_freq => 2
```

---

## 2. MEASUREMENT METHODOLOGY

| Step | Detail |
|---|---|
| Tool | `http_timer2.php` — PHP CLI script that (1) logs in via POST, (2) extracts session cookies, (3) issues authenticated GET requests with `curl.exe`, (4) collects `time_total` from curl timing |
| Runs | 3 full sweeps per endpoint (before & after) |
| Values | `min` = cleanest (least queue contamination); `median` = middle |
| Server | `php artisan serve --host=127.0.0.1 --port=8000` (single-threaded, IPv4 only) |
| Host | `127.0.0.1` (avoids `::1` IPv6 fallback) |
| Server restart | Old PID killed → new `php artisan serve` started between BEFORE and AFTER |
| OPcache warmup | First curl hit after serve start is cold (OPcache populating); subsequent hits are warm |

---

## 3. BEFORE / AFTER RESULTS

### 3A. Primary comparison (http_timer2.php, min of 3 runs)

| Endpoint | BEFORE (ms) | AFTER (ms) | Speedup |
|---|---|---|---|
| `/up` (minimal Laravel route) | **770** | **56** | **13.8x** |
| `/admin/dashboard` (authed) | **1,602** | **320** | **5.0x** |
| `/admin/products` (authed) | **1,795** | **326** | **5.5x** |
| `/admin/stock` (authed) | **~10,899*** | **366** | — |
| `/admin/sales` (authed) | **~8,335*** | **364** | — |
| `/admin/notifications/low-stock-data` | *(timed out)* | **390** | — |

\* Stock/Sales BEFORE values are **inflated by single-threaded serialization** — they ran after
dashboard+products (which took ~3.4 s combined), so the server had queued requests. The "min"
value here is not a clean first-request measurement. The `/up` and `dashboard` BEFORE values are
the cleanest since they were the first requests in their sequence.

### 3B. Verification runs (6 consecutive /up hits, post-warmup)

| Run | Status | Time (ms) |
|---|---|---|
| 1 | 200 | 167 |
| 2 | 200 | 113 |
| 3 | 200 | 109 |
| 4 | 200 | 148 |
| 5 | 200 | 133 |
| 6 | 200 | 115 |

Post-warmup `/up` median ≈ **115 ms** (vs 770–1,070 ms before).

### 3C. Behavior verification (all pages, authenticated)

| Page | Status | Size |
|---|---|---|
| `/admin/dashboard` | 200 | 160,737 |
| `/admin/products` | 200 | 301,970 |
| `/admin/stock` | 200 | 120,869 |
| `/admin/sales` | 200 | 157,636 |
| `/admin/notifications/low-stock-data` | 200 | 23,321 |
| `/up` | 200 | 2,128 |

**Response sizes identical to BEFORE** — no content changed.

---

## 4. SUMMARY

| Metric | BEFORE | AFTER | Improvement |
|---|---|---|---|
| Minimal Laravel response (`/up`) | 770–1,070 ms | 56–167 ms | **~5–14x faster** |
| Dashboard (authed, 8 queries) | 1,300–1,800 ms | 320 ms | **~5x faster** |
| Products (authed) | 1,795 ms | 326 ms | **~5.5x faster** |
| Low-stock (authed) | 1,420–1,480 ms | 390 ms | **~3.7x faster** |
| Fixed per-request framework floor | ~770 ms | ~56 ms | **eliminated** |

The ~770 ms fixed per-request Laravel/PHP framework floor (identified in `QA_REQUEST_LATENCY_PROFILE.md`)
is **eliminated by OPcache**. Every request now loads PHP bytecode from cache instead of recompiling
framework source files on every request.

---

## 5. WHAT CHANGED

- **PHP opcode cache:** Framework and vendor PHP files are now compiled once into shared memory
  and reused across requests, eliminating the per-request `php -f` compilation cost.
- **`validate_timestamps=1` + `revalidate_freq=2`:** On Windows, OPcache re-stats files every
  2 seconds to detect changes. This is dev-safe — edited files are picked up within 2 s without
  manual cache invalidation.
- **`enable_cli=1`:** Required because `php artisan serve` uses the CLI SAPI, not Apache/FPM.
  Without this, the serve process would not load OPcache at all.

---

## 6. WHAT DID NOT CHANGE

- Business logic, formulas, FIFO, costing, accounting, BOM, permissions, roles, UI, localization
- Database structure, queries, or values
- API responses, routes, session handling, authentication
- Decimal precision, rounding, sorting, filtering
- Production, purchase, sale, stock, or ledger behavior

---

## 7. FINAL RESPONSE

RESULT: **OPCACHE ENABLED — SERVER-SIDE 5x FASTER**

CHANGE: `C:\xampp\php\php.ini` — OPcache enabled (zend_extension + enable=1 + enable_cli=1 +
validate_timestamps=1 + revalidate_freq=2). Backup: `php.ini.bak_opcache`.

BEFORE: `/up` 770–1,070 ms / dashboard 1,300–1,800 ms / products 1,795 ms
AFTER:  `/up` 56–167 ms / dashboard 320 ms / products 326 ms

BEHAVIOR CHANGED: NO
DB CHANGED: NO
BUSINESS LOGIC CHANGED: NO

NEXT STEPS:
1. Self-host the 11 CDN libraries locally (removes 1–3.2 s × 11 external frontend latencies)
2. Optimize low-stock endpoint N+1 queries (100+ queries → batch)
3. Consider `config:cache` after fixing `WhatsAppHelper.php:22,24` and `WhatsAppController.php:53`
   runtime `env()` calls (currently unsafe)

STOP.
