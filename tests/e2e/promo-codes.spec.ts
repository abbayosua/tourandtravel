import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Promo Codes - admin CRUD', () => {

  test('list: semua kode promo tampil', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/promo-codes.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kode Promo/i);
    expect(body).toMatch(/HEMAT10|FLAT50|WELCOME/i);
  });

  test('add: submit valid redirect msg=added', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/promo-codes.php?edit=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tambah Kode Promo/i);

    await page.fill('input[name="code"]', 'E2EPROMO');
    await page.fill('input[name="discount_value"]', '20');
    await page.click('button[name="save"]');
    await page.waitForLoadState('load');

    expect(page.url()).toContain('msg=added');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/Berhasil ditambahkan/i);

    // Bersihkan
    await page.goto(`${BASE}/admin/promo-codes.php`, { waitUntil: 'load' });
    page.on('dialog', d => d.accept());
    const del = page.locator('tr:has-text("E2EPROMO") a[href*="delete="]');
    if (await del.count()) {
      await del.first().click();
      await page.waitForLoadState('load');
    }
  });

  test('add duplicate code: error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/promo-codes.php?edit=0`, { waitUntil: 'load' });
    await page.fill('input[name="code"]', 'HEMAT10');
    await page.fill('input[name="discount_value"]', '10');
    await page.click('button[name="save"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/sudah ada|Kode promo/i);
  });

  test('edit: id valid renders form', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/promo-codes.php?edit=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Edit Kode Promo/i);
    await expect(page.locator('input[name="code"]')).toHaveValue(/HEMAT10/i);
  });
});

test.describe('Promo Codes - AJAX endpoint', () => {

  test('HEMAT10: diskon 10% benar', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/apply-promo-ajax.php`, {
      form: { code: 'HEMAT10', subtotal: '1000000' },
    });
    expect(resp.status()).toBe(200);
    const data = await resp.json();
    expect(data.success).toBe(true);
    expect(data.discount).toBe(100000);
    expect(data.total).toBe(900000);
  });

  test('FLAT50: diskon fixed 50000', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/apply-promo-ajax.php`, {
      form: { code: 'FLAT50', subtotal: '500000' },
    });
    const data = await resp.json();
    expect(data.success).toBe(true);
    expect(data.discount).toBe(50000);
  });

  test('kode tidak ada: error', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/apply-promo-ajax.php`, {
      form: { code: 'TIDAKADA99', subtotal: '1000000' },
    });
    const data = await resp.json();
    expect(data.success).toBe(false);
    expect(data.message).toMatch(/tidak ditemukan/i);
  });

  test('min purchase tidak terpenuhi: error', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/apply-promo-ajax.php`, {
      form: { code: 'HEMAT10', subtotal: '100000' },
    });
    const data = await resp.json();
    expect(data.success).toBe(false);
    expect(data.message).toMatch(/Minimal pembelian/i);
  });

  test('GET (bukan POST): method not allowed', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/apply-promo-ajax.php`);
    const data = await resp.json();
    expect(data.success).toBe(false);
    expect(data.message).toMatch(/Method not allowed/i);
  });
});