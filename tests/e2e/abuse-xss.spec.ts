import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;
const XSS = '<script>alert(1)</script>';

async function registerUser(page) {
  const email = `xss_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', 'XSS Tester');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '081234567890');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  return email;
}

test.describe('Abuse - XSS Cross-Field', () => {

  test('XSS di search query: ter-escape', async ({ page }) => {
    await page.goto(`${BASE}/tours.php?search=${encodeURIComponent(XSS)}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Tidak ada script executable
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
  });

  test('XSS di city filter (hotels): ter-escape', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php?city=${encodeURIComponent(XSS)}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
  });

  test('XSS di rental city filter: tidak crash, ter-escape', async ({ page }) => {
    await page.goto(`${BASE}/rental-cars.php?city=${encodeURIComponent(XSS)}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
  });

  test('XSS di profile name: ter-escape setelah simpan', async ({ page }) => {
    await registerUser(page);
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    // Bypass required
    await page.evaluate(() => {
      const inp = document.querySelector('input[name="name"]');
      inp.removeAttribute('required');
      inp.value = '<script>alert(1)</script>';
    });
    // Submit via evaluate to bypass HTML5
    await page.evaluate(() => {
      document.querySelector('button[type="submit"]').click();
    });
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
    // Pastikan tidak ada tag script di output
    expect(body).toMatch(/alert\(1\)/); // text muncul sebagai teks biasa
  });

  test('XSS di ferries from field: ter-escape, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/ferries.php?from=${encodeURIComponent(XSS)}&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
  });

  test('XSS di destinasi city: ter-escape', async ({ page }) => {
    await page.goto(`${BASE}/destinasi.php?city=${encodeURIComponent(XSS)}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // textContent menampilkan <script>alert(1)</script> sebagai teks biasa
    expect(body).toMatch(/alert\(1\)/);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
  });

  test('XSS di track booking code: form, tidak executable', async ({ page }) => {
    await page.goto(`${BASE}/track.php?code=${encodeURIComponent(XSS)}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const scripts = await page.locator('script').allTextContents();
    expect(scripts.some(s => s.includes('alert(1)'))).toBe(false);
    // Form pencarian masih tampil
    expect(body).toMatch(/Cari Booking|Masukkan kode booking/i);
  });
});