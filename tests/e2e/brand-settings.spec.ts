import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const ADMIN = `${BASE}/admin`;

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' }).toString().trim();
}

test.describe('Brand custom dari admin', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${ADMIN}/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard/, { timeout: 15000 });
  });

  test.afterEach(() => {
    dbRun("DELETE FROM settings WHERE setting_key IN ('site_name','site_logo','site_tagline')");
  });

  test('simpan nama tampil di navbar + footer', async ({ page }) => {
    await page.goto(`${ADMIN}/brand-settings.php`, { waitUntil: 'load' });
    await page.fill('[data-testid="brand-name"]', 'WisataKita');
    await page.click('[data-testid="brand-save"]');
    await expect(page.locator('[data-testid="brand-saved"]')).toBeVisible({ timeout: 10000 });
    expect(dbOne("SELECT setting_value FROM settings WHERE setting_key='site_name'")).toBe('WisataKita');

    await page.goto(`${BASE}/index.php`, { waitUntil: 'load' });
    await expect(page.locator('[data-testid="brand-nav"]')).toContainText('WisataKita');
    const body = await page.textContent('body');
    expect(body).toMatch(/WisataKita/);
    expect(body).not.toMatch(/TourAndTravel/);
  });

  test('manifest.php ikut nama custom', async ({ page }) => {
    dbRun("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name','WisataKita') ON DUPLICATE KEY UPDATE setting_value='WisataKita'");
    const r = await page.request.get(`${BASE}/manifest.php`);
    expect(r.status()).toBe(200);
    const j = await r.json();
    expect(j.name).toBe('WisataKita');
  });
});
