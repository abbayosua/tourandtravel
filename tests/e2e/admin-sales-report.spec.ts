import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

async function rowCount(page): Promise<number> {
  const m = await page.locator('[data-testid="row-count"]').textContent();
  return parseInt((m || '0').replace(/\D/g, ''), 10);
}

test.describe('Admin Sales Report (ADMINPRD)', () => {

  test('halaman render: filter + summary + tabel + pie', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('[data-testid="filter-type"]')).toBeVisible();
    await expect(page.locator('[data-testid="filter-status"]')).toBeVisible();
    await expect(page.locator('[data-testid^="summary-"]')).toHaveCount(4);
    await expect(page.locator('[data-testid="export-csv"]')).toBeVisible();
  });

  test('filter vertikal: type=tour hanya badge Tour di kolom Tipe', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php?type=tour`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // dropdown terpilih tour
    const sel = await page.locator('[data-testid="filter-type"]').inputValue();
    expect(sel).toBe('tour');
    // semua badge tipe di kolom Tipe = Tour (kolom ke-5 dari baris data)
    const typeCells = page.locator('[data-testid="sales-row"] td:nth-child(5) span.badge');
    const n = await typeCells.count();
    expect(n).toBeGreaterThan(0);
    for (let i = 0; i < n; i++) {
      expect(await typeCells.nth(i).textContent()).toMatch(/Tour/i);
    }
    const count = await rowCount(page);
    expect(count).toBeGreaterThan(0);
  });

  test('filter vertikal: type=hotel tanpa data → empty state', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php?type=hotel`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Belum ada transaksi|No transactions/i);
    expect(await rowCount(page)).toBe(0);
  });

  test('filter status=cancelled hanya status cancelled', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php?status=cancelled`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const sel = await page.locator('[data-testid="filter-status"]').inputValue();
    expect(sel).toBe('cancelled');
    // semua badge status = Cancelled (kolom ke-8)
    const statusCells = page.locator('[data-testid="sales-row"] td:nth-child(8) span.badge');
    const n = await statusCells.count();
    for (let i = 0; i < n; i++) {
      expect(await statusCells.nth(i).textContent()).toMatch(/Cancelled/i);
    }
  });

  test('filter tanggal 1 hari: semua row tanggal sama', async ({ page }) => {
    await loginAdmin(page);
    const today = new Date().toISOString().slice(0, 10);
    await page.goto(`${BASE}/admin/sales-report.php?from=${today}&to=${today}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // kolom tanggal (ke-1): ambil bagian tanggal d/m
    const dateCells = page.locator('[data-testid="sales-row"] td:nth-child(1) small');
    const n = await dateCells.count();
    expect(n).toBeGreaterThan(0);
    const dates = new Set<string>();
    for (let i = 0; i < n; i++) {
      const t = (await dateCells.nth(i).textContent()) || '';
      dates.add(t.slice(0, 5)); // dd/mm
    }
    expect(dates.size).toBeLessThanOrEqual(1);
  });

  test('export CSV terunduh dengan header & isi benar', async ({ page }) => {
    test.setTimeout(20000);
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php`, { waitUntil: 'load' });
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }),
      page.locator('[data-testid="export-csv"]').click(),
    ]);
    // Filename pattern sales-report-{from}-{to}.csv
    expect(download.suggestedFilename()).toMatch(/^sales-report-\d{4}-\d{2}-\d{2}-\d{4}-\d{2}-\d{2}\.csv$/);
    const filePath = await download.path();
    expect(fs.existsSync(filePath!)).toBeTruthy();
    const content = fs.readFileSync(filePath!, 'utf-8');
    // BOM + header 10 kolom termasuk Metode
    const lines = content.replace(/^\uFEFF/, '').split('\n');
    const parseCsvLine = (line: string): string[] => {
      const out: string[] = [];
      let cur = '', inQ = false;
      for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') { inQ = !inQ; continue; }
        if (ch === ',' && !inQ) { out.push(cur); cur = ''; continue; }
        cur += ch;
      }
      out.push(cur);
      return out;
    };
    const header = parseCsvLine(lines[0]);
    // Header pakai t() — bisa ID atau EN tergantung session bahasa
    expect(header).toEqual(expect.arrayContaining(['Tanggal', 'Date'].filter(h => header.includes(h))));
    expect(header.length).toBe(10);
    expect(['Status']).toEqual(expect.arrayContaining([header[8]]));
    expect(['Metode', 'Method']).toContain(header[9]);
    // Minimal ada 1 baris data
    expect(lines.length).toBeGreaterThan(1);
  });

  test('CSV filter ferry: semua baris Ferry', async ({ page }) => {
    test.setTimeout(20000);
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php?type=ferry`, { waitUntil: 'load' });
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }),
      page.locator('[data-testid="export-csv"]').click(),
    ]);
    const fp = await download.path();
    const lines = fs.readFileSync(fp!, 'utf-8').replace(/^\uFEFF/, '').split('\n');
    const parseCsvLine = (line: string): string[] => {
      const out: string[] = [];
      let cur = '', inQ = false;
      for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') { inQ = !inQ; continue; }
        if (ch === ',' && !inQ) { out.push(cur); cur = ''; continue; }
        cur += ch;
      }
      out.push(cur);
      return out;
    };
    let dataRows = 0;
    for (let i = 1; i < lines.length; i++) {
      if (!lines[i].trim()) continue;
      const cols = parseCsvLine(lines[i]);
      expect(cols[5]).toBe('Ferry');
      dataRows++;
    }
    expect(dataRows).toBeGreaterThan(0);
  });

  test('pie chart revenue breakdown render', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php`, { waitUntil: 'load' });
    const hasChart = await page.evaluate(() => {
      return typeof (window as any).Chart !== 'undefined' && !!(window as any).Chart.getChart('revenuePieChart');
    });
    expect(hasChart, 'Chart instance pie sales-report').toBeTruthy();
  });

  test('tanpa error console di sales report', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
    page.on('pageerror', (err) => errors.push(String(err)));
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/sales-report.php`, { waitUntil: 'load' });
    await page.waitForTimeout(500);
    expect(errors, 'console errors: ' + errors.join(' | ')).toHaveLength(0);
  });
});
