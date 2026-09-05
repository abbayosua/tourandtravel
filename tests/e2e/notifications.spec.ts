import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\"')}"`).toString().trim();
}

test.describe('Notifications — user terkait saja, mark-read, badge', () => {
  const email = `notif${Date.now()}@test.local`;
  let userId = 0;

  test.afterAll(() => {
    dbRun(`DELETE FROM notifications WHERE user_id=${userId}; DELETE FROM users WHERE email='${email}';`);
  });

  test('register → lonceng tampil; notif user terkait saja', async ({ page }) => {
    await page.goto(`${BASE}/register.php`);
    await page.fill('input[name="name"]', 'Notif User');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'secret123');
    await page.fill('input[name="confirm_password"]', 'secret123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    userId = Number(dbOne(`SELECT id FROM users WHERE email='${email}'`));
    expect(userId).toBeGreaterThan(0);

    // user lain punya notif — tidak boleh bocor
    dbRun(`INSERT INTO notifications (user_id, type, title) VALUES (${userId + 1000}, 'info', 'NOTIF USER LAIN')`);

    // seed notif untuk user ini
    dbRun(`INSERT INTO notifications (user_id, type, title, link) VALUES (${userId}, 'status', 'Status booking: confirmed', 'track.php?code=X')`);

    await page.goto(`${BASE}/notifications.php`);
    const body = await page.textContent('body') || '';
    expect(body).toContain('Status booking: confirmed');
    expect(body).not.toContain('NOTIF USER LAIN');
    await expect(page.locator('a[href*="read_all"]')).toBeVisible();
  });

  test('mark read: badge hilang setelah tandai semua dibaca', async ({ page }) => {
    // login
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'secret123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    // badge unread tampil
    await page.goto(`${BASE}/index.php`);
    await expect(page.locator('a[href="notifications.php"] .badge')).toBeVisible();

    // tandai semua
    await page.goto(`${BASE}/notifications.php?read_all=1`);
    await page.waitForLoadState('load');
    expect(dbOne(`SELECT COUNT(*) FROM notifications WHERE user_id=${userId} AND read_at IS NULL`)).toBe('0');

    // badge hilang
    await page.goto(`${BASE}/index.php`);
    await expect(page.locator('a[href="notifications.php"] .badge')).toHaveCount(0);
  });

  test('guest: redirect ke login', async ({ page }) => {
    await page.goto(`${BASE}/notifications.php`);
    expect(page.url()).toContain('login.php');
  });
});
