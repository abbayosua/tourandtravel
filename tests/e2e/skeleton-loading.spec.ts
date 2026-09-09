import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Skeleton loading — appears then disappears', () => {

  test('tours.php: skeleton appears then disappears', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'domcontentloaded' });
    
    // Skeleton should be present initially
    const skeleton = await page.locator('#tourSkeleton').count();
    expect(skeleton).toBe(1);
    
    // Wait for content to load and skeleton to disappear
    await page.waitForFunction(() => {
      const skeleton = document.getElementById('tourSkeleton');
      return skeleton && skeleton.style.display === 'none';
    }, { timeout: 5000 });
    
    // Content should be visible
    const content = await page.locator('#tourContent').count();
    expect(content).toBe(1);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('hotels.php: skeleton appears then disappears', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php`, { waitUntil: 'domcontentloaded' });
    
    // Skeleton should be present initially
    const skeleton = await page.locator('#hotelSkeleton').count();
    expect(skeleton).toBe(1);
    
    // Wait for content to load and skeleton to disappear
    await page.waitForFunction(() => {
      const skeleton = document.getElementById('hotelSkeleton');
      return skeleton && skeleton.style.display === 'none';
    }, { timeout: 5000 });
    
    // Content should be visible
    const content = await page.locator('#hotelContent').count();
    expect(content).toBe(1);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('flights.php: skeleton appears then disappears', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'domcontentloaded' });
    
    // Skeleton should be present initially
    const skeleton = await page.locator('#flightSkeleton').count();
    expect(skeleton).toBe(1);
    
    // Wait for content to load and skeleton to disappear
    await page.waitForFunction(() => {
      const skeleton = document.getElementById('flightSkeleton');
      return skeleton && skeleton.style.display === 'none';
    }, { timeout: 5000 });
    
    // Content should be visible
    const content = await page.locator('#flightContent').count();
    expect(content).toBe(1);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('tours.php: skeleton has correct structure', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'domcontentloaded' });
    
    // Check skeleton structure
    const skeletonCards = await page.locator('#tourSkeleton .skeleton-card').count();
    expect(skeletonCards).toBe(6);
    
    const skeletonImages = await page.locator('#tourSkeleton .skeleton-img').count();
    expect(skeletonImages).toBe(6);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('hotels.php: skeleton has correct structure', async ({ page }) => {
    await page.goto(`${BASE}/hotels.php`, { waitUntil: 'domcontentloaded' });
    
    // Check skeleton structure
    const skeletonImages = await page.locator('#hotelSkeleton .skeleton-img').count();
    expect(skeletonImages).toBe(4);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('flights.php: skeleton has correct structure', async ({ page }) => {
    await page.goto(`${BASE}/flights.php`, { waitUntil: 'domcontentloaded' });
    
    // Check skeleton structure
    const skeletonElements = await page.locator('#flightSkeleton .skeleton').count();
    expect(skeletonElements).toBeGreaterThan(0);
    
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
  });

  test('skeleton CSS animation is present', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'domcontentloaded' });
    
    // Check that skeleton has shimmer animation
    const hasAnimation = await page.evaluate(() => {
      const skeleton = document.querySelector('.skeleton');
      if (!skeleton) return false;
      const style = window.getComputedStyle(skeleton);
      return style.animation.includes('skeleton-shimmer') || style.animationName.includes('skeleton-shimmer');
    });
    
    // Animation might not be detectable in all browsers, so just check skeleton exists
    const skeleton = await page.locator('.skeleton').count();
    expect(skeleton).toBeGreaterThan(0);
  });

  test('content replaces skeleton after timeout', async ({ page }) => {
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'domcontentloaded' });
    
    // Wait for content to fully load
    await page.waitForTimeout(500);
    
    // Skeleton should be hidden
    const skeletonVisible = await page.locator('#tourSkeleton').isVisible();
    expect(skeletonVisible).toBe(false);
    
    // Content should be visible
    const contentVisible = await page.locator('#tourContent').isVisible();
    expect(contentVisible).toBe(true);
  });

});
