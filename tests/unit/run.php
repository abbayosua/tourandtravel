<?php
/**
 * Unit Test Runner — tanpa composer/framework.
 *
 * Pemakaian:
 *   php tests/unit/run.php                 # jalankan semua *Test.php
 *   php tests/unit/run.php Midtrans        # jalankan file yang cocok nama
 *
 * Konvensi file test:
 *   tests/unit/<Nama>Test.php — berisi satu atau lebih fungsi
 *   function test<NamaKasus>() { ... }  → dipanggil otomatis.
 *
 * Helper assertion tersedia global:
 *   assertTrue($cond, $msg), assertEquals($a,$b,$msg), assertContains($needle,$hay,$msg),
 *   assertSame($a,$b,$msg), assertMatches($regex,$str,$msg)
 *
 * Exit code: 0 bila semua lulus, 1 bila ada kegagalan.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '1');

$ROOT = dirname(__DIR__, 2);

// Bootstrap aplikasi (config me-load functions; db untuk test ber-DB)
require_once $ROOT . '/includes/config.php';
require_once $ROOT . '/includes/db.php';

// ============================================================
// Assertion helpers (fail = exception dengan pesan jelas)
// ============================================================
class UnitTestFailure extends Exception {}

function assertTrue($cond, string $msg = 'expected true'): void {
    if (!$cond) throw new UnitTestFailure($msg);
}
function assertEquals($expected, $actual, string $msg = ''): void {
    if ($expected != $actual) {
        throw new UnitTestFailure(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}
function assertSame($expected, $actual, string $msg = ''): void {
    if ($expected !== $actual) {
        throw new UnitTestFailure(($msg ? "$msg: " : '') . 'expected(same) ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}
function assertContains($needle, $haystack, string $msg = ''): void {
    if (is_string($haystack)) {
        if (strpos($haystack, $needle) === false) throw new UnitTestFailure(($msg ? "$msg: " : '') . "string does not contain '$needle'");
        return;
    }
    if (!in_array($needle, $haystack, true)) throw new UnitTestFailure(($msg ? "$msg: " : '') . "array does not contain " . var_export($needle, true));
}
function assertMatches(string $regex, $subject, string $msg = ''): void {
    if (!preg_match($regex, (string)$subject)) throw new UnitTestFailure(($msg ? "$msg: " : '') . "'$subject' does not match $regex");
}

// ============================================================
// Discovery & eksekusi
// ============================================================
$testDir = __DIR__;
$filter = $argv[1] ?? null;

$files = glob($testDir . '/*Test.php');
if ($filter) {
    $files = array_values(array_filter($files, fn($f) => stripos(basename($f), $filter) !== false));
}
if (!count($files)) {
    echo "Tidak ada file test cocok" . ($filter ? " '$filter'" : "") . ".\n";
    exit(1);
}

$totalPassed = 0;
$totalFailed = 0;
$failures = [];

foreach ($files as $file) {
    $base = basename($file);
    require_once $file;

    // Kumpulkan fungsi test yang didefinisikan file ini (bandingkan sebelum/sesudah)
    static $known = [];
    $declared = get_defined_functions()['user'];
    $newTests = array_filter($declared, fn($f) => stripos($f, 'test') === 0 && !in_array($f, $known, true));
    foreach ($newTests as $t) $known[] = $t;
    $tests = array_values($newTests);

    echo "=== $base (" . count($tests) . " kasus)\n";

    foreach ($tests as $fn) {
        $label = str_pad($fn, 45);
        try {
            $fn();
            $totalPassed++;
            echo "  ✓ $label\n";
        } catch (UnitTestFailure $e) {
            $totalFailed++;
            $failures[] = "$base::$fn — " . $e->getMessage();
            echo "  ✘ $label\n      " . $e->getMessage() . "\n";
        } catch (Throwable $e) {
            $totalFailed++;
            $failures[] = "$base::$fn — EXCEPTION: " . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine();
            echo "  ✘ $label\n      EXCEPTION: " . $e->getMessage() . " @ " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
    }
}

echo "\n" . str_repeat('=', 52) . "\n";
echo "LULUS: $totalPassed   GAGAL: $totalFailed\n";
if ($failures) {
    echo "\nDaftar kegagalan:\n";
    foreach ($failures as $i => $f) echo "  " . ($i + 1) . ". $f\n";
    exit(1);
}
exit(0);
