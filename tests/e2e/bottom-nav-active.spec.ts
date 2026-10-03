import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX (mobile): bottom-nav dulu tidak menyorot tab apa pun di
 * halaman detail maupun sebagian halaman akun. Sekarang halaman detail masuk
 * ke tab "Cari" dan wallet/itinerary masuk ke tab "Akun".
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const PASS = 'tmpcheck123';
const EMAIL = 'e2e-bottomnav@t.local';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.use({ viewport: { width: 390, height: 844 } });

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(PASS)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E BottomNav','${EMAIL}','${hash}','user') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM users WHERE email='${EMAIL}'`);
});

async function login(page: Page) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('form[data-submit-once] button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('bottom nav menyorot tab Cari di halaman detail', async ({ page }) => {
  const pages = [
    'tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town',
    'hotel-detail.php?slug=grand-hyatt-bali',
    'faq.php',
    'tours.php',
  ];
  for (const p of pages) {
    await page.goto(`${BASE}/${p}${p.includes('?') ? '&' : '?'}lang=id`);
    await expect(page.locator('.bottom-nav a[href="tours.php"]')).toHaveClass(/active/);
  }

  await page.goto(`${BASE}/tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town&lang=id`);
  await expect(page.locator('.bottom-nav')).toHaveScreenshot('bottom-nav-detail-active.png', { maxDiffPixelRatio: 0.1 });
});

test('bottom nav menyorot tab Akun di halaman akun', async ({ page }) => {
  await login(page);
  for (const p of ['wallet.php', 'my-itinerary.php', 'my-points.php']) {
    await page.goto(`${BASE}/${p}?lang=id`);
    await expect(page.locator('.bottom-nav a[href="profile.php"]')).toHaveClass(/active/);
  }
});
