import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';

test.describe('Abuse - POST booking tanpa login (lintas layanan)', () => {

  test('rental POST tanpa login: 302 → login.php', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/rental-car-detail.php?slug=toyota-avanza-jakarta`, {
      form: { days: '2', name: 'NoAuth', phone: '0812' },
      maxRedirects: 0,
    });
    expect(resp.status()).toBe(302);
    expect(resp.headers()['location']).toContain('login.php');
  });

  test('hotel POST tanpa login: 302 → login.php', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali`, {
      form: { checkin: '2027-09-01', checkout: '2027-09-03', name: 'NoAuth', phone: '0812' },
      maxRedirects: 0,
    });
    expect(resp.status()).toBe(302);
    expect(resp.headers()['location']).toContain('login.php');
  });

  test('tour-detail POST tanpa login: 302 → login.php (BUG di-fix)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`, {
      form: {
        form_submitted: '1',
        tour_date_id: '1',
        name: 'NoAuth',
        email: 'x@y.com',
        phone: '0812',
        participants: '1',
      },
      maxRedirects: 0,
    });
    expect(resp.status()).toBe(302);
    expect(resp.headers()['location']).toContain('login.php');
  });
});