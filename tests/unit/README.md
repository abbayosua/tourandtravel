# Unit Tests (tanpa composer)

Jalankan semua: `php tests/unit/run.php`
Filter: `php tests/unit/run.php Midtrans`

- File test: `tests/unit/<Nama>Test.php`, fungsi bernama `test*()`
- Assertion: assertTrue, assertEquals, assertSame, assertContains, assertMatches
- Bootstrap otomatis (config + db) — helper aplikasi bisa langsung dipakai
- Exit code 0 = lulus, 1 = ada gagal (CI-friendly)
