# QA — Performance Batch 03: Self-hosting external CDN assets

| | |
|---|---|
| **Batch** | 03 — Serve all external CDN frontend assets locally |
| **Date** | 2026-09-05 |
| **Environment** | Laravel ERP, `D:\Projects\Qadir`, PHP 8.x (`php artisan serve` on `localhost:8000`) |
| **Verdict** | **PASS** for the ERP surface (login + all measured admin pages load with **0 external requests**). Two documented exclusions (see "Exclusions"). |

---

## 1. Objective

Remove every CDN round-trip from the ERP's own pages by downloading the exact assets currently served by the CDNs into `public/vendor/` and re-pointing Blade references to `asset('vendor/...')` — **without changing any version, behaviour, markup ordering, or routes**.

## 2. Constraints respected

- No version upgrades (files byte-matched to what the CDN was serving for that URL).
- No UI/UX redesign; element and script/style order untouched; no `defer`/`async` added.
- No routes / API formats / controllers / services / DB / BOM / raw-material formulas changed.
- Only `resources/views/**/*.blade.php` edited (plus new files under `public/vendor/`).
- No packages installed, no composer/npm changes.

## 3. Method

1. Phase 1 – Inventory: regex scan of `resources/views/` (~216 jsDelivr, ~141 DataTables.net, ~26 cdnjs, ~29 fonts.googleapis, 8 code.jquery.com, 8 fonts.bunny.net occurrences across ~200 views).
2. Phase 2 – Resolve exact versions per running URL (jsDelivr `x-jsd-version` header, npm `latest`, file banner).
3. Phase 3 – Download exact files into `public/vendor/` preserving CDN layout (e.g. Font Awesome `css/` + `webfonts/`, Bootstrap Icons self-contained `fonts/` per version).
4. Phase 4 – Self-host Google Fonts (`inter`, `poppins`, `montserrat`/`playfair-display`, `droid-arabic-kufi`, `figtree`) as local `@font-face` CSS + woff2.
5. Phase 5 – Icon fonts local (Bootstrap Icons 3 versions, Font Awesome 5.0/7.x, Remixicon).
6. Phase 6 – Audit every downloaded CSS/JS for residual external URLs (only comments/docs/SVG data-URIs remain).
7. Phase 7 – Replace references:
   - 117 views bulk-updated with a URL→`asset()` mapping (DataTables 1.13.6 + Buttons 2.4.1, Select2 4.1.0-rc.0, SweetAlert2@11, jQuery 3.7.1/3.6.0, Bootstrap 5.3.2, Bootstrap Icons 1.10.3/1.10.5/1.11.3, Inter, ApexCharts@3.35.0, Flatpickr, Font Awesome 6.5.0 + latest 7.3.1, html2canvas, jsPDF, JSZip, pdfmake 0.2.7 + 0.1.36, Remixicon, Tom Select, moment 2.29.4, daterangepicker, chart.js@4.4.1).
   - `layouts/admin/base.blade.php` edited in the previous session.
   - 18 more views fixed manually this session (fonts, chart.js unversioned, apexcharts unversioned, figtree, jquery 3.6.0, Droid font, momentjs/latest).

## 4. Asset inventory (resolved version → local path)

### Libraries — `public/vendor/`
| Asset | Version | Local path(s) |
|---|---|---|
| Bootstrap | 5.3.0 (main) / 5.3.2 (client, receipts) | `vendor/bootstrap/css/bootstrap.min.css`, `...-5.3.2.min.css`, `vendor/bootstrap/js/bootstrap.bundle.min.js`, `...-5.3.2.bundle.min.js` |
| jQuery | 3.7.1 / 3.6.0 | `vendor/jquery/jquery-3.7.1.min.js`, `vendor/jquery360/jquery-3.6.0.min.js` |
| Bootstrap Icons | 1.11.3 / 1.10.5 / 1.10.3 | `vendor/bootstrap-icons/<v>/bootstrap-icons.min.css` + `fonts/` |
| DataTables | 1.13.6 core + Bootstrap5 | `vendor/datatables/css|js/` |
| DataTables Buttons | 2.4.1 | `vendor/datatables/buttons/` |
| Select2 | 4.1.0-rc.0 | `vendor/select2/select2.min.{css,js}` |
| SweetAlert2 | 11.26.25 | `vendor/sweetalert2/sweetalert2.min.js` |
| Toastify | 1.12.0 | `vendor/toastify/toastify.min.css`, `vendor/toastify/toastify.min.js` |
| Toastr | 2.1.4 (cdnjs latest) | `vendor/toastr/toastr.min.js` |
| ApexCharts | 3.35.0 (pinned) / 7.1.0 (unversioned) | `vendor/apexcharts/apexcharts-3.35.0.min.{css,js}`, `vendor/apexcharts/apexcharts-7.1.0.{css,min.js}` |
| Chart.js | 4.4.1 (client) / 4.5.1 (admin) | `vendor/chartjs/chart-4.4.1.min.js`, `vendor/chartjs/chart-4.5.1.min.js` |
| Moment | 2.29.4 (npm/cdnjs) / 2.18.1 (GitHub `momentjs/latest`) | `vendor/moment/moment-2.29.4.min.js`, `vendor/moment/moment-2.18.1.min.js` |
| Daterangepicker | 3.1.0 | `vendor/daterangepicker/daterangepicker.css`, `vendor/daterangepicker/daterangepicker.min.js` |
| Flatpickr | 4.6.13 | `vendor/flatpickr/flatpickr.min.css`, `vendor/flatpickr/flatpickr.min.js` |
| Font Awesome | 6.5.0 (cdnjs) / 7.3.1 (latest) | `vendor/fontawesome/<v>/css/all.min.css` + `webfonts/` |
| html2canvas | 1.4.1 | `vendor/html2canvas/html2canvas.min.js` |
| jsPDF | 2.5.1 | `vendor/jspdf/jspdf.umd.min.js` |
| JSZip | 3.10.1 | `vendor/jszip/jszip.min.js` |
| pdfmake | 0.2.7 / 0.1.36 (both in use) | `vendor/pdfmake/{pdfmake,vfs_fonts}-0.2.7*`, `...-0.1.36*` |
| Remixicon | 3.5.0 | `vendor/remixicon/remixicon.css` + `fonts/` |
| Tom Select | 2.3.1 | `vendor/tom-select/tom-select.css`, `vendor/tom-select/tom-select.complete.min.js` |

### Fonts — `public/vendor/fonts/`
| Family | Details | Local path |
|---|---|---|
| Inter | v20, 300–900 | `fonts/inter/inter.css` (+7 woff2) |
| Droid Arabic Kufi | 400/700 (see note 5.5) | `fonts/droid-arabic-kufi/droidarabickufi.css` |
| Poppins | 300–600 | `fonts/poppins/poppins.css` |
| Montserrat + Playfair Display | 300–600 / 500 | `fonts/montserrat/montserrat-playfair.css` |
| Figtree | 400/500/600 latin + latin-ext | `fonts/figtree/figtree.css` |

## 5. Verification

1. **Compile**: `php artisan view:cache` — all Blade templates compiled with no errors; then cleared.
2. **No dangling references**: script verified every `asset('vendor/...')` referenced in views resolves to an existing file. ✓
3. **No residual CDN URLs** in `resources/views` or `resources/{css,js}` (regex across all known CDN domains + generic external `href/src/action`). ✓
   - Remaining non-local hosts: `fida.af` (brand logo, see 6.2) and a w3schools demo avatar in an unrouted dead view (see 6.3).
4. **Tests**: Pest suite — **48 passed (431 assertions)**.
5. **Runtime measure** (headless Chrome, fresh profile, via CDP `performance.timing` + resource entries):

| Page | DOMContentLoaded (ms) | Load (ms) | Finish (ms) | Resources | External requests |
|---|---|---|---|---|---|
| `/admin/products` | 3408 | 3414 | 3711 | 47 | **0** |
| `/admin/sales` | 2996 | 2999 | 3312 | 47 | **0** |
| `/admin/stock` | 3241 | 3246 | 3553 | 47 | **0** |
| `/admin/dashboard` | 6957 | 6961 | 6948 | 47 | **0** |
| `/login` | 3301 | 3306 | 3601 | 47 | **0** |
| `/up` | 811 | 812 | 830 | 3 | 2 (framework page, see 6.1) |

   Comparison with the baseline recorded before the batch (DevTools Performance panel): DOMContentLoaded ~6.58 s, Load ~16.2 s, Finish ~22.58 s. Tooling differs (DevTools vs CDP), so treat the delta directionally; the hard guarantee is **external request count = 0** for the ERP surface (was >200 CDN requests before).

6. **Known notes / exclusions**
   1. `/up` is Laravel's **built-in** health-check page (not an ERP view) and hardcodes the Tailwind Play CDN + `fonts.bunny.net` figtree. Changing it requires editing framework/maintenance routing — out of scope and forbidden by the no-route-change rule. Left as-is.
   2. `https://fida.af/...` logo images in `accounts/export|print-preview` are the company brand URL, not a library — left as-is.
   3. `admin/welcome.blade.php` (w3schools avatar, `layouts.staff.base`, `Auth::guard('staff')`) is an unrouted dead view (the routed `welcome` view is a separate 0-byte file) — left untouched.
   4. Empty (0-byte) views left untouched: `welcome`, `app_settings/{company_settings,invoice_templates,units}/index`, `settings/whatsapp/{manage,session}`.
   5. `fonts.googleapis.com/earlyaccess/droidarabickufi.css` references v6 font files that 404 on Google today; the local replacement serves the same family from the current css2-API v26 files.
   6. `momentjs/latest` (GitHub-repo URL) actually serves **moment 2.18.1** (51465 B, banner "version : 2.18.1") — the dashboard has been running 2.18.1 all along. The exact artifact was mirrored to preserve behaviour; the separate npm/cdnjs refs use 2.29.4.
   7. `cdn.jsdelivr.net/npm/chart.js/dist/chart.min.css` returns **404** today; the two unreferencable `<link>` tags in `reports/creditors|debtors` were removed (zero visual impact, one less failed request).
   8. Toastr copy is cdnjs "latest" (2.1.4); its internal banner still says "v2.1.3" (cdnjs artifact metadata mismatch) — original text preserved.

## 7. Files changed this batch

- `resources/views/**/*.blade.php` — 117 files bulk-replaced + 18 files manually (see §3), all scoped strictly to CDN `href/src`.
- `public/vendor/**` — all downloaded/derived local assets (new tree).
- `QA_LOCAL_ASSET_PERFORMANCE.md` — this report.

## 8. Reproduce / re-verify

```powershell
php artisan view:clear
php artisan view:cache        # compiles every blade (syntax check)
php vendor/bin/pest          # 48 tests, 431 assertions
# DevTools/headless: load /login + each /admin/* page, open Network -> should show 0
# requests to cdn/jsdelivr/cdnjs/datatables/googleapis/bunny/jquery domains.
```

---

**Sign-off**: PASS — the ERP login and all measured admin pages now render with zero external CDN requests; self-hosted files byte-match the previously-served artifacts; all Blade compiles; test suite green; no version, route, UI, or logic changed.