import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';
import { writeFileSync, mkdirSync, rmSync } from 'fs';
import { join } from 'path';

/**
 * Regresi: halaman Lacak Booking (track.php).
 *
 * 1) QR voucher disembunyikan sementara ($showTrackQr = false) — tidak boleh ada
 *    gambar QR api.qrserver.com yang terender.
 * 2) Foto paspor dibuka via modal Bootstrap, BUKAN pindah tab/halaman.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const BOOKING_CODE = 'E2EPASS1';
const PASSPORT_FILE = 'e2e-passport-test.png';
const PASSPORT_DIR = join(process.cwd(), 'uploads', 'passports');
const PASSPORT_PATH = join(PASSPORT_DIR, PASSPORT_FILE);

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let bookingId = 0;

test.beforeAll(() => {
  mkdirSync(PASSPORT_DIR, { recursive: true });
  // PNG 1x1 transparan, cukup agar <img> tidak broken.
  writeFileSync(
    PASSPORT_PATH,
    Buffer.from(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
      'base64'
    )
  );

  mysql(`DELETE FROM bookings WHERE booking_code = '${BOOKING_CODE}'`);
  const tourDateId = mysql(`SELECT id FROM tour_dates WHERE tour_id = 148 ORDER BY departure_date ASC LIMIT 1`);
  mysql(
    `INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
      `VALUES ('${BOOKING_CODE}', 148, ${tourDateId}, 'E2E Passport Buyer', 'e2e@t.local', '0800000000', 1, 100000, 'pending')`
  );
  bookingId = Number(mysql(`SELECT id FROM bookings WHERE booking_code = '${BOOKING_CODE}'`));
  mysql(
    `INSERT INTO booking_participants (booking_id, full_name, passport_photo) VALUES ` +
      `(${bookingId}, 'E2E Peserta Paspor', '${PASSPORT_FILE}')`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code = '${BOOKING_CODE}'`);
  rmSync(PASSPORT_PATH, { force: true });
});

test('QR voucher tidak ditampilkan di track.php', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=${BOOKING_CODE}`);
  await expect(page.locator('.fs-3.fw-bold.text-primary')).toContainText(BOOKING_CODE);
  await expect(page.locator('img[src*="api.qrserver.com"]')).toHaveCount(0);
});

test('foto paspor dibuka lewat modal tanpa pindah halaman', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=${BOOKING_CODE}`);
  const urlBefore = page.url();

  let popupOpened = false;
  page.on('popup', () => { popupOpened = true; });

  const trigger = page.locator('[data-passport]').first();
  await expect(trigger).toBeVisible();
  await trigger.click();

  const modal = page.locator('#passportViewer');
  await expect(modal).toBeVisible();
  const img = page.locator('#passportViewerImg');
  await expect(img).toHaveAttribute('src', new RegExp(PASSPORT_FILE));

  // tetap di halaman yang sama, tidak ada tab baru
  expect(page.url()).toBe(urlBefore);
  expect(popupOpened).toBe(false);

  // tutup modal
  await modal.locator('.passport-viewer__close').click();
  await expect(modal).not.toBeVisible();
});

test('foto paspor di admin dibuka lewat modal (dari dalam modal peserta)', async ({ page }) => {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'tmpcheck123');
  await Promise.all([page.waitForURL(/admin\//), page.click('button[type="submit"]')]);

  await page.goto(`${BASE}/admin/bookings.php?type=tour`);
  await page.click(`[data-testid="pax-view-${bookingId}"]`);
  const paxModal = page.locator(`#paxModal${bookingId}`);
  await expect(paxModal).toBeVisible();

  // modal peserta harus tetap berfungsi (regresi: include path salah bikin fatal)
  expect(await page.evaluate(() => typeof (window as any).bootstrap)).toBe('object');

  await paxModal.locator('[data-passport]').first().click();
  const modal = page.locator('#passportViewer');
  await expect(modal).toBeVisible();
  await expect(page.locator('#passportViewerImg')).toHaveAttribute('src', new RegExp(PASSPORT_FILE));

  await modal.locator('.passport-viewer__close').click();
  await expect(modal).not.toBeVisible();
  // modal peserta tetap terbuka di belakang
  await expect(paxModal).toBeVisible();
});
