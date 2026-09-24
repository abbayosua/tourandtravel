<?php
/**
 * Flight Hero — shim ke transport-search.php (mode flight).
 * SATU hero+form reusable untuk Pesawat/Ferry/Kereta ada di
 * includes/homepage/transport-search.php. ID E2E tidak berubah:
 * flightSearchForm, tripTypeHidden, fromInput/toInput, dsb.
 */
$tsMode = 'flight';
require __DIR__ . '/transport-search.php';
