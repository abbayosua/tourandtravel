import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: daftar booking admin dulu hanya punya filter status/tipe —
 * tidak ada pencarian teks, sehingga sulit menemukan booking tertentu. Sekarang
 * ada kotak pencarian (semua kolom) + empty state saat tak ada hasil.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const CODE = 'E2EBKSRCH';
const MARKER = 'Zzsearchmarker';

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
      `VALUES ('${CODE}',${ids[0]},${ids[1]},'${MARKER}','e2e-bksearch@t.local','08123456789',1,100000,'pending','unpaid')`
  );
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

test('pencarian booking admin memfilter dan menampilkan empty state', async ({ page }) => {
  await adminLogin(page);

  await page.goto(`${BASE}/admin/bookings.php?lang=id`);
  await expect(page.locator('[data-testid="admin-list-search"]')).toBeVisible();

  // Cocok → baris tampil.
  await page.goto(`${BASE}/admin/bookings.php?q=${MARKER}&lang=id`);
  await expect(page.locator('table tbody')).toContainText(MARKER);
  await expect(page.locator('table tbody')).not.toContainText('Belum ada booking');

  // Tidak cocok → empty state.
  await page.goto(`${BASE}/admin/bookings.php?q=zzznomatchxyz&lang=id`);
  await expect(page.locator('table tbody')).toContainText('Belum ada booking');
  await expect(page.locator('table tbody')).toHaveScreenshot('admin-bookings-empty.png', { maxDiffPixelRatio: 0.15 });
});
