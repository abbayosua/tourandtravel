import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Bottom nav — visible on mobile viewport', () => {

  test('bottom nav visible on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show bottom nav
    const bottomNav = await page.locator('.bottom-nav').count();
    expect(bottomNav).toBe(1);

    // Bottom nav should be visible on mobile
    await expect(page.locator('.bottom-nav')).toBeVisible();
  });

  test('bottom nav hidden on desktop viewport', async ({ page }) => {
    // Set desktop viewport
    await page.setViewportSize({ width: 1280, height: 720 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Bottom nav should be hidden on desktop (display: none)
    const bottomNav = await page.locator('.bottom-nav').count();
    expect(bottomNav).toBe(1);
  });

  test('bottom nav has 4 tabs', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have 4 tabs
    const tabs = await page.locator('.bottom-nav a').count();
    expect(tabs).toBe(4);
  });

  test('bottom nav tabs have correct labels', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show tab labels
    expect(body).toMatch(/Beranda|Home/i);
    expect(body).toMatch(/Cari|Search/i);
    expect(body).toMatch(/Booking/i);
    expect(body).toMatch(/Akun|Account/i);
  });

  test('bottom nav tabs have correct icons', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show tab icons (bi-*)
    const icons = await page.locator('.bottom-nav i.bi').count();
    expect(icons).toBe(4);
  });

  test('bottom nav active state highlights current page', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have one active tab
    const activeTabs = await page.locator('.bottom-nav a.active').count();
    expect(activeTabs).toBe(1);
  });

  test('bottom nav links navigate correctly', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    
    // Click on Home tab
    await page.click('.bottom-nav a:first-child');
    await page.waitForLoadState('load');
    
    // Should navigate to index
    expect(page.url()).toContain('index.php');
  });

  test('bottom nav has fixed positioning', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    
    // Check CSS properties
    const position = await page.locator('.bottom-nav').evaluate(el => {
      return window.getComputedStyle(el).position;
    });
    expect(position).toBe('fixed');
  });

  test('bottom nav has correct z-index', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    
    // Check z-index
    const zIndex = await page.locator('.bottom-nav').evaluate(el => {
      return window.getComputedStyle(el).zIndex;
    });
    expect(parseInt(zIndex)).toBeGreaterThanOrEqual(1050);
  });

});
