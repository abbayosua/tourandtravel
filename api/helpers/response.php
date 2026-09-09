<?php
function jsonResponse(bool $success, $data = null, array $meta = null, string $error = null, string $message = null, int $httpCode = 200): never {
    http_response_code($httpCode);
    $payload = ['success' => $success];
    if ($data !== null) $payload['data'] = $data;
    if ($meta !== null) $payload['meta'] = $meta;
    if ($error !== null) $payload['error'] = $error;
    if ($message !== null) $payload['message'] = $message;
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonOk($data = null, array $meta = null): never {
    jsonResponse(true, $data, $meta);
}

function jsonError(string $error, string $message = null, int $httpCode = 400): never {
    jsonResponse(false, null, null, $error, $message, $httpCode);
}
