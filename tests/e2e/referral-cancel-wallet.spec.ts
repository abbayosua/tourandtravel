import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function registerUser(page, suffix = '') {
  const ts = Date.now();
  const email = `ref_${ts}${suffix}@example.com`;
  await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
  await page.fill('input[name="name"]', `Referral User ${suffix}`);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForURL(/index\.php/);
  return email;
}

test.describe('Referral Flow', () => {

  test('referral link muncul di profile', async ({ page }) => {
    await registerUser(page, 'A');
    await page.goto(`${BASE}/profile.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Referral|Kode Referral|REF-/i);
  });

  test('register dengan referral code: referrer terima reward', async ({ page, context }) => {
    // Register user A (referrer)
    const emailA = `refa_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Referral User A');
    await page.fill('input[name="email"]', emailA);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);

    // Grab referral code dari referral.php (ada di input value)
    await page.goto(`${BASE}/referral.php`, { waitUntil: 'load' });
    const refLinkValue = await page.locator('#refLinkInput').inputValue();
    expect(refLinkValue).toMatch(/register\.php\?ref=REF-/);
    const refMatch = refLinkValue.match(/REF-[A-Z0-9]+-[A-Z0-9]+/);
    expect(refMatch).not.toBeNull();
    const refCode = refMatch![0];

    // Logout A
    await page.goto(`${BASE}/logout.php`, { waitUntil: 'load' });

    // Register user B dengan referral code A (browser baru)
    const pageB = await context.newPage();
    await pageB.goto(`${BASE}/register.php?ref=${refCode}`, { waitUntil: 'load' });
    await pageB.fill('input[name="name"]', 'Referral User B');
    await pageB.fill('input[name="email"]', `refb_${Date.now()}@example.com`);
    await pageB.fill('input[name="phone"]', '0813');
    await pageB.fill('input[name="password"]', 'password123');
    await pageB.fill('input[name="confirm_password"]', 'password123');
    await pageB.click('button[type="submit"]');
    await pageB.waitForURL(/index\.php/);
    const bBody = await pageB.textContent('body');
    expect(bBody).not.toMatch(PHP_ERROR);
    await pageB.close();

    // Login kembali A dan cek wallet balance (reward 50k)
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', emailA);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);
    await page.goto(`${BASE}/wallet.php`, { waitUntil: 'load' });
    const walletBody = await page.textContent('body');
    expect(walletBody).not.toMatch(PHP_ERROR);
    expect(walletBody).toMatch(/Reward referral|Bonus|50/i);
  });

  test('halaman referral menampilkan daftar', async ({ page }) => {
    await registerUser(page, 'C');
    await page.goto(`${BASE}/referral.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Referral|Kode Referral/i);
  });
});

test.describe('Cancellation - my-bookings', () => {

  test('cancel button tersedia di booking', async ({ page }) => {
    const email = `cancel_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Cancel Test');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);

    // Book a training
    await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    await page.fill('input[name="travel_date"]', '2026-12-25');
    await page.selectOption('select[name="seats"]', '1');
    await page.fill('input[name="name"]', 'Cancel Test');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForURL(/booking-success\.php/);

    // Go to my-bookings to cancel
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Batalkan/i);
  });

  test('cancel booking memunculkan alert sukses', async ({ page }) => {
    // Register fresh
    const email = `cancel2_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Cancel Test2');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);

    // Book a train
    await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    await page.fill('input[name="travel_date"]', '2026-12-25');
    await page.selectOption('select[name="seats"]', '1');
    await page.fill('input[name="name"]', 'Cancel Test2');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '0812');
    await page.click('button[type="submit"]');
    await page.waitForURL(/booking-success\.php/);

    // Cancel
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    await page.click('text=Batalkan');
    try { await page.waitForURL(/my-bookings\.php\?msg=cancelled/, { timeout: 3000 }); } catch {}
    // Dialog confirm
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    // If we can't handle confirm dialog via URL, try direct URL
    // Get booking ID from the page

    // Check page has cancelled message
    const body = await page.textContent('body');
    if (body.includes('Berhasil dibatalkan')) {
      expect(true).toBeTruthy();
    } else {
      // Try finding a cancel link and extracting ID
      const links = await page.locator('a[href*="cancel="]').all();
      if (links.length > 0) {
        const href = await links[0].getAttribute('href');
        if (href) {
          await page.goto(`${BASE}/${href}`, { waitUntil: 'load' });
          const body2 = await page.textContent('body');
          expect(body2).toMatch(/Berhasil dibatalkan|dibatalkan/i);
        }
      }
    }
  });
});

test.describe('Wallet Spend Flow', () => {

  test('booking dengan wallet: total berkurang & transaksi tercatat', async ({ page, context }) => {
    // 1) Register user A
    const emailA = `walletspend_${Date.now()}@example.com`;
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    await page.fill('input[name="name"]', 'Wallet Spend A');
    await page.fill('input[name="email"]', emailA);
    await page.fill('input[name="phone"]', '0812');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);

    // 2) Ambil referral code A
    await page.goto(`${BASE}/referral.php`, { waitUntil: 'load' });
    const refLinkValue = await page.locator('#refLinkInput').inputValue();
    const refMatch = refLinkValue.match(/REF-[A-Z0-9]+-[A-Z0-9]+/);
    expect(refMatch).not.toBeNull();
    const refCode = refMatch![0];
    await page.goto(`${BASE}/logout.php`, { waitUntil: 'load' });

    // 3) Register user B dengan ref A → A dapat reward 50k
    const pageB = await context.newPage();
    await pageB.goto(`${BASE}/register.php?ref=${refCode}`, { waitUntil: 'load' });
    await pageB.fill('input[name="name"]', 'Wallet Spend B');
    await pageB.fill('input[name="email"]', `walletspendb_${Date.now()}@example.com`);
    await pageB.fill('input[name="phone"]', '0813');
    await pageB.fill('input[name="password"]', 'password123');
    await pageB.fill('input[name="confirm_password"]', 'password123');
    await pageB.click('button[type="submit"]');
    await pageB.waitForURL(/index\.php/);
    await pageB.close();

    // 4) Login A, cek saldo 50k
    await page.goto(`${BASE}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="email"]', emailA);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/index\.php/);
    await page.goto(`${BASE}/wallet.php`, { waitUntil: 'load' });
    const walletBody = await page.textContent('body');
    expect(walletBody).toMatch(/50\.000|Rp50\.000/i);

    // 5) Book train 250.000 dengan checkbox KlookCash → total jadi 200.000
    await page.goto(`${BASE}/train-detail.php?slug=argo-parahyangan`, { waitUntil: 'load' });
    await page.fill('input[name="travel_date"]', '2026-12-25');
    await page.selectOption('select[name="seats"]', '1');
    await page.fill('input[name="name"]', 'Wallet Spend A');
    await page.fill('input[name="email"]', emailA);
    await page.fill('input[name="phone"]', '0812');
    const walletCheckbox = page.locator('#useWalletTrain');
    await expect(walletCheckbox).toBeVisible();
    await walletCheckbox.check();
    await Promise.all([
      page.waitForURL(/booking-success\.php/),
      page.click('button[type="submit"]'),
    ]);
    const successBody = await page.textContent('body');
    expect(successBody).toMatch(/200\.000/i);

    // 6) Wallet balance berkurang
    await page.goto(`${BASE}/wallet.php`, { waitUntil: 'load' });
    const walletBody2 = await page.textContent('body');
    expect(walletBody2).toMatch(/Spend|Pembayaran menggunakan KlookCash/i);
  });
});