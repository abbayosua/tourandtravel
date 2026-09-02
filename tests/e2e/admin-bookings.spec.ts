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

test.describe('Admin Bookings', () => {

  test('list render: 5 booking', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kelola Booking/i);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBeGreaterThanOrEqual(4);
  });

  test('filter status: pending rows exist', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?status=pending`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBeGreaterThanOrEqual(2);
  });

  test('filter status: confirmed → 2', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?status=confirmed`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(2);
  });

  test('filter status: cancelled → empty state', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?status=cancelled`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Belum ada booking/i);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(1);
  });

  test('filter status invalid: empty state', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?status=invalid_status_xyz`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Belum ada booking/i);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(1);
  });

  test('update_status pending→confirmed: msg updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?update_status=2&status=confirmed`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('bookings.php?msg=updated');
    const body = await page.textContent('body');
    expect(body).toMatch(/Status booking berhasil diperbarui/i);
    expect(body).toMatch(/Confirmed/i);

    // Restore back to pending
    await page.goto(`${BASE}/admin/bookings.php?update_status=2&status=pending`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
  });

  test('update_status invalid: tidak redirect, tidak ada msg', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?update_status=2&status=blahblah`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).not.toMatch(/berhasil diperbarui/i);
  });

  test('delete invalid (id=999): tidak crash, redirect', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(page.url()).toContain('bookings.php?msg=deleted');
  });

  test('delete valid: msg deleted + row berkurang', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php`, { waitUntil: 'load' });
    const before = await page.locator('table tbody tr').count();
    expect(before).toBeGreaterThan(0);

    // Cari baris TAT-DEL01. Jika tidak ada (terhapus run sebelumnya), skip.
    const row = page.locator('tr:has-text("TAT-DEL01")').first();
    if (await row.count() === 0) {
      console.log('SKIP: TAT-DEL01 not found, cannot test delete');
      return;
    }

    const deleteHref = await row.locator('a[href*="delete="]').first().getAttribute('href');
    const id = new URL(deleteHref, BASE).searchParams.get('delete');
    expect(id).toBeTruthy();

    await page.goto(`${BASE}/admin/bookings.php?delete=${id}`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('bookings.php?msg=deleted');
    const body = await page.textContent('body');
    expect(body).toMatch(/Booking berhasil dihapus/i);

    const after = await page.locator('table tbody tr').count();
    expect(after).toBe(before - 1);
  });
});