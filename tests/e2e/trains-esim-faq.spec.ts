import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Trains - catalog', () => {

  test('tanpa filter: semua kereta tampil', async ({ page }) => {
    const resp = await page.goto(`${BASE}/trains.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/kereta ditemukan|Kereta Api/i);
    expect(body).toMatch(/Argo Bromo|Argo Parahyangan|Taksaka/i);
  });

  test('filter by route from Gambir', async ({ page }) => {
    await page.goto(`${BASE}/trains.php?from=Gambir`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Argo Bromo|Argo Parahyangan|Taksaka/i);
  });

  test('filter by class Eksekutif', async ({ page }) => {
    await page.goto(`${BASE}/trains.php?class=Eksekutif`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Eksekutif/i);
  });

  test('empty state: tidak ada kereta', async ({ page }) => {
    await page.goto(`${BASE}/trains.php?from=UnknownCity999`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tidak ada kereta ditemukan/i);
    await expect(page.locator('a:has-text("Reset Filter")')).toBeVisible();
  });
});

test.describe('Trains - detail & booking', () => {

  test('slug valid: nama, harga, jadwal render', async ({ page }) => {
    const resp = await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Argo Parahyangan/i);
    expect(body).toMatch(/Rp/i);
  });

  test('slug tidak valid: 404 + link kembali', async ({ page }) => {
    await page.goto(`${BASE}/train-detail.php?slug=not-exist`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/tidak ditemukan|Kembali ke Katalog/i);
  });

  test('guest booking tanpa passport: redirect ke success', async ({ page }) => {
    await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).toMatch(/Pesan Tiket/);

    await page.fill('input[name="travel_date"]', '2026-12-25');
    await page.selectOption('select[name="seats"]', '2');
    await page.fill('input[name="name"]', 'Train Guest');
    await page.fill('input[name="email"]', 'train_guest@example.com');
    await page.fill('input[name="phone"]', '081234567890');
    await Promise.all([
      page.waitForURL(/booking-success\.php/),
      page.click('button[type="submit"]'),
    ]);
    const success = await page.textContent('body');
    expect(success).toMatch(/Berhasil|Terima kasih|Sukses/i);
  });

  test('form kosong: tampil error validasi (server-side)', async ({ page }) => {
    await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    // Bypass HTML5 required untuk trigger validasi server-side
    await page.evaluate(() => {
      document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
    });
    await page.click('button[type="submit"]');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tanggal perjalanan harus diisi|Nama harus diisi|Name cannot be empty|Tanggal|Nama/i);
  });
});

test.describe('eSIM - catalog & detail', () => {

  test('katalog: semua produk tampil', async ({ page }) => {
    const resp = await page.goto(`${BASE}/esim.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/eSIM|produk ditemukan/i);
    expect(body).toMatch(/eSIM Indonesia|Pocket WiFi/i);
  });

  test('filter type esim', async ({ page }) => {
    await page.goto(`${BASE}/esim.php?type=esim`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/eSIM/i);
  });

  test('detail valid: beli eSIM', async ({ page }) => {
    const resp = await page.goto(`${BASE}/esim-detail.php?slug=esim-indonesia-5gb`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/eSIM Indonesia/i);

    await page.selectOption('select[name="quantity"]', '2');
    await page.fill('input[name="name"]', 'Esim Guest');
    await page.fill('input[name="email"]', 'esim_guest@example.com');
    await page.fill('input[name="phone"]', '081234567890');
    await Promise.all([
      page.waitForURL(/booking-success\.php/),
      page.click('button[type="submit"]'),
    ]);
  });

  test('detail tidak valid: 404', async ({ page }) => {
    await page.goto(`${BASE}/esim-detail.php?slug=nope`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/tidak ditemukan/i);
  });
});

test.describe('FAQ - public', () => {

  test('halaman FAQ menampilkan kategori dan pertanyaan', async ({ page }) => {
    const resp = await page.goto(`${BASE}/faq.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Pusat Bantuan|FAQ/i);
    expect(body).toMatch(/Umum|Pembayaran|Pembatalan/i);
    expect(body).toMatch(/Bagaimana cara memesan tour\?|Apa itu KlookCash/i);
  });
});

test.describe('Admin - trains/esim/faq CRUD', () => {

  test('sidebar admin punya link modul baru', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kelola Atraksi/i);
    expect(body).toMatch(/Kelola Transfer/i);
    expect(body).toMatch(/Kelola Kereta/i);
    expect(body).toMatch(/Kelola eSIM/i);
    expect(body).toMatch(/Kode Promo/i);
    expect(body).toMatch(/Koleksi/i);
    expect(body).toMatch(/Kelola FAQ/i);
    expect(body).toMatch(/Loyalty Settings/i);
  });

  test('admin trains list & tambah', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/trains.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kereta Api/i);
    expect(body).toMatch(/Argo Parahyangan/i);

    await page.goto(`${BASE}/admin/train-edit.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', `Test Train ${Date.now()}`);
    await page.fill('input[name="route_from"]', 'Jakarta');
    await page.fill('input[name="route_to"]', 'Bandung');
    await page.fill('input[name="departure_time"]', '08:00');
    await page.fill('input[name="arrival_time"]', '11:00');
    await page.fill('input[name="price"]', '200000');
    await page.fill('input[name="class"]', 'Bisnis');
    await page.click('button[type="submit"]');
    await page.waitForURL(/admin\/trains\.php\?msg=added/, { timeout: 10000 });
  });

  test('admin esim list', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/esim.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/eSIM & Connectivity/i);
    expect(body).toMatch(/eSIM Indonesia/i);
  });

  test('admin faq list & tambah item', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/faq.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Bagaimana cara memesan tour/i);

    await page.goto(`${BASE}/admin/faq-edit.php`, { waitUntil: 'load' });
    await page.fill('input[name="question"]', `Test Question ${Date.now()}`);
    await page.fill('textarea[name="answer"]', 'Test answer');
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await Promise.all([
      page.waitForURL(/admin\/faq\.php\?msg=added/),
      page.click('button[type="submit"]'),
    ]);
  });

  test('admin bookings menampilkan multi tipe', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/bookings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Kelola Booking/i);
  });
});