import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: daftar hotel admin dulu tanpa kotak pencarian dan tanpa empty
 * state (tabel kosong = header saja). Sekarang ada pencarian (nama/kota) dan
 * baris "Belum ada data." saat hasil kosong.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('pencarian hotel admin memfilter dan menampilkan empty state', async ({ page }) => {
  await adminLogin(page);

  await page.goto(`${BASE}/admin/hotels.php?lang=id`);
  await expect(page.locator('[data-testid="admin-hotel-search"]')).toBeVisible();

  // Cocok → baris tampil, tidak ada empty state.
  await page.goto(`${BASE}/admin/hotels.php?q=Grand&lang=id`);
  await expect(page.locator('tbody')).toContainText('Grand Hyatt Bali');
  await expect(page.locator('tbody')).not.toContainText('Belum ada data.');

  // Tidak cocok → empty state tampil.
  await page.goto(`${BASE}/admin/hotels.php?q=zzznomatchxyz&lang=id`);
  const empty = page.locator('tbody td.text-center');
  await expect(empty).toContainText('Tidak ada hasil untuk pencarian Anda.');
  await expect(page.locator('tbody')).toHaveScreenshot('admin-list-empty.png', { maxDiffPixelRatio: 0.15 });
});
