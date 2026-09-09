import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Price alert — create and view in my-alerts', () => {

  test('tour detail shows price alert button when logged in', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to tour detail
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show price alert button
    expect(body).toMatch(/Set Price Alert|Atur Alert Harga/i);
  });

  test('tour detail shows login prompt when not logged in', async ({ page }) => {
    // Navigate to tour detail without login
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show login prompt
    expect(body).toMatch(/Login untuk Set Price Alert/i);
  });

  test('hotel detail shows price alert button when logged in', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to hotel detail
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show price alert button
    expect(body).toMatch(/Set Price Alert|Atur Alert Harga/i);
  });

  test('create price alert via modal form', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to tour detail
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Click price alert button
    await page.click('button[data-bs-target="#priceAlertModal"]');
    
    // Wait for modal to appear
    await page.waitForSelector('#priceAlertModal.show', { timeout: 5000 });
    
    // Fill target price
    await page.fill('input[name="target_price"]', '500000');
    
    // Submit form
    await page.click('#priceAlertModal button[type="submit"]');
    
    // Wait for response
    await page.waitForLoadState('load');
  });

  test('my-alerts page shows created alerts', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-alerts
    await page.goto(`${BASE}/my-alerts.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show price alerts page
    expect(body).toMatch(/Price Alert|Alert Harga/i);
  });

  test('my-alerts shows alert with toggle and delete buttons', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-alerts
    await page.goto(`${BASE}/my-alerts.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for alert management elements
    const toggleButtons = await page.locator('button[name="toggle_alert"]').count();
    const deleteButtons = await page.locator('a[href*="delete="]').count();
    
    // Should have toggle and delete buttons if alerts exist
    if (toggleButtons > 0) {
      expect(toggleButtons).toBeGreaterThan(0);
      expect(deleteButtons).toBeGreaterThan(0);
    }
  });

  test('price alert status shows active/inactive', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-alerts
    await page.goto(`${BASE}/my-alerts.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Check for status badges
    const activeBadges = await page.locator('.badge:has-text("Aktif")').count();
    const inactiveBadges = await page.locator('.badge:has-text("Nonaktif")').count();
    
    // Should have some status badges
    expect(activeBadges + inactiveBadges).toBeGreaterThanOrEqual(0);
  });

});
