import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: input angka dengan step yang tidak selaras memblokir submit
 * secara senyap (native stepMismatch). Dulu:
 *  - admin reseller_price step="1000" (harga non-kelipatan 1000 tak bisa disimpan)
 *  - reseller topup amount step="10000" (mis. Rp55.000 ditolak)
 * Sekarang step="any" sehingga nilai valid apa pun bisa dikirim.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const PASS = 'tmpcheck123';
const RESELLER_EMAIL = 'e2e-step-reseller@t.local';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(PASS)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E Step','${RESELLER_EMAIL}','${hash}','reseller') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), role='reseller'`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM users WHERE email='${RESELLER_EMAIL}'`);
});

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('harga reseller non-kelipatan 1000 tetap valid', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/reseller-pricing.php`);

  const input = page.locator('input[name="reseller_price"]');
  await input.fill('1234500');
  expect(await input.evaluate((el) => (el as HTMLInputElement).checkValidity())).toBe(true);

  await expect(input).toHaveScreenshot('reseller-price-step.png', { maxDiffPixelRatio: 0.15 });
});

test('topup non-kelipatan 10.000 tetap valid', async ({ page }) => {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', RESELLER_EMAIL);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('form[data-submit-once] button[type="submit"]')]);
  await page.goto(`${BASE}/reseller-topup.php`);

  const input = page.locator('input[name="amount"]');
  await input.fill('55000');
  expect(await input.evaluate((el) => (el as HTMLInputElement).checkValidity())).toBe(true);

  await expect(input).toHaveScreenshot('reseller-topup-step.png', { maxDiffPixelRatio: 0.15 });
});
