import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

const ADMIN_FILES = [
  'dashboard.php', 'tours.php', 'tour-add.php', 'tour-edit.php',
  'hotels.php', 'hotel-edit.php', 'flights.php', 'flight-edit.php',
  'ferries.php', 'ferry-edit.php', 'rental-cars.php', 'rental-car-edit.php',
  'bookings.php', 'currency-settings.php', 'wa-settings.php', 'wa-test.php', 'wa-ajax.php',
];

test.describe('Abuse - User biasa akses admin', () => {

  test('login sebagai user biasa, akses dashboard admin → redirect login', async ({ page }) => {
    // Register user biasa
    const email = `useradmin_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Normal User');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('index.php'); // auto-login sukses

    // Coba akses admin dashboard — harus redirect ke admin/login.php
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('admin/login.php');
  });

  test('user biasa akses semua admin files → redirect login', async ({ page }) => {
    const email = `useradmin2_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Normal User 2');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    for (const file of ADMIN_FILES) {
      await page.goto(`${BASE}/admin/${file}`, { waitUntil: 'load' });
      await page.waitForLoadState('load');
      expect(page.url(), `${file} harus redirect ke admin login`).toContain('admin/login.php');
    }
  });
});