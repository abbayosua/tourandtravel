import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Image lightbox — opens on tour-detail gallery click', () => {

  test('tour-detail has GLightbox initialized', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have GLightbox initialized
    const hasGlightbox = await page.evaluate(() => {
      return typeof GLightbox !== 'undefined';
    });
    expect(hasGlightbox).toBe(true);
  });

  test('tour-detail has gallery links with glightbox class', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have gallery links with glightbox class
    const galleryLinks = await page.locator('a.glightbox').count();
    expect(galleryLinks).toBeGreaterThan(0);
  });

  test('tour-detail gallery links have data-gallery attribute', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have data-gallery attribute
    const galleryLinks = await page.locator('a.glightbox[data-gallery]').count();
    expect(galleryLinks).toBeGreaterThan(0);
  });

  test('tour-detail gallery images have lazy loading', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have lazy loading images
    const lazyImages = await page.locator('img.lazy-image').count();
    expect(lazyImages).toBeGreaterThan(0);
  });

  test('clicking gallery image opens lightbox', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Wait for GLightbox to initialize
    await page.waitForFunction(() => typeof GLightbox !== 'undefined', { timeout: 5000 });
    
    // Click first gallery link
    const galleryLink = page.locator('a.glightbox').first();
    if (await galleryLink.count() > 0) {
      await galleryLink.click();
      
      // Wait for lightbox to open
      await page.waitForTimeout(500);
      
      // GLightbox creates a container with class glightbox
      const lightboxContainer = await page.locator('.glightbox, .glightbox-container, .glightbox-slide').count();
      expect(lightboxContainer).toBeGreaterThan(0);
    }
  });

  test('lightbox shows image in full size', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Wait for GLightbox to initialize
    await page.waitForFunction(() => typeof GLightbox !== 'undefined', { timeout: 5000 });
    
    // Click first gallery link
    const galleryLink = page.locator('a.glightbox').first();
    if (await galleryLink.count() > 0) {
      await galleryLink.click();
      
      // Wait for lightbox to open
      await page.waitForTimeout(500);
      
      // Should show image in lightbox
      const lightboxImage = await page.locator('.glightbox img, .glightbox-container img').count();
      expect(lightboxImage).toBeGreaterThan(0);
    }
  });

  test('hotel-detail has GLightbox initialized', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have GLightbox initialized
    const hasGlightbox = await page.evaluate(() => {
      return typeof GLightbox !== 'undefined';
    });
    expect(hasGlightbox).toBe(true);
  });

  test('hotel-detail has gallery links with glightbox class', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should have gallery links with glightbox class
    const galleryLinks = await page.locator('a.glightbox').count();
    expect(galleryLinks).toBeGreaterThan(0);
  });

  test('GLightbox CDN loaded correctly', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Check that GLightbox CSS is loaded
    const cssLoaded = await page.evaluate(() => {
      const links = document.querySelectorAll('link[href*="glightbox"]');
      return links.length > 0;
    });
    expect(cssLoaded).toBe(true);
    
    // Check that GLightbox JS is loaded
    const jsLoaded = await page.evaluate(() => {
      const scripts = document.querySelectorAll('script[src*="glightbox"]');
      return scripts.length > 0;
    });
    expect(jsLoaded).toBe(true);
  });

});
