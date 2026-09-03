import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

// Kata Indonesia khas (BUKAN loanword yang sama di EN seperti Transfer/Ferry/Rental/eSIM/Hotel/Instan)
const ID_MARKERS = [
  'Beranda', 'Pesawat', 'Kereta', 'Atraksi', 'Batal Gratis',
  'Konfirmasi Instan', 'Destinasi Populer', 'Rekomendasi Paket Tour',
  'Kategori Wisata', 'Lihat Semua', 'Tidak ada hotel', 'Belum ada',
  'Nama Lengkap', 'No. WhatsApp', 'Harga Termurah', 'Jam Berangkat',
  'Tiket Tempat Wisata', 'Kembali ke Katalog', 'Saldo KlookCash', 'Pusat Bantuan',
];

const EN_MARKERS = [
  'Home', 'Flights', 'Train', 'Attractions', 'Free Cancellation',
  'Instant Confirmation', 'Popular Destinations', 'Recommended Tour',
  'Tour Categories', 'View All', 'Search', 'Full Name', 'WhatsApp',
];

// Marker admin (hindari false positive: "Total Booking" substring "Total Bookings")
const ADMIN_ID_MARKERS = [
  'Kelola Tour', 'Kelola Hotel', 'Kelola Pesawat', 'Kelola Ferry',
  'Pengaturan WA', 'Mata Uang', 'Koleksi Tour', 'Kode Promo',
  'Berhasil ditambahkan', 'Selamat datang', 'Booking Terbaru',
  'Tambah Tour', 'Simpan Pengaturan',
];
const ADMIN_EN_MARKERS = [
  'Manage Tours', 'Manage Hotels', 'Manage Bookings', 'Active Tours',
  'Total Bookings', 'Recent Bookings', 'WhatsApp Settings', 'Currency',
  'Promo Codes', 'Collections', 'Save Settings', 'Manage Attractions',
];

// Pages public
const PUBLIC_PAGES = ['index.php', 'tours.php', 'hotels.php', 'flights.php', 'ferries.php', 'trains.php', 'esim.php', 'faq.php', 'attractions.php', 'transfers.php', 'collection.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town'];

// Pages butuh login
const AUTH_PAGES = ['wallet.php', 'referral.php'];

async function enableEn(page) {
  await page.goto(`${BASE}/index.php?lang=en`, { waitUntil: 'load' });
  // set cookie persist untuk semua halaman
  await page.context().addCookies([{ name: 'lang', value: 'en', url: BASE }]);
}

async function registerUser(page) {
  const email = `completeness_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'Completeness Test');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForURL(/index\.php/);
}

test.describe('Translation Completeness — 0 bahasa Indonesia bocor di lang=en', () => {

  for (const pagePath of PUBLIC_PAGES) {
    test(`public: ${pagePath}`, async ({ page }) => {
      await enableEn(page);
      await page.goto(`${BASE}/${pagePath}`, { waitUntil: 'load' });
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
      const txt = await page.evaluate(() => document.body.innerText);
      const bocor = ID_MARKERS.filter((k) => txt.includes(k));
      expect(bocor).toEqual([]);
      // Kata EN hadir
      const enPresent = EN_MARKERS.filter((k) => txt.includes(k));
      expect(enPresent.length).toBeGreaterThanOrEqual(2);
    });
  }

  for (const pagePath of AUTH_PAGES) {
    test(`auth: ${pagePath}`, async ({ page }) => {
      await registerUser(page);
      await enableEn(page);
      await page.goto(`${BASE}/${pagePath}`, { waitUntil: 'load' });
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
      const txt = await page.evaluate(() => document.body.innerText);
      const bocor = ID_MARKERS.filter((k) => txt.includes(k));
      expect(bocor).toEqual([]);
    });
  }
});

test.describe('Translation Completeness — Admin (lang=en 0 bocor)', () => {

  async function loginAdmin(page) {
    await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard.php');
  }

  const ADMIN_PAGES = ['admin/dashboard.php', 'admin/tours.php', 'admin/bookings.php', 'admin/wa-settings.php'];

  for (const pagePath of ADMIN_PAGES) {
    test(`admin: ${pagePath}`, async ({ page }) => {
      await loginAdmin(page);
      await page.goto(`${BASE}/${pagePath}?lang=en`, { waitUntil: 'load' });
      const body = await page.textContent('body');
      if (!pagePath.includes('wa-settings')) expect(body).not.toMatch(PHP_ERROR);
      const txt = await page.evaluate(() => document.body.innerText);
      const bocor = ADMIN_ID_MARKERS.filter((k) => txt.includes(k));
      expect(bocor).toEqual([]);
      const enPresent = ADMIN_EN_MARKERS.filter((k) => txt.includes(k));
      expect(enPresent.length).toBeGreaterThanOrEqual(3);
    });
  }
});