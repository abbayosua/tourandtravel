import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi: track.php menampilkan rekening + konfirmasi pembayaran untuk booking
 * manual (pending tanpa payment gateway). Booking non-pending tidak menampilkannya.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const CODE_PENDING = 'E2ETRKM1';
const CODE_CONFIRMED = 'E2ETRKC1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let prevSettings: Record<string, string> = {};
const SETTING_KEYS = ['manual_bank_name', 'manual_bank_number', 'manual_bank_holder'];

function insertBooking(code: string, status: string): void {
  const tourDateId = mysql(`SELECT id FROM tour_dates WHERE tour_id = 148 ORDER BY departure_date ASC LIMIT 1`);
  mysql(
    `INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
      `VALUES ('${code}', 148, ${tourDateId}, 'E2E Track Buyer', 'e2e@t.local', '0800000000', 1, 250000, '${status}')`
  );
}

test.beforeAll(() => {
  for (const k of SETTING_KEYS) {
    prevSettings[k] = mysql(`SELECT setting_value FROM settings WHERE setting_key = '${k}'`);
  }
  const upsert = (k: string, v: string) =>
    mysql(`INSERT INTO settings (setting_key, setting_value) VALUES ('${k}', '${v}') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`);
  upsert('manual_bank_name', 'Bank Test E2E');
  upsert('manual_bank_number', '999888777');
  upsert('manual_bank_holder', 'PT E2E');

  mysql(`DELETE FROM bookings WHERE booking_code IN ('${CODE_PENDING}', '${CODE_CONFIRMED}')`);
  insertBooking(CODE_PENDING, 'pending');
  insertBooking(CODE_CONFIRMED, 'confirmed');
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code IN ('${CODE_PENDING}', '${CODE_CONFIRMED}')`);
  for (const k of SETTING_KEYS) {
    mysql(`UPDATE settings SET setting_value = '${prevSettings[k] ?? ''}' WHERE setting_key = '${k}'`);
  }
});

test('booking manual pending menampilkan rekening + konfirmasi WhatsApp', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=${CODE_PENDING}&lang=id`);

  const block = page.locator('[data-testid="track-manual-payment"]');
  await expect(block).toBeVisible();
  await expect(block).toContainText('Bank Test E2E');
  await expect(block).toContainText('999888777');
  await expect(block).toContainText('Rp 250.000');

  const wa = page.locator('[data-testid="track-wa-confirm"]');
  await expect(wa).toBeVisible();
  const href = await wa.getAttribute('href');
  expect(href).toContain('https://wa.me/');
  expect(decodeURIComponent(href || '')).toContain(CODE_PENDING);
  expect(wa).toHaveAttribute('target', '_blank');
});

test('booking non-pending tidak menampilkan instruksi transfer', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=${CODE_CONFIRMED}&lang=id`);
  await expect(page.locator('[data-testid="track-manual-payment"]')).toHaveCount(0);
});

test('instruksi transfer mengikuti bahasa (EN)', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=${CODE_PENDING}&lang=en`);
  const block = page.locator('[data-testid="track-manual-payment"]');
  await expect(block).toBeVisible();
  await expect(block).toContainText('Payment Instructions');
  await expect(block).toContainText('Bank Test E2E');
});
