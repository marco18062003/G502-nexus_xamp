<?php
/**
 * webhook.php
 * ─────────────────────────────────────────────────────────
 * Telegram llama a este script automáticamente cada vez que alguien
 * presiona el botón "✅ Marcar hecho — PLU ..." en el chat.
 *
 * Flujo:
 *   1. Telegram envía un POST con el "callback_query" (qué botón, quién,
 *      en qué mensaje).
 *   2. Extraemos el id del producto desde callback_data ("done:123").
 *   3. Marcamos state = 'X' en la base de datos para ese id.
 *   4. Le avisamos a Telegram que ya procesamos el tap (answerCallbackQuery)
 *      — esto hace que el botón deje de "girar" en el celular del usuario.
 *   5. Editamos el mensaje original quitando ese botón específico,
 *      para que quede claro visualmente que ya se marcó.
 *
 * IMPORTANTE — cómo activar esto (una sola vez):
 *   Sube este archivo a tu servidor (misma carpeta que notify_telegram.php)
 *   y visita esta URL UNA VEZ en tu navegador (cambia los valores):
 *
 *   https://api.telegram.org/bot<TU_TOKEN>/setWebhook?url=https://TU_DOMINIO/ruta/webhook.php&secret_token=<UN_TEXTO_SECRETO_QUE_INVENTES>
 *
 *   Usa el mismo <UN_TEXTO_SECRETO_QUE_INVENTES> que pongas abajo en
 *   $WEBHOOK_SECRET. Esto evita que alguien más le mande datos falsos
 *   a este script.
 */

require_once __DIR__ . '/../../config/db.php';
date_default_timezone_set('America/Bogota');

$TELEGRAM_BOT_TOKEN = '8889034847:AAFDko2odwudfknTqF1a1NWQb8dcIItnl7w';
$WEBHOOK_SECRET      = 'CAMBIA_ESTO_POR_UN_TEXTO_SECRETO'; // debe coincidir con el que uses en setWebhook

function logLinea($texto) {
    file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . ' | ' . $texto . "\n", FILE_APPEND);
}

// ── Verificar que la petición realmente viene de Telegram ────────────
$headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
if ($headerSecret !== $WEBHOOK_SECRET) {
    http_response_code(403);
    logLinea('Rechazado: secret_token no coincide');
    exit;
}

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!isset($update['callback_query'])) {
    // No es un tap de botón (podría ser otro tipo de update); no hacemos nada.
    http_response_code(200);
    exit;
}

$callback = $update['callback_query'];
$callbackId = $callback['id'];
$data = $callback['data'] ?? '';
$mensaje = $callback['message'] ?? null;

if (!$mensaje || strpos($data, 'done:') !== 0) {
    http_response_code(200);
    exit;
}

$id = (int) substr($data, strlen('done:'));

function llamarTelegram($token, $metodo, $payload) {
    $url = "https://api.telegram.org/bot{$token}/{$metodo}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

if ($id <= 0) {
    llamarTelegram($TELEGRAM_BOT_TOKEN, 'answerCallbackQuery', [
        'callback_query_id' => $callbackId,
        'text' => '⚠️ No se pudo identificar el producto.',
        'show_alert' => false,
    ]);
    logLinea("callback_data inválido: $data");
    exit;
}

// ── Marcar como hecho en la base de datos ─────────────────────────
$updateStmt = $conn->prepare("UPDATE place_expiration SET state = 'X' WHERE id = ?");
$updateStmt->bind_param('i', $id);
$ok = $updateStmt->execute();
$filasAfectadas = $updateStmt->affected_rows;

if ($ok && $filasAfectadas > 0) {
    $textoAlerta = '✅ Marcado como hecho';
    logLinea("id=$id marcado como hecho por " . ($callback['from']['username'] ?? $callback['from']['first_name'] ?? 'desconocido'));
} else {
    $textoAlerta = 'ℹ️ Ya estaba marcado (o no se encontró).';
    logLinea("id=$id sin cambios (affected_rows=$filasAfectadas)");
}

// ── Avisar al usuario que ya se procesó el tap ─────────────────────
llamarTelegram($TELEGRAM_BOT_TOKEN, 'answerCallbackQuery', [
    'callback_query_id' => $callbackId,
    'text' => $textoAlerta,
    'show_alert' => false,
]);

// ── Quitar solo ese botón del teclado del mensaje original ─────────
$tecladoActual = $mensaje['reply_markup']['inline_keyboard'] ?? [];
$tecladoNuevo = [];
foreach ($tecladoActual as $fila) {
    $filaNueva = array_filter($fila, function ($boton) use ($data) {
        return ($boton['callback_data'] ?? '') !== $data;
    });
    if (!empty($filaNueva)) {
        $tecladoNuevo[] = array_values($filaNueva);
    }
}

llamarTelegram($TELEGRAM_BOT_TOKEN, 'editMessageReplyMarkup', [
    'chat_id'      => $mensaje['chat']['id'],
    'message_id'   => $mensaje['message_id'],
    'reply_markup' => json_encode(['inline_keyboard' => $tecladoNuevo]),
]);

http_response_code(200);