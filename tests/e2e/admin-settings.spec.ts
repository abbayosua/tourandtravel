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

test.describe('Admin Currency Settings', () => {

  test('form render: select default currency + rates table', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/currency-settings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Pengaturan Mata Uang/i);
    await expect(page.locator('select[name="default_currency"]')).toBeVisible();
    await expect(page.locator('button[name="save_currency"]')).toBeVisible();
    await expect(page.locator('button[name="refresh_rates"]')).toBeVisible();
  });

  test('ganti default IDR→USD: tersimpan di settings table', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/currency-settings.php`, { waitUntil: 'load' });
    await page.selectOption('select[name="default_currency"]', 'USD');
    await page.click('button[name="save_currency"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Mata uang default berhasil disimpan: USD/i);

    // Kembalikan ke IDR
    await page.selectOption('select[name="default_currency"]', 'IDR');
    await page.click('button[name="save_currency"]');
    await page.waitForLoadState('load');
  });

  test('refresh rates: tidak fatal (sukses atau gagal external API)', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/currency-settings.php`, { waitUntil: 'load' });
    await page.click('button[name="refresh_rates"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // sukses: "Kurs berhasil diperbarui" ATAU gagal: "Gagal mengambil kurs"
    expect(body).toMatch(/Kurs berhasil diperbarui|Gagal mengambil kurs/i);
  });
});

test.describe('Admin WA Settings', () => {

  test('form render: token, server_url, admin_phone, test_phone', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-settings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/WhatsApp/i);
    await expect(page.locator('input[name="admin_phone"]')).toBeVisible();
    await expect(page.locator('input[name="token"]')).toBeVisible();
    await expect(page.locator('input[name="server_url"]')).toBeVisible();
  });

  test('save valid: msg "Pengaturan WhatsApp berhasil disimpan"', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-settings.php`, { waitUntil: 'load' });
    await page.fill('input[name="admin_phone"]', '6285174488415');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Pengaturan WhatsApp berhasil disimpan/i);
  });

  test('save dengan phone invalid (tanpa 62): error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-settings.php`, { waitUntil: 'load' });
    await page.fill('input[name="admin_phone"]', '08123456789');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Nomor WA harus diawali 62/i);
  });

  test('save dengan phone kosong: error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-settings.php`, { waitUntil: 'load' });
    await page.evaluate(() => {
      const inp = document.querySelector('input[name="admin_phone"]');
      inp.removeAttribute('required');
      inp.value = '';
      document.querySelector('button[type="submit"]').click();
    });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).toMatch(/Nomor WA admin harus diisi/i);
  });

  test('wa-test.php: redirect ke wa-settings (tanpa POST)', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/wa-test.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    // Tanpa POST: $phone default WA_ADMIN, kirim WA → redirect ke wa-settings
    expect(page.url()).toContain('wa-settings.php');
  });
});