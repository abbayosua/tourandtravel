import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync } from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PROJECT = '/Users/user/www/tourandtravel';
const PHP_ERROR = /Fatal error|Warning:|Parse error|Deprecated:/i;
const SLUG = 'chongqing-wulong-karst-national-park-day-tour';

/**
 * Fase 4: Travel insurance add-on.
 * Checkbox +3% → summary total bertambah exact; booking tersimpan dengan addon;
 * my-bookings menampilkan badge; booking tanpa addon tidak berubah.
 */
function runPhp(code: string): string {
  const tmp = `${PROJECT}/tests/e2e/_tmp_seed.php`;
  writeFileSync(tmp, `<?php\nrequire '${PROJECT}/includes/config.php';\nrequire '${PROJECT}/includes/db.php';\n${code}\n`);
  const out = execSync(`php ${tmp}`, { encoding: 'utf8' });
  try { unlinkSync(tmp); } catch {}
  return out.trim();
}

function seedTourDate(quota = 5): string {
  return runPhp(
    `$tid = (int)db()->query("SELECT id FROM tours WHERE slug='${SLUG}'")->fetchColumn();` +
    `db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) VALUES ($tid, DATE_ADD(CURDATE(), INTERVAL 150 DAY), DATE_ADD(CURDATE(), INTERVAL 152 DAY), ${quota}, 0, 1)");` +
    `echo db()->lastInsertId();`
  );
}

function cleanupTourDate(tdId: string) {
  runPhp(
    `db()->prepare("DELETE b, ba, l FROM bookings b LEFT JOIN availability_ledger l ON l.booking_type='tour' AND l.booking_id=b.id LEFT JOIN booking_addons ba ON ba.booking_type='tour' AND ba.booking_id=b.id WHERE b.tour_date_id=${tdId}")->execute();` +
    `db()->prepare("DELETE FROM tour_dates WHERE id=${tdId}")->execute();`
  );
}

async function register(page: any) {
  const email = `ins_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`);
  await page.fill('input[name="name"]', 'Ins Test');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812388888');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  return email;
}

async function fillBooking(page: any, tdId: string, pax: number, withInsurance: boolean, email?: string) {
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
  await page.locator('select[name="tour_date_id"]').selectOption(tdId);
  await page.fill('input[name="name"]', 'Ins Test');
  if (email) await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812388888');
  await page.fill('input[name="participants"]', String(pax));
  if (withInsurance) await page.check('#addInsuranceTour');
  await page.setInputFiles('input[type="file"]', {
    name: 'p.png', mimeType: 'image/png',
    buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'),
  });
}

function lastBookingFor(email: string): { id: string; total: string; premi: string } {
  const code =
    "$r = db()->query(\"SELECT b.id, b.total_price, COALESCE((SELECT amount FROM booking_addons ba WHERE ba.booking_type='tour' AND ba.booking_id=b.id AND ba.type='insurance'),0) premi FROM bookings b WHERE b.email='" + email + "' ORDER BY b.id DESC LIMIT 1\")->fetch();" +
    "echo $r['id'] . '|' . (float)$r['total_price'] . '|' . (float)$r['premi'];";
  const out = runPhp(code);
  const [id, total, premi] = out.split('|');
  return { id, total, premi };
}

test.describe('Travel insurance add-on (Fase 4)', () => {
  let tdId: string;
  let email: string;

  test.afterEach(async () => {
    if (tdId) cleanupTourDate(tdId);
    tdId = '';
  });

  test('TC-209a checkbox insurance → summary total +3% exact & tersimpan', async ({ page }) => {
    tdId = seedTourDate();
    email = await register(page);

    await fillBooking(page, tdId, 2, false, email);
    // summary tanpa insurance: total = base*2, baris insurance hidden
    const unitPrice = await page.locator('select[name="tour_date_id"] option:checked').getAttribute('data-price');
    const expectedBase = parseFloat(unitPrice!) * 2;
    await expect(page.locator('[data-testid="summary-total"]')).toHaveText(new RegExp(expectedBase.toLocaleString('id-ID')));
    await expect(page.locator('[data-testid="insurance-row"]')).toHaveClass(/d-none/);

    // centang insurance → total = base*2 + round(base*2*0.03)
    await page.check('#addInsuranceTour');
    const expectedPremi = Math.round(expectedBase * 0.03 / 100) * 100;
    const expectedTotal = expectedBase + expectedPremi;
    await expect(page.locator('[data-testid="insurance-row"]')).not.toHaveClass(/d-none/);
    await expect(page.locator('[data-testid="summary-total"]')).toHaveText(new RegExp(expectedTotal.toLocaleString('id-ID')));

    // submit → booking tersimpan: total_price = expectedTotal, addon = expectedPremi
    // NB: tombol submit pakai setTimeout(form.submit, 100) → tunggu redirect selesai
    await page.locator('#bookingSubmitBtn').click();
    await page.waitForURL(/booking-success\.php/, { timeout: 15000 });
    const b = lastBookingFor(email);
    expect(parseFloat(b.total)).toBe(expectedTotal);
    expect(parseFloat(b.premi)).toBe(expectedPremi);
  });

  test('TC-209b tanpa insurance → total & addon 0 (tidak berubah)', async ({ page }) => {
    tdId = seedTourDate();
    email = await register(page);

    await fillBooking(page, tdId, 1, false, email);
    const unitPrice = parseFloat(await page.locator('select[name="tour_date_id"] option:checked').getAttribute('data-price')!);
    await page.locator('#bookingSubmitBtn').click();
    await page.waitForURL(/booking-success\.php/, { timeout: 15000 });
    const b = lastBookingFor(email);
    expect(parseFloat(b.premi)).toBe(0);
    expect(parseFloat(b.total)).toBe(unitPrice);
  });

  test('TC-209c booking dengan insurance → badge tampil di my-bookings', async ({ page }) => {
    tdId = seedTourDate();
    email = await register(page);

    await fillBooking(page, tdId, 1, true, email);
    await page.locator('#bookingSubmitBtn').click();
    await page.waitForURL(/booking-success\.php/, { timeout: 15000 });
    const b = lastBookingFor(email);
    expect(parseFloat(b.premi)).toBeGreaterThan(0);

    await page.goto(`${BASE}/my-bookings.php`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator(`[data-testid="insurance-badge-${b.id}"]`)).toBeVisible();
  });
});
