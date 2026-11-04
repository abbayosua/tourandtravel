import { test } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const OUT = path.join(__dirname, '..', '..', 'assets', 'img', 'panduan');

async function shot(page: any, name: string, opts: any = {}) {
  const p = path.join(OUT, name);
  await page.screenshot({ path: p, ...opts });
  const st = fs.statSync(p);
  console.log(name + ' bytes=' + st.size);
  if (st.size < 20000) throw new Error(name + ' too small, blank?');
}

test('screenshot panduan Bab 0 via CDP', async ({ page }) => {
  fs.mkdirSync(OUT, { recursive: true });
  await page.goto(BASE + '/admin/login.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.fill('input[name=username]', 'admin');
  await page.fill('input[name=password]', 'tmpcheck123');
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await page.goto(BASE + '/admin/dashboard.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.locator('#adminSidebar').screenshot({ path: path.join(OUT, 'panduan-bab0-02-sidebar.png') });
  console.log('panduan-bab0-02-sidebar.png bytes=' + fs.statSync(path.join(OUT, 'panduan-bab0-02-sidebar.png')).size);
  await shot(page, 'panduan-bab0-03-dashboard.png');
});
