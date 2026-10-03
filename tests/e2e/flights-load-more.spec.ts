import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: infinite scroll di flights.php dulu DEAD CODE — trigger
 * "load-more" hanya dirender di cabang non-search yang tak pernah dieksekusi,
 * sehingga hasil lokal (fallback DB) dipotong LIMIT 20 tanpa cara memuat
 * sisanya. Sekarang hasil lokal dipaginasi (10/halaman) dan trigger dirender
 * di cabang pencarian, memuat halaman berikutnya via flights-ajax.php.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const FROM = 'TST';
const TO = 'TTP';
const DATE = '2026-12-15';
const FLIGHT_NO = 'TA100';
const FROM_CITY = 'Testville (TST)';
const TO_CITY = 'Testopolis (TTP)';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  mysql(`DELETE fs FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE f.flight_number='${FLIGHT_NO}'`);
  mysql(`DELETE FROM flights WHERE flight_number='${FLIGHT_NO}'`);
  mysql(
    `INSERT INTO flights (airline, flight_number, from_city, to_city, departure_time, arrival_time, duration, price, class, is_active) ` +
      `VALUES ('TestAir','${FLIGHT_NO}','${FROM_CITY}','${TO_CITY}','08:00:00','10:00:00','2h 0m',100000.00,'economy',1)`
  );
  const fid = mysql(`SELECT id FROM flights WHERE flight_number='${FLIGHT_NO}'`);
  const rows = Array.from({ length: 12 }, (_, i) => `(${fid},'${DATE}',120,${100000 + i * 10000},1)`).join(',');
  mysql(`INSERT INTO flight_schedules (flight_id, departure_date, available_seats, price, is_active) VALUES ${rows}`);
});

test.afterAll(() => {
  mysql(`DELETE fs FROM flight_schedules fs JOIN flights f ON fs.flight_id=f.id WHERE f.flight_number='${FLIGHT_NO}'`);
  mysql(`DELETE FROM flights WHERE flight_number='${FLIGHT_NO}'`);
});

const url = () => `${BASE}/flights.php?search=1&from=${FROM}&to=${TO}&date=${DATE}&lang=id`;

async function openResults(page: Page) {
  await page.goto(url());
  await expect(page.locator('#flightContent')).toBeVisible();
  await expect(page.locator('#flightGrid')).toBeVisible();
}

test('hasil lokal menampilkan 10 kartu pertama + trigger halaman berikutnya', async ({ page }) => {
  await openResults(page);
  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(10);
  const trigger = page.locator('.load-more-trigger');
  await expect(trigger).toHaveCount(1);
  await expect(trigger).toHaveAttribute('data-page', '1');
  await expect(trigger).toHaveAttribute('data-last-page', '2');
});

test('scroll memuat halaman berikutnya lalu trigger hilang', async ({ page }) => {
  await openResults(page);
  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(10);

  await page.locator('.load-more-trigger').scrollIntoViewIfNeeded();

  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(12);
  await expect(page.locator('.load-more-trigger')).toHaveCount(0);
});

test('tampilan hasil lokal stabil (screenshot)', async ({ page }) => {
  await openResults(page);
  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(10);
  await expect(page.locator('#flightGrid')).toHaveScreenshot('flights-local-results.png', { maxDiffPixelRatio: 0.05 });
});

test('kegagalan memuat halaman berikut menampilkan pesan + tombol coba lagi', async ({ page }) => {
  await page.route('**/flights-ajax.php*', (route) => route.abort('failed'));
  await openResults(page);
  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(10);

  await page.locator('.load-more-trigger').scrollIntoViewIfNeeded();

  const errorBox = page.locator('[data-load-error="true"]');
  await expect(errorBox).toBeVisible();
  await expect(errorBox).toContainText('Gagal memuat penerbangan');
  await expect(page.locator('.load-more-spinner')).toBeHidden();
  await expect(page.locator('[data-load-retry]')).toBeVisible();

  await expect(page.locator('.load-more-trigger')).toHaveScreenshot('flights-load-more-error.png', { maxDiffPixelRatio: 0.1 });
});

test('tombol coba lagi memuat halaman berikut setelah koneksi pulih', async ({ page }) => {
  await page.route('**/flights-ajax.php*', (route) => route.abort('failed'));
  await openResults(page);
  await page.locator('.load-more-trigger').scrollIntoViewIfNeeded();
  await expect(page.locator('[data-load-error="true"]')).toBeVisible();

  await page.unroute('**/flights-ajax.php*');
  await page.locator('[data-load-retry]').click();

  await expect(page.locator('#flightGrid .flight-card')).toHaveCount(12);
  await expect(page.locator('.load-more-trigger')).toHaveCount(0);
});
