import { test, expect } from '@playwright/test';
const BASE = 'http://localhost/tourandtravel';

test.describe('Polish — dark mode, PWA, manifest', () => {
  test('manifest.json reachable & valid JSON', async ({ page }) => {
    const r = await page.request.get(`${BASE}/manifest.json`);
    expect(r.status()).toBe(200);
    const j = await r.json();
    expect(j.name).toBe('TourAndTravel');
  });

  test('sw.js reachable', async ({ page }) => {
    const r = await page.request.get(`${BASE}/sw.js`);
    expect(r.status()).toBe(200);
  });

  test('dark mode: toggle menetapkan data-theme + persist', async ({ page }) => {
    await page.goto(`${BASE}/index.php`);
    await page.click('#themeToggle');
    const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
    expect(['dark', 'light']).toContain(theme);
    await page.reload();
    const persisted = await page.evaluate(() => document.documentElement.getAttribute('data-theme') || 'light');
    expect(persisted).toBe(theme);
    // bersihkan
    await page.evaluate(() => localStorage.removeItem('theme'));
  });

  test('skip-link a11y ada', async ({ page }) => {
    await page.goto(`${BASE}/index.php`);
    expect(await page.locator('.skip-link').count()).toBeGreaterThan(0);
  });
});
