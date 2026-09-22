import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning:|Parse error|Uncaught)/i;
const USER = `booker_${Date.now()}@example.com`;

async function registerUser(page: any) {
  await page.goto(`${BASE}/register.php`);
  await page.fill('input[name="name"]', 'Booker User');
  await page.fill('input[name="email"]', USER);
  await page.fill('input[name="phone"]', '0812345000');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test.describe('Tour booking happy path', () => {
  test('TC-205 booking tour sukses → kode + tersimpan + muncul di my-bookings', async ({ page }) => {
    await registerUser(page);

    // ambil tour_date valid dari DB via halaman detail (tour_date 180 → tour 178 Chongqing)
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=chongqing-wulong-karst-national-park-day-tour`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(200);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    await page.selectOption('select[name="tour_date_id"]', { index: 1 });
    await page.fill('input[name="name"]', 'Booker User');
    await page.fill('input[name="email"]', USER);
    await page.fill('input[name="phone"]', '0812345000');
    await page.fill('input[name="participants"]', '2');
    await page.fill('textarea[name="notes"]', 'E2E booking test');

    // upload passport (wajib)
    await page.setInputFiles('input[type="file"]', {
      name: 'passport.png',
      mimeType: 'image/png',
      buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        'base64'
      ),
    });

    await page.click('#bookingSubmitBtn');
    await page.waitForLoadState('networkidle');

    const after = await page.textContent('body');
    expect(after).not.toMatch(PHP_ERROR);
    // sukses: kode booking TAT-XXXX tampil / redirect booking-success
    expect(after).toMatch(/TAT-[A-Z0-9]+/);
  });

  test('TC-205b booking muncul di my-bookings user', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', USER);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/my-bookings.php`);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/TAT-[A-Z0-9]+/);
  });
});

test.describe('Tour booking sad path', () => {
  const SP = 'chongqing-wulong-karst-national-park-day-tour';

  async function openForm(page: any) {
    await page.goto(`${BASE}/tour-detail.php?slug=${SP}`, { waitUntil: 'load' });
    await page.selectOption('select[name="tour_date_id"]', { index: 1 });
  }

  test('TC-206a nama/telepon kosong ditolak', async ({ page }) => {
    await registerUser(page);
    await openForm(page);
    await page.fill('input[name="name"]', '');
    await page.fill('input[name="phone"]', '');
    await page.fill('input[name="participants"]', '1');
    await page.setInputFiles('input[type="file"]', {
      name: 'p.png', mimeType: 'image/png',
      buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'),
    });
    await page.click('#bookingSubmitBtn');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Nama harus diisi|Name is required|WhatsApp/i);
    expect(page.url()).toContain('tour-detail.php');
  });

  test('TC-206b peserta < 1 ditolak (server-side)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=${SP}`, {
      form: { form_submitted: '1', tour_date_id: '180', name: 'Booker User', email: USER, phone: '0812345000', participants: '0', notes: '' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/minimal 1|at least 1/i);
  });

  test('TC-206c tanpa foto paspor ditolak', async ({ page }) => {
    await registerUser(page);
    await openForm(page);
    await page.fill('input[name="name"]', 'Booker User');
    await page.fill('input[name="phone"]', '0812345000');
    await page.fill('input[name="participants"]', '1');
    await page.click('#bookingSubmitBtn');
    await page.waitForLoadState('networkidle');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/paspor|passport/i);
  });

  test('TC-206d tour_date_id invalid (tanggal tak valid) ditolak', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/tour-detail.php?slug=${SP}`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Booker User');
    await page.fill('input[name="phone"]', '0812345000');
    await page.fill('input[name="participants"]', '1');
    await page.setInputFiles('input[type="file"]', {
      name: 'p.png', mimeType: 'image/png',
      buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'),
    });
    // paksa tour_date_id=999999999 via request POST langsung
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=${SP}`, {
      form: { form_submitted: '1', tour_date_id: '999999999', name: 'Booker User', email: USER, phone: '0812345000', participants: '1', notes: '' },
    });
    const text = await resp.text();
    expect(text).toMatch(/Tanggal keberangkatan tidak valid|Invalid departure/i);
  });

  test('TC-206e peserta melebihi sisa slot ditolak (server-side)', async ({ page }) => {
    await registerUser(page);
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=${SP}`, {
      form: { form_submitted: '1', tour_date_id: '180', name: 'Booker User', email: USER, phone: '0812345000', participants: '99999', notes: '' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/sisa slot|hanya|only/i);
  });
});
