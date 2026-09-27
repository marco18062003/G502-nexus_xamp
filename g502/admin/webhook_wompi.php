<?php
/**
 * webhook_wompi.php
 *
 * Configura esta URL en tu dashboard de Wompi (Developers > Eventos):
 *   https://tudominio.com/g502/webhook_wompi.php
 *
 * Wompi la llama automáticamente cada vez que cambia el estado de una transacción.
 * Aquí es donde de verdad se confirma el pago y se genera la factura para Wompi,
 * sin depender de que el cliente vuelva a la pestaña del navegador.
 */

require_once '../config/db.php';
require_once 'ventas_helper.php';
header('Content-Type: application/json');

// ⚠️ Este es el "Secreto de eventos" (Events secret), NO el de integridad del checkout.
// Se consigue en: Wompi Dashboard > Desarrolladores > Configuración de eventos.
// 👇 REEMPLAZA el texto entre comillas por el secreto real que copiaste
//    del dashboard de Wompi: Desarrolladores > Programadores > Secretos > Eventos
define('WOMPI_EVENTS_SECRET', 'prod_events_zYlVOvYNXUQOQe0OBOkzdl0Jlis9P8aU');

$rawInput = file_get_contents('php://input');
$evento = json_decode($rawInput, true);

if (!$evento || !isset($evento['signature']['checksum'], $evento['signature']['properties'], $evento['timestamp'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload inválido']);
    exit;
}

// --- 1. Verificar la firma (OBLIGATORIO: sin esto, cualquiera podría forjar una "aprobación") ---
$cadena = '';
foreach ($evento['signature']['properties'] as $propiedad) {
    // Cada 'propiedad' es algo como "transaction.id" o "transaction.status".
    // Hay que navegar el array $evento['data'] siguiendo esa ruta con puntos.
    $partes = explode('.', $propiedad);
    $valor = $evento['data'];
    foreach ($partes as $parte) {
        $valor = $valor[$parte] ?? null;
    }
    $cadena .= (string)$valor;
}
$cadena .= (string)$evento['timestamp'];
$cadena .= WOMPI_EVENTS_SECRET;

$checksumCalculado = hash('sha256', $cadena);
$checksumRecibido = $evento['signature']['checksum'];

if (!hash_equals($checksumCalculado, $checksumRecibido)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Firma inválida']);
    exit;
}

// --- 2. Solo nos interesan eventos de transacción ---
$transaction = $evento['data']['transaction'] ?? null;
if (!$transaction) {
    http_response_code(200); // Respondemos 200 igual para que Wompi no reintente
    echo json_encode(['status' => 'ignored', 'message' => 'Evento sin transacción']);
    exit;
}

$referencia = $transaction['reference'];
$estadoTransaccion = $transaction['status']; // APPROVED | DECLINED | VOIDED | ERROR | PENDING

// --- 2b. Bifurcación: ¿es un pedido de la TIENDA ONLINE o una venta del POS? ---
// Distinguimos por el prefijo de la referencia, porque Wompi solo permite
// registrar UNA URL de eventos por cuenta — así que este mismo archivo
// atiende ambos flujos sin necesidad de dos webhooks distintos.
if (strpos($referencia, 'PEDIDO_') === 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, estado_pago FROM pedidos WHERE referencia_pago = ?");
    mysqli_stmt_bind_param($stmt, "s", $referencia);
    mysqli_stmt_execute($stmt);
    $pedido = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$pedido) {
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'message' => 'No hay pedido para esta referencia']);
        exit;
    }

    // Idempotencia: si ya está pagado, no lo proceses de nuevo
    if ($pedido['estado_pago'] === 'pagado') {
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Pedido ya estaba pagado, sin cambios']);
        exit;
    }

    if ($estadoTransaccion === 'APPROVED') {
        $stmtUpd = mysqli_prepare($conn, "UPDATE pedidos SET estado_pago = 'pagado', estado_pedido = 'confirmado' WHERE referencia_pago = ?");
        mysqli_stmt_bind_param($stmtUpd, "s", $referencia);
        mysqli_stmt_execute($stmtUpd);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'pedido_id' => $pedido['id']]);
    } elseif (in_array($estadoTransaccion, ['DECLINED', 'VOIDED', 'ERROR'])) {
        $stmtUpd = mysqli_prepare($conn, "UPDATE pedidos SET estado_pago = 'fallido' WHERE referencia_pago = ?");
        mysqli_stmt_bind_param($stmtUpd, "s", $referencia);
        mysqli_stmt_execute($stmtUpd);

        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Pedido marcado como ' . $estadoTransaccion]);
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Estado intermedio, sin acción']);
    }

    mysqli_close($conn);
    exit;
}

// --- 3. (Flujo POS) Buscar la venta pendiente asociada a esta referencia ---
$stmt = mysqli_prepare($conn, "SELECT * FROM venta_pendiente WHERE referencia_unica = ?");
mysqli_stmt_bind_param($stmt, "s", $referencia);
mysqli_stmt_execute($stmt);
$ventaPendiente = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$ventaPendiente) {
    // Puede pasar con referencias del flujo "legacy" (sin ref) - no hay carrito que recuperar.
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'No hay venta_pendiente para esta referencia']);
    exit;
}

// --- 4. Idempotencia: si ya la procesamos, no la proceses de nuevo ---
// (Wompi puede reenviar el mismo evento varias veces)
if ($ventaPendiente['estado'] !== 'PENDIENTE') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Ya estaba procesada, sin cambios']);
    exit;
}

if ($estadoTransaccion === 'APPROVED') {
    try {
        $productos = json_decode($ventaPendiente['carrito_json'], true);

        $resultado = registrarVenta(
            $conn,
            $productos,
            (float)$ventaPendiente['total'],
            'virtual',
            '0', // no hay cambio en pago virtual
            (int)$ventaPendiente['cajero_id']
        );

        // Marcamos la venta pendiente y el registro de wompi1 como procesados
        $stmtUpd = mysqli_prepare($conn, "UPDATE venta_pendiente SET estado = 'PROCESADA', numero_factura = ? WHERE referencia_unica = ?");
        mysqli_stmt_bind_param($stmtUpd, "ss", $resultado['numero_factura'], $referencia);
        mysqli_stmt_execute($stmtUpd);

        $stmtWompi = mysqli_prepare($conn, "UPDATE wompi1 SET estado_pago = 'APROBADO' WHERE referencia_unica = ?");
        mysqli_stmt_bind_param($stmtWompi, "s", $referencia);
        mysqli_stmt_execute($stmtWompi);

        http_response_code(200);
        echo json_encode(['status' => 'success', 'numero_factura' => $resultado['numero_factura']]);
    } catch (Exception $e) {
        // Si falla el registro de la venta, NO marcamos como procesada:
        // así, si reintentas manualmente o Wompi reenvía el evento, se puede volver a intentar.
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif (in_array($estadoTransaccion, ['DECLINED', 'VOIDED', 'ERROR'])) {
    $stmtUpd = mysqli_prepare($conn, "UPDATE venta_pendiente SET estado = 'FALLIDA' WHERE referencia_unica = ?");
    mysqli_stmt_bind_param($stmtUpd, "s", $referencia);
    mysqli_stmt_execute($stmtUpd);

    $stmtWompi = mysqli_prepare($conn, "UPDATE wompi1 SET estado_pago = ? WHERE referencia_unica = ?");
    mysqli_stmt_bind_param($stmtWompi, "ss", $estadoTransaccion, $referencia);
    mysqli_stmt_execute($stmtWompi);

    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Transacción marcada como ' . $estadoTransaccion]);
} else {
    // PENDING u otro estado intermedio: no hacemos nada, esperamos el próximo evento.
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Estado intermedio, sin acción']);
}

mysqli_close($conn);