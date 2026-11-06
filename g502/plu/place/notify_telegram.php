<?php
/**
 * notify_telegram.php
 * ─────────────────────────────────────────────────────────
 * Revisa la base de datos por productos que vencen en los próximos
 * 5 días (o ya vencidos) y NO han sido marcados como "Hecho", y
 * envía un resumen por Telegram.
 *
 * Pensado para correr una vez al día vía cron.
 *
 * CAMBIOS (2026-09-07):
 * 1) Los mensajes se dividen en varias partes si superan el límite de
 *    4096 caracteres de Telegram (antes, el envío completo fallaba).
 * 2) Los productos VENCIDOS ahora incluyen un botón "✅ Marcar como
 *    hecho" debajo de cada uno. Al presionarlo, webhook.php actualiza
 *    state = 'X' en la base de datos y el producto deja de aparecer
 *    en reportes futuros — sin necesidad de entrar a phpMyAdmin.
 *    Los productos PRÓXIMOS A VENCER siguen siendo solo informativos
 *    por ahora.
 */

require_once __DIR__ . '/../../config/db.php';
date_default_timezone_set('America/Bogota');

// ── CONFIGURA ESTOS DOS VALORES ──────────────────────────────
$TELEGRAM_BOT_TOKEN = '8889034847:AAFDko2odwudfknTqF1a1NWQb8dcIItnl7w';
$TELEGRAM_CHAT_ID   = '-1003863680824';
// ──────────────────────────────────────────────────────────────

$DIAS_ANTICIPACION = 5;
$TELEGRAM_MAX_LEN  = 4096;

$sql = "SELECT id, plu_code, departamento, description, quantity,
               fecha_vencimiento,
               DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
        FROM place_expiration
        WHERE fecha_vencimiento IS NOT NULL
          AND (state IS NULL OR TRIM(state) != 'X')
          AND DATEDIFF(fecha_vencimiento, CURDATE()) <= ?
        ORDER BY departamento, dias_restantes ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $DIAS_ANTICIPACION);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    exit;
}

// ── Agrupar y fusionar duplicados (mismo PLU + fecha + departamento + descripción) ──
// Nota: si hay duplicados, se queda con el 'id' del primero encontrado
// para el botón (fusionar solo suma la cantidad mostrada, no cambia la BD).
$items = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['plu_code'] . '|' . $row['fecha_vencimiento'] . '|' . $row['departamento'] . '|' . $row['description'];
    if (isset($items[$key])) {
        $items[$key]['quantity'] += (int)$row['quantity'];
    } else {
        $items[$key] = $row;
        $items[$key]['quantity'] = (int)$row['quantity'];
    }
}

$vencidos = [];
$proximos = [];
foreach ($items as $item) {
    $depto = $item['departamento'] ?: 'SIN DEPARTAMENTO';
    if ((int)$item['dias_restantes'] < 0) {
        $vencidos[$depto][] = $item;
    } else {
        $proximos[$depto][] = $item;
    }
}

$totalVencidos = array_sum(array_map('count', $vencidos));
$totalProximos = array_sum(array_map('count', $proximos));
$hoy = date('d/m/Y');

function formatearLinea($p) {
    $desc  = $p['description'] ?: 'Sin descripción';
    $fecha = date('d/m/y', strtotime($p['fecha_vencimiento']));
    $dias  = (int)$p['dias_restantes'];

    if ($dias < 0) {
        $etiqueta = "venció $fecha (hace " . abs($dias) . "d)";
    } elseif ($dias === 0) {
        $etiqueta = "vence HOY ($fecha)";
    } else {
        $etiqueta = "vence $fecha (faltan {$dias}d)";
    }

    return "• PLU {$p['plu_code']} — {$desc} (x{$p['quantity']}) — {$etiqueta}\n";
}

function botonHecho($p) {
    return [
        'text'          => "✅ Marcar hecho — PLU {$p['plu_code']}",
        'callback_data' => "done:{$p['id']}",
    ];
}

// ── Construir "bloques": cada bloque es un departamento completo ─────
// (encabezado + sus líneas). Los bloques de VENCIDOS llevan botones;
// los de PRÓXIMOS no. Empaquetamos bloques en mensajes sin superar el
// límite de Telegram, sin cortar nunca un departamento a la mitad
// (salvo el caso raro de que un solo departamento ya sea enorme).
function construirBloques($grupoPorDepto, $conBotones) {
    $bloques = [];
    foreach ($grupoPorDepto as $depto => $productos) {
        $texto = "📍 *$depto*\n";
        $botones = [];
        foreach ($productos as $p) {
            $texto .= formatearLinea($p);
            if ($conBotones) {
                $botones[] = [botonHecho($p)]; // una fila por botón
            }
        }
        $bloques[] = ['texto' => $texto, 'botones' => $botones];
    }
    return $bloques;
}

function empaquetarMensajes($bloques, $encabezadoSeccion, $maxLen) {
    $mensajes = []; // cada uno: ['texto' => ..., 'botones' => [...]]
    $textoActual = $encabezadoSeccion;
    $botonesActuales = [];

    foreach ($bloques as $bloque) {
        $candidato = $textoActual . $bloque['texto'];
        if (strlen($candidato) > $maxLen && $textoActual !== $encabezadoSeccion) {
            // Cerrar el mensaje actual y empezar uno nuevo con este bloque
            $mensajes[] = ['texto' => $textoActual, 'botones' => $botonesActuales];
            $textoActual = $encabezadoSeccion . $bloque['texto'];
            $botonesActuales = $bloque['botones'];
        } else {
            $textoActual = $candidato;
            $botonesActuales = array_merge($botonesActuales, $bloque['botones']);
        }
    }
    if ($textoActual !== $encabezadoSeccion || !empty($botonesActuales)) {
        $mensajes[] = ['texto' => $textoActual, 'botones' => $botonesActuales];
    }
    return $mensajes;
}

// Margen para el encabezado "🔔 Alerta... (parte N/M)" que se añade al enviar
$margen = 80;
$maxLenSeccion = $TELEGRAM_MAX_LEN - $margen;

$todosLosMensajes = [];

if ($totalVencidos > 0) {
    $bloquesVencidos = construirBloques($vencidos, true);
    $encabezadoVencidos = "🚨 *VENCIDOS (urgente)*\n";
    $todosLosMensajes = array_merge(
        $todosLosMensajes,
        empaquetarMensajes($bloquesVencidos, $encabezadoVencidos, $maxLenSeccion)
    );
}

if ($totalProximos > 0) {
    $bloquesProximos = construirBloques($proximos, false);
    $encabezadoProximos = "⌛ *PRÓXIMOS A VENCER*\n";
    $todosLosMensajes = array_merge(
        $todosLosMensajes,
        empaquetarMensajes($bloquesProximos, $encabezadoProximos, $maxLenSeccion)
    );
}

$totalPartes = count($todosLosMensajes);

// ── Enviar cada parte a Telegram ──────────────────────────────
function enviarMensajeTelegram($token, $chatId, $texto, $botones) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id'    => $chatId,
        'text'       => $texto,
        'parse_mode' => 'Markdown',
    ];
    if (!empty($botones)) {
        $payload['reply_markup'] = json_encode(['inline_keyboard' => $botones]);
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$httpCode, $response];
}

foreach ($todosLosMensajes as $idx => $msg) {
    $encabezado = "🔔 *Alerta de vencimientos* — $hoy";
    if ($totalPartes > 1) {
        $encabezado .= " (parte " . ($idx + 1) . "/$totalPartes)";
    }
    $texto = $encabezado . "\n" . $msg['texto'];

    [$httpCode, $response] = enviarMensajeTelegram(
        $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID, $texto, $msg['botones']
    );

    $logLine = date('Y-m-d H:i:s') . " | parte " . ($idx + 1) . "/$totalPartes"
        . " | HTTP $httpCode | " . $response . "\n";
    file_put_contents(__DIR__ . '/notify_telegram.log', $logLine, FILE_APPEND);

    if ($totalPartes > 1) {
        usleep(400000);
    }
}