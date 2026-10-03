import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: booking hotel memakai pola POST/Redirect/GET.
 *
 * Bug yang dijaga: dulu setelah POST sukses halaman di-render ulang, sehingga
 * refresh memicu re-submit (muncul error "tanggal sudah dibooking" setelah
 * booking yang berhasil). Sekarang POST sukses -> redirect ke GET, pesan
 * sukses ditampilkan sekali dari session.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const CI = '2027-07-01';
const CO = '2027-07-03';
const NAME = 'E2E PRG';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

const count = () =>
  mysql(`SELECT COUNT(*) FROM hotel_bookings WHERE hotel_id = 1 AND checkin = '${CI}' AND name = '${NAME}'`);
const cleanup = () => {
  try {
    mysql(`DELETE FROM hotel_bookings WHERE hotel_id = 1 AND checkin = '${CI}' AND name = '${NAME}'`);
  } catch {
    /* ignore */
  }
};

test.afterAll(cleanup);

test('booking hotel redirect ke GET dan refresh tidak re-submit', async ({ page }) => {
  cleanup();
  await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&checkin=${CI}&checkout=${CO}&lang=id`);
  await page.locator('#hotelBookingName').fill(NAME);
  await page.locator('#hotelBookingPhone').fill('08123456789');

  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.locator('#bookingSubmitBtn').click(),
  ]);

  // Redirect (PRG) dan pesan sukses tampil sekali.
  await expect(page).toHaveURL(/booking=success/);
  await expect(page.locator('.alert-success')).toBeVisible();
  expect(count()).toBe('1');

  await expect(page.locator('.alert-success')).toHaveScreenshot('hotel-booking-success.png', {
    maxDiffPixelRatio: 0.1,
  });

  // Refresh pada halaman sukses tidak boleh membuat booking kedua / error.
  await page.reload();
  await expect(page.locator('.alert-success')).toHaveCount(0);
  await expect(page.locator('.alert-danger')).toHaveCount(0);
  expect(count()).toBe('1');
});
