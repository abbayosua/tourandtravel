import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const EMAIL = `log_${Date.now()}@example.com`;

test.describe('Login/logout happy + sad', () => {
  test('TC-104 login kredensial benar', async ({ page }) => {
    // seed user via register
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Login Test');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="phone"]', '08129999');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // logout dulu
    await page.goto(`${BASE}/logout.php`);
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).not.toContain('login.php');
    const body = await page.textContent('body');
    expect(body).toContain('Login Test');
  });

  test('TC-105a login password salah ditolak', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', 'wrongpass99');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
    const body = await page.textContent('body');
    expect(body).toMatch(/salah|incorrect|wrong|invalid/i);
  });

  test('TC-105b login akun tidak ada ditolak', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', `ghost_${Date.now()}@nowhere.test`);
    await page.fill('input[name="password"]', 'whatever123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
    const body = await page.textContent('body');
    expect(body).toMatch(/salah|incorrect|wrong|invalid|tidak ditemukan|not found/i);
  });

  test('TC-106 logout menghapus session', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    expect(page.url()).not.toContain('login.php');

    await page.goto(`${BASE}/logout.php`);
    await page.waitForLoadState('networkidle');
    // setelah logout, halaman user terlindungi harus redirect ke login
    await page.goto(`${BASE}/my-bookings.php`);
    await page.waitForLoadState('networkidle');
    expect(page.url()).toContain('login.php');
  });
});
