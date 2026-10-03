import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: ikon sosial di footer dulu dead link (href="#"). Sekarang URL
 * media sosial diatur di admin/brand-settings.php dan footer hanya menampilkan
 * ikon yang URL-nya diisi.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const URL = 'https://instagram.com/e2ebrand';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}
function setSetting(k: string, v: string) {
  mysql(`INSERT INTO settings (setting_key, setting_value) VALUES ('${k}','${v}') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)`);
}

let prev = '';

test.beforeAll(() => {
  prev = mysql(`SELECT setting_value FROM settings WHERE setting_key='social_instagram'`);
});

test.afterAll(() => {
  setSetting('social_instagram', prev);
});

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('URL media sosial mengisi ikon footer', async ({ page }) => {
  await adminLogin(page);
  await page.goto(`${BASE}/admin/brand-settings.php?lang=id`);
  await page.fill('[data-testid="brand-social-instagram"]', URL);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('[data-testid="brand-save"]')]);

  await page.goto(`${BASE}/index.php?lang=id`);
  const ig = page.locator('[data-testid="social-instagram"]');
  await expect(ig).toHaveAttribute('href', URL);
  await expect(ig).toHaveAttribute('target', '_blank');
  // Yang belum diisi tidak dirender (bukan dead link).
  await expect(page.locator('[data-testid="social-facebook"]')).toHaveCount(0);

  await expect(ig.locator('..')).toHaveScreenshot('footer-social.png', { maxDiffPixelRatio: 0.15 });
});
