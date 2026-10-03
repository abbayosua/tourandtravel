import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: hapus itinerary di my-itinerary.php dulu menelan error senyap
 * (catch kosong) — tombol tidak memberi umpan balik apa pun bila request gagal.
 * Sekarang ada loading state + pesan error saat gagal.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const PASS = 'tmpcheck123';
const EMAIL = 'e2e-itin@t.local';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(PASS)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E Itin','${EMAIL}','${hash}','user') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)`
  );
  const uid = mysql(`SELECT id FROM users WHERE email='${EMAIL}'`);
  mysql(`DELETE FROM user_itineraries WHERE user_id=${uid}`);
  mysql(`INSERT INTO user_itineraries (user_id, title) VALUES (${uid}, 'E2E Itinerary')`);
});

test.afterAll(() => {
  const uid = mysql(`SELECT id FROM users WHERE email='${EMAIL}'`);
  if (uid) mysql(`DELETE FROM user_itineraries WHERE user_id=${uid}`);
  mysql(`DELETE FROM users WHERE email='${EMAIL}'`);
});

async function login(page: Page) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('form[data-submit-once] button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('gagal hapus itinerary menampilkan pesan error', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/my-itinerary.php?lang=id`);

  const delBtn = page.locator('.itin-del-btn').first();
  await expect(delBtn).toBeVisible();

  await page.route('**/itinerary-ajax.php*', (route) => route.abort('failed'));
  page.on('dialog', (d) => d.accept());
  await delBtn.click();

  const msg = page.locator('[data-testid="itin-error"]');
  await expect(msg).toBeVisible();
  await expect(msg).toContainText('Gagal menghapus itinerary');
  await expect(delBtn).toBeEnabled();

  await expect(msg).toHaveScreenshot('itinerary-delete-error.png', { maxDiffPixelRatio: 0.15 });
});
