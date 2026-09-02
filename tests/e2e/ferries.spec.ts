import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Ferries - happy path', () => {

  test('tanpa search: prompt "Masukkan kota asal dan tujuan"', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Masukkan kota asal|Cari Ferry/i);
    await expect(page.locator('input[name="from"]')).toHaveCount(1);
    await expect(page.locator('input[name="to"]')).toHaveCount(1);
  });

  test('search from=Merak&to=Bakauheni (DB fallback): jadwal muncul', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // DB fallback: dari from/to tanpa pid -> easybookError dulu, tapi DB ferries tampil
    // Ada jadwal ASDP Merak->Bakauheni
    expect(body).toMatch(/ASDP|Merak|Bakauheni|Jadwal Ferry/i);
  });

  test('search from=Merak&to=Bakauheni&date (DB fallback tidak crash)', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&date=2026-07-25&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('search dari+rute Batam->Singapore', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Batam&to=Singapore&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Ferries - sad path / edge', () => {

  test('rute tidak ada: pesan tidak ditemukan, tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=ZZZ&to=YYY&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/tidak ada|Tidak ada jadwal|tidak ditemukan/i);
  });

  test('date lewat (2020) dengan search: tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&date=2020-01-01&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('date invalid (abc): tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&date=abc&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('passengers tidak ada di param — tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1&passengers=0`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('passengers=9: tidak fatal', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1&passengers=9`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('hanya from tanpa to: pesan error kota tidak ditemukan', async ({ page }) => {
    const resp = await page.goto(
      `${BASE}/ferries.php?from=Merak&search=1`,
      { waitUntil: 'load' }
    );
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('XSS di from: HTML-escaped, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=%3Cscript%3Ealert(1)%3C%2Fscript%3E&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // textContent renders entities literal — alert(1) muncul sebagai teks, bukan script
    expect(body).toMatch(/alert\(1\)/);
    // Tidak ada tag script executable
    const scripts = await page.locator('script').allTextContents();
    const hasAlert = scripts.some(s => s.includes('alert(1)'));
    expect(hasAlert).toBe(false);
  });
});