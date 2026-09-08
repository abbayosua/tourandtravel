<?php
/**
 * SocialProofTest — mask nama privacy-safe + struktur endpoint.
 */

function spMaskNameLocal(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Tamu';
    $parts = preg_split('/\s+/', $name);
    $first = mb_substr($parts[0], 0, 1) . str_repeat('*', max(1, mb_strlen($parts[0]) - 1));
    if (count($parts) > 1) {
        $last = mb_substr($parts[count($parts) - 1], 0, 1) . str_repeat('*', max(1, mb_strlen($parts[count($parts) - 1]) - 1));
        return $first . ' ' . $last;
    }
    return $first;
}

function testMaskNameHidesFullName() {
    $masked = spMaskNameLocal('Budi Santoso');
    assertMatches('/^[A-Za-z]\*+ [A-Za-z]\*+$/', $masked, 'format inisial+asterisk');
    assertTrue(strpos($masked, 'Budi') === false, 'nama lengkap tidak boleh bocor');
    assertTrue(strpos($masked, 'Santoso') === false, 'nama belakang tidak boleh bocor');
}

function testMaskNameSingleWord() {
    assertMatches('/^[A-Za-z]\*+$/', spMaskNameLocal('Siti'));
}

function testMaskNameEmptyFallsBack() {
    assertEquals('Tamu', spMaskNameLocal(''));
    assertEquals('Tamu', spMaskNameLocal('   '));
}

function testSocialProofEndpointReturnsJsonStructure() {
    $tmp = dirname(__DIR__, 2) . '/scripts/out/social-proof-cache.json';
    @unlink($tmp);
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $base = defined('TEST_BASE_URL') ? TEST_BASE_URL : 'http://localhost/tourandtravel';
    $json = @file_get_contents($base . '/social-proof-ajax.php?limit=5', false, $ctx);
    if ($json === false) {
        throw new UnitTestFailure('endpoint tidak terjangkau dari CLI test');
    }
    $data = json_decode($json, true);
    assertTrue(is_array($data), 'response JSON valid');
    assertTrue(isset($data['success']) && $data['success'] === true, 'success=true');
    assertTrue(isset($data['count']) && is_int($data['count']), 'count int');
    assertTrue(isset($data['items']) && is_array($data['items']), 'items array');
    foreach ($data['items'] as $item) {
        assertContains($item['type'], ['tour', 'hotel'], 'type valid');
        assertMatches('/^[A-Za-z]\*+( [A-Za-z]\*+)?$/', $item['name'], 'nama ter-mask');
        assertTrue(!isset($item['email']) && !isset($item['phone']) && !isset($item['user_id']), 'tidak ada data sensitif');
    }
    // cache file dibuat
    assertTrue(is_file($tmp), 'cache file dibuat');
    @unlink($tmp);
}

function testSocialProofCacheWarm() {
    $tmp = dirname(__DIR__, 2) . '/scripts/out/social-proof-cache.json';
    @unlink($tmp);
    $base = defined('TEST_BASE_URL') ? TEST_BASE_URL : 'http://localhost/tourandtravel';
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    @file_get_contents($base . '/social-proof-ajax.php', false, $ctx);
    $mtime1 = @filemtime($tmp);
    sleep(1);
    @file_get_contents($base . '/social-proof-ajax.php', false, $ctx);
    $mtime2 = @filemtime($tmp);
    assertEquals($mtime1, $mtime2, 'hit kedua memakai cache (mtime tak berubah)');
    @unlink($tmp);
}
