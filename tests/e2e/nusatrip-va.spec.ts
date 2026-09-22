import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

test.describe('NusaTrip VA booking - full cycle', () => {

  test('search Batam tampil hasil NusaTrip', async ({ page }) => {
    test.setTimeout(120000);
    const ci = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    const co = new Date(Date.now() + 2 * 86400000).toISOString().slice(0, 10);
    await page.goto(`${BASE}/hotels.php?city=Batam&checkin=${ci}&checkout=${co}&guests=2`, { waitUntil: 'load', timeout: 90000 });
    await expect(page.locator('#hotelContent')).toBeVisible({ timeout: 90000 });
    const body = await page.textContent('#hotelContent');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/ditemukan|found/i);
    expect(body).toMatch(/NusaTrip|Harga live/i);
  });

  test('detail NusaTrip tampil Pilih Kamar + Pesan Sekarang', async ({ page }) => {
    test.setTimeout(120000);
    const ci = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    const co = new Date(Date.now() + 2 * 86400000).toISOString().slice(0, 10);
    const hid = 'd8190694c07f7585';
    await page.goto(`${BASE}/hotel-detail.php?live=1&src=nusatrip&city=Batam&id=${hid}&checkin=${ci}&checkout=${co}&guests=2`, { waitUntil: 'load', timeout: 90000 });
    await expect(page.locator('text=/Pilih Kamar/i').first()).toBeVisible({ timeout: 90000 });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Pilih Kamar/i);
    expect(body).toMatch(/Pesan Sekarang|Order Now|Pesan/i);
  });

  test('booking VA BCA sampai nomor VA muncul', async ({ page }) => {
    const ci = new Date(Date.now() + 86400000).toISOString().slice(0, 10);
    const co = new Date(Date.now() + 2 * 86400000).toISOString().slice(0, 10);
    const hid = 'd8190694c07f7585';
    test.setTimeout(180000);
    await page.goto(`${BASE}/nusatrip-book.php?hotel_id=${hid}&checkin=${ci}&checkout=${co}&guests=1&city=Batam&room_idx=0`, { waitUntil: 'load', timeout: 90000 });
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Data Tamu/i);
    await page.fill('input[name="first_name"]', 'Angga');
    await page.fill('input[name="last_name"]', 'Saputra');
    await page.fill('input[name="email"]', 'motivasihiduptt@gmail.com');
    await page.fill('input[name="phone"]', '62 8517488415');
    const vaRadio = page.locator('#pm16');
    await expect(vaRadio).toHaveCount(1, { timeout: 30000 });
    await vaRadio.check();
    await page.click('button:has-text("Bayar Sekarang")');
    await page.waitForURL(/step=result/, { timeout: 60000 });
    await expect(page.locator('text=Menunggu Pembayaran')).toBeVisible({ timeout: 120000 });
    body = await page.textContent('body');
    expect(body).toMatch(/Virtual Account/i);
    expect(body).toMatch(/\d{15,25}/);
  });
});
