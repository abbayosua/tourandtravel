import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: itinerary builder di tour-detail (modal "Simpan ke Itinerary")
 * dulu tidak punya error state — bila request itinerary-ajax gagal, tak ada
 * umpan balik apa pun (dan callback null bisa melempar). Sekarang ada pesan error.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const PASS = 'tmpcheck123';
const EMAIL = 'e2e-itin-builder@t.local';
const SLUG = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(PASS)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
  mysql(
    `INSERT INTO users (name,email,password_hash,role) VALUES ('E2E ItinBuilder','${EMAIL}','${hash}','user') ` +
      `ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)`
  );
});

test.afterAll(() => {
  const uid = mysql(`SELECT id FROM users WHERE email='${EMAIL}'`);
  if (uid) {
    mysql(`DELETE FROM user_itineraries WHERE user_id=${uid}`);
  }
  mysql(`DELETE FROM users WHERE email='${EMAIL}'`);
});

async function login(page: Page) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('form[data-submit-once] button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test('gagal simpan itinerary menampilkan pesan error', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`);

  // GET (list) dibiarkan sukses; POST (create) digagalkan.
  await page.route('**/itinerary-ajax.php*', (route) =>
    route.request().method() === 'POST' ? route.abort('failed') : route.continue()
  );

  await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
  const title = page.locator('#itinTitle');
  await expect(title).toBeVisible();
  await title.fill('Trip E2E');
  await page.locator('#itinCreateBtn').click();

  const status = page.locator('[data-testid="itin-status"]');
  await expect(status).toBeVisible();
  await expect(status).toContainText('Terjadi kesalahan');
  await expect(status).toHaveScreenshot('itinerary-builder-error.png', { maxDiffPixelRatio: 0.15 });
});
