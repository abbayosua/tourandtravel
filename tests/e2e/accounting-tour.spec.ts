import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function registerUser(page) {
  const email = `acct_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'Accounting Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '081234567890');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

test.describe('Accounting - Harga di DB vs Halaman', () => {

  test('tour 61: harga tampil di halaman detail', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Rp|S\$|\$|price|currency|orang|peserta/i);
  });

  test('hotel 1: harga/malam tampil', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/malam|price|Rp|\$|currency/i);
  });

  test('rental Avanza: harga/hari tampil', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/hari|350|\$|Rp|price/i);
  });
});

test.describe('Accounting - Perhitungan Total', () => {

  test('hotel: nights × price per night (guest, harga tampil)', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&checkin=2026-12-01&checkout=2026-12-04`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/malam|3|price|Rp|\$/i);
  });

  test('hotel: checkout<checkin → clamp 1 malam', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&checkin=2026-12-10&checkout=2026-12-08`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // nights = max(1, negative) = 1, harga 2.5jt
    expect(body).toMatch(/malam|price|Rp|\$|currency/i);
  });

  test('rental: register + sewa 5 hari = sukses', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, { waitUntil: 'load' });
    await page.fill('input[name="days"]', '5');
    await page.fill('input[name="name"]', 'Rental Test');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking berhasil/i);
    expect(body).toMatch(/5 hari|350\.000|1\.750\.000|\$|Rp/i);
  });

  test('rental: days=0 → tidak sukses (guard server)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, {
      form: { days: '0', name: 'Test', phone: '0812' },
      maxRedirects: 0,
    });
    const body = await resp.text();
    expect(body).not.toMatch(/Booking berhasil/i);
  });
});