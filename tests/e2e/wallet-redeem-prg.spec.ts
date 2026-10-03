import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: penukaran points (wallet.php) memakai POST/Redirect/GET.
 *
 * Bug yang dijaga: dulu POST sukses langsung render ulang, sehingga refresh
 * mengirim ulang form dan menukar points dua kali.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-redeem-prg@t.local';
const AUTH_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;

function seedPoints(points: number) {
  mysql(`DELETE FROM points_ledger WHERE user_id = ${userId}`);
  mysql(`INSERT INTO points_ledger (user_id, points, reason, note) VALUES (${userId}, ${points}, 'earn', 'e2e seed')`);
}

function balance(): string {
  return mysql(`SELECT COALESCE(SUM(points),0) FROM points_ledger WHERE user_id = ${userId}`);
}

async function login(page: Page) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', AUTH_EMAIL);
  await page.fill('input[name="password"]', AUTH_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(AUTH_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();
  mysql(
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E RedeemPRG', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));
});

test.afterAll(() => {
  if (userId) {
    mysql(`DELETE FROM points_ledger WHERE user_id = ${userId}`);
    mysql(`DELETE FROM wallet_transactions WHERE user_id = ${userId}`);
    try {
      mysql(`DELETE FROM users WHERE id = ${userId}`);
    } catch {
      /* ignore */
    }
  }
});

test('penukaran points redirect ke GET dan refresh tidak menukar ulang', async ({ page }) => {
  seedPoints(500);
  await login(page);
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await expect(page.locator('[data-testid="points-balance"]')).toContainText('500');

  page.once('dialog', (d) => d.accept());
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.locator('[data-testid="redeem-btn"]').click(),
  ]);

  await expect(page).toHaveURL(/redeem=success/);
  await expect(page.locator('[data-testid="redeem-success"]')).toBeVisible();
  expect(balance()).toBe('400');

  // Refresh tidak menukar lagi.
  await page.reload();
  await expect(page.locator('[data-testid="redeem-success"]')).toHaveCount(0);
  expect(balance()).toBe('400');
});
