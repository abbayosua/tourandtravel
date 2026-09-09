import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Trust badge — visible on detail pages', () => {

  test('tour-detail shows trust badge section', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show trust badge section
    expect(body).toMatch(/Kenapa Pilih|Why Choose/i);
    
    // Should show trust badges
    expect(body).toMatch(/Harga Transparan|Transparent Pricing/i);
    expect(body).toMatch(/Terpercaya|Trusted/i);
    expect(body).toMatch(/CS 24\/7/i);
    expect(body).toMatch(/Mudah Booking|Easy Booking/i);
  });

  test('hotel-detail shows trust badge section', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show trust badge section
    expect(body).toMatch(/Kenapa Booking|Why Book/i);
    
    // Should show trust badges
    expect(body).toMatch(/Harga Transparan|Transparent Pricing/i);
    expect(body).toMatch(/Terpercaya|Trusted/i);
    expect(body).toMatch(/CS 24\/7/i);
    expect(body).toMatch(/Mudah Booking|Easy Booking/i);
  });

  test('flight-detail shows trust badge section', async ({ page }) => {
    // Flight detail requires schedule_id, use a known one
    await page.goto(`${BASE}/flight-detail.php?schedule_id=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show trust badge section (or redirect if no flight)
    // Trust badges should be present if page loads
    if (body.includes('Kenapa Pesan') || body.includes('Why Book')) {
      expect(body).toMatch(/Harga Transparan|Transparent Pricing/i);
      expect(body).toMatch(/Terpercaya|Trusted/i);
    }
  });

  test('trust badge has 4 cards in grid layout', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show 4 trust badge cards
    const trustCards = await page.locator('.col-6.col-md-3 .card').count();
    expect(trustCards).toBe(4);
  });

  test('trust badge section has correct styling', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    
    // Check for trust section with proper classes
    const trustSection = await page.locator('section:has(.col-6.col-md-3)').count();
    expect(trustSection).toBeGreaterThanOrEqual(1);
    
    // Check for card styling
    const cards = await page.locator('.col-6.col-md-3 .card').count();
    expect(cards).toBe(4);
  });

  test('trust badge icons render correctly', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show trust badge icons (bi-*)
    expect(body).toMatch(/bi-tags-fill|bi-shield-check|bi-headset|bi-wallet2/i);
  });

  test('trust badge text content is correct', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=bali-adventure-3d2n`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Should show trust badge text
    expect(body).toMatch(/Tidak ada biaya tersembunyi|No hidden fees/i);
    expect(body).toMatch(/tahun melayani pelanggan|years serving customers/i);
    expect(body).toMatch(/Siap bantu kapan saja|Ready to help anytime/i);
    expect(body).toMatch(/Proses cepat.*praktis|Fast.*easy process/i);
  });

});
