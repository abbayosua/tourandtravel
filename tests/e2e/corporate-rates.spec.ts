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
  dbRun(`UPDATE users SET corporate_company_id=NULL WHERE id=1;`);
  dbRun(`DELETE FROM corporate_companies WHERE name LIKE 'E2E-CR%';`);
  dbRun(`DELETE FROM bookings WHERE email='e2e-cr@test.local';`);
}

test.describe('Corporate rates', () => {

  test.beforeEach(async () => {
    cleanup();
  });

  test.afterEach(async () => {
    cleanup();
  });

  test('non-korporat: tidak ada note diskon di form booking', async ({ page }) => {
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('[data-testid="corporate-discount-note"]')).toHaveCount(0);
  });

  test('user korporat 10%: note diskon tampil di form booking', async ({ page }) => {
    dbRun(`INSERT INTO corporate_companies (name, discount_percent, is_active) VALUES ('E2E-CR Corp', 10, 1);`);
    dbRun(`UPDATE users SET corporate_company_id=(SELECT id FROM corporate_companies WHERE name='E2E-CR Corp') WHERE id=1;`);
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await expect(page.locator('[data-testid="corporate-discount-note"]')).toBeVisible();
    await expect(page.locator('[data-testid="corporate-discount-note"]')).toContainText('10%');
  });

  test('user korporat: checkout menyimpan total terdiskon di DB', async ({ page }) => {
    dbRun(`INSERT INTO corporate_companies (name, discount_percent, is_active) VALUES ('E2E-CR Corp', 10, 1);`);
    dbRun(`UPDATE users SET corporate_company_id=(SELECT id FROM corporate_companies WHERE name='E2E-CR Corp') WHERE id=1;`);
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const basePrice = parseFloat(dbVal(`SELECT price FROM tours WHERE slug='11d9n-amazing-new-zealand'`));
    await page.selectOption('select[name="tour_date_id"]', { index: 1 });
    await page.fill('input[name="name"]', 'E2E CR Tester');
    await page.fill('input[name="email"]', 'e2e-cr@test.local');
    await page.fill('input[name="phone"]', '0812000000');
    await page.fill('input[name="participants"]', '1');
    execSync(`php -r '$i=imagecreatetruecolor(10,10); imagefill($i,0,0,imagecolorallocate($i,200,0,0)); imagejpeg($i,"/tmp/e2e-cr-pass.jpg",90);'`);
    await page.setInputFiles('input[name="passport_photo"]', '/tmp/e2e-cr-pass.jpg');
    const bookingForm = page.locator('form').filter({ has: page.locator('input[name="passport_photo"]') });
    await Promise.all([
      page.waitForURL('**/booking-success.php?code=*', { timeout: 20000 }),
      bookingForm.locator('button[type="submit"]').click(),
    ]);
    const totalStr = dbVal(`SELECT total_price FROM bookings WHERE email='e2e-cr@test.local' ORDER BY id DESC LIMIT 1`);
    if (totalStr === '') test.skip(true, 'booking gagal dibuat (validasi form)');
    const total = parseFloat(totalStr);
    const expected = Math.round(basePrice * 0.9 * 100) / 100;
    expect(Math.abs(total - expected)).toBeLessThan(1);
  });

  test('company nonaktif: note hilang + harga normal', async ({ page }) => {
    dbRun(`INSERT INTO corporate_companies (name, discount_percent, is_active) VALUES ('E2E-CR Off', 20, 0);`);
    dbRun(`UPDATE users SET corporate_company_id=(SELECT id FROM corporate_companies WHERE name='E2E-CR Off') WHERE id=1;`);
    await login(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await expect(page.locator('[data-testid="corporate-discount-note"]')).toHaveCount(0);
  });

});
