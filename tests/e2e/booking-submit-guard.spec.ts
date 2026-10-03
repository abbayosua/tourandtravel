import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: guard anti double-submit (data-submit-once di footer-shared)
 * harus menonaktifkan tombol + menampilkan spinner di form booking lain,
 * bukan hanya hotel-detail. Di sini diuji pada attraction-detail.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-guard@t.local';
const AUTH_PASS = 'e2epass123';
const ATTRACTION = 'tiket-masuk-taman-mini-indonesia-indah';

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
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Guard', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
});

test.afterAll(() => {
  try {
    mysql(`DELETE FROM users WHERE email = '${AUTH_EMAIL}'`);
  } catch {
    // abaikan
  }
});

test('attraction booking form menampilkan loading saat submit', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/attraction-detail.php?slug=${ATTRACTION}&lang=id`);

  const form = page.locator('form[data-submit-once]').first();
  await expect(form).toBeVisible();
  const btn = form.locator('button[type="submit"]');
  await expect(btn).toBeEnabled();

  // Isi field wajib agar constraint validation tidak memblokir submit.
  const visit = form.locator('input[name="visit_date"]');
  if (await visit.count()) await visit.fill('2026-12-01');
  await form.locator('input[name="name"]').fill('E2E Tester');
  await form.locator('input[name="phone"]').fill('08123456789');

  const state = await page.evaluate(() => {
    const f = document.querySelector('form[data-submit-once]') as HTMLFormElement;
    f.addEventListener('submit', (e) => e.preventDefault(), { capture: true, once: true });
    f.requestSubmit();
    const b = f.querySelector('button[type="submit"]') as HTMLButtonElement;
    return { disabled: b.disabled, text: (b.textContent || '').trim() };
  });

  expect(state.disabled).toBe(true);
  expect(state.text).toContain('Memproses');

  await expect(btn).toBeDisabled();
  await expect(btn).toHaveScreenshot('attraction-booking-submit-loading.png', {
    maxDiffPixelRatio: 0.1,
  });
});
