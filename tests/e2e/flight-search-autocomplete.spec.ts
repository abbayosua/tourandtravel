import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: autocomplete pencarian transport (includes/homepage/transport-search.php)
 * harus menampilkan state loading / kosong / error, bukan senyap saat fetch gagal.
 * Diuji pada hero pencarian flights.php (input #fromInput → #fromDropdown).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const input = '#fromInput';
const dropdown = '#fromDropdown';

let mode: 'empty' | 'error' | 'loading' = 'empty';

test.beforeEach(async ({ page }) => {
  await page.route('**/city-search-ajax.php*', async (route) => {
    if (mode === 'error') return route.fulfill({ status: 500, contentType: 'application/json', body: '{}' });
    if (mode === 'loading') {
      await new Promise((r) => setTimeout(r, 1500));
      return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
    }
    return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
  });
});

test('autocomplete transport menampilkan loading, kosong, dan error', async ({ page }) => {
  mode = 'loading';
  await page.goto(`${BASE}/flights.php?lang=id`);
  await page.locator(input).fill('jak');

  await expect(page.locator(dropdown)).toContainText('Mencari');
  await expect(page.locator(dropdown)).toContainText('Tidak ada hasil ditemukan');
  await expect(page.locator(dropdown)).toHaveScreenshot('flight-search-empty.png', { maxDiffPixelRatio: 0.1 });

  mode = 'error';
  await page.locator(input).fill('sura');
  await expect(page.locator(dropdown)).toContainText('Gagal memuat hasil');
});
