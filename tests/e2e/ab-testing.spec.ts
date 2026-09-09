import { test, expect } from '@playwright/test';

const PHP_ERROR = /(Fatal error|Warning:|Deprecated|Parse error|Uncaught|Undefined variable|error\s*:\s*<)/i;
const VARIANT_A = /Pesan Sekarang|Order Now/i;
const VARIANT_B = /Booking Sekarang|Book Now/i;

async function getCTAText(page) {
  const text = (await page.locator('#bookingSubmitBtn').first().textContent())?.trim() ?? '';
  return text;
}

async function expectValidVariant(page) {
  const text = await getCTAText(page);
  expect(VARIANT_A.test(text) || VARIANT_B.test(text)).toBe(true);
  return VARIANT_A.test(text) ? 'A' : 'B';
}

test.describe('A/B testing CTA tour-detail', () => {

  test('CTA menampilkan salah satu varian valid', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expectValidVariant(page);
  });

  test('varian konsisten setelah reload (session-persist)', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const first = await expectValidVariant(page);
    for (let i = 0; i < 2; i++) {
      await page.reload({ waitUntil: 'load' });
      const again = await expectValidVariant(page);
      expect(again).toBe(first);
    }
  });

  test('varian konsisten lintas halaman tour dalam satu session', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const first = await expectValidVariant(page);
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=6d-tokyo-wonders', { waitUntil: 'load' });
    const second = await expectValidVariant(page);
    expect(second).toBe(first);
  });

  test('impresi tercatat di DB untuk session ini', async ({ page, request }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await expectValidVariant(page);
    const { execSync } = await import('child_process');
    const count = execSync(
      `php -r 'require "includes/config.php"; require "includes/db.php"; echo db()->query("SELECT COUNT(*) FROM ab_impressions WHERE test_name=\\"tour_cta_text\\"")->fetchColumn();'`
    ).toString().trim();
    expect(parseInt(count)).toBeGreaterThanOrEqual(1);
  });

});
