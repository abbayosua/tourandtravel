import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: "Tandai Kedaluwarsa" di admin/payments.php dulu langsung
 * mengubah status pembayaran (GET) tanpa konfirmasi — mudah salah klik.
 * Sekarang ada dialog konfirmasi.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const ORDER = 'E2E-EXPIRE-1';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let payId = '';

test.beforeAll(() => {
  mysql(`DELETE FROM payments WHERE order_id='${ORDER}'`);
  mysql(`INSERT INTO payments (booking_type, booking_id, order_id, gateway, gross_amount, status) VALUES ('tour', 1, '${ORDER}', 'midtrans', 100000, 'pending')`);
  payId = mysql(`SELECT id FROM payments WHERE order_id='${ORDER}'`);
});

test.afterAll(() => {
  mysql(`DELETE FROM payments WHERE order_id='${ORDER}'`);
});

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('tandai kedaluwarsa meminta konfirmasi', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/payments.php?status=pending`);

  const link = page.locator(`a[href="payments.php?expire=${payId}"]`);
  await expect(link).toBeVisible();

  // Dismiss → status tetap pending.
  page.once('dialog', (d) => d.dismiss());
  await link.click();
  await page.waitForTimeout(400);
  expect(mysql(`SELECT status FROM payments WHERE id=${payId}`)).toBe('pending');

  // Accept → expired.
  page.once('dialog', (d) => d.accept());
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    link.click(),
  ]);
  await page.waitForTimeout(300);
  expect(mysql(`SELECT status FROM payments WHERE id=${payId}`)).toBe('expired');
});
