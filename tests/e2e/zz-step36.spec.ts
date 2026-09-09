import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
const BASE = 'http://localhost/tourandtravel';
const dbRun = (sql: string) => execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`).toString();
const dbOne = (sql: string) => execSync(`mysql -u root tourandtravel -N -s -e "${sql.replace(/"/g, '\"')}"`).toString().trim();

async function login(page, email, pass) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', pass);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test('tab points: saldo + ledger + redeem (happy & sad)', async ({ page }) => {
  // siapkan user & points
  execSync('mysql -u root tourandtravel < /tmp/pts_seed.sql');
  const uid = Number(dbOne(`SELECT id FROM users WHERE email='e2e_pts@test.local'`));
  dbRun(`INSERT INTO points_ledger (user_id, points, reason, note) VALUES (${uid}, 500, 'earn', 'Seed test')`);
  await login(page, 'e2e_pts@test.local', 'password123');
  // tab points
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await expect(page.locator('[data-testid="points-balance"]')).toHaveText('500');
  // ledger render
  await expect(page.locator('[data-testid="points-ledger"] tbody tr').first()).toContainText('500');
  // sad: input di bawah minimum → server tolak (bypass min via evaluate)
  await page.locator('[data-testid="redeem-form"] input[name="points"]').evaluate(el => { el.removeAttribute('min'); el.removeAttribute('step'); el.value = '50'; });
  await Promise.all([page.waitForURL(/tab=points/), page.locator('[data-testid="redeem-btn"]').click()]);
  await page.waitForLoadState('load');
  expect(await page.textContent('body')).toContain('Minimal penukaran');
  // happy: tukar 200 → saldo 300 + klookcash +20rb
  await page.goto(`${BASE}/wallet.php?tab=points`);
  await page.locator('[data-testid="redeem-form"] input[name="points"]').fill('200');
  await page.locator('[data-testid="redeem-btn"]').click();
  await page.waitForLoadState('load');
  await expect(page.locator('[data-testid="points-balance"]')).toHaveText('300');
  expect(await page.textContent('body')).toContain('Berhasil menukar');
  // sad: tukar melebihi saldo → error
  await page.locator('[data-testid="redeem-form"] input[name="points"]').fill('1000');
  await page.locator('[data-testid="redeem-btn"]').click();
  await page.waitForLoadState('load');
  expect(await page.textContent('body')).toContain('tidak cukup');
  execSync(`mysql -u root tourandtravel -e "DELETE FROM points_ledger WHERE user_id=${uid}; DELETE FROM wallet_transactions WHERE user_id=${uid}; DELETE FROM users WHERE id=${uid};"`);
});
