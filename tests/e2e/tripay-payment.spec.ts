import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql}"`, { stdio: 'pipe' });
}
function setMode(mode: string, gateway: string) {
  dbRun(`UPDATE settings SET setting_value='${mode}' WHERE setting_key='payment_mode';`);
  dbRun(`UPDATE settings SET setting_value='${gateway}' WHERE setting_key='payment_gateway';`);
}

async function loginAdmin(page: any) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Payment mode manual/instant + Tripay', () => {
  test('admin payments: mode + gateway + tripay fields render', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/payments.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('[data-testid="payment-mode"]')).toBeVisible();
    await expect(page.locator('[data-testid="payment-gateway"]')).toBeVisible();
    await expect(page.locator('[data-testid="tripay-api-key"]')).toBeVisible();
    await expect(page.locator('[data-testid="tripay-private-key"]')).toBeVisible();
    await expect(page.locator('[data-testid="tripay-merchant-code"]')).toBeVisible();
  });

  test('mode manual tersimpan via form', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/payments.php`, { waitUntil: 'load' });
    await page.selectOption('[data-testid="payment-mode"]', 'manual');
    await page.selectOption('[data-testid="payment-gateway"]', 'midtrans');
    await page.click('button[name="save_settings"]');
    await page.waitForLoadState('load');
    expect(await page.textContent('body')).toMatch(/Berhasil diperbarui|updated/i);
  });

  test('webhook-tripay: signature salah jadi 403', async ({ request }) => {
    setMode('manual', 'midtrans');
    const res = await request.post(`${BASE}/webhook-tripay.php`, {
      headers: { 'X-Callback-Signature': 'salah', 'X-Callback-Event': 'payment_status' },
      data: { reference: 'X', merchant_ref: 'Y', status: 'PAID' },
    });
    expect(res.status()).toBe(403);
    expect((await res.json()).success).toBe(false);
  });

  test('webhook-tripay: body kosong jadi success false', async ({ request }) => {
    const res = await request.post(`${BASE}/webhook-tripay.php`, { data: {} });
    expect((await res.json()).success).toBe(false);
  });

  test('create-payment mode manual jadi payment_disabled', async ({ request }) => {
    setMode('manual', 'midtrans');
    const res = await request.post(`${BASE}/ajax/create-payment.php`, {
      form: { booking_type: 'tour', booking_id: '1' },
    });
    const json = await res.json();
    expect(json.ok).toBe(false);
    expect(json.error).toBe('payment_disabled');
  });
});
