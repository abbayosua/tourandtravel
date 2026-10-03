import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: menghapus item dari itinerary builder (tour-detail) harus
 * meminta konfirmasi dulu, supaya tidak terhapus karena salah klik.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-itin@t.local';
const AUTH_PASS = 'e2epass123';
const TOUR = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
const CONFIRM_TEXT = 'Hapus item ini dari itinerary?';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;

function cleanupItineraries() {
  if (!userId) return;
  mysql(`DELETE FROM user_itinerary_items WHERE day_id IN (SELECT id FROM user_itinerary_days WHERE itinerary_id IN (SELECT id FROM user_itineraries WHERE user_id = ${userId}))`);
  mysql(`DELETE FROM user_itinerary_days WHERE itinerary_id IN (SELECT id FROM user_itineraries WHERE user_id = ${userId})`);
  mysql(`DELETE FROM user_itineraries WHERE user_id = ${userId}`);
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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Itin', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));
  cleanupItineraries();
});

test.afterAll(() => {
  cleanupItineraries();
  try {
    mysql(`DELETE FROM users WHERE id = ${userId}`);
  } catch {
    /* ignore */
  }
});

test('hapus item itinerary meminta konfirmasi', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${TOUR}&lang=id`);

  // Buka builder, buat itinerary, tambah 1 item.
  await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
  await expect(page.locator('#itinBuilderModal')).toBeVisible();
  await page.locator('#itinTitle').fill('E2E Trip');
  await page.locator('#itinCreateBtn').click();
  await expect(page.locator('#itinDays .itin-day')).toHaveCount(1);
  await page.locator('#itinItemTitle').fill('E2E Aktivitas');
  await page.locator('#itinAddItemBtn').click();
  await expect(page.locator('#itinDays .itin-del').first()).toBeVisible();
  await expect(page.locator('#itinDays')).toHaveScreenshot('itinerary-item-with-delete.png', {
    maxDiffPixelRatio: 0.1,
  });

  const del = page.locator('#itinDays .itin-del').first();
  const itemId = await del.getAttribute('data-item');
  const sel = `#itinDays .itin-del[data-item="${itemId}"]`;

  // Dismiss: item tetap ada.
  let msg = '';
  page.once('dialog', (d) => {
    msg = d.message();
    d.dismiss();
  });
  await del.click();
  expect(msg).toBe(CONFIRM_TEXT);
  await expect(page.locator(sel)).toHaveCount(1);

  // Accept: item terhapus.
  page.once('dialog', (d) => d.accept());
  await page.locator(sel).click();
  await expect(page.locator(sel)).toHaveCount(0);
});
