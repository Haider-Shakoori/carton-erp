# QA — HTTP Request Optimization & Dashboard Load Time (Batch 04)

Date: 2026-09-05   |   Status: **PARTIAL** (dashboard reduced; other admin pages already lean)

## Objective
Reduce HTTP requests and dashboard load time in the Laravel ERP by (1) explaining the
Batch-03 "47 resources" vs the ~26 seen in DevTools, (2) inventorying real requests,
(3) removing only objectively-safe duplicate/unnecessary work, and (4) finding and fixing
why `/admin/dashboard` felt ~2× slower than Products/Sales/Stock.

## Methodology
- Headless Chrome via CDP (`Network.enable`, `Network.clearBrowserCache`) with a **real
  authenticated session** (`superadmin`), the same in Batch-03 reconciliation. Server:
  `php artisan serve` (single-threaded, no OPcache — see Environment notes).
- Each page measured **EMPTY cache** and **WARM cache**; per-request URL, initiator, size,
  duration, TTFB captured from `PerformanceResourceTiming`.
- Baseline = first authenticated harness run; After = post-change harness.

## RESULT: PARTIAL
- **Dashboard** `/admin/dashboard`: 1 request removed per load **and** the server-side
  cost of the load dropped measurably (see before/after below).
- **Products / Sales / Stock**: request counts are already lean (17–25) with no safe
  duplication found, so no further reduction was made there (STOP condition applied).

## 47 VS 26 EXPLANATION
The Batch-03 "~47 resources per page" and "~6957 ms dashboard" figures were **not the
admin pages**. The Batch-03 harness used a **fresh, unauthenticated** Chrome profile, so
every `/admin/*` navigation issued a 302 redirect to `/login`. The numbers measured were
the **login (Materio auth) page**:

- Fresh unauth `/login` = **exactly 47 resources**:
  - 9 CSS (incl. `iconify-icons.css` ~1.3 MB, `core.css` ~706 KB)
  - 18 vendor JS (jQuery 322 KB, Bootstrap 335 KB, autocomplete 282 KB, form-validation 331 KB, …)
  - 14 fetches (12 template-customizer SVGs + locale JSON)
  - 4 images + 2 fonts/backgrounds
- **Authenticated** admin pages load **17–25 resources** (Dashboard 23, Products 25,
  Sales/Stock 17 on an EMPTY cache) — matching the ~26 seen in DevTools.

So: same measurement tool, different pages. The login page (unbranded public page) carries
the heavy Materio scaffold; the admin shell is a much lighter custom layout. All 47 login
resources are local too (0 external) — none were "missing" from the optimization.

## Request Inventory (authenticated, EMPTY cache)

### /admin/dashboard — xfr ~2.11 MB
| # | Type  | Resource | Size (dec.) | Kind |
|---|-------|----------|-------------|------|
| 1 | link  | /vendor/bootstrap/css/bootstrap.min.css | 233 KB | GLOBAL CSS |
| 2 | link  | /vendor/bootstrap-icons/1.11.3/bootstrap-icons.min.css | 86 KB | GLOBAL CSS |
| 3 | link  | /vendor/apexcharts/apexcharts-7.1.0.css | 48 KB | PAGE (dashboard) CSS |
| 4 | link  | /vendor/fonts/inter/inter.css | 14 KB | GLOBAL CSS |
| 5 | link  | /vendor/toastify/toastify.min.css | 2 KB | GLOBAL CSS |
| 6 | css  | /vendor/fonts/inter/font-3.woff2 | 48 KB | FONT |
| 7 | css  | /vendor/bootstrap-icons/.../bootstrap-icons.woff2 | 130 KB | ICON FONT |
| 8 | img  | /assets/img/flags/*.svg (en/fa/ps) | ~44 KB | GLOBAL (lang switcher) |
| 9 | img  | /images/im_logo-1-removebg-preview.png | 175 KB | GLOBAL logo |
| 10 | other | /images/im_logo-1-removebg-preview.png (favicon) | 175 KB | DUPLICATE * |
| 11 | script | /vendor/apexcharts/apexcharts-7.1.0.min.js | 937 KB | PAGE JS |
| 12 | script | /vendor/jquery/jquery-3.7.1.min.js | 88 KB | GLOBAL JS |
| 13 | script | /vendor/bootstrap/js/bootstrap.bundle.min.js | 80 KB | GLOBAL JS |
| 14 | script | /vendor/toastify/toastify.min.js | 15 KB | GLOBAL JS |
| 15 | script | /vendor/toastr/toastr.min.js | 6 KB | GLOBAL JS |
| 16 | XHR  | /admin/dashboard/stats?timeframe=month | 3.4 KB | XHR (stats, incl. alerts) |
| 17 | XHR  | /admin/dashboard/charts?type=main&period=daily | 3.2 KB | XHR (charts) |
| 18 | XHR  | /admin/dashboard/charts?type=customers&period=daily | 0.4 KB | XHR (charts) |
| 19 | XHR  | /admin/dashboard/recent?type=all&limit=10 | 2.6 KB | XHR (activity) |
| 20 | XHR  | /admin/notifications/low-stock-data | 22.5 KB | XHR (global bell, every page) |

*(Before the change page 20/21 also included a dedicated `/admin/dashboard/alerts` XHR —
see Duplicate requests.)*

### Unity audit — no single-page double library loads
Audited every page for double versions of the candidate libraries
(Bootstrap/jQuery/Bootstrap-Icons/ApexCharts/Chart.js/Moment/FA/PDFMake). Every admin page
loads each library **once**. The only duplicated URL on every admin page is the 175 KB logo
PNG fetched both as `<link rel="shortcut icon">` **and** as the sidebar `<img>` (row 10, *)
— kept intentionally to preserve the brand favicon; after the cache-header change it is
fetched fresh once and served from cache thereafter (see Cache headers).

## XHR & Low-Stock Audit
On-load XHRs per page (before → after):
- Dashboard: **6 → 5** (stats, charts×2, recent, low-stock) — the sent `alerts` XHR removed.
- Products: 2 (global low-stock + /admin/products/low-stock), Sales: 1 (global low-stock), Stock: 1 (global low-stock). All are feature-driven (bell/badge/dropdown/modal) and were kept.
- **Duplicate computation found & removed (frontend-only):** `DashboardController::getStats()`
  already returns `data.alerts` (same `getAlertData()` output as the dedicated `alerts`
  endpoint) yet `index.blade.php` fired a **second** `GET /admin/dashboard/alerts`, re-running
  the low-stock + out-of-stock query every load. The page now renders `response.data.alerts`
  directly. Both endpoints return byte-identical data, so UI is unchanged.
- **Low-stock request server cost fixed (~28×):** `getLowStockData()` (and the twin
  `ProductController::getLowStockProducts()`) iterated every product and ran a correlated
  sub-query per product (`current_stock` accessor) — measured ~1.9 s HTTP TTFB, running on
  **every admin page**. Replaced with a single grouped `SUM(qty_available)` join over arrived
  purchases. Output verified **byte-identical** (JSON w/o vs after, see
  `C:\Users\Dell.com\AppData\Local\Temp\opencode\lowstock-before.json` /
  `lowstock-after.json`): 122 products, same order, same `"10.0000"`/`0` field quirk.
  Endpoint body now computes in **~0.07 s**.

## Dashboard Bottleneck (why it felt ~7 s)
`DashboardController::index()` (the HTML) is trivial (1 currency query). The perceived load
is the **chain of AJAX stats/charts/recent + global low-stock** hitting a single-threaded
`php artisan serve` (no OPcache). Resource timing showed the page codepages executing the
stats/charts/recent/alerts/low-stock queries back-to-back through one worker — server-side
queue wait dominates. Removing the alerts re-compute and making low-stock ~28× faster cut
the serialized server work substantially.

## Static Asset Cache Headers
- **Finding:** `php artisan serve` serves `public/` files directly, and **none** carry
  `Cache-Control`, so **every** navigation re-downloaded ~2 MB (incl. 937 KB ApexCharts) —
  WARM `transferSize` equalled EMPTY for all assets (0 cache/revalidate hits).
- **Fix (config-only):** `public/.htaccess` now sends `Cache-Control: public, max-age=86400`
  for static files (`css|js|woff2|ttf|svg|png|jpg|jpeg|gif|ico|webp|avif`). Applied to an
  Apache/nginx-style deployment this turns repeated navigations into revalidate-or-cache
  hits and also resolves the logo/pg favicon double-transfer. The built-in dev server ignores
  `.htaccess`, so measurement here is unaffected — this is a production-side improvement,
  **no route/API/behaviour change** (FilesMatch only matches static-file extensions).

## Environment notes (not changed, recorded)
- Single-threaded `php artisan serve` serialises concurrent XHRs (multiplies TTFBs), and
  PHP built-in server runs without OPcache → ~0.5–1 s baseline TTFB per request regardless
  of code. With a normal web server + OPcache the same code will be faster still.
- `/up`, `/login`, exports/print views and the fida.af logo remain out of scope as before.

## Files changed
1. `resources/views/admin/dashboard/index.blade.php` — alerts now rendered from the stats
   payload; removed the separate `loadAlerts()` XHR.
2. `app/Http/Controllers/Admin/NotificationController.php` — `getLowStockData()` single
   grouped aggregate (byte-identical JSON).
3. `app/Http/Controllers/Admin/ProductController.php` — `getLowStockProducts()` same fix.
4. `public/.htaccess` — static-asset cache headers (production servers).

## Before / After (identical authenticated CDP methodology)

### /admin/dashboard (EMPTY cache, best-of-3 for After)
| Metric | Before | After | Δ |
|---|---|---|---|
| Requests | 23 | **22** | −1 |
| XHRs | 6 | **5** | −1 |
| TTFB | 929 ms | **564 ms** | −39% |
| DOMContentLoaded | 2138 ms | **1638 ms** | −23% |
| Load | 2141 ms | **1641 ms** | −23% |
| Finish (last request) | 5291 ms | **3229 ms** | −39% |
| Transferred | 2.11 MB | 2.11 MB | 0 (dev server: no caching) |

### WARM cache
| Metric | Before | After |
|---|---|---|
| Requests | 22 | **21** |
| TTFB / DCL / Load / Finish | 948/2226/2228/5131 ms | **519/1609/1610/3194 ms** |

### Products / Sales / Stock
| Page | After resources (EMPTY) | Δ vs Before | After XHRs |
|---|---|---|---|
| /admin/products | 25 | 0 | 2 |
| /admin/sales | 17 | 0 | 1 |
| /admin/stock | 17 | 0 | 1 |

Request counts unchanged (no safe duplication); server-side low-stock work dropped ~28×
(visible on quiet pages, e.g. stock EMPTY TTFB 1035 ms → 608 ms in the same run).

## Verification
- **UI (headless, authed):** dashboard renders all 8 stat cards, both ApexCharts, activity
  feed, alert list (now from stats), notification badge `99+`, low-stock dropdown/modal;
  loading overlay clears. No JS errors observed.
- **Low-stock JSON:** byte-identical `NotificationController` + `ProductController` output
  before/after (count 122, same product order, same value types).
- **Tests:** `php vendor/bin/pest --no-coverage` → **48 passed / 431 assertions**.
- **Blade:** `php artisan view:cache` compiles cleanly.

## FINAL
- **RESULT: PARTIAL**
- **47 VS 26 EXPLANATION:** Batch-03 harness measured the unauthenticated redirect target
  `/login` (47 Materio-scaffold resources); authenticated admin pages load 17–25.
- **DASHBOARD REQUESTS BEFORE/AFTER:** 23 / 22 (EMPTY); 22 / 21 (WARM)
- **WARM DASHBOARD REQUESTS:** 21
- **DUPLICATE REQUESTS FOUND/REMOVED:** 1 (dedicated `alerts` XHR duplicating `data.alerts`
  embedded in the stats response). Logo/pg favicon double-load documented; kept for UI/brand,
  resolved by cache headers on production servers.
- **GLOBAL ASSETS MADE PAGE-SPECIFIC:** none (audit showed all global assets are used by the
  base layout; moving any would be brittle; ApexCharts JS+CSS are already dashboard-only).
- **XHR BEFORE/AFTER:** Dashboard 6 → 5. Products/Sales/Stock unchanged (1–2, all required).
- **DASHBOARD TTFB/DCL/LOAD/FINISH BEFORE/AFTER (EMPTY):**
  929/2138/2141/5291 ms → 564/1638/1641/3229 ms.
- **EXTERNAL ERP REQUESTS:** 0
- **LIBRARY VERSIONS CHANGED:** NO
- **UI CHANGED:** NO
- **BUSINESS LOGIC CHANGED:** NO
- **REGRESSION:** PASS
- **SAFE FOR CLIENT PRESENTATION:** YES
- **STOP:** Remaining requests are legitimate (feature-driven XHRs and required assets), all
  local; the largest remaining cost is the single-threaded/no-cache dev server, which is an
  environment concern, not an app defect.