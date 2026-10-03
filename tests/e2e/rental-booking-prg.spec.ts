import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: booking rental mobil memakai pola POST/Redirect/GET.
 *
 * Bug yang dijaga: dulu POST sukses langsung render ulang halaman, sehingga
 * refresh mengirim ulang form (double wallet deduct). Sekarang POST sukses ->
 * redirect ke GET, pesan sukses ditampilkan sekali dari session.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const SLUG = 'toyota-avanza-jakarta';

test('booking rental mobil redirect ke GET dan refresh tidak re-submit', async ({ page }) => {
  await page.goto(`${BASE}/rental-car-detail.php?slug=${SLUG}&lang=id`);
  await page.locator('input[name="days"]').fill('2');
  await page.locator('input[name="name"]').fill('E2E Rental');
  await page.locator('input[name="phone"]').fill('08123456789');

  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.locator('form[data-submit-once] button[type="submit"]').click(),
  ]);

  await expect(page).toHaveURL(/booking=success/);
  await expect(page.locator('.alert-success')).toContainText('Booking berhasil');
  await expect(page.locator('.alert-success')).toHaveScreenshot('rental-booking-success.png', {
    maxDiffPixelRatio: 0.1,
  });

  // Refresh tidak menampilkan pesan lagi (flash sekali pakai) dan tanpa error.
  await page.reload();
  await expect(page.locator('.alert-success')).toHaveCount(0);
});
