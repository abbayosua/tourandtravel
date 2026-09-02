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

test.describe('Collections - public page', () => {

  test('collection valid: render header + tour grid', async ({ page }) => {
    const resp = await page.goto(`${BASE}/collection.php?slug=best-seller`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Best Seller/i);
    expect(body).toMatch(/tour/i);
    // Tour cards render
    const cards = await page.locator('.tour-card-klook').count();
    expect(cards).toBeGreaterThanOrEqual(3);
  });

  test('collection 404: slug tidak ada', async ({ page }) => {
    const resp = await page.goto(`${BASE}/collection.php?slug=nonexistent-collection-999`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Collection tidak ditemukan|tidak ditemukan/i);
  });

  test('collection di index: Best Seller section muncul', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Best Seller collection section (dari tabel collections)
    expect(body).toMatch(/Best Seller/i);
    expect(body).toMatch(/Lihat Semua/i);
  });
});

test.describe('Collections - admin CRUD', () => {

  test('admin list: collections tampil', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/collections.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Koleksi Tour/i);
    expect(body).toMatch(/Best Seller/i);
    await expect(page.locator('a:has-text("Tambah Koleksi")')).toBeVisible();
  });

  test('admin edit: id valid renders form + tour checkboxes', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/collections.php?edit=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Edit Koleksi/i);
    await expect(page.locator('input[name="name"]')).toHaveValue(/Best Seller/i);
    // Tour checkboxes visible
    const checks = await page.locator('input[name="tour_ids[]"]').count();
    expect(checks).toBeGreaterThanOrEqual(6); // 6+ tours di DB
  });

  test('admin add: submit valid redirect msg=added', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/collections.php?edit=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tambah Koleksi/i);

    await page.fill('input[name="name"]', 'E2E Test Collection');
    await page.click('button[name="save"]');
    await page.waitForLoadState('load');

    expect(page.url()).toContain('msg=added');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/Berhasil ditambahkan/i);

    // Bersihkan
    page.on('dialog', d => d.accept());
    let del = page.locator('tr:has-text("E2E Test Collection") a[href*="delete="]');
    if (await del.count()) {
      await del.first().click();
      await page.waitForLoadState('load');
    }
  });
});