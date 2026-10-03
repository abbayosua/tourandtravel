import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: tautan ubah status booking di admin/bookings.php dulu
 * langsung mengubah status (dan mengirim notifikasi/email ke customer, serta
 * membatalkan/refund bila Cancelled) tanpa konfirmasi — mudah salah klik.
 * Sekarang ada dialog konfirmasi.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const CODE = 'E2ESTATUS1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let bookingId = '';

test.beforeAll(() => {
  const ids = mysql(
    `SELECT t.id, td.id FROM tours t JOIN tour_dates td ON td.tour_id=t.id ` +
      `WHERE t.is_active=1 AND td.departure_date>=CURDATE() ORDER BY td.departure_date LIMIT 1`
  ).split('\t');
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
  mysql(
    `INSERT INTO bookings (booking_code,tour_id,tour_date_id,name,email,phone,participants,total_price,status,payment_status) ` +
      `VALUES ('${CODE}',${ids[0]},${ids[1]},'E2E Status','e2e-status@t.local','08123456789',1,100000,'pending','unpaid')`
  );
  bookingId = mysql(`SELECT id FROM bookings WHERE booking_code='${CODE}'`);
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code='${CODE}'`);
});

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('ubah status booking meminta konfirmasi', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/bookings.php?status=pending&type=tour`);

  const link = page.locator(`a[href*="update_status=${bookingId}"][href*="status=confirmed"]`);
  await expect(link).toHaveCount(1);
  // Buka dropdown baris ini agar tautannya terlihat.
  await link.evaluate((el) => el.closest('.dropdown-menu')?.classList.add('show'));
  await expect(link).toBeVisible();

  // Dismiss → status tetap pending.
  page.once('dialog', (d) => d.dismiss());
  await link.click();
  await page.waitForTimeout(400);
  expect(mysql(`SELECT status FROM bookings WHERE id=${bookingId}`)).toBe('pending');

  // Accept → status berubah ke confirmed.
  page.once('dialog', (d) => d.accept());
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    link.click(),
  ]);
  await page.waitForTimeout(500);
  expect(mysql(`SELECT status FROM bookings WHERE id=${bookingId}`)).toBe('confirmed');
});
