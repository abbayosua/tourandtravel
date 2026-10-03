import { test, expect } from '@playwright/test';

/**
 * Regresi UI/UX: track.php dulu menampilkan ulang form pencarian tanpa pesan
 * saat kode booking tidak ditemukan — user mengira kodenya salah ketik tanpa
 * umpan balik. Sekarang ada error state yang jelas.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

test('kode booking tidak ditemukan menampilkan pesan error', async ({ page }) => {
  await page.goto(`${BASE}/track.php?code=NOPE123&lang=id`);

  const alert = page.locator('[data-testid="track-not-found"]');
  await expect(alert).toBeVisible();
  await expect(alert).toContainText('Kode booking tidak ditemukan');

  // Kode yang diketik tetap di input agar tak perlu ketik ulang.
  await expect(page.locator('input[name="code"]')).toHaveValue('NOPE123');

  await expect(page.locator('[data-testid="track-not-found"]').locator('..')).toHaveScreenshot('track-not-found.png', {
    maxDiffPixelRatio: 0.15,
  });
});

test('tanpa kode tidak ada pesan error', async ({ page }) => {
  await page.goto(`${BASE}/track.php?lang=id`);
  await expect(page.locator('[data-testid="track-not-found"]')).toHaveCount(0);
  await expect(page.locator('input[name="code"]')).toBeVisible();
});
