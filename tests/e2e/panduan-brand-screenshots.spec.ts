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

test('screenshot panduan branding via CDP', async ({ page }) => {
  fs.mkdirSync(OUT, { recursive: true });
  // 01 login (belum submit, biar kredensial tidak kefoto)
  await page.goto(BASE + '/admin/login.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await shot(page, 'panduan-brand-01-login.png');
  // login
  await page.fill('input[name=username]', 'admin');
  await page.fill('input[name=password]', 'tmpcheck123');
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  // 02-05: foto per bagian form (element screenshot, bukan full page)
  await page.goto(BASE + '/admin/brand-settings.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.locator('xpath=//input[@data-testid="brand-name"]/..').scrollIntoViewIfNeeded();
  const nBlock = await page.locator('xpath=//input[@data-testid="brand-name"]/..').boundingBox();
  const tBlock = await page.locator('xpath=//input[@data-testid="brand-tagline"]/..').boundingBox();
  const clip02 = { x: Math.min(nBlock!.x, tBlock!.x) - 8, y: nBlock!.y - 8, width: Math.max(nBlock!.width, tBlock!.width) + 16, height: (tBlock!.y + tBlock!.height) - nBlock!.y + 16 };
  await page.screenshot({ path: path.join(OUT, 'panduan-brand-02-form-atas.png'), clip: clip02 });
  console.log('panduan-brand-02-form-atas.png bytes=' + fs.statSync(path.join(OUT, 'panduan-brand-02-form-atas.png')).size);
  await page.locator('xpath=//input[@data-testid="brand-social-instagram"]/..').screenshot({ path: path.join(OUT, 'panduan-brand-03-sosmed.png') });
  console.log('panduan-brand-03-sosmed.png bytes=' + fs.statSync(path.join(OUT, 'panduan-brand-03-sosmed.png')).size);
  await page.locator('xpath=//input[@name="contact_address"]/..').screenshot({ path: path.join(OUT, 'panduan-brand-04-kontak.png') });
  console.log('panduan-brand-04-kontak.png bytes=' + fs.statSync(path.join(OUT, 'panduan-brand-04-kontak.png')).size);
  await page.locator('xpath=//input[@data-testid="brand-logo"]/..').screenshot({ path: path.join(OUT, 'panduan-brand-05-logo.png') });
  console.log('panduan-brand-05-logo.png bytes=' + fs.statSync(path.join(OUT, 'panduan-brand-05-logo.png')).size);
  // 06: klik Simpan tanpa ubah data (idempotent) -> alert hijau
  await Promise.all([page.waitForSelector('[data-testid=brand-saved]'), page.click('[data-testid=brand-save]')]);
  await shot(page, 'panduan-brand-06-simpan.png');
  // 07: hasil footer di homepage
  await page.goto(BASE + '/index.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await page.locator('footer#kontak').scrollIntoViewIfNeeded();
  await shot(page, 'panduan-brand-07-hasil-footer.png');
});
