import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning:|Parse error|Uncaught)/i;
const QS = 'company=ASDP&from=Merak&to=Bakauheni&date=2026-12-20&time=08:00&price=350000&passengers=2&vessel=Ferry%20Test';
    const QS1 = 'company=ASDP&from=Merak&to=Bakauheni&date=2026-12-20&time=08:00&price=350000&passengers=1&vessel=Ferry%20Test';

test.describe('Ferry booking happy + sad', () => {
  test('TC-501a booking ferry sukses → kode FB + total = price × pax', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS}`, { waitUntil: 'load' });
    const body0 = await page.textContent('body');
    expect(body0).not.toMatch(PHP_ERROR);

    await page.fill('[data-testid="input-name"]', 'Ferry Tester');
    await page.fill('[data-testid="input-email"]', `ferry_${Date.now()}@example.com`);
    await page.fill('[data-testid="input-phone"]', '08123456789');
    await page.fill('[data-testid="input-pax-1"]', 'Ferry Tester');
    await page.fill('[data-testid="input-pax-2"]', 'Pax Two');
    await page.click('[data-testid="btn-confirm-booking"]');
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/FB[A-Z0-9]{10}/);
    // total = 350000 × 2
    expect(body).toMatch(/700\.000|700,000|1,050,000|Rp\s?700/);
  });

  test('TC-501b booking tersimpan (1 pax) → kode FB', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS1}`, { waitUntil: 'load' });
    await page.fill('[data-testid="input-name"]', 'Ferry Second');
    await page.fill('[data-testid="input-email"]', `ferry2_${Date.now()}@example.com`);
    await page.fill('[data-testid="input-phone"]', '08123456788');
    await page.fill('[data-testid="input-pax-1"]', 'Ferry Second');
    await page.click('[data-testid="btn-confirm-booking"]');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/FB[A-Z0-9]{10}/);
  });

  test('TC-502a nama < 2 karakter ditolak', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS}`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'A');
    await page.fill('input[name="email"]', `bad_${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', '08123456789');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/wajib diisi|required/i);
    expect(body).not.toMatch(/FB[A-Z0-9]{10}/);
  });

  test('TC-502b email invalid ditolak (server-side)', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS}`, { waitUntil: 'load' });
    const resp = await page.request.post(`${BASE}/ferry-booking.php?${QS}`, {
      form: { name: 'Ferry Tester', email: 'bukan-email', phone: '08123456789', passengers: '2', pax_name_2: 'Pax Two' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/Email tidak valid|invalid email/i);
    expect(text).not.toMatch(/FB[A-Z0-9]{10}/);
  });

  test('TC-502c telepon < 8 digit ditolak (server-side)', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS}`, { waitUntil: 'load' });
    const resp = await page.request.post(`${BASE}/ferry-booking.php?${QS}`, {
      form: { name: 'Ferry Tester', email: `bad2_${Date.now()}@example.com`, phone: '081', passengers: '2', pax_name_2: 'Pax Two' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/telepon tidak valid|phone/i);
    expect(text).not.toMatch(/FB[A-Z0-9]{10}/);
  });

  test('TC-502d pax nama kosong utk multi-penumpang ditolak', async ({ page }) => {
    await page.goto(`${BASE}/ferry-booking.php?${QS}`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Ferry Tester');
    await page.fill('input[name="email"]', `bad3_${Date.now()}@example.com`);
    await page.fill('input[name="phone"]', '08123456789');
    const pax2 = page.locator('input[name="pax_name_2"]');
    if (await pax2.count()) await pax2.fill('');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/penumpang.*wajib|wajib diisi|required/i);
    expect(body).not.toMatch(/FB[A-Z0-9]{10}/);
  });
});
