import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: tombol "Pakai" kode promo harus menampilkan state loading
 * (Memeriksa kode promo...) selagi request berjalan, bukan diam saja.
 * Diuji pada hotel-detail (assets/js/klook.js applyPromo).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const input = '#promoCodeHotel';
const result = '#promoResultHotel';

let mode: 'success' | 'error' = 'success';

test.beforeEach(async ({ page }) => {
  await page.route('**/apply-promo-ajax.php*', async (route) => {
    if (mode === 'error') return route.fulfill({ status: 500, contentType: 'text/html', body: 'oops' });
    await new Promise((r) => setTimeout(r, 1200));
    return route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, message: 'Kode promo berlaku!', discount: 10000 }),
    });
  });
});

test('apply promo menampilkan state loading lalu hasil', async ({ page }) => {
  await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=id`);
  await page.locator(input).fill('HEMAT10');
  await page.locator('button.klook-promo-btn').first().click();

  // Loading state selama request.
  await expect(page.locator(result)).toContainText('Memeriksa kode promo');

  // Lalu hasil sukses.
  await expect(page.locator(result)).toContainText('Diskon');
  await expect(page.locator(result)).toHaveScreenshot('promo-result-success.png', { maxDiffPixelRatio: 0.1 });

  // Error state.
  mode = 'error';
  await page.locator(input).fill('XXXX');
  await page.locator('button.klook-promo-btn').first().click();
  await expect(page.locator(result)).toContainText('Terjadi kesalahan');
});
