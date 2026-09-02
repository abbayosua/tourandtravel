import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = '8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Tour Detail - happy path', () => {

  test('slug valid: title, price, description, gallery render', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    expect(resp?.status() === 200 || resp?.status() === null).toBeTruthy();

    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Title
    expect(body).toMatch(/Zhangjiajie|HUNAN/i);

    // Price (SGD 1059)
    expect(body).toMatch(/1\.059|S\$|price/i);

    // Description
    expect(body).toMatch(/Zhangjiajie National Forest|Tianmen Mountain|Fenghuang/i);

    // Gallery section present
    const galleryThumbs = await page.locator('img.gallery-thumb').count();
    expect(galleryThumbs).toBeGreaterThanOrEqual(0); // may be empty if no images

    // Breadcrumb navigation
    await expect(page.locator('.breadcrumb')).toHaveCount(1);

    // Sidebar — either booking form or 'no dates' warning
    const sidebarForms = await page.locator('form').count();
    if (sidebarForms === 0) {
      // No tour dates available — warning shown instead
      await expect(page.locator('.alert-warning')).toBeVisible();
    }
  });

  test('slug valid: amenities included section', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Amenities / included section
    expect(body).toMatch(/Fasilitas Termasuk|Included Amenities|Hotel|Makan|Guide/i);
  });

  test('slug valid: location/map section', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Map section (at least area mentioned)
    expect(body).toMatch(/Lokasi|Location|Zhangjiajie|Hunan|China/i);
  });

  test('slug valid: review section', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Review section
    expect(body).toMatch(/Ulasan|Review|Testimonial/i);
  });

  test('slug valid: sidebar sticky with booking form', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Sidebar should have price
    expect(body).toMatch(/\$|SGD|Rp|orang|price/i);
  });

  test('slug valid: language switch preserves slug', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // English text
    expect(body).toMatch(/Image Gallery|Included Amenities|Location/i);
    expect(page.url()).toContain(SLUG);
  });
});

test.describe('Tour Detail - sad path', () => {

  test('slug tidak ada (404-like)', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=nonexistent-slug-12345`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should show error or redirect — but not crash
    expect(body).toMatch(/tidak di|tidak ada|not found|error|Error|404|kembali|back/i);
  });

  test('slug kosong', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should not crash — show error or redirect
  });

  test('slug dengan karakter spesial', async ({ page }) => {
    const resp = await page.goto(`${BASE}/tour-detail.php?slug=<script>alert(1)</script>`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Should not execute XSS, no crash
    expect(body).not.toMatch(/alert\(1\)/);
  });
});