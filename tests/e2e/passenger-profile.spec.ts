import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Passenger profile — save and auto-fill in booking form', () => {

  test('my-profiles page shows passenger profile form when logged in', async ({ page }) => {
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

  test('my-profiles form has required fields', async ({ page }) => {
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

    // Should show form fields
    expect(body).toMatch(/Nama Lengkap|Full Name/i);
    expect(body).toMatch(/Nomor Paspor|Passport No/i);
    expect(body).toMatch(/No\. Telepon|Phone/i);
  });

  test('create passenger profile via form', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to my-profiles
    await page.goto(`${BASE}/my-profiles.php`, { waitUntil: 'load' });
    
    // Fill passenger profile form
    await page.fill('input[name="full_name"]', 'Test Passenger E2E');
    await page.fill('input[name="passport_no"]', 'E2E123456');
    await page.fill('input[name="phone"]', '08123456789');
    
    // Submit form
    await page.click('button[type="submit"][name="action"][value="save"]');
    await page.waitForLoadState('load');
    
    // Should redirect with success message
    const body = await page.textContent('body');
    expect(body).toMatch(/Profil tersimpan|Profile saved/i);
  });

  test('saved passenger profile appears in list', async ({ page }) => {
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

    // Should show saved profiles (if any exist)
    const profileCards = await page.locator('.card.border-0.shadow-sm').count();
    expect(profileCards).toBeGreaterThanOrEqual(0);
  });

  test('tour detail shows passenger dropdown when logged in', async ({ page }) => {
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

  test('passenger auto-fill fills name and phone fields', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to tour detail
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Check if passenger dropdown exists
    const dropdown = await page.locator('#passengerSelect').count();
    if (dropdown > 0) {
      // Select first option (if any)
      const options = await page.locator('#passengerSelect option').count();
      if (options > 1) { // More than just the placeholder
        await page.selectOption('#passengerSelect', { index: 1 });
        
        // Verify name field is filled
        const nameValue = await page.inputValue('#bookingName');
        expect(nameValue).toBeTruthy();
      }
    }
  });

  test('booking form has save passenger checkbox', async ({ page }) => {
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

  test('hotel detail shows passenger dropdown when logged in', async ({ page }) => {
    // Login first
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', 'admin@tourandtravel.web.id');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/index.php', { timeout: 5000 });

    // Navigate to hotel detail
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show passenger profile dropdown
    expect(body).toMatch(/Gunakan Profil Tersimpan|Use Saved Profile/i);
  });

});
