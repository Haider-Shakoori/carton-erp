# Browser validation in GitHub Actions

GitHub workflow: `.github/workflows/chromium-browser-erp-smoke.yml`.

The workflow boots Laravel 12 with PHP 8.3, MySQL 8, Node 22, and Playwright Chromium in an isolated GitHub-hosted Ubuntu runner, builds Vite assets, seeds client carton records, and creates a randomly generated **test-only** admin password. No production database, VPS, or privileged real-user account is used.

Browser scenarios:

1. Anonymous users are redirected to login; wrong credentials cannot open accounting.
2. Real administrator login redirects to the dashboard.
3. Browser navigates the products, stock, purchase orders, sales, accounting, user/role administration, management reporting, finished-goods importer, and BOM weight-audit screens.
4. Accountants' balance sheet displays retained earnings and the balance difference, and Profit & Loss tab renders.
5. CSV template and existing-goods export, real multipart CSV upload, and idempotent product weight updates without duplicate records.
6. Mobile Chromium viewport (390 × 844) visits the catalog.

Playwright saves per-screen screenshots, screenshots/traces/video on test failures, a JUnit test report, HTML report, and server logs to a GitHub Actions artifact (`chromium-erp-smoke-report`).

**Financial integrity:** Playwright checks workflows through the real UI; authoritative accounting equations, landed cost, FIFO deductions, shareholder profit, conversion variances, and CSV/no-stock-change invariants remain covered by the financial Pest suite. Both layers and both dependency audits must pass before a production rollout.

If the runner fails before Playwright starts, the workflow will surface a failed setup stage; no browser success must be claimed in that case. UI tests cannot replace actual factory UAT with a backup of production data.
