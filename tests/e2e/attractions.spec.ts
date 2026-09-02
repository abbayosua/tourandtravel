import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;
const SLUG = 'waterbom-bali-day-pass';
const SLUG_DETAIL = 'tiket-masuk-taman-mini-indonesia-indah';

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Attractions - catalog', () => {

  test('tanpa filter: semua tiket tampil', async ({ page }) => {
    const resp = await page.goto(`${BASE}/attractions.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tiket Tempat Wisata|tiket ditemukan/i);
    expect(body).toMatch(/Taman Mini|Monas|Waterbom/i);
  });

  test('filter by city: Jakarta', async ({ page }) => {
    await page.goto(`${BASE}/attractions.php?city=Jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Jakarta/i);
    expect(body).toMatch(/Taman Mini|Monas|Jakarta Aquarium/i);
  });

  test('filter by category: Taman Air', async ({ page }) => {
    await page.goto(`${BASE}/attractions.php?category=Taman+Air`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Waterbom|Taman Air/i);
  });

  test('empty state: filter tidak ada hasil', async ({ page }) => {
    await page.goto(`${BASE}/attractions.php?city=UnknownCity999`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada tiket ditemukan|tidak ada/i);
    await expect(page.locator('a:has-text("Reset Filter")')).toBeVisible();
  });
});

test.describe('Attractions - detail', () => {

  test('slug valid: nama, harga, deskripsi render', async ({ page }) => {
    const resp = await page.goto(`${BASE}/attraction-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Waterbom Bali/i);
    expect(body).toMatch(/350\.000|Rp/i);
    expect(body).toMatch(/Taman Air|Bali/i);
  });

  test('guest: login prompt muncul', async ({ page }) => {
    await page.goto(`${BASE}/attraction-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/Login untuk Booking|Masuk/i);
    await expect(page.locator('a:has-text("Masuk / Daftar")')).toBeVisible();
  });

  test('slug tidak ada: 404', async ({ page }) => {
    const resp = await page.goto(`${BASE}/attraction-detail.php?slug=nonexistent-slug-123`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tiket tidak ditemukan|tidak ditemukan/i);
  });
});

test.describe('Attractions - admin CRUD', () => {

  test('admin list: semua tiket tampil + tambah button', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/attractions.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tiket Tempat Wisata/i);
    expect(body).toMatch(/Monas|Waterbom|Taman Mini/i);
    await expect(page.locator('a:has-text("Tambah")')).toBeVisible();
  });

  test('admin add: form render + submit valid', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/attraction-edit.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tambah Tiket/i);

    await page.fill('input[name="name"]', 'E2E Test Attraction');
    await page.fill('input[name="city"]', 'Jakarta');
    await page.fill('input[name="price"]', '75000');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    expect(page.url()).toContain('msg=added');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/Berhasil ditambahkan/i);

    // Bersihkan
    await page.goto(`${BASE}/admin/attractions.php`, { waitUntil: 'load' });
    page.on('dialog', d => d.accept());
    let deleteLinks = page.locator('tr:has-text("E2E Test Attraction") a[href*="delete="]');
    let n = await deleteLinks.count();
    while (n > 0) {
      await deleteLinks.first().click();
      await page.waitForLoadState('load');
      deleteLinks = page.locator('tr:has-text("E2E Test Attraction") a[href*="delete="]');
      n = await deleteLinks.count();
    }
  });

  test('admin edit: id valid renders form', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/attraction-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Edit Tiket/i);
    await expect(page.locator('input[name="name"]')).toHaveValue(/Taman Mini/i);
  });
});