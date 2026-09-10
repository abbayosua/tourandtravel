import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined.*variable|Undefined.*array)/i;
const EMAIL = `reseller_poor_${Date.now()}@example.com`;
const PASSWORD = 'password123';
let tourId = 0;
let tourDateId = 0;

function phpExec(cmd: string): string {
  return execSync(`php -r '${cmd}'`, { encoding: 'utf-8' }).trim();
}

function setup() {
  try {
    const tour = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id FROM tours WHERE is_active = 1 ORDER BY id ASC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim();
    tourId = parseInt(tour);

    const td = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date ASC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim();
    tourDateId = parseInt(td);

    // Create reseller with zero balance
    phpExec(`require_once "includes/config.php"; require_once "includes/db.php"; $hash = password_hash("${PASSWORD}", PASSWORD_DEFAULT); db()->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, reseller_balance) VALUES (?, ?, ?, ?, ?, ?)")->execute(["E2E Poor Reseller", "${EMAIL}", "081234567890", $hash, "reseller", 0]);`);

    // Set reseller pricing (high price)
    execSync(`mysql -u root tourandtravel -e "INSERT IGNORE INTO reseller_tour_prices (tour_id, reseller_price, min_pax, active) VALUES (${tourId}, 99999999, 1, 1)"`, { stdio: 'pipe' });
  } catch (e) {
    console.error('Setup error:', e);
  }
}

function cleanup() {
  try {
    execSync(`mysql -u root tourandtravel -e "
      DELETE FROM bookings WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
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

test.describe('Reseller Insufficient Balance', () => {
  test.beforeAll(() => { setup(); });
  test.afterAll(() => { cleanup(); });

  test('reseller with zero balance gets error when booking', async ({ page }) => {
    await loginReseller(page);
    await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show balance as Rp 0
    expect(body).toContain('Saldo');

    // Fill form and submit
    if (tourDateId) {
      await page.selectOption('select[name="tour_date_id"]', String(tourDateId));
    }
    await page.fill('input[name="passengers"]', '1');
    await page.fill('input[name="name"]', 'E2E Poor Reseller');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="phone"]', '081234567890');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const body2 = await page.textContent('body');
    // Should show insufficient balance error
    expect(body2).toMatch(/Saldo tidak cukup|saldo.*cukup|insufficient/i);
  });

  test('no booking created in DB after failed attempt', async () => {
    const count = execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source = 'reseller'"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseInt(count)).toBe(0);
  });

  test('balance remains zero after failed attempt', async () => {
    const balance = execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseFloat(balance)).toBe(0);
  });
});
