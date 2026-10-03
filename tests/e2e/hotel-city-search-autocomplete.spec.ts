import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: autocomplete kota hotel di homepage (includes/homepage/hotel-hero.php)
 * harus menampilkan state loading / kosong / error, bukan senyap saat fetch gagal.
 *
 * Hotel hero hanya dirender saat setting site_focus = 'hotel', jadi test
 * menyetelnya sementara lalu memulihkannya.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const input = '#voyageHotelCity';
const dropdown = '#voyageHotelCityDrop';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let originalFocus = 'tour';

let mode: 'empty' | 'error' | 'loading' = 'empty';

test.beforeAll(() => {
  originalFocus = mysql("SELECT setting_value FROM settings WHERE setting_key='site_focus'") || 'tour';
  mysql("UPDATE settings SET setting_value='hotel' WHERE setting_key='site_focus'");
});

test.afterAll(() => {
  mysql(`UPDATE settings SET setting_value='${originalFocus}' WHERE setting_key='site_focus'`);
});

test.beforeEach(async ({ page }) => {
  await page.route('**/hotel-suggest-ajax.php*', async (route) => {
    if (mode === 'error') return route.fulfill({ status: 500, contentType: 'application/json', body: '{}' });
    if (mode === 'loading') {
      await new Promise((r) => setTimeout(r, 1500));
      return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
    }
    return route.fulfill({ status: 200, contentType: 'application/json', body: '[]' });
  });
});

test('autocomplete kota hotel menampilkan loading, kosong, dan error', async ({ page }) => {
  mode = 'loading';
  await page.goto(`${BASE}/index.php?lang=id`);
  await page.locator(input).fill('jak');

  await expect(page.locator(dropdown)).toContainText('Mencari');
  await expect(page.locator(dropdown)).toContainText('Tidak ada hasil ditemukan');
  await expect(page.locator(dropdown)).toHaveScreenshot('hotel-city-search-empty.png', { maxDiffPixelRatio: 0.1 });

  mode = 'error';
  await page.locator(input).fill('bali');
  await expect(page.locator(dropdown)).toContainText('Gagal memuat hasil');
});
