import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';
import { existsSync, readdirSync, statSync } from 'fs';
import { join } from 'path';
import { tmpdir } from 'os';

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

function savedSetting(key: string): string {
  return mysql(`SELECT setting_value FROM settings WHERE setting_key='${key}'`);
}

function restoreSettings(prev: Record<string, string>): void {
  for (const [k, v] of Object.entries(prev)) {
    if (v) mysql(`INSERT INTO settings (setting_key,setting_value) VALUES ('${k}','${v}') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`);
    else mysql(`DELETE FROM settings WHERE setting_key='${k}'`);
  }
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
let refundUserId = 0;
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
  for (const uid of [userId, redeemUserId, cancelUserId, modUserId, refundUserId]) {
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
  refundUserId = 0;
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

/** Buka kalender ketersediaan dan pilih tanggal keberangkatan pertama yang aktif. */
async function pickFirstDeparture(page: Page): Promise<void> {
  await page.locator('#bookingDateCal').click();
  const cal = page.locator('.flatpickr-calendar.open');
  await expect(cal).toBeVisible();
  await cal.locator('.flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)').first().click();
  await expect(page.locator('#tourDateId')).not.toHaveValue('');
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
  await pickFirstDeparture(page);
  await page.locator('#bookingName').fill('Buyer Tour');
  await page.locator('#bookingPhone').fill('081234567890');
  if (opts.usePoints) await page.locator('#usePointsTour').check();
  if (opts.useWallet) await page.locator('#useWalletTour').check();
  if (opts.promoCode) await page.locator('#promoCodeTour').fill(opts.promoCode);
  if (opts.insurance) await page.locator('#addInsuranceTour').check();

  await Promise.all([
    page.waitForURL(/booking-success\.php\?code=/, { timeout: 60000 }),
    page.locator('#bookingSubmitBtn').click(),
  ]);

  await expect(page.locator('.klook-booking-code')).toBeVisible();
  await expect(page.getByText('Pesanan Diterima')).toBeVisible();

  const code = (page.url().match(/code=([^&]+)/)?.[1] ?? '').trim();
  expect(code).not.toBe('');
  return code;
}

test('pembelian 1 peserta berhasil dan tersimpan (mode manual)', async ({ page }) => {
  await login(page);
  const code = await bookTour(page, { participants: 1 });

  // TravelPoints 5% (Rp 75.000) tampil di konfirmasi pertama untuk user login.
  const bsText = await page.locator('body').textContent();
  expect(bsText).toContain('TravelPoints');
  expect(bsText).toContain('75.000');

  const row = mysql(
    `SELECT status, participants, total_price, user_id FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`
  );
  expect(row).toContain('pending');
  expect(row).toContain('1');
  expect(row).toContain(String(userId));
  expect(row).toContain(String(UNIT_PRICE));
  expect(mysql(`SELECT COUNT(*) FROM booking_participants b JOIN bookings bk ON b.booking_id = bk.id WHERE bk.booking_code = '${code}'`)).toBe('1');

  // Halaman konfirmasi menampilkan total & instruksi pembayaran manual.
  await page.goto(`${BASE}/booking-success.php?code=${code}`);
  await expect(page.locator('.klook-booking-code')).toContainText(code);
  await expect(page.locator('body')).toContainText('Rp 1.500.000');
  await expect(page.locator('body')).toContainText('konfirmasi melalui WhatsApp');
  // Pending manual bukan "sukses".
  await expect(page.getByText('Booking Berhasil!')).toHaveCount(0);
  // Detail tour (judul + tanggal keberangkatan) tampil di konfirmasi.
  await expect(page.locator('body')).toContainText('E2E Tour Purchase');
  await expect(page.locator('body')).toContainText('Desember 2027');

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

  // Kode booking tetap bisa dilacak dengan detail lengkap.
  await page.goto(`${BASE}/track.php?code=${code}`);
  await expect(page.locator(`text=${code}`).first()).toBeVisible();
  await expect(page.locator('body')).toContainText('Menunggu Konfirmasi');
  await expect(page.locator('body')).toContainText('Rp 1.500.000');
  await expect(page.locator('body')).toContainText('E2E Tour Purchase');
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
  // Bersihkan sisa run sebelumnya agar saldo deterministik.
  mysql(`DELETE FROM wallet_transactions WHERE user_id = ${redeemUserId}`);
  mysql(`DELETE FROM points_ledger WHERE user_id = ${redeemUserId}`);
  mysql(`INSERT INTO points_ledger (user_id, points, reason) VALUES (${redeemUserId}, 500, 'earn')`);
  mysql(`INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (${redeemUserId}, 500000, 'earn', 'e2e seed')`);

  await login(page, AUTH_EMAIL_REDEEM);
  const code = await bookTour(page, { participants: 1, usePoints: true, useWallet: true });

  // 1.500.000 - 10.000 (100 points) - 500.000 (wallet) = 990.000
  const row = mysql(`SELECT total_price, user_id FROM bookings WHERE booking_code = '${code}' AND tour_id = ${tourId}`);
  expect(row).toContain('990000');
  expect(row).toContain(String(redeemUserId));

  // Total terpotong tampil di halaman konfirmasi.
  await expect(page.locator('body')).toContainText('Rp 990.000');

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

  // Total setelah diskon grup + promo + asuransi tampil di konfirmasi.
  await expect(page.locator('body')).toContainText('Rp 7.287.300');

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
  // Bersihkan sisa run sebelumnya agar saldo deterministik.
  mysql(`DELETE FROM wallet_transactions WHERE user_id = ${cancelUserId}`);
  mysql(`DELETE FROM points_ledger WHERE user_id = ${cancelUserId}`);
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

test('ajukan refund pada booking confirmed', async ({ page }) => {
  // Kembalikan slot (test waitlist sebelumnya menyetel 0).
  mysql(`UPDATE tour_dates SET available_slots = 10 WHERE tour_id = ${tourId}`);
  const refundEmail = 'e2e-tourbuy-refund@t.local';
  refundUserId = createUser(refundEmail);
  await login(page, refundEmail);
  const code = await bookTour(page, { participants: 1 });
  const bookingId = Number(mysql(`SELECT id FROM bookings WHERE booking_code = '${code}'`));

  // Simulasi booking dikonfirmasi admin.
  mysql(`UPDATE bookings SET status = 'confirmed' WHERE id = ${bookingId}`);

  await page.goto(`${BASE}/my-bookings.php`);
  await page.locator(`[data-testid="refund-btn-${bookingId}"]`).click();
  await page.locator(`#refundModal${bookingId}`).waitFor({ state: 'visible' });
  await page.locator(`#refundModal${bookingId} textarea[name="reason"]`).fill('Perubahan jadwal perjalanan');

  await Promise.all([
    page.waitForURL(/my-bookings\.php\?msg=refund_requested/, { timeout: 15000 }),
    page.locator(`#refundModal${bookingId} [data-testid="refund-submit"]`).click(),
  ]);

  expect(mysql(`SELECT refund_status FROM bookings WHERE id = ${bookingId}`)).toBe('requested');
});

test('kalender ketersediaan hanya mengaktifkan tanggal keberangkatan & mengisi tour_date_id', async ({ page }) => {
  // Pastikan kedua tanggal seed punya slot (booking test sebelumnya mengurangi sisa).
  mysql(`UPDATE tour_dates SET available_slots = 100 WHERE tour_id = ${tourId}`);

  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await expect(page.locator('#bookingDateCal')).toBeVisible();

  // Buka popup kalender ketersediaan booking.
  await page.locator('#bookingDateCal').click();
  const cal = page.locator('.flatpickr-calendar.open');
  await expect(cal).toBeVisible();

  // Hanya tanggal keberangkatan tour (2 tanggal seed) yang dapat dipilih.
  const enabledDays = cal.locator('.flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)');
  await expect(enabledDays).toHaveCount(2);

  // Klik tanggal keberangkatan kedua -> hidden tour_date_id terisi (regresi format).
  await enabledDays.filter({ hasText: /^15$/ }).click();
  const hid = page.locator('#tourDateId');
  await expect(hid).not.toHaveValue('');
  const date2Id = mysql(`SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND departure_date = '${DATE2}'`).trim();
  expect(await hid.inputValue()).toBe(date2Id);
});

test('booking ditolak bila tanggal keberangkatan belum dipilih di kalender', async ({ page }) => {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await page.locator('#bookingName').fill('Buyer Tour');
  await page.locator('#bookingPhone').fill('081234567890');
  await page.locator('#bookingSubmitBtn').click();

  await expect(page.locator('#bookingDateError')).toBeVisible();
  expect(page.url()).toContain('tour-detail.php');
});

test('klik baris Jadwal Keberangkatan memilih tanggal', async ({ page }) => {
  mysql(`UPDATE tour_dates SET available_slots = 100 WHERE tour_id = ${tourId}`);
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);

  const row = page.locator(`.date-item[data-date="${DATE2}"]`);
  await row.click();

  const date2Id = mysql(`SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND departure_date = '${DATE2}'`).trim();
  await expect(page.locator('#tourDateId')).toHaveValue(date2Id);
  await expect(page.locator('#bookingDateCal')).toHaveValue(DATE2);
  await expect(row).toHaveClass(/active/);
});

test('ganti kurs memperbarui subtotal & total di form booking', async ({ page }) => {
  // Mulai dari IDR agar nilai awal deterministik.
  await page.addInitScript(() => localStorage.setItem('currency', 'IDR'));
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);

  const total = page.locator('#sumTotal');
  const subtotal = page.locator('#sumBase');
  await expect(total).toContainText('Rp');
  const before = (await total.textContent()) || '';

  // Ganti kurs ke USD (jalur yang sama dengan tombol toggle mata uang).
  await page.evaluate(() => (window as any).CurrencySwitcher.switchTo('USD'));

  await expect(total).toContainText('$');
  await expect(subtotal).toContainText('$');
  expect(await total.textContent()).not.toBe(before);
});

test('subtotal menampilkan harga kotor & rekonsiliasi dengan total', async ({ page }) => {
  await page.addInitScript(() => localStorage.setItem('currency', 'IDR'));
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);

  // 5 peserta: gross 7.500.000, diskon grup 5% (375.000) -> total 7.125.000.
  await page.locator('input[name="participants"]').fill('5');

  await expect(page.locator('#sumBase')).toContainText('7.500.000');
  await expect(page.locator('#sumGroupRow')).toBeVisible();
  await expect(page.locator('#sumGroup')).toContainText('375.000');
  await expect(page.locator('#sumTotal')).toContainText('7.125.000');
});

test('pilih profil tersimpan disembunyikan bila belum ada profil', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  // User e2e tidak punya passenger_profiles -> blok harus tersembunyi.
  await expect(page.locator('#passengerSelect')).toBeHidden();
  await expect(page.locator('#savedProfileWrap')).toBeHidden();
});

test('validasi frontend menolak submit tanpa foto paspor', async ({ page }) => {
  mysql(`UPDATE tour_dates SET available_slots = 100 WHERE tour_id = ${tourId}`);
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await pickFirstDeparture(page);
  await page.locator('#bookingName').fill('Frontend Guard');
  await page.locator('#bookingPhone').fill('081234567890');
  await page.locator('#bookingSubmitBtn').click();

  await expect(page.locator('#bookingClientError')).toBeVisible();
  await expect(page.locator('#bookingClientError')).toContainText('paspor');
  expect(page.url()).toContain('tour-detail.php');
  expect(mysql(`SELECT COUNT(*) FROM bookings WHERE tour_id = ${tourId} AND name = 'Frontend Guard'`)).toBe('0');
});

test('konfirmasi manual: instruksi transfer + WhatsApp, bukan "sukses"', async ({ page }) => {
  await login(page);
  const code = await bookTour(page, { participants: 1 });
  await page.goto(`${BASE}/booking-success.php?code=${code}&lang=id`);

  await expect(page.getByText('Pesanan Diterima')).toBeVisible();
  await expect(page.getByText('Booking Berhasil!')).toHaveCount(0);
  const box = page.locator('[data-testid="manual-payment-instructions"]');
  await expect(box).toBeVisible();
  await expect(box).toContainText('Silakan transfer tepat sebesar');
  const wa = page.locator('[data-testid="wa-contact"]');
  await expect(wa).toBeVisible();
  expect(await wa.getAttribute('href')).toContain(encodeURIComponent(code));
});

test('instruksi manual menampilkan rekening dari pengaturan', async ({ page }) => {
  const keys = ['manual_bank_name', 'manual_bank_number', 'manual_bank_holder'];
  const prev: Record<string, string> = {};
  for (const k of keys) prev[k] = savedSetting(k);
  mysql(
    `INSERT INTO settings (setting_key, setting_value) VALUES ` +
      `('manual_bank_name','BCA E2E'),('manual_bank_number','1234567890'),('manual_bank_holder','PT E2E') ` +
      `ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`
  );
  try {
    await login(page);
    const code = await bookTour(page, { participants: 1 });
    await page.goto(`${BASE}/booking-success.php?code=${code}&lang=id`);

    const box = page.locator('[data-testid="manual-payment-instructions"]');
    await expect(box).toContainText('BCA E2E');
    await expect(box).toContainText('1234567890');
  } finally {
    restoreSettings(prev);
  }
});

test('catatan pembayaran manual mengikuti bahasa', async ({ page }) => {
  const keys = ['manual_payment_note', 'manual_payment_note_en', 'manual_payment_note_zh'];
  const prev: Record<string, string> = {};
  for (const k of keys) prev[k] = savedSetting(k);
  mysql(
    `INSERT INTO settings (setting_key, setting_value) VALUES ` +
      `('manual_payment_note','Catatan ID'),('manual_payment_note_en','Note EN'),('manual_payment_note_zh','备注 ZH') ` +
      `ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`
  );
  try {
    await login(page);
    const code = await bookTour(page, { participants: 1 });
    const cases: [string, string][] = [['id', 'Catatan ID'], ['en', 'Note EN'], ['zh', '备注 ZH']];
    for (const [lang, note] of cases) {
      await page.goto(`${BASE}/booking-success.php?code=${code}&lang=${lang}`);
      await expect(page.locator('[data-testid="manual-payment-instructions"]')).toContainText(note);
    }
  } finally {
    restoreSettings(prev);
  }
});

test('foto paspor besar tetap berhasil diunggah (dikompres di klien)', async ({ page }) => {
  mysql(`UPDATE tour_dates SET available_slots = 100 WHERE tour_id = ${tourId}`);
  const big = join(tmpdir(), 'e2e-pax-big.jpg');
  execFileSync('php', ['-r', '$im=imagecreatetruecolor(3000,3000);for($i=0;$i<3000;$i+=8){imagefilledrectangle($im,0,$i,3000,$i+4,imagecolorallocate($im,rand(0,255),rand(0,255),rand(0,255)));}imagejpeg($im,$argv[1],100);', big]);
  expect(statSync(big).size).toBeGreaterThan(2 * 1024 * 1024);

  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);
  await page.locator('[data-testid="open-pax-modal"]').click();
  await expect(page.locator('[data-testid="pax-rows"]')).toBeVisible();
  await page.locator('input.pax-file[data-idx="1"]').setInputFiles(big);

  // Kompresi klien menurunkan ukuran di bawah batas server -> upload sukses.
  await expect(page.locator('#passportFile1')).not.toHaveValue('', { timeout: 20000 });
});

test('nama paket di booking-success mengikuti bahasa', async ({ page }) => {
  mysql(`UPDATE tours SET title = 'Tur E2E Judul', title_en = 'E2E Title EN', title_zh = 'E2E 标题-ZH' WHERE id = ${tourId}`);
  try {
    await login(page);
    const code = await bookTour(page, { participants: 1 });
    const cases: [string, string][] = [['id', 'Tur E2E Judul'], ['en', 'E2E Title EN'], ['zh', 'E2E 标题-ZH']];
    for (const [lang, title] of cases) {
      await page.goto(`${BASE}/booking-success.php?code=${code}&lang=${lang}`);
      await expect(page.locator('body')).toContainText(title);
    }
  } finally {
    mysql(`UPDATE tours SET title = 'E2E Tour Purchase', title_en = '', title_zh = '' WHERE id = ${tourId}`);
  }
});
