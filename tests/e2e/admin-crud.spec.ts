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

test.describe('Admin Hotels CRUD', () => {

  test('list render: 20 hotel', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotels.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(20);
  });

  test('edit id valid: form terisi', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotel-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const nameVal = await page.locator('input[name="name"]').inputValue();
    expect(nameVal).toMatch(/Grand Hyatt/i);
  });

  test('edit id=0: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotel-edit.php?id=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('save perubahan: msg updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotel-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Grand Hyatt Bali E2E');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php?msg=updated');
    // restore
    await page.goto(`${BASE}/admin/hotel-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Grand Hyatt Bali');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
  });

  test('delete invalid (id=999): tidak crash, redirect', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotels.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php?msg=deleted');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Admin Flights CRUD', () => {

  test('list render: 12 flight', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/flights.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(12);
  });

  test('edit id valid: form terisi', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/flight-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const airline = await page.locator('input[name="airline"]').inputValue();
    expect(airline).toMatch(/Garuda/i);
  });

  test('edit id=0: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/flight-edit.php?id=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('save perubahan: msg updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/flight-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="flight_number"]', 'GA-E2E');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('flights.php?msg=updated');
    // restore
    await page.goto(`${BASE}/admin/flight-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="flight_number"]', 'GA-888');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
  });

  test('delete invalid (id=999): tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/flights.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Admin Ferries CRUD', () => {

  test('list render: 8 ferry', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/ferries.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(8);
  });

  test('edit id valid: form terisi', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/ferry-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const company = await page.locator('input[name="company"]').inputValue();
    expect(company).toMatch(/ASDP/i);
  });

  test('edit id=0: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/ferry-edit.php?id=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('save perubahan: msg updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/ferry-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="vessel_name"]', 'KMP E2E');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('ferries.php?msg=updated');
    // restore
    await page.goto(`${BASE}/admin/ferry-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="vessel_name"]', 'KMP Portlink III');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
  });

  test('delete invalid: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/ferries.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});

test.describe('Admin Rental Cars CRUD', () => {

  test('list render: 10 rental car', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/rental-cars.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const rows = await page.locator('table tbody tr').count();
    expect(rows).toBe(10);
  });

  test('edit id valid: form terisi', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/rental-car-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const name = await page.locator('input[name="name"]').inputValue();
    expect(name).toMatch(/Avanza/i);
  });

  test('edit id=0: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/rental-car-edit.php?id=0`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('save perubahan: msg updated', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/rental-car-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="car_type"]', 'MPV-E2E');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('rental-cars.php?msg=updated');
    // restore
    await page.goto(`${BASE}/admin/rental-car-edit.php?id=1`, { waitUntil: 'load' });
    await page.fill('input[name="car_type"]', 'MVP');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
  });

  test('delete invalid: tidak crash', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/rental-cars.php?delete=999`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });
});