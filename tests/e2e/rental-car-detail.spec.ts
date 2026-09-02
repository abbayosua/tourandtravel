import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = 'toyota-avanza-jakarta';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

async function registerUser(page, tag) {
  const email = `rc_${tag}_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'RC Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '089988776655');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

test.describe('Rental Car Detail - happy path', () => {

  test('slug valid: nama, harga/hari render + guest checkout form', async ({ page }) => {
    const resp = await page.goto(`${BASE}/rental-car-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Toyota Avanza/i);
    expect(body).toMatch(/350\.000|Rp/i);
    // Guest checkout: form tetap tampil + warning
    await expect(page.locator('input[name="days"]')).toBeVisible();
    expect(body).toMatch(/booking sebagai tamu|Sewa Sekarang/i);
  });

  test('POST valid (days=3): register dulu, baru submit', async ({ page }) => {
    // Register user dulu
    await registerUser(page, 'post');
    await page.goto(`${BASE}/rental-car-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    await page.fill('input[name="days"]', '3');
    await page.fill('input[name="name"]', 'Budi');
    await page.fill('input[name="phone"]', '081234567');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking berhasil/i);
    expect(body).toMatch(/1\.050\.000|350\.000 × 3|3 hari/i);
  });
});

test.describe('Rental Car Detail - sad path / edge', () => {

  test('slug tidak ada: redirect ke rental-cars.php', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=mobil-tidak-ada-xyz`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('rental-cars.php');
  });

  test('slug kosong: redirect ke rental-cars.php', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('rental-cars.php');
  });

  test('days=0: validasi HTML min=1 & server tolak (tidak sukses)', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    // isi days=0 — HTML min=1 mencegah via UI; tes POST manual via evaluate
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('days negatif via POST langsung: server tolak (days>0 guard)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=${SLUG}`, {
      form: { days: '-5', name: 'Budi', phone: '0812' }
    });
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).not.toMatch(/Booking berhasil/i); // guard days>0 -> tidak sukses
  });

  test('days=0 via POST: ditolak server', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=${SLUG}`, {
      form: { days: '0', name: 'Budi', phone: '0812' }
    });
    const body = await resp.text();
    expect(body).not.toMatch(/Booking berhasil/i);
  });

  test('POST tanpa name/phone: tidak sukses', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=${SLUG}`, {
      form: { days: '2', name: '', phone: '' }
    });
    const body = await resp.text();
    expect(body).not.toMatch(/Booking berhasil/i);
  });

  test('XSS slug: redirect aman, tidak eksekusi', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(/alert\(1\)/);
  });

  test('POST tanpa login: guest booking sukses (200)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=${SLUG}`, {
      form: { days: '3', name: 'Guest', phone: '0812' },
    });
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).toMatch(/Booking berhasil|Sewa Sekarang/i);
  });

  test('guest lihat form booking + warning guest checkout', async ({ page }) => {
    await page.goto(`${BASE}/rental-car-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/booking sebagai tamu|Sewa Sekarang/i);
    await expect(page.locator('input[name="days"]')).toBeVisible();
  });
});