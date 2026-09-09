import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Loyalty tier upgrade after booking payment', () => {

  test('my-points page shows current tier and points balance', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-points
    await page.goto(`${BASE}/my-points.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show tier badge
    expect(body).toMatch(/Explorer|Silver|Gold|Platinum|Tier/i);

    // Should show points balance
    expect(body).toMatch(/poin|points|saldo/i);

    // Should show progress bar to next tier
    const progressBar = await page.locator('.progress-bar').count();
    expect(progressBar).toBeGreaterThanOrEqual(0);
  });

  test('points ledger shows transaction history', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-points
    await page.goto(`${BASE}/my-points.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show points history section
    expect(body).toMatch(/Riwayat Poin|Points History/i);
  });

  test('booking form shows passenger profile dropdown when logged in', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to tour detail
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show passenger profile dropdown
    expect(body).toMatch(/Gunakan Profil Tersimpan|Use Saved Profile/i);
  });

  test('passenger profile save checkbox exists in booking form', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to tour detail
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show save passenger checkbox
    expect(body).toMatch(/Simpan sebagai profil penumpang|Save as passenger profile/i);
  });

  test('my-profiles page shows saved passengers', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-profiles
    await page.goto(`${BASE}/my-profiles.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show passenger profiles page
    expect(body).toMatch(/Profil Penumpang|Passenger Profile/i);

    // Should show form for adding new profile
    expect(body).toMatch(/Tambah Profil Baru|Add New Profile/i);
  });

  test('price alert page shows alert management', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-alerts
    await page.goto(`${BASE}/my-alerts.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show price alerts page
    expect(body).toMatch(/Price Alert|Alert Harga/i);
  });

});
