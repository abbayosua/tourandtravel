import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: approve topup reseller di admin/reseller-topups.php dulu
 * langsung menambah saldo reseller TANPA konfirmasi. Sekarang ada dialog
 * konfirmasi (dismiss = tidak approve, accept = approve).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const EMAIL = 'e2e-topup-confirm@t.local';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let topupId = '';
let userId = '';

test.beforeAll(() => {
  mysql(`INSERT INTO users (name,email,password_hash,role) VALUES ('E2E Topup','${EMAIL}','x','reseller') ON DUPLICATE KEY UPDATE role='reseller'`);
  userId = mysql(`SELECT id FROM users WHERE email='${EMAIL}'`);
  mysql(`DELETE FROM reseller_topups WHERE user_id=${userId}`);
  mysql(`INSERT INTO reseller_topups (user_id, amount, payment_method, status) VALUES (${userId}, 500000, 'bank_transfer', 'pending')`);
  topupId = mysql(`SELECT id FROM reseller_topups WHERE user_id=${userId} ORDER BY id DESC LIMIT 1`);
});

test.afterAll(() => {
  if (userId) mysql(`DELETE FROM reseller_topups WHERE user_id=${userId}`);
  mysql(`DELETE FROM users WHERE email='${EMAIL}'`);
});

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

const approveBtn = (page: Page) => page.locator('form:has(input[value="approved"]) button[type="submit"]').first();

test('approve topup meminta konfirmasi', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/reseller-topups.php?user_id=${userId}`);
  await expect(approveBtn(page)).toBeVisible();

  // Dismiss → tidak approve (status tetap pending).
  page.once('dialog', (d) => d.dismiss());
  await approveBtn(page).click();
  await page.waitForTimeout(400);
  expect(mysql(`SELECT status FROM reseller_topups WHERE id=${topupId}`)).toBe('pending');

  // Accept → approve.
  page.once('dialog', (d) => d.accept());
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    approveBtn(page).click(),
  ]);
  await page.waitForTimeout(300);
  expect(mysql(`SELECT status FROM reseller_topups WHERE id=${topupId}`)).toBe('approved');
});
