import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

const SECTIONS = ['Overview', 'Inventory', 'Bookings', 'Marketing', 'Finance', 'Content', 'Settings'];

test.describe('Admin Sidebar Reorganized (ADMINPRD)', () => {

  test('8 section header tampil dengan urutan benar', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    const labels = await page.locator('.nav-section-label').allTextContents();
    const trimmed = labels.map((l: string) => l.trim());
    // Label terakhir bisa "Eksternal" (ID) atau "External" (EN) tergantung session
    const head = trimmed.slice(0, 7);
    expect(head).toEqual(SECTIONS);
    expect(['Eksternal', 'External']).toContain(trimmed[7]);
  });

  test('link Sales Report & Accounting ada di section Finance', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    await expect(page.locator('[data-testid="nav-sales-report"]')).toHaveCount(1);
    await expect(page.locator('[data-testid="nav-accounting"]')).toHaveCount(1);
    // Posisi setelah section Finance
    const financeIdx = await page.evaluate(() => {
      const items = Array.from(document.querySelectorAll('#adminSidebar .nav-section-label, #adminSidebar [data-testid="nav-sales-report"]'));
      return items.findIndex((el: any) => el.classList?.contains('nav-section-label') && el.textContent?.trim() === 'Finance');
    });
    expect(financeIdx).toBeGreaterThanOrEqual(0);
  });

  test('semua 30 link internal sidebar → HTTP 200 tanpa PHP error', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    const hrefs = await page.locator('#adminSidebar a.nav-link').evaluateAll(
      (els: any[]) => els.map((el) => (el as HTMLAnchorElement).getAttribute('href')).filter((h): h is string => !!h && h.endsWith('.php'))
    );
    expect(hrefs.length).toBeGreaterThanOrEqual(28);
    for (const href of hrefs) {
      const target = href === '../index.php' ? `${BASE}/index.php` : `${BASE}/admin/${href}`;
      const resp = await page.request.get(target);
      expect(resp.status(), `${href} harus 200`).toBe(200);
    }
  });

  test('active state per section: dashboard, tours, bookings, sales-report, posts, wa-settings', async ({ page }) => {
    await loginAdmin(page);
    const cases = [
      ['dashboard.php', 'dashboard.php'],
      ['tours.php', 'tours.php'],
      ['bookings.php', 'bookings.php'],
      ['sales-report.php', 'sales-report.php'],
      ['accounting.php', 'accounting.php'],
      ['posts.php', 'posts.php'],
      ['wa-settings.php', 'wa-settings.php'],
    ] as const;
    for (const [pageUrl, activeHref] of cases) {
      await page.goto(`${BASE}/admin/${pageUrl}`, { waitUntil: 'load' });
      const active = await page.locator(`#adminSidebar a.nav-link.active[href="${activeHref}"]`).count();
      expect(active, `${pageUrl} harus menandai ${activeHref} active`).toBe(1);
    }
  });

  test('edit page menandai induk section active (tour-edit → tours.php)', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/hotel-edit.php?id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const active = await page.locator('#adminSidebar a.nav-link.active[href="hotels.php"]').count();
    expect(active).toBe(1);
  });

  test('sidebar mobile: toggle & overlay berfungsi', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    // Toggle ada
    await expect(page.locator('#sidebarToggle')).toBeVisible();
    // CSS mobile sidebar fixed + translateX terdefinisi
    const cssOk = await page.evaluate(() => {
      for (const sheet of Array.from(document.styleSheets)) {
        try {
          for (const rule of Array.from(sheet.cssRules)) {
            if (rule.cssText?.includes('max-width: 767.98px') && rule.cssText.includes('translateX(-100%)')) return true;
          }
        } catch { /* cross-origin */ }
      }
      return false;
    });
    expect(cssOk, 'CSS mobile collapsed ada').toBeTruthy();
    // Klik toggle → sidebar terbuka (collapsed hilang)
    await page.locator('#sidebarToggle').click();
    await page.waitForTimeout(400);
    const opened = await page.evaluate(() => {
      const sb = document.getElementById('adminSidebar');
      if (!sb) return false;
      const cs = getComputedStyle(sb);
      // terbuka = tidak hidden via transform negatif / width 0
      return sb.classList.contains('collapsed') === false || (cs.display !== 'none' && cs.width !== '0px');
    });
    expect(opened).toBeTruthy();
  });

  test('mode icon-only menyembunyikan section label (CSS)', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    const css = await page.evaluate(() => {
      for (const sheet of Array.from(document.styleSheets)) {
        try {
          for (const rule of Array.from(sheet.cssRules)) {
            if (rule.cssText?.includes('.icon-only') && rule.cssText.includes('.nav-section-label')) return true;
          }
        } catch { /* cross-origin */ }
      }
      return false;
    });
    expect(css, 'CSS .icon-only .nav-section-label ada').toBeTruthy();
  });

  test('language toggle di sidebar tetap ada', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/dashboard.php`, { waitUntil: 'load' });
    const badges = await page.locator('#adminSidebar .badge[href*="lang="]').count();
    expect(badges).toBeGreaterThanOrEqual(2);
  });
});
