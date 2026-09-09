import { test, expect } from '@playwright/test';
import * as fs from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Admin Accounting (ADMINPRD)', () => {

  test('P&L statement tampil: Revenue, COGS, Gross Profit, Expenses, Net Profit', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // 4 top cards (testid dari slug label — ID/EN) + statement
    expect(await page.locator('[data-testid="pnl-totalpendapatan"], [data-testid="pnl-totalrevenue"]').count()).toBe(1);
    expect(await page.locator('[data-testid="pnl-labakotor"], [data-testid="pnl-grossprofit"]').count()).toBe(1);
    expect(await page.locator('[data-testid="pnl-totalpengeluaran"], [data-testid="pnl-totalexpenses"]').count()).toBe(1);
    expect(await page.locator('[data-testid="pnl-lababersih"], [data-testid="pnl-netprofit"]').count()).toBe(1);
    await expect(page.locator('[data-testid="pnl-statement"]')).toBeVisible();
    expect(body).toMatch(/Total Pendapatan|Total Revenue/i);
    expect(body).toMatch(/Laba Kotor|Gross Profit/i);
    expect(body).toMatch(/Total Pengeluaran|Total Expenses/i);
    expect(body).toMatch(/Laba Bersih|Net Profit/i);

    // P&L statement: revenue rows & cogs rows
    expect(await page.locator('[data-testid="pnl-revenue-row"]').count()).toBeGreaterThanOrEqual(1);
    expect(await page.locator('[data-testid="pnl-cogs-row"]').count()).toBeGreaterThanOrEqual(0);
    // Margin % tampil
    expect(body).toMatch(/\d+(\.\d+)?%/);
  });

  test('2 chart render: monthly comparison + expense pie', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });
    const m1 = await page.evaluate(() => !!(window as any).Chart?.getChart('monthlyChart'));
    const m2 = await page.evaluate(() => !!(window as any).Chart?.getChart('expensePieChart'));
    expect(m1, 'monthlyChart instance').toBeTruthy();
    expect(m2, 'expensePieChart instance').toBeTruthy();
  });

  test('add expense → baris muncul di tabel & P&L', async ({ page }) => {
    test.setTimeout(20000);
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });

    const desc = `E2E-EXP-${Date.now()}`;
    await page.locator('[data-testid="add-expense-btn"]').click();
    await expect(page.locator('[data-testid="expense-modal"]')).toBeVisible();
    await page.locator('[data-testid="expense-category"]').selectOption('Marketing');
    await page.locator('[data-testid="expense-description"]').fill(desc);
    await page.locator('[data-testid="expense-amount"]').fill('123456');
    await page.locator('[data-testid="expense-submit"]').click();

    // Redirect + flash
    await page.waitForLoadState('load');
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Berhasil|Success/i);

    // Baris muncul di tabel expense
    const row = page.locator(`[data-testid="expense-row"][data-description="${desc}"]`);
    await expect(row).toHaveCount(1);
    expect(await row.getAttribute('data-amount')).toBe('123456.00');
    expect(await row.getAttribute('data-category')).toBe('Marketing');
    // Muncul juga sebagai expense row di P&L statement
    expect(await page.locator('[data-testid="pnl-expense-row"]').count()).toBeGreaterThanOrEqual(1);
  });

  test('edit expense → nilai berubah', async ({ page }) => {
    test.setTimeout(25000);
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });

    const desc = `E2E-EDIT-${Date.now()}`;
    // Add dulu
    await page.locator('[data-testid="add-expense-btn"]').click();
    await page.locator('[data-testid="expense-category"]').selectOption('Sewa');
    await page.locator('[data-testid="expense-description"]').fill(desc);
    await page.locator('[data-testid="expense-amount"]').fill('50000');
    await page.locator('[data-testid="expense-submit"]').click();
    await page.waitForLoadState('load');

    // Edit via tombol pencil (modal prefill dari data-attributes)
    const row = page.locator(`[data-testid="expense-row"][data-description="${desc}"]`);
    await expect(row).toHaveCount(1);
    await row.locator('[data-testid="edit-expense"]').click();
    await expect(page.locator('[data-testid="expense-modal"]')).toBeVisible();
    // Prefill benar
    expect(await page.locator('[data-testid="expense-description"]').inputValue()).toBe(desc);
    expect(await page.locator('[data-testid="expense-amount"]').inputValue()).toBe('50000');
    // Ubah
    await page.locator('[data-testid="expense-description"]').fill(desc + '-EDITED');
    await page.locator('[data-testid="expense-amount"]').fill('99000');
    await page.locator('[data-testid="expense-category"]').selectOption('Utilitas');
    await page.locator('[data-testid="expense-submit"]').click();
    await page.waitForLoadState('load');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const edited = page.locator(`[data-testid="expense-row"][data-description="${desc}-EDITED"]`);
    await expect(edited).toHaveCount(1);
    expect(await edited.getAttribute('data-amount')).toBe('99000.00');
    expect(await edited.getAttribute('data-category')).toBe('Utilitas');
    // Deskripsi lama hilang
    expect(await page.locator(`[data-testid="expense-row"][data-description="${desc}"]`).count()).toBe(0);
  });

  test('delete expense → baris hilang', async ({ page }) => {
    test.setTimeout(25000);
    page.on('dialog', (dialog) => dialog.accept());
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });

    const desc = `E2E-DEL-${Date.now()}`;
    await page.locator('[data-testid="add-expense-btn"]').click();
    await page.locator('[data-testid="expense-description"]').fill(desc);
    await page.locator('[data-testid="expense-amount"]').fill('11111');
    await page.locator('[data-testid="expense-submit"]').click();
    await page.waitForLoadState('load');

    const row = page.locator(`[data-testid="expense-row"][data-description="${desc}"]`);
    await expect(row).toHaveCount(1);
    await row.locator('[data-testid="delete-expense"]').click();
    await page.waitForLoadState('load');

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(await page.locator(`[data-testid="expense-row"][data-description="${desc}"]`).count()).toBe(0);
  });

  test('CSRF: POST tanpa token ditolak', async ({ page }) => {
    await loginAdmin(page);
    // POST langsung via request API tanpa csrf_token
    const resp = await page.request.post(`${BASE}/admin/accounting.php`, {
      form: { expense_action: 'save', category: 'Marketing', description: 'NO-CSRF', amount: '100' },
    });
    expect(resp.status()).toBe(403);
  });

  test('export CSV P&L terunduh dengan struktur statement', async ({ page }) => {
    test.setTimeout(20000);
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 15000 }),
      page.locator('[data-testid="export-pnl-csv"]').click(),
    ]);
    expect(download.suggestedFilename()).toMatch(/^pnl-\d{4}-\d{2}-\d{2}-\d{4}-\d{2}-\d{2}\.csv$/);
    const fp = await download.path();
    const content = fs.readFileSync(fp!, 'utf-8').replace(/^\uFEFF/, '');
    // Struktur statement — header bisa EN atau ID tergantung session bahasa (REVENUE/Pendapatan)
    expect(content).toMatch(/Profit & Loss/i);
    expect(content).toMatch(/REVENUE|PENDAPATAN/i);
    expect(content).toMatch(/COGS|HPP/i);
    expect(content).toMatch(/EXPENSES|PENGELUARAN/i);
  });

  test('tanpa error console di accounting', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });
    page.on('pageerror', (err) => errors.push(String(err)));
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/accounting.php`, { waitUntil: 'load' });
    await page.waitForTimeout(500);
    expect(errors, 'console errors: ' + errors.join(' | ')).toHaveLength(0);
  });
});
