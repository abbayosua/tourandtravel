import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: autocomplete pencarian tour (assets/js/script.js) harus
 * menampilkan state loading / kosong / error, bukan senyap saat fetch gagal.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const dropdown = '#catalogSearchDropdown';

let mode: 'empty' | 'error' | 'loading' = 'empty';

test.beforeEach(async ({ page }) => {
  await page.route('**/search-ajax.php*', async (route) => {
    if (mode === 'error') return route.fulfill({ status: 500, contentType: 'application/json', body: '{}' });
    if (mode === 'loading') {
      await new Promise((r) => setTimeout(r, 1500));
      return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
    }
    return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
  });
});

test('autocomplete menampilkan loading, kosong, dan error', async ({ page }) => {
  mode = 'loading';
  await page.goto(`${BASE}/tours.php?lang=id`);
  const input = page.locator('#catalogSearch');
  await input.fill('zzz');

  // Loading state tampil selama request berjalan.
  await expect(page.locator(dropdown)).toContainText('Mencari');

  // Lalu empty state setelah hasil kosong.
  await expect(page.locator(dropdown)).toContainText('Tidak ada hasil ditemukan');
  await expect(page.locator(dropdown)).toHaveScreenshot('tour-search-empty.png', { maxDiffPixelRatio: 0.1 });

  // Error state saat request gagal.
  mode = 'error';
  await input.fill('qqq');
  await expect(page.locator(dropdown)).toContainText('Gagal memuat hasil');
});
