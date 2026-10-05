<?php
/**
 * FxBufferTest — FX buffer global (%) & rate-lock helpers.
 * getFxBufferPct / applyFxBuffer / getFxRate.
 */

function fxSetBuffer(string $val) {
    setSetting('fx_buffer_pct', $val);
}

function testFxBufferPctClamp() {
    fxSetBuffer('3');
    assertEquals(3.0, getFxBufferPct(), 'pct 3');
    fxSetBuffer('75');
    assertEquals(50.0, getFxBufferPct(), 'clamp maksimal 50%');
    fxSetBuffer('-5');
    assertEquals(0.0, getFxBufferPct(), 'clamp minimal 0%');
    fxSetBuffer('0');
    assertEquals(0.0, getFxBufferPct(), 'reset 0');
}

function testApplyFxBufferTourOnly() {
    fxSetBuffer('10');
    assertEquals(1100.0, applyFxBuffer(1000.0, 'tour'), 'tour +10% = 1100');
    assertEquals(1000.0, applyFxBuffer(1000.0, 'hotel'), 'non-tour tidak kena buffer');
    fxSetBuffer('0');
    assertEquals(1000.0, applyFxBuffer(1000.0, 'tour'), 'pct 0 = no-op');
}

function testApplyFxBufferRounding() {
    fxSetBuffer('7.5');
    assertEquals(1075.0, applyFxBuffer(1000.0, 'tour'), '7.5% → 1075');
    assertEquals(139.73, applyFxBuffer(129.98, 'tour'), 'round 2 desimal');
    fxSetBuffer('0');
}

function testGetFxRateSameCurrency() {
    assertEquals(1.0, getFxRate('IDR', 'IDR'), 'mata uang sama = 1');
    assertEquals(1.0, getFxRate('SGD', 'SGD'), 'SGD→SGD = 1');
}

function testGetFxRateCrossCurrency() {
    $sgd = getFxRate('SGD', 'IDR');
    if ($sgd !== null) {
        assertTrue($sgd > 1000, 'SGD→IDR wajar (>1000)');
        $usd = getFxRate('USD', 'IDR');
        assertTrue($usd !== null && $usd > 1000, 'USD→IDR wajar (>1000)');
    }
}
