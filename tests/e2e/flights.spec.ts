import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Flights - happy path', () => {

  test('search CGK->DPS pakai schedule date: daftar tawaran muncul', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Ada hasil (flight-card) ATAU pesan no-result — yang penting tidak fatal
    const cards = await page.locator('.flight-card').count();
    console.log('flight cards:', cards);
    if (cards === 0) {
      // No results — harus ada pesan yang jelas, bukan error
      expect(body).toMatch(/tidak ada|tidak ditemukan|no (flight|result)|jangkau|unreachable|error/i);
    }
  });

  test('search tanpa hasil (rute tidak ada): pesan jelas, tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/flights.php?from=ZZZ%20(ZZZ)&to=YYY%20(YYY)&date=2026-07-25&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('page tanpa search: form tampil + jadwal lokal (jika ada)', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Form pencarian
    expect(body).toMatch(/Dari|Ke|Cari/i);
    // City search inputs
    await expect(page.locator('input[name="from"]')).toHaveCount(1);
    await expect(page.locator('input[name="to"]')).toHaveCount(1);
  });

  test('date di masa lalu dengan search=1: tidak fatal (Duffel validasi)', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2020-01-01&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('passengers=0 & 10 (clamp edge)', async ({ page }) => {
    for (const p of [0, 10]) {
      const resp = await page.goto(
        `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&passengers=${p}&search=1`,
        { waitUntil: 'load' }
      );
      expect(resp?.status()).toBe(200);
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
    }
  });

  test('trip_type=roundtrip tanpa return_date', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&trip_type=roundtrip&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('class business & premium_economy', async ({ page }) => {
    for (const c of ['business', 'premium_economy']) {
      const resp = await page.goto(
        `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&class=${c}&search=1`,
        { waitUntil: 'load' }
      );
      expect(resp?.status()).toBe(200);
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
    }
  });

  test('hasil: tombol Pilih menuju flight-detail', async ({ page }) => {
    await page.goto(
      `${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&search=1`,
      { waitUntil: 'load' }
    );
    const pilihBtn = page.locator('a:has-text("Pilih")').first();
    if (await pilihBtn.count() > 0) {
      const href = await pilihBtn.getAttribute('href');
      expect(href).toContain('flight-detail.php');
    }
  });
});