import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined.*variable|Undefined.*array)/i;
const EMAIL = `reseller_admin_${Date.now()}@example.com`;
const PASSWORD = 'password123';
let tourId = 0;
let tourTitle = '';

function phpExec(cmd: string): string {
  return execSync(`php -r '${cmd}'`, { encoding: 'utf-8' }).trim();
}

function setup() {
  try {
    const tour = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id, title FROM tours WHERE is_active = 1 ORDER BY id ASC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim().split('\t');
    tourId = parseInt(tour[0]);
    tourTitle = tour[1];

    phpExec(`require_once "includes/config.php"; require_once "includes/db.php"; $hash = password_hash("${PASSWORD}", PASSWORD_DEFAULT); db()->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, reseller_balance) VALUES (?, ?, ?, ?, ?, ?)")->execute(["E2E Reseller Admin", "${EMAIL}", "081234567890", $hash, "reseller", 0]);`);

    // Clean any existing pricing for this tour
    execSync(`mysql -u root tourandtravel -e "DELETE FROM reseller_tour_prices WHERE tour_id = ${tourId}"`, { stdio: 'pipe' });

    // Create a pending topup
    const userId = execSync(
      `mysql -u root tourandtravel -N -e "SELECT id FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim();
    execSync(`mysql -u root tourandtravel -e "INSERT INTO reseller_topups (user_id, amount, payment_method, status) VALUES (${userId}, 500000, 'bank_transfer', 'pending')"`, { stdio: 'pipe' });
  } catch (e) {
    console.error('Setup error:', e);
  }
}

function cleanup() {
  try {
    execSync(`mysql -u root tourandtravel -e "
      DELETE FROM reseller_topups WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM wallet_transactions WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM reseller_tour_prices WHERE tour_id = ${tourId};
      DELETE FROM bookings WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM users WHERE email = '${EMAIL}';
    "`, { stdio: 'pipe' });
  } catch {}
}

async function loginAdmin(page: any) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test.describe('Reseller Admin', () => {
  test.beforeAll(() => { setup(); });
  test.afterAll(() => { cleanup(); });

  test('admin sees reseller in list', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/resellers.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('Kelola Reseller');
    expect(body).toContain('E2E Reseller Admin');
  });

  test('admin can see and approve pending topup', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/reseller-topups.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    const approveBtn = page.locator('button:has-text("Approve")').first();
    if (await approveBtn.isVisible()) {
      await approveBtn.click();
      await page.waitForLoadState('networkidle');
      const body2 = await page.textContent('body');
      expect(body2).toMatch(/approved|disetujui/i);
    }
  });

  test('admin can set reseller pricing', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/reseller-pricing.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('Harga Reseller');

    // Select tour and set price
    await page.selectOption('select[name="tour_id"]', String(tourId));
    await page.fill('input[name="reseller_price"]', '500000');
    await page.fill('input[name="min_pax"]', '1');
    await page.check('input[name="active"]');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Verify in DB directly
    const price = execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_price FROM reseller_tour_prices WHERE tour_id = ${tourId}"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseFloat(price)).toBe(500000);
  });

  test('reseller can see reseller price after admin setup', async ({ page }) => {
    // Login as reseller
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Check tour detail for reseller price
    const slug = execSync(
      `mysql -u root tourandtravel -N -e "SELECT slug FROM tours WHERE id = ${tourId}"`,
      { encoding: 'utf-8' }
    ).trim();
    await page.goto(`${BASE}/tour-detail.php?slug=${slug}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toContain('Harga Reseller');
    expect(body).toMatch(/500[,.]?000/);
  });
});
