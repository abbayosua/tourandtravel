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
      'kuota', 'seluruh', 'khusus', 'pengiriman', 'selama', 'dimuat', 'peta', 'ulasan', 'penumpang',
      'kembali', 'lanjut', 'simpan', 'kirim', 'jadwal', 'stasiun', 'pelabuhan',
      'penting', 'catatan', 'kupon', 'diterjemahkan', 'bayar', 'kartu', 'tersisa', 'menyetujui', 'syarat', 'afiliasi',
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
  'forgot-password.php',
  'reset-password.php',
  'attractions.php',
  'transfers.php',
  'rental-cars.php',
  'esim.php',
  'blog.php',
  'destinasi.php',
  'pelni.php',
  'track.php',
  'collection.php?slug=best-seller',
  'booking-success.php?code=E2EI18N1',
  'nusatrip-book.php',
  'ferry-booking.php?company=TestFerry&from=A&to=B&date=2026-12-01&time=08:00&price=100000&passengers=1&vessel=V1',

  'pelni-booking.php',
  // Halaman detail (konten DB + widget harga/alert)
  'tour-detail.php?slug=8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town',
  'tour-detail.php?slug=beijing-qushui-lanting-cabang-sihui',
  'hotel-detail.php?slug=grand-hyatt-bali',
  'hotel-detail.php?slug=four-seasons-resort-ubud',
  'attraction-detail.php?slug=tiket-masuk-taman-mini-indonesia-indah',
  'attraction-detail.php?slug=candi-borobudur-sunrise-ticket',
  'transfer-detail.php?slug=bandara-juanda-ke-pusat-kota-surabaya',
  'rental-car-detail.php?slug=toyota-avanza-jakarta',
  'esim-detail.php?slug=esim-bali-10gb',
  'train-detail.php?slug=argo-bromo-anggrek',
  'flight-detail.php?schedule_id=1',
  'blog-detail.php?slug=tips-memilih-paket-tour-keluarga',
  'blog-detail.php?slug=panduan-beijing-zh',
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
  test.beforeAll(() => {
    // Booking deterministik untuk menyapu halaman booking-success.php.
    try {
      mysql(`DELETE FROM bookings WHERE booking_code = 'E2EI18N1'`);
      const td = mysql(`SELECT id, tour_id FROM tour_dates WHERE is_active = 1 ORDER BY id LIMIT 1`).split('	');
      mysql(
        `INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
          `VALUES ('E2EI18N1', ${td[1]}, ${td[0]}, 'E2E I18N', 'e2e@t.local', '0800000000', 1, 100000, 'pending')`
      );
    } catch { /* ignore */ }
  });
  test.afterAll(() => {
    try { mysql(`DELETE FROM bookings WHERE booking_code = 'E2EI18N1'`); } catch { /* ignore */ }
  });

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
      test.setTimeout(300_000);
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
      test.setTimeout(300_000);
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
      await page.locator('#roomSelect').selectOption({ index: 1 });
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

  // Opsi sumber 'Auto' dulu hardcoded.
  test('opsi sumber hotel mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/hotel-api-settings.php?lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    const select = page.locator('select[name="src"]');
    await expect(select).toContainText('自动');
    await expect(select).not.toContainText('Auto');
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

  // 'Mulai' = waktu mulai flash sale harus "Start", bukan "From" (key lama dipakai ganda).
  test('header waktu flash sale memakai label start/end', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Mulai', en: 'Start', zh: '开始' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/flash-sales.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('thead')).toContainText(label);
      if (lang !== 'id') await expect(page.locator('thead')).not.toContainText('Mulai');
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

test.describe('i18n admin nav menus', () => {
  test('label menu navigasi mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah Menu', en: 'Add Menu', zh: '添加菜单' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/nav-menus.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });

  // Header tabel (URL/Tab/Menu) dulu hardcoded.
  test('header tabel menu navigasi mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    await page.goto(`${BASE}/admin/nav-menus.php?lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    const thead = page.locator('table thead').first();
    await expect(thead).toContainText('标签页');
    await expect(thead).toContainText('菜单');
    await expect(thead).not.toContainText('Tab');
  });
});

test.describe('i18n admin brand settings', () => {
  test('label brand settings mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Pratinjau', en: 'Preview', zh: '预览' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/brand-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin live chat settings', () => {
  test('label pengaturan live chat mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = {
      id: 'Widget tidak dirender sampai Property ID diisi.',
      en: 'The widget is not rendered until the Property ID is filled in.',
      zh: '在填写 Property ID 之前不会渲染该组件。',
    };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/chat-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });

  // Help text tawk.to dulu punya nilai en berbahasa Indonesia.
  test('help text tawk.to mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = {
      en: 'From the tawk.to dashboard',
      zh: '来自 tawk.to',
    };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/chat-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
      await expect(page.locator('body')).not.toContainText('Dari dashboard tawk.to');
    }
  });
});

test.describe('i18n admin reseller pricing', () => {
  test('label harga reseller mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah Harga Reseller', en: 'Add Reseller Price', zh: '添加分销价' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/reseller-pricing.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin price alerts', () => {
  test('label price alerts mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Jalankan Pengecekan', en: 'Run Check', zh: '运行检查' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/price-alerts.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin reseller topups', () => {
  test('label topup reseller mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Semua Status', en: 'All Statuses', zh: '所有状态' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/reseller-topups.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin corporate rates', () => {
  test('label corporate rates mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah Perusahaan', en: 'Add Company', zh: '添加公司' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/corporate-rates.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n reseller pages', () => {
  const EMAIL = 'i18nreseller@t.local';
  const PASS = 'tmpcheck123';

  test.beforeAll(() => {
    const hash = execFileSync('php', ['-r', `echo password_hash(${JSON.stringify(PASS)}, PASSWORD_DEFAULT);`], { encoding: 'utf8' }).trim();
    mysql(
      `INSERT INTO users (name, email, password_hash, role) VALUES ('I18N Reseller', '${EMAIL}', '${hash}', 'reseller') ` +
        `ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'reseller'`
    );
    // Reseller price agar reseller-booking.php tidak redirect ke tour-detail.
    try {
      mysql(`DELETE FROM reseller_tour_prices WHERE tour_id = 63`);
      mysql(`INSERT INTO reseller_tour_prices (tour_id, reseller_price, min_pax, active) VALUES (63, 9000000, 2, 1)`);
    } catch { /* ignore */ }
  });
  test.afterAll(() => {
    try { mysql(`DELETE FROM users WHERE email = '${EMAIL}'`); } catch { /* ignore */ }
    try { mysql(`DELETE FROM reseller_tour_prices WHERE tour_id = 63`); } catch { /* ignore */ }
  });

  test('label reseller mengikuti bahasa', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASS);
    await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
    const expected: Record<string, string> = { id: 'Dashboard Reseller', en: 'Reseller Dashboard', zh: '分销商仪表板' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/reseller-dashboard.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });

  test('label booking reseller mengikuti bahasa', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASS);
    await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
    const expected: Record<string, string> = { id: 'Booking Reseller', en: 'Reseller Booking', zh: '分销商订单' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/reseller-booking.php?tour_id=63&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });

  test('label topup reseller mengikuti bahasa', async ({ page }) => {
    await page.goto(`${BASE}/login.php`);
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASS);
    await Promise.all([page.waitForLoadState('domcontentloaded'), page.click('button[type="submit"]')]);
    const expected: Record<string, string> = { id: 'Topup Saldo Reseller', en: 'Reseller Balance Topup', zh: '分销商余额充值' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/reseller-topup.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n booking labels', () => {
  test('label ulasan & info tour-detail mengikuti bahasa', async ({ page }) => {
    const slug = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
    const expected: Record<string, string> = { id: 'Ulasan', en: 'Reviews', zh: '评价' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/tour-detail.php?slug=${slug}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin tours list', () => {
  test('label daftar tour mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah Tour', en: 'Add Tour', zh: '添加旅行团' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/tours.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin WA connection', () => {
  test('pesan koneksi WhatsApp mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Belum terhubung', en: 'Not connected', zh: '尚未连接' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/wa-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      // Pesan dirender via JS template, jadi cek sumber HTML.
      expect(await page.content()).toContain(label);
    }
  });
});

test.describe('i18n JS alerts', () => {
  test('pesan alert JS mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Google login failed', zh: 'Google 登录失败' };
    for (const [lang, val] of Object.entries(expected)) {
      await page.goto(`${BASE}/login.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const v = await page.evaluate(() => (window as any).I18N?.strings?.['Login Google gagal']);
      expect(v, `I18N string untuk ${lang}`).toBe(val);
    }
  });
});

test.describe('i18n admin Singapay settings', () => {
  test('label Singapay mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Pengaturan Singapay (Virtual Account)', en: 'Singapay Settings (Virtual Account)', zh: 'Singapay 设置（虚拟账户）' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/payments.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      expect(await page.content()).toContain(label);
    }
  });

  // Label field Singapay dulu hardcoded (bukan t()).
  test('label field Singapay mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string[]> = {
      en: ['API Key', 'Client ID', 'Client Secret', 'Account ID'],
      zh: ['API 密钥', '客户端 ID', '客户端密钥', '账户 ID'],
    };
    for (const [lang, labels] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/payments.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const html = await page.content();
      for (const l of labels) expect(html).toContain(l);
    }
    await page.goto(`${BASE}/admin/payments.php?lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    expect(await page.content()).not.toContain('>API Key<');
  });
});

test.describe('i18n hotel content zh', () => {
  test('konten hotel tampil dalam bahasa aktif', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=the-ritz-carlton-jakarta&lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText('雅加达丽思卡尔顿酒店');
    await expect(page.locator('body')).not.toContainText('The Ritz-Carlton Jakarta');
    await page.goto(`${BASE}/hotel-detail.php?slug=the-ritz-carlton-jakarta&lang=en`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText('The Ritz-Carlton Jakarta, Mega Kuningan');
  });

  // Nama tipe kamar (hotel_rooms.name_zh) dulu kosong → tampil Inggris di zh.
  test('nama tipe kamar mengikuti bahasa', async ({ page }) => {
    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText('高级大床房');
    await expect(page.locator('body')).not.toContainText('Superior Double');

    await page.goto(`${BASE}/hotel-detail.php?slug=grand-hyatt-bali&lang=en`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText('Superior Double');
  });
});

test.describe('i18n admin hero slide edit', () => {
  test('label edit slide mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = {
      id: 'JPG/PNG/WebP, maks 2MB. Rekomendasi 1920px lebar.',
      en: 'JPG/PNG/WebP, max 2MB. Recommended 1920px width.',
      zh: 'JPG/PNG/WebP，最大 2MB。建议宽度 1920px。',
    };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/hero-slide-edit.php?id=6&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
    }
  });
});

test.describe('i18n admin reviews', () => {
  test('judul halaman ulasan mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Ulasan', en: 'Reviews', zh: '评价' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/reviews.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      expect(await page.content()).toContain(label);
    }
  });
});

test.describe('i18n admin attraction edit', () => {
  test('tombol simpan/tambah mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { id: 'Tambah', en: 'Add', zh: '添加' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/attraction-edit.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('button[type="submit"]').last()).toContainText(label);
    }
  });
});

test.describe('i18n admin login page', () => {
  test('halaman login admin mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Admin Login', zh: '管理员登录' };
    for (const [lang, title] of Object.entries(expected)) {
      await page.goto(`${BASE}/admin/login.php?lang=${lang}`);
      await expect(page.locator('html')).toHaveAttribute('lang', lang);
      await expect(page).toHaveTitle(new RegExp(title));
    }
  });
});

test.describe('i18n admin html lang', () => {
  test('halaman admin mendeklarasikan bahasa aktif', async ({ page }) => {
    await adminLogin(page);
    for (const lang of ['id', 'en', 'zh']) {
      for (const p of ['dashboard.php', 'bookings.php', 'tours.php', 'payments.php', 'accounting.php']) {
        await page.goto(`${BASE}/admin/${p}?lang=${lang}`);
        await expect(page.locator('html'), `${p} @ ${lang}`).toHaveAttribute('lang', lang);
      }
    }
  });
});

test.describe('i18n tour detail notes', () => {
  test('judul catatan penting mengikuti bahasa', async ({ page }) => {
    const slug = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
    const expected: Record<string, string> = { en: 'Important Notes', zh: '重要须知' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/tour-detail.php?slug=${slug}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
      await expect(page.locator('body')).not.toContainText('Catatan Penting');
    }
  });

  // Akomodasi itinerary (itineraries.accommodation_zh) dulu kosong → tampil Inggris di zh.
  test('akomodasi itinerary mengikuti bahasa', async ({ page }) => {
    const slug = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
    await page.goto(`${BASE}/tour-detail.php?slug=${slug}&lang=zh`);
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('body')).toContainText('4-5星酒店（上海）');
    await expect(page.locator('body')).not.toContainText('Hotel 4-5* (Shanghai)');
  });
});
test.describe('i18n itinerary builder tour-detail', () => {
  // Tombol "Buat Itinerary" + placeholder aktivitas dulu punya nilai en identity (tetap ID).
  test('label itinerary builder mengikuti bahasa', async ({ page }) => {
    const slug = '8d7n-shanghai-jiangnan-highlights-ink-wash-jiangnan-wuzhen-water-town';
    const expected: Record<string, { btn: string; ph: string }> = {
      en: { btn: 'Create Itinerary', ph: 'Activity, e.g.' },
      zh: { btn: '创建行程', ph: '活动，例如' },
    };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/tour-detail.php?slug=${slug}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('#itinCreateBtn')).toContainText(label.btn);
      await expect(page.locator('#itinItemTitle')).toHaveAttribute('placeholder', new RegExp(label.ph));
      await expect(page.locator('body')).not.toContainText('Buat Itinerary');
    }
  });
});
test.describe('i18n admin mata uang & promo', () => {
  // Label admin ini dulu punya nilai en identity (tetap ID) sehingga EN bocor.
  test('label mata uang & promo mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const currency: Record<string, string> = { en: 'Default Currency', zh: '默认货币' };
    const promo: Record<string, string> = { en: 'Min. Purchase', zh: '最低购买' };
    for (const lang of ['en', 'zh']) {
      await page.goto(`${BASE}/admin/currency-settings.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(currency[lang]);
      await expect(page.locator('body')).not.toContainText('Mata Uang Default');

      await page.goto(`${BASE}/admin/promo-codes.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(promo[lang]);
      await expect(page.locator('body')).not.toContainText('Min. Pembelian');
    }
  });
});

test.describe('i18n label pembayaran', () => {
  // Label channel pembayaran (Virtual Account / Biaya) dulu hardcoded Inggris.
  const CODE = 'E2EPAYLBL';
  const saved: Record<string, string> = {};

  test.beforeAll(() => {
    try {
      for (const key of ['payment_mode', 'payment_gateway', 'singapay_account_id']) {
        saved[key] = mysql(`SELECT setting_value FROM settings WHERE setting_key = '${key}'`);
      }
      const set = (k: string, v: string) =>
        mysql(`INSERT INTO settings (setting_key, setting_value) VALUES ('${k}', '${v}') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`);
      set('payment_mode', 'instant');
      set('payment_gateway', 'singapay');
      set('singapay_account_id', 'E2EACC');
      const td = mysql(`SELECT id, tour_id FROM tour_dates WHERE is_active = 1 ORDER BY id LIMIT 1`).split('\t');
      mysql(
        `INSERT INTO bookings (booking_code, tour_id, tour_date_id, name, email, phone, participants, total_price, status) ` +
          `VALUES ('${CODE}', ${td[1]}, ${td[0]}, 'E2E Pay', 'e2epay@t.local', '0800000000', 1, 100000, 'pending')`
      );
    } catch { /* ignore */ }
  });

  test.afterAll(() => {
    try { mysql(`DELETE FROM bookings WHERE booking_code = '${CODE}'`); } catch { /* ignore */ }
    try {
      for (const [k, v] of Object.entries(saved)) {
        if (v !== '') mysql(`UPDATE settings SET setting_value = '${v}' WHERE setting_key = '${k}'`);
        else mysql(`DELETE FROM settings WHERE setting_key = '${k}'`);
      }
    } catch { /* ignore */ }
  });

  test('label Virtual Account mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Virtual Account', zh: '虚拟账户' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/booking-success.php?code=${CODE}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const methods = page.locator('[data-testid="singapay-methods"]');
      await expect(methods).toBeVisible();
      await expect(methods).toContainText(label);
      if (lang === 'zh') await expect(methods).not.toContainText('Virtual Account');
    }
  });

  // Label channel Tripay ("BRI Virtual Account") ikut bahasa aktif.
  test('label channel Tripay mengikuti bahasa', async ({ page }) => {
    const set = (k: string, v: string) =>
      mysql(`INSERT INTO settings (setting_key, setting_value) VALUES ('${k}', '${v}') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)`);
    set('payment_gateway', 'tripay');
    set('tripay_api_key', 'E2EKEY');
    set('tripay_private_key', 'E2EPRIV');
    set('tripay_merchant_code', 'E2E001');
    try {
      const expected: Record<string, string> = { en: 'BRI Virtual Account', zh: 'BRI 虚拟账户' };
      for (const [lang, label] of Object.entries(expected)) {
        await page.goto(`${BASE}/booking-success.php?code=${CODE}&lang=${lang}`);
        await page.waitForLoadState('domcontentloaded');
        const grid = page.locator('[data-testid="tripay-methods"]');
        await expect(grid).toBeVisible();
        await expect(grid).toContainText(label);
        if (lang === 'zh') await expect(grid).not.toContainText('Virtual Account');
      }
    } finally {
      set('payment_gateway', 'singapay');
      set('tripay_api_key', '');
      set('tripay_private_key', '');
      set('tripay_merchant_code', '');
    }
  });
});

test.describe('i18n halaman booking ferry', () => {
  // Key 'Pesan Ferry' dulu punya nilai en identity (tetap Indonesia).
  const BOOK = 'ferry-booking.php?company=TestFerry&from=A&to=B&date=2026-12-01&time=08:00&price=100000&passengers=1&vessel=V1';
  test('judul booking ferry mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { id: 'Pesan Ferry', en: 'Book Ferry', zh: '预订船票' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/${BOOK}&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await expect(page.locator('body')).toContainText(label);
      if (lang !== 'id') await expect(page.locator('body')).not.toContainText('Pesan Ferry');
    }
  });
});

test.describe('i18n newsletter footer', () => {
  // Pesan error newsletter (footer, semua halaman) dulu pakai I18N.t() yang tidak
  // terdaftar, sehingga selalu tampil Indonesia di semua bahasa.
  test('pesan error newsletter mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'Something went wrong', zh: '发生错误' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/index.php?lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      await page.route('**/newsletter-ajax.php', (route) => route.abort());
      await page.fill('#newsletterEmail', 'e2e-newsletter@t.local');
      await page.click('#newsletterForm button[type="submit"]');
      const msg = page.locator('.klook-newsletter-msg');
      await expect(msg).toContainText(label);
      await expect(msg).not.toContainText('Terjadi kesalahan');
      await expect(msg).not.toContainText('Gagal');
    }
  });
});

test.describe('i18n hasil pencarian ferry', () => {
  // Badge hasil ferry ("Ferry" / "e-ticket") dulu hardcoded.
  const KEY = 'ferry:9001:9002:2026-12-01:0:0:1';
  test.beforeAll(() => {
    try {
      const trips = { trips: [
        { company: 'TestFerry', vessel_name: 'V1', departure_time: '08:00', arrival_time: '10:00', from_terminal: 'Merak', to_terminal: 'Bakauheni', price: 100000, date: '2026-12-01' },
        { company: 'TestFerry', vessel_name: 'V2', departure_time: '12:00', arrival_time: '14:00', from_terminal: 'Merak', to_terminal: 'Bakauheni', price: 150000, date: '2026-12-01' },
      ] };
      const json = JSON.stringify(trips).replace(/'/g, "''");
      mysql(
        `INSERT INTO flight_cache (cache_key, source, response_json, offers_count, expires_at) ` +
          `VALUES ('${KEY}', 'ferry', '${json}', 2, UTC_TIMESTAMP() + INTERVAL 1 HOUR) ` +
          `ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), expires_at = VALUES(expires_at)`
      );
    } catch { /* ignore */ }
  });
  test.afterAll(() => {
    try { mysql(`DELETE FROM flight_cache WHERE cache_key = '${KEY}'`); } catch { /* ignore */ }
  });

  test('badge hasil ferry mengikuti bahasa', async ({ page }) => {
    const expected: Record<string, string> = { en: 'e-ticket', zh: '电子票' };
    for (const [lang, label] of Object.entries(expected)) {
      await page.goto(`${BASE}/ferries.php?search=1&from_pid=9001&to_pid=9002&date=2026-12-01&passengers=1&lang=${lang}`);
      await page.waitForLoadState('domcontentloaded');
      const card = page.locator('.flight-card').first();
      await expect(card).toBeVisible();
      await expect(card).toContainText(label);
      if (lang === 'zh') await expect(card).not.toContainText('e-ticket');
    }
  });
});

test.describe('i18n admin settings sweep', () => {
  // 'Reseller'/'Reseller booking' sah berbahasa Inggris (identity-en).
  const ADMIN_LEAK_ALLOWLIST = new Set(['Reseller', 'Reseller booking']);
  // Halaman admin pengaturan yang belum punya sweep khusus.
  const ADMIN_PAGES = [
    'admin/appearance.php',
    'admin/loyalty-settings.php',
    'admin/analytics.php',
    'admin/ab-tests.php',
    'admin/tour-add.php',
  ];
  for (const lang of ['en', 'zh']) {
    test(`admin settings bersih dari teks Indonesia saat bahasa=${lang}`, async ({ page }) => {
      test.setTimeout(180_000);
      await adminLogin(page);
      const failures: string[] = [];
      for (const path of ADMIN_PAGES) {
        await page.goto(`${BASE}/${path}?lang=id`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const idStrings = await visibleStrings(page);

        await page.goto(`${BASE}/${path}?lang=${lang}`);
        await page.waitForLoadState('domcontentloaded');
        await page.waitForTimeout(300);
        const otherStrings = await visibleStrings(page);

        const leaked = leakedStrings(idStrings, otherStrings).filter((s) => !ADMIN_LEAK_ALLOWLIST.has(s));
        if (leaked.length) failures.push(`${path}:\n  - ${leaked.join('\n  - ')}`);
      }
      expect(failures, `Teks Indonesia bocor di admin ${lang}:\n${failures.join('\n')}`).toEqual([]);
    });
  }
});

test.describe('i18n admin list chrome', () => {
  // Header tabel daftar admin harus ikut bahasa (chrome, bukan data).
  const PAGES = [
    'admin/attractions.php',
    'admin/esim.php',
    'admin/collections.php',
    'admin/faq-category.php',
    'admin/faq.php',
    'admin/trains.php',
    'admin/transfers.php',
    'admin/hotels.php',
    'admin/flights.php',
    'admin/ferries.php',
    'admin/hero-slides.php',
    'admin/posts.php',
    'admin/rental-cars.php',
    'admin/promo-codes.php',
  ];
  test('header tabel daftar admin mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { en: 'Actions', zh: '操作' };
    for (const [lang, label] of Object.entries(expected)) {
      for (const path of PAGES) {
        await page.goto(`${BASE}/${path}?lang=${lang}`);
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('table thead').first()).toContainText(label);
      }
    }
  });
});

test.describe('i18n admin edit forms', () => {
  // Tombol simpan form edit admin harus ikut bahasa.
  const PAGES = [
    'admin/hotel-edit.php?id=1',
    'admin/faq-edit.php?id=1',
    'admin/post-edit.php?id=1',
    'admin/ferry-edit.php?id=1',
    'admin/train-edit.php?id=1',
    'admin/transfer-edit.php?id=1',
    'admin/rental-car-edit.php?id=1',
    'admin/flight-edit.php?id=1',
    'admin/esim-edit.php?id=1',
  ];
  test('tombol simpan form edit mengikuti bahasa', async ({ page }) => {
    await adminLogin(page);
    const expected: Record<string, string> = { en: 'Save', zh: '保存' };
    for (const [lang, label] of Object.entries(expected)) {
      for (const path of PAGES) {
        await page.goto(`${BASE}/${path}&lang=${lang}`);
        await page.waitForLoadState('domcontentloaded');
        await expect(page.locator('button[type="submit"]').first()).toContainText(label);
      }
    }
  });
});

test.describe('i18n email templates', () => {
  // Render each transactional email in id/en/zh and assert no Indonesian leaks.
  test('template email tidak menyisakan teks Indonesia', async () => {
    // Convert a JS value to a PHP literal (array/object/string/number/bool/null).
    const toPhp = (v: unknown): string => {
      if (v === null || v === undefined) return 'null';
      if (typeof v === 'string') return JSON.stringify(v);
      if (typeof v === 'number' || typeof v === 'boolean') return String(v);
      if (Array.isArray(v)) return '[' + v.map(toPhp).join(', ') + ']';
      if (typeof v === 'object') {
        return '[' + Object.entries(v as Record<string, unknown>)
          .map(([k, val]) => JSON.stringify(k) + ' => ' + toPhp(val))
          .join(', ') + ']';
      }
      return 'null';
    };
    const events = [
      { event: 'booking-created', data: { booking_code: 'TAT-1', total: 'Rp 100', pay_link: 'http://x/pay', track_link: 'http://x/track' } },
      { event: 'booking-status', data: { booking_code: 'TAT-2', status: 'paid', track_link: 'http://x/track' } },
      { event: 'invoice', data: { order_id: 'ORD-1', amount: 'Rp 50', insurance_premi: 0, insurance_amount: '' } },
      { event: 'reset-password', data: { reset_link: 'http://x/reset' } },
      { event: 'topup-approved', data: { amount: 'Rp 100.000', admin_note: 'ok', track_link: 'http://x/t' } },
      { event: 'topup-rejected', data: { amount: 'Rp 100.000', admin_note: 'no', track_link: 'http://x/t' } },
      { event: 'welcome', data: {} },
    ];
    const markers = ['tidak', 'belum', 'sudah', 'silakan', 'mohon', 'berhasil', 'gagal', 'pesanan', 'pembayaran', 'kupon', 'paket', 'tur'];
    for (const { event, data } of events) {
      for (const lang of ['en', 'zh']) {
        const out = execFileSync('php', [
          '-r',
          `require "includes/config.php"; require "includes/db.php"; require "includes/email.php";
           $t = renderEmailTemplate(${JSON.stringify(event)}, ${toPhp(data)}, ${JSON.stringify(lang)});
           echo $t["subject"] . "\n" . $t["html"];`,
        ], { encoding: 'utf8' });
        for (const m of markers) {
          expect(out.toLowerCase(), `${event} @ ${lang} contains "${m}"`).not.toContain(m);
        }
      }
    }
  });
});
