import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
const BASE = 'http://localhost/tourandtravel';

async function adminLogin(page: import('@playwright/test').Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Analytics — render, filter, guard', () => {
  test('dashboard render KPI + grafik + funnel', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/analytics.php`);
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(/Fatal error/);
    expect(body).toMatch(/Booking|Revenue|Analytics/i);
    expect(page.locator('svg rect').first()).toBeVisible();
  });

  test('filter tanggal mengubah URL & tetap render', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/analytics.php?from=2026-01-01&to=2026-01-31`);
    expect(page.url()).toContain('from=2026-01-01');
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(/Fatal error/);
  });

  test('guard: redirect ke login tanpa sesi', async ({ page }) => {
    await page.goto(`${BASE}/admin/analytics.php`);
    expect(page.url()).toContain('login.php');
  });
});
