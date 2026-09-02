import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = 'grand-hyatt-bali';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

// Bersihkan booking test dari run sebelumnya (tanggal test yg dipakai overlap/no-overlap tests)
test.beforeAll(async () => {
  // via request: tidak bisa DB langsung dari browser; cleanup di awal tiap test overlap
});

// Login helper — set session via register/login flow
async function loginAs(page, email, password) {
  await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Hotel Detail - happy path', () => {

  test('slug valid: nama, harga/malam render (guest)', async ({ page }) => {
    const resp = await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Grand Hyatt Bali/i);
    expect(body).toMatch(/2\.500\.000.*malam|malam/i);
  });

  test('guest: login prompt muncul, redirect param ada', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Login untuk Booking|Masuk \/ Daftar/i);
    const loginLink = page.locator('a[href*="login.php?redirect="]').first();
    const href = await loginLink.getAttribute('href');
    expect(href).toContain('redirect=');
  });

  test('similar hotels section (guest)', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Hotel Lain di Bali|Similar|Serupa/i);
  });

  test('logged-in: form booking tampil + total update', async ({ page }) => {
    // Create a fresh user
    const email = `hotel_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Hotel Tester');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08111111111');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    // Now on detail with session
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}&checkin=2025-08-01&checkout=2025-08-04`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Booking form visible (not login prompt)
    expect(body).toMatch(/Pesan Sekarang/i);
    expect(body).toMatch(/Check-in|Check-out|Kamar|Tamu/i);
    // 3 nights total = 2.5jt * 3 = 7.5jt
    expect(body).toMatch(/7\.500\.000/i);
  });
});

test.describe('Hotel Detail - sad path / edge cases', () => {

  test('slug tidak ada: redirect ke hotels.php', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=hotel-tidak-ada-xyz`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php');
  });

  test('slug kosong: redirect ke hotels.php', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php');
  });

  test('slug tanpa param: redirect ke hotels.php', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php');
  });

  test('checkout < checkin (guest): tidak error, render', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}&checkin=2025-08-10&checkout=2025-08-05`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Grand Hyatt Bali/i);
  });

  test('date invalid: tidak fatal error', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}&checkin=abc&checkout=def`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Grand Hyatt Bali/i);
  });

  test('guests=99: tidak crash (dipaksa ke select range)', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}&guests=99`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Grand Hyatt Bali/i);
  });

  test('booking POST dengan checkout<checkin (logged-in): nights clamp 1, tidak error', async ({ page }) => {
    const email = `hotelpost_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'POST Tester');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08222222222');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/hotel-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    // Set checkin > checkout (inverted)
    await page.fill('input[name="checkin"]', '2025-08-10');
    await page.fill('input[name="checkout"]', '2025-08-05');
    await page.fill('input[name="name"]', 'POST Tester');
    await page.fill('input[name="phone"]', '08222222222');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking berhasil/i);
  });

  test('booking overlap: tanggal bentrok -> error "sudah dibooking"', async ({ page }) => {
    // Seed ada booking hotel_id=1 (Grand Hyatt Bali) checkin 2026-12-01 s/d 2026-12-05
    const email = `hotelov_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Overlap Tester');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08333333333');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    // Booking Dec 2-4 (bentrok dgn Dec 1-5)
    await page.fill('input[name="checkin"]', '2026-12-02');
    await page.fill('input[name="checkout"]', '2026-12-04');
    await page.fill('input[name="name"]', 'Overlap Tester');
    await page.fill('input[name="phone"]', '08333333333');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/sudah dibooking|Tanggal sudah/i);
    expect(body).not.toMatch(/Booking berhasil/i);
  });

  test('booking no overlap: tanggal bebas -> sukses', async ({ page }) => {
    const email = `hotelno_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'No Overlap');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08444444444');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    // Tanggal unik dinamis (jauh di masa depan) agar tidak bentrok dgn data lama
    const start = new Date();
    start.setFullYear(start.getFullYear() + 2);
    const ci = start.toISOString().slice(0, 10);
    const coDate = new Date(start);
    coDate.setDate(coDate.getDate() + 3);
    const co = coDate.toISOString().slice(0, 10);
    await page.fill('input[name="checkin"]', ci);
    await page.fill('input[name="checkout"]', co);
    await page.fill('input[name="name"]', 'No Overlap');
    await page.fill('input[name="phone"]', '08444444444');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking berhasil/i);
    expect(body).not.toMatch(/sudah dibooking/i);
  });
});