import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX (PRG): halaman booking ferry & PELNI dulu merender status
 * sukses langsung dari POST — refresh (F5) mengirim ulang POST dan membuat
 * booking GANDA. Sekarang memakai Post/Redirect/Get: setelah submit, redirect
 * ke GET ?booking=<kode> sehingga refresh tidak membuat booking baru.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const FERRY_EMAIL = 'e2e-prg-ferry@t.local';
const PELNI_EMAIL = 'e2e-prg-pelni@t.local';
const FERRY = 'ferry-booking.php?company=TestFerry&from=A&to=B&date=2026-12-01&time=08:00&price=100000&passengers=1&vessel=V1';
const PELNI = 'pelni-booking.php?ship_name=TestShip&ship_code=TS1&from=A&to=B&date=2026-12-01&time=08:00&price=100000&passengers=1&ship_class=Ekonomi&ship_number=123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

async function submitBooking(page: Page, url: string, email: string) {
  await page.goto(`${BASE}/${url}&lang=id`);
  await page.fill('[data-testid="input-name"]', 'E2E PRG');
  await page.fill('[data-testid="input-email"]', email);
  await page.fill('[data-testid="input-phone"]', '081234567890');
  await page.fill('[data-testid="input-pax-1"]', 'E2E PRG');
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.click('[data-testid="btn-confirm-booking"]'),
  ]);
  await page.waitForLoadState('networkidle');
}

test.afterAll(() => {
  mysql(`DELETE FROM ferry_bookings WHERE email='${FERRY_EMAIL}'`);
  mysql(`DELETE FROM pelni_bookings WHERE email='${PELNI_EMAIL}'`);
});

test('booking ferry pakai PRG: refresh tidak membuat booking ganda', async ({ page }) => {
  mysql(`DELETE FROM ferry_bookings WHERE email='${FERRY_EMAIL}'`);

  await submitBooking(page, FERRY, FERRY_EMAIL);
  await expect(page.locator('[data-testid="booking-code"]')).toBeVisible();
  expect(page.url()).toContain('booking=');

  const code = (await page.locator('[data-testid="booking-code"]').textContent())!.trim();
  expect(mysql(`SELECT COUNT(*) FROM ferry_bookings WHERE email='${FERRY_EMAIL}'`)).toBe('1');

  // Refresh: URL GET, booking tetap satu dan kode sama.
  await page.reload();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('[data-testid="booking-code"]')).toHaveText(code);
  expect(mysql(`SELECT COUNT(*) FROM ferry_bookings WHERE email='${FERRY_EMAIL}'`)).toBe('1');

  // Kode acak → normalkan teks agar baseline screenshot stabil.
  await page.locator('[data-testid="booking-code"]').evaluate((el) => {
    el.textContent = 'FB0000000000';
  });
  await expect(page.locator('[data-testid="booking-code"]').locator('..')).toHaveScreenshot('ferry-booking-prg.png', {
    maxDiffPixelRatio: 0.15,
  });
});

test('booking PELNI pakai PRG: refresh tidak membuat booking ganda', async ({ page }) => {
  mysql(`DELETE FROM pelni_bookings WHERE email='${PELNI_EMAIL}'`);

  await submitBooking(page, PELNI, PELNI_EMAIL);
  await expect(page.locator('[data-testid="booking-code"]')).toBeVisible();
  expect(page.url()).toContain('booking=');

  const code = (await page.locator('[data-testid="booking-code"]').textContent())!.trim();
  expect(mysql(`SELECT COUNT(*) FROM pelni_bookings WHERE email='${PELNI_EMAIL}'`)).toBe('1');

  await page.reload();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('[data-testid="booking-code"]')).toHaveText(code);
  expect(mysql(`SELECT COUNT(*) FROM pelni_bookings WHERE email='${PELNI_EMAIL}'`)).toBe('1');
});
