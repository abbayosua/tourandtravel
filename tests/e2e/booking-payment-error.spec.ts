import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: tombol "Bayar Sekarang" di booking-success dulu, saat request
 * create-payment gagal (network error), hanya me-reset tombol TANPA pesan error
 * di statusArea — user tak tahu kenapa. Sekarang menampilkan pesan error.
 *
 * payNowBtn hanya dirender saat mode pembayaran "instant", jadi test menyalakan
 * sementara (payment_mode=instant + midtrans_server_key dummy) lalu memulihkannya.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const CODE = 'E2EPAY01';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}
function getSetting(k: string): string {
  return mysql(`SELECT setting_value FROM settings WHERE setting_key='${k}'`);
}
function setSetting(k: string, v: string) {
  mysql(`UPDATE settings SET setting_value='${v}' WHERE setting_key='${k}'`);
}

test.beforeAll(() => {
  const ids = mysql(
    `SELECT t.id, td.id FROM tours t JOIN tour_dates td ON td.tour_id=t.id ` +
      `WHERE t.is_active=1 AND td.departure_date>=CURDATE() ORDER BY td.departure_date LIMIT 1`
  ).split('\t');
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
  mysql(
    `INSERT INTO bookings (booking_code,tour_id,tour_date_id,name,email,phone,participants,total_price,status,payment_status) ` +
      `VALUES ('${CODE}',${ids[0]},${ids[1]},'E2E Pay','e2e-pay@t.local','08123456789',1,100000,'pending','unpaid')`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
});

test('gagal memulai pembayaran menampilkan pesan error', async ({ page }) => {
  const prevMode = getSetting('payment_mode') || 'manual';
  const prevKey = getSetting('midtrans_server_key');
  setSetting('payment_mode', 'instant');
  setSetting('midtrans_server_key', 'e2e-dummy');
  try {
    await page.route('**/ajax/create-payment.php*', (route) => route.abort('failed'));
    await page.goto(`${BASE}/booking-success.php?code=${CODE}&lang=id`);

    const btn = page.locator('#payNowBtn');
    await expect(btn).toBeVisible();
    await btn.click();

    const statusArea = page.locator('#paymentStatusArea');
    await expect(statusArea).toContainText('Gagal memulai pembayaran');
    await expect(btn).toBeEnabled();

    await expect(statusArea).toHaveScreenshot('payment-error.png', { maxDiffPixelRatio: 0.15 });
  } finally {
    setSetting('payment_mode', prevMode);
    setSetting('midtrans_server_key', prevKey);
  }
});
