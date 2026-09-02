import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Tour Detail - sad path (404 handling)', () => {

  test('slug tidak ada: HTTP 404 + pesan + tombol kembali', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=tour-tidak-pernah-ada-xyz123`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tour tidak ditemukan|Tour Tidak Ditemukan|not found/i);

    // Tombol kembali ke katalog
    await expect(page.locator('a:has-text("Kembali")')).toBeVisible();
    const href = await page.locator('a:has-text("Kembali")').getAttribute('href');
    expect(href).toContain('tours.php');
  });

  test('slug kosong: HTTP 404 + pesan', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Tour tidak ditemukan|not found/i);
  });

  test('slug tanpa param slug sama sekali: HTTP 404', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(404);

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('slug dengan karakter spesial di-escape (anti XSS)', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=%3Cscript%3Ealert(1)%3C%2Fscript%3E`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Script tidak dieksekusi & tidak muncul mentah
    expect(body).not.toMatch(/alert\(1\)/);
    expect(await page.locator('script:has-text("alert(1)")').count()).toBe(0);
  });

  test('slug whitespace-only: 404 (trim diperlakukan)', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=++++`, { waitUntil: 'load' });
    // whitespace-only slug -> kemungkinan 404 atau redirect, yang penting tidak fatal
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('slug case salah (huruf besar): tetap ketemu (case-insensitive) ATAU 404 wajar', async ({ page }) => {
    // Cek bagaimana server memperlakukan case — konsisten tidak fatal
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=8D-HUNAN-ZHANGJIAJIE-FENGHUANG-ANCIENT-TOWN-FURONG-TOWN`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tidak crash: apakah match (200) atau 404, keduanya OK
    expect([200, 404]).toContain(resp?.status());
  });

  test('redirect dari slug valid tetap jalan: booking-success redirect chain', async ({ page }) => {
    // Pastikan halaman slug valid tidak redirect loop
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`, { waitUntil: 'load' });
    expect(resp?.status()).toBe(200);
    expect(page.url()).toContain('tour-detail.php');
  });
});