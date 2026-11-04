import { test, expect } from '@playwright/test';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';

async function adminLogin(page: any) {
  await page.context().addCookies([{ name: 'lang', value: 'id', url: BASE }]);
  await page.goto(BASE + '/admin/login.php');
  await page.fill('input[name=username]', ADMIN_USER);
  await page.fill('input[name=password]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type=submit]')]);
  await page.waitForLoadState('domcontentloaded');
}

async function imgOk(page: any, min = 7) {
  const n = await page.locator('.panduan-img').count();
  expect(n).toBeGreaterThanOrEqual(min);
  for (let i = 0; i < n; i++) {
    const w = await page.locator('.panduan-img').nth(i).evaluate((e: any) => (e as HTMLImageElement).naturalWidth);
    expect(w, 'gambar panduan ke-' + i + ' termuat').toBeGreaterThan(0);
  }
}

test('panduan multilingual EN tampil penuh', async ({ page }) => {
  await adminLogin(page);
  await page.goto(BASE + '/admin/panduan.php?lang=en');
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('h4').first()).toContainText('Admin Panel Guide');
  await expect(page.locator('#adminContent')).toContainText('Chapter 4');
  await expect(page.locator('#adminContent')).toContainText('Chapter 5');
  await expect(page.locator('#adminContent')).toContainText('Chapter 7');
  await expect(page.locator('#adminContent')).toContainText('Chapter 8');
  await expect(page.locator('#adminContent')).toContainText('Chapter 9');
  await expect(page.locator('#adminContent')).toContainText('Chapter 10');
  await expect(page.locator('#adminContent')).toContainText('Step 1');
  await expect(page.locator('#adminContent')).toContainText('Step 7');
  await expect(page.locator('#adminContent')).toContainText('If it fails');
  await imgOk(page);
});

test('panduan multilingual ZH tampil penuh', async ({ page }) => {
  await adminLogin(page);
  await page.goto(BASE + '/admin/panduan.php?lang=zh');
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('h4').first()).toContainText('管理面板指南');
  await expect(page.locator('#adminContent')).toContainText('第5章');
  await expect(page.locator('#adminContent')).toContainText('第7章');
  await expect(page.locator('#adminContent')).toContainText('第8章');
  await expect(page.locator('#adminContent')).toContainText('第9章');
  await expect(page.locator('#adminContent')).toContainText('第10章');
  await imgOk(page);
});

test('panduan ID tetap tampil penuh', async ({ page }) => {
  await adminLogin(page);
  await page.goto(BASE + '/admin/panduan.php?lang=id');
  await page.waitForLoadState('domcontentloaded');
  await expect(page.locator('h4').first()).toContainText('Panduan Admin Panel');
  await expect(page.locator('#adminContent')).toContainText('Bab 4');
  await expect(page.locator('#adminContent')).toContainText('Bab 5');
  await expect(page.locator('#adminContent')).toContainText('Bab 7');
  await expect(page.locator('#adminContent')).toContainText('Bab 8');
  await expect(page.locator('#adminContent')).toContainText('Bab 9');
  await expect(page.locator('#adminContent')).toContainText('Bab 10');
  await imgOk(page);
});
