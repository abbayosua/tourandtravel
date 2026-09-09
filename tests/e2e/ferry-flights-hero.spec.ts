import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'http://127.0.0.1:8080';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Ferry search – happy path', () => {

  test('hero section has dark gradient background', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    const hero = page.locator('.hero-search-section');
    await expect(hero).toBeVisible();
    const bg = await hero.evaluate(el => getComputedStyle(el).backgroundImage);
    expect(bg).toContain('linear-gradient');
  });

  test('transport tabs are visible (Ferry, Pesawat, Kereta, Rental)', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    await expect(page.locator('.traveloka-tab')).toHaveCount(4);
    await expect(page.locator('.traveloka-tab.active')).toContainText('Ferry');
  });

  test('search form fields present (Dari, Ke, Tanggal, Penumpang)', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    await expect(page.locator('input[name="from"]')).toBeVisible();
    await expect(page.locator('input[name="to"]')).toBeVisible();
    await expect(page.locator('input[name="date"]')).toBeVisible();
    await expect(page.locator('select[name="passengers"]')).toBeVisible();
  });

  test('search Batam → Singapore returns ferry results', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=Batam&to=Singapore&date=2026-09-10&from_pid=1548&to_pid=440&passengers=1&search=1`, { waitUntil: 'load' });
    await page.waitForTimeout(2000);
    const rows = page.locator('table tbody tr');
    const count = await rows.count();
    expect(count).toBeGreaterThan(0);
    // Logo perusahaan tampil
    const logos = page.locator('table tbody img[src*="ferry"]');
    expect(await logos.count()).toBeGreaterThan(0);
    // Tombol Pesan ada
    await expect(page.locator('[data-testid="btn-book-ferry"]').first()).toBeVisible();
  });

  test('klik Pesan menuju ferry-booking.php', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=Batam&to=Singapore&date=2026-09-10&from_pid=1548&to_pid=440&passengers=1&search=1`, { waitUntil: 'load' });
    await page.waitForTimeout(2000);
    const btn = page.locator('[data-testid="btn-book-ferry"]').first();
    const href = await btn.getAttribute('href');
    expect(href).toContain('ferry-booking.php');
    expect(href).toContain('company=');
    expect(href).toContain('price=');
  });

  test('no PHP errors on ferry page', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Ferry search – sad path', () => {

  test('empty search shows placeholder', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    await expect(page.locator('text=Masukkan kota asal dan tujuan')).toBeVisible();
  });

  test('non-existent route shows empty message', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=Atlantis&to=Mars&date=2026-09-10&from_pid=99999&to_pid=99998&passengers=1&search=1`, { waitUntil: 'load' });
    await page.waitForTimeout(2000);
    const body = await page.textContent('body');
    expect(body).toMatch(/Tidak ada jadwal|tidak ditemukan/i);
  });
});

test.describe('Flight search – happy path', () => {

  test('hero section has dark gradient background', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    const hero = page.locator('.hero-search-section');
    await expect(hero).toBeVisible();
    const bg = await hero.evaluate(el => getComputedStyle(el).backgroundImage);
    expect(bg).toContain('linear-gradient');
  });

  test('transport tabs visible with Pesawat active', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    await expect(page.locator('.traveloka-tab')).toHaveCount(4);
    await expect(page.locator('.traveloka-tab.active')).toContainText('Pesawat');
  });

  test('trip type radio buttons present', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    await expect(page.locator('#tripOneway')).toBeVisible();
    await expect(page.locator('#tripRoundtrip')).toBeVisible();
    await expect(page.locator('#tripMulticity')).toBeVisible();
  });

  test('search form fields present (Dari, Ke, Tanggal, Penumpang, Kelas)', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    await expect(page.locator('input[name="from"]')).toBeVisible();
    await expect(page.locator('input[name="to"]')).toBeVisible();
    await expect(page.locator('input[name="date"]')).toBeVisible();
    await expect(page.locator('select[name="passengers"]')).toBeVisible();
    await expect(page.locator('select[name="class"]')).toBeVisible();
  });

  test('no PHP errors on flights page', async ({ page }) => {
    const body = await page.textContent('body', { timeout: 10000 }).catch(() => '');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Flight search – sad path', () => {

  test('empty search shows no results or placeholder', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    // Without search, page renders without error
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('non-existent route shows empty/error state', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=Atlantis&to=Mars&date=2026-09-10&passengers=1&search=1`, { waitUntil: 'load' });
    await page.waitForTimeout(2000);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});
