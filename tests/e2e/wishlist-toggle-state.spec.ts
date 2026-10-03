import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: tombol wishlist (footer-shared toggleWishlist) dulu gagal
 * senyap — fetch tanpa catch, tanpa state loading/error. Sekarang: tombol
 * dinonaktifkan selama request dan menampilkan state error saat gagal.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-wishlist@t.local';
const AUTH_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;

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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Wish', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));
});

test.afterAll(() => {
  if (userId) {
    mysql(`DELETE FROM wishlists WHERE user_id = ${userId}`);
    try {
      mysql(`DELETE FROM users WHERE id = ${userId}`);
    } catch {
      /* ignore */
    }
  }
});

test('wishlist toggle menampilkan state loading + error', async ({ page }) => {
  let mode: 'error' | 'success' = 'error';
  await page.route('**/wishlist-ajax.php', async (route) => {
    if (mode === 'error') {
      await new Promise((r) => setTimeout(r, 800));
      return route.fulfill({ status: 500, contentType: 'text/html', body: 'oops' });
    }
    await new Promise((r) => setTimeout(r, 800));
    return route.fulfill({ status: 200, contentType: 'application/json', body: '{"status":"added"}' });
  });

  await login(page);
  await page.goto(`${BASE}/tours.php?lang=id`);
  const btn = page.locator('.wishlist-btn').first();
  await expect(btn).toBeVisible();

  await btn.click();
  // Loading: tombol dinonaktifkan (opacity-50 + busy).
  const busy = await btn.evaluate((el) => el.classList.contains('opacity-50') || el.getAttribute('data-wl-busy') === '1');
  expect(busy).toBe(true);

  // Error: ikon peringatan + warna warning.
  await page.waitForTimeout(1000);
  const errState = await btn.evaluate((el) => ({ cls: el.className, icon: (el.querySelector('i') as HTMLElement)?.className || '' }));
  expect(errState.cls).toContain('text-warning');
  expect(errState.icon).toContain('bi-exclamation-triangle-fill');

  // Sukses: ikon hati terisi (state persisten -> aman untuk screenshot).
  mode = 'success';
  await page.waitForTimeout(900); // tunggu revert
  await btn.click();
  await expect(btn.locator('i')).toHaveClass(/bi-heart-fill/);
  await expect(btn).not.toHaveClass(/opacity-50/); // tunggu loading state selesai
  await page.evaluate(async () => {
    const f = (document as any).fonts;
    await f.load('1em "bootstrap-icons"');
    await f.ready;
  });
  await expect(btn).toHaveScreenshot('wishlist-toggle-success.png', { maxDiffPixelRatio: 0.35 });
});
