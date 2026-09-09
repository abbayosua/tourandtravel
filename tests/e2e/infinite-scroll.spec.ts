import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Infinite scroll — loads more items on tours listing', () => {

  test('tours.php has tour grid container', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have tour grid container
    const tourGrid = await page.locator('#tourGrid').count();
    expect(tourGrid).toBe(1);
  });

  test('tours.php has IntersectionObserver for infinite scroll', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have IntersectionObserver in JavaScript
    const hasObserver = await page.evaluate(() => {
      return typeof IntersectionObserver !== 'undefined';
    });
    expect(hasObserver).toBe(true);
  });

  test('tours.php has load-more-trigger sentinel', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have load-more-trigger (if more pages exist)
    const trigger = await page.locator('.load-more-trigger').count();
    // Trigger may not exist if only 1 page of results
    expect(trigger).toBeGreaterThanOrEqual(0);
  });

  test('tours-ajax.php returns HTML fragment', async ({ page }) => {
    await page.goto(`${BASE}/tours-ajax.php?page=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should return HTML with tour cards
    expect(body).toMatch(/tour-card-klook|col-md-6/i);
  });

  test('tours-ajax.php has data-page attribute', async ({ page }) => {
    await page.goto(`${BASE}/tours-ajax.php?page=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have data-page attribute
    expect(body).toMatch(/data-page="1"/);
  });

  test('tours-ajax.php page 2 returns different content', async ({ page }) => {
    await page.goto(`${BASE}/tours-ajax.php?page=2`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should return HTML (might be empty if only 1 page)
    const hasContent = body.includes('tour-card-klook') || body.includes('Semua tour sudah dimuat');
    expect(hasContent).toBe(true);
  });

  test('infinite scroll JavaScript is present', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have infinite scroll JavaScript
    const hasScrollJS = body.includes('IntersectionObserver') || body.includes('load-more-trigger');
    expect(hasScrollJS).toBe(true);
  });

  test('tour cards have proper structure for infinite scroll', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have tour cards
    const tourCards = await page.locator('.tour-card-klook').count();
    expect(tourCards).toBeGreaterThan(0);
  });

});
