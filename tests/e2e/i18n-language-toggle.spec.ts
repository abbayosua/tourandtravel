import { test, expect, Page } from '@playwright/test';

/**
 * Regresi i18n: setiap halaman publik harus benar-benar berganti bahasa.
 *
 * Cara kerja:
 *  1. Buka halaman dalam bahasa Indonesia (default) dan kumpulkan semua string
 *     yang terlihat (teks, placeholder, title, aria-label).
 *  2. Ganti bahasa ke EN / ZH lewat kontrol switcher di navbar.
 *  3. Kumpulkan lagi stringnya. String yang TETAP SAMA di kedua bahasa dan
 *     mengandung kata penanda bahasa Indonesia = terjemahan bocor (hardcoded
 *     atau key yang belum diterjemahkan).
 *
 * Ini menangkap baik teks hardcoded maupun key t() yang jatuh ke fallback ID.
 */

const BASE = process.env.E2E_BASE_URL || 'http://localhost/tourandtravel';

// Kata penanda khas bahasa Indonesia (di luar kata serapan yang juga dipakai EN).
const ID_MARKERS = new RegExp(
  '\\b(' +
    [
      'yang', 'untuk', 'dengan', 'tidak', 'belum', 'sudah', 'akan', 'dari', 'atau', 'juga',
      'bisa', 'dapat', 'anda', 'kami', 'kita', 'pada', 'oleh', 'agar', 'saat', 'setiap',
      'lebih', 'paling', 'cari', 'pilih', 'lihat', 'buka', 'tutup', 'masuk', 'daftar',
      'tanggal', 'jumlah', 'orang', 'anak', 'dewasa', 'malam', 'tiket', 'kursi', 'kamar',
      'pesawat', 'kereta', 'kapal', 'perjalanan', 'wisata', 'destinasi', 'ulasan', 'bayar',
      'pembayaran', 'berhasil', 'gagal', 'minimal', 'wajib', 'harus', 'silakan', 'mohon',
      'terima', 'maaf', 'harga', 'paket', 'pesan', 'nama', 'kota', 'bandara', 'penumpang',
      'penerbangan', 'pengiriman', 'saldo', 'poin', 'fasilitas', 'ketersediaan',
      'keberangkatan', 'jadwal', 'stasiun', 'pelabuhan', 'rute', 'bantuan', 'lacak',
      'profil', 'notifikasi', 'tentang', 'ketentuan', 'kebijakan', 'hubungi', 'beranda',
      'reseller', 'favorit', 'mitra', 'terpercaya', 'diskon', 'segera', 'segala',
    ].join('|') +
    ')\\b',
  'i'
);

// Halaman publik yang harus bersih di semua bahasa (lihat database/migrate-translations-i18n-gaps.php).
const PAGES = [
  'index.php',
  'tours.php',
  'hotels.php',
  'flights.php',
  'ferries.php',
  'trains.php',
  'faq.php',
  'about.php',
  'terms.php',
  'privacy.php',
  'refund-policy.php',
  'login.php',
  'register.php',
  'attractions.php',
  'transfers.php',
  'rental-cars.php',
  'esim.php',
  'blog.php',
  'destinasi.php',
];

/** Semua string user-facing yang tampil di halaman (teks + placeholder/title/aria-label). */
async function visibleStrings(page: Page): Promise<string[]> {
  return page.evaluate(() => {
    const out = new Set<string>();
    const norm = (s: string) => s.replace(/\s+/g, ' ').trim();

    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let node: Node | null;
    while ((node = walker.nextNode())) {
      const el = node.parentElement;
      if (!el) continue;
      const tag = el.tagName;
      if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') continue;
      const t = norm(node.textContent || '');
      if (t.length >= 4) out.add(t);
    }

    document.querySelectorAll('[placeholder],[title],[aria-label]').forEach((el) => {
      for (const attr of ['placeholder', 'title', 'aria-label']) {
        const v = norm(el.getAttribute(attr) || '');
        if (v.length >= 4) out.add(v);
      }
    });

    return Array.from(out);
  });
}

/** Ganti bahasa lewat kontrol switcher di navbar (bukan sekadar ubah URL). */
async function switchLanguage(page: Page, lang: string) {
  await page.click('a[aria-label="Language"]');
  const option = page.locator(`.dropdown-menu a[href*="lang=${lang}"]`).first();
  await option.waitFor({ state: 'visible' });
  await Promise.all([page.waitForLoadState('domcontentloaded'), option.click()]);
  await page.waitForLoadState('networkidle');
}

function leakedStrings(idStrings: string[], otherStrings: string[]): string[] {
  const other = new Set(otherStrings);
  return idStrings
    .filter((s) => other.has(s))
    .filter((s) => ID_MARKERS.test(s))
    .filter((s) => !/^[\d\s.,%+\-–—/():]+$/.test(s))
    .sort();
}

test.describe('i18n language toggle', () => {
  test('switcher mengganti bahasa, <html lang>, dan nav lalu persisten', async ({ page }) => {
    await page.goto(`${BASE}/?lang=id`);
    await expect(page.locator('html')).toHaveAttribute('lang', 'id');

    await switchLanguage(page, 'en');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.locator('.voyage-tabs')).toContainText('Flight');
    await expect(page.locator('.voyage-tabs')).not.toContainText('Pesawat');

    // Persisten setelah reload (session + cookie).
    await page.reload();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');

    await switchLanguage(page, 'zh');
    await expect(page.locator('html')).toHaveAttribute('lang', 'zh');
    await expect(page.locator('.voyage-tabs')).toContainText('机票');
    await expect(page.locator('.voyage-tabs')).not.toContainText('Pesawat');
  });

  for (const lang of ['en', 'zh']) {
    test(`tidak ada sisa teks Indonesia saat bahasa=${lang}`, async ({ page }) => {
      const failures: string[] = [];

      for (const path of PAGES) {
        await page.goto(`${BASE}/${path}?lang=id`);
        await page.waitForLoadState('networkidle');
        const idStrings = await visibleStrings(page);

        await page.goto(`${BASE}/${path}?lang=${lang}`);
        await page.waitForLoadState('networkidle');
        const otherStrings = await visibleStrings(page);

        const leaked = leakedStrings(idStrings, otherStrings);
        if (leaked.length) failures.push(`${path}:\n  - ${leaked.join('\n  - ')}`);
      }

      expect(failures, `Teks Indonesia bocor di bahasa ${lang}:\n${failures.join('\n')}`).toEqual([]);
    });
  }
});
