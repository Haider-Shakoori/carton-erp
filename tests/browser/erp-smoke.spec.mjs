import { test, expect } from '@playwright/test';
import { mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const browserPassword = process.env.BROWSER_SMOKE_PASSWORD;

async function login(page) {
  if (!browserPassword) throw new Error('BROWSER_SMOKE_PASSWORD is required for browser tests.');
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.locator('#username').fill('superadmin');
  await page.locator('#password').fill(browserPassword);
  await page.locator('#formAuthentication button[type="submit"]').click();
  await expect(page).toHaveURL(/\/admin\/dashboard(?:\?|$)/);
}

async function openAdminPage(page, url) {
  const response = await page.goto(url, { waitUntil: 'domcontentloaded' });
  expect(response?.status(), url).toBe(200);
  await expect(page).toHaveURL(new RegExp(url + '(?:\\?|$)'));
  await expect(page.locator('body')).toBeVisible();
}

async function evidence(page, name) {
  mkdirSync(path.join('test-results', 'screenshots'), { recursive: true });
  await page.screenshot({
    path: path.join('test-results', 'screenshots', name + '.png'),
    fullPage: true,
    animations: 'disabled',
  });
}

function csvParse(raw) {
  const rows = [];
  let row = [], cell = '', quoted = false;
  for (let i = 0; i < raw.length; i++) {
    const ch = raw[i];
    if (ch === '"' && quoted && raw[i + 1] === '"') {
      cell += '"';
      i++;
    } else if (ch === '"') {
      quoted = !quoted;
    } else if (ch === ',' && !quoted) {
      row.push(cell);
      cell = '';
    } else if ((ch === '\n' || ch === '\r') && !quoted) {
      if (ch === '\r' && raw[i + 1] === '\n') i++;
      row.push(cell);
      rows.push(row);
      row = [];
      cell = '';
    } else {
      cell += ch;
    }
  }
  if (cell.length || row.length) rows.push([...row, cell]);
  return rows;
}

function csvQuoted(x) {
  return '"' + String(x).replaceAll('"', '""') + '"';
}

test('guests are redirected to login and wrong passwords cannot access financial screens', async ({ page }) => {
  await page.goto('/admin/accounting');
  await expect(page).toHaveURL(/\/login(?:\?|$)/);
  await expect(page.locator('#formAuthentication')).toBeVisible();
  await page.locator('#username').fill('superadmin');
  await page.locator('#password').fill('incorrect-browser-password');
  await page.locator('#formAuthentication button[type="submit"]').click();
  await expect(page).toHaveURL(/\/login(?:\?|$)/);
  await expect(page.locator('#formAuthentication')).toBeVisible();
  await evidence(page, 'access-denied');
});

test('real admin login and main ERP screen navigation', async ({ page }) => {
  const runtimeErrors = [];
  page.on('pageerror', err => runtimeErrors.push(err.message));
  await login(page);
  await evidence(page, 'authenticated-dashboard');

  const screens = [
    ['/admin/products', 'products'],
    ['/admin/stock', 'stock'],
    ['/admin/purchase-orders', 'purchase-orders'],
    ['/admin/sales', 'sales'],
    ['/admin/accounting', 'accounting'],
    ['/admin/users', 'users'],
    ['/admin/roles', 'roles'],
    ['/admin/management-reporting', 'management-reporting'],
    ['/admin/products/finished-goods/import', 'finished-goods-import'],
    ['/admin/products/finished-goods/weight-audit', 'bom-weight-audit'],
  ];
  for (const [url, screenshot] of screens) {
    await test.step('Open ' + url, async () => {
      await openAdminPage(page, url);
      await evidence(page, screenshot);
    });
  }
  expect(runtimeErrors, 'Unexpected browser JavaScript errors').toEqual([]);
});

test('financial accounting tabs show the retained earnings reconciliation', async ({ page }) => {
  await login(page);
  await openAdminPage(page, '/admin/accounting');
  await page.locator('button[data-bs-target="#balance"]').click();
  await expect(page.locator('#balance')).toBeVisible();
  await expect(page.locator('#balance')).toContainText('Retained earnings');
  await expect(page.locator('#balance')).toContainText('Balance sheet difference');
  await evidence(page, 'financial-balance-sheet');
  await page.locator('button[data-bs-target="#pnl"]').click();
  await expect(page.locator('#pnl')).toBeVisible();
  await evidence(page, 'profit-and-loss');
});

test('real browser CSV export, upload, and repeatable import update finished-good weight', async ({ page }) => {
  await login(page);
  await openAdminPage(page, '/admin/products/finished-goods/import');
  const template = await page.request.get('/admin/products/finished-goods/template');
  expect(template.status()).toBe(200);
  expect(await template.text()).toContain('weight_g');
  const exportRes = await page.request.get('/admin/products/finished-goods/export');
  expect(exportRes.status()).toBe(200);
  const exported = csvParse(await exportRes.text());
  const headers = exported[0];
  expect(headers).toContain('product_id');
  expect(headers).toContain('weight_g');
  const product = exported.slice(1).find(r => /^\d+$/.test(r[0]) && r[2] && r[3]);
  expect(product, 'Seeded finished good with ID, name, category').toBeTruthy();

  const csv = [
    'product_id,name,category,weight_g',
    [product[0], csvQuoted(product[2]), csvQuoted(product[3]), '412.75'].join(','),
  ].join('\n') + '\n';
  for (let attempt = 1; attempt <= 2; attempt++) {
    await page.locator('#finished_goods_csv').setInputFiles({
      name: 'measured-carton-weights.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from(csv),
    });
    await page.getByRole('button', { name: /Import finished goods/i }).click();
    // Flash notifications can be consumed by concurrent dashboard AJAX polling.
    // Verify the persisted value via a fresh authenticated export instead of
    // treating missing one-time UI feedback as a failed financial operation.
    const validationError = await page.locator('.alert-danger').allTextContents();
    await expect.poll(async () => {
      const verify = await page.request.get('/admin/products/finished-goods/export');
      if (verify.status() !== 200) return 'HTTP ' + verify.status();
      const rows = csvParse(await verify.text());
      const row = rows.slice(1).find(r => r[0] === product[0]);
      return row ? Number(row[headers.indexOf('weight_g')]) : 'Product not found';
    }, {
      message: 'Carton weight must persist after CSV upload. Validation errors: ' + validationError.join(' | '),
      timeout: 12_000,
    }).toBe(412.75);
  }
  await evidence(page, 'finished-goods-csv-import-confirmation');
  await openAdminPage(page, '/admin/products/finished-goods/weight-audit');
  await expect(page.locator('table')).toContainText('Review result');
  await expect(page.getByRole('link', { name: /review-required BOM worksheet/i })).toBeVisible();
  const reviewExport = await page.request.get('/admin/products/finished-goods/bom-review-sheet');
  expect(reviewExport.status()).toBe(200);
  const reviewBody = await reviewExport.text();
  expect(reviewBody).toContain('verified_paper_kg_per_carton');
  const reviewRows = csvParse(reviewBody);
  expect(reviewRows.length, 'Client seed should expose BOMs needing factory measurements').toBeGreaterThan(1);
  writeFileSync(path.join('test-results', 'bom-review-required-worksheet.csv'), reviewBody);
});

test('real purchasing and production entry forms render and remain behind authentication', async ({ page }) => {
  await login(page);
  for (const [url, name] of [
    ['/admin/purchase-orders/create', 'purchase-order-create'],
    ['/admin/production-orders', 'production-orders'],
    ['/admin/production-orders/create', 'production-order-create'],
    ['/admin/sales/create', 'sale-order-create'],
    ['/admin/bom/create', 'bom-create'],
  ]) {
    await test.step('View ' + url, async () => {
      await openAdminPage(page, url);
      await evidence(page, name);
    });
  }
});

test('mobile Chromium uses a real 390px responsive viewport, with an off-canvas navigation drawer', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  await openAdminPage(page, '/admin/products');

  await expect(page.locator('#sidebar')).not.toBeInViewport();
  const overflow = await page.evaluate(() => ({
    viewport: window.innerWidth,
    documentWidth: document.documentElement.scrollWidth,
    bodyWidth: document.body.scrollWidth,
  }));
  expect(overflow.documentWidth, 'Document must not expand past a 390px mobile viewport').toBeLessThanOrEqual(overflow.viewport + 3);
  expect(overflow.bodyWidth, 'Body must not overflow the mobile viewport').toBeLessThanOrEqual(overflow.viewport + 3);
  await evidence(page, 'mobile-products-catalog');

  await page.locator('#sidebarToggle').click();
  await expect(page.locator('#sidebar')).toBeInViewport();
  await expect(page.locator('#sidebarOverlay')).toBeVisible();
  await evidence(page, 'mobile-navigation-open');

  await page.locator('#sidebarOverlay').click({ position: { x: 380, y: 390 } });
  await expect(page.locator('#sidebar')).not.toBeInViewport();
});
