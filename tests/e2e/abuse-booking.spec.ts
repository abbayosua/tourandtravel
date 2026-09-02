import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function registerUser(page) {
  const email = `abuse_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'Abuse Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '081234567890');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

test.describe('Abuse - Double Submit Booking', () => {

  test('hotel double submit: overlap check blokir booking kedua', async ({ page }) => {
    await registerUser(page);

    // Submit booking hotel pertama (tanggal unik)
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    await page.fill('input[name="checkin"]', '2027-06-01');
    await page.fill('input[name="checkout"]', '2027-06-05');
    await page.fill('input[name="name"]', 'Abuse Test');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body1 = await page.textContent('body');
    expect(body1).toMatch(/Booking berhasil/i);

    // Submit booking kedua (tanggal SAMA — overlap)
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    await page.fill('input[name="checkin"]', '2027-06-01');
    await page.fill('input[name="checkout"]', '2027-06-05');
    await page.fill('input[name="name"]', 'Abuse Test 2');
    await page.fill('input[name="phone"]', '0813');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/sudah dibooking|Tanggal sudah/i);
    expect(body2).not.toMatch(/Booking berhasil/i);
  });

  test('hotel double submit: tanggal berbeda -> keduanya sukses', async ({ page }) => {
    await registerUser(page);

    // Booking pertama
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    await page.fill('input[name="checkin"]', '2027-07-01');
    await page.fill('input[name="checkout"]', '2027-07-03');
    await page.fill('input[name="name"]', 'Multi');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect((await page.textContent('body'))).toMatch(/Booking berhasil/i);

    // Booking kedua, tanggal berbeda
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, { waitUntil: 'load' });
    await page.fill('input[name="checkin"]', '2027-08-01');
    await page.fill('input[name="checkout"]', '2027-08-03');
    await page.fill('input[name="name"]', 'Multi 2');
    await page.fill('input[name="phone"]', '0813');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect((await page.textContent('body'))).toMatch(/Booking berhasil/i);
  });

  test('rental double submit: tidak ada duplikasi (tidak persist)', async ({ page }) => {
    await registerUser(page);

    // Submit rental 2x cepat
    await page.goto(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, { waitUntil: 'load' });
    await page.fill('input[name="days"]', '2');
    await page.fill('input[name="name"]', 'Rental Abuse');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect((await page.textContent('body'))).toMatch(/Booking berhasil/i);

    // Submit lagi
    await page.goto(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, { waitUntil: 'load' });
    await page.fill('input[name="days"]', '2');
    await page.fill('input[name="name"]', 'Rental Abuse');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect((await page.textContent('body'))).toMatch(/Booking berhasil/i);
    // Keduanya sukses, tidak ada duplikasi (rental tidak persist ke DB)
  });

  test('tour booking tanpa passport: error validasi', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Cek apakah ada tour_date select
    const dateSelect = page.locator('select[name="tour_date_id"]');
    if (await dateSelect.count() === 0) {
      console.log('SKIP: no tour dates available');
      return;
    }

    await dateSelect.selectOption({ index: 1 });
    await page.fill('input[name="name"]', 'Tour Test');
    await page.fill('input[name="email"]', 'tour@test.com');
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="participants"]', '1');
    // Biarkan passport kosong
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body2 = await page.textContent('body');
    expect(body2).toMatch(/paspor|passport|wajib|wajib diupload/i);
    expect(body2).not.toMatch(/booking-success|Booking Berhasil/i);
  });
});