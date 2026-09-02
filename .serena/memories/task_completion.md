# Task Completion

- PHP no build/lint/test runner — verifikasi manual: buka halaman terkait di browser (`BASE_URL`), cek `php -l <file>` untuk syntax, dan `git diff` sebelum selesai.
- Jika ubah DB: re-run `php database/seeder.php` / `database/schema.sql` di dev dan konfirmasi migrasi.
- Jangan commit/push tanpa diminta user; jika diminta ikuti format commit yang ada (`fix:`, `chore:`, `update:`).
- Setelah edit memori/code, jalankan `serena memories check` bila menyentuh referensi `mem:*`.
