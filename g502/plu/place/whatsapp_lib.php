<?php
/**
 * whatsapp_lib.php - shared helpers (DB queries, formatting, sending).
 * Included by webhook_whatsapp.php and notify_whatsapp.php.
 */
require_once __DIR__ . '/whatsapp_config.php';
date_default_timezone_set('America/Bogota');

const DIAS_ANTICIPACION = 5;
const MAX_ITEMS = 100;
const MAX_CHARS = 3800;
const PENDIENTE = "(state IS NULL OR TRIM(state) != 'X')";

function logMsg($t) {
    file_put_contents(__DIR__ . '/whatsapp.log', date('Y-m-d H:i:s') . " | $t\n", FILE_APPEND);
}

// Connects only when needed, so "ping" works even if the DB fails.
function db() {
    static $c = null;
    if ($c === null) {
        require __DIR__ . '/../../config/db.php'; // must define $conn
        $c = $conn;
    }
    return $c;
}

// ── Queries ──────────────────────────────────────────────────
// Dates are computed in PHP (Bogota time), not with MySQL's CURDATE().
function consultar($where, $types, $params) {
    $sql = "SELECT plu_code, departamento, description, quantity, fecha_vencimiento, state
            FROM place_expiration
            WHERE fecha_vencimiento IS NOT NULL AND $where
            ORDER BY departamento, fecha_vencimiento ASC";
    $stmt = db()->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $hoy   = new DateTime(date('Y-m-d'));
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $r['hecho'] = strtoupper(trim((string)$r['state'])) === 'X';
        $key = implode('|', [$r['plu_code'], $r['fecha_vencimiento'], $r['departamento'], $r['description'], (int)$r['hecho']]);
        if (isset($items[$key])) {
            $items[$key]['quantity'] += (int)$r['quantity'];
        } else {
            $r['quantity'] = (int)$r['quantity'];
            $r['dias'] = (int)$hoy->diff(new DateTime($r['fecha_vencimiento']))->format('%r%a');
            $items[$key] = $r;
        }
    }
    return array_values($items);
}

function listaVencidos() { return consultar(PENDIENTE . " AND fecha_vencimiento < ?", 's', [date('Y-m-d')]); }
function listaHoy()      { return consultar(PENDIENTE . " AND fecha_vencimiento = ?", 's', [date('Y-m-d')]); }
function listaProximos() {
    return consultar(PENDIENTE . " AND fecha_vencimiento BETWEEN ? AND ?", 'ss',
        [date('Y-m-d'), date('Y-m-d', strtotime('+' . DIAS_ANTICIPACION . ' days'))]);
}
function listaPlu($plu) { return consultar("plu_code = ?", 's', [$plu]); }

// "1234" + optional date -> mark as done (state = 'X'). Returns rows changed.
function marcarHecho($plu, $fecha) {
    if ($fecha) {
        $sql = "UPDATE place_expiration SET state = 'X'
                WHERE plu_code = ? AND fecha_vencimiento = ? AND " . PENDIENTE;
    } else {
        // no date: only already-expired rows
        $sql = "UPDATE place_expiration SET state = 'X'
                WHERE plu_code = ? AND fecha_vencimiento < ? AND " . PENDIENTE;
        $fecha = date('Y-m-d');
    }
    $stmt = db()->prepare($sql);
    $stmt->bind_param('ss', $plu, $fecha);
    $stmt->execute();
    return $stmt->affected_rows;
}

// ── Formatting ───────────────────────────────────────────────
function formatearLinea($p) {
    $desc  = $p['description'] ?: 'Sin descripción';
    $fecha = date('d/m/y', strtotime($p['fecha_vencimiento']));
    $d     = $p['dias'];
    if ($d < 0)        $et = "venció $fecha (hace " . abs($d) . "d)";
    elseif ($d === 0)  $et = "vence HOY ($fecha)";
    else               $et = "vence $fecha (faltan {$d}d)";
    $mark = $p['hecho'] ? ' ✅ hecho' : '';
    return "• PLU {$p['plu_code']} — {$desc} (x{$p['quantity']}) — {$et}{$mark}\n";
}

function formatearLista($items, $titulo) {
    if (!$items) return "$titulo\n\nNo hay productos en esta categoría ✅";
    $total = count($items);
    $items = array_slice($items, 0, MAX_ITEMS);
    $out = "$titulo\n";
    $depto = null;
    foreach ($items as $p) {
        $d = $p['departamento'] ?: 'SIN DEPARTAMENTO';
        if ($d !== $depto) { $out .= "\n📍 *$d*\n"; $depto = $d; }
        $out .= formatearLinea($p);
    }
    if ($total > MAX_ITEMS) $out .= "\nMostrando " . MAX_ITEMS . " de $total.";
    return rtrim($out);
}

// ── Sending ──────────────────────────────────────────────────
function apiPost(array $payload) {
    $ch = curl_init("https://graph.facebook.com/" . WHATSAPP_API_VERSION . "/" . WHATSAPP_PHONE_NUMBER_ID . "/messages");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . trim(WHATSAPP_TOKEN), 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = $res === false ? curl_error($ch) : '';
    curl_close($ch);
    logMsg("send to {$payload['to']} | HTTP $code | " . ($err ?: substr((string)$res, 0, 300)));
    return $code;
}

// Splits by lines (never cuts an emoji or accent in half).
function enviarTexto($to, $mensaje) {
    $chunks = []; $actual = '';
    foreach (explode("\n", $mensaje) as $linea) {
        if ($actual !== '' && mb_strlen($actual . "\n" . $linea) > MAX_CHARS) {
            $chunks[] = $actual; $actual = $linea;
        } else {
            $actual = ($actual === '') ? $linea : $actual . "\n" . $linea;
        }
    }
    if ($actual !== '') $chunks[] = $actual;

    foreach ($chunks as $i => $c) {
        apiPost([
            'messaging_product' => 'whatsapp',
            'to'   => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $c],
        ]);
        if ($i < count($chunks) - 1) usleep(300000);
    }
}