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

async function elshot(page: any, sel: string, name: string) {
  const p = path.join(OUT, name);
  await page.locator(sel).first().screenshot({ path: p });
  const st = fs.statSync(p);
  console.log(name + ' bytes=' + st.size);
  if (st.size < 8000) throw new Error(name + ' too small, blank?');
}

test('screenshot panduan Bab 3 via CDP', async ({ page }) => {
  fs.mkdirSync(OUT, { recursive: true });
  await page.goto(BASE + '/admin/login.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.fill('input[name=username]', 'admin');
  await page.fill('input[name=password]', 'tmpcheck123');
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await page.goto(BASE + '/admin/bookings.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab3-01-daftar-booking.png');
  await page.goto(BASE + '/admin/bookings.php?type=tour&lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab3-02-filter-status.png');
  await page.goto(BASE + '/admin/bookings.php?type=hotel&lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab3-03-proses-status.png');
  await page.goto(BASE + '/admin/payments.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab3-04-gateway.png');
  await page.goto(BASE + '/track.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab3-05-hasil-track.png');
});
