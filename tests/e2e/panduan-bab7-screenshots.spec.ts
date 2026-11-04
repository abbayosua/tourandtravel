import { test } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const OUT = path.join(__dirname, '..', '..', 'assets', 'img', 'panduan');

test('screenshot panduan Bab 7 via CDP', async ({ page }) => {
  fs.mkdirSync(OUT, { recursive: true });
  const shot = async (url: string, name: string) => {
    await page.goto(BASE + url);
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
  await shot('/admin/posts.php?lang=id', 'panduan-bab7-01-posts.png');
  await shot('/admin/reviews.php?lang=id', 'panduan-bab7-02-reviews.png');
  await shot('/admin/faq.php?lang=id', 'panduan-bab7-03-faq.png');
  await shot('/admin/nav-menus.php?lang=id', 'panduan-bab7-04-nav.png');
  await shot('/admin/hero-slides.php?lang=id', 'panduan-bab7-05-hero.png');
});
