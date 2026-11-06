<?php
require_once __DIR__ . '/whatsapp_config.php';

function logMsg($t) {
    file_put_contents(__DIR__ . '/webhook_test.log', date('Y-m-d H:i:s') . " | $t\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode  = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';

    if ($mode === 'subscribe' && $token === WHATSAPP_VERIFY_TOKEN) {
        echo $_GET['hub_challenge'];
    } else {
        http_response_code(403);
        echo "mismatch | mode=" . htmlspecialchars($mode)
           . " | received length=" . strlen($token)
           . " | expected length=" . strlen(WHATSAPP_VERIFY_TOKEN);
    }
    exit;
}

// Incoming messages (POST)
$data = json_decode(file_get_contents('php://input'), true);
logMsg(json_encode($data));
http_response_code(200);

$msg = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
if (!$msg) exit;

$from  = $msg['from'];
$text  = strtolower(trim($msg['text']['body'] ?? ''));
$reply = ($text === 'ping') ? 'pong ✅' : 'Received: ' . $text;

$ch = curl_init("https://graph.facebook.com/v20.0/" . WHATSAPP_PHONE_NUMBER_ID . "/messages");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . WHATSAPP_TOKEN, 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'messaging_product' => 'whatsapp',
        'to'   => $from,
        'type' => 'text',
        'text' => ['body' => $reply],
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);
$res = curl_exec($ch);
logMsg("send HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " | " . $res);