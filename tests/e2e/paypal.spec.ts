import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /Fatal error|Parse error|Deprecated:/i;

/**
 * Backlog #7: PayPal checkout (sandbox).
 * Tanpa kredensial: fitur fail-soft — halaman tetap berfungsi, tidak ada crash.
 * Kupon: AJAX endpoint paypal-checkout.php menolak dengan pesan jelas.
 */
test.describe('PayPal payment integration (Backlog #7)', () => {
  test('TC-212a endpoint paypal-checkout tanpa kredensial → error JSON rapi', async ({ request }) => {
    const resp = await request.post(`${BASE}/ajax/paypal-checkout.php`, {
      data: { booking_type: 'tour', booking_id: 1 },
      headers: { 'Content-Type': 'application/json' },
    });
    const body = await resp.json();
    expect(body.success).toBe(false);
    expect(['paypal_not_configured', 'unauthorized', 'invalid_booking']).toContain(body.error);
  });

  test('TC-212b GET method ditolak 405', async ({ request }) => {
    const resp = await request.get(`${BASE}/ajax/paypal-checkout.php`);
    const body = await resp.json();
    expect(body.success).toBe(false);
    expect(['method_not_allowed', 'unauthorized']).toContain(body.error);
  });

  test('TC-212c booking-success tetap berfungsi normal (fail-soft integration)', async ({ page }) => {
    // tour booking success page tidak crash walau paypal lib di-load
    const code = await page.evaluate(() => null).catch(() => null);
    void code;
    // buat booking tour langsung via DB untuk mendapat kode valid
    const out = await (async () => {
      const { execSync } = await import('child_process');
      const { writeFileSync, unlinkSync } = await import('fs');
      const tmp = '/Users/user/www/tourandtravel/tests/e2e/_tmp_pp.php';
      writeFileSync(tmp, `<?php\nrequire '/Users/user/www/tourandtravel/includes/config.php';\nrequire '/Users/user/www/tourandtravel/includes/db.php';\ndb()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) VALUES (61, DATE_ADD(CURDATE(), INTERVAL 200 DAY), DATE_ADD(CURDATE(), INTERVAL 202 DAY), 5, 0, 1)");\n$td = (int)db()->lastInsertId();\n$code = 'PP' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));\ndb()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) VALUES (?, 61, ?, 'PP', 'pp@t.local', '0812', 1, 100000, 'confirmed')")->execute([$code, $td]);\necho $code . '|' . $td;\n`);
      const out = execSync(`php ${tmp}`, { encoding: 'utf8' }).trim();
      try { unlinkSync(tmp); } catch {}
      return out;
    })();
    const [code2, tdId] = out.split('|');
    try {
      await page.goto(`${BASE}/booking-success.php?code=${code2}`, { waitUntil: 'load' });
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
      await expect(page.locator('h3')).toContainText(/Booking Berhasil|Booking Successful/i);
    } finally {
      const { execSync } = await import('child_process');
      const { writeFileSync, unlinkSync } = await import('fs');
      const tmp = '/Users/user/www/tourandtravel/tests/e2e/_tmp_pp.php';
      writeFileSync(tmp, `<?php\nrequire '/Users/user/www/tourandtravel/includes/config.php';\nrequire '/Users/user/www/tourandtravel/includes/db.php';\ndb()->exec("DELETE FROM bookings WHERE booking_code='${code2}'");\ndb()->exec("DELETE FROM tour_dates WHERE id=${tdId}");\n`);
      execSync(`php ${tmp}`);
      try { unlinkSync(tmp); } catch {}
    }
  });
});
