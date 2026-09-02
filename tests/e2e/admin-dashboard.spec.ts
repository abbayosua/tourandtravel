import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Admin Dashboard', () => {

  test('dashboard render: stat cards + tidak ada PHP error', async ({ page }) => {
    await loginAdmin(page);
    expect(page.url()).toContain('admin/dashboard.php');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Dashboard/i);

    // Stat cards: tours, bookings, pending, confirmed, revenue
    const statCards = await page.locator('.stat-card').count();
    expect(statCards).toBeGreaterThanOrEqual(4);
  });

  test('stat cards berisi angka (tours, bookings, pending, confirmed, revenue)', async ({ page }) => {
    await loginAdmin(page);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tour|Paket|Booking|Pending|Confirmed|Revenue|Pendapatan|Rp/i);
  });

  test('daftar booking terbaru di dashboard', async ({ page }) => {
    await loginAdmin(page);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tabel booking atau daftar booking
    expect(body).toMatch(/Booking|Pemesanan|TAT-|HUNAN|TOKYO|NEW ZEALAND/i);
  });

  test('semua link modul admin tidak 404', async ({ page }) => {
    await loginAdmin(page);

    const modules = [
      { name: 'tours', url: 'admin/tours.php' },
      { name: 'tour-add', url: 'admin/tour-add.php' },
      { name: 'hotels', url: 'admin/hotels.php' },
      { name: 'flights', url: 'admin/flights.php' },
      { name: 'ferries', url: 'admin/ferries.php' },
      { name: 'rental-cars', url: 'admin/rental-cars.php' },
      { name: 'bookings', url: 'admin/bookings.php' },
      { name: 'currency-settings', url: 'admin/currency-settings.php' },
      { name: 'wa-settings', url: 'admin/wa-settings.php' },
    ];

    for (const mod of modules) {
      const resp = await page.goto(`${BASE}/${mod.url}`, { waitUntil: 'domcontentloaded' });
      // Must be 200, not 404
      const status = resp?.status();
      expect(status, `${mod.name} should be 200, got ${status}`).toBe(200);
      // No PHP error
      const body = await page.textContent('body');
      expect(body, `${mod.name} has PHP error`).not.toMatch(PHP_ERROR);
    }
  });
});