import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

const SQLI = "' OR '1'='1' -- ";
const SQLI_ENC = encodeURIComponent(SQLI);

test.describe('Abuse - SQL Injection', () => {

  test('tour-detail slug: tidak crash, tidak bocor semua tour', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SQLI_ENC}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Harus 404, bukan menampilkan tour pertama
    expect(body).toMatch(/Tour tidak ditemukan|tidak ditemukan|not found|404/i);
    // Tidak ada judul tour dari DB
    expect(body).not.toMatch(/HUNAN|TOKYO|NEW ZEALAND|ZHANGJIAJIE|WONDERS/i);
  });

  test('hotel-detail slug: tidak crash, redirect ke hotels.php', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SQLI_ENC}`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Redirect ke hotels.php (bukan detail item)
    expect(page.url()).toContain('hotels.php');
    // Tidak crash — halaman list render normal
    expect(body).toMatch(/hotel|Hotel/i);
  });

  test('rental-car-detail slug: tidak crash, redirect ke rental-cars.php', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=${SQLI_ENC}`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Redirect ke rental-cars.php (list) — bukan detail
    expect(page.url()).toContain('rental-cars.php');
    // Tidak crash
    expect(body).toMatch(/Rental|mobil|Mobil/i);
  });

  test('search-ajax q: JSON response, tidak crash', async ({ page }) => {
    const resp = await page.goto(`${BASE}/search-ajax.php?q=${SQLI_ENC}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Harus JSON array atau object
    expect(body).toMatch(/^\[|^\{/);
  });

  test('flights search from: tidak crash', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=${SQLI_ENC}&to=Denpasar&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('ferries search from: tidak crash', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=${SQLI_ENC}&to=Bakauheni&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('track code: tidak crash, tidak bocor booking', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=${SQLI_ENC}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tidak menampilkan data booking siapapun
    expect(body).not.toMatch(/TAT-FIX|confirmed|pending|Budi|Siti|Andi/i);
    // Form pencarian
    expect(body).toMatch(/Cari Booking|Masukkan kode booking/i);
  });

  test('tour-detail slug numeric: 404 wajar', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=999999999999999`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tour tidak ditemukan|tidak ditemukan|not found/i);
  });

  test('destinasi city sql injection: tidak crash, redirect atau empty', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=${SQLI_ENC}`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});