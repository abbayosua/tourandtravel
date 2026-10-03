import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import { createHash } from 'crypto';

/**
 * Regresi UI/UX: form autentikasi (login/register/forgot/reset-password)
 * adalah form POST polos tanpa guard anti dobel-submit — user bisa klik dua
 * kali (koneksi lambat) dan mengirim permintaan ganda tanpa umpan balik.
 * Sekarang memakai data-submit-once: tombol dinonaktifkan + spinner.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const TOKEN = 'e2etoken123';
const EMAIL = 'e2e-submit@t.local';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

test.beforeAll(() => {
  mysql(`DELETE FROM password_resets WHERE email='${EMAIL}'`);
  const hash = createHash('sha256').update(TOKEN).digest('hex');
  mysql(
    `INSERT INTO password_resets (email, token_hash, expires_at) VALUES ('${EMAIL}','${hash}', DATE_ADD(NOW(), INTERVAL 1 HOUR))`
  );
});

test.afterAll(() => {
  mysql(`DELETE FROM password_resets WHERE email='${EMAIL}'`);
});

test('form login menampilkan loading & mencegah dobel-submit', async ({ page }) => {
  await page.goto(`${BASE}/login.php?lang=id`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', 'secret123');

  const btn = page.locator('form[data-submit-once] button[type="submit"]');

  // Kirim event submit (tanpa navigasi) → handler guard harus mengunci tombol.
  await page.evaluate(() => {
    const f = document.querySelector('form[data-submit-once]')!;
    f.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
  });

  await expect(btn).toBeDisabled();
  await expect(btn.locator('.spinner-border')).toHaveCount(1);
  await expect(btn).toHaveScreenshot('login-submit-loading.png', { maxDiffPixelRatio: 0.15 });

  // Submit kedua diabaikan karena tombol sudah terkunci.
  const stillDisabled = await page.evaluate(() => {
    const f = document.querySelector('form[data-submit-once]') as HTMLFormElement;
    f.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    return (f.querySelector('button[type="submit"]') as HTMLButtonElement).disabled;
  });
  expect(stillDisabled).toBe(true);
});

test('form auth memakai guard anti dobel-submit', async ({ page }) => {
  const pages = ['login.php', 'register.php', 'forgot-password.php', `reset-password.php?token=${TOKEN}`];
  for (const path of pages) {
    await page.goto(`${BASE}/${path}${path.includes('?') ? '&' : '?'}lang=id`);
    await expect(page.locator('form[data-submit-once]')).toHaveCount(1);
  }
});
