import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX (mobile): bottom-nav dulu tidak menyorot tab apa pun di
 * halaman detail (tour/hotel/flight/dll) — user tak tahu posisinya. Sekarang
 * halaman detail masuk ke tab "Cari" (jelajah).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

test.use({ viewport: { width: 390, height: 844 } });

test('bottom nav menyorot tab Cari di halaman detail', async ({ page }) => {
  const pages = [
    'tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town',
    'hotel-detail.php?slug=grand-hyatt-bali',
    'faq.php',
    'tours.php',
  ];
  for (const p of pages) {
    await page.goto(`${BASE}/${p}${p.includes('?') ? '&' : '?'}lang=id`);
    const cari = page.locator('.bottom-nav a[href="tours.php"]');
    await expect(cari).toHaveClass(/active/);
  }

  await page.goto(`${BASE}/tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town&lang=id`);
  await expect(page.locator('.bottom-nav')).toHaveScreenshot('bottom-nav-detail-active.png', { maxDiffPixelRatio: 0.1 });
});
