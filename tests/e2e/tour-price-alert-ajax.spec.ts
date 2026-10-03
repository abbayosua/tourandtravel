import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Regresi UI/UX: form "Set Price Alert" di tour-detail.php dulu POST biasa ke
 * price-alert-ajax.php, sehingga user diarahkan ke halaman JSON mentah
 * ({"success":true,...}) — alur membingungkan, bukan feedback di modal.
 *
 * Sekarang disubmit via fetch: modal tetap terbuka, pesan sukses/gagal tampil,
 * dan user tidak pernah meninggalkan halaman.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const AUTH_EMAIL = 'e2e-pricealert@t.local';
const AUTH_PASS = 'e2epass123';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

let userId = 0;
let slug = '';
let hotelSlug = '';

async function login(page: Page) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', AUTH_EMAIL);
  await page.fill('input[name="password"]', AUTH_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.beforeAll(() => {
  const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(AUTH_PASS)}, PASSWORD_DEFAULT);`], {
    encoding: 'utf8',
  }).trim();
  mysql(
    `INSERT INTO users (name, email, password_hash, role) VALUES ('E2E Alert', '${AUTH_EMAIL}', '${hash}', 'user') ` +
      `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
  );
  userId = Number(mysql(`SELECT id FROM users WHERE email = '${AUTH_EMAIL}'`));
  slug = mysql('SELECT slug FROM tours WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
  hotelSlug = mysql('SELECT slug FROM hotels WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
});

test.afterAll(() => {
  if (userId) {
    mysql(`DELETE FROM price_alerts WHERE user_id = ${userId}`);
    try {
      mysql(`DELETE FROM users WHERE id = ${userId}`);
    } catch {
      // abaikan bila masih direferensikan
    }
  }
});

test('submit price alert tetap di halaman dan menampilkan pesan sukses', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${encodeURIComponent(slug)}`);
  await page.click('#priceAlertBtn');
  await expect(page.locator('#priceAlertModal')).toBeVisible();

  await page.locator('#priceAlertForm input[name="target_price"]').fill('1000');
  await page.locator('#priceAlertSubmit').click();

  // Tidak boleh berpindah ke price-alert-ajax.php (halaman JSON mentah).
  await expect(page.locator('#priceAlertMsg')).toContainText('Alert harga disimpan!');
  expect(page.url()).not.toContain('price-alert-ajax');

  await expect(page.locator('#priceAlertModal')).toHaveScreenshot('tour-price-alert-success.png', {
    maxDiffPixelRatio: 0.1,
  });
});

test('kegagalan simpan menampilkan pesan error, bukan navigasi', async ({ page }) => {
  await page.route('**/price-alert-ajax.php', (route) => route.fulfill({ status: 500, body: '{"error":"boom"}' }));
  await login(page);
  await page.goto(`${BASE}/tour-detail.php?slug=${encodeURIComponent(slug)}`);
  await page.click('#priceAlertBtn');
  await expect(page.locator('#priceAlertModal')).toBeVisible();

  await page.locator('#priceAlertForm input[name="target_price"]').fill('1000');
  await page.locator('#priceAlertSubmit').click();

  await expect(page.locator('#priceAlertMsg')).toContainText('Gagal menyimpan alert');
  expect(page.url()).not.toContain('price-alert-ajax');
});

test('hotel-detail: price alert tidak menavigasi ke JSON dan modal bisa dipakai', async ({ page }) => {
  await login(page);
  await page.goto(`${BASE}/hotel-detail.php?slug=${encodeURIComponent(hotelSlug)}`);
  await page.click('[data-bs-target="#priceAlertModalHotel"]');
  await expect(page.locator('#priceAlertModalHotel')).toBeVisible();

  // Modal harus benar-benar bisa diklik (tidak tertutup modal-backdrop).
  const box = (await page.locator('#priceAlertSubmitHotel').boundingBox())!;
  const onTop = await page.evaluate(
    ([x, y]) => {
      const el = document.elementFromPoint(x, y);
      return !!(el && el.closest('#priceAlertSubmitHotel'));
    },
    [box.x + box.width / 2, box.y + box.height / 2] as const
  );
  expect(onTop).toBe(true);

  await page.locator('#priceAlertFormHotel input[name="target_price"]').fill('1000');
  await page.locator('#priceAlertSubmitHotel').click();

  await expect(page.locator('#priceAlertMsgHotel')).toContainText('Alert harga disimpan!');
  expect(page.url()).not.toContain('price-alert-ajax');
});
