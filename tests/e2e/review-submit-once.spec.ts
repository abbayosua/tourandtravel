import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: form ulasan (review) harus memakai guard anti double-submit
 * (data-submit-once) supaya tidak mengirim ulasan ganda saat tombol diklik
 * berkali-kali. Diuji pada form ulasan hotel-detail.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-review@t.local';
const AUTH_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Review', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
});

test.afterAll(() => {
  try {
    mysql(`DELETE FROM users WHERE email = '${AUTH_EMAIL}'`);
  } catch {
    /* ignore */
  }
});

test('form ulasan hotel menampilkan loading saat submit', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=id`);

  const form = page.locator('form[action="hotel-review-submit.php"]');
  await expect(form).toBeVisible();
  await form.locator('textarea[name="comment"]').fill('Ulasan uji E2E');
  const btn = form.locator('button[type="submit"]');
  await expect(btn).toBeEnabled();

  const state = await page.evaluate(() => {
    const f = document.querySelector('form[action="hotel-review-submit.php"]') as HTMLFormElement;
    f.addEventListener('submit', (e) => e.preventDefault(), { capture: true, once: true });
    f.requestSubmit();
    const b = f.querySelector('button[type="submit"]') as HTMLButtonElement;
    return { disabled: b.disabled, text: (b.textContent || '').trim() };
  });

  expect(state.disabled).toBe(true);
  expect(state.text).toContain('Memproses');

  await expect(btn).toBeDisabled();
  await expect(btn).toHaveScreenshot('hotel-review-submit-loading.png', { maxDiffPixelRatio: 0.1 });
});
