import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = '8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

async function switchLang(page, langLabel) {
  const toggler = page.locator('a.nav-link.dropdown-toggle:has(i.bi-translate)');
  await toggler.click();
  const menu = page.locator('.dropdown-menu.show').first();
  await menu.locator(`a.dropdown-item:has-text("${langLabel}")`).first().click();
  await page.waitForLoadState('load');
}

test.describe('Language switch (id/en)', () => {

  test('homepage: id -> en via dropdown (teks berubah)', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Siap Liburan|Mudah Booking|Harga Transparan|Lihat Semua/i);

    await switchLang(page, 'English');
    body = await page.textContent('body');
    expect(body).toMatch(/Holiday Ready|Easy Booking|Transparent Pricing|View All/i);
  });

  test('homepage: en -> id via dropdown (teks berubah)', async ({ page }) => {
    await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).toMatch(/Holiday Ready|Easy Booking/i);

    await switchLang(page, 'Indonesia');
    body = await page.textContent('body');
    expect(body).toMatch(/Siap Liburan|Mudah Booking|Harga Transparan/i);
  });

  test('tours listing: filter preserved setelah switch en->id', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?category=China&lang=en`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/All Sections|Duration|Price|Details/i);

    await switchLang(page, 'Indonesia');
    body = await page.textContent('body');
    expect(body).toMatch(/Semua Kategori|Durasi|Harga/i);
    // Filter category STILL in URL (lang dihapus server, filter tidak)
    expect(page.url()).toContain('category=China');
  });

  test('tours listing: search param preserved', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?search=Tokyo`, { waitUntil: 'load' });
    await switchLang(page, 'English');
    expect(page.url()).toContain('search=Tokyo');
    const body = await page.textContent('body');
    expect(body).toMatch(/Tokyo|View All|All Sections/i);
  });

  test('tour-detail: slug preserved saat switch en->id', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=en`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Image Gallery|Included Amenities|Location/i);
    expect(page.url()).toContain(SLUG);

    await switchLang(page, 'Indonesia');
    body = await page.textContent('body');
    expect(body).toMatch(/Galeri Foto|Fasilitas Termasuk|Lokasi/i);
    expect(page.url()).toContain(SLUG);
  });

  test('tour-detail: lang persist ke halaman lain via session', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=en`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).toMatch(/Image Gallery|Included Amenities|Location/i);

    // Navigasi ke tours.php TANPA param lang — bahasa harus tetap EN (session)
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    body = await page.textContent('body');
    expect(body).toMatch(/All Sections|Duration|Price/i);
  });

  test('invalid lang param falls back ke id', async ({ page }) => {
    await page.goto(`${BASE}/index.php?lang=xx`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Siap Liburan|Mudah Booking|Harga Transparan/i);
  });

  test('lang persist via cookie antar halaman', async ({ page }) => {
    await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/All Sections|Duration|Price|View All/i);
  });
});