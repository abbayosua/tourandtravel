import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';
import { existsSync, readdirSync } from 'fs';
import { join } from 'path';

/**
 * Regresi alur pembelian paket tour (mission: pastikan Pembelian Paket Tour usable).
 *
 * Menjalankan pembelian end-to-end dengan mode pembayaran MANUAL:
 * login -> buka tour-detail -> upload paspor peserta -> isi data -> submit ->
 * booking-success.php. Memastikan booking + baris peserta benar-benar tersimpan
 * di database dan muncul di my-bookings, lalu membersihkan datanya.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-tourbuy@t.local';
const AUTH_PASS = 'e2epass123';
const SLUG = 'e2e-tour-purchase';
const DATE = '2027-12-01';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

function passportFixture(): string {
  const dir = join(process.cwd(), 'uploads', 'passports');
  if (!existsSync(dir)) throw new Error('uploads/passports tidak ada');
  const file = readdirSync(dir).find((f) => f.endsWith('.webp'));
  if (!file) throw new Error('tidak ada fixture paspor .webp');
  return join(dir, file);
}

let userId = 0;
let tourId = 0;

function cleanup() {
  // Bersihkan sisa run sebelumnya (by slug) lalu by id.
  const ids = mysql(`SELECT id FROM tours WHERE slug = '${SLUG}'`).split('\n').filter(Boolean);
  const targets = Array.from(new Set([...ids, ...(tourId ? [String(tourId)] : [])]));
  for (const id of targets) {
    mysql(`DELETE b FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.tour_id = ${id}`);
    mysql(`DELETE FROM bookings WHERE tour_id = ${id}`);
    mysql(`DELETE FROM tour_dates WHERE tour_id = ${id}`);
    mysql(`DELETE FROM tours WHERE id = ${id}`);
  }
  tourId = 0;
  if (userId) {
    mysql(`DELETE FROM wallet_transactions WHERE user_id = ${userId}`);
    mysql(`DELETE FROM points_ledger WHERE user_id = ${userId}`);
    try {
      mysql(`DELETE FROM users WHERE id = ${userId}`);
    } catch {
      /* masih direferensikan */
    }
  }
}

test.beforeAll(() => {
  cleanup();
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(AUTH_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();
  mysql(
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E TourBuy', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));

  mysql(
    `INSERT INTO tours (title, slug, category, price, price_currency, content_language, max_participants, is_active) ` +
      `VALUES ('E2E Tour Purchase', '${SLUG}', 'City Tour', 1500000, 'IDR', 'id', 10, 1)`
  );
  tourId = Number(mysql(`SELECT id FROM tours WHERE slug = '${SLUG}'`));
  mysql(
    `INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) ` +
      `VALUES (${tourId}, '${DATE}', '${DATE}', 10, 0, 1)`
  );
});

test.afterAll(cleanup);

async function login(page: Page) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', AUTH_EMAIL);
  await page.fill('input[name="password"]', AUTH_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('pembelian paket tour berhasil dan tersimpan (mode manual)', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);

  await expect(page.locator('#tourBookingForm')).toBeVisible();
  // Harga + tombol booking tampil (form usable).
  await expect(page.locator('#bookingSubmitBtn')).toBeVisible();

  // Upload foto paspor peserta #1 lewat modal multi-peserta.
  await page.locator('[data-testid="open-pax-modal"]').click();
  await expect(page.locator('[data-testid="pax-rows"]')).toBeVisible();
  await page.locator('input.pax-file[data-idx="1"]').setInputFiles(passportFixture());
  await expect(page.locator('#passportFile1')).not.toHaveValue('', { timeout: 10000 });
  await page.locator('[data-testid="pax-done"]').click();

  // Isi data pemesan.
  await page.locator('#tourDateSelect').selectOption({ index: 1 });
  await page.locator('#bookingName').fill('Buyer Tour');
  await page.locator('#bookingPhone').fill('081234567890');

  await Promise.all([
    page.waitForURL(/booking-success\.php\?code=/, { timeout: 15000 }),
    page.locator('#bookingSubmitBtn').click(),
  ]);

  await expect(page.locator('.klook-booking-code')).toBeVisible();
  await expect(page.getByText('Booking Berhasil!')).toBeVisible();

  const code = (page.url().match(/code=([^&]+)/)?.[1] ?? '').trim();
  expect(code).not.toBe('');

  // Booking benar-benar tersimpan dengan status pending (mode manual), milik user.
  const row = mysql(
    `SELECT status, participants, total_price, user_id FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`
  );
  expect(row).toContain('pending');
  expect(row).toContain('1');
  expect(row).toContain(String(userId));
  const participantCount = mysql(
    `SELECT COUNT(*) FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.booking_code = '${code}'`
  );
  expect(participantCount).toBe('1');

  // Booking muncul di halaman "Booking Saya".
  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});
