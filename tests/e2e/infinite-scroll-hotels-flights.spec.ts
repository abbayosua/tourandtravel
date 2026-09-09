import { test, expect } from '@playwright/test';

const PHP_ERROR = /(Fatal error|Warning:|Deprecated|Parse error|Uncaught|Undefined variable|error\s*:\s*<)/i;

test.describe('Infinite scroll hotels & flights', () => {

  test('hotels: sentinel ada + data-page/data-last-page benar', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/hotels.php', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const sentinel = page.locator('[data-testid="hotel-load-more"]');
    await expect(sentinel).toBeVisible();
    expect(await sentinel.getAttribute('data-page')).toBe('1');
    expect(parseInt(await sentinel.getAttribute('data-last-page') ?? '0')).toBeGreaterThanOrEqual(2);
  });

  test('hotels: AJAX endpoint merespons halaman 2 dengan kartu data-page=2', async ({ request }) => {
    const res = await request.get('http://localhost/tourandtravel/hotels-ajax.php?page=2');
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('data-page="2"');
    expect(html).not.toMatch(PHP_ERROR);
  });

  test('hotels: scroll → konten bertambah tanpa reload', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/hotels.php', { waitUntil: 'load' });
    await page.waitForTimeout(600);
    const before = await page.locator('#hotelContent .klook-hover-card').count();
    const ajaxReqs: string[] = [];
    page.on('request', r => { if (r.url().includes('hotels-ajax.php')) ajaxReqs.push(r.url()); });
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2500);
    const after = await page.locator('#hotelContent .klook-hover-card').count();
    expect(after).toBeGreaterThan(before);
    expect(ajaxReqs.some(u => u.includes('page=2'))).toBe(true);
    // halaman tidak reload
    expect(await page.locator('#hotelContent .klook-hover-card').count()).toBe(after);
  });

  test('hotels: sentinel hilang setelah semua halaman termuat', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/hotels.php', { waitUntil: 'load' });
    await page.waitForTimeout(600);
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2500);
    await expect(page.locator('[data-testid="hotel-load-more"]')).toHaveCount(0);
  });

  test('flights: sentinel ada + data-last-page >= 2', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/flights.php', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const sentinel = page.locator('[data-testid="flight-load-more"]');
    await expect(sentinel).toBeVisible();
    expect(await sentinel.getAttribute('data-page')).toBe('1');
    expect(parseInt(await sentinel.getAttribute('data-last-page') ?? '0')).toBeGreaterThanOrEqual(2);
  });

  test('flights: AJAX endpoint merespons halaman 2', async ({ request }) => {
    const res = await request.get('http://localhost/tourandtravel/flights-ajax.php?page=2');
    expect(res.status()).toBe(200);
    const html = await res.text();
    expect(html).toContain('data-page="2"');
    expect(html).not.toMatch(PHP_ERROR);
  });

  test('flights: scroll → konten bertambah tanpa reload', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/flights.php', { waitUntil: 'load' });
    await page.waitForTimeout(600);
    const before = await page.locator('#flightGrid .flight-card').count();
    const ajaxReqs: string[] = [];
    page.on('request', r => { if (r.url().includes('flights-ajax.php')) ajaxReqs.push(r.url()); });
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2500);
    const after = await page.locator('#flightGrid .flight-card').count();
    expect(after).toBeGreaterThan(before);
    expect(ajaxReqs.some(u => u.includes('page=2'))).toBe(true);
  });

});
