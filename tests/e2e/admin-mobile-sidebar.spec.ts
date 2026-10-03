import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX (mobile): sidebar admin = drawer off-canvas (position: fixed)
 * di layar kecil, tetapi dulu dirender TERBUKA (tanpa class `collapsed`)
 * sehingga menutupi konten. Sekarang tertutup secara default di mobile dan
 * terbuka saat tombol toggle ditekan.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';

test.use({ viewport: { width: 390, height: 844 } });

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('sidebar admin tertutup default di mobile, terbuka saat toggle', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/dashboard.php`);

  const sidebar = page.locator('#adminSidebar');
  await expect(sidebar).toHaveClass(/collapsed/);

  // Tertutup → tergeser keluar layar (tidak menutupi konten).
  const box = (await sidebar.boundingBox())!;
  expect(box.x + box.width).toBeLessThanOrEqual(1);

  // Konten mulai dari kiri (tidak tertutup sidebar).
  const content = (await page.locator('#adminContent').boundingBox())!;
  expect(content.x).toBeLessThan(2);

  // Buka via tombol toggle.
  await page.locator('#sidebarToggle').click();
  await expect(sidebar).not.toHaveClass(/collapsed/);
  const openBox = (await sidebar.boundingBox())!;
  expect(openBox.x).toBeGreaterThanOrEqual(0);
  expect(openBox.width).toBeGreaterThan(100);

  await expect(sidebar).toHaveScreenshot('admin-sidebar-open.png', { maxDiffPixelRatio: 0.15 });
});
