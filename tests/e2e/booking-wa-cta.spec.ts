import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: booking-success.php (mode pembayaran manual) dulu hanya
 * menulis "Kami akan menghubungi Anda via WhatsApp" tanpa memberi cara
 * menghubungi — buntu. Sekarang ada tombol "Hubungi Kami" (wa.me) dengan kode
 * booking terisi otomatis.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const CODE = 'E2EWA01';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  const ids = mysql(
    `SELECT t.id, td.id FROM tours t JOIN tour_dates td ON td.tour_id=t.id ` +
      `WHERE t.is_active=1 AND td.departure_date>=CURDATE() ORDER BY td.departure_date LIMIT 1`
  ).split('\t');
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
  mysql(
    `INSERT INTO bookings (booking_code,tour_id,tour_date_id,name,email,phone,participants,total_price,status,payment_status) ` +
      `VALUES ('${CODE}',${ids[0]},${ids[1]},'E2E WA','e2e-wa@t.local','08123456789',1,100000,'pending','unpaid')`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
});

test('booking pending manual punya tombol hubungi WhatsApp', async ({ page }) => {
  await page.goto(`${BASE}/booking-success.php?code=${CODE}&lang=id`);

  const wa = page.locator('[data-testid="wa-contact"]');
  await expect(wa).toBeVisible();
  const href = await wa.getAttribute('href');
  expect(href).toContain('https://wa.me/6281234567890');
  expect(href).toContain(encodeURIComponent(`Booking ${CODE}`));

  await expect(page.locator('.d-flex.gap-2.justify-content-center').first()).toHaveScreenshot('booking-wa-cta.png', {
    maxDiffPixelRatio: 0.15,
  });
});
