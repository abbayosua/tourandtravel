import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';

test.describe('Abuse - POST booking tanpa login (guest checkout)', () => {

  test('rental POST tanpa login: guest booking diperbolehkan (200, sukses)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, {
      form: { days: '2', name: 'NoAuth', phone: '0812' },
    });
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).toMatch(/Booking berhasil/i);
  });

  test('hotel POST tanpa login: guest booking sukses (user_id NULL)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, {
      form: { checkin: '2027-10-01', checkout: '2027-10-03', name: 'NoAuth', phone: '0812' },
    });
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).toMatch(/Booking berhasil/i);
  });

  test('tour-detail POST tanpa login: guest booking diperbolehkan', async ({ page }) => {
    // Guest checkout sekarang diizinkan — tanpa paspor harus error validasi, bukan redirect login
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`, {
      form: {
        form_submitted: '1',
        tour_date_id: '1',
        name: 'NoAuth',
        email: 'x@y.com',
        phone: '0812',
        participants: '1',
      },
    });
    // Tidak redirect ke login; tampil halaman dengan error (paspor wajib) atau sukses
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).not.toContain('location: login.php');
    expect(body).not.toMatch(/Fatal error/i);
  });
});