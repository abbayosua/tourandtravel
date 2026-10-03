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
 *
 * Cakupan: 1 peserta, multi-peserta (2), dan redeem points + wallet (TravelPoints).
 * Test redeem memakai user terpisah agar saldo wallet tidak terakumulasi dari
 * earn 5% booking sebelumnya.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-tourbuy@t.local';
const AUTH_EMAIL_REDEEM = 'e2e-tourbuy-redeem@t.local';
const AUTH_PASS = 'e2epass123';
const SLUG = 'e2e-tour-purchase';
const DATE = '2027-12-01';
const DATE2 = '2027-12-15';
const UNIT_PRICE = 1500000;

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
let redeemUserId = 0;
let cancelUserId = 0;
let modUserId = 0;
let tourId = 0;

function createUser(email: string): number {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(AUTH_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();
  mysql(
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E ${email}', '${email}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  return Number(mysql(`SELECT id FROM users WHERE email = '${email}'`));
}

function cleanup() {
  // Bersihkan sisa run sebelumnya (by slug) lalu by id.
  const ids = mysql(`SELECT id FROM tours WHERE slug = '${SLUG}'`).split('\n').filter(Boolean);
  const targets = Array.from(new Set([...ids, ...(tourId ? [String(tourId)] : [])]));
  if (targets.length > 0) {
    // Hapus waitlist & promo SEBELUM tour dihapus (masih butuh tour_id).
    mysql(`DELETE FROM tour_waitlist WHERE tour_id IN (${targets.join(',')})`);
    mysql(`DELETE FROM promo_codes WHERE code = 'E2E50'`);
    for (const id of targets) {
      mysql(`DELETE b FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.tour_id = ${id}`);
      mysql(`DELETE FROM bookings WHERE tour_id = ${id}`);
      mysql(`DELETE FROM tour_dates WHERE tour_id = ${id}`);
      mysql(`DELETE FROM tours WHERE id = ${id}`);
    }
  }
  tourId = 0;
  for (const uid of [userId, redeemUserId, cancelUserId, modUserId]) {
    if (!uid) continue;
    mysql(`DELETE FROM wallet_transactions WHERE user_id = ${uid}`);
    mysql(`DELETE FROM points_ledger WHERE user_id = ${uid}`);
    try {
      mysql(`DELETE FROM users WHERE id = ${uid}`);
    } catch {
      /* masih direferensikan */
    }
  }
  userId = 0;
  redeemUserId = 0;
  cancelUserId = 0;
  modUserId = 0;
}

test.beforeAll(() => {
  cleanup();
  // Pastikan tabel waitlist ada (migrasi migrate-tour-waitlist.sql).
  execFileSync('php', ['database/migrate-tour-waitlist.sql'], { stdio: 'ignore' });
  userId = createUser(AUTH_EMAIL);

  mysql(
    `INSERT INTO tours (title, slug, category, price, price_currency, content_language, max_participants, is_active) ` +
      `VALUES ('E2E Tour Purchase', '${SLUG}', 'City Tour', ${UNIT_PRICE}, 'IDR', 'id', 10, 1)`
  );
  tourId = Number(mysql(`SELECT id FROM tours WHERE slug = '${SLUG}'`));
  mysql(
    `INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) ` +
      `VALUES (${tourId}, '${DATE}', '${DATE}', 10, 0, 1)`
  );
  mysql(
    `INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) ` +
      `VALUES (${tourId}, '${DATE2}', '${DATE2}', 10, 0, 1)`
  );
});

test.afterAll(cleanup);

async function login(page: Page, email: string = AUTH_EMAIL) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', AUTH_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

interface BookOpts {
  participants: number;
  paxNames?: string[];
  usePoints?: boolean;
  useWallet?: boolean;
  promoCode?: string;
  insurance?: boolean;
}

/** Isi form booking tour lalu submit; return kode booking. */
async function bookTour(page: Page, opts: BookOpts): Promise<string> {
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await expect(page.locator('#tourBookingForm')).toBeVisible();
  await expect(page.locator('#bookingSubmitBtn')).toBeVisible();

  if (opts.participants > 1) {
    await page.locator('input[name="participants"]').fill(String(opts.participants));
  }

  // Upload foto paspor setiap peserta lewat modal multi-peserta.
  await page.locator('[data-testid="open-pax-modal"]').click();
  await expect(page.locator('[data-testid="pax-rows"]')).toBeVisible();
  for (let i = 1; i <= opts.participants; i++) {
    await page.locator(`input.pax-file[data-idx="${i}"]`).setInputFiles(passportFixture());
    await expect(page.locator(`#passportFile${i}`)).not.toHaveValue('', { timeout: 10000 });
    const name = opts.paxNames?.[i - 1];
    if (name) await page.locator(`input[name="pax_name_${i}"]`).fill(name);
  }
  await page.locator('[data-testid="pax-done"]').click();

  // Isi data pemesan.
  await page.locator('#tourDateSelect').selectOption({ index: 1 });
  await page.locator('#bookingName').fill('Buyer Tour');
  await page.locator('#bookingPhone').fill('081234567890');
  if (opts.usePoints) await page.locator('#usePointsTour').check();
  if (opts.useWallet) await page.locator('#useWalletTour').check();
  if (opts.promoCode) await page.locator('#promoCodeTour').fill(opts.promoCode);
  if (opts.insurance) await page.locator('#addInsuranceTour').check();

  await Promise.all([
    page.waitForURL(/booking-success\.php\?code=/, { timeout: 15000 }),
    page.locator('#bookingSubmitBtn').click(),
  ]);

  await expect(page.locator('.klook-booking-code')).toBeVisible();
  await expect(page.getByText('Booking Berhasil!')).toBeVisible();

  const code = (page.url().match(/code=([^&]+)/)?.[1] ?? '').trim();
  expect(code).not.toBe('');
  return code;
}

test('pembelian 1 peserta berhasil dan tersimpan (mode manual)', async ({ page }) => {
  await login(page);
  const code = await bookTour(page, { participants: 1 });

  const row = mysql(
    `SELECT status, participants, total_price, user_id FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`
  );
  expect(row).toContain('pending');
  expect(row).toContain('1');
  expect(row).toContain(String(userId));
  expect(row).toContain(String(UNIT_PRICE));
  expect(mysql(`SELECT COUNT(*) FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.booking_code = '${code}'`)).toBe('1');

  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});

test('booking sebagai tamu (tanpa login) berhasil', async ({ page }) => {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  const code = await bookTour(page, { participants: 1 });

  // user_id NULL (tamu), status pending, tanpa earn TravelPoints.
  const row = mysql(
    `SELECT user_id, status, total_price FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`
  );
  expect(row).toContain('pending');
  expect(row).toContain(String(UNIT_PRICE));
  expect(mysql(`SELECT COUNT(*) FROM wallet_transactions WHERE reference_type = 'tour_booking' AND reference_id = (SELECT id FROM bookings WHERE booking_code = '${code}')`)).toBe('0');

  // Kode booking tetap bisa dilacak.
  await page.goto(`${BASE}/track.php?code=${code}`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});

test('pembelian 2 peserta tersimpan dengan benar', async ({ page }) => {
  await login(page);
  const code = await bookTour(page, { participants: 2, paxNames: [undefined, 'Second Buyer'] });

  const row = mysql(
    `SELECT status, participants, total_price FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`
  );
  expect(row).toContain('pending');
  expect(row).toContain('2');
  expect(row).toContain(String(UNIT_PRICE * 2));

  const names = mysql(
    `SELECT full_name FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.booking_code = '${code}' ORDER BY b.id`
  );
  expect(names).toContain('Buyer Tour');
  expect(names).toContain('Second Buyer');

  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});

test('redeem points + wallet (TravelPoints) memotong total', async ({ page }) => {
  // User terpisah + seed saldo: 500 points dan Rp 500.000 wallet.
  redeemUserId = createUser(AUTH_EMAIL_REDEEM);
  mysql(`INSERT INTO points_ledger (user_id, points, reason) VALUES (${redeemUserId}, 500, 'earn')`);
  mysql(`INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (${redeemUserId}, 500000, 'earn', 'e2e seed')`);

  await login(page, AUTH_EMAIL_REDEEM);
  const code = await bookTour(page, { participants: 1, usePoints: true, useWallet: true });

  // 1.500.000 - 10.000 (100 points) - 500.000 (wallet) = 990.000
  const row = mysql(`SELECT total_price, user_id FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`);
  expect(row).toContain('990000');
  expect(row).toContain(String(redeemUserId));

  // Wallet terpotong 500.000 (spend) dan points terpotong 100 (redeem).
  expect(
    mysql(`SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ${redeemUserId} AND type = 'spend' AND amount = -500000`)
  ).toBe('1');
  expect(
    mysql(`SELECT COUNT(*) FROM points_ledger WHERE user_id = ${redeemUserId} AND reason = 'redeem' AND points = -100`)
  ).toBe('1');

  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});

test('diskon grup + promo + asuransi diterapkan pada total', async ({ page }) => {
  // Promo code tetap: diskon tetap Rp 50.000.
  mysql(
    `INSERT INTO promo_codes (code, description, discount_type, discount_value, valid_from, valid_until, is_active) ` +
      `VALUES ('E2E50', 'e2e fixed', 'fixed', 50000, '2020-01-01', '2030-01-01', 1) ` +
      `ON DUPLICATE KEY UPDATE discount_value = VALUES(discount_value), is_active = 1`
  );

  await login(page);
  const code = await bookTour(page, {
    participants: 5,
    paxNames: [undefined, 'Pax Dua', 'Pax Tiga', 'Pax Empat', 'Pax Lima'],
    promoCode: 'E2E50',
    insurance: true,
  });

  // 5 x 1.500.000 = 7.500.000; grup 5% = 375.000 -> 7.125.000;
  // promo tetap 50.000 -> 7.075.000; asuransi 3% (dibulatkan ke 100) = 212.300
  // -> total 7.287.300
  const row = mysql(`SELECT total_price, participants FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`);
  expect(row).toContain('7287300');
  expect(row).toContain('5');

  expect(
    mysql(`SELECT COUNT(*) FROM booking_addons WHERE booking_type = 'tour' AND booking_id = (SELECT id FROM bookings WHERE booking_code = '${code}') AND type = 'insurance' AND amount = 212300`)
  ).toBe('1');

  await page.goto(`${BASE}/my-bookings.php`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
});

test('pembatalan booking tour mengembalikan wallet (TravelPoints)', async ({ page }) => {
  const cancelEmail = 'e2e-tourbuy-cancel@t.local';
  cancelUserId = createUser(cancelEmail);
  mysql(`INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (${cancelUserId}, 500000, 'earn', 'e2e seed')`);

  await login(page, cancelEmail);
  const code = await bookTour(page, { participants: 1, useWallet: true });
  const bookingId = Number(mysql(`SELECT id FROM bookings WHERE booking_code = '${code}'`));

  // Wallet terpotong 500.000 saat booking (reference_type='tour_booking').
  expect(
    mysql(`SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ${cancelUserId} AND type = 'spend' AND reference_type = 'tour_booking' AND amount = -500000`)
  ).toBe('1');

  // Batalkan lewat "Batalkan" di my-bookings.
  page.once('dialog', (d) => d.accept());
  await page.goto(`${BASE}/my-bookings.php?cancel=${bookingId}&type=tour`);
  await page.waitForLoadState('domcontentloaded');

  expect(mysql(`SELECT status FROM bookings WHERE id = ${bookingId}`)).toBe('cancelled');
  // Wallet dikembalikan penuh (refund +500.000).
  expect(
    mysql(`SELECT COUNT(*) FROM wallet_transactions WHERE user_id = ${cancelUserId} AND type = 'refund' AND reference_type = 'tour_booking' AND amount = 500000`)
  ).toBe('1');
});

test('ubah booking (tanggal + peserta) memperbarui total', async ({ page }) => {
  const modEmail = 'e2e-tourbuy-mod@t.local';
  modUserId = createUser(modEmail);
  await login(page, modEmail);

  const code = await bookTour(page, { participants: 1 });
  const bookingId = Number(mysql(`SELECT id FROM bookings WHERE booking_code = '${code}'`));
  const date2Id = Number(mysql(`SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND departure_date = '${DATE2}'`));

  // Buka modal "Ubah" untuk booking ini.
  await page.goto(`${BASE}/my-bookings.php`);
  await page.locator(`button[data-bs-target="#modifyModal${bookingId}"]`).click();
  await page.locator(`#modifyModal${bookingId} select[name="new_date_id"]`).waitFor({ state: 'visible' });

  await page.locator(`#modifyModal${bookingId} select[name="new_date_id"]`).selectOption({ value: String(date2Id) });
  await page.locator(`#modifyModal${bookingId} input[name="new_participants"]`).fill('2');
  await page.locator(`#modifyModal${bookingId} button[type="submit"]`).click();
  await page.waitForURL(/my-bookings\.php\?msg=modified/, { timeout: 15000 });

  const row = mysql(`SELECT tour_date_id, participants, total_price FROM bookings WHERE id = ${bookingId}`);
  expect(row).toContain(String(date2Id));
  expect(row).toContain('2');
  expect(row).toContain(String(UNIT_PRICE * 2));
});

test('waitlist saat tour penuh', async ({ page }) => {
  // Penuhi tour: set available_slots = 0 pada semua tanggal.
  mysql(`UPDATE tour_dates SET available_slots = 0 WHERE tour_id = ${tourId}`);

  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await expect(page.locator('.waitlist-form')).toBeVisible();

  await page.locator('.waitlist-form input[name="waitlist_name"]').fill('Waitlist User');
  await page.locator('.waitlist-form input[name="waitlist_email"]').fill('waitlist@t.local');
  await page.locator('.waitlist-form input[name="waitlist_phone"]').fill('081234567890');
  await page.locator('.waitlist-form button[type="submit"]').click();

  await expect(page.locator('.waitlist-form')).toBeVisible();
  await expect(page.getByText('Berhasil join waitlist')).toBeVisible();
  expect(mysql(`SELECT COUNT(*) FROM tour_waitlist WHERE tour_id = ${tourId} AND email = 'waitlist@t.local'`)).toBe('1');
});
