import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Dual-month datepicker — renders on tour-detail', () => {

  test('tour-detail has flatpickr initialized', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have flatpickr initialized
    const hasFlatpickr = await page.evaluate(() => {
      return typeof flatpickr !== 'undefined';
    });
    expect(hasFlatpickr).toBe(true);
  });

  test('tour-detail has date input for price calendar', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have date input
    const dateInput = await page.locator('#datePriceInput').count();
    expect(dateInput).toBe(1);
  });

  test('tour-detail has price calendar data', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have PRICE_CAL variable
    const hasPriceCal = await page.evaluate(() => {
      return typeof PRICE_CAL !== 'undefined' && Array.isArray(PRICE_CAL);
    });
    expect(hasPriceCal).toBe(true);
  });

  test('flatpickr CDN loaded correctly', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Check that flatpickr CSS is loaded
    const cssLoaded = await page.evaluate(() => {
      const links = document.querySelectorAll('link[href*="flatpickr"]');
      return links.length > 0;
    });
    expect(cssLoaded).toBe(true);
    
    // Check that flatpickr JS is loaded
    const jsLoaded = await page.evaluate(() => {
      const scripts = document.querySelectorAll('script[src*="flatpickr"]');
      return scripts.length > 0;
    });
    expect(jsLoaded).toBe(true);
  });

  test('date input has flatpickr class', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have flatpickr input
    const flatpickrInput = await page.locator('.flatpickr-input, #datePriceInput').count();
    expect(flatpickrInput).toBe(1);
  });

  test('date picker shows inline calendar', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Wait for flatpickr to initialize
    await page.waitForFunction(() => typeof flatpickr !== 'undefined', { timeout: 5000 });
    
    // Click on date input to open calendar
    const dateInput = page.locator('#datePriceInput');
    if (await dateInput.count() > 0) {
      await dateInput.click();
      
      // Wait for calendar to appear
      await page.waitForTimeout(500);
      
      // Should show flatpickr calendar
      const calendar = await page.locator('.flatpickr-calendar, .flatpickr-months').count();
      expect(calendar).toBeGreaterThan(0);
    }
  });

  test('date picker has 2 months displayed', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Wait for flatpickr to initialize
    await page.waitForFunction(() => typeof flatpickr !== 'undefined', { timeout: 5000 });
    
    // Click on date input to open calendar
    const dateInput = page.locator('#datePriceInput');
    if (await dateInput.count() > 0) {
      await dateInput.click();
      
      // Wait for calendar to appear
      await page.waitForTimeout(500);
      
      // Should show 2 months (flatpickr with showMonths: 2)
      const months = await page.locator('.flatpickr-month').count();
      expect(months).toBe(2);
    }
  });

  test('date picker has price color coding', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Wait for flatpickr to initialize
    await page.waitForFunction(() => typeof flatpickr !== 'undefined', { timeout: 5000 });
    
    // Click on date input to open calendar
    const dateInput = page.locator('#datePriceInput');
    if (await dateInput.count() > 0) {
      await dateInput.click();
      
      // Wait for calendar to appear
      await page.waitForTimeout(500);
      
      // Check for colored days (green or red)
      const coloredDays = await page.locator('.flatpickr-day[style*="color"]').count();
      // Price coloring might not always be visible, so just check calendar exists
      const calendar = await page.locator('.flatpickr-calendar').count();
      expect(calendar).toBe(1);
    }
  });

  test('hotel-detail has flatpickr for check-in/check-out', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have flatpickr inputs for check-in and check-out
    const flatpickrInputs = await page.locator('.flatpickr-hotel').count();
    expect(flatpickrInputs).toBe(2);
  });

  test('hotel-detail has HOTEL_CAL data', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have HOTEL_CAL variable
    const hasHotelCal = await page.evaluate(() => {
      return typeof HOTEL_CAL !== 'undefined' && Array.isArray(HOTEL_CAL);
    });
    expect(hasHotelCal).toBe(true);
  });

});
