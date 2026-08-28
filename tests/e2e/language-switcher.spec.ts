import { test, expect } from '@playwright/test';

const BASE = 'https://tourandtravel.web.id';
const TOUR_SLUG = '12d-wonders-of-t-rk-ye-guangzhou';

test.describe('Language Switcher - Query Params Preservation', () => {

  test('switching language on tour-detail preserves slug param', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${TOUR_SLUG}`);
    await expect(page).toHaveURL(new RegExp(`slug=${TOUR_SLUG}`));

    // Open language dropdown and click English
    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("English")').click();
    await page.waitForLoadState('domcontentloaded');

    const url = page.url();
    console.log('After EN:', url);
    expect(url).toContain(`slug=${TOUR_SLUG}`);

    // Switch back to Indonesian
    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('domcontentloaded');

    const url2 = page.url();
    console.log('After ID:', url2);
    expect(url2).toContain(`slug=${TOUR_SLUG}`);
  });

  test('switching language on flights page preserves all params', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=CGK&to=DPS&date=2026-09-15&search=1`);

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("English")').click();
    await page.waitForLoadState('domcontentloaded');

    const url = page.url();
    console.log('Flights EN:', url);
    expect(url).toContain('from=CGK');
    expect(url).toContain('to=DPS');
    expect(url).toContain('date=2026-09-15');
  });

  test('switching language on tours listing preserves category', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?category=Internasional`);

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("English")').click();
    await page.waitForLoadState('domcontentloaded');

    const url = page.url();
    console.log('Tours EN:', url);
    expect(url).toContain('category=Internasional');
  });
});
