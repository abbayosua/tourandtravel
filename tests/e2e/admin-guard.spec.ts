import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const ADMIN_FILES = [
  'bookings.php', 'currency-settings.php', 'dashboard.php',
  'ferries.php', 'ferry-edit.php', 'flight-edit.php', 'flights.php',
  'hotel-edit.php', 'hotels.php', 'rental-car-edit.php', 'rental-cars.php',
  'tour-add.php', 'tour-edit.php', 'tours.php',
  'wa-ajax.php', 'wa-settings.php', 'wa-test.php',
];

test.describe('Admin guard - tanpa session', () => {

  for (const file of ADMIN_FILES) {
    test(`${file} redirect ke admin/login.php`, async ({ page }) => {
      const resp = await page.goto(`${BASE}/admin/${file}`, { waitUntil: 'load' });
      await page.waitForLoadState('load');
      // cekLogin redirects to BASE_URL/admin/login.php
      expect(page.url()).toContain('admin/login.php');
    });
  }
});

test.describe('Admin login/logout', () => {

  test('login salah: pesan error', async ({ page }) => {
    await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'salahpassword');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/error|salah|tidak valid|invalid/i);
    // masih di halaman login
    expect(page.url()).toContain('admin/login.php');
  });

  test('login benar: redirect ke dashboard', async ({ page }) => {
    await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('admin/dashboard.php');
    const body = await page.textContent('body');
    expect(body).toMatch(/Dashboard|admin/i);
  });

  test('logout: session hilang, redirect login', async ({ page }) => {
    // Login dulu
    await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    // Logout
    await page.goto(`${BASE}/admin/logout.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('admin/login.php');

    // Coba akses dashboard — harus redirect login
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('admin/login.php');
  });
});