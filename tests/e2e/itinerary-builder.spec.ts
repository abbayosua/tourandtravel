import { test, expect } from '@playwright/test';


const PHP_ERROR = /(Fatal error|Warning|Deprecated|Notice|Parse error|Uncaught|Undefined variable|trying to access array offset|error\s*:\s*<)/i;

async function login(page) {
  await page.goto('http://localhost/tourandtravel/login.php', { waitUntil: 'load' });
  await page.fill('input[name="email"]', 'mautau@mautau.com');
  await page.fill('input[name="password"]', 'mautau123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/index.php', { timeout: 10000 });
}

async function cleanupViaApi(request) {
  // buang itinerary sisa test (judul prefix E2E-ITIN)
  const res = await request.get('http://localhost/tourandtravel/itinerary-ajax.php?action=list');
  const data = await res.json().catch(() => ({ user_itineraries: [] }));
  for (const it of (data.user_itineraries || [])) {
    if ((it.title || '').startsWith('E2E-ITIN')) {
      await request.post('http://localhost/tourandtravel/itinerary-ajax.php', { form: { action: 'delete_itinerary', itinerary_id: String(it.id) } });
    }
  }
}

test.describe('Itinerary builder', () => {

  test.beforeEach(async ({ page, request }) => {
    await login(page);
    await cleanupViaApi(request);
  });

  test.afterEach(async ({ request }) => {
    await cleanupViaApi(request);
  });

  test('tombol Simpan ke Itinerary ada di tour-detail', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('button[data-bs-target="#itinBuilderModal"]')).toBeVisible();
  });

  test('guest melihat hint login di modal, bukan builder', async ({ page, browser }) => {
    const ctx = await browser.newContext();
    const guest = await ctx.newPage();
    await guest.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await guest.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await expect(guest.locator('#itinLoginHint')).toBeVisible();
    await expect(guest.locator('#itinMain')).toHaveClass(/d-none/);
    await ctx.close();
  });

  test('buat itinerary → tour otomatis jadi item hari 1', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await page.fill('#itinTitle', 'E2E-ITIN Trip');
    await page.click('#itinCreateBtn');
    await expect(page.locator('#itinDays')).toContainText(/Amazing New Zealand/i, { timeout: 5000 });
    await expect(page.locator('#itinDays')).toContainText(/Hari 1|Day 1/i);
  });

  test('tambah item ke hari terpilih', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await page.fill('#itinTitle', 'E2E-ITIN Trip');
    await page.click('#itinCreateBtn');
    await expect(page.locator('#itinDays .itin-day').first()).toBeVisible();
    await page.fill('#itinItemTitle', 'E2E Snorkeling');
    await page.click('#itinAddItemBtn');
    await expect(page.locator('#itinDays')).toContainText('E2E Snorkeling', { timeout: 5000 });
  });

  test('hapus item dari modal', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await page.fill('#itinTitle', 'E2E-ITIN Trip');
    await page.click('#itinCreateBtn');
    await page.fill('#itinItemTitle', 'E2E DelItem');
    await page.click('#itinAddItemBtn');
    const row = page.locator('.itin-day', { hasText: 'E2E DelItem' }).first();
    await expect(row).toBeVisible();
    await row.locator('.itin-del').last().click();
    await expect(page.locator('#itinDays')).not.toContainText('E2E DelItem', { timeout: 10000 });
  });

  test('persist: item masih ada setelah reload (DB-backed)', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await page.fill('#itinTitle', 'E2E-ITIN Persist');
    await page.click('#itinCreateBtn');
    await page.fill('#itinItemTitle', 'E2E PersistItem');
    await page.click('#itinAddItemBtn');
    await expect(page.locator('#itinDays')).toContainText('E2E PersistItem', { timeout: 5000 });
    await page.reload({ waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await expect(page.locator('#itinDays')).toContainText('E2E PersistItem', { timeout: 5000 });
  });

  test('my-itinerary.php menampilkan itinerary yang dibuat', async ({ page }) => {
    await page.goto('http://localhost/tourandtravel/tour-detail.php?slug=11d9n-amazing-new-zealand', { waitUntil: 'load' });
    await page.locator('button[data-bs-target="#itinBuilderModal"]').click();
    await page.fill('#itinTitle', 'E2E-ITIN ListPage');
    await page.click('#itinCreateBtn');
    await expect(page.locator('#itinDays')).toContainText(/Amazing New Zealand/i, { timeout: 5000 });
    await page.goto('http://localhost/tourandtravel/my-itinerary.php', { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    await expect(page.locator('.itin-card', { hasText: 'E2E-ITIN ListPage' }).first()).toBeVisible();
  });

  test('hapus itinerary dari my-itinerary.php', async ({ page }) => {
    // setup via API
    const res = await page.request.post('http://localhost/tourandtravel/itinerary-ajax.php', { form: { action: 'create_itinerary', title: 'E2E-ITIN Hapus' } });
    await page.goto('http://localhost/tourandtravel/my-itinerary.php', { waitUntil: 'load' });
    const card = page.locator('.itin-card', { hasText: 'E2E-ITIN Hapus' }).first();
    await expect(card).toBeVisible();
    page.once('dialog', d => d.accept());
    await card.locator('.itin-del-btn').click();
    await page.waitForURL('**/my-itinerary.php', { timeout: 5000 });
    await page.reload({ waitUntil: 'load' });
    await expect(page.locator('.itin-card', { hasText: 'E2E-ITIN Hapus' })).toHaveCount(0);
  });

});
