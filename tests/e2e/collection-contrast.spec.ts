import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX (kontras): label kecil di header collection dulu memakai
 * `text-white-50` (putih 50%) di atas gradient biru/ungu — sulit dibaca.
 * Sekarang putih penuh.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

test('label header collection memakai putih penuh (kontras)', async ({ page }) => {
  await page.goto(`${BASE}/collection.php?slug=best-seller&lang=id`);

  const count = page.locator('.card .container small').first();
  await expect(count).toContainText('tour');

  const color = await count.evaluate((el) => getComputedStyle(el).color);
  expect(color).toBe('rgb(255, 255, 255)');

  await expect(page.locator('.card').first()).toHaveScreenshot('collection-header-contrast.png', {
    maxDiffPixelRatio: 0.15,
  });
});
