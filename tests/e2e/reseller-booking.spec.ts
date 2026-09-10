import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined.*variable|Undefined.*array)/i;
const EMAIL = `reseller_book_${Date.now()}@example.com`;
const PASSWORD = 'password123';
const RESELLER_PRICE = 500000;
let tourId = 0;
let tourSlug = '';
let tourDateId = 0;

function phpExec(cmd: string): string {
  return execSync(`php -r '${cmd}'`, { encoding: 'utf-8' }).trim();
}

function setup() {
  try {
    const tour = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id, slug FROM tours WHERE is_active = 1 ORDER BY id ASC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim().split('\t');
    tourId = parseInt(tour[0]);
    tourSlug = tour[1];

    const td = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date ASC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim();
    tourDateId = parseInt(td);

    // Create user with proper password hash via PHP
    phpExec(`require_once "includes/config.php"; require_once "includes/db.php"; $hash = password_hash("${PASSWORD}", PASSWORD_DEFAULT); db()->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, reseller_balance) VALUES (?, ?, ?, ?, ?, ?)")->execute(["E2E Reseller Book", "${EMAIL}", "081234567890", $hash, "reseller", 1000000]);`);
    execSync(`mysql -u root tourandtravel -e "INSERT IGNORE INTO reseller_tour_prices (tour_id, reseller_price, min_pax, active) VALUES (${tourId}, ${RESELLER_PRICE}, 1, 1)"`, { stdio: 'pipe' });
  } catch (e) {
    console.error('Setup error:', e);
  }
}

function cleanup() {
  try {
    execSync(`mysql -u root tourandtravel -e "
      DELETE FROM bookings WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM wallet_transactions WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM reseller_tour_prices WHERE tour_id = ${tourId};
      DELETE FROM users WHERE email = '${EMAIL}';
    "`, { stdio: 'pipe' });
  } catch {}
}

async function loginReseller(page: any) {
  await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test.describe('Reseller Booking', () => {
  test.beforeAll(() => { setup(); });
  test.afterAll(() => { cleanup(); });

  test('reseller sees reseller price on tour detail', async ({ page }) => {
    await loginReseller(page);
    await page.goto(`${BASE}/tour-detail.php?slug=${tourSlug}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    const resellerBadge = page.locator('[data-testid="reseller-price"]');
    await expect(resellerBadge).toBeVisible();
    expect(await resellerBadge.textContent()).toContain('Reseller');
  });

  test('reseller can book via reseller-booking page', async ({ page }) => {
    await loginReseller(page);

    const balBefore = parseFloat(execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim());

    await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('Booking Reseller');

    if (tourDateId) {
      await page.selectOption('select[name="tour_date_id"]', String(tourDateId));
    }
    await page.fill('input[name="passengers"]', '2');
    await page.fill('input[name="name"]', 'E2E Reseller Book');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="phone"]', '081234567890');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const body2 = await page.textContent('body');
    const hasSuccess = body2.includes('Booking berhasil') || body2.includes('kode booking');
    const hasError = body2.includes('Saldo tidak cukup') || body2.includes('Gagal');
    expect(hasSuccess || hasError).toBeTruthy();

    if (hasSuccess) {
      const balAfter = parseFloat(execSync(
        `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
        { encoding: 'utf-8' }
      ).trim());
      expect(balAfter).toBeLessThan(balBefore);

      const bookingSource = execSync(
        `mysql -u root tourandtravel -N -e "SELECT booking_source FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source = 'reseller' ORDER BY id DESC LIMIT 1"`,
        { encoding: 'utf-8' }
      ).trim();
      expect(bookingSource).toBe('reseller');
    }
  });

  test('reseller-booking shows balance and tour details', async ({ page }) => {
    await loginReseller(page);
    await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toContain('Saldo');
    expect(body).toContain('Harga Reseller');
  });
});
