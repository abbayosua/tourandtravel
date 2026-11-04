import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';
const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const T = Date.now().toString().slice(-6);
const ADDR = 'Jl. E2E Royal ' + T + ', Batam';
const PHONE = '0778' + T;
const WA = '628' + T + '1234';
const EMAIL = 'e2e-royal-' + T + '@example.test';
const WD = 'Senin - Jumat: 09:00 - 17:00';
const SN = 'Sabtu: 09:00 - 12:00';
function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}
function setSetting(k: string, v: string) {
  mysql('INSERT INTO settings (setting_key, setting_value) VALUES (' + String.fromCharCode(39) + k + String.fromCharCode(39) + ',' + String.fromCharCode(39) + v + String.fromCharCode(39) + ') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
}
const KEYS = ['site_name','site_tagline','contact_address','contact_phone','contact_wa','contact_email','contact_hours_weekday','contact_hours_sunday'];
let prev: Record<string, string> = {};
test.beforeAll(() => { for (const k of KEYS) { prev[k] = mysql('SELECT setting_value FROM settings WHERE setting_key=' + String.fromCharCode(39) + k + String.fromCharCode(39)); } });
test.afterAll(() => { for (const k of KEYS) { setSetting(k, prev[k] || ''); } });
async function adminLogin(page: Page) {
  await page.goto(BASE + '/admin/login.php');
  await page.fill('input[name=username]', ADMIN_USER);
  await page.fill('input[name=password]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await page.waitForLoadState('domcontentloaded');
}
test('kontak footer mengikuti admin Brand dan Logo', async ({ page }) => {
  await adminLogin(page);
  await page.goto(BASE + '/admin/brand-settings.php?lang=id');
  await page.fill('[data-testid=brand-name]', 'Royal Dream E2E ' + T);
  await page.fill('[data-testid=brand-contact-address]', ADDR);
  await page.fill('[data-testid=brand-contact-phone]', PHONE);
  await page.fill('[data-testid=brand-contact-wa]', WA);
  await page.fill('[data-testid=brand-contact-email]', EMAIL);
  await page.fill('[data-testid=brand-contact-hours-weekday]', WD);
  await page.fill('[data-testid=brand-contact-hours-sunday]', SN);
  await Promise.all([page.waitForSelector('[data-testid=brand-saved]'), page.click('[data-testid=brand-save]')]);
  await expect(page.locator('form[data-submit-once]')).toHaveScreenshot('brand-settings-admin.png', { maxDiffPixelRatio: 0.15 });
  await page.goto(BASE + '/index.php?lang=id');
  await expect(page.locator('[data-testid=brand-nav]')).toContainText('Royal Dream E2E');
  await expect(page.locator('[data-testid=brand-nav]')).toHaveScreenshot('brand-nav.png', { maxDiffPixels: 800 });
  await expect(page.locator('[data-testid=brand-contact-address]')).toContainText(ADDR);
  await expect(page.locator('[data-testid=brand-contact-phone]')).toContainText(PHONE);
  await expect(page.locator('[data-testid=brand-contact-wa]')).toContainText(WA);
  await expect(page.locator('[data-testid=brand-contact-email]')).toContainText(EMAIL);
  await expect(page.locator('[data-testid=brand-contact-hours-weekday]')).toContainText(WD);
  await expect(page.locator('[data-testid=brand-contact-hours-sunday]')).toContainText(SN);
  await expect(page.locator('[data-testid=brand-contact-phone] a')).toHaveAttribute('href', 'tel:' + PHONE);
  await expect(page.locator('[data-testid=brand-contact-email] a')).toHaveAttribute('href', 'mailto:' + EMAIL);
  await expect(page.locator('[data-testid=brand-contact-wa] a')).toHaveAttribute('href', 'https://wa.me/' + WA);
  await page.goto(BASE + '/about.php?lang=id');
  await expect(page.locator('text=' + ADDR).first()).toBeVisible();
  await expect(page.locator('text=' + EMAIL).first()).toBeVisible();
  await expect(page.locator('section').filter({ hasText: 'Hubungi Kami' }).first()).toHaveScreenshot('brand-about-contact.png', { maxDiffPixelRatio: 0.15 });
  await expect(page.locator('[data-testid=brand-contact-address]')).toHaveScreenshot('brand-contact-footer.png', { maxDiffPixelRatio: 0.15 });
});

test('kontak footer mobile 360x800 Royal Dream', async ({ browser }) => {
  const ctx = await browser.newContext({ viewport: { width: 360, height: 800 } });
  const page = await ctx.newPage();
  await page.goto(BASE + '/index.php?lang=id');
  await expect(page.locator('[data-testid=brand-contact-address]')).toBeVisible();
  await expect(page.locator('footer#kontak')).toHaveScreenshot('brand-contact-mobile-360.png', { maxDiffPixelRatio: 0.15 });
  await ctx.close();
});
