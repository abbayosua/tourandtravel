import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;
const SLUG = 'bandara-soekarno-hatta-ke-kota-jakarta';

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Transfers - catalog', () => {

  test('tanpa filter: semua transfer tampil', async ({ page }) => {
    const resp = await page.goto(`${BASE}/transfers.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Transfer|transfer ditemukan/i);
    expect(body).toMatch(/Soekarno-Hatta|Ngurah Rai|YIA/i);
  });

  test('filter from: Jakarta', async ({ page }) => {
    await page.goto(`${BASE}/transfers.php?from=Soekarno-Hatta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Soekarno-Hatta|Jakarta/i);
  });

  test('filter vehicle: MVP', async ({ page }) => {
    await page.goto(`${BASE}/transfers.php?vehicle=MVP`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/MVP/i);
  });

  test('empty state: rute tidak ada', async ({ page }) => {
    await page.goto(`${BASE}/transfers.php?from=UnknownCity999`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada transfer ditemukan|tidak ada/i);
    await expect(page.locator('a:has-text("Reset Filter")')).toBeVisible();
  });
});

test.describe('Transfers - detail', () => {

  test('slug valid: nama, rute, harga render', async ({ page }) => {
    const resp = await page.goto(`${BASE}/transfer-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Soekarno-Hatta|Kota Jakarta/i);
    expect(body).toMatch(/150\.000|Rp/i);
  });

  test('guest: login prompt muncul', async ({ page }) => {
    await page.goto(`${BASE}/transfer-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/Login untuk Booking|Masuk/i);
    await expect(page.locator('a:has-text("Masuk / Daftar")')).toBeVisible();
  });

  test('slug tidak ada: 404', async ({ page }) => {
    const resp = await page.goto(`${BASE}/transfer-detail.php?slug=nonexistent-transfer-999`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);
    const body = await page.textContent('body');
    expect(body).toMatch(/Transfer tidak ditemukan|tidak ditemukan/i);
  });
});

test.describe('Transfers - admin CRUD', () => {

  test('admin list: semua transfer tampil', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/transfers.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Transfer Bandara/i);
    expect(body).toMatch(/Soekarno-Hatta|Ngurah Rai/i);
    await expect(page.locator('a:has-text("Tambah")')).toBeVisible();
  });

  test('admin add: submit valid redirect msg=added', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/transfer-edit.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tambah Transfer/i);

    await page.fill('input[name="name"]', 'E2E Test Transfer');
    await page.fill('input[name="from_city"]', 'Test Airport');
    await page.fill('input[name="to_city"]', 'Test City');
    await page.fill('input[name="price"]', '200000');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    expect(page.url()).toContain('msg=added');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/Berhasil ditambahkan/i);

    // Bersihkan
    await page.goto(`${BASE}/admin/transfers.php`, { waitUntil: 'load' });
    page.on('dialog', d => d.accept());
    let deleteLinks = page.locator('tr:has-text("E2E Test Transfer") a[href*="delete="]');
    let n = await deleteLinks.count();
    while (n > 0) {
      await deleteLinks.first().click();
      await page.waitForLoadState('load');
      deleteLinks = page.locator('tr:has-text("E2E Test Transfer") a[href*="delete="]');
      n = await deleteLinks.count();
    }
  });

  test('admin edit: id valid renders form', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/transfer-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Edit Transfer/i);
    await expect(page.locator('input[name="name"]')).toHaveValue(/Soekarno-Hatta|Transfer/i);
  });
});