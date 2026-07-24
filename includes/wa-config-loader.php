<?php
/**
 * Load WhatsApp config from JSON file
 */
function loadWAConfig() {
    $configFile = __DIR__ . '/wa-config.json';
    if (file_exists($configFile)) {
        $data = json_decode(file_get_contents($configFile), true);
        if ($data && isset($data['admin_phone'])) {
            return $data;
        }
    }
    // Default
    return [
        'admin_phone' => '6285174488415',
        'token'       => 'abbayosua',
        'server_url'  => 'http://45.158.126.130:48499',
    ];
}

function saveWAConfig($data) {
    $configFile = __DIR__ . '/wa-config.json';
    $current = loadWAConfig();
    $merged = array_merge($current, $data);
    return file_put_contents($configFile, json_encode($merged, JSON_PRETTY_PRINT), LOCK_EX);
}
