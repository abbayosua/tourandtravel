import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: infinite scroll di hotels.php harus punya error state.
 *
 * Bug yang dijaga: fetch() ke hotels-ajax.php gagal senyap — spinner terus
 * berputar tanpa pesan/tombol, user mengira masih memuat. Sekarang kegagalan
 * menampilkan pesan + tombol "Coba Lagi" yang benar-benar memuat halaman
 * berikutnya saat koneksi pulih.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

// min_price=0 mencocokkan semua hotel -> >1 halaman sehingga sentinel muncul.
const LISTING = 'hotels.php?lang=id&min_price=0';

const trigger = '[data-testid="hotel-load-more"]';
const errorBox = '[data-load-error="true"]';
const spinner = '.load-more-spinner';
const retry = '[data-load-retry]';
const cards = '[data-testid="card-price"]';

async function openHotelsWithBrokenPagination(page: Page) {
  await page.route('**/hotels-ajax.php*', (route) => route.abort('failed'));
  await page.goto(`${BASE}/${LISTING}`);
  await expect(page.locator('#hotelContent')).toBeVisible();
  await expect(page.locator(trigger)).toHaveCount(1);
  await page.locator(trigger).scrollIntoViewIfNeeded();
}

test('kegagalan memuat halaman berikut menampilkan pesan + tombol coba lagi', async ({ page }) => {
  await openHotelsWithBrokenPagination(page);

  await expect(page.locator(errorBox)).toBeVisible();
  await expect(page.locator(errorBox)).toContainText('Gagal memuat hotel');
  await expect(page.locator(spinner)).toBeHidden();

  await expect(page.locator(retry)).toBeVisible();
  const box = (await page.locator(retry).boundingBox())!;
  expect(box.width).toBeGreaterThan(0);
  expect(box.height).toBeGreaterThan(0);
  const onTop = await page.evaluate(
    ([x, y]) => {
      const el = document.elementFromPoint(x, y);
      return !!(el && el.closest('[data-load-retry]'));
    },
    [box.x + box.width / 2, box.y + box.height / 2] as const
  );
  expect(onTop).toBe(true);

  await expect(page.locator(trigger)).toHaveScreenshot('hotels-load-more-error.png', {
    maxDiffPixelRatio: 0.1,
  });
});

test('tombol coba lagi memuat halaman berikut setelah koneksi pulih', async ({ page }) => {
  await openHotelsWithBrokenPagination(page);
  await expect(page.locator(errorBox)).toBeVisible();

  const before = await page.locator(cards).count();

  await page.unroute('**/hotels-ajax.php*');
  await page.locator(retry).click();

  await expect(page.locator(errorBox)).toBeHidden();
  await expect
    .poll(async () => page.locator(cards).count(), { timeout: 10000 })
    .toBeGreaterThan(before);
});
