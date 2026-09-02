import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const USER_EMAIL = `e2e_${Date.now()}@example.com`;

test.describe('Local Auth - Admin Login', () => {
  test('admin logs in via admin panel', async ({ page }) => {
    await page.goto(`${BASE}/admin/login.php`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    expect(page.url()).toContain('/admin/');
    const body = await page.textContent('body');
    expect(body).toContain('Dashboard');
  });
});

test.describe('Local Auth - User Register & Login', () => {
  test('register a new user', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="name"]', 'E2E User');
    await page.fill('input[name="email"]', USER_EMAIL);
    await page.fill('input[name="phone"]', '08123456789');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // auto-login: redirected to index.php after successful registration
    expect(page.url()).toContain('index.php');
    const body = await page.textContent('body');
    expect(body).toContain('E2E User');
    console.log('Register redirect body has success:', /berhasil|success/i.test(body));
  });

  test('login as the registered user', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.waitForLoadState('networkidle');

    await page.fill('input[name="email"]', USER_EMAIL);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // logged-in users are redirected to homepage
    expect(page.url()).toContain(`${BASE}/`);
    const body = await page.textContent('body');
    expect(body).not.toContain('Login');
  });
});
