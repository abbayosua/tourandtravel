import { test, expect, Page } from '@playwright/test';

/**
 * Regresi UI/UX: form newsletter di footer dulu punya DUA handler submit
 * (inline di includes/footer-shared.php + assets/js/klook.js initNewsletter),
 * sehingga satu klik mengirim dua POST ke newsletter-ajax.php (balapan pesan,
 * potensi baris ganda). Sekarang hanya satu handler + guard anti dobel-submit
 * dan tombol di-disable saat request berjalan.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

async function openHome(page: Page) {
  await page.goto(`${BASE}/index.php?lang=id`);
  await expect(page.locator('#newsletterForm')).toBeVisible();
}

test('submit newsletter hanya mengirim satu POST', async ({ page }) => {
  let posts = 0;
  await page.route('**/newsletter-ajax.php', async (route) => {
    if (route.request().method() === 'POST') posts++;
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, message: 'Berhasil berlangganan! Cek email Anda untuk konfirmasi.' }),
    });
  });

  await openHome(page);
  await page.fill('#newsletterEmail', 'e2e-once@t.local');
  await page.click('#newsletterForm button[type="submit"]');

  const msg = page.locator('.klook-newsletter-msg');
  await expect(msg).toContainText('Berhasil berlangganan');
  // Beri jeda singkat supaya handler duplikat (kalau ada) ikut terhitung.
  await page.waitForTimeout(600);
  expect(posts).toBe(1);
});

test('tombol terkunci saat request berjalan lalu aktif lagi', async ({ page }) => {
  let release!: () => void;
  const gate = new Promise<void>((r) => (release = r));
  await page.route('**/newsletter-ajax.php', async (route) => {
    await gate;
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, message: 'Berhasil berlangganan! Cek email Anda untuk konfirmasi.' }),
    });
  });

  await openHome(page);
  const btn = page.locator('#newsletterForm button[type="submit"]');
  await page.fill('#newsletterEmail', 'e2e-busy@t.local');
  await btn.click();

  await expect(btn).toBeDisabled();

  release();
  await expect(page.locator('.klook-newsletter-msg')).toContainText('Berhasil berlangganan');
  await expect(btn).toBeEnabled();
});

test('blok newsletter footer stabil (screenshot)', async ({ page }) => {
  await page.route('**/newsletter-ajax.php', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, message: 'Berhasil berlangganan! Cek email Anda untuk konfirmasi.' }),
    })
  );

  await openHome(page);
  const block = page.locator('[data-testid="newsletter-block"]');
  await block.scrollIntoViewIfNeeded();
  await page.fill('#newsletterEmail', 'e2e-shot@t.local');
  await page.click('#newsletterForm button[type="submit"]');
  await expect(page.locator('.klook-newsletter-msg')).toContainText('Berhasil berlangganan');

  await expect(block).toHaveScreenshot('newsletter-block.png', { maxDiffPixelRatio: 0.05 });
});
