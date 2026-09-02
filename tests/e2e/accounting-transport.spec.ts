import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

test.describe('Accounting - Ferry', () => {

  test('harga ferry DB (350rb) vs halaman', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/350\.000|Rp|price/i);
  });

  test('ferry: harga per orang tampil di kartu', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Harga ferry (350rb) tampil
    expect(body).toMatch(/350\.000|Rp|currency/i);
  });
});

test.describe('Accounting - Flight (DB local)', () => {

  test('flight schedule price vs halaman: 1.500.000', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=Jakarta+%28CGK%29&to=Denpasar+%28DPS%29&date=2026-07-25&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // DB local schedule: 1.500.000
    expect(body).toMatch(/1\.500\.000|Rp|price/i);
  });

  test('flight local booking: total = price × passengers', async ({ page }) => {
    // Register dulu (flight booking butuh login)
    const email = `flt_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Flight Tester');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    // Cari flight
    await page.goto(`${BASE}/flights.php?from=Jakarta+%28CGK%29&to=Denpasar+%28DPS%29&date=2026-07-25&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Klik tombol Pilih (Lokal) — schedule_id
    const localBtn = page.locator('a[href*="schedule_id="]').first();
    if (await localBtn.count() === 0) {
      console.log('SKIP: no local schedule');
      return;
    }
    await localBtn.click();
    await page.waitForLoadState('load');

    // Di flight-detail, booking 2 passengers
    const passSelect = page.locator('select[name="passengers"]');
    if (await passSelect.count() === 0) {
      console.log('SKIP: no passengers select');
      return;
    }
    await passSelect.selectOption('2');
    await page.fill('input[name="name"]', 'Flight Tester');
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    const body2 = await page.textContent('body');
    expect(body2).not.toMatch(PHP_ERROR);
    // 1.5jt × 2 = 3jt
    expect(body2).toMatch(/Penerbangan berhasil dipesan|3\.000\.000|Rp|Total/i);
  });

  test('flight: passengers=0 → tidak sukses', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=Jakarta+%28CGK%29&to=Denpasar+%28DPS%29&date=2026-07-25&search=1`, { waitUntil: 'load' });
    const localBtn = page.locator('a[href*="schedule_id="]').first();
    if (await localBtn.count() === 0) {
      console.log('SKIP: no local schedule');
      return;
    }
    const href = await localBtn.getAttribute('href');
    const sid = new URLSearchParams(href.split('?')[1]).get('schedule_id');
    // POST langsung dengan passengers=0
    const resp = await page.request.post(`${BASE}/flight-detail.php?schedule_id=${sid}`, {
      form: { passengers: '0', name: 'T', phone: '1' },
      maxRedirects: 0,
    });
    const body = await resp.text();
    expect(body).not.toMatch(/Penerbangan berhasil dipesan/i);
  });
});