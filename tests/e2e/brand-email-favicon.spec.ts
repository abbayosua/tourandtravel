import { test, expect } from '@playwright/test';
import { execFileSync } from 'child_process';

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

function setting(key: string): string {
  return mysql('SELECT setting_value FROM settings WHERE setting_key=' + String.fromCharCode(39) + key + String.fromCharCode(39));
}
test('head favicon theme-color dan OG mengikuti Brand dan Logo', async ({ page }) => {
  const theme = setting('theme_color') || '#0d6efd';
  const og = setting('site_og_image');
  await page.goto(BASE + '/index.php?lang=id');
  const icon = page.locator('[data-testid=brand-favicon]');
  const iconHref = await icon.getAttribute('href');
  await expect(icon).toHaveAttribute('href', new RegExp('uploads/brand/'));
  const iconRes = await page.request.get(iconHref || '');
  expect(iconRes.ok()).toBe(true);
  expect(String(iconRes.headers()['content-type'])).toContain('image');
  await page.locator('[data-testid=brand-favicon-img], footer').first().screenshot({ path: 'test-results/brand-favicon-visible.png' }).catch(() => {});
  await expect(page.locator('footer')).toBeVisible();
  const tc = await page.locator('meta[name=theme-color]').getAttribute('content');
  expect(tc).toBe(theme);
  const ogImg = await page.locator('meta[property="og:image"]').getAttribute('content');
  expect(ogImg).toContain(og);
  await expect(page.locator('footer')).toHaveScreenshot('brand-head-favicon.png', { maxDiffPixelRatio: 0.35 });
});

test('manifest PWA memakai nama dan warna Royal Dream', async ({ request }) => {
  const res = await request.get(BASE + '/manifest.php');
  expect(res.ok()).toBe(true);
  const j = await res.json();
  expect(j.name).toContain('Royal Dream');
  const theme = setting('theme_color') || '#0d6efd';
  expect(j.theme_color).toBe(theme);
  expect(j.background_color).toBe(theme);
});

test('email log transaksi berisi brand Royal Dream', async () => {
  const email = 'e2e-royal-' + Date.now().toString().slice(-6) + '@example.test';
  const raw = execFileSync('php', ['/tmp/send-brand-email.php', email], { encoding: 'utf8' });
  const sent = JSON.parse(raw);
  expect(sent.ok).toBe(true);
  const id = String(sent.log_id);
  expect(mysql('SELECT to_email FROM email_log WHERE id=' + id)).toBe(email);
  expect(mysql('SELECT subject FROM email_log WHERE id=' + id).length).toBeGreaterThan(0);
  const html = execFileSync('php', ['/tmp/send-brand-email-html.php'], { encoding: 'utf8' });
  expect(html).toContain('Royal Dream');
});
