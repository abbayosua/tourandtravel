<?php
/**
 * SmokeTest — memastikan runner & bootstrap bekerja (bukan fitur bisnis).
 */
function testRunnerBootsAppHelpers() {
    assertTrue(function_exists('getSetting'), 'getSetting harus tersedia (bootstrap config)');
    assertTrue(function_exists('db'), 'db() harus tersedia (bootstrap db)');
    assertTrue(function_exists('formatRupiah'), 'formatRupiah harus tersedia');
}

function testBasicAssertionsWork() {
    assertTrue(true);
    assertEquals('a', 'a');
    assertSame(5, 5);
    assertContains('x', ['x', 'y']);
    assertContains('hello', 'say hello world');
    assertMatches('/^abc\d+$/', 'abc123');
}

function testAssertionsFailAsExpected() {
    $failed = false;
    try {
        assertTrue(false, 'harus gagal');
    } catch (UnitTestFailure $e) {
        $failed = true;
        assertContains('harus gagal', $e->getMessage());
    }
    assertTrue($failed, 'assertTrue(false) harus melempar UnitTestFailure');
}
