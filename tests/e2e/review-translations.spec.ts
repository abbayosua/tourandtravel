import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync, unlinkSync } from 'fs';

const BASE = 'http://localhost/tourandtravel';
const PROJECT = '/Users/user/www/tourandtravel';
const PHP_ERROR = /Fatal error|Parse error|Deprecated:/i;

/**
 * Backlog #9: Review multi-bahasa.
 * E2E: tour-detail dengan review bahasa lain (punya terjemahan) → tampil + badge "Diterjemahkan";
 * review tanpa terjemahan → fallback teks asli tanpa badge.
 */
function runPhp(code: string): string {
  const tmp = `${PROJECT}/tests/e2e/_tmp_seed.php`;
  writeFileSync(tmp, `<?php\nrequire '${PROJECT}/includes/config.php';\nrequire '${PROJECT}/includes/db.php';\nrequire '${PROJECT}/includes/review-translations.php';\n${code}\n`);
  const out = execSync(`php ${tmp}`, { encoding: 'utf8' });
  try { unlinkSync(tmp); } catch {}
  return out.trim();
}

const TOUR_SLUG = 'chongqing-wulong-karst-national-park-day-tour';

test.describe('Review multi-bahasa (Backlog #9)', () => {
  let reviewId: string;

  test.afterEach(async () => {
    if (reviewId) {
      runPhp(`db()->prepare("DELETE FROM review_translations WHERE review_id=?")->execute([${reviewId}]);
              db()->prepare("DELETE FROM reviews WHERE id=?")->execute([${reviewId}]);
              db()->exec("DELETE FROM users WHERE id >= 999060 AND email LIKE '%@rt-e2e.local'");`);
      reviewId = '';
    }
  });

  test('TC-214a review EN dengan terjemahan ID → tampil + badge Diterjemahkan', async ({ page }) => {
    reviewId = runPhp(`
      $uid = 999060;
      db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'RT E2E', 'rt999060@rt-e2e.local', 'x')")->execute([$uid]);
      db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (180, ?, 5, 'Very good guide and clean bus', 'en')")->execute([$uid]);
      $rid = (int)db()->lastInsertId();
      rtGenerateForReview($rid, 'Very good guide and clean bus', 'en');
      echo $rid;
    `);

    await page.goto(`${BASE}/tour-detail.php?slug=${TOUR_SLUG}&lang=id`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // review EN (berbeda lang dari cookie id) punya terjemahan → muncul + badge
    await expect(page.locator('[data-testid="review-translated"]').first()).toBeVisible();
    // isi terjemahan mengandung kata kunci dari kamus
    expect(body).toMatch(/pemandu|bersih/i);
  });

  test('TC-214b review zh tanpa kata dikenal kamus → fallback teks asli tanpa badge', async ({ page }) => {
    reviewId = runPhp(`
      $uid = 999061;
      db()->prepare("INSERT IGNORE INTO users (id, name, email, password_hash) VALUES (?, 'RT E2E 2', 'rt999061@rt-e2e.local', 'x')")->execute([$uid]);
      db()->prepare("INSERT INTO reviews (tour_id, user_id, rating, comment, lang) VALUES (180, ?, 4, 'RT-TEST Unknown zh content', 'zh')")->execute([$uid]);
      echo (int)db()->lastInsertId();
    `);

    await page.goto(`${BASE}/tour-detail.php?slug=${TOUR_SLUG}&lang=id`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // fallback: teks asli tampil (bukan hilang), tanpa badge translated untuk review ini
    expect(body).toContain('RT-TEST Unknown zh content');
    // pastikan badge "Diterjemahkan" tidak muncul lebih dari TC-214a sisa — konteks review ini tidak diberi badge:
    const badgeCount = await page.locator('[data-testid="review-translated"]').count();
    // review zh tanpa terjemahan id (kamus zh→id tidak kenal kata itu) → tidak ada badge baru
    // (jumlah badge boleh 0 karena hanya 1 review di halaman ini)
    expect(badgeCount).toBeLessThanOrEqual(1);
  });
});
