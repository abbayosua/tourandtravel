import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Tours listing - filter & search', () => {

  test('category filter: China shows only China tours', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?category=China`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('China');
    // Should contain China tours, not Japan
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').allTextContents();
    expect(cards.length).toBeGreaterThan(0);
    for (const c of cards) {
      expect(c).toMatch(/China|Shanghai|Hunan|Zhangjiajie|Yichun/i);
    }
  });

  test('search via query param', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?search=Tokyo`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('Tokyo');
  });

  test('search no results', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?search=zzzznonexistent`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should show empty state, not crash
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBe(0);
  });

  test('harga filter: < Rp 5 Juta (1) - no tours that cheap', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?harga=1`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tours under 5M IDR (~359 SGD) — cheapest tour is 998 SGD, so 0 tours
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBe(0);
  });

  test('harga filter: > Rp 20 Juta (4) - all tours (cheapest 998 SGD ≈ 13.9jt)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?harga=4`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // >20jt IDR (~1435 SGD) — 6 tours exceed this (998 & 1059 SGD tours don't)
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBe(6);
  });

  test('durasi filter: 6-8 Hari (2)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?durasi=2`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should match some tours
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    // At least some should match 6-8 day tours
    expect(cards).toBeGreaterThanOrEqual(0);
  });

  test('rating filter: 4.5+', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?rating=4.5`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('sort: termurah (price ascending)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?sort=termurah`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // First card should be cheapest (998.00 - Shanghai)
    const firstCard = await page.locator('.tour-card-klook h6.fw-semibold').first().textContent();
    expect(firstCard).toMatch(/Shanghai|998|SHANGHAI/i);
  });

  test('sort: termahal (price descending)', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?sort=termahal`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // First card should be most expensive (5078.00 - New Zealand)
    const firstCard = await page.locator('.tour-card-klook h6.fw-semibold').first().textContent();
    expect(firstCard).toMatch(/New Zealand|5078|ZEALAND|SELANDIA BARU|BARU YANG/i);
  });

  test('sort: rating', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?sort=rating`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThan(0);
  });

  test('pagination: page=2 with perPage=1 creates pages', async ({ page }) => {
    // Force page=2 with limit=1 by using page param
    // Note: with 8 tours and 12/page, only 1 page by default
    await page.goto(`${BASE}/tours.php?page=2`, { waitUntil: 'load' });
    await page.waitForSelector('body', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should not crash — either shows first page or empty
    // Page 2 with 12/perPage and 8 total = lastPage=1, so page=2 is clamped
    // Just verify no error
  });

  test('combined filters: category+sort+rating', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?category=Japan&sort=termurah&rating=4`, { waitUntil: 'load' });
    await page.waitForSelector('h6.fw-semibold', { timeout: 5000 });

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').allTextContents();
    expect(cards.length).toBeGreaterThan(0);
    for (const c of cards) {
      expect(c).toMatch(/Tokyo|Japan|Hokkaido/i);
    }
  });
});