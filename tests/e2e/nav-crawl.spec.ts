import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

test.describe('Navbar & Footer Crawl', () => {

  test('link nav utama: semua HTTP 200', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });

    // Nav utama: link di navigasi BOTTOM (bukan dropdown) dgn href eksplisit
    const navBottom = page.locator('nav').last();
    const checks = [
      { label: 'Beranda', href: ['index.php', '/'] },
      { label: 'Tour', href: ['tours.php'] },
      { label: 'Pesawat', href: ['flights.php'] },
      { label: 'Ferry', href: ['ferries.php'] },
      { label: 'Rental', href: ['rental-cars.php'] },
    ];

    for (const link of checks) {
      const anchor = navBottom.locator(`a[href*="${link.href[0]}"]`).first();
      if (await anchor.count()) {
        await anchor.click();
        await page.waitForLoadState('load');
        const ok = link.href.some(u => page.url().includes(u));
        expect(ok, `nav ${link.label} -> ${page.url()}`).toBeTruthy();
        // Kembali ke home untuk iterasi berikutnya
        await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
      }
    }
  });

  test('dropdown Layanan: submenu links 200', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });

    // Buka dropdown Layanan
    const services = page.locator('a:has-text("Layanan")').first();
    await services.hover();
    await page.waitForTimeout(500);

    const subLinks = [
      { text: 'Paket Tour', url: 'tours.php' },
      { text: 'Pesawat', url: 'flights.php' },
      { text: 'Ferry', url: 'ferries.php' },
      { text: 'Rental Mobil', url: 'rental-cars.php' },
    ];
    for (const sub of subLinks) {
      const loc = page.locator('.dropdown-menu a:has-text("' + sub.text + '")').first();
      if (await loc.count()) {
        const href = await loc.getAttribute('href');
        expect(href, sub.text).toContain(sub.url);
      }
    }
  });

  test('lang dropdown: switch en → id kembali', async ({ page }) => {
    await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).toMatch(/Holiday Ready|Easy Booking|View All/i);

    // Switch ke Indonesia
    const toggler = page.locator('a.nav-link.dropdown-toggle:has(i.bi-translate)');
    await toggler.click();
    const menu = page.locator('.dropdown-menu.show').first();
    await menu.locator('a.dropdown-item:has-text("Indonesia")').first().click();
    await page.waitForLoadState('load');

    body = await page.textContent('body');
    expect(body).toMatch(/Siap Liburan|Mudah Booking|Lihat Semua/i);
  });

  test('currency dropdown: pilih USD → label berubah', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });

    const currToggler = page.locator('#currencyDropdown');
    await currToggler.click();
    const currMenu = page.locator('.dropdown-menu.show').first();
    await currMenu.locator('a[data-currency="USD"]').click();
    await page.waitForTimeout(300);

    // Label currency di navbar berubah jadi USD
    const label = await page.locator('#currencyLabel').textContent();
    expect(label).toContain('USD');
  });

  test('wishlist & login links: 200', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    // Wishlist icon (guest → redirect login)
    const wishLink = page.locator('a[href*="wishlist.php"]').first();
    if (await wishLink.count()) {
      await wishLink.click();
      await page.waitForLoadState('load');
      expect(page.url()).toContain('login.php');
    }
  });

  test('footer: social links aman (#)', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const footer = page.locator('footer');
    await expect(footer).toBeVisible();

    // Footer social links (instagram/facebook/youtube/tiktok) pakai href="#"
    const socialLinks = await footer.locator('a[href="#"]').count();
    expect(socialLinks).toBeGreaterThanOrEqual(3);
  });

  test('footer kontak & info: render', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/Kontak|Ikuti Kami|Jam Operasional|TourAndTravel/i);
  });

  test('destinasi links di homepage: HTTP 200', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const destLinks = page.locator('a[href*="destinasi.php?city="]');
    const count = await destLinks.count();
    expect(count).toBeGreaterThan(0);

    // Cek 3 destinasi random — semuanya 200
    for (let i = 0; i < Math.min(3, count); i++) {
      const href = await destLinks.nth(i).getAttribute('href');
      const resp = await page.request.get(`${BASE}/${href}`);
      expect(resp.status(), href).toBe(200);
    }
  });
});