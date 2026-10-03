import { test, expect, devices, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi: modal admin harus benar-benar bisa dipakai (bukan sekadar tampil).
 *
 * Bug yang dijaga:
 * 1) Modal dulu dirender di dalam <td> tabel -> position:fixed salah hitung
 *    (tinggi mengikuti tinggi tabel, bukan viewport) dan tertutup .modal-backdrop
 *    sehingga input/tombol tidak bisa diklik.
 * 2) Tabel daftar peserta di modal meluber keluar dialog.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const BOOKING_CODE = 'E2EMODAL1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let bookingId = 0;
let participantIds: number[] = [];

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForURL(/admin\/(bookings|index|dashboard)/), page.click('button[type="submit"]')]);
}

test.beforeAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code = '${BOOKING_CODE}'`);
  const tourDateId = mysql(`SELECT id FROM tour_dates WHERE tour_id = 148 ORDER BY departure_date ASC LIMIT 1`);
  mysql(
    `INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
      `VALUES ('${BOOKING_CODE}', 148, ${tourDateId}, 'E2E Modal Buyer', 'e2e@t.local', '0800000000', 2, 100000, 'pending')`
  );
  bookingId = Number(mysql(`SELECT id FROM bookings WHERE booking_code = '${BOOKING_CODE}'`));
  mysql(
    `INSERT INTO booking_participants (booking_id, full_name, passport_photo) VALUES ` +
      `(${bookingId}, 'E2E Peserta Satu', NULL), (${bookingId}, 'E2E Peserta Dua', NULL)`
  );
  participantIds = mysql(`SELECT id FROM booking_participants WHERE booking_id = ${bookingId} ORDER BY id`)
    .split('\n')
    .map((v) => Number(v.trim()));
});

test.afterAll(() => {
  mysql(`DELETE FROM bookings WHERE booking_code = '${BOOKING_CODE}'`);
});

/** Inti regresi: modal harus setinggi viewport dan titik di dalamnya tidak tertutup backdrop. */
async function expectModalUsable(page: Page, modalSelector: string, innerSelector: string) {
  const geom = await page.evaluate(
    ([mSel, iSel]) => {
      const modal = document.querySelector(mSel)!;
      const inner = document.querySelector(iSel)!;
      const mb = modal.getBoundingClientRect();
      const ib = inner.getBoundingClientRect();
      const top = document.elementFromPoint(ib.left + ib.width / 2, ib.top + ib.height / 2);
      return {
        modalHeight: Math.round(mb.height),
        viewportHeight: window.innerHeight,
        topElementClass: top ? String(top.className) : '',
        topInsideModal: !!(top && top.closest(mSel)),
        innerWidth: Math.round(ib.width),
        dialogWidth: Math.round(modal.querySelector('.modal-dialog')!.getBoundingClientRect().width),
      };
    },
    [modalSelector, innerSelector] as const
  );

  // tidak boleh lebih tinggi dari viewport (bug lama: mengikuti tinggi tabel)
  expect(geom.modalHeight).toBeLessThanOrEqual(geom.viewportHeight + 1);
  // titik di dalam modal tidak boleh tertutup backdrop / sidebar
  expect(geom.topInsideModal, `tertutup oleh: ${geom.topElementClass}`).toBe(true);
  // input tidak boleh meluber keluar dialog
  expect(geom.innerWidth).toBeLessThanOrEqual(geom.dialogWidth);
}

test.describe('mobile', () => {
  test.use({
    viewport: devices['iPhone 13'].viewport,
    userAgent: devices['iPhone 13'].userAgent,
    deviceScaleFactor: devices['iPhone 13'].deviceScaleFactor,
    isMobile: devices['iPhone 13'].isMobile,
    hasTouch: devices['iPhone 13'].hasTouch,
  });

  test('modal peserta & catatan bisa dipakai di mobile', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/bookings.php?type=tour`);

    await page.click(`[data-testid="pax-view-${bookingId}"]`);
    await expect(page.locator(`#paxModal${bookingId}`)).toBeVisible();
    await expectModalUsable(page, `#paxModal${bookingId}`, `#paxModal${bookingId} input[type="text"]`);

    // benar-benar bisa diketik
    const firstInput = page.locator(`#paxModal${bookingId} input[type="text"]`).first();
    await firstInput.click();
    await firstInput.fill('E2E Nama Diubah');
    await expect(firstInput).toHaveValue('E2E Nama Diubah');

    // simpan nama -> tersimpan ke DB
    await page.click(`[data-testid="pax-save-${bookingId}"]`);
    await page.waitForLoadState('load');
    expect(mysql(`SELECT full_name FROM booking_participants WHERE id = ${participantIds[0]}`)).toBe('E2E Nama Diubah');

    // hapus peserta kedua
    await page.goto(`${BASE}/admin/bookings.php?type=tour`);
    await page.click(`[data-testid="pax-view-${bookingId}"]`);
    page.once('dialog', (d) => d.accept());
    await page.locator(`[data-testid="pax-del-${participantIds[1]}"]`).click();
    await page.goto(`${BASE}/admin/bookings.php?type=tour`);
    expect(mysql(`SELECT COUNT(*) FROM booking_participants WHERE id = ${participantIds[1]}`)).toBe('0');

    // modal catatan internal (pre-existing) juga harus bisa dipakai
    await page.click(`button[data-bs-target="#noteModal${bookingId}"]`);
    await expect(page.locator(`#noteModal${bookingId}`)).toBeVisible();
    await expectModalUsable(page, `#noteModal${bookingId}`, `#noteModal${bookingId} textarea`);
    await page.locator(`#noteModal${bookingId} textarea`).click();
    await page.locator(`#noteModal${bookingId} textarea`).fill('E2E catatan ok');
    await expect(page.locator(`#noteModal${bookingId} textarea`)).toHaveValue('E2E catatan ok');
  });
});

test.describe('desktop', () => {
  test('modal peserta & catatan bisa dipakai di desktop', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/bookings.php?type=tour`);

    await page.click(`[data-testid="pax-view-${bookingId}"]`);
    await expect(page.locator(`#paxModal${bookingId}`)).toBeVisible();
    await expectModalUsable(page, `#paxModal${bookingId}`, `#paxModal${bookingId} input[type="text"]`);
    await page.locator(`#paxModal${bookingId} input[type="text"]`).first().click();
    await page.keyboard.press('Escape');

    await page.click(`button[data-bs-target="#noteModal${bookingId}"]`);
    await expect(page.locator(`#noteModal${bookingId}`)).toBeVisible();
    await expectModalUsable(page, `#noteModal${bookingId}`, `#noteModal${bookingId} textarea`);
    await page.locator(`#noteModal${bookingId} textarea`).click();
  });
});
