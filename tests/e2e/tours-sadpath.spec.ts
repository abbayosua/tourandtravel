import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Tours listing - sad path / edge cases', () => {

  test('category tidak valid (non-existent)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?category=NoCategory999`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Empty state: "Tidak ada tour ditemukan"
    expect(body).toMatch(/Tidak ada tour ditemukan|tidak ada/);
    // Reset Filter button present
    await expect(page.locator('a:has-text("Reset Filter")')).toBeVisible();
  });

  test('harga tidak valid (invalid value)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?harga=99`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Invalid value falls through to default (no filter) — shows all tours
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('page=999 (beyond last page)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?page=999`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should show empty state or first page — but definitely no PHP error
    // With 8 tours / 12 perPage, lastPage=1, page gets clamped to 1
    // So it should show tours
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('page invalid (string)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?page=abc`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // (int)"abc" = 0, max(1,0) = 1, so page 1
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('category+harga+rating combined no match', async ({ page }) => {
    // China + harga=1 (< 5jt) + rating=4.5 — China tours min 998 USD ≈ 15jt+ IDR
    await page.goto(`${BASE}/tours.php?category=Japan&harga=1&rating=4.5`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Japan tours are all > 20jt, so harga=1 (<5jt) = no match
    expect(body).toMatch(/Tidak ada tour ditemukan|tidak ada/);
  });

  test('sort invalid value', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?sort=invalid_sort_value`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Falls back to default sort (created_at DESC) — shows all tours
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('empty query string (all default)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThanOrEqual(8); // minimal 8 tour (bisa lebih jika test lain menambah)
  });
});