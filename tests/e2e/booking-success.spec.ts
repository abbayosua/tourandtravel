import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Booking Success - happy path', () => {

  test('kode valid: konfirmasi + kode + detail booking', async ({ page }) => {
    const resp = await page.goto(`${BASE}/booking-success.php?code=TAT-FIX01`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(200);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking Berhasil/i);
    expect(body).toMatch(/Terima kasih/i);
    expect(body).toMatch(/TAT-FIX01/);
    expect(body).toMatch(/HUNAN ZHANGJIAJIE/i);
    expect(body).toMatch(/Budi Santoso|2 orang/i);
    expect(body).toMatch(/5\.000\.000|Rp/i);
  });

  test('kode valid: tombol Tracking & Lihat Tour Lainnya', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=TAT-FIX02`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('a:has-text("Tracking Booking")')).toBeVisible();
    await expect(page.locator('a:has-text("Lihat Tour Lainnya")')).toBeVisible();
    const trackLink = page.locator('a:has-text("Tracking Booking")');
    const href = await trackLink.getAttribute('href');
    expect(href).toContain('track.php?code=TAT-FIX02');
  });

  test('kode valid: link tracking di bagian atas', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=TAT-FIX03`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/TAT-FIX03/);
    expect(body).toMatch(/NEW ZEALAND/i);
  });

  test('kode valid: status badge Pending + simpan kode', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=TAT-FIX04`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Pending/i);
    expect(body).toMatch(/Simpan kode booking/i);
  });
});

test.describe('Booking Success - sad path', () => {

  test('kode tidak ada: redirect ke tours.php', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=TAT-WRONG99`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('kode kosong: redirect ke tours.php', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('tanpa param: redirect ke tours.php', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });

  test('kode XSS: redirect aman, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    // Redirect ke tours.php, tidak ada alert di halaman
    expect(page.url()).toContain('tours.php');
    const body = await page.textContent('body');
    expect(body).not.toMatch(/alert\(1\)/);
  });

  test('kode whitespace: redirect (trim tidak, tapi tidak match -> redirect)', async ({ page }) => {
    await page.goto(`${BASE}/booking-success.php?code=++++`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php');
  });
});