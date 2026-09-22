import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined)/i;
const EMAIL = `reseller_topup_${Date.now()}@example.com`;
const PASSWORD = 'password123';

function cleanup() {
  try {
    execSync(`mysql -u root tourandtravel -e "
      DELETE FROM reseller_topups WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM wallet_transactions WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM users WHERE email = '${EMAIL}';
    "`, { stdio: 'pipe' });
  } catch {}
}

async function registerReseller(page: any) {
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'E2E Reseller Topup');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="phone"]', '081234567890');
  await page.fill('input[name="password"]', PASSWORD);
  await page.fill('input[name="confirm_password"]', PASSWORD);
  await page.check('input[name="as_reseller"]');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function loginReseller(page: any) {
  await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function loginAdmin(page: any) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test.describe('Reseller Topup', () => {
  test.afterAll(() => { cleanup(); });

  test('full topup flow: register → topup → admin approve → balance updated', async ({ page }) => {
    // 1. Register reseller
    await registerReseller(page);

    // 2. Login and submit topup
    await loginReseller(page);
    await page.goto(`${BASE}/reseller-topup.php`, { waitUntil: 'load' });
    const body1 = await page.textContent('body');
    expect(body1).not.toMatch(PHP_ERROR);
    expect(body1).toContain('Topup');

    await page.fill('input[name="amount"]', '100000');
    await page.selectOption('select[name="payment_method"]', 'bank_transfer');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const body2 = await page.textContent('body');
    expect(body2).toContain('berhasil');

    // 3. Verify pending in DB
    const status = execSync(
      `mysql -u root tourandtravel -N -e "SELECT status FROM reseller_topups WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') ORDER BY id DESC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(status).toBe('pending');

    // 4. Admin login and approve
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/reseller-topups.php`, { waitUntil: 'load' });
    const body3 = await page.textContent('body');
    expect(body3).not.toMatch(PHP_ERROR);

    const approveForm = page.locator('form:has(button:has-text("Approve"))').first();
    if (await approveForm.isVisible()) {
      await approveForm.locator('button:has-text("Approve")').click();
      await page.waitForLoadState('networkidle');
    }

    // 5. Verify balance updated
    const balance = execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseFloat(balance)).toBeGreaterThan(0);

    // 6. Reseller sees balance and history
    await loginReseller(page);
    await page.goto(`${BASE}/reseller-topup.php`, { waitUntil: 'load' });
    const body4 = await page.textContent('body');
    expect(body4).toContain('Riwayat Topup');
  });
});

test.describe('Reseller Topup - akuntansi tepat', () => {
  const AMT = '150000';

  test('TC-704b approve → balance = amount TEPAT (bukan lebih/kurang)', async ({ page }) => {
    await registerReseller(page);
    await loginReseller(page);
    await page.goto(`${BASE}/reseller-topup.php`, { waitUntil: 'load' });
    await page.fill('input[name="amount"]', AMT);
    await page.selectOption('select[name="payment_method"]', 'bank_transfer');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(await page.textContent('body')).toContain('berhasil');

    // topup row: amount TEPAT & pending
    const row = execSync(
      `mysql -u root tourandtravel -N -e "SELECT amount, status FROM reseller_topups WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') ORDER BY id DESC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(row).toBe(`${AMT}.00\tpending`);

    await loginAdmin(page);
    await page.goto(`${BASE}/admin/reseller-topups.php`, { waitUntil: 'load' });
    const approveForm = page.locator('form:has(button:has-text("Approve"))').first();
    await approveForm.locator('button:has-text("Approve")').click();
    await page.waitForLoadState('networkidle');

    // AKUNTANSI: balance harus persis = amount (150000.00), status topup = approved
    const [bal, st] = execSync(
      `mysql -u root tourandtravel -N -e "SELECT u.reseller_balance, t.status FROM users u JOIN reseller_topups t ON t.user_id = u.id WHERE u.email = '${EMAIL}' ORDER BY t.id DESC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim().split('\t');
    expect(parseFloat(bal)).toBe(150000);
    expect(st).toBe('approved');
  });
});

test.describe('Reseller Topup - reject & invalid', () => {
  const REMAIL = `rej_${Date.now()}@example.com`;

  async function registerR(page: any) {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Reject Tester');
    await page.fill('input[name="email"]', REMAIL);
    await page.fill('input[name="phone"]', '0812377000');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.check('input[name="as_reseller"]');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
  }

  test('TC-705 reject → balance TETAP 0 + status rejected + email log tercatat', async ({ page }) => {
    await registerR(page);
    // login reseller
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', REMAIL);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/reseller-topup.php`, { waitUntil: 'load' });
    await page.fill('input[name="amount"]', '200000');
    await page.selectOption('select[name="payment_method"]', 'qris');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/admin/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.goto(`${BASE}/admin/reseller-topups.php`, { waitUntil: 'load' });

    const rejForm = page.locator('form:has(button:has-text("Reject"))').first();
    await rejForm.locator('button:has-text("Reject")').click();
    await page.waitForLoadState('networkidle');

    const [bal, st] = execSync(
      `mysql -u root tourandtravel -N -e "SELECT u.reseller_balance, t.status FROM users u JOIN reseller_topups t ON t.user_id = u.id WHERE u.email = '${REMAIL}' ORDER BY t.id DESC LIMIT 1"`,
      { encoding: 'utf-8' }
    ).trim().split('\t');
    expect(parseFloat(bal)).toBe(0);
    expect(st).toBe('rejected');

    // email reject terkirim (email_log)
    const mail = execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM email_log WHERE to_email = '${REMAIL}' AND subject LIKE '%Ditolak%'"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseInt(mail)).toBeGreaterThanOrEqual(1);
  });

  test('TC-703 topup amount 50000 kurang dari min → ditolak (via UI)', async ({ page }) => {
    await registerR(page);
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', REMAIL);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await page.goto(`${BASE}/reseller-topup.php`, { waitUntil: 'load' });
    await page.fill('input[name="amount"]', '49999');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Minimal topup|50\.000/i);
  });
});
