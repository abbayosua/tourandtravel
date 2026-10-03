import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: booking hotel harus memilih tipe kamar dulu. Dulu select kamar
 * tidak `required`, jadi user bisa submit tanpa memilih kamar (booking dibuat
 * tanpa tipe kamar / harga dasar).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
// Tanggal dikunci agar nilai form (check-in/out & total) deterministik —
// sebelumnya default = hari ini sehingga baseline screenshot berubah tiap hari.
const CHECKIN = '2026-10-10';
const CHECKOUT = '2026-10-12';

test('booking hotel mewajibkan pilih tipe kamar', async ({ page }) => {
  await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=id&checkin=${CHECKIN}&checkout=${CHECKOUT}`);

  const sel = page.locator('#roomSelect');
  await expect(sel).toHaveAttribute('required', '');
  expect(await sel.evaluate((el) => (el as HTMLSelectElement).checkValidity())).toBe(false);

  // Klik Pesan Sekarang tanpa memilih kamar -> validasi memblokir (tidak navigasi).
  const urlBefore = page.url();
  await page.locator('#bookingSubmitBtn').click();
  await page.waitForTimeout(400);
  expect(page.url()).toBe(urlBefore);
  await expect(page.locator('#bookingSubmitBtn')).toBeEnabled();

  // Setelah memilih kamar -> valid.
  await sel.selectOption({ index: 1 });
  expect(await sel.evaluate((el) => (el as HTMLSelectElement).checkValidity())).toBe(true);

  await expect(page.locator('#hotelBookingForm')).toHaveScreenshot('hotel-room-required.png', {
    maxDiffPixelRatio: 0.15,
  });
});
