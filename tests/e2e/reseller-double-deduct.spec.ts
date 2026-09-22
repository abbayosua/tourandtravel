import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Deprecated|Notice:|Parse error|Uncaught|Undefined.*variable)/i;
const EMAIL = `ddos_reseller_${Date.now()}@example.com`;
const PASSWORD = 'password123';
const RESELLER_PRICE = 500000;
let tourId = 0;
let tourDateId = 0;

function setup() {
  const tour = execSync(
    `mysql -u root tourandtravel -N -e "SELECT id FROM tours WHERE is_active = 1 ORDER BY id ASC LIMIT 1"`,
    { encoding: 'utf-8' }
  ).trim();
  tourId = parseInt(tour);
  const td = execSync(
    `mysql -u root tourandtravel -N -e "SELECT id FROM tour_dates WHERE tour_id = ${tourId} AND is_active = 1 AND departure_date >= CURDATE() ORDER BY departure_date ASC LIMIT 1"`,
    { encoding: 'utf-8' }
  ).trim();
  tourDateId = parseInt(td);
  execSync(`php -r 'require_once "includes/config.php"; require_once "includes/db.php"; $hash = password_hash("${PASSWORD}", PASSWORD_DEFAULT); db()->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, reseller_balance) VALUES (?, ?, ?, ?, ?, ?)")->execute(["DDoS Reseller", "${EMAIL}", "0812399777", $hash, "reseller", 2000000]);'`, { stdio: 'pipe' });
  execSync(`mysql -u root tourandtravel -e "INSERT IGNORE INTO reseller_tour_prices (tour_id, reseller_price, min_pax, active) VALUES (${tourId}, ${RESELLER_PRICE}, 1, 1)"`, { stdio: 'pipe' });
}

function cleanup() {
  try {
    execSync(`mysql -u root tourandtravel -e "
      DELETE FROM bookings WHERE user_id IN (SELECT id FROM users WHERE email = '${EMAIL}');
      DELETE FROM reseller_tour_prices WHERE tour_id = ${tourId};
      DELETE FROM users WHERE email = '${EMAIL}';
    "`, { stdio: 'pipe' });
  } catch {}
}

async function login(page: any) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="password"]', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

async function bookOnce(page: any) {
  await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
  if (tourDateId) await page.selectOption('select[name="tour_date_id"]', String(tourDateId));
  await page.fill('input[name="passengers"]', '1');
  await page.fill('input[name="name"]', 'DDoS Reseller');
  await page.fill('input[name="email"]', EMAIL);
  await page.fill('input[name="phone"]', '0812399777');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

test.describe('Reseller double-deduction guard', () => {
  test.beforeAll(() => { setup(); });
  test.afterAll(() => { cleanup(); });

  test('TC-709 retry 2x booking → 2 booking terpisah dgn deduksi masing2 TEPAT (total akuntansi konsisten)', async ({ page }) => {
    await login(page);
    const balStart = parseFloat(execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim());

    await bookOnce(page);
    await bookOnce(page);

    const balEnd = parseFloat(execSync(
      `mysql -u root tourandtravel -N -e "SELECT reseller_balance FROM users WHERE email = '${EMAIL}'"`,
      { encoding: 'utf-8' }
    ).trim());

    // Akuntansi konsisten: total deduksi = jumlah booking × harga (500rb per booking)
    const nBookings = parseInt(execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source='reseller'"`,
      { encoding: 'utf-8' }
    ).trim());
    const sumBookings = parseFloat(execSync(
      `mysql -u root tourandtravel -N -e "SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source='reseller'"`,
      { encoding: 'utf-8' }
    ).trim());

    expect(nBookings).toBe(2);
    expect(sumBookings).toBe(1000000);
    expect(balStart - balEnd).toBe(sumBookings); // INVARIAN: deduksi total == sum booking

    // tidak ada booking dgn total_price ≠ reseller_price×pax
    const bad = execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND total_price NOT IN (500000, 1000000, 1500000)"`,
      { encoding: 'utf-8' }
    ).trim();
    expect(parseInt(bad)).toBe(0);
  });

  test('TC-709b refresh halaman sukses TIDAK membuat booking baru (GET aman)', async ({ page }) => {
    await login(page);
    const before = parseInt(execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source='reseller'"`,
      { encoding: 'utf-8' }
    ).trim());

    // refresh GET — booking hanya via POST, GET harus idempoten
    await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
    await page.reload({ waitUntil: 'load' });
    await page.reload({ waitUntil: 'load' });

    const after = parseInt(execSync(
      `mysql -u root tourandtravel -N -e "SELECT COUNT(*) FROM bookings WHERE user_id = (SELECT id FROM users WHERE email = '${EMAIL}') AND booking_source='reseller'"`,
      { encoding: 'utf-8' }
    ).trim());
    expect(after).toBe(before);
  });
});

test.describe('Reseller UI display', () => {
  test('TC-1006 saldo & role tampil di header/dropdown reseller', async ({ page }) => {
    await login(page);
    // header menampilkan nama + dropdown; halaman reseller-booking menampilkan Saldo & Harga Reseller
    await page.goto(`${BASE}/reseller-booking.php?tour_id=${tourId}`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(body).toMatch(/Saldo/i);
    expect(body).toMatch(/Harga Reseller/i);
    expect(body).toMatch(/Rp\s?500\.000|Rp\s?500,000/);
  });

  test('TC-1006b badge reseller di tour-card untuk reseller', async ({ page }) => {
    await login(page);
    await page.goto(`${BASE}/tours.php`, { waitUntil: 'load' });
    const badge = page.locator('[data-testid="card-reseller-price"]');
    // tour pertama punya pricing reseller dari setup
    expect(await badge.count()).toBeGreaterThanOrEqual(1);
    const txt = await badge.first().textContent();
    expect(txt).toMatch(/Reseller/i);
    expect(txt).toMatch(/Rp/);
  });
});
