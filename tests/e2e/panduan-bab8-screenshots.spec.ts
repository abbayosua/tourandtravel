import { test } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const OUT = path.join(__dirname, '..', '..', 'assets', 'img', 'panduan');

test('screenshot panduan Bab 8 via CDP', async ({ page }) => {
  test.setTimeout(120000);
  fs.mkdirSync(OUT, { recursive: true });
  const shot = async (url: string, name: string) => {
    await page.goto(BASE + url, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await page.waitForLoadState('domcontentloaded');
    const p = path.join(OUT, name);
    await page.screenshot({ path: p });
    const st = fs.statSync(p);
    console.log(name + ' bytes=' + st.size);
    if (st.size < 20000) throw new Error(name + ' too small, blank?');
  };
  await page.goto(BASE + '/admin/login.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.fill('input[name=username]', 'admin');
  await page.fill('input[name=password]', 'tmpcheck123');
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await shot('/admin/wa-settings.php?lang=id', 'panduan-bab8-01-wa.png');
  await shot('/admin/chat-settings.php?lang=id', 'panduan-bab8-02-chat.png');
  await shot('/admin/email-log.php?lang=id', 'panduan-bab8-03-email.png');
  await shot('/admin/currency-settings.php?lang=id', 'panduan-bab8-04-currency.png');
  await shot('/admin/hotel-api-settings.php?lang=id', 'panduan-bab8-05-hotelapi.png');
});
