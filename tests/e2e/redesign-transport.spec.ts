import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

test.describe('Redesign signature - Flights (Traveloka)', () => {

  test('transport tabs + search fields + sort bar + sidebar filter', async ({ page }) => {
    const resp = await page.goto(`${BASE}/flights.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Traveloka transport tabs: Pesawat aktif, Kereta/Ferry/Rental link
    const tabs = await page.locator('.booking-tab').count();
    expect(tabs).toBeGreaterThanOrEqual(4);
    await expect(page.locator('.booking-tab.active:has-text("Pesawat"), .booking-tab.active:has-text("Flights")')).toBeVisible();

    // Legacy field names preserved
    await expect(page.locator('input[name="from"]')).toHaveCount(1);
    await expect(page.locator('input[name="to"]')).toHaveCount(1);
    await expect(page.locator('input[name="date"]')).toHaveCount(1);
    await expect(page.locator('input[name="return_date"]')).toHaveCount(1);
    await expect(page.locator('select[name="passengers"]')).toHaveCount(1);
    await expect(page.locator('select[name="class"]')).toHaveCount(1);
    await expect(page.locator('button[name="search"]')).toHaveCount(1);
    await expect(page.locator('input#tripTypeHidden')).toHaveCount(1);
    await expect(page.locator('.trip-type-btn[data-type="oneway"]')).toHaveCount(1);
    await expect(page.locator('.trip-type-btn[data-type="roundtrip"]')).toHaveCount(1);

    // Sidebar filter Traveloka hanya tampil saat doSearch — uji via halaman hasil
    await page.goto(`${BASE}/flights.php?from=CGK&to=DPS&date=2026-12-20&search=1`, { waitUntil: 'load' });
    const body2 = await page.textContent('body');
    expect(body2).not.toMatch(PHP_ERROR);
    expect(body2).toMatch(/Maskapai|Airline|Jam Berangkat|Transit|Harga/i);
    await expect(page.locator('#flightFilterCollapse')).toBeVisible();
  });

  test('sort bar muncul saat search + flight-card hover class', async ({ page }) => {
    await page.goto(`${BASE}/flights.php?from=Jakarta%20(CGK)&to=Denpasar%20(DPS)&date=2026-07-25&search=1`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    // Sort bar (muncul jika ada hasil; boleh empty state)
    const hasSort = await page.locator('a:has-text("Termurah"), a:has-text("Tercepat"), a:has-text("Terpopuler")').count();
    if (hasSort > 0) {
      await expect(page.locator('a:has-text("Termurah")')).toBeVisible();
    }
    // Hover class di flight-card
    const cards = await page.locator('.flight-card.klook-hover-card').count();
    const plain = await page.locator('.flight-card').count();
    expect(cards).toBe(plain);
  });
});

test.describe('Redesign signature - Hotels (Agoda)', () => {

  test('search bar 4-field + sidebar filter Agoda + list view', async ({ page }) => {
    const resp = await page.goto(`${BASE}/hotels.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Agoda search fields
    await expect(page.locator('.traveloka-search-field input[name="city"]')).toHaveCount(1);
    await expect(page.locator('.traveloka-search-field input[name="checkin"]')).toHaveCount(1);
    await expect(page.locator('.traveloka-search-field input[name="checkout"]')).toHaveCount(1);
    await expect(page.locator('.traveloka-search-field select[name="guests"]')).toHaveCount(1);

    // Sidebar filter Agoda (label i18n: ID/EN)
    expect(body).toMatch(/Bintang|Stars|Harga per Malam|Price per Night|Fasilitas|Facilities|Urutkan|Sort/i);
    await expect(page.locator('#filterCollapse')).toBeVisible();
    await expect(page.locator('#fltBest')).toBeVisible();
    await expect(page.locator('#fltCancel')).toBeVisible();
    await expect(page.locator('#fltInstant')).toBeVisible();

    // List view (Agoda): row g-0 + h6.fw-semibold.mb-1 + price fs-5 + Pesan link
    const nameCount = await page.locator('h6.fw-semibold.mb-1').count();
    expect(nameCount).toBeGreaterThanOrEqual(10);
    const priceCount = await page.locator('span.fw-bold.text-primary.fs-5').count();
    expect(priceCount).toBeGreaterThanOrEqual(10);
    const pesanLink = page.locator('a.btn-primary:has-text("Pesan"), a.btn-primary:has-text("Messages"), a.btn-primary:has-text("Book")').first();
    const href = await pesanLink.getAttribute('href');
    expect(href).toContain('hotel-detail.php?slug=');
    expect(href).toContain('checkin=');
    expect(href).toContain('checkout=');
    expect(href).toContain('guests=');
  });
});

test.describe('Redesign signature - Ferries (Easybook)', () => {

  test('3-step search + tabel jadwal + badge Hemat + info e-ticket', async ({ page }) => {
    const resp = await page.goto(`${BASE}/ferries.php`, { waitUntil: 'load' });
    expect([200, null]).toContain(resp?.status());
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);

    // Legacy ferry fields
    await expect(page.locator('input[name="from"]')).toHaveCount(1);
    await expect(page.locator('input[name="to"]')).toHaveCount(1);
    await expect(page.locator('input[name="date"]')).toHaveCount(1);
    await expect(page.locator('select[name="passengers"]')).toHaveCount(1);
    await expect(page.locator('button[name="search"]')).toHaveCount(1);

    // Transport tabs
    await expect(page.locator('.booking-tab.active:has-text("Ferry")')).toBeVisible();

    // Table muncul setelah search
    await page.goto(`${BASE}/ferries.php?from=Merak&to=Bakauheni&search=1`, { waitUntil: 'load' });
    await expect(page.locator('.easybook-table')).toBeVisible();
    const body2 = await page.textContent('body');
    expect(body2).not.toMatch(PHP_ERROR);
    expect(body2).toMatch(/Perusahaan|Company|Kapal|Vessel|Berangkat|Depart|Tiba|Arrive|Harga|Price/i);
    expect(body2).toMatch(/ASDP|Merak|Bakauheni|Jadwal Ferry/i);
    expect(body2).toMatch(/Hemat|e-ticket|Sesampainya|pay at|Bayar di/i);
  });
});