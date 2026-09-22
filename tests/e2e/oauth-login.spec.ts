import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';

/**
 * Fase 1: OAuth Google login.
 * Token Google asli tidak bisa dimock di level jaringan (tokeninfo endpoint eksternal),
 * jadi happy path diverifikasi via unit OAuthTest.php. Di E2E ini kita uji:
 * - TC-110a: API sad path — credential invalid → 401 + JSON error, session TIDAK dibuat
 * - TC-110b: API sad path — credential kosong → error invalid_credential
 * - TC-110c: API sad path — GET method → 405 method_not_allowed
 * - TC-110d: halaman login.php masih render normal (fitur off: divider "atau", tanpa tombol)
 * - TC-110e: register.php tetap bisa daftar + login form flow normal setelah partial ditambah
 */

async function postCredential(request: any, credential: string) {
  return request.post(`${BASE}/api/oauth-google.php`, {
    data: { credential },
    headers: { 'Content-Type': 'application/json' },
  });
}

test.describe('OAuth Google login (Fase 1)', () => {
  test('TC-110a credential invalid ditolak 401 tanpa session', async ({ page, request }) => {
    const res = await postCredential(request, 'eyJhbGciOiJSUzI1NiJ9.fake.payload');
    expect(res.status()).toBe(401);
    const body = await res.json();
    expect(body.success).toBe(false);
    expect(['invalid_token', 'invalid_payload']).toContain(body.error);
    // pastikan tidak ada session user
    await page.goto(`${BASE}/my-bookings.php`);
    expect(page.url()).toContain('login.php');
  });

  test('TC-110b credential kosong → invalid_credential', async ({ request }) => {
    const res = await postCredential(request, '');
    expect(res.status()).toBe(400);
    const body = await res.json();
    expect(body.success).toBe(false);
    expect(body.error).toBe('invalid_credential');
  });

  test('TC-110c GET method ditolak 405', async ({ request }) => {
    const res = await request.get(`${BASE}/api/oauth-google.php`);
    expect(res.status()).toBe(405);
    const body = await res.json();
    expect(body.error).toBe('method_not_allowed');
  });

  test('TC-110d login.php render normal tanpa GOOGLE_CLIENT_ID', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    const body = await page.textContent('body');
    // fitur off → divider "atau" tampil, tanpa div tombol Google
    expect(body).toContain('atau');
    expect(await page.locator('#g_id_signin').count()).toBe(0);
    // form login biasa tetap berfungsi
    await page.fill('input[name="email"]', 'wrong@example.com');
    await page.fill('input[name="password"]', 'wrongpass');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('TC-110e register.php form normal setelah partial oauth', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="confirm_password"]')).toBeVisible();
    // HTML5 validation menahan submit kosong
    await page.click('button[type="submit"]');
    await expect(page.locator('input[name="name"]:invalid')).toHaveCount(1);
  });
});
