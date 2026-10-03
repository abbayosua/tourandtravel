import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: menukar points di wallet.php harus minta konfirmasi.
 *
 * Bug yang dijaga: tombol "Tukar" dulu langsung menukar points (1 point = Rp 100)
 * tanpa dialog konfirmasi — aksi bernilai dan tidak bisa dibatalkan.
 *
 * Test menyemai user sementara + 500 points, lalu memastikan dialog muncul,
 * dismiss = tidak ada penukaran, accept = points benar-benar ditukar.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-redeem@t.local';
const AUTH_PASS = 'e2epass123';
const CONFIRM_TEXT = 'Tukar points menjadi TravelPoints? Penukaran tidak dapat dibatalkan.';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;

function seedPoints(points: number) {
  mysql(`DELETE FROM points_ledger WHERE user_id = ${userId}`);
  mysql(`INSERT INTO points_ledger (user_id, points, reason, note) VALUES (${userId}, ${points}, 'earn', 'e2e seed')`);
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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Redeem', '${AUTH_EMAIL}', '${hash}', 'user') ` +
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
      // abaikan bila masih direferensikan
    }
  }
});

test('menukar points menampilkan dialog konfirmasi; dismiss tidak menukar', async ({ page }) => {
  seedPoints(500);
  await login(page);
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await expect(page.locator('[data-testid="redeem-form"]')).toBeVisible();
  await expect(page.locator('[data-testid="points-balance"]')).toContainText('500');

  await expect(page.locator('[data-testid="redeem-form"]')).toHaveScreenshot('wallet-redeem-form.png', {
    maxDiffPixelRatio: 0.1,
  });

  let dialogMessage = '';
  page.once('dialog', (d) => {
    dialogMessage = d.message();
    d.dismiss();
  });
  await page.locator('[data-testid="redeem-btn"]').click();

  expect(dialogMessage).toBe(CONFIRM_TEXT);
  // dismiss → tidak ada penukaran: tetap di halaman yang sama, saldo tidak berubah.
  await expect(page.locator('[data-testid="redeem-success"]')).toHaveCount(0);
  await expect(page.locator('[data-testid="points-balance"]')).toContainText('500');
});

test('accept pada dialog benar-benar menukar points', async ({ page }) => {
  seedPoints(500);
  await login(page);
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await expect(page.locator('[data-testid="points-balance"]')).toContainText('500');

  page.once('dialog', (d) => d.accept());
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    page.locator('[data-testid="redeem-btn"]').click(),
  ]);

  await expect(page.locator('[data-testid="redeem-success"]')).toBeVisible();
  await expect(page.locator('[data-testid="points-balance"]')).toContainText('400');
});
