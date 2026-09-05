import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\"')}"`).toString().trim();
}
function tokenHash(token: string): string {
  return require('crypto').createHash('sha256').update(token).digest('hex');
}

test.describe('Password Reset — alur lengkap & penolakan', () => {
  const email = () => `pr${Date.now()}@test.local`;
  let e = '';

  test.beforeEach(() => { e = email(); });
  test.afterEach(() => {
    dbRun(`DELETE FROM password_resets WHERE email='${e}'; DELETE FROM users WHERE email='${e}';`);
  });

  test('alur lengkap: minta token → set password baru → login sukses', async ({ page }) => {
    // seed user
    dbRun(`INSERT INTO users (name, email, password_hash) VALUES ('PR User', '${e}', '${require("crypto").createHash("md5").update("x").digest("hex")}')`);
    // minta reset
    await page.goto(`${BASE}/forgot-password.php`);
    await page.fill('input[name="email"]', e);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/tautan reset|reset link/i);
    // token dari DB (email driver log)
    const tokenRow = dbOne(`SELECT token_hash FROM password_resets WHERE email='${e}' ORDER BY id DESC LIMIT 1`);
    expect(tokenRow).not.toBe('');
    // cari token plaintext: driver log menyimpan template; ambil dari log subject? — ekstrak dari email_log? template reset menyimpan link penuh di body → kita regenerasi: gunakan log body? body tidak disimpan.
    // Alternatif deterministik: hitung ulang tidak bisa → gunakan DB: buat token baru manual via hash yang kita tahu
    const plain = 'e2eplain' + Date.now();
    dbRun(`UPDATE password_resets SET token_hash='${tokenHash(plain)}' WHERE email='${e}' ORDER BY id DESC LIMIT 1`);
    await page.goto(`${BASE}/reset-password.php?token=${plain}`);
    await page.fill('input[name="password"]', 'newpass123');
    await page.fill('input[name="confirm_password"]', 'newpass123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/berhasil diubah|has been changed/i);

    // login dengan password baru
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', e);
    await page.fill('input[name="password"]', 'newpass123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).not.toContain('login.php');
  });

  test('token kedua kali ditolak (sekali pakai)', async ({ page }) => {
    dbRun(`INSERT INTO users (name, email, password_hash) VALUES ('PR2', '${e}', 'x')`);
    const plain = 'singleuse' + Date.now();
    dbRun(`INSERT INTO password_resets (email, token_hash, expires_at) VALUES ('${e}', '${tokenHash(plain)}', NOW() + INTERVAL 1 HOUR)`);
    // pakai pertama
    await page.goto(`${BASE}/reset-password.php?token=${plain}`);
    await page.fill('input[name="password"]', 'newpass123');
    await page.fill('input[name="confirm_password"]', 'newpass123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    // pakai kedua → ditolak
    await page.goto(`${BASE}/reset-password.php?token=${plain}`);
    expect(await page.textContent('body')).toMatch(/tidak valid|kedaluwarsa|invalid|expired/i);
  });

  test('token kedaluwarsa ditolak', async ({ page }) => {
    const plain = 'expired' + Date.now();
    dbRun(`INSERT INTO password_resets (email, token_hash, expires_at) VALUES ('${e}', '${tokenHash(plain)}', NOW() - INTERVAL 5 MINUTE)`);
    await page.goto(`${BASE}/reset-password.php?token=${plain}`);
    expect(await page.textContent('body')).toMatch(/tidak valid|kedaluwarsa|invalid|expired/i);
  });

  test('rate limit: permintaan kedua dalam 1 menit ditolak', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password.php`);
    await page.fill('input[name="email"]', e);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/tautan reset|reset link/i);
    // kedua
    await page.goto(`${BASE}/forgot-password.php`);
    await page.fill('input[name="email"]', e);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/Terlalu banyak|Too many/i);
  });

  test('email tidak ada → pesan netral (tanpa bocor akun)', async ({ page }) => {
    await page.goto(`${BASE}/forgot-password.php`);
    await page.fill('input[name="email"]', 'tidak-ada-' + email);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/Bila email terdaftar|If the email is registered/i);
    expect(await page.textContent('body')).not.toMatch(/tidak terdaftar|not registered/i);
  });
});
