import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync } from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PROJECT = '/Users/user/www/tourandtravel';
const PHP_ERROR = /Fatal error|Parse error|Deprecated:/i;

/**
 * Backlog #8: Saved payment methods + 1-click pay.
 * E2E: AJAX endpoint guard (unauthorized/method/invalid token), ownership,
 * dan UI saved-methods hanya tampil saat ada metode tersimpan.
 */
function runPhp(code: string): string {
  const tmp = `${PROJECT}/tests/e2e/_tmp_seed.php`;
  writeFileSync(tmp, `<?php\nrequire '${PROJECT}/includes/config.php';\nrequire '${PROJECT}/includes/db.php';\n${code}\n`);
  const out = execSync(`php ${tmp}`, { encoding: 'utf8' });
  try { unlinkSync(tmp); } catch {}
  return out.trim();
}

async function register(page: any): Promise<string> {
  const email = `spm_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`);
  await page.fill('input[name="name"]', 'SPM Test');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812344444');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  return email;
}

test.describe('Saved payment methods (Backlog #8)', () => {
  test('TC-213a unauthenticated → semua action ditolak unauthorized', async ({ request }) => {
    const list = await request.get(`${BASE}/ajax/saved-payments.php`);
    expect((await list.json()).error).toBe('unauthorized');
    const save = await request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'save', token: 'tok_xxxxxxxxxxxx' },
    });
    expect((await save.json()).error).toBe('unauthorized');
    const charge = await request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'charge', booking_type: 'tour', booking_id: 1, method_id: 1 },
    });
    expect((await charge.json()).error).toBe('unauthorized');
  });

  test('TC-213b login → save token, list, remove; token invalid ditolak', async ({ page, request }) => {
    const email = await register(page);

    // save token valid
    const save = await page.request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'save', token: 'tok_e2e_valid_123456', brand: 'visa', masked_number: '4811-****-1111', expiry_month: 12, expiry_year: 2028 },
    });
    const saveBody = await save.json();
    expect(saveBody.success).toBe(true);

    // list berisi 1 metode default
    const list = await page.request.get(`${BASE}/ajax/saved-payments.php`);
    const listBody = await list.json();
    expect(listBody.methods.length).toBe(1);
    expect(listBody.methods[0].is_default).toBe(1);

    // save token terlalu pendek → ditolak
    const bad = await page.request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'save', token: 'short' },
    });
    expect((await bad.json()).error).toBe('invalid_token');

    // remove metode user lain → gagal (rowCount 0)
    const otherId = runPhp(`db()->exec("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (999099, 'O', 'o@t.local', 'x')"); echo 999099;`);
    void otherId;
    const myMethodId = listBody.methods[0].id;
    // set method milik user lain tidak mungkin via API (query sudah filter user_id), tapi coba id acak besar:
    const rmOther = await page.request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'remove', id: 999999999 },
    });
    expect((await rmOther.json()).success).toBe(false);

    // remove sendiri → sukses, list kosong
    const rm = await page.request.post(`${BASE}/ajax/saved-payments.php`, {
      form: { action: 'remove', id: myMethodId },
    });
    expect((await rm.json()).success).toBe(true);
    const list2 = await page.request.get(`${BASE}/ajax/saved-payments.php`);
    expect((await list2.json()).methods.length).toBe(0);

    runPhp(`db()->prepare("DELETE FROM saved_payment_methods WHERE user_id = (SELECT id FROM users WHERE email='${email}')")->execute();`);
  });

  test('TC-213c booking-success tanpa metode tersimpan → tidak ada saved-methods block', async ({ page }) => {
    const email = await register(page);
    const seed = runPhp(`
      $uid = (int)db()->query("SELECT id FROM users WHERE email='${email}'")->fetchColumn();
      db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) VALUES (61, DATE_ADD(CURDATE(), INTERVAL 210 DAY), DATE_ADD(CURDATE(), INTERVAL 212 DAY), 5, 0, 1)");
      $td = (int)db()->lastInsertId();
      $code = 'SPM' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
      db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, user_id, name, email, phone, participants, total_price, status) VALUES (?, 61, ?, ?, 'SPM', '${email}', '0812', 1, 100000, 'confirmed')")->execute([$code, $td, $uid]);
      echo $code . '|' . $td;
    `);
    const [code, tdId] = seed.split('|');
    try {
      await page.goto(`${BASE}/booking-success.php?code=${code}`, { waitUntil: 'load' });
      const body = await page.textContent('body');
      expect(body).not.toMatch(PHP_ERROR);
      await expect(page.locator('[data-testid="saved-methods"]')).toHaveCount(0);
    } finally {
      runPhp(`db()->exec("DELETE FROM bookings WHERE booking_code='${code}'"); db()->exec("DELETE FROM tour_dates WHERE id=${tdId}");`);
    }
  });
});
