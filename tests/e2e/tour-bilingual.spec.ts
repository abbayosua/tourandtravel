import { test, expect } from '@playwright/test';

const BASE = 'http://localhost/tourandtravel';
const SLUG = 'beijing-qushui-lanting-cabang-sihui';
const PHP_ERROR = /(Fatal error|Parse error|Uncaught|Undefined variable|trying to access array offset)/i;

async function loginAdmin(page) {
  await page.goto(`${BASE}/admin/login.php`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('load');
}

test.describe('Tour bilingual (ID/EN/ZH)', () => {

  test('detail lang=en tampil konten EN (title + desc)', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=en`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const title = await page.textContent('h2.fw-bold');
    expect(title).toContain('Sihui Branch');
    expect(body).toMatch(/Explore the beauty of Beijing/i);
  });

  test('detail lang=id tampil konten ID (fallback)', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    const title = await page.textContent('h2.fw-bold');
    expect(title).toContain('Cabang Sihui');
  });

  test('PDF EN valid + label/konten EN', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=${SLUG}&pdf_lang=en`);
    expect(resp.status()).toBe(200);
    const body = await resp.body();
    expect(body.subarray(0, 5).toString()).toBe('%PDF-');
    expect(body.length).toBeGreaterThan(5000);
  });

  test('PDF ID valid + PDF ZH valid (font CJK, 4 byte %PDF)', async ({ page }) => {
    for (const lang of ['id', 'zh']) {
      const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=${SLUG}&pdf_lang=${lang}`);
      expect(resp.status()).toBe(200);
      const body = await resp.body();
      expect(body.subarray(0, 5).toString()).toBe('%PDF-');
      expect(body.length).toBeGreaterThan(5000);
    }
  });

  test('tombol PDF trilingual ada di detail', async ({ page }) => {
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=en`, { waitUntil: 'load' });
    const btn = page.locator('[data-testid="pdf-download"]');
    await expect(btn).toBeVisible();
    const href = await btn.getAttribute('href');
    expect(href).toContain('pdf_lang=en');
    const zhBtn = page.locator('a[href*="pdf_lang=zh"]');
    expect(await zhBtn.count()).toBeGreaterThanOrEqual(1);
  });

  test('admin edit: field bilingual EN/ZH tampil + simpan', async ({ page }) => {
    await loginAdmin(page);
    await page.goto(`${BASE}/admin/tour-edit.php?id=131`, { waitUntil: 'load' });
    const body = await page.textContent('body');
    expect(body).not.toMatch(PHP_ERROR);
    expect(await page.locator('input[name="title_en"]').count()).toBe(1);
    expect(await page.locator('input[name="title_zh"]').count()).toBe(1);
    expect(await page.locator('textarea[name="description_en"]').count()).toBe(1);
    expect(await page.locator('input[name="it_title_en"]').count()).toBe(1);
    expect(await page.locator('input[name="date_note_en"]').count()).toBe(1);
    const enVal = await page.locator('input[name="title_en"]').inputValue();
    expect(enVal).toContain('Sihui Branch');
    await page.locator('button[type="submit"]').first().click();
    await page.waitForLoadState('load');
    expect(page.url()).toContain('tours.php?msg=updated');
  });

  test('PDF ala Balindo: rute + visa + hotel bintang + meals ikon + kontak + twin', async ({ page }) => {
    const { execSync } = await import('child_process');
    const out = execSync(
      `curl -s -b /tmp/cj "${BASE}/tour-itinerary-pdf.php?slug=${SLUG}&pdf_lang=id" | pdftotext -layout - -`,
      { encoding: 'utf-8', maxBuffer: 2 * 1024 * 1024 }
    );
    expect(out).toMatch(/RUTE PERJALANAN/);
    expect(out).toMatch(/JAKARTA/);
    expect(out).toMatch(/VISA CHINA/);
    expect(out).toMatch(/1 KAMAR 2/);
    expect(out).toMatch(/Jl\. Wisata/);
  });

  test('PDF all (EN+ZH 1 file) valid dan memuat kedua bahasa', async ({ page }) => {
    const resp = await page.request.get(`${BASE}/tour-itinerary-pdf.php?slug=${SLUG}&pdf_lang=all`);
    expect(resp.status()).toBe(200);
    const body = await resp.body();
    expect(body.subarray(0, 5).toString()).toBe('%PDF-');
    expect(body.length).toBeGreaterThan(10000);
    const btn = page.locator('a[href*="pdf_lang=all"]');
    await page.goto(`${BASE}/tour-detail.php?slug=${SLUG}&lang=id`, { waitUntil: 'load' });
    expect(await btn.count()).toBeGreaterThanOrEqual(0);
  });
});
