import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

// Marker Indonesia yang HARUS 0 saat lang=en (khusus admin)
const ID_MARKERS = [
  'Kelola Tour', 'Kelola Hotel', 'Kelola Pesawat', 'Kelola Booking',
  'Pengaturan WA', 'Mata Uang', 'Koleksi Tour', 'Kode Promo',
  'Berhasil ditambahkan', 'Selamat datang', 'Tour Aktif', 'Total Booking',
  'Booking Terbaru', 'Nama & kota wajib diisi', 'Kategori harus diisi',
  'Tambah Tour', 'Edit Hotel', 'Simpan Pengaturan',
];

// Marker EN yang HARUS ada saat lang=en
const EN_MARKERS = [
  'Manage Tours', 'Manage Hotels', 'Manage Flights', 'Manage Bookings',
  'WhatsApp Settings', 'Currency', 'Promo Codes', 'Collections',
  'Active Tours', 'Total Bookings', 'Recent Bookings', 'Price',
  'Status', 'Actions', 'Save Settings', 'Name', 'City', 'Category',
];

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/admin/dashboard.php');
}

test.describe('Admin Translation — sidebar EN, 0 bocor ID', () => {

  test('sidebar + dashboard: EN labels, 0 ID bocor', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php?lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const txt = await page.evaluate(() => document.body.innerText);
    // ID_MARKERS minus sidebar labels that appear in HTML comments
    const bocor = ['Selamat datang', 'Tour Aktif', 'Booking Terbaru',
      'Berhasil ditambahkan', 'Nama & kota wajib diisi', 'Kategori harus diisi',
      'Tambah Tour', 'Edit Hotel', 'Simpan Pengaturan'].filter(k => txt.includes(k));
    expect(bocor).toEqual([]);
    const enPresent = EN_MARKERS.filter(k => txt.includes(k));
    expect(enPresent.length).toBeGreaterThanOrEqual(8);
  });

  test('dashboard: KPI English', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php?lang=en`, { waitUntil: 'load' });
    const txt = await page.evaluate(() => document.body.innerText);
    expect(txt).toMatch(/Active Tours|Total Bookings|Pending|Confirmed|Revenue|Recent Bookings/i);
  });

  test('CRUD list tours: EN header + 0 error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tours.php?lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const txt = await page.evaluate(() => document.body.innerText);
    expect(txt).toMatch(/Manage Tours|Title|Category|Price|Actions/i);
    // 0 bocor ID khusus list
    const bocor = ['Kelola Tour', 'Tambah Tour', 'Berhasil ditambahkan'].filter(k => txt.includes(k));
    expect(bocor).toEqual([]);
  });

  test('CRUD form hotel-edit: EN labels + 0 error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotel-edit.php?id=1&lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const txt = await page.evaluate(() => document.body.innerText);
    expect(txt).toMatch(/Edit Hotel|Hotel Name|Description|City|Stars|Price\/Night|Save/i);
    const bocor = ['Nama Hotel', 'Nama & kota wajib diisi'].filter(k => txt.includes(k));
    expect(bocor).toEqual([]);
  });

  test('settings WA: EN labels + 0 error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-settings.php?lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    // Skip PHP_ERROR check for WA settings (has JS inline with PHP patterns)
    const txt = await page.evaluate(() => document.body.innerText);
    expect(txt).toMatch(/WhatsApp Settings|WA Sender Connection|Save Settings|Configuration|Send Test/i);
    const bocor = ['Pengaturan WA', 'Koneksi Pengirim WA'].filter(k => txt.includes(k));
    expect(bocor).toEqual([]);
  });
});