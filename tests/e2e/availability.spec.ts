import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync } from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /Fatal error|Warning:|Parse error|Deprecated:/i;
const SLUG = 'chongqing-wulong-karst-national-park-day-tour';
const PROJECT = '/Users/user/www/tourandtravel';

/**
 * Fase 2: Real-time availability engine.
 * Anti-overbooking 2 layer:
 *  1) Booking pending: getSisaSlot() menghitung pending+confirmed → dropdown exclude slot 0,
 *     submit pax > sisa ditolak server-side.
 *  2) Settlement: tour_dates.booked dideduksi atomik via availability engine (unit test cover).
 * E2E di sini menguji layer 1 lewat UI + API.
 */
function runPhp(code: string): string {
  const tmp = `${PROJECT}/tests/e2e/_tmp_seed.php`;
  writeFileSync(tmp, `<?php\nrequire '${PROJECT}/includes/config.php';\nrequire '${PROJECT}/includes/db.php';\n${code}\n`);
  const out = execSync(`php ${tmp}`, { encoding: 'utf8' });
  try { unlinkSync(tmp); } catch {}
  return out.trim();
}

function seedTourDate(quota: number): string {
  return runPhp(
    `$tid = (int)db()->query("SELECT id FROM tours WHERE slug='chongqing-wulong-karst-national-park-day-tour'")->fetchColumn();` +
    `db()->exec("INSERT INTO tour_dates (tour_id, departure_date, return_date, available_slots, booked, is_active) VALUES ($tid, DATE_ADD(CURDATE(), INTERVAL 145 DAY), DATE_ADD(CURDATE(), INTERVAL 147 DAY), ${quota}, 0, 1)");` +
    `echo db()->lastInsertId();`
  );
}

function cleanupTourDate(tdId: string) {
  runPhp(
    `db()->prepare("DELETE b, l FROM bookings b LEFT JOIN availability_ledger l ON l.booking_type='tour' AND l.booking_id=b.id WHERE b.tour_date_id=${tdId}")->execute();` +
    `db()->prepare("DELETE FROM tour_dates WHERE id=${tdId}")->execute();`
  );
}

function pendingPax(tdId: string): string {
  return runPhp(`echo (int)db()->query("SELECT COALESCE(SUM(participants),0) FROM bookings WHERE tour_date_id=${tdId} AND status IN ('pending','confirmed')")->fetchColumn();`);
}

async function registerAndBook(page: any, pax: number, tdId?: string) {
  const email = `avail_${Date.now()}@example.com`;
  await page.goto(`${BASE}/register.php`);
  await page.fill('input[name="name"]', 'Avail User');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812399999');
  await page.fill('input[name="password"]', 'password123');
  await page.fill('input[name="confirm_password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
  const tdSelect = page.locator('select[name="tour_date_id"]');
  if (tdId) await tdSelect.selectOption(tdId);
  else await tdSelect.selectOption({ index: 1 });
  await page.fill('input[name="name"]', 'Avail User');
  await page.fill('input[name="phone"]', '0812399999');
  await page.fill('input[name="participants"]', String(pax));
  await page.setInputFiles('input[type="file"]', {
    name: 'p.png', mimeType: 'image/png',
    buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64'),
  });
  await page.click('#bookingSubmitBtn');
  await page.waitForLoadState('networkidle');
}

test.describe('Availability engine (Fase 2)', () => {
  let tdId: string;

  test.afterEach(async () => {
    if (tdId) cleanupTourDate(tdId);
    tdId = '';
  });

  test('TC-207a booking sampai kuota habis → dropdown hilang & tombol Penuh', async ({ page }) => {
    tdId = seedTourDate(2);
    await registerAndBook(page, 2, tdId); // habiskan kuota
    let body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(page.url()).toContain('booking-success.php');

    // Reload detail: date test (sisa 0) TIDAK ADA di dropdown; jika satu-satunya → tombol Penuh disabled
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const opts = await page.locator('select[name="tour_date_id"] option').evaluateAll(
      os => os.map(o => (o as HTMLOptionElement).value).filter(v => v && v !== '')
    );
    expect(opts).not.toContain(tdId);
    expect(pendingPax(tdId)).toBe('2');
  });

  test('TC-207b slot tersisa parsial → tombol submit tetap normal', async ({ page }) => {
    tdId = seedTourDate(5);
    await registerAndBook(page, 2, tdId); // sisa 3
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(page.url()).toContain('booking-success.php');

    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    await expect(page.locator('[data-testid="tour-full-btn"]')).toHaveCount(0);
    const opts = await page.locator('select[name="tour_date_id"] option').evaluateAll(
      os => os.map(o => (o as HTMLOptionElement).value).filter(v => v && v !== '')
    );
    expect(opts).toContain(tdId);
    expect(pendingPax(tdId)).toBe('2');
  });

  test('TC-207c sad path: booking melebihi sisa slot ditolak server-side', async ({ page }) => {
    tdId = seedTourDate(2);
    const resp = await page.request.post(`${BASE}/tour-detail.php?slug=${SLUG}`, {
      form: { form_submitted: '1', tour_date_id: tdId, name: 'Avail User', email: `x${Date.now()}@e.com`, phone: '0812300000', participants: '99', notes: '' },
    });
    const text = await resp.text();
    expect(text).not.toMatch(PHP_ERROR);
    expect(text).toMatch(/sisa slot|hanya.*kursi|only.*left/i);
    expect(pendingPax(tdId)).toBe('0');
  });
});
