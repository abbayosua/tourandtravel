import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\\"')}"`).toString().trim();
}

test.describe('Polish — dark mode, PWA, manifest, editor EN', () => {

  test('manifest.json reachable & valid JSON', async ({ page }) => {
    const r = await page.request.get(`${BASE}/manifest.json`);
    expect(r.status()).toBe(200);
    const j = await r.json();
    expect(j.name).toBe('TourAndTravel');
  });

  test('sw.js reachable', async ({ page }) => {
    const r = await page.request.get(`${BASE}/sw.js`);
    expect(r.status()).toBe(200);
  });

  test('dark mode: toggle menetapkan data-theme + persist', async ({ page }) => {
    await page.goto(`${BASE}/index.php`);
    await page.click('#themeToggle');
    const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
    expect(['dark', 'light']).toContain(theme);
    await page.reload();
    const persisted = await page.evaluate(() => document.documentElement.getAttribute('data-theme') || 'light');
    expect(persisted).toBe(theme);
    await page.evaluate(() => localStorage.removeItem('theme'));
  });

  test('skip-link a11y ada', async ({ page }) => {
    await page.goto(`${BASE}/index.php`);
    expect(await page.locator('.skip-link').count()).toBeGreaterThan(0);
  });

  test('recently viewed: kunjungi detail → tersimpan di localStorage', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town`);
    await page.waitForLoadState('networkidle');
    const viewed = await page.evaluate(() => JSON.parse(localStorage.getItem('recentlyViewed') || '[]'));
    expect(viewed.length).toBeGreaterThan(0);
    expect(viewed[0].slug).toBe('8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town');
  });

  test('editor EN transfer: simpan name_en → tersimpan di DB', async ({ page }) => {
    await page.goto(`${BASE}/admin/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');
    await page.goto(`${BASE}/admin/transfer-edit.php?id=1`);
    await page.fill('input[name="name_en"]', 'E2E Transfer EN Name');
    await page.click('form button[type="submit"]');
    await page.waitForLoadState('load');
    expect(dbOne(`SELECT name_en FROM transfers WHERE id=1`)).toBe('E2E Transfer EN Name');
    // restore
    dbRun(`UPDATE transfers SET name_en='Soekarno-Hatta Airport Transfer' WHERE id=1`);
  });
});
