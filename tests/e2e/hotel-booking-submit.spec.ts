import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: tombol "Pesan Sekarang" di hotel-detail.php harus menampilkan
 * state loading (disabled + spinner) saat form booking dikirim, supaya tidak
 * double-submit / double-booking.
 *
 * Submit dipicu dengan requestSubmit() sambil mencegah navigasi (capture-phase
 * preventDefault) agar state tombol bisa diperiksa secara deterministik.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const HOTEL = 'grand-hyatt-bali';

test('submit booking hotel menampilkan loading dan mencegah double-submit', async ({ page }) => {
  await page.goto(`${BASE}/hotel-detail.php?slug=${HOTEL}&lang=id`);
  await page.locator('#hotelBookingName').fill('E2E Tester');
  await page.locator('#hotelBookingPhone').fill('08123456789');
  await page.locator('#roomSelect').selectOption({ index: 1 });

  const btn = page.locator('#bookingSubmitBtn');
  await expect(btn).toBeEnabled();

  const state = await page.evaluate(() => {
    const form = document.getElementById('hotelBookingForm') as HTMLFormElement;
    // Cegah navigasi agar bisa memeriksa state tombol setelah submit.
    form.addEventListener('submit', (e) => e.preventDefault(), { capture: true, once: true });
    form.requestSubmit();
    const b = document.getElementById('bookingSubmitBtn') as HTMLButtonElement;
    return { disabled: b.disabled, text: (b.textContent || '').trim() };
  });

  expect(state.disabled).toBe(true);
  expect(state.text).toContain('Memproses');

  await expect(btn).toBeDisabled();
  await expect(btn).toHaveScreenshot('hotel-booking-submit-loading.png', {
    maxDiffPixelRatio: 0.1,
  });
});
