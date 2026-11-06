<?php
require_once __DIR__ . '/whatsapp_config.php';
if (($_GET['k'] ?? '') !== WHATSAPP_VERIFY_TOKEN) { http_response_code(403); exit('Forbidden'); }

function graph($method) {
    $ch = curl_init('https://graph.facebook.com/' . WHATSAPP_API_VERSION . '/1082752314194715/subscribed_apps');
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . trim(WHATSAPP_TOKEN)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    return $res === false ? curl_error($ch) : $res;
}

header('Content-Type: text/plain');
echo "ANTES (GET):\n"  . graph('GET')  . "\n\n";
echo "SUSCRIBIR (POST):\n" . graph('POST') . "\n\n";
echo "DESPUÉS (GET):\n" . graph('GET')  . "\n";