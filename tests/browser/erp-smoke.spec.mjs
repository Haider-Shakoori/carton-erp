import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
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
    await expect(page.locator('.alert-success')).toContainText(/1 updated/);
    const verify = await page.request.get('/admin/products/finished-goods/export');
    expect(verify.status()).toBe(200);
    const rows = csvParse(await verify.text());
    const row = rows.slice(1).find(r => r[0] === product[0]);
    expect(Number(row[headers.indexOf('weight_g')])).toBe(412.75);
  }
  await evidence(page, 'finished-goods-csv-import-confirmation');
  await openAdminPage(page, '/admin/products/finished-goods/weight-audit');
  await expect(page.locator('table')).toContainText('Review result');
});

test('mobile Chromium can authenticate and open the goods catalog', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await login(page);
  await openAdminPage(page, '/admin/products');
  await evidence(page, 'mobile-products-catalog');
});
