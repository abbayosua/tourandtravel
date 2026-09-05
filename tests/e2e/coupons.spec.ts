import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
const BASE = 'http://localhost/tourandtravel';
function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\"')}"`).toString().trim();
}
async function login(page: import('@playwright/test').Page) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', 'admin@x.local');
  // fallback: gunakan user biasa yang dibuat register
}

test.describe('Kupon & Referral', () => {
  test('my-coupons: redirect guest ke login', async ({ page }) => {
    await page.goto(`${BASE}/my-coupons.php`);
    expect(page.url()).toContain('login.php');
  });

  test('my-coupons: user baru melihat kupon aktif & bagian kedaluwarsa', async ({ page }) => {
    const email = `cp${Date.now()}@test.local`;
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'CP');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'secret123');
    await page.fill('input[name="confirm_password"]', 'secret123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    await page.goto(`${BASE}/my-coupons.php`);
    const body = await page.textContent('body') || '';
    expect(body).toMatch(/Kupon Saya|My Coupons/i);
    expect(body).toMatch(/Tersedia|Available/i);
    dbRun(`DELETE FROM users WHERE email='${email}';`);
  });

  test('referral: leaderboard render', async ({ page }) => {
    const email = `rf${Date.now()}@test.local`;
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'RF');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'secret123');
    await page.fill('input[name="confirm_password"]', 'secret123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    await page.goto(`${BASE}/referral.php`);
    const body = await page.textContent('body') || '';
    expect(body).toMatch(/Top Referrer/i);
    dbRun(`DELETE FROM users WHERE email='${email}';`);
  });
});
