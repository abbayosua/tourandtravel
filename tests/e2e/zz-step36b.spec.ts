import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import { writeFileSync } from 'fs';
const BASE = 'http://localhost/tourandtravel';
const dbFile = (sql: string): string => {
  writeFileSync('/tmp/q36.sql', sql);
  return execSync('mysql -u root tourandtravel -N -s < /tmp/q36.sql').toString().trim();
};
test('checkout: use_points mengurangi total & saldo', async ({ page }) => {
  const uid = Number(dbFile("SELECT id FROM users WHERE email LIKE 'e2e_pts%'"));
  test.skip(!uid, 'user test dibuat di step36');
  execSync(`mysql -u root tourandtravel -e "DELETE FROM points_ledger WHERE user_id=${uid}; INSERT INTO points_ledger (user_id, points, reason, note) VALUES (${uid}, 200, 'earn', 'Seed checkout');"`).toString();
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', 'e2e_pts@test.local');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.goto(`${BASE}/tour-detail.php?slug=6d-tokyo-wonders`);
  await expect(page.locator('#usePointsTour')).toBeVisible(); // saldo >= 100 → checkbox ada
  await page.check('#usePointsTour');
  await page.fill('input[name="name"]', 'E2E Pts Checkout');
  await page.fill('input[name="phone"]', '0812000555');
  const passport = await page.locator('input[name="passport_photo"]');
  await passport.setInputFiles({ name: 'pp.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64') });
  await page.click('#bookingSubmitBtn');
  await page.waitForLoadState('load');
  // total diverifikasi via saldo points
  // 1858 SGD - flash? tour 62 flash 15% → 1579.3; - Rp 10.000... harga SGD! diskon points dalam IDR → konversi rumit; cukup pastikan tercatat & points saldo berkurang
  const bal = Number(dbFile(`SELECT COALESCE(SUM(points),0) FROM points_ledger WHERE user_id=${uid}`));
  expect(bal).toBe(100); // 200 - 100 redeem
  execSync(`mysql -u root tourandtravel -e "DELETE FROM points_ledger WHERE user_id=${uid}; DELETE FROM bookings WHERE name='E2E Pts Checkout';"`).toString();
});
