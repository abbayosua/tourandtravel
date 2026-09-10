import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined)/i;

function cleanupReseller(email: string) {
  try {
    execSync(`mysql -u root tourandtravel -e "DELETE FROM users WHERE email = '${email}'"`, { stdio: 'pipe' });
  } catch {}
}

test.describe('Reseller Register', () => {
  const email = `reseller_e2e_${Date.now()}@example.com`;

  test.afterAll(() => {
    cleanupReseller(email);
  });

  test('register page shows reseller checkbox', async ({ page }) => {
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toContain('Reseller');
  });

  test('register as reseller sets role to reseller', async ({ page }) => {
    await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });

    await page.fill('input[name="name"]', 'E2E Reseller');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '081234567890');
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="confirm_password"]', 'password123');
    await page.check('input[name="as_reseller"]');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should redirect to index after successful registration
    expect(page.url()).toContain('index.php');

    // Verify role in DB
    const result = execSync(
      `mysql -u root tourandtravel -N -e "SELECT role FROM users WHERE email = '${email}'"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(result).toBe('reseller');
  });

  test('register without reseller checkbox sets role to user', async ({ page }) => {
    const userEmail = `regular_e2e_${Date.now()}@example.com`;
    try {
      await page.goto(`${BASE}/register.php`, { waitUntil: 'load' });

      await page.fill('input[name="name"]', 'E2E Regular');
      await page.fill('input[name="email"]', userEmail);
      await page.fill('input[name="phone"]', '081234567891');
      await page.fill('input[name="password"]', 'password123');
      await page.fill('input[name="confirm_password"]', 'password123');
      // Do NOT check as_reseller
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle');

      expect(page.url()).toContain('index.php');

      const result = execSync(
        `mysql -u root tourandtravel -N -e "SELECT role FROM users WHERE email = '${userEmail}'"`,
        { encoding: 'utf-8' }
      ).trim();
      expect(result).toBe('user');
    } finally {
      cleanupReseller(userEmail);
    }
  });
});
