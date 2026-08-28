import { test, expect } from '@playwright/test';

const BASE = 'https://tourandtravel.web.id';

test.describe('Multilingual - Tour Detail Page', () => {

  test('tour detail switches to English', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town&lang=en`);
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = ['Image Gallery', 'Included Amenities', 'Location'];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Tour EN missing:', missing);
    expect(missing).toEqual([]);
  });

  test('tour detail switches back to Indonesian', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town&lang=en`);
    await page.waitForLoadState('networkidle');

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = ['Galeri Foto', 'Fasilitas Termasuk', 'Lokasi'];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Tour ID missing:', missing);
    expect(missing).toEqual([]);
  });

  test('URL preserves slug after language switch', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town&lang=en`);
    await page.waitForLoadState('networkidle');

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('domcontentloaded');

    expect(page.url()).toContain('slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town');
  });
});

test.describe('Multilingual - Homepage', () => {

  test('homepage switches to English', async ({ page }) => {
    await page.goto(`${BASE}/?lang=en`);
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = [
      'Your World of Joy',
      'View All',
      'Transparent Pricing',
      'Easy Booking',
      'Holiday Ready?',
      'Start Now',
    ];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Homepage EN missing:', missing);
    expect(missing).toEqual([]);
  });

  test('homepage switches back to Indonesian', async ({ page }) => {
    await page.goto(`${BASE}/?lang=en`);
    await page.waitForLoadState('networkidle');

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = [
      'Lihat Semua',
      'Harga Transparan',
      'Mudah Booking',
      'Siap Liburan?',
      'Mulai Sekarang',
    ];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Homepage ID missing:', missing);
    expect(missing).toEqual([]);
  });
});

test.describe('Multilingual - Tours Listing', () => {

  test('tours listing switches to English', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?lang=en`);
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = ['All Sections', 'Duration', 'Price', 'Details'];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Tours EN missing:', missing);
    expect(missing).toEqual([]);
  });

  test('tours listing switches back to Indonesian', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?lang=en`);
    await page.waitForLoadState('networkidle');

    await page.locator('#navbarNav .nav-link.dropdown-toggle:has(i.bi-translate)').click();
    await page.locator('.dropdown-menu.show a.dropdown-item:has-text("Indonesia")').click();
    await page.waitForLoadState('networkidle');

    const body = await page.textContent('body');

    const checks = ['Semua Kategori', 'Durasi', 'Harga', 'Detail'];
    const missing = checks.filter(t => !body.includes(t));
    console.log('Tours ID missing:', missing);
    expect(missing).toEqual([]);
  });
});
