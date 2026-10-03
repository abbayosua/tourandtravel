import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: form akun (profile, my-profiles, reseller-topup) adalah form
 * POST tanpa guard anti dobel-submit. Sekarang memakai data-submit-once
 * (tombol dinonaktifkan + spinner). Handler global juga harus mengabaikan
 * submit yang dibatalkan (mis. konfirmasi hapus di-cancel) agar tombol tidak
 * ikut terkunci.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const PASS = 'tmpcheck123';
const USER_EMAIL = 'e2e-forms@t.local';
const RESELLER_EMAIL = 'e2e-forms-reseller@t.local';
const BOOKING_CODE = 'E2EFRM01';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

function hash(pw: string): string {
  return execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(pw)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E Forms','${USER_EMAIL}','${hash(PASS)}','user') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='user'`
  );
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E Reseller','${RESELLER_EMAIL}','${hash(PASS)}','reseller') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='reseller'`
  );
  const uid = mysql(`SELECT id FROM users WHERE email='${USER_EMAIL}'`);
  mysql(`DELETE FROM passenger_profiles WHERE user_id=${uid}`);
  mysql(`INSERT INTO passenger_profiles (user_id, full_name, is_default) VALUES (${uid}, 'E2E Passenger', 0)`);

  // Booking terkonfirmasi (untuk modal refund/modify) + 1 price alert.
  const ids = mysql(
    `SELECT t.id, td.id FROM tours t JOIN tour_dates td ON td.tour_id=t.id ` +
      `WHERE t.is_active=1 AND td.departure_date>=CURDATE() ORDER BY td.departure_date LIMIT 1`
  ).split('\t');
  mysql(`DELETE FROM bookings WHERE booking_code='${BOOKING_CODE}'`);
  mysql(
    `INSERT INTO bookings (booking_code,user_id,tour_id,tour_date_id,name,email,phone,participants,total_price,status,payment_status) ` +
      `VALUES ('${BOOKING_CODE}',${uid},${ids[0]},${ids[1]},'E2E Forms','${USER_EMAIL}','08123456789',1,100000,'confirmed','unpaid')`
  );
  mysql(`DELETE FROM price_alerts WHERE user_id=${uid}`);
  mysql(`INSERT INTO price_alerts (user_id, item_type, item_id, target_price) VALUES (${uid}, 'tour', ${ids[0]}, 500000)`);
});

test.afterAll(() => {
  const uid = mysql(`SELECT id FROM users WHERE email='${USER_EMAIL}'`);
  if (uid) mysql(`DELETE FROM passenger_profiles WHERE user_id=${uid}`);
  if (uid) mysql(`DELETE FROM price_alerts WHERE user_id=${uid}`);
  mysql(`DELETE FROM bookings WHERE booking_code='${BOOKING_CODE}'`);
  mysql(`DELETE FROM users WHERE email IN ('${USER_EMAIL}','${RESELLER_EMAIL}')`);
});

async function login(page: Page, email: string) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.click('form[data-submit-once] button[type="submit"]'),
  ]);
  await page.waitForLoadState('domcontentloaded');
}

test('form akun memakai guard anti dobel-submit', async ({ page }) => {
  await login(page, USER_EMAIL);
  await page.goto(`${BASE}/profile.php`);
  await expect(page.locator('form[data-submit-once]')).toHaveCount(1);
  await page.goto(`${BASE}/my-profiles.php`);
  await expect(page.locator('form[data-submit-once]')).toHaveCount(3);

  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator('form[data-submit-once]')).toHaveCount(2);
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await expect(page.locator('form[data-testid="redeem-form"][data-submit-once]')).toHaveCount(1);
  await page.goto(`${BASE}/my-alerts.php`);
  await expect(page.locator('form[data-submit-once]')).toHaveCount(1);

  await login(page, RESELLER_EMAIL);
  await page.goto(`${BASE}/reseller-topup.php`);
  await expect(page.locator('form[data-submit-once]')).toHaveCount(1);
});

test('simpan profil menampilkan loading & mengunci tombol', async ({ page }) => {
  await login(page, USER_EMAIL);
  await page.goto(`${BASE}/my-profiles.php`);

  const btn = page.locator('form[data-submit-once]').first().locator('button[type="submit"]');
  await page.evaluate(() => {
    const f = document.querySelector('form[data-submit-once]')!;
    f.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
  });

  await expect(btn).toBeDisabled();
  await expect(btn.locator('.spinner-border')).toHaveCount(1);
  await expect(btn).toHaveScreenshot('account-save-loading.png', { maxDiffPixelRatio: 0.15 });
});

test('batal konfirmasi hapus tidak mengunci tombol', async ({ page }) => {
  await login(page, USER_EMAIL);
  await page.goto(`${BASE}/my-profiles.php`);

  const delBtn = page.locator('form[onsubmit] button[type="submit"]').first();
  page.on('dialog', (d) => d.dismiss());
  await delBtn.click();

  // Konfirmasi dibatalkan → submit tidak jadi → tombol tetap aktif.
  await expect(delBtn).toBeEnabled();
});
