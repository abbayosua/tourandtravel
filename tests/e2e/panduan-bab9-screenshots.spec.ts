import { test } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const OUT = path.join(__dirname, '..', '..', 'assets', 'img', 'panduan');

test('screenshot panduan Bab 9 via CDP', async ({ page }) => {
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
  await shot('/admin/dashboard.php?lang=id', 'panduan-bab9-01-dashboard.png');
  await shot('/admin/analytics.php?lang=id', 'panduan-bab9-02-analytics.png');
  await shot('/admin/admins.php?lang=id', 'panduan-bab9-03-admins.png');
});
