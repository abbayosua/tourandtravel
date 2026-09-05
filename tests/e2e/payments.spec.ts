import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const SERVER_KEY = 'SB-Mid_server-E2EKEY';

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\\"')}"`, { stdio: 'pipe' });
}
function dbOne(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\\"')}"`).toString().trim();
}
function sha512(s: string): string {
  return require('crypto').createHash('sha512').update(s).digest('hex');
}

/** Pastikan setting payment aktif + server key fixture */
function enablePayments() {
  dbRun(`UPDATE settings SET setting_value='${SERVER_KEY}' WHERE setting_key='midtrans_server_key';`);
  dbRun(`UPDATE settings SET setting_value='1' WHERE setting_key='payment_enabled';`);
  dbRun(`UPDATE settings SET setting_value='sandbox' WHERE setting_key='midtrans_env';`);
}
function disablePayments() {
  dbRun(`UPDATE settings SET setting_value='' WHERE setting_key='midtrans_server_key';`);
}

/** Buat booking pending + payments row; kembalikan {bookingId, orderId} */
function createPendingBooking(code: string): { bookingId: number; orderId: string } {
  dbRun(`INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status, payment_status)
         VALUES ('${code}', 61, 1, 'E2E Payer', 'e2e-pay@example.com', '0812', 1, 250000, 'pending', 'unpaid')`);
  const bookingId = Number(dbOne(`SELECT id FROM bookings WHERE booking_code='${code}'`));
  const orderId = `TAT-T-${bookingId}-E2E${Math.random().toString(16).slice(2, 8).toUpperCase()}`;
  dbRun(`INSERT INTO payments (booking_type, booking_id, booking_code, order_id, gross_amount, status)
         VALUES ('tour', ${bookingId}, '${code}', '${orderId}', 250000, 'pending')`);
  return { bookingId, orderId };
}

function cleanupBooking(code: string) {
  dbRun(`DELETE FROM payments WHERE booking_code='${code}'; DELETE FROM bookings WHERE booking_code='${code}';`);
}

function sendWebhook(orderId: string, gross: string, status: string, sigOverride?: string): { code: number; body: any } {
  const sig = sigOverride ?? sha512(orderId + '200' + gross + SERVER_KEY);
  const payload = JSON.stringify({
    order_id: orderId, status_code: '200', gross_amount: gross,
    transaction_status: status, payment_type: 'gopay', transaction_id: 'TX-' + orderId,
    signature_key: sig,
  });
  const res = execSync(
    `curl -s -w '\\n%{http_code}' -X POST ${BASE}/webhook-midtrans.php -H 'Content-Type: application/json' -d '${payload.replace(/'/g, "'\\''")}'`
  ).toString();
  const [body, code] = res.trim().split('\n');
  return { code: Number(code), body: JSON.parse(body || '{}') };
}

test.describe('Payments — Midtrans webhook, tombol bayar, admin', () => {

  test.afterEach(() => {
    dbRun(`DELETE FROM payments WHERE booking_code LIKE 'E2E-PAY%'; DELETE FROM bookings WHERE booking_code LIKE 'E2E-PAY%';`);
    disablePayments();
  });

  test('webhook: signature valid settlement → payment paid + booking paid', async () => {
    enablePayments();
    const { bookingId, orderId } = createPendingBooking('E2E-PAY-01');
    const r = sendWebhook(orderId, '250000.00', 'settlement');
    expect(r.code).toBe(200);
    expect(r.body.ok).toBe(true);
    expect(dbOne(`SELECT status FROM payments WHERE order_id='${orderId}'`)).toBe('paid');
    expect(dbOne(`SELECT payment_status FROM bookings WHERE id=${bookingId}`)).toBe('paid');
    expect(dbOne(`SELECT COUNT(*) FROM payments WHERE order_id='${orderId}'`)).toBe('1');
  });

  test('webhook: duplikat kirim 2x → idempotent (tetap 1 row, tetap paid)', async () => {
    enablePayments();
    const { bookingId, orderId } = createPendingBooking('E2E-PAY-02');
    expect(sendWebhook(orderId, '250000.00', 'settlement').code).toBe(200);
    expect(sendWebhook(orderId, '250000.00', 'settlement').code).toBe(200);
    expect(dbOne(`SELECT COUNT(*) FROM payments WHERE order_id='${orderId}'`)).toBe('1');
    expect(dbOne(`SELECT status FROM payments WHERE order_id='${orderId}'`)).toBe('paid');
    expect(dbOne(`SELECT payment_status FROM bookings WHERE id=${bookingId}`)).toBe('paid');
  });

  test('webhook: signature salah → 403, tanpa perubahan', async () => {
    enablePayments();
    const { bookingId, orderId } = createPendingBooking('E2E-PAY-03');
    const r = sendWebhook(orderId, '250000.00', 'settlement', 'f'.repeat(128));
    expect(r.code).toBe(403);
    expect(r.body.error).toBe('invalid_signature');
    expect(dbOne(`SELECT status FROM payments WHERE order_id='${orderId}'`)).toBe('pending');
    expect(dbOne(`SELECT payment_status FROM bookings WHERE id=${bookingId}`)).toBe('unpaid');
  });

  test('webhook: order tidak dikenal → 404', async () => {
    enablePayments();
    const r = sendWebhook('TAT-T-999999-UNKNOWN', '250000.00', 'settlement');
    expect(r.code).toBe(404);
    expect(r.body.error).toBe('order_not_found');
  });

  test('webhook: expire → status expired', async () => {
    enablePayments();
    const { orderId } = createPendingBooking('E2E-PAY-04');
    const r = sendWebhook(orderId, '250000.00', 'expire');
    expect(r.code).toBe(200);
    expect(dbOne(`SELECT status FROM payments WHERE order_id='${orderId}'`)).toBe('expired');
  });

  test('webhook: JSON invalid → 400', async () => {
    const res = execSync(
      `curl -s -o /dev/null -w '%{http_code}' -X POST ${BASE}/webhook-midtrans.php -H 'Content-Type: application/json' -d 'bukan-json'`
    ).toString().trim();
    expect(Number(res)).toBe(400);
  });

  test('booking-success: tombol Bayar Sekarang muncul saat enabled & pending', async ({ page }) => {
    enablePayments();
    const { bookingId, orderId } = createPendingBooking('E2E-PAY-05');
    await page.goto(`${BASE}/booking-success.php?code=E2E-PAY-05`);
    await expect(page.locator('#payNowBtn')).toBeVisible();
    // polling area memakai order existing
    const orderAttr = await page.getAttribute('#paymentStatusArea', 'data-order-id');
    expect(orderAttr).toBe(orderId);
    expect(await page.textContent('body')).not.toMatch(/Fatal error|Warning:/);
  });

  test('booking-success: tombol hilang saat payment disabled', async ({ page }) => {
    createPendingBooking('E2E-PAY-06');
    disablePayments();
    await page.goto(`${BASE}/booking-success.php?code=E2E-PAY-06`);
    await expect(page.locator('#payNowBtn')).toHaveCount(0);
  });

  test('ajax payment-status: pending → paid (setelah webhook)', async ({ page }) => {
    enablePayments();
    const { orderId } = createPendingBooking('E2E-PAY-07');
    const r1 = await page.request.get(`${BASE}/ajax/payment-status.php?order_id=${orderId}`);
    expect((await r1.json()).status).toBe('pending');
    sendWebhook(orderId, '250000.00', 'settlement');
    const r2 = await page.request.get(`${BASE}/ajax/payment-status.php?order_id=${orderId}`);
    expect((await r2.json()).status).toBe('paid');
  });

  test('admin: daftar pembayaran + setting tersimpan', async ({ page }) => {
    enablePayments();
    createPendingBooking('E2E-PAY-08');
    await page.goto(`${BASE}/admin/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/admin/payments.php`);
    await expect(page.locator('tr:has-text("E2E-PAY-08")')).toBeVisible();
    expect(await page.textContent('body')).toMatch(/Sandbox/);

    // Ubah env → tersimpan
    await page.selectOption('select[name="midtrans_env"]', 'production');
    await page.click('form button[type="submit"]');
    await page.waitForLoadState('load');
    expect(page.url()).toContain('msg=updated');
    expect(dbOne(`SELECT setting_value FROM settings WHERE setting_key='midtrans_env'`)).toBe('production');
    // restore
    dbRun(`UPDATE settings SET setting_value='sandbox' WHERE setting_key='midtrans_env';`);
  });

  test('admin: tandai kedaluwarsa manual untuk pending', async ({ page }) => {
    enablePayments();
    createPendingBooking('E2E-PAY-09');
    await page.goto(`${BASE}/admin/login.php`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.goto(`${BASE}/admin/payments.php`);
    await page.click('tr:has-text("E2E-PAY-09") a[href*="expire"]');
    await page.waitForLoadState('load');
    expect(dbOne(`SELECT status FROM payments WHERE booking_code='E2E-PAY-09'`)).toBe('expired');
  });
});
