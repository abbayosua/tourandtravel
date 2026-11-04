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

test('screenshot panduan Bab 2 via CDP', async ({ page }) => {
  fs.mkdirSync(OUT, { recursive: true });
  await page.goto(BASE + '/admin/login.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.fill('input[name=username]', 'admin');
  await page.fill('input[name=password]', 'tmpcheck123');
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await page.goto(BASE + '/admin/tours.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-01-daftar-tour.png');
  await page.goto(BASE + '/admin/tour-add.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await elshot(page, 'form', 'panduan-bab2-02-form-tambah.png');
  await page.check('#modeAuto');
  await page.locator('#aiHint').scrollIntoViewIfNeeded();
  const aiBox = await page.locator('#aiHint').boundingBox();
  if (aiBox) {
    await page.screenshot({ path: path.join(OUT, 'panduan-bab2-03-ai-auto.png'), clip: { x: Math.max(0, aiBox.x - 300), y: Math.max(0, aiBox.y - 120), width: Math.min(900, aiBox.width + 600), height: aiBox.height + 200 } });
  } else {
    await elshot(page, '#btnAiPreview', 'panduan-bab2-03-ai-auto.png');
  }
  console.log('panduan-bab2-03-ai-auto.png bytes=' + fs.statSync(path.join(OUT, 'panduan-bab2-03-ai-auto.png')).size);
  await page.goto(BASE + '/admin/tour-edit.php?id=61&lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-04-edit-tanggal-harga.png');
  await page.goto(BASE + '/admin/hotels.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-05-daftar-hotel.png');
  await page.goto(BASE + '/admin/hotel-edit.php?id=1&lang=id');
  await page.waitForLoadState('domcontentloaded');
  await elshot(page, 'form', 'panduan-bab2-06-form-hotel.png');
  await page.goto(BASE + '/admin/hotel-rooms.php?hotel_id=1&lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-07-kamar.png');
  await page.goto(BASE + '/admin/flights.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-08-daftar-pesawat.png');
  await page.goto(BASE + '/tour-detail.php?slug=test&lang=id');
  await page.waitForLoadState('domcontentloaded').catch(() => {});
  await page.goto(BASE + '/tours.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-09-hasil-web.png');
  await page.goto(BASE + '/admin/ferries.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-bab2-10-daftar-ferry.png');
});
