import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';

test.describe('Register sad path', () => {
  test('TC-102 email duplikat ditolak', async ({ page }) => {
    const email = `sad_${Date.now()}@example.com`;
    // registrasi pertama
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Sad One');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('index.php');

    // registrasi ulang email sama → tetap di register.php + pesan error
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Sad Two');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('register.php');
    const body = await page.textContent('body');
    expect(body).toMatch(/sudah terdaftar|already registered/i);
  });

  test('TC-103a password < 6 karakter ditolak', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Short Pass');
    await page.fill('input[name="email"]', `short_${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'abc12');
    await page.fill('input[name="confirm_password"]', 'abc12');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    // HTML5 minlength memblokir submit — gagal di client-side, tetap di register
    expect(page.url()).toContain('register.php');
    const url = page.url();
    expect(url).not.toContain('index.php');
  });

  test('TC-103b konfirmasi password tidak cocok ditolak', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Mismatch');
    await page.fill('input[name="email"]', `mism_${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password999');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('register.php');
    const body = await page.textContent('body');
    expect(body).toMatch(/tidak cocok|not match/i);
  });

  test('TC-103c email invalid ditolak', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Bad Email');
    await page.fill('input[name="email"]', 'bukan-email');
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    // HTML5 type=email memblokir submit — tetap di register, tidak registrasi
    expect(page.url()).toContain('register.php');
    expect(page.url()).not.toContain('index.php');
  });

  test('TC-103d nama kosong ditolak', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', '');
    await page.fill('input[name="email"]', `empty_${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('register.php');
  });
});
