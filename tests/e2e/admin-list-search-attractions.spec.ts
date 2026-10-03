import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: daftar atraksi/kereta/transfer admin dulu tanpa kotak
 * pencarian, sehingga daftar kosong hanya menampilkan header tanpa pesan.
 * Sekarang ada pencarian (semua kolom) dan empty state "Belum ada data.".
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

test('pencarian daftar atraksi memfilter dan menampilkan empty state', async ({ page }) => {
  await adminLogin(page);

  await page.goto(`${BASE}/admin/attractions.php?lang=id`);
  await expect(page.locator('[data-testid="admin-list-search"]')).toBeVisible();

  // Cocok → baris tampil, tidak ada empty state.
  await page.goto(`${BASE}/admin/attractions.php?q=Taman%20Mini&lang=id`);
  await expect(page.locator('tbody')).toContainText('Taman Mini');
  await expect(page.locator('tbody')).not.toContainText('Belum ada data.');

  // Tidak cocok → empty state tampil.
  await page.goto(`${BASE}/admin/attractions.php?q=zzznomatchxyz&lang=id`);
  await expect(page.locator('tbody td.text-center')).toContainText('Belum ada data.');
  await expect(page.locator('tbody')).toHaveScreenshot('admin-attractions-empty.png', { maxDiffPixelRatio: 0.15 });
});
