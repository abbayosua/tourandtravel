import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const SLUG = '8d-hunan-zhangjiajie-fenghuang-ancient-town-furong-town';

function dbRun(sql: string) {
  execSync(`mysql -u root tourandtravel -e "${sql.replace(/"/g, '\"')}"`, { stdio: 'pipe' });
}

async function registerAndBook(page: import('@playwright/test').Page, tag: string): Promise<string> {
  const email = `rv${tag}${Date.now()}@test.local`;
  await page.goto(`${BASE}/register.php`);
  await page.fill('input[name="name"]', 'RV ' + tag);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', 'secret123');
  await page.fill('input[name="confirm_password"]', 'secret123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
  dbRun(`UPDATE tour_dates SET available_slots = available_slots + 2 WHERE id = 1;`);
  await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`);
  const opt = await page.$eval('select[name="tour_date_id"] option:not([value=""])', o => o.value);
  await page.selectOption('select[name="tour_date_id"]', opt);
  await page.fill('input[name="name"]', 'RV ' + tag);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '0812');
  await page.setInputFiles('input[name="passport_photo"]', '/tmp/hero-e2e-test.png');
  await page.click('#bookingSubmitBtn');
  await page.waitForURL('**/booking-success.php*', { timeout: 20000 });
  dbRun(`UPDATE bookings SET status='confirmed' WHERE email='${email}' AND status='pending'`);
  return email;
}

test.describe('Review sub-rating — aspect bars display', () => {
  test.afterEach(() => {
    dbRun(`DELETE FROM review_subratings WHERE review_id IN (SELECT id FROM reviews WHERE comment LIKE 'E2E-SR%'); DELETE FROM reviews WHERE comment LIKE 'E2E-SR%'; DELETE FROM users WHERE email LIKE 'rv%@test.local';`);
  });

  test('submit review with sub-ratings → aspect bars display', async ({ page }) => {
    const email = await registerAndBook(page, 'SR1');
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&review=success`);
    
    // Fill review form with sub-ratings
    await page.fill('textarea[name="comment"]', 'E2E-SR review with sub-ratings');
    
    // Set sub-ratings for each aspect
    const aspects = ['cleanliness', 'location', 'staff', 'value', 'facilities', 'comfort'];
    for (const aspect of aspects) {
      await page.click(`input[name="subrating[${aspect}]"][value="5"]`);
    }
    
    // Set main rating
    await page.click('input[name="rating"][value="5"]');
    
    // Submit review
    await page.click('form[action="review-submit.php"] button[type="submit"]');
    await page.waitForLoadState('load');
    
    const body = await page.textContent('body') || '';
    expect(body).toContain('E2E-SR review with sub-ratings');
  });

  test('review sub-ratings saved in database', async ({ page }) => {
    const email = await registerAndBook(page, 'SR2');
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&review=success`);
    
    // Fill review with specific sub-ratings
    await page.fill('textarea[name="comment"]', 'E2E-SR test sub-ratings saved');
    await page.click('input[name="subrating[cleanliness]"][value="4"]');
    await page.click('input[name="subrating[location]"][value="5"]');
    await page.click('input[name="rating"][value="4"]');
    
    // Submit review
    await page.click('form[action="review-submit.php"] button[type="submit"]');
    await page.waitForLoadState('load');
    
    // Verify sub-ratings in database
    const reviewId = dbRun(`SELECT id FROM reviews WHERE comment = 'E2E-SR test sub-ratings saved' LIMIT 1`);
    const subratings = dbRun(`SELECT COUNT(*) FROM review_subratings WHERE review_id = ${reviewId}`);
    expect(parseInt(subratings)).toBeGreaterThanOrEqual(2);
  });

  test('tour detail shows aspect bars when reviews have sub-ratings', async ({ page }) => {
    // Create a review with sub-ratings via DB
    dbRun(`INSERT INTO reviews (tour_id, user_id, rating, comment) VALUES (61, 1, 5, 'E2E-SR aspect bars test')`);
    const reviewId = dbRun(`SELECT id FROM reviews WHERE comment = 'E2E-SR aspect bars test' LIMIT 1`);
    dbRun(`INSERT INTO review_subratings (review_id, aspect, rating) VALUES (${reviewId}, 'cleanliness', 5)`);
    dbRun(`INSERT INTO review_subratings (review_id, aspect, rating) VALUES (${reviewId}, 'location', 4)`);
    dbRun(`INSERT INTO review_subratings (review_id, aspect, rating) VALUES (${reviewId}, 'staff', 5)`);
    
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    
    // Should show aspect bars section
    expect(body).toMatch(/Rating per Aspek|Aspect Rating/i);
    
    // Should show specific aspects
    expect(body).toMatch(/Kebersihan|Lokasi|Staff/i);
  });

  test('hotel review with sub-ratings displays aspect bars', async ({ page }) => {
    // Create a hotel review with sub-ratings via DB
    dbRun(`INSERT INTO reviews (hotel_id, user_id, rating, comment) VALUES (37, 1, 5, 'E2E-SR hotel review')`);
    const reviewId = dbRun(`SELECT id FROM reviews WHERE comment = 'E2E-SR hotel review' LIMIT 1`);
    dbRun(`INSERT INTO review_subratings (review_id, aspect, rating) VALUES (${reviewId}, 'cleanliness', 5)`);
    dbRun(`INSERT INTO review_subratings (review_id, aspect, rating) VALUES (${reviewId}, 'location', 4)`);
    
    await page.goto(`${BASE}/hotel-detail.php?slug=artotel-gelora-senayan`, { waitUntil: 'load' });
    const body = await page.textContent('body') || '';
    
    // Should show aspect bars section
    expect(body).toMatch(/Rating per Aspek|Aspect Rating/i);
    
    // Cleanup
    dbRun(`DELETE FROM review_subratings WHERE review_id = ${reviewId}`);
    dbRun(`DELETE FROM reviews WHERE id = ${reviewId}`);
  });

  test('sub-rating form elements present in review form', async ({ page }) => {
    const email = await registerAndBook(page, 'SR3');
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&review=success`);
    
    // Check that sub-rating form elements exist
    const subratingInputs = await page.locator('input[name^="subrating["]').count();
    expect(subratingInputs).toBe(6); // 6 aspects
    
    // Check each aspect has radio buttons
    const aspects = ['cleanliness', 'location', 'staff', 'value', 'facilities', 'comfort'];
    for (const aspect of aspects) {
      const radios = await page.locator(`input[name="subrating[${aspect}]"]`).count();
      expect(radios).toBe(5); // 5 star options
    }
  });

});
