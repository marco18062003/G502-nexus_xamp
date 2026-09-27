<?php
/**
 * webhook_whatsapp.php
 * ─────────────────────────────────────────────────────────
 * Bot de WhatsApp interactivo para G502.
 * Lee comandos del chat y responde usando las consultas a la BD.
 */

require_once __DIR__ . '/../../config/db.php';
date_default_timezone_set('America/Bogota');

// ── CARGAR CONFIGURACIÓN ─────────────────────────────────────
$configFile = __DIR__ . '/whatsapp_config.php';
if (file_exists($configFile)) {
    require_once $configFile;
    $WHATSAPP_TOKEN         = defined('WHATSAPP_TOKEN') ? WHATSAPP_TOKEN : null;
    $WHATSAPP_PHONE_NUMBER_ID = defined('WHATSAPP_PHONE_NUMBER_ID') ? WHATSAPP_PHONE_NUMBER_ID : null;
    $WHATSAPP_VERIFY_TOKEN    = defined('WHATSAPP_VERIFY_TOKEN') ? WHATSAPP_VERIFY_TOKEN : null;
} else {
    $WHATSAPP_TOKEN         = getenv('WHATSAPP_TOKEN');
    $WHATSAPP_PHONE_NUMBER_ID = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $WHATSAPP_VERIFY_TOKEN    = getenv('WHATSAPP_VERIFY_TOKEN');
}

$WHATSAPP_API_VERSION = 'v20.0';
$LOG_FILE             = __DIR__ . '/webhook_whatsapp.log';

function logMsg($texto) {
    global $LOG_FILE;
    file_put_contents($LOG_FILE, date('Y-m-d H:i:s') . " | $texto\n", FILE_APPEND);
}

if (!$WHATSAPP_VERIFY_TOKEN) {
    logMsg("ERROR FATAL: WHATSAPP_VERIFY_TOKEN no está definido.");
}

// ── PASO 1: Verificación del Webhook (GET) ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode'] ?? '';
    $token     = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $WHATSAPP_VERIFY_TOKEN) {
        logMsg("Webhook verificado correctamente");
        echo $challenge;
        exit;
    }
    http_response_code(403);
    logMsg("Verificación de webhook FALLÓ (token no coincide)");
    exit('Forbidden');
}

// ── PASO 2: Recepción de Mensajes (POST) ─────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    logMsg("POST recibido: " . $body);

    // Responder rápido a Meta con 200 OK para evitar reintentos innecesarios
    http_response_code(200);

    $entry    = $data['entry'][0] ?? null;
    $change   = $entry['changes'][0] ?? null;
    $value    = $change['value'] ?? null;
    $mensajes = $value['messages'] ?? null;

    if (!$mensajes) {
        exit; // Es un evento de estado (entregado/leído), no un mensaje de texto
    }

    foreach ($mensajes as $msg) {
        $from  = $msg['from'] ?? null; 
        $texto = trim(strtolower($msg['text']['body'] ?? ''));

        if (!$from) continue;

        logMsg("Mensaje procesado de $from: $texto");
        $respuesta = procesarComando($texto);
        enviarWhatsApp($from, $respuesta);
    }
    exit;
}

http_response_code(405);
exit('Method Not Allowed');

// ── LÓGICA DE COMANDOS ───────────────────────────────────────
function procesarComando($texto) {
    if ($texto === '') {
        return "No entendí ese mensaje. Escribe *ayuda* para ver los comandos disponibles.";
    }

    if (in_array($texto, ['ayuda', 'help', 'menu', 'comandos'])) {
        return "🤖 *Comandos disponibles para G502:*\n\n"
             . "• *hoy* — productos que vencen hoy\n"
             . "• *vencidos* — productos ya vencidos\n"
             . "• *proximos* — vencen en los próximos 5 días\n"
             . "• *plu 1234* — busca un código PLU específico\n";
    }

    if (preg_match('/^plu\s+(\S+)$/', $texto, $m)) {
        return buscarPorPlu($m[1]);
    }

    if ($texto === 'hoy') {
        return listarProductos('= 0', "📅 *Vencen HOY*");
    }
    if ($texto === 'vencidos') {
        return listarProductos('< 0', "🚨 *VENCIDOS*");
    }
    if ($texto === 'proximos' || $texto === 'próximos') {
        return listarProductos('BETWEEN 0 AND 5', "⌛ *Próximos a vencer (5 días)*");
    }

    return "No entendí ese comando. Escribe *ayuda* para ver las opciones.";
}

function listarProductos($condicionDias, $titulo) {
    global $conn;

    $sql = "SELECT plu_code, departamento, description, quantity,
                   fecha_vencimiento,
                   DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
            FROM place_expiration
            WHERE fecha_vencimiento IS NOT NULL
              AND (state IS NULL OR TRIM(state) != 'X')
              AND DATEDIFF(fecha_vencimiento, CURDATE()) $condicionDias
            ORDER BY departamento, dias_restantes ASC
            LIMIT 30";

    $result = $conn->query($sql);

    if (!$result || $result->num_rows === 0) {
        return "$titulo\n\nNo hay productos en esta categoría. ✅";
    }

    $mensaje = "$titulo\n\n";
    while ($p = $result->fetch_assoc()) {
        $mensaje .= formatearLinea($p);
    }
    return $mensaje;
}

function buscarPorPlu($plu) {
    global $conn;

    $stmt = $conn->prepare(
        "SELECT plu_code, departamento, description, quantity,
                fecha_vencimiento,
                DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
         FROM place_expiration
         WHERE plu_code = ?
         ORDER BY fecha_vencimiento ASC"
    );
    $stmt->bind_param('s', $plu);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return "No encontré ningún producto con el PLU *$plu*.";
    }

    $mensaje = "🔎 *Resultados para PLU $plu:*\n\n";
    while ($p = $result->fetch_assoc()) {
        $mensaje .= formatearLinea($p);
    }
    return $mensaje;
}

function formatearLinea($p) {
    $desc  = $p['description'] ?: 'Sin descripción';
    $fecha = date('d/m/y', strtotime($p['fecha_vencimiento']));
    $dias  = (int)$p['dias_restantes'];
    $depto = $p['departamento'] ?: 'SIN DEPTO';

    if ($dias < 0) {
        $etiqueta = "venció $fecha (hace " . abs($dias) . "d)";
    } elseif ($dias === 0) {
        $etiqueta = "vence HOY ($fecha)";
    } else {
        $etiqueta = "vence $fecha (faltan {$dias}d)";
    }

    return "• PLU {$p['plu_code']} — {$desc} ({$depto}) x{$p['quantity']} — {$etiqueta}\n";
}

// ── ENVÍO DE RESPUESTA A WHATSAPP ────────────────────────────
function enviarWhatsApp($to, $mensaje) {
    global $WHATSAPP_TOKEN, $WHATSAPP_PHONE_NUMBER_ID, $WHATSAPP_API_VERSION;

    $chunks = str_split($mensaje, 4000);
    $url = "https://graph.facebook.com/{$WHATSAPP_API_VERSION}/{$WHATSAPP_PHONE_NUMBER_ID}/messages";

    foreach ($chunks as $chunk) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $chunk,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $WHATSAPP_TOKEN,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        logMsg("Respuesta enviada a $to | HTTP $httpCode | $response");
    }
}