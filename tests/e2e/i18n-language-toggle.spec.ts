import { test, expect, Page } from '@playwright/test';
import { execFileSync } from 'child_process';

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
      'kuota', 'seluruh', 'khusus', 'pengiriman', 'selama',
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
  'pelni.php',
  'track.php',
  'nusatrip-book.php',
  'ferry-booking.php',
  'pelni-booking.php',
  // Halaman detail (konten DB + widget harga/alert)
  'tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town',
  'tour-detail.php?slug=beijing-qushui-lanting-cabang-sihui',
  'hotel-detail.php?slug=grand-hyatt-bali',
  'attraction-detail.php?slug=tiket-masuk-taman-mini-indonesia-indah',
  'transfer-detail.php?slug=bandara-juanda-ke-pusat-kota-surabaya',
  'rental-car-detail.php?slug=toyota-avanza-jakarta',
  'esim-detail.php?slug=esim-bali-10gb',
  'train-detail.php?slug=argo-bromo-anggrek',
  'blog-detail.php?slug=tips-memilih-paket-tour-keluarga',
];

/** Tambahkan parameter bahasa tanpa merusak query string yang sudah ada. */
function withLang(path: string, lang: string): string {
  return `${BASE}/${path}${path.includes('?') ? '&' : '?'}lang=${lang}`;
}

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

  // Format angka/tanggal sisi browser harus ikut bahasa aktif (bukan id-ID hardcoded).
  test('locale JS mengikuti bahasa aktif untuk format angka/tanggal', async ({ page }) => {
    const expected: Record<string, string> = { id: 'id-ID', en: 'en-US', zh: 'zh-CN' };
    for (const [lang, locale] of Object.entries(expected)) {
      await page.goto(`${BASE}/tours.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const got = await page.evaluate(() => (window as any).I18N && (window as any).I18N.locale);
      expect(got, `I18N.locale untuk ${lang}`).toBe(locale);
    }
  });

  // Pesan interaktif (toast/error) di tour-detail harus ikut bahasa aktif.
  test('pesan promo tour-detail mengikuti bahasa', async ({ page }) => {
    const slug = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
    const expected: Record<string, string> = { en: 'Enter a promo code', zh: '输入优惠码' };
    for (const [lang, phrase] of Object.entries(expected)) {
      await page.goto(`${BASE}/tour-detail.php?slug=${slug}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(400);
      await page.locator('.klook-promo-btn').first().click();
      await expect(page.locator('#promoResultTour')).toContainText(phrase);
      await expect(page.locator('#promoResultTour')).not.toContainText('Masukkan kode promo');
    }
  });

  // Tombol "Book" (key 'Pesan') harus diterjemahkan, bukan jadi "Messages".
  test('tombol booking memakai label bahasa aktif', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Book', zh: '预订' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/attractions.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const btn = page.locator('a[href*="attraction-detail.php"]').first();
      await expect(btn).toContainText(label);
    }
  });

  // Tombol "Search" (key 'Cari') harus diterjemahkan, bukan "Go".
  test('tombol pencarian memakai label bahasa aktif', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Search', zh: '搜索' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/track.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('button[type="submit"]').first()).toContainText(label);
    }
  });

  for (const lang of ['en', 'zh']) {
    test(`tidak ada sisa teks Indonesia saat bahasa=${lang}`, async ({ page }) => {
      test.setTimeout(180_000);
      const failures: string[] = [];

      for (const path of PAGES) {
        await page.goto(withLang(path, 'id'));
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const idStrings = await visibleStrings(page);

        await page.goto(withLang(path, lang));
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const otherStrings = await visibleStrings(page);

        const leaked = leakedStrings(idStrings, otherStrings);
        if (leaked.length) failures.push(`${path}:\n  - ${leaked.join('\n  - ')}`);
      }

      expect(failures, `Teks Indonesia bocor di bahasa ${lang}:\n${failures.join('\n')}`).toEqual([]);
    });
  }
});

// ---- Halaman akun (perlu login) ----
const AUTH_EMAIL = 'i18ncheck@t.local';
const AUTH_PASS = 'tmpcheck123';

// Halaman yang butuh sesi login.
const AUTH_PAGES = [
  'my-bookings.php',
  'profile.php',
  'wallet.php',
  'my-points.php',
  'wishlist.php',
  'referral.php',
  'my-coupons.php',
  'my-itinerary.php',
  'notifications.php',
  'my-alerts.php',
  'my-profiles.php',
];

function mysql(sql: string): string {
  return execFileSync('mysql', ['-uroot', 'tourandtravel', '-N', '-B', '-e', sql], { encoding: 'utf8' }).trim();
}

async function login(page: Page) {
  await page.goto(`${BASE}/login.php`);
  await page.fill('input[name="email"]', AUTH_EMAIL);
  await page.fill('input[name="password"]', AUTH_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.describe('i18n halaman akun', () => {
  test.beforeAll(() => {
    const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(AUTH_PASS)}, PASSWORD_DEFAULT);`], {
      encoding: 'utf8',
    }).trim();
    mysql(
      `INSERT INTO users (name, email, password_hash, role) VALUES ('I18N Checker', '${AUTH_EMAIL}', '${hash}', 'user') ` +
        `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)`
    );
  });

  test.afterAll(() => {
    try {
      mysql(`DELETE FROM users WHERE email = '${AUTH_EMAIL}'`);
    } catch {
      // abaikan: user mungkin masih direferensikan baris lain
    }
  });

  for (const lang of ['en', 'zh']) {
    test(`halaman akun bersih dari teks Indonesia saat bahasa=${lang}`, async ({ page }) => {
      test.setTimeout(180_000);
      await login(page);
      const failures: string[] = [];

      for (const path of AUTH_PAGES) {
        await page.goto(withLang(path, 'id'));
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const idStrings = await visibleStrings(page);

        await page.goto(withLang(path, lang));
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const otherStrings = await visibleStrings(page);

        const leaked = leakedStrings(idStrings, otherStrings);
        if (leaked.length) failures.push(`${path}:\n  - ${leaked.join('\n  - ')}`);
      }

      expect(failures, `Teks Indonesia bocor di bahasa ${lang}:\n${failures.join('\n')}`).toEqual([]);
    });
  }
});

// ---- Admin panel (perlu login admin) ----
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'tmpcheck123';

async function adminLogin(page: Page) {
  await page.goto(`${BASE}/admin/login.php`);
  await page.fill('input[name="username"]', ADMIN_USER);
  await page.fill('input[name="password"]', ADMIN_PASS);
  await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
  await page.waitForLoadState('domcontentloaded');
}

test.describe('i18n admin panel', () => {
  test('sidebar admin mengikuti bahasa aktif', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Ulasan', en: 'Reviews', zh: '评价' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/dashboard.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('#adminSidebar')).toContainText(label);
    }
    // Tidak menyisakan label ID pada versi EN.
    await page.goto(`${BASE}/admin/dashboard.php?lang=en`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('#adminSidebar')).not.toContainText('Ulasan');
    await expect(page.locator('#adminSidebar')).not.toContainText('Lihat Website');
  });
});

test.describe('i18n admin laporan', () => {
  test('label laporan akuntansi mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Total Pendapatan', en: 'Total Revenue', zh: '总收入' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/accounting.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin dashboard', () => {
  test('label dashboard mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tren Pendapatan', en: 'Revenue Trend', zh: '收入趋势' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/dashboard.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin bookings', () => {
  test('label booking admin mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Ubah Status', en: 'Change Status', zh: '更改状态' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/bookings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin editor tour', () => {
  test('label editor tour mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Upload Galeri', en: 'Upload Gallery', zh: '上传相册' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/tour-edit.php?id=63&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin pengeluaran', () => {
  test('label pengeluaran akuntansi mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Daftar Pengeluaran', en: 'Expense List', zh: '支出列表' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/accounting.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin payments', () => {
  test('label pengaturan pembayaran mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Pengaturan Tripay', en: 'Tripay Settings', zh: 'Tripay 设置' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/payments.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n hotel booking', () => {
  const CI = '2027-06-01';
  const CO = '2027-06-03';
  const cleanup = () => { try { mysql(`DELETE FROM hotel_bookings WHERE hotel_id = 1 AND checkin = '${CI}' AND name = 'E2E I18N'`); } catch { /* ignore */ } };

  test.afterAll(cleanup);

  test('pesan sukses booking hotel mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Booking successful!', zh: '预订成功！' };
    for (const [lang, phrase] of Object.entries(expected)) {
      cleanup();
      await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&checkin=${CI}&checkout=${CO}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await page.locator('#hotelBookingName').fill('E2E I18N');
      await page.locator('#hotelBookingPhone').fill('08123456789');
      await Promise.all([page.waitForLoadState('domcontentloaded'), page.locator('#bookingSubmitBtn').click()]);
      await expect(page.locator('.alert-success')).toContainText(phrase);
      await expect(page.locator('.alert-success')).not.toContainText('Booking berhasil');
    }
  });
});

test.describe('i18n admin sales report', () => {
  test('label sales report mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Detail Transaksi', en: 'Transaction Details', zh: '交易明细' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/sales-report.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin hotel api settings', () => {
  test('label pengaturan hotel API mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Sumber utama', en: 'Primary source', zh: '主要来源' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/hotel-api-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin push notifications', () => {
  test('label push notification mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Kirim Notifikasi', en: 'Send Notification', zh: '发送通知' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/push-notifications.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin flash sales', () => {
  test('label flash sale mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah Flash Sale', en: 'Add Flash Sale', zh: '添加限时特惠' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/flash-sales.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin email log', () => {
  test('label log email mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Kepada', en: 'To', zh: '收件人' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/email-log.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin WA settings', () => {
  test('label pengaturan WhatsApp mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Nama Tour', en: 'Tour Name', zh: '旅游名称' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/wa-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});
