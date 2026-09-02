import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Track booking - happy path', () => {

  test('kode valid (confirmed): status + detail tour tampil', async ({ page }) => {
    const resp = await page.goto(`${BASE}/track.php?code=TAT-FIX01`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(200);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/TAT-FIX01/);
    expect(body).toMatch(/Dikonfirmasi|confirmed/i);
    expect(body).toMatch(/HUNAN ZHANGJIAJIE/i);
    expect(body).toMatch(/Budi Santoso|budi@/i);
    expect(body).toMatch(/2 orang/i);
    expect(body).toMatch(/Kembali ke halaman tour/i);
  });

  test('kode valid (pending): status "Menunggu Konfirmasi"', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=TAT-FIX02`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/TAT-FIX02/);
    expect(body).toMatch(/Menunggu Konfirmasi|pending/i);
    expect(body).toMatch(/TOKYO WONDERS/i);
    expect(body).toMatch(/Siti Aminah/i);
  });

  test('kode valid: timeline render (4 step)', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=TAT-FIX03`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking|Konfirmasi|Pembayaran|Selesai/i);
    expect(body).toMatch(/NEW ZEALAND/i);
  });

  test('kode valid: total harga + detail tabel', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=TAT-FIX01`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Total|Harga|5\.000\.000|Rp/i);
    expect(body).toMatch(/departure_date|2026|Desember/i);
  });
});

test.describe('Track booking - sad path', () => {

  test('kode kosong: form pencarian tampil', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Cari Booking|Masukkan kode booking/i);
    await expect(page.locator('input[name="code"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]:has-text("Cari")')).toBeVisible();
  });

  test('tanpa param code: form pencarian', async ({ page }) => {
    await page.goto(`${BASE}/track.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Cari Booking/i);
  });

  test('kode salah (tidak ada): form pencarian (tidak ada booking)', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=TAT-WRONG99`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Cari Booking|Masukkan kode booking/i);
    expect(body).not.toMatch(/TAT-WRONG99/);
  });

  test('kode XSS: ter-escape di form, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tidak ditemukan — form pencarian
    expect(body).toMatch(/Cari Booking|Masukkan kode booking/i);
    const scripts = await page.locator('script').allTextContents();
    const hasAlert = scripts.some(s => s.includes('alert(1)'));
    expect(hasAlert).toBe(false);
  });

  test('kode whitespace-only: form pencarian (trim kosong)', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=++++`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Cari Booking/i);
  });
});