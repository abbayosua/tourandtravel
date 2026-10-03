import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: setelah mengirim ulasan hotel (redirect ?review=success),
 * halaman hotel-detail harus menampilkan pesan sukses. Sebelumnya redirect
 * itu tidak ditangani sehingga tidak ada feedback.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-hreview@t.local';
const AUTH_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;

function cleanupReviews() {
  if (!userId) return;
  mysql(`DELETE FROM review_subratings WHERE review_id IN (SELECT id FROM reviews WHERE user_id = ${userId})`);
  mysql(`DELETE FROM review_images WHERE review_id IN (SELECT id FROM reviews WHERE user_id = ${userId})`);
  mysql(`DELETE FROM reviews WHERE user_id = ${userId}`);
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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E HReview', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));
  cleanupReviews();
});

test.afterAll(() => {
  cleanupReviews();
  try {
    mysql(`DELETE FROM users WHERE id = ${userId}`);
  } catch {
    /* ignore */
  }
});

test('kirim ulasan hotel menampilkan pesan sukses', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=id`);

  const form = page.locator('form[action="hotel-review-submit.php"]');
  await form.locator('textarea[name="comment"]').fill('Ulasan uji E2E — hotel bagus.');
  await Promise.all([
    page.waitForLoadState('domcontentloaded'),
    form.locator('button[type="submit"]').click(),
  ]);

  await expect(page).toHaveURL(/review=success/);
  await expect(page.locator('[data-testid="review-success"]')).toContainText('Ulasan berhasil dikirim');
  await expect(page.locator('[data-testid="review-success"]')).toHaveScreenshot('hotel-review-success.png', {
    maxDiffPixelRatio: 0.1,
  });
});
