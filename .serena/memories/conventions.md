# Conventions

- PHP: `require_once` config→db→functions di setiap page; helper `e($str)` untuk escape, `formatRupiah()`, `tglIndonesia()`, `buatSlug()`, `uploadGambar()`/`uploadWebP()`, `getTours()` dengan filter+pagination, `getTourBySlug/Id()`, wishlist `isWishlisted/getWishlistIds`.
- Naming: file/pages kebab-case (`tour-detail.php`, `rental-car-detail.php`), ajax `*-ajax.php`, admin di `admin/` dengan guard `cekLogin()` + `admin-header/footer.php`.
- DB: snake_case kolom, FK `ON DELETE CASCADE`, `is_active` flag, slug unique, pagination `LIMIT/OFFSET` via string interpolation (perPage/offset cast int).
- WA: `wa-config.json` (gitignored) override defaults di `send-wa.php`; token/server/admin_phone editable via `admin/wa-settings.php`.
- Gitignore: `macbook_rsa*`, `SERVER.md`, `uploads/passports/`, `wa-config.json`.
