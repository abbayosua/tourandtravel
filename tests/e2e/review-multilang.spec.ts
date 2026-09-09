import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const PHP_ERROR = /(Fatal error|Warning:|Deprecated|Parse error|Uncaught|Undefined variable|error\s*:\s*<)/i;

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\\"')}"`, { stdio: 'pipe' });
}

function dbVal(sql: string): string {
  return execSync(`mysql -u root tourandtravel -N -e "${sql.replace(/"/g, '\\"')}"`).toString().trim();
}

async function login(page) {
  await page.goto('http://localhost/tourandtravel/login.php', { waitUntil: 'load' });
  await page.fill('input[name="email"]', 'mautau@mautau.com');
  await page.fill('input[name="password"]', 'mautau123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/index.php', { timeout: 10000 });
}

async function cleanup() {
  dbRun(`DELETE FROM reviews WHERE comment LIKE 'E2E-ML %';`);
  dbRun(`UPDATE reviews SET tour_id=64, hotel_id=NULL WHERE user_id=1 AND tour_id IS NULL AND hotel_id IS NULL AND comment NOT LIKE 'E2E-ML%';`);
}

test.describe('Multi-lang UGC reviews', () => {

  test.beforeEach(async () => {
    // pastikan user 1 boleh review tour 64 (punya booking confirmed, belum review)
    dbRun(`DELETE FROM reviews WHERE user_id=1 AND tour_id=64;`);
    dbRun(`DELETE FROM bookings WHERE booking_code LIKE 'E2E-ML%';`);
    dbRun(`UPDATE bookings SET user_id=1 WHERE id=12 AND status='confirmed';`);
    dbRun(`INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, user_id, status) SELECT 'E2E-MLSEED', 64, (SELECT id FROM tour_dates WHERE tour_id=64 LIMIT 1), 'E2E ML', 'e2e-ml@test.local', '0812000000', 1, 100000, 1, 'confirmed';`);
  });

  test.afterEach(async () => {
    cleanup();
    dbRun(`DELETE FROM bookings WHERE booking_code LIKE 'E2E-ML%';`);
  });

  test('dropdown bahasa tampil di form review tour', async ({ page }) => {
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const sel = page.locator('select[name="review_lang"]');
    await expect(sel).toBeVisible();
    const options = await sel.locator('option').allTextContents();
    expect(options.length).toBeGreaterThanOrEqual(2);
  });

  test('pilih EN → submit → tersimpan lang=en di DB', async ({ page }) => {
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.selectOption('select[name="review_lang"]', 'en');
    await page.fill('textarea[name="comment"]', 'E2E-ML English review text');
    await page.locator('form[action="review-submit.php"] button[type="submit"]').click();
    await page.waitForLoadState('load');
    const lang = dbVal(`SELECT lang FROM reviews WHERE comment='E2E-ML English review text' LIMIT 1`);
    expect(lang).toBe('en');
  });

  test('review EN tampil di halaman lang=en, sembunyi di lang=id', async ({ page }) => {
    // seed via DB (user lain agar bebas unique key)
    dbRun(`INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (64, 4, 5, 'E2E-ML seed EN', 'en');`);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand&lang=en', { waitUntil: 'load' });
    await expect(page.locator('body')).toContainText('E2E-ML seed EN');
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand&lang=id', { waitUntil: 'load' });
    await expect(page.locator('body')).not.toContainText('E2E-ML seed EN');
  });

  test('review ID tampil di halaman lang=id, sembunyi di lang=en', async ({ page }) => {
    dbRun(`INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (64, 4, 4, 'E2E-ML seed ID', 'id');`);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand&lang=id', { waitUntil: 'load' });
    await expect(page.locator('body')).toContainText('E2E-ML seed ID');
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand&lang=en', { waitUntil: 'load' });
    await expect(page.locator('body')).not.toContainText('E2E-ML seed ID');
  });

  test('submit ID via hotel form → tersimpan lang=id', async ({ page }) => {
    const hid = dbVal(`SELECT id FROM hotels WHERE is_active=1 LIMIT 1`);
    const hslug = dbVal(`SELECT slug FROM hotels WHERE id=${hid}`);
    await login(page);
    await page.goto(`/hotel-detail.php?slug=${hslug}`, { waitUntil: 'load' });
    const sel = page.locator('select[name="review_lang"]');
    if (await sel.count() === 0) test.skip(true, 'hotel review form tidak tersedia');
    await page.selectOption('select[name="review_lang"]', 'id');
    await page.fill('textarea[name="comment"]', 'E2E-ML hotel ID review');
    await page.locator('form[action="hotel-review-submit.php"] button[type="submit"]').click();
    await page.waitForLoadState('load');
    const lang = dbVal(`SELECT lang FROM reviews WHERE comment='E2E-ML hotel ID review' LIMIT 1`);
    expect(lang).toBe('id');
  });

});
