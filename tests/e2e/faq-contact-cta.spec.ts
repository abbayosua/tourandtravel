import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: tombol "Hubungi Kami" di FAQ dulu dead link
 * (href="#" onclick="return false") — diklik tidak melakukan apa pun. Sekarang
 * mengarah ke WhatsApp (wa.me) memakai nomor perusahaan.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

test('tombol Hubungi Kami di FAQ mengarah ke WhatsApp', async ({ page }) => {
  await page.goto(`${BASE}/faq.php?lang=id`);

  const cta = page.locator('[data-testid="faq-contact"]');
  await expect(cta).toBeVisible();

  const href = await cta.getAttribute('href');
  expect(href).toContain('https://wa.me/6281234567890');
  expect(await cta.getAttribute('onclick')).toBeNull();
  expect(await cta.getAttribute('target')).toBe('_blank');

  await expect(cta.locator('..')).toHaveScreenshot('faq-contact-cta.png', { maxDiffPixelRatio: 0.15 });
});
