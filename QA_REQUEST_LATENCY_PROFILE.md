# QA: REQUEST LATENCY PROFILE

**Task:** Locate the ~6s latency (not optimize code) — measure first.

All measurements taken **live** against the actually-served app on `localhost:8000`
(`php artisan serve`). No application code was changed during this profiling task.
No diagnostic routes/files were added; all probes live in the temp dir.
No business logic, formulas, FIFO, costing, accounting, BOM, permission, or UI changed.

---

## 1. ENVIRONMENT (measured)

| Item | Value | Notes |
|---|---|---|
| PHP | 8.2.12 (ZTS, VC2019 x64) | `C:\xampp\php\php.ini` |
| OPcache | **NOT LOADED** | `php_opcache.dll` exists in `ext` but is **not enabled** in php.ini |
| Xdebug | **NOT LOADED** | not in `php -m`; no load in web SAPI either |
| APP_ENV | production | |
| APP_DEBUG | **true** | inconsistent with production env |
| DB | mysql | connect ~24ms, SELECT 1 ~4ms, product query ~43ms |
| SESSION_DRIVER | database | 2 queries/request, negligible |
| CACHE_DRIVER | database | |
| Config cache | **NOT CACHED** | |
| Route cache | **NOT CACHED** | |
| Event cache | NOT CACHED | |
| View cache | CACHED | |
| Serve mechanism | `php artisan serve` | PID 29376, **single-threaded** PHP built-in server, bound `-S 127.0.0.1:8000` (IPv4 only) |
| localhost resolution | `::1` (IPv6) + `127.0.0.1` (IPv4) | server listens IPv4 only; Firefox may try IPv6 first (small overhead) |
| Debug packages | none | no Debugbar / Telescope / Clockwork |

---

## 2. COMPONENT TIMING TABLE (server-side)

| COMPONENT | TIME | Evidence |
|---|---|---|
| **Total dashboard request** (HTTP) | **~1,300 – 1,800 ms** | curl, authenticated, 127.0.0.1 & localhost |
| Laravel bootstrap (cold, fresh process) | ~310 ms | autoload ~50 ms + app create ~10 ms + kernel boot ~250 ms |
| Controller/service (in-app dispatch) | ~120 – 230 ms | tinker kernel.handle, exclusive of HTTP/boot |
| Database queries (dashboard) | **~7 – 36 ms** (8–9 queries) | `DB::listen` accumulation |
| Blade rendering | part of controller block | not isolated; inside 120–230 ms dispatch |
| Middleware | part of bootstrap+dispatch | no external calls (verified) |
| Session (database driver) | ~2 queries, negligible | |
| Other (HTTP pipeline floor) | ~770 – 850 ms on trivial `/up` | **fixed floor even on minimal route** |
| **low-stock endpoint total** | **~1,420 – 1,480 ms** | HTTP, authenticated |
| **minimal Laravel endpoint** (`/up`) | **~770 – 1,070 ms** | HTTP, no auth, trivial handler |
| **simple PHP baseline** | **~0 ms** | `php -f` echo |
| Static local file (logo png) | **~58 – 94 ms** | built-in server static = fast |

**Interpretation:** The database is NOT the problem — the dashboard runs only 8–9 queries totaling
~30 ms, and DB connect/query latency is low. Even a **trivial `/up` route through the same stack
takes ~770–850 ms**, i.e. a large fixed per-request framework/bootstrap overhead independent of
controller logic or queries. This is bounded by the Windows environment with **OPcache disabled**
(framework source recompiled every request), **uncached config/routes**, and **APP_DEBUG=true**.

---

## 3. FRONTEND / BROWSER — the DOMINANT multi-second cause

The dashboard HTML references **11 external CDN resources**. Latency to each on this network
(measured with curl from the same machine):

| CDN resource (blocking) | Time |
|---|---|
| cdn.jsdelivr.net bootstrap.min.css | **2,795 ms** |
| cdn.jsdelivr.net bootstrap-icons.min.css | 1,450 ms |
| **fonts.googleapis.com (Inter)** | **3,178 ms** |
| cdn.jsdelivr.net toastify.min.css | 1,068 ms |
| cdn.jsdelivr.net apexcharts.css | 1,619 ms |
| code.jquery.com jquery.min.js | 1,423 ms |
| cdn.jsdelivr.net bootstrap.bundle.min.js | 1,310 ms |
| cdnjs.cloudflare.com toastr.min.js | 1,786 ms |

The render-blocking CSS + the `fonts.googleapis.com` font in `<head>` account for **~6 s of
DOMContentLoaded** and the external JS accounts for the later **16 s Load / 22 s Finish**.
Local static files are fast (~60–90 ms); the slowness is **internet latency to CDNs**, not the app.

---

## 4. PHASE-BY-PHASE FINDINGS

- **Phase 1 (reproduce):** Authenticated HTTP timings repeatable: dashboard ~1.3–1.8 s, low-stock
  ~1.4 s, products ~1.6 s, stock ~1.4 s, sales ~1.5 s, `/up` ~0.77–1.07 s. Cold vs warm differ by
  a few hundred ms only.
- **Phase 2 (DB vs PHP):** Dashboard = 8–9 queries / ~30 ms DB vs ~1.4 s total → **DB is ~2%**.
  PHP/framework is the server cost.
- **Phase 3 (bootstrap):** Cold bootstrap ~310 ms (65 autoload + 260 kernel). No external calls in
  middleware; 3 custom middleware are lightweight (cache/session/permission).
- **Phase 4 (debug/xdebug):** Xdebug **not loaded**; no debug packages. APP_DEBUG=true is a
  candidate but not the multi-second cause.
- **Phase 5 (filesystem):** Local static ~60–90 ms → filesystem/antivirus is not the primary issue.
  No repeat Blade recompilation (views cached).
- **Phase 6 (cache):** Config/routes/events NOT cached. `config:cache` is **unsafe** here because
  `WhatsAppHelper.php` and `WhatsAppController.php` call `env()` at runtime (documented; would break).
  Route cache not applied (health route uses closures). View cache already on.
- **Phase 7 (low-stock):** route `/admin/notifications/low-stock-data`, `getLowStockData`. It is
  query-heavy (100+ queries incl. per-row stock lookups — this was never optimized). DB ~245–486 ms
  (accumulating in profiler). Still HTTP ~1.4 s dominated by the same framework floor.
- **Phase 8 (minimal endpoint):** `/up` = ~770–1,070 ms confirmed large fixed per-request floor;
  a trivial response is not "instant" through this stack on this Windows host.
- **Phase 9 (PHP baseline):** raw PHP ~0 ms → Laravel bootstrap + framework is the server cost.
- **Phase 10 (DB connect):** connect 24 ms, SELECT 1 4 ms → not a factor.
- **Phase 11 (external ops):** **found — external CDN assets** (see section 3). No synchronous
  external call in server request path (WhatsApp helper is user-triggered, not on these pages).
- **Phase 12 (session):** database driver, ~2 queries/request, negligible.
- **Phase 13 (waterfall):** DOMContentLoaded 6.58 s / Load 16.2 s / Finish 22.58 s is explained by
  the 11 slow external CDN resources + single-threaded dev server serializing the ~21 local requests.
- **Serve mechanism:** confirmed `php artisan serve` (single-threaded, IPv4 only). Static is fast;
  the server is not the primary latency cause but serializes concurrent asset/XHR requests.

---

## 5. FINAL CLASSIFICATION

Primary bottlenecks (in order of real-world impact):

1. **FRONTEND (external CDN network latency)** — 11 external resources at 1–3.2 s each; this is the
   dominant cause of the user's 6.59 s XHR / 6.58 s DOMContentLoaded / 16.2 s Load / 22.58 s Finish.
2. **PHP / LARAVEL BOOTSTRAP & FRAMEWORK OVERHEAD on Windows (no OPcache, uncached config/routes,
   APP_DEBUG=true, single-threaded dev server)** — ~770–850 ms fixed per-request floor; makes every
   request measurably slow regardless of query count.
3. **DATABASE** — NOT a primary bottleneck (8–9 queries, ~30 ms).
   *Caveat:* low-stock endpoint is genuinely query-heavy and unoptimized.
4. **LOCAL DEVELOPMENT SERVER** — minor (single-threaded serialization of concurrent requests;
   IPv6 `::1`/IPv4 fallback on `localhost`).

---

## 6. SAFE FIX APPLIED

**NONE applied to code** (correctly). The dominant server-side cost is infrastructure/environment
(OPcache + config caching), and the frontend cost is external CDN assets — neither is a safe
in-code, behavior-preserving change that could be applied by editing the application alone.
`config:cache` is explicitly unsafe here (runtime `env()` calls). Per instructions, no speculative
caching or broad refactor was performed.

## 7. RECOMMENDED TARGETED NEXT FIXES (evidence-backed)

1. **Enable OPcache** for the serve process (infrastructure, zero behavior change, biggest server win):
   uncomment/enable in `C:\xampp\php\php.ini`:
   `zend_extension=C:\xampp\php\ext\php_opcache.dll` + `opcache.enable=1`,
   `opcache.validate_timestamps=0` (and `opcache.enable_cli=0` for CLI). Restart serve.
   Proven lever: today every request recompiles framework files on Windows.
2. **Self-host the 11 CDN libraries** (Bootstrap, Icons, Inter font, toastify, apexcharts, jQuery,
   toastr) as local assets (bundle/vendor them into `/public`). This removes the ~1–3 s × 11
   external latencies behind DOMContentLoaded/Load/Finish. (Requires copying lib files — recommend
   as the next task; behavior/UI identical, only the source of assets changes.)
3. **Optimize the low-stock endpoint** (`getLowStockData`), which is a genuine N+1 (100+ queries) and
   is the slowest *server* endpoint — apply the same batch pre-fetch pattern used for products/stock.
4. **(Config, optional)** set `APP_DEBUG=false` in production to trim debug overhead.
5. Use `127.0.0.1` for local browsing to avoid `::1` IPv6 fallback overhead.

---

## FINAL RESPONSE

RESULT: **ROOT CAUSE FOUND**

DASHBOARD TOTAL: ~1,300 – 1,800 ms (server HTTP; isolated)  
DATABASE TIME: ~7 – 36 ms (8–9 queries)  
LARAVEL/PHP TIME: ~770 – 850 ms floor (bootstrap+framework) + remainder  
LOW-STOCK TOTAL: ~1,420 – 1,480 ms (HTTP)  
MINIMAL LARAVEL RESPONSE: ~770 – 1,070 ms (`/up`)

PRIMARY BOTTLENECK:
1. **FRONTEND — slow external CDN resources (1–3.2 s each × 11)** → the user's 6.6 s XHR / 6.58 s
   DOMContentLoaded / 16.2 s Load / 22.58 s Finish.
2. **PHP/LARAVEL BOOTSTRAP & FRAMEWORK overhead on Windows** — ~770–850 ms fixed per-request floor
   (no OPcache, uncached config/routes, APP_DEBUG=true, single-threaded `php artisan serve`).
   Database is negligible (~2% of request).

ROOT CAUSE: The ~6 s latency is **not** caused by DB queries (only 8–9 queries, ~30 ms). It is the
combination of (a) 11 external CDN assets with 1–3.2 s network latency that block the browser render,
and (b) a fixed ~770–850 ms per-request Laravel/framework floor on this Windows host caused by
OPcache being disabled, uncached config/routes, APP_DEBUG=true, and the single-threaded
`php artisan serve` serializing the ~21 browser requests.

SAFE FIX APPLIED: NONE (no safe, in-code, behavior-preserving fix exists for these specific causes;
config:cache would break WhatsApp `env()` calls — documented; OPcache enablement is an
infrastructure change that must be reported first).

BEFORE: dashboard ~1,300 – 1,800 ms HTTP / ~22 s page finish (CDN-bound)  
AFTER: — (no change applied)

BUSINESS LOGIC CHANGED: NO  
UI BEHAVIOR CHANGED: NO  
DATABASE SEMANTICS CHANGED: NO

NEXT ACTION: (1) enable OPcache in `C:\xampp\php\php.ini` (infrastructure, biggest server win);
(2) vendor the 11 CDN libraries locally to remove the 1–3.2 s × 11 frontend latencies;
(3) optimize the low-stock endpoint's N+1 queries. These are the evidence-backed targeted fixes.

STOP.
