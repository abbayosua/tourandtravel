# Full Multilingual (EN/ID + ekstensibel) — SELESAI

## Arsitektur
- Registry: `getSupportedLanguages()` di functions.php (satu titik tambah bahasa). Tambah bahasa = 1 baris + seed + (opsional) kolom `_{lang}` — switcher/html lang/validasi otomatis. Doc: MULTILINGUAL.md.
- `getCurrentLang()`: session > cookie > Accept-Language > 'id', tervalidasi registry. config.php whitelist `?lang=` pakai registry (dengan guard require functions.php).
- `t($key)`: key = string Indonesia; preload semua translations per request (1 query). `tContent($row,$field)`: kolom `{field}_{lang}` + fallback asli.
- JS: `i18nJs()` inject window.I18N {lang, locale id-ID/en-US} + assets/js/i18n.js I18N.t(); key terdaftar di getJsI18nKeys(). Locale tag HARUS dash (id-ID), bukan underscore — Intl.NumberFormat crash dengan underscore.
- formatDate()/formatNumber() locale-aware; formatRupiah/formatCurrency separator ikut bahasa.
- Konten DB: kolom _en + content_language di 9 tabel (migrate-content-lang.sql, idempotent; schema-klook.sql juga memuat DDL hotels/rental_cars/ferries yang tadinya runtime-only).
- Audit: scripts/audit-translations.php per-cluster (public/includes/ajax/admin/js) → scripts/out/.

## Status verifikasi
- E2E full `--workers=1`: 408 tests, 308 passed, 0 failed (baseline 4 failure = multilingual.spec.ts menarget remote web.id). Multi-worker memicu race DB (XAMPP MySQL tunggal) ~98 failure palsu — SELALU jalankan --workers=1.
- Audit: missing en = 0; hardcoded user-visible = 0 (4 FP terdokumentasi).
- Smoke manual 29 halaman lang=en: bersih.
- Produksi tourandtravel.web.id: deploy f6ecd15, migration dijalankan, regenerate-en jalan di server, smoke 12 halaman 200 + EN session bekerja.

## Commit
9 commit `feat: i18n phase 1..9` (58c25b7..f6ecd15) di origin/main. Server config.php diverge (kredensial) — selalu stash/resolve saat pull.

## Follow-up potensial
- ~555 key EN masih identity (fallback); disempurnakan bertahap via kamus regenerate-en.php.
- Bahasa ke-3 tinggal ikuti MULTILINGUAL.md.
