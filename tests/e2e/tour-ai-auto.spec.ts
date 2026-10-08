import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

/**
 * Fitur AI AUTO tambah paket tour (admin/tour-add.php + tour-edit.php).
 *
 * 1. Mode Manual (default): 3 input bahasa tampil, tombol AI hidden.
 * 2. Mode AI AUTO: hanya input bahasa sumber tampil + tombol pratinjau.
 * 3. Label mode tersedia di 3 bahasa (id/en/zh).
 * 4. Pratinjau AJAX mengisi kolom target via Atria (API asli).
 * 5. Simpan mode auto → tour tersimpan + kolom _en/_zh terisi (API asli).
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';
const E2E_TITLE = `E2E AI AUTO ${Date.now()}`;

function mysql(sql: string): string {
  // Jangan .trim(): tab leading = kolom kosong depan ikut terbuang. Kupas newline akhir saja.
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).replace(/\r?\n$/, '');
}

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.afterAll(() => {
  try { mysql(`DELETE FROM tours WHERE title LIKE 'E2E AI AUTO %'`); } catch { /* ignore */ }
});

test.describe('tour AI AUTO mode toggle', () => {
  test('manual default: 3 bahasa tampil, tombol AI hidden', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');

    await expect(page.locator('#modeManual')).toBeChecked();
    await expect(page.locator('#btnAiPreview')).toBeHidden();
    await expect(page.locator('#aiHint')).toBeHidden();
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="title_en"]')).toBeVisible();
    await expect(page.locator('input[name="title_zh"]')).toBeVisible();
  });

  test('auto: hanya input sumber tampil + tombol preview muncul', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');

    await page.check('#modeAuto');
    await expect(page.locator('#btnAiPreview')).toBeVisible();
    await expect(page.locator('#aiHint')).toBeVisible();
    // Sumber default = id
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="title_en"]')).toBeHidden();
    await expect(page.locator('input[name="title_zh"]')).toBeHidden();

    // Ganti sumber ke English → yang tampil tinggal _en
    await page.selectOption('#contentLangSelect', 'en');
    await expect(page.locator('input[name="title_en"]')).toBeVisible();
    await expect(page.locator('input[name="title"]')).toBeHidden();
    await expect(page.locator('input[name="title_zh"]')).toBeHidden();

    // Kolom sumber wajib diisi → placeholder fallback manual harus hilang
    await expect(page.locator('input[name="title_en"]')).toHaveAttribute('placeholder', '');
    await expect(page.locator('textarea[name="description_en"]')).toHaveAttribute('placeholder', '');

    // Kembali ke manual → placeholder fallback muncul lagi
    await page.check('#modeManual');
    await expect(page.locator('input[name="title_en"]')).not.toHaveAttribute('placeholder', '');
  });

  test('label mode tersedia di 3 bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string[]> = {
      id: ['Mode Input:', 'Manual (isi 3 bahasa sendiri)', 'AI AUTO (tulis 1 bahasa, auto translate)', 'Terjemahkan Otomatis (pratinjau)'],
      en: ['Input Mode:', 'Manual (fill in 3 languages yourself)', 'AI AUTO (write in 1 language, auto-translate)', 'Auto-Translate (preview)'],
      zh: ['输入模式：', '手动（自行填写3种语言）', 'AI自动（写1种语言，自动翻译）', '自动翻译（预览）'],
    };
    for (const [lang, labels] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/tour-add.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      for (const label of labels) {
        await expect(page.locator('body'), `${lang}: ${label}`).toContainText(label);
      }
    }
  });
});

test.describe('tour AI AUTO edge cases', () => {
  test('preview: field kosong → 400', async ({ page }) => {
    await adminLogin(page);
    const res = await page.request.post(`${BASE}/admin/ajax/tour-translate-ai.php`, {
      data: { source_lang: 'id', fields: {} },
    });
    expect(res.status()).toBe(400);
    const body = await res.json();
    expect(body.ok).toBe(false);
  });

  test('preview: source_lang invalid → 400', async ({ page }) => {
    await adminLogin(page);
    const res = await page.request.post(`${BASE}/admin/ajax/tour-translate-ai.php`, {
      data: { source_lang: 'xx', fields: { title: 'Halo' } },
    });
    expect(res.status()).toBe(400);
  });

  test('preview: teks raksasa ditolak cepat tanpa panggil AI', async ({ page }) => {
    await adminLogin(page);
    const big = 'x'.repeat(13000);
    const t0 = Date.now();
    const res = await page.request.post(`${BASE}/admin/ajax/tour-translate-ai.php`, {
      data: { source_lang: 'id', fields: { description: big } },
    });
    expect(res.status()).toBe(500);
    const body = await res.json();
    expect(body.ok).toBe(false);
    expect(body.error).toContain('terlalu panjang');
    expect(Date.now() - t0, 'ditolak lokal < 10 dtk').toBeLessThan(10000);
  });

  test('preview: GET → 405', async ({ page }) => {
    await adminLogin(page);
    const res = await page.request.get(`${BASE}/admin/ajax/tour-translate-ai.php`);
    expect(res.status()).toBe(405);
  });

  test('preview: tanpa login → redirect login', async ({ browser }) => {
    const ctx = await browser.newContext();
    const anon = await ctx.newPage();
    const res = await anon.request.post(`${BASE}/admin/ajax/tour-translate-ai.php`, {
      data: { source_lang: 'id', fields: { title: 'Halo' } },
      maxRedirects: 0,
    });
    expect(res.status()).toBe(302);
    expect(res.headers()['location'] || '').toContain('admin/login.php');
    await ctx.close();
  });

  test('preview: hanya field asing → 400 tanpa panggil AI', async ({ page }) => {
    await adminLogin(page);
    const t0 = Date.now();
    const res = await page.request.post(`${BASE}/admin/ajax/tour-translate-ai.php`, {
      data: { source_lang: 'id', fields: { price: '100', hacked: 'x' } },
    });
    expect(res.status()).toBe(400);
    expect(Date.now() - t0, 'ditolak lokal < 10 dtk').toBeLessThan(10000);
  });

  test('simpan auto sumber kosong → validasi, tak ada tour nyasar', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');
    await page.check('#modeAuto');
    await page.fill('input[name="price"]', '1000000');
    await page.click('button[type="submit"].btn-primary');
    await expect(page.locator('body')).toContainText('Judul tour harus diisi');
    const n = mysql(`SELECT COUNT(*) FROM tours WHERE price = 1000000 AND title = ''`);
    expect(n).toBe('0');
  });

  test('simpan auto minimal (tanpa deskripsi) → baris valid, NULL tak crash', async ({ page }) => {
    test.setTimeout(300_000);
    const title = `E2E MINIMAL ${Date.now()}`;
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');
    await page.check('#modeAuto');
    await page.fill('input[name="title"]', title);
    await page.fill('input[name="category"]', 'Jawa Timur');
    await page.fill('input[name="price"]', '999000');
    await page.uncheck('#isActive');
    await Promise.all([
      page.waitForURL('**/tour-edit.php?id=*&msg=added*', { timeout: 60_000 }),
      page.click('button[type="submit"].btn-primary'),
    ]);
    // Background hanya menerjemahkan yang terisi → polling title_en
    const deadline = Date.now() + 270_000;
    let en = '';
    for (;;) {
      en = mysql(`SELECT COALESCE(title_en,'') FROM tours WHERE title = '${title}' LIMIT 1`);
      if (en !== '' || Date.now() > deadline) break;
      await new Promise((r) => setTimeout(r, 5000));
    }
    expect(en.length, 'title_en terisi').toBeGreaterThan(0);
    const desc = mysql(`SELECT COALESCE(description_zh,'EMPTY') FROM tours WHERE title = '${title}' LIMIT 1`);
    expect(desc, 'deskripsi kosong tetap kosong, bukan sampah AI').toBe('EMPTY');
    mysql(`DELETE FROM tours WHERE title = '${title}'`);
  });
});

test.describe('tour AI AUTO translate (API asli)', () => {
  test('pratinjau AJAX mengisi EN+ZH', async ({ page }) => {
    test.setTimeout(120_000);
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');

    await page.check('#modeAuto');
    await page.fill('input[name="title"]', 'Paket Tour E2E Candi Borobudur');
    await page.fill('input[name="category"]', 'Jawa Tengah');
    await page.fill('textarea[name="description"]', 'Sunrise di candi terbesar dunia. Termasuk tiket masuk dan sarapan.');
    await page.click('#btnAiPreview');

    await expect(page.locator('#aiStatus')).toContainText('kolom terisi otomatis', { timeout: 90_000 });
    const en = await page.locator('input[name="title_en"]').inputValue();
    const zh = await page.locator('input[name="title_zh"]').inputValue();
    expect(en.trim().length, 'title_en terisi').toBeGreaterThan(0);
    expect(zh.trim().length, 'title_zh terisi').toBeGreaterThan(0);
  });

  test('simpan auto: tour tersimpan 3 bahasa + edit-auto tampil', async ({ page }) => {
    test.setTimeout(300_000);
    await adminLogin(page);
    await page.goto(`${BASE}/admin/tour-add.php?lang=id`);
    await page.waitForLoadState('domcontentloaded');

    await page.check('#modeAuto');
    await page.fill('input[name="title"]', E2E_TITLE);
    await page.fill('input[name="category"]', 'Jawa Tengah');
    await page.fill('textarea[name="description"]', 'Sunrise di candi terbesar dunia. Termasuk tiket masuk dan sarapan.');
    await page.fill('textarea[name="highlights"]', 'Sunrise Borobudur\nTiket masuk\nSarapan');
    await page.fill('textarea[name="includes"]', 'Tiket masuk\nTransport');
    await page.fill('input[name="price"]', '1500000');
    await page.uncheck('#isActive');

    await Promise.all([
      page.waitForURL('**/tour-edit.php?id=*&msg=added*', { timeout: 60_000 }),
      page.click('button[type="submit"].btn-primary'),
    ]);
    // AI jalan background setelah redirect → polling DB sampai terisi (maks ~3 mnt)
    let row: string[] = [];
    const deadline = Date.now() + 270_000;
    for (;;) {
      // COALESCE: mysql -B mencetak NULL sebagai string "NULL" — samarkan jadi ''
      row = mysql(
        `SELECT COALESCE(title_en,''), COALESCE(title_zh,''), COALESCE(category_en,''), COALESCE(category_zh,''), ` +
        `COALESCE(description_en,''), COALESCE(description_zh,''), COALESCE(highlights_zh,''), COALESCE(includes_zh,''), content_language ` +
        `FROM tours WHERE title = '${E2E_TITLE}' LIMIT 1`
      ).split('\t');
      const filled = row.slice(0, 8).every((v) => (v || '').trim().length > 0);
      if (filled || Date.now() > deadline) break;
      await new Promise((r) => setTimeout(r, 5000));
    }
    expect(row.length, 'baris tour ada').toBe(9);
    for (const [i, col] of ['title_en', 'title_zh', 'category_en', 'category_zh', 'description_en', 'description_zh', 'highlights_zh', 'includes_zh'].entries()) {
      expect(row[i]?.trim().length, `${col} terisi AI`).toBeGreaterThan(0);
    }
    expect(row[8], 'content_language = sumber').toBe('id');

    // Halaman edit tour yang sama juga punya mode AI AUTO
    const tourId = mysql(`SELECT id FROM tours WHERE title = '${E2E_TITLE}' LIMIT 1`);
    await page.goto(`${BASE}/admin/tour-edit.php?id=${tourId}&lang=id`);
    await page.waitForLoadState('domcontentloaded');
    await page.check('#modeAuto');
    await expect(page.locator('#btnAiPreview')).toBeVisible();
    await expect(page.locator('input[name="title"]')).toBeVisible();
    await expect(page.locator('input[name="title_en"]')).toBeHidden();

    mysql(`DELETE FROM tours WHERE title = '${E2E_TITLE}'`);
  });
});
