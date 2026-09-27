<?php
/**
 * notify_whatsapp.php
 * ─────────────────────────────────────────────────────────
 * Revisa la base de datos por productos que vencen en los próximos
 * 5 días (o ya vencidos) y NO han sido marcados como "Hecho", y
 * envía un resumen por WhatsApp usando la Meta WhatsApp Cloud API.
 *
 * Pensado para correr una vez al día vía cron (ver instrucciones abajo).
 * No requiere librerías externas: usa cURL nativo de PHP.
 *
 * ⚠️ IMPORTANTE — Ventana de 24 horas:
 * La Cloud API solo permite mensajes de texto libre si el destinatario
 * te escribió en las últimas 24h. Para un aviso diario automático que
 * nadie "inicia", Meta exige usar una PLANTILLA (template) aprobada.
 * Este script usa texto libre por defecto; más abajo explico cómo
 * cambiar a plantilla si tus mensajes empiezan a fallar por eso.
 */

require_once __DIR__ . '/../../config/db.php';
date_default_timezone_set('America/Bogota');

// ── CONFIGURA ESTOS VALORES ──────────────────────────────────
// Los obtienes en developers.facebook.com > tu App > WhatsApp > API Setup
$WHATSAPP_TOKEN          = getenv('WHATSAPP_TOKEN');           // Access token (temporal o permanente)
$WHATSAPP_PHONE_NUMBER_ID = getenv('WHATSAPP_PHONE_NUMBER_ID'); // ID del número emisor (NO es el número en sí)
$WHATSAPP_TO             = getenv('WHATSAPP_TO');               // Número destino en formato E.164 sin '+', ej: 573001234567
$WHATSAPP_API_VERSION    = 'v20.0';
// ──────────────────────────────────────────────────────────────

if (!$WHATSAPP_TOKEN || !$WHATSAPP_PHONE_NUMBER_ID || !$WHATSAPP_TO) {
    file_put_contents(
        __DIR__ . '/notify_whatsapp.log',
        date('Y-m-d H:i:s') . " | ERROR: faltan variables de entorno WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID / WHATSAPP_TO\n",
        FILE_APPEND
    );
    exit(1);
}

$DIAS_ANTICIPACION = 5; // umbral: avisar cuando falten <= 5 días

$sql = "SELECT plu_code, departamento, description, quantity,
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
    // Nada que avisar hoy — no se envía mensaje para no generar ruido innecesario.
    exit;
}

// ── Agrupar y fusionar duplicados (mismo PLU + fecha + departamento + descripción) ──
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

// Separar en vencidos (urgente) y próximos, cada uno agrupado por departamento
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
// WhatsApp usa *texto* para negrita (un solo asterisco, no doble como Telegram)
$mensaje = "🔔 *Alerta de vencimientos* — $hoy\n";
$mensaje .= "📊 $totalVencidos vencido" . ($totalVencidos === 1 ? '' : 's') . " | $totalProximos por vencer\n";

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

if ($totalVencidos > 0) {
    $mensaje .= "\n🚨 *VENCIDOS (urgente)*\n";
    foreach ($vencidos as $depto => $productos) {
        $mensaje .= "📍 *$depto*\n";
        foreach ($productos as $p) {
            $mensaje .= formatearLinea($p);
        }
    }
}

if ($totalProximos > 0) {
    $mensaje .= "\n⌛ *PRÓXIMOS A VENCER*\n";
    foreach ($proximos as $depto => $productos) {
        $mensaje .= "📍 *$depto*\n";
        foreach ($productos as $p) {
            $mensaje .= formatearLinea($p);
        }
    }
}

// WhatsApp Cloud API limita cada mensaje de texto a 4096 caracteres.
// Si el mensaje es muy largo, lo partimos en varios envíos.
$chunks = str_split($mensaje, 4000);

// ── Enviar a WhatsApp Cloud API ──────────────────────────────
$url = "https://graph.facebook.com/{$WHATSAPP_API_VERSION}/{$WHATSAPP_PHONE_NUMBER_ID}/messages";

foreach ($chunks as $chunk) {
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $WHATSAPP_TO,
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

    // Log simple para depurar si algo falla (revisa este archivo si no llegan los mensajes)
    $logLine = date('Y-m-d H:i:s') . " | HTTP $httpCode | " . $response . "\n";
    file_put_contents(__DIR__ . '/notify_whatsapp.log', $logLine, FILE_APPEND);
}