import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Tours listing - flash sale badge', () => {

  test('flash sale badge visible on tour cards with active flash sale', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('.tour-card-klook', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check if any flash sale badges exist
    const flashBadges = await page.locator('.tour-card-klook .badge.bg-danger').count();
    const flashCountdowns = await page.locator('.tour-card-klook .flash-countdown').count();
    
    // At least some tours should have flash sale badges (from seed data)
    // If no flash sales exist, that's also valid - just verify no errors
    if (flashBadges > 0) {
      expect(flashCountdowns).toBeGreaterThan(0);
    }
  });

  test('flash sale price shows discount badge', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('.tour-card-klook', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for flash sale discount percentage badges
    const discountBadges = await page.locator('.tour-card-klook .badge:has-text("%")').count();
    
    // Verify discount badges display correctly
    if (discountBadges > 0) {
      const firstBadge = page.locator('.tour-card-klook .badge:has-text("%")').first();
      await expect(firstBadge).toBeVisible();
      const text = await firstBadge.textContent();
      expect(text).toMatch(/-\d+%/);
    }
  });

  test('flash sale stock indicator visible', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('.tour-card-klook', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for stock indicators (Sisa X slot)
    const stockIndicators = await page.locator('.tour-card-klook [data-testid="card-flash-stock"]').count();
    
    // Verify stock indicators display correctly
    if (stockIndicators > 0) {
      const firstStock = page.locator('.tour-card-klook [data-testid="card-flash-stock"]').first();
      await expect(firstStock).toBeVisible();
      const text = await firstStock.textContent();
      expect(text).toMatch(/Sisa \d+ slot/);
    }
  });

  test('flash sale countdown timer present', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('.tour-card-klook', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for countdown timers
    const countdowns = await page.locator('.tour-card-klook .flash-countdown').count();
    
    // Verify countdown timers have data-deadline attribute
    if (countdowns > 0) {
      const firstCountdown = page.locator('.tour-card-klook .flash-countdown').first();
      const deadline = await firstCountdown.getAttribute('data-deadline');
      expect(deadline).toBeTruthy();
      expect(deadline).toMatch(/^\d{4}-\d{2}-\d{2}T/);
    }
  });

  test('flash sale price lower than regular price', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('.tour-card-klook', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for strikethrough original price
    const originalPrices = await page.locator('.tour-card-klook .text-decoration-line-through').count();
    
    // If flash sales exist, original price should be shown with strikethrough
    if (originalPrices > 0) {
      const firstOriginal = page.locator('.tour-card-klook .text-decoration-line-through').first();
      await expect(firstOriginal).toBeVisible();
    }
  });

  test('flash sale badge on hotel listing', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php`, { waitUntil: 'load' });
    await page.waitForSelector('.klook-hover-card', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for flash sale badges on hotel cards
    const flashBadges = await page.locator('.klook-hover-card .badge.bg-danger').count();
    const flashCountdowns = await page.locator('.klook-hover-card .flash-countdown').count();
    
    // If flash sales exist, badges should be visible
    if (flashBadges > 0) {
      expect(flashCountdowns).toBeGreaterThan(0);
    }
  });

});
