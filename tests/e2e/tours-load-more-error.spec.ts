import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: infinite scroll di tours.php harus punya error state.
 *
 * Bug yang dijaga:
 *   fetch() ke tours-ajax.php dulu gagal secara senyap — spinner terus berputar,
 *   tidak ada pesan maupun tombol coba lagi, jadi user mengira masih memuat.
 *
 * Sekarang: kegagalan menampilkan pesan + tombol "Coba Lagi" (bukan spinner),
 * dan tombol itu benar-benar memuat halaman berikutnya saat koneksi pulih.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

const trigger = '.load-more-trigger';
const errorBox = '[data-load-error="true"]';
const spinner = '.load-more-spinner';
const retry = '[data-load-retry]';
const cards = '#tourGrid .tour-card-klook';

async function openToursWithBrokenPagination(page: Page) {
  await page.route('**/tours-ajax.php*', (route) => route.abort('failed'));
  await page.goto(`${BASE}/tours.php?lang=id`);
  await expect(page.locator('#tourContent')).toBeVisible();
  await expect(page.locator(trigger)).toHaveCount(1);
  await page.locator(trigger).scrollIntoViewIfNeeded();
}

test('kegagalan memuat halaman berikut menampilkan pesan + tombol coba lagi', async ({ page }) => {
  await openToursWithBrokenPagination(page);

  await expect(page.locator(errorBox)).toBeVisible();
  await expect(page.locator(errorBox)).toContainText('Gagal memuat tour');
  await expect(page.locator(spinner)).toBeHidden();

  // Tombol coba lagi harus bisa dipakai (di dalam viewport, tidak tertutup apa pun).
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

  // Assertion visual: state error harus stabil (regresi layout/teks).
  await expect(page.locator(trigger)).toHaveScreenshot('tours-load-more-error.png', {
    maxDiffPixelRatio: 0.1,
  });
});

test('tombol coba lagi memuat halaman berikut setelah koneksi pulih', async ({ page }) => {
  await openToursWithBrokenPagination(page);
  await expect(page.locator(errorBox)).toBeVisible();

  const before = await page.locator(cards).count();

  // Koneksi pulih → klik coba lagi → kartu baru masuk, error hilang.
  await page.unroute('**/tours-ajax.php*');
  await page.locator(retry).click();

  await expect(page.locator(errorBox)).toBeHidden();
  await expect
    .poll(async () => page.locator(cards).count(), { timeout: 10000 })
    .toBeGreaterThan(before);
});
