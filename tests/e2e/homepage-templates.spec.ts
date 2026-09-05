import { test, expect, test as base } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning:|Deprecated|Parse error|Uncaught|Undefined variable)/i;

/**
 * DB helper: set site_focus langsung via mysql (dengan cleanup otomatis).
 * Selalu kembalikan ke 'tour' setelah test agar preset lain tidak terkontaminasi.
 */
async function setFocus(focus: string) {
  execSync(
    `mysql -u root tourandtravel -e "UPDATE settings SET setting_value='${focus}' WHERE setting_key='site_focus';"`,
    { stdio: 'pipe' }
  );
}
async function getFocus(): Promise<string> {
  const out = execSync(
    `mysql -u root tourandtravel -N -e "SELECT setting_value FROM settings WHERE setting_key='site_focus';"`
  ).toString().trim();
  return out || 'tour';
}

test.describe('Homepage Templates — fokus website (tour/hotel/flight)', () => {

  test.afterEach(async () => {
    // Cleanup: kembalikan fokus default
    await setFocus('tour');
  });

  // ===== Preset TOUR (Klook, regresi nol) =====
  test('preset tour: render Klook-style (hero carousel, flash deals, featured)', async ({ page }) => {
    await setFocus('tour');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('.hero-klook')).toBeVisible();
    await expect(page.locator('#heroCarousel')).toBeVisible();
    expect(body).toMatch(/Tour Categories|Kategori Wisata|Categories/);
    expect(body).toMatch(/Destinasi Populer|Popular Destinations|Top Destination/i);
    expect(body).toMatch(/Rekomendasi Paket Tour|Recommended Tour/i);
    // Tidak ada elemen preset lain
    expect(body).not.toContain('Cari Hotel');
    expect(body).not.toContain('Cari Penerbangan');
  });

  // ===== Preset HOTEL (Agoda-style) =====
  test('preset hotel: hero search dominan + deals + cities + trust + cross-sell', async ({ page }) => {
    await setFocus('hotel');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(PHP_ERROR);

    // Hero search hotel (form dengan field khas hotel)
    const heroForm = page.locator('form[action="hotels.php"]');
    await expect(heroForm).toBeVisible();
    await expect(heroForm.locator('input[name="city"]')).toBeVisible();
    await expect(heroForm.locator('input[name="checkin"]')).toBeVisible();
    await expect(heroForm.locator('input[name="checkout"]')).toBeVisible();
    await expect(heroForm.locator('select[name="guests"]')).toBeVisible();

    // Section kunci + urutan
    expect(body).toMatch(/Deal Hotel Terbaik|Best Hotel Deals/i);
    expect(body).toMatch(/Kota Populer|Popular Cities/i);
    expect(body).toMatch(/Kenapa Booking Hotel di|Why Book Hotels with/i);
    expect(body).toMatch(/Apa Kata Mereka\?|What do they say\?|Testimonial/i);
    expect(body).toMatch(/Jelajahi Juga: Paket Tour|Also Explore: Tour Packages/i);

    // Tidak ada elemen preset lain
    expect(body).not.toContain('Cari Penerbangan');
    expect(body).not.toContain('heroCarousel');
  });

  test('preset hotel: search form submit → hotels.php dengan param terbawa', async ({ page }) => {
    await setFocus('hotel');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    await page.fill('form[action="hotels.php"] input[name="city"]', 'Bali');
    await page.click('form[action="hotels.php"] button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('hotels.php');
    expect(page.url()).toContain('city=Bali');
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('preset hotel: kartu hotel render (nama, harga, link detail)', async ({ page }) => {
    await setFocus('hotel');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const cards = page.locator('.card a[href*="hotel-detail.php"]');
    expect(await cards.count()).toBeGreaterThan(0);
    const body = await page.textContent('body') || '';
    expect(body).toMatch(/Rp\s/); // harga
  });

  test('preset hotel: empty state saat tidak ada hotel aktif', async ({ page }) => {
    await setFocus('hotel');
    execSync(`mysql -u root tourandtravel -e "UPDATE hotels SET is_active=0;"`, { stdio: 'pipe' });
    try {
      await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
      const body = await page.textContent('body') || '';
      expect(body).not.toMatch(PHP_ERROR);
      expect(body).toMatch(/Belum ada hotel tersedia|No hotels available/i);
      // Hero tetap tampil (tidak blank)
      await expect(page.locator('form[action="hotels.php"]')).toBeVisible();
    } finally {
      execSync(`mysql -u root tourandtravel -e "UPDATE hotels SET is_active=1;"`, { stdio: 'pipe' });
    }
  });

  // ===== Preset FLIGHT (Tiket.com-style) =====
  test('preset flight: hero search + promo + rute populer + trust + cross-sell', async ({ page }) => {
    await setFocus('flight');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    expect(body).not.toMatch(PHP_ERROR);

    // Hero search flight
    const flightForm = page.locator('#homeFlightSearchForm');
    await expect(flightForm).toBeVisible();
    await expect(flightForm.locator('input[name="from"]')).toBeVisible();
    await expect(flightForm.locator('input[name="to"]')).toBeVisible();
    await expect(flightForm.locator('input[name="date"]')).toBeVisible();
    await expect(flightForm.locator('input[name="trip_type"][value="oneway"]')).toBeVisible();
    await expect(flightForm.locator('input[name="trip_type"][value="roundtrip"]')).toBeVisible();

    // Section kunci
    expect(body).toMatch(/Promo Tiket Setiap Hari|Daily Flight Deals/i);
    expect(body).toMatch(/Rute Populer|Popular Routes/i);
    expect(body).toMatch(/Kenapa Pesan Tiket di|Why Book Flights with/i);
    expect(body).toMatch(/Lengkapi Perjalanan: Hotel|Complete Your Trip: Hotels/i);
    expect(body).toMatch(/Paket Tour Populer|Popular Tour Packages/i);

    // Tidak ada elemen preset lain
    expect(body).not.toContain('Cari Hotel');
  });

  test('preset flight: toggle roundtrip menampilkan return date + submit ke flights.php', async ({ page }) => {
    await setFocus('flight');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });

    // Default oneway → return hidden
    const returnCol = page.locator('.home-return-date-col');
    await expect(returnCol).toBeHidden();

    // Pilih roundtrip → return visible
    await page.check('#homeTripRoundtrip');
    await expect(returnCol).toBeVisible();

    // Submit
    await page.fill('#homeFromInput', 'Jakarta (CGK)');
    await page.fill('#homeToInput', 'Denpasar (DPS)');
    await page.click('#homeFlightSearchForm button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('flights.php');
    expect(page.url()).toContain('trip_type=roundtrip');
    expect(page.url()).toContain('from=Jakarta');
  });

  test('preset flight: kartu rute populer dengan link flights.php', async ({ page }) => {
    await setFocus('flight');
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const routeLinks = page.locator('a[href*="flights.php?from="]');
    expect(await routeLinks.count()).toBeGreaterThan(0);
  });

  // ===== Switch fokus: idempotent & tidak merusak data =====
  test('switch fokus tour→hotel→flight→tour: idempotent, data tours utuh', async ({ page }) => {
    const toursBefore = execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM tours WHERE is_active=1;"`
    ).toString().trim();

    for (const focus of ['hotel', 'flight', 'tour']) {
      await setFocus(focus);
      await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
      const body = await page.textContent('body') || '';
      expect(body).not.toMatch(PHP_ERROR);
      if (focus === 'hotel') expect(body).toMatch(/Deal Hotel Terbaik|Best Hotel Deals/i);
      if (focus === 'flight') expect(body).toMatch(/Rute Populer|Popular Routes/i);
      if (focus === 'tour') await expect(page.locator('#heroCarousel')).toBeVisible();
    }

    const toursAfter = execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM tours WHERE is_active=1;"`
    ).toString().trim();
    expect(toursAfter).toBe(toursBefore); // data tidak berubah
    expect(await getFocus()).toBe('tour');
  });

  // ===== i18n: kedua bahasa di ketiga preset =====
  test('i18n: preset hotel EN vs ID label berbeda', async ({ page }) => {
    await setFocus('hotel');
    await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
    const en = await page.textContent('body') || '';
    expect(en).toMatch(/Best Hotel Deals|Popular Cities|Search Hotels/);

    await page.goto(`${BASE}/index.php?lang=id`, { waitUntil: 'load' });
    const id = await page.textContent('body') || '';
    expect(id).toMatch(/Deal Hotel Terbaik|Kota Populer|Cari Hotel/);
    expect(id).not.toMatch(/Best Hotel Deals/);
  });

  test('i18n: preset flight EN vs ID label berbeda', async ({ page }) => {
    await setFocus('flight');
    await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
    const en = await page.textContent('body') || '';
    expect(en).toMatch(/Daily Flight Deals|Popular Routes|Search Flights/);

    await page.goto(`${BASE}/index.php?lang=id`, { waitUntil: 'load' });
    const id = await page.textContent('body') || '';
    expect(id).toMatch(/Promo Tiket Setiap Hari|Rute Populer|Cari Penerbangan/);
    expect(id).not.toMatch(/Daily Flight Deals/);
  });

  test('i18n: html lang dinamis di semua preset', async ({ page }) => {
    for (const focus of ['hotel', 'flight']) {
      await setFocus(focus);
      await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
      await expect(page.locator('html')).toHaveAttribute('lang', 'en');
      const i18nLang = await page.evaluate(() => (window as any).I18N?.lang);
      expect(i18nLang).toBe('en');
    }
  });

  // ===== Link section semua 200 =====
  test('link section preset hotel & flight → 200', async ({ page }) => {
    const targets = ['hotels.php', 'tours.php', 'attractions.php', 'flights.php', 'hotels.php?city=Bali'];
    for (const t of targets) {
      const resp = await page.goto(`${BASE}/${t}`);
      expect(resp?.status()).toBe(200);
    }
  });

  // ===== Admin appearance: dropdown tersimpan =====
  test('admin: appearance page fokus tersimpan via UI', async ({ page }) => {
    await page.goto(`${BASE}/admin/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/admin/appearance.php`);
    await page.selectOption('#siteFocusSelect', 'flight');
    await page.click('form button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('msg=updated');
    expect(await getFocus()).toBe('flight');

    // Homepage publik ikut flight
    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    expect(body).toMatch(/Rute Populer|Popular Routes/i);
  });
});
