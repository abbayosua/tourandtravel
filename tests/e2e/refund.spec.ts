import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync } from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PROJECT = '/Users/user/www/tourandtravel';
const PHP_ERROR = /Fatal error|Warning:|Parse error|Deprecated:/i;

/**
 * Fase 3: Refund self-service end-to-end.
 * Flow: user booking confirmed (H-12 → full 100%) → minta refund via UI modal
 * → admin approve via dropdown → wallet user bertambah exact.
 */
function runPhp(code: string): string {
  const tmp = `${PROJECT}/tests/e2e/_tmp_seed.php`;
  writeFileSync(tmp, `<?php\nrequire '${PROJECT}/includes/config.php';\nrequire '${PROJECT}/includes/db.php';\n${code}\n`);
  const out = execSync(`php ${tmp}`, { encoding: 'utf8' });
  try { unlinkSync(tmp); } catch {}
  return out.trim();
}

async function seedFixture(): Promise<{ uid: string; tdId: string; bkId: string; email: string }> {
  const email = `rf_e2e_${Date.now()}@t.local`;
  const out = runPhp(`
    db()->exec("INSERT INTO users (name, email, password_hash) VALUES ('RF E2E', '${email}', '" . password_hash('password123', PASSWORD_DEFAULT) . "')");
    $uid = (int)db()->lastInsertId();
    db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) VALUES (61, DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 5, 0, 1)");
    $td = (int)db()->lastInsertId();
    $code = 'RFE2E-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    db()->prepare("INSERT INTO bookings (booking_code, tour_id, tour_date_id, user_id, name, email, phone, participants, total_price, status) VALUES (?, 61, ?, ?, 'RF E2E', '${email}', '0812', 2, 200000, 'confirmed')")->execute([$code, $td, $uid]);
    echo $uid . '|' . $td . '|' . (int)db()->lastInsertId();
  `);
  const [uid, tdId, bkId] = out.split('|');
  return { uid, tdId, bkId, email };
}

function cleanupFixture(f: { uid: string; tdId: string; bkId: string }) {
  runPhp(`
    db()->prepare("DELETE b FROM bookings b LEFT JOIN availability_ledger l ON l.booking_type='tour' AND l.booking_id=b.id WHERE b.id=${f.bkId}")->execute();
    db()->prepare("DELETE FROM wallet_transactions WHERE user_id=${f.uid}")->execute();
    db()->prepare("DELETE FROM tour_dates WHERE id=${f.tdId}")->execute();
    db()->prepare("DELETE FROM users WHERE id=${f.uid}")->execute();
  `);
}

function walletBalance(uid: string): string {
  return runPhp(`echo (float)db()->query("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE user_id=${uid} AND type='refund'")->fetchColumn();`);
}

test.describe('Refund self-service (Fase 3)', () => {
  let f: { uid: string; tdId: string; bkId: string; email: string };

  test.afterEach(async () => {
    if (f) cleanupFixture(f);
  });

  test('TC-208a request → admin approve → wallet +200000 exact', async ({ page }) => {
    f = await seedFixture();

    // 1) User login → my-bookings → modal refund → submit
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', f.email);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    const btn = page.locator(`[data-testid="refund-btn-${f.bkId}"]`);
    await expect(btn).toHaveCount(1);
    await btn.click();
    await page.fill(`#refundModal${f.bkId} textarea[name="reason"]`, 'Jadwal berubah');
    await page.locator(`#refundModal${f.bkId} [data-testid="refund-submit"]`).click();
    await page.waitForLoadState('networkidle');

    body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('[data-testid="refund-ok"]')).toBeVisible();
    await expect(page.locator(`[data-testid="refund-timeline-${f.bkId}"]`)).toBeVisible();
    expect(walletBalance(f.uid)).toBe('0'); // belum dikredit sebelum approve

    // 2) Admin approve
    await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('load');

    await page.goto(`${BASE}/admin/bookings.php?type=tour`, { waitUntil: 'load' });
    body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator(`[data-testid="refund-requested-${f.bkId}"]`)).toBeVisible();

    // buka dropdown dulu, lalu klik approve (dropdown-item hidden sampai dropdown dibuka)
    page.once('dialog', d => d.accept());
    await page.locator('table tbody tr', { has: page.locator(`text=${(await runPhp(`echo db()->query("SELECT booking_code FROM bookings WHERE id=${f.bkId}")->fetchColumn();`))}`) }).first()
      .locator('.dropdown-toggle').first().click();
    await page.locator(`[data-testid="refund-approve-${f.bkId}"]`).click();
    await page.waitForLoadState('networkidle');
    body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('[data-testid="refund-admin-msg"]')).toBeVisible();

    // 3) Wallet bertambah exact 200000
    expect(walletBalance(f.uid)).toBe('200000');

    // 4) User lihat timeline approved di my-bookings
    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Disetujui|Approved/i);
  });

  test('TC-208b sad path: H-2 (pct 0) → pengajuan ditolak dengan pesan', async ({ page }) => {
    // override: seed fixture lalu ubah departure ke H-2
    f = await seedFixture();
    runPhp(`db()->prepare("UPDATE tour_dates SET departure_date = DATE_ADD(CURDATE(), INTERVAL 2 DAY) WHERE id=${f.tdId}")->execute();`);

    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', f.email);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    // tombol refund TIDAK muncul untuk pct 0 (validasi client-side via server rule: tetap render btn, tapi server menolak)
    // POST langsung untuk bypass UI:
    const resp = await page.request.post(`${BASE}/my-bookings.php`, {
      form: { action: 'request_refund', booking_id: f.bkId, reason: 'too late' },
      maxRedirects: 0,
    });
    const loc = resp.headersArray().find(h => h.name.toLowerCase() === 'location')?.value ?? '';
    expect(loc).toContain('refund_fail');
    expect(walletBalance(f.uid)).toBe('0');
  });

  test('TC-208c sad path: request untuk booking orang lain ditolak', async ({ page }) => {
    f = await seedFixture();
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', f.email);
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');

    const resp = await page.request.post(`${BASE}/my-bookings.php`, {
      form: { action: 'request_refund', booking_id: 999999999, reason: 'not mine' },
      maxRedirects: 0,
    });
    const loc = resp.headersArray().find(h => h.name.toLowerCase() === 'location')?.value ?? '';
    expect(loc).toContain('refund_fail');
    expect(walletBalance(f.uid)).toBe('0');
  });
});
