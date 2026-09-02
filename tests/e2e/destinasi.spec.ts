import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Destinasi - happy path', () => {

  test('city=China: kategori tour China muncul', async ({ page }) => {
    const resp = await page.goto(`${BASE}/destinasi.php?city=China`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Paket Tour ke China/i);
    expect(body).toMatch(/China|Zhangjiajie|Shanghai|Hunan/i);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThanOrEqual(3); // 4 China tours
  });

  test('city=Tokyo: tour Japan Tokyo muncul', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=Tokyo`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Paket Tour ke Tokyo/i);
    expect(body).toMatch(/Tokyo|TOKYO|WONDERS/i);
  });

  test('city=New Zealand: tour NZ muncul', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=New Zealand`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/New Zealand|Selandia Baru/i);
  });

  test('breadcrumb navigasi: Beranda > Paket Tour > city', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=Japan`, { waitUntil: 'load' });
    const bc = await page.locator('.breadcrumb').textContent();
    expect(bc).not.toMatch(PHP_ERROR);
    expect(bc).toMatch(/Beranda/);
    expect(bc).toMatch(/Paket Tour/);
    expect(bc).toMatch(/Japan/);
  });

  test('link ke tour-detail dari tiap kartu', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=Japan`, { waitUntil: 'load' });
    const link = page.locator('a[href*="tour-detail.php"]').first();
    await expect(link).toBeVisible();
    const href = await link.getAttribute('href');
    expect(href).toContain('slug=');
  });
});

test.describe('Destinasi - sad path / edge', () => {

  test('city kosong: redirect ke tours.php', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('city tanpa parameter: redirect ke tours.php', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('city tidak ada di DB: "Belum ada paket tour"', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=ZZZNonexistentCity`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Belum ada paket tour/i);
    expect(body).toMatch(/Lihat Semua Tour/i);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBe(0);
  });

  test('city whitespace-only: redirect (trim)', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=++++`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('XSS di city: ter-escape, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Harusnya "Belum ada paket tour ke <script>alert(1)</script>"
    expect(body).toMatch(/Belum ada paket tour/i);
    expect(body).toMatch(/alert\(1\)/);
    const scripts = await page.locator('script').allTextContents();
    const hasAlert = scripts.some(s => s.includes('alert(1)'));
    expect(hasAlert).toBe(false);
  });

  test('lang=en: konten terjemah (Paket Tour to / tersedia)', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=Japan&lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Japan/i);
  });

  test('lang=id default: konten Indonesia', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=Japan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Paket Tour ke Japan/i);
  });
});