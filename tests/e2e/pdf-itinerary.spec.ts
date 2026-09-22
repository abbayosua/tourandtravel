import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = 'chongqing-wulong-karst-national-park-day-tour';

test.describe('PDF itinerary download', () => {
  test('TC-207 download itinerary PDF sukses (valid PDF, headers benar)', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=${SLUG}`);
    expect(resp.status()).toBe(200);
    const ct = resp.headers()['content-type'] || '';
    expect(ct).toMatch(/pdf|octet-stream|download/i);
    const body = await resp.body();
    // magic bytes %PDF-
    expect(body.subarray(0, 5).toString()).toBe('%PDF-');
    expect(body.length).toBeGreaterThan(500);
  });

  test('TC-208a PDF slug invalid → HTTP 404', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=tidak-pernah-ada-xyz`);
    expect(resp.status()).toBe(404);
  });

  test('TC-208b PDF tanpa slug → HTTP 404', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php`);
    expect(resp.status()).toBe(404);
  });

  test('TC-208c PDF tour invalid tidak menghasilkan PDF valid', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=xxx-404`);
    const body = await resp.body();
    expect(resp.status()).toBe(404);
    expect(body.subarray(0, 5).toString()).not.toBe('%PDF-');
  });

  test('TC-208d itinerary user PDF tanpa login → redirect/403', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/itinerary-pdf.php?id=1`, { maxRedirects: 0 });
    expect([302, 401, 403]).toContain(resp.status());
  });
});
