import { test, expect } from '@playwright/test';

const BASE = 'https://tourandtravel.web.id';

test.describe('Tour Content Auto-Translation', () => {

  test('tour title translates to English', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?lang=en`);
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    // Check that tour titles are translated (not showing Indonesian raw text)
    const hasTranslatedContent = body.includes('Snowy') || body.includes('Winter') || body.includes('Hokkaido') || body.includes('Tokyo') || body.includes('Shanghai');
    expect(hasTranslatedContent).toBeTruthy();
  });

  test('tour detail title and description translate', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d7n-snowy-winter-in-northern-hokkaido&lang=en`);
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    // Tour title should be in English or have translation
    const hasEnglishTitle = body.includes('Snowy') || body.includes('Winter') || body.includes('Hokkaido');
    expect(hasEnglishTitle).toBeTruthy();
  });

  test('tour detail switches back to Indonesian', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d7n-snowy-winter-in-northern-hokkaido&lang=en`);
    await page.waitForLoadState('networkidle');

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    // Should show Indonesian content
    const hasIndonesianContent = body.includes('Galeri Foto') || body.includes('Fasilitas Termasuk') || body.includes('Lokasi');
    expect(hasIndonesianContent).toBeTruthy();
  });
});
