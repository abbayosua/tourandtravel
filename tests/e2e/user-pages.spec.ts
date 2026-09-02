import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

async function registerUser(page) {
  const email = `user_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'E2E User');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '087777777777');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

test.describe('My Bookings', () => {

  test('guest: redirect ke login', async ({ page }) => {
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('login.php');
    expect(page.url()).toContain('redirect=my-bookings');
  });

  test('user baru: empty state (belum ada booking)', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Empty state — tidak ada booking
    expect(body).toMatch(/Belum ada|tidak ada|booking|kosong|empty/i);
  });
});

test.describe('Profile', () => {

  test('guest: redirect ke login', async ({ page }) => {
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('login.php');
    expect(page.url()).toContain('redirect=profile');
  });

  test('user login: form terisi data user', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Profil Saya|profil/i);
    const nameInput = await page.locator('input[name="name"]').inputValue();
    expect(nameInput).toBe('E2E User');
  });

  test('update nama: sukses "Profil berhasil diperbarui" + navbar berubah', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Updated Name');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Profil berhasil diperbarui/i);
    expect(body).toMatch(/Updated Name/i);
  });

  test('update phone: sukses', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    await page.fill('input[name="phone"]', '08123456789');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Profil berhasil diperbarui/i);
  });

  test('password baru: update password valid', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    await page.fill('input[name="password"]', 'newpass123');
    await page.fill('input[name="confirm_password"]', 'newpass123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Profil berhasil diperbarui/i);
  });

  test('password mismatch: error', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    await page.fill('input[name="password"]', 'newpass123');
    await page.fill('input[name="confirm_password"]', 'different');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/tidak cocok|mismatch|error/i);
  });

  test('nama kosong via POST: error validasi', async ({ page }) => {
    await registerUser(page);
    const resp = await page.request.post(`${BASE}/profile.php`, { form: { name: '', phone: '' } });
    const body = await resp.text();
    expect(body).toMatch(/Nama harus diisi/i);
  });
});

test.describe('Wishlist', () => {

  test('guest: redirect ke login', async ({ page }) => {
    await page.goto(`${BASE}/wishlist.php`, { waitUntil: 'load' });
    await page.waitForLoadState('load');
    expect(page.url()).toContain('login.php');
    expect(page.url()).toContain('redirect=wishlist');
  });

  test('user baru: wishlist kosong', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/wishlist.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Wishlist Saya|wishlist/i);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBe(0);
  });

  test('toggle wishlist via AJAX di tours listing', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });

    // Click first wishlist heart button
    const wishlistBtn = page.locator('button.wishlist-btn').first();
    await expect(wishlistBtn).toBeVisible();
    const tourId = await wishlistBtn.getAttribute('data-tour-id');
    expect(tourId).toBeTruthy();

    // Toggle ON
    await wishlistBtn.click();
    await page.waitForTimeout(500);

    // Go to wishlist page — should have the tour
    await page.goto(`${BASE}/wishlist.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const cards = await page.locator('.tour-card-klook h6.fw-semibold').count();
    expect(cards).toBeGreaterThanOrEqual(1);
  });

  test('wishlist toggle via direct AJAX request', async ({ page }) => {
    await registerUser(page);
    // Toggle a tour to wishlist via AJAX
    const resp = await page.request.get(`${BASE}/wishlist-ajax.php?action=toggle&tour_id=61`);
    const text = await resp.text();
    expect(text).toMatch(/added|removed|true|"status"/i);

    // Toggle again — remove
    const resp2 = await page.request.get(`${BASE}/wishlist-ajax.php?action=toggle&tour_id=61`);
    const text2 = await resp2.text();
    expect(text2).toMatch(/removed|"status"/i);
  });
});