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

test.describe('Admin Dashboard Enhanced (ADMINPRD)', () => {

  test('login admin → dashboard dengan 8 KPI card', async ({ page }) => {
    await loginAdmin(page);
    expect(page.url()).toContain('admin/dashboard.php');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // 8 KPI cards dengan data-testid
    const kpis = page.locator('[data-testid^="kpi-"]');
    await expect(kpis).toHaveCount(8);

    // Semua label KPI ada (ID atau EN)
    expect(body).toMatch(/Tour Aktif|Active Tours/i);
    expect(body).toMatch(/Total Booking/i);
    expect(body).toMatch(/Pending/i);
    expect(body).toMatch(/Confirmed|Terkonfirmasi/i);
    expect(body).toMatch(/Revenue|Pendapatan/i);
    expect(body).toMatch(/Net Profit|Laba Bersih/i);
    expect(body).toMatch(/Avg Order Value|Rata-rata/i);
    expect(body).toMatch(/Conversion Rate|Tingkat Konversi/i);
  });

  test('KPI cards berisi angka/format valid', async ({ page }) => {
    await loginAdmin(page);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Revenue dalam format Rp
    expect(body).toMatch(/Rp\s?[\d.]+/);
    // Conversion rate dalam persen
    expect(body).toMatch(/\d+(\.\d+)?%/);
  });

  test('2 chart canvas render dengan instance Chart.js aktif', async ({ page }) => {
    await loginAdmin(page);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    for (const id of ['revenueTrendChart', 'verticalBarChart']) {
      const canvas = page.locator(`#${id}`);
      await expect(canvas).toHaveCount(1);
      // Chart.js benar-benar menginisialisasi instance untuk canvas ini
      const hasChart = await page.evaluate((cid) => {
        return typeof (window as any).Chart !== 'undefined' && !!(window as any).Chart.getChart(cid);
      }, id);
      expect(hasChart, `Chart instance ${id} aktif`).toBeTruthy();
    }
  });

  test('chart data terisi (trend 30 titik, 8 vertikal)', async ({ page }) => {
    await loginAdmin(page);
    // trendData di-inject via json_encode — harus parse-able dan 30 titik
    const trendLen = await page.evaluate(() => {
      const m = document.documentElement.innerHTML.match(/var trendData = (\[[\s\S]*?\]);/);
      return m ? JSON.parse(m[1]).length : -1;
    });
    expect(trendLen).toBe(30);

    const vertLen = await page.evaluate(() => {
      const m = document.documentElement.innerHTML.match(/var vertData = (\[[\s\S]*?\]);/);
      return m ? JSON.parse(m[1]).length : -1;
    });
    expect(vertLen).toBe(8);
  });

  test('quick actions panel dengan 4 link', async ({ page }) => {
    await loginAdmin(page);
    const panel = page.locator('[data-testid="quick-actions"]');
    await expect(panel).toBeVisible();
    const links = panel.locator('a.btn');
    expect(await links.count()).toBe(4);
    // Link tujuan benar
    expect(await panel.locator('a[href="bookings.php"]').count()).toBe(1);
    expect(await panel.locator('a[href="sales-report.php"]').count()).toBe(1);
    expect(await panel.locator('a[href="accounting.php"]').count()).toBe(1);
    expect(await panel.locator('a[href="tour-add.php"]').count()).toBe(1);
  });

  test('activity feed maksimal 10 item', async ({ page }) => {
    await loginAdmin(page);
    const feed = page.locator('[data-testid="activity-feed"]');
    await expect(feed).toBeVisible();
    const items = feed.locator('.d-flex.align-items-start');
    expect(await items.count()).toBeLessThanOrEqual(10);
  });

  test('recent bookings tabel union semua vertikal (badge tipe)', async ({ page }) => {
    await loginAdmin(page);
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Booking Terbaru|Recent Bookings/i);
    // tabel recent bookings tampil (baris booking atau empty state)
    expect(body).toMatch(/Tour|Hotel|Ferry|Pesawat|Flight|Belum ada/i);
  });

  test('tanpa error console di dashboard', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
    page.on('pageerror', (err) => errors.push(String(err)));
    await loginAdmin(page);
    await page.waitForTimeout(500);
    expect(errors, 'console errors: ' + errors.join(' | ')).toHaveLength(0);
  });
});
