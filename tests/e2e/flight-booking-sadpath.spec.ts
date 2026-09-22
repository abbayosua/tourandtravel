import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning:|Parse error|Uncaught)/i;

test.describe('Flight booking sad path', () => {
  test('TC-407a tanpa sesi offer (book_duffel tanpa offer) → error sesi', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/flight-detail.php?offer_id=invalid-offer-xyz`, {
      form: { book_duffel: '1', name: 'Test User', phone: '08123456789' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/Penerbangan tidak tersedia|Sesi penerbangan tidak valid|not available/i);
  });

  test('TC-407b book_duffel tanpa login → minta login', async ({ page }) => {
    // offer id duffel butuh API live; gunakan sesi local mode utk jalur beda:
    const resp = await page.request.post(`${BASE}/flight-detail.php?offer_id=off_dummy`, {
      form: { book_duffel: '1', name: 'Test User', phone: '0812' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/tidak valid|login|Silakan/i);
  });

  test('TC-407c book_flightlist nama/telepon kosong → ditolak', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/flight-detail.php?offer_id=fl_dummy`, {
      form: { book_fl: '1', name: '', phone: '' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/tidak valid|wajib|required|login/i);
  });

  test('TC-407d local booking passengers=0 → tidak sukses (total tidak dihitung)', async ({ page }) => {
    const resp = await page.request.post(`${BASE}/flight-detail.php?schedule_id=1`, {
      form: { name: 'Test User', phone: '08123456789', passengers: '0' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).not.toMatch(/berhasil dipesan|successfully booked/i);
  });
});
