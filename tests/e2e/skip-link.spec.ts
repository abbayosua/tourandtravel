import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX/a11y: header publik punya tautan "Skip to content" ke
 * #mainContent, tetapi tidak ada elemen #mainContent / <main> di mana pun dan
 * handler smooth-scroll di script.js mem-preventDefault semua a[href^="#"]
 * tanpa memindah fokus — tautan mati. Sekarang header membuka <main
 * id="mainContent" tabindex="-1">, footer menutupnya, dan skip link dibiarkan
 * memakai navigasi native sehingga fokus benar-benar pindah ke konten utama.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

test('skip link memindahkan fokus ke konten utama', async ({ page }) => {
  await page.goto(`${BASE}/tours.php?lang=id`);

  const main = page.locator('main#mainContent');
  await expect(main).toHaveCount(1);

  const skip = page.locator('a.skip-link');
  await expect(skip).toHaveAttribute('href', '#mainContent');

  // Tersembunyi sampai difokus (keyboard), lalu muncul di dalam viewport.
  await skip.focus();
  await expect(skip).toBeFocused();
  const box = (await skip.boundingBox())!;
  expect(box.x).toBeGreaterThanOrEqual(0);
  await expect(skip).toHaveScreenshot('skip-link-focus.png', { maxDiffPixelRatio: 0.15 });

  // Aktivasi keyboard memindah fokus ke konten utama (bukan tetap di tautan).
  await page.keyboard.press('Enter');
  await expect(main).toBeFocused();
});
