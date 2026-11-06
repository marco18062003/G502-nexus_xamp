
<?php
/**
 * webhook_whatsapp.php - interactive WhatsApp bot.
 * Commands: ayuda, ping, hoy, vencidos, proximos, plu 1234, hecho 1234 [fecha]

 */
 
require_once __DIR__ . '/whatsapp_lib.php';
logMsg($_SERVER['REQUEST_METHOD'] . ' from ' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' | ' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
require_once __DIR__ . '/whatsapp_lib.php';

// ── 1) Meta verification (GET) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_GET['hub_mode'] ?? '') === 'subscribe'
        && hash_equals(WHATSAPP_VERIFY_TOKEN, (string)($_GET['hub_verify_token'] ?? ''))) {
        echo $_GET['hub_challenge'] ?? '';
    } else {
        http_response_code(403);
        echo 'Forbidden';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// ── 2) Incoming messages (POST) ──────────────────────────────
$raw = file_get_contents('php://input');

// Signature check (active only if WHATSAPP_APP_SECRET is set)
if (WHATSAPP_APP_SECRET !== '') {
    $sig = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $exp = 'sha256=' . hash_hmac('sha256', $raw, WHATSAPP_APP_SECRET);
    if (!hash_equals($exp, $sig)) {
        http_response_code(403);
        logMsg('Rejected: invalid signature');
        exit;
    }
}

http_response_code(200); // answer Meta fast

$data = json_decode($raw, true);
$msg  = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
if (!$msg) exit; // status update (sent/delivered/read)

$from = $msg['from'] ?? '';
if (!in_array($from, WHATSAPP_ALLOWED, true)) {
    logMsg("Ignored message from unauthorized number $from");
    exit;
}

if (($msg['type'] ?? '') !== 'text') {
    enviarTexto($from, "Solo entiendo mensajes de texto. Escribe *ayuda* para ver los comandos.");
    exit;
}

$texto = trim(mb_strtolower($msg['text']['body'] ?? ''));
logMsg("from $from: $texto");

try {
    $respuesta = procesarComando($texto);
} catch (Throwable $e) {
    logMsg('ERROR: ' . $e->getMessage());
    $respuesta = "⚠️ Ocurrió un error consultando la base de datos. Intenta de nuevo en un momento.";
}
enviarTexto($from, $respuesta);
exit;

// ── Commands ─────────────────────────────────────────────────
function procesarComando($t) {
    if ($t === '') return "No entendí ese mensaje. Escribe *ayuda* para ver los comandos.";

    if ($t === 'ping') return 'pong ✅';

    if (in_array($t, ['ayuda', 'help', 'menu', 'menú', 'comandos'], true)) {
        return "🤖 *Comandos disponibles:*\n\n"
             . "• *hoy* — vencen hoy\n"
             . "• *vencidos* — ya vencidos\n"
             . "• *proximos* — vencen en los próximos " . DIAS_ANTICIPACION . " días\n"
             . "• *plu 1234* — busca un PLU\n"
             . "• *hecho 1234* — marca como hecho los vencidos de ese PLU\n"
             . "• *hecho 1234 2026-10-03* — marca ese PLU con esa fecha";
    }

    if ($t === 'hoy')      return formatearLista(listaHoy(),      "📅 *Vencen HOY*");
    if ($t === 'vencidos') return formatearLista(listaVencidos(), "🚨 *VENCIDOS (urgente)*");
    if ($t === 'proximos' || $t === 'próximos') {
        return formatearLista(listaProximos(), "⌛ *Próximos a vencer (" . DIAS_ANTICIPACION . " días)*");
    }

    if (preg_match('/^plu\s+(\S+)$/u', $t, $m)) {
        return formatearLista(listaPlu($m[1]), "🔎 *Resultados para PLU {$m[1]}*");
    }

    if (preg_match('/^hecho\s+(\S+)(?:\s+(\S+))?$/u', $t, $m)) {
        $plu   = $m[1];
        $fecha = null;
        if (!empty($m[2])) {
            $fecha = parseFecha($m[2]);
            if (!$fecha) return "Fecha no válida. Usa el formato *2026-10-03* o *03/10/2026*.";
        }
        $n = marcarHecho($plu, $fecha);
        if ($n > 0) return "✅ Listo: $n registro" . ($n === 1 ? '' : 's') . " del PLU *$plu* marcado" . ($n === 1 ? '' : 's') . " como hecho.";
        return $fecha
            ? "No encontré registros pendientes del PLU *$plu* con esa fecha."
            : "No hay registros *vencidos* pendientes del PLU *$plu*. Si aún no vence, escribe la fecha: *hecho $plu 2026-10-03*.";
    }

    return "No entendí ese comando. Escribe *ayuda* para ver las opciones.";
}

function parseFecha($s) {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return $s;
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return null;
}