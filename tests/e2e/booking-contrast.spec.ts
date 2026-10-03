import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX (kontras): teks kecil putih-50% di atas latar berwarna pekat
 * (kartu biru kode booking, banner promo tiket) sulit dibaca — rasio kontras
 * ~2:1. Sekarang putih penuh (dan gradient banner digelapkan) agar >=4.5:1.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const CODE = 'ETSTC1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
  const ids = mysql(
    `SELECT t.id, td.id FROM tours t JOIN tour_dates td ON td.tour_id=t.id ` +
      `WHERE t.is_active=1 AND td.departure_date>=CURDATE() ORDER BY td.departure_date LIMIT 1`
  ).split('\t');
  mysql(
    `INSERT INTO bookings (booking_code, user_id, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
      `VALUES ('${CODE}', NULL, ${ids[0]}, ${ids[1]}, 'E2E Contrast', 'e2e@t.local', '08123456789', 1, 100000, 'pending')`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
});

test('label kode booking kontras di kartu biru', async ({ page }) => {
  await page.goto(`${BASE}/booking-success.php?code=${CODE}&lang=id`);

  const label = page.locator('.klook-booking-code small').first();
  await expect(label).toContainText('Kode Booking');
  expect(await label.evaluate((el) => getComputedStyle(el).color)).toBe('rgb(255, 255, 255)');

  await expect(page.locator('.klook-booking-code')).toHaveScreenshot('booking-code-contrast.png', {
    maxDiffPixelRatio: 0.1,
  });
});

test('subjudul banner promo tiket kontras di gradient', async ({ page }) => {
  // Banner promo tiket hanya dirender pada preset beranda "flight".
  const prev = mysql("SELECT setting_value FROM settings WHERE setting_key='site_focus'") || 'tour';
  mysql("UPDATE settings SET setting_value='flight' WHERE setting_key='site_focus'");
  try {
    await page.goto(`${BASE}/index.php?lang=id`);

    const banner = page.locator('[data-testid="flight-promo-banner"]');
    await banner.scrollIntoViewIfNeeded();
    const sub = banner.locator('p');
    await expect(sub).toContainText('kuota terbatas');
    expect(await sub.evaluate((el) => getComputedStyle(el).color)).toBe('rgb(255, 255, 255)');

    await expect(banner).toHaveScreenshot('flight-promo-contrast.png', { maxDiffPixelRatio: 0.1 });
  } finally {
    mysql(`UPDATE settings SET setting_value='${prev}' WHERE setting_key='site_focus'`);
  }
});
