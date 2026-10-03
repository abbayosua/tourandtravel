import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: form admin dulu tidak punya guard anti dobel-submit / loading
 * state (handler hanya ada di footer publik). Sekarang admin-footer.php memuat
 * handler yang sama dan form-form admin memakai data-submit-once.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('form admin menampilkan loading & mengunci tombol saat submit', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/promo-codes.php`);

  const form = page.locator('form[data-submit-once]').first();
  const btn = form.locator('button[type="submit"]').first();
  await expect(btn).toBeVisible();

  // Kirim event submit (tanpa navigasi) → handler guard mengunci tombol.
  await form.evaluate((f) => f.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));

  await expect(btn).toBeDisabled();
  await expect(btn.locator('.spinner-border')).toHaveCount(1);
  await expect(btn).toHaveScreenshot('admin-form-loading.png', { maxDiffPixelRatio: 0.15 });
});

test('halaman admin memuat handler data-submit-once', async ({ page }) => {
  await adminLogin(page);
  for (const p of ['promo-codes.php', 'flash-sales.php']) {
    await page.goto(`${BASE}/admin/${p}`);
    await expect(page.locator('form[data-submit-once]').first()).toBeAttached();
  }
});
