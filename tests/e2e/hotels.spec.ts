import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Hotels listing - happy path', () => {

  test('tanpa filter: semua hotel tampil', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/\d+ hotels? (ditemukan|found)/i);
    const cards = await page.locator('h6.fw-semibold.mb-1').count();
    expect(cards).toBeGreaterThanOrEqual(10);
  });

  test('filter city: Bali (live atau fallback DB)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=Bali`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/\d+ hotels?\s*(ditemukan|found)/i);
    const cards = await page.locator('[data-testid="card-price"]').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('filter city: Jakarta (live)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=Jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/\d+ hotels?\s*(ditemukan|found)/i);
    const cards = await page.locator('[data-testid="card-price"]').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('filter city: Bandung (live)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=Bandung`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/\d+ hotels?\s*(ditemukan|found)/i);
    const cards = await page.locator('[data-testid="card-price"]').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('sort: price_desc (harga termahal)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?sort=price_desc`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const prices = await page.locator('span.fw-bold.text-primary.fs-5').allTextContents();
    expect(prices.length).toBeGreaterThan(0);
    // First should be premium (4.5jt) — ID: 4.500.000 / EN: 4,500,000
    expect(prices[0]).toMatch(/4[.,]500/);
  });

  test('stars filter: 5-star', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?stars=5`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should show only 5-star hotels
    expect(body).toMatch(/★★★★★/i);
    // Check count
  });
});

test.describe('Hotels listing - sad path / edge cases', () => {

  test('city tidak ada: empty state', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=ZZZNonexistentCity`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada hotel ditemukan|No hotels found|tidak ada|no hotels/i);
    await expect(page.locator('a:has-text("Reset")')).toBeVisible();
  });

  test('city case-insensitive: jakarta', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/hotel ditemukan|hotels? found/i);
    expect(body).toMatch(/Jakarta/i);
    const cards = await page.locator('h6.fw-semibold.mb-1').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('stars invalid (99): fallback ke semua', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?stars=99`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Invalid stars — no match, show all hotels
    expect(body).toMatch(/\d+ hotels? (ditemukan|found)/i);
  });

  test('stars tidak match (1): empty state', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?stars=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada hotel ditemukan|No hotels found|tidak ada|no hotels/i);
  });

  test('sort invalid value: fallback default', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?sort=blahblah`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Default sort = price ASC — shows all hotels
    expect(body).toMatch(/\d+ hotels? (ditemukan|found)/i);
  });

  test('guests=0 (edge — diteruskan ke detail)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?guests=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/hotel ditemukan|hotels? found/i);
    // Check that the Pesan link includes guests=0
    const pesanLink = page.locator('a[href*="guests=0"]').first();
    const href = await pesanLink.getAttribute('href');
    expect(href).toContain('guests=0');
  });

  test('guests=10 (edge — batas atas)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?guests=10`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Halaman tetap render, guests diteruskan ke detail
    const pesanLink = page.locator('a[href*="guests=10"]').first();
    const href = await pesanLink.getAttribute('href');
    expect(href).toContain('guests=10');
  });

  test('checkout < checkin (diteruskan ke detail — tidak divalidasi di list)', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?checkin=2025-06-10&checkout=2025-06-05`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // List tidak validasi, hanya render
    expect(body).toMatch(/hotel ditemukan|hotels? found/i);
  });

  test('city+stars+sort combined', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=Jakarta&stars=4&sort=price_desc`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/hotel ditemukan|hotels? found/i);
    expect(body).toMatch(/Jakarta|★★★★/i);
  });
});