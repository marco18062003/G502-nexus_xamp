<?php
session_start();

// ------------------------------------------------------------------
// SEGURIDAD: cambia a true cuando tengas tu login y ajusta la variable de sesión
// ------------------------------------------------------------------
$EXIGIR_ADMIN = false;
if ($EXIGIR_ADMIN && empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once '../config/db.php';
require_once '../config/keys.php';

if (empty($conn)) {
    http_response_code(500);
    echo "Error de conexión a la base de datos: No se pudo establecer la conexión.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf'];
session_write_close(); // libera la sesión para que las llamadas AJAX no se encolen

// ------------------------------------------------------------------
// Helpers de base de datos
// ------------------------------------------------------------------
function q_all($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception(mysqli_error($conn)); }
    if ($types !== '') { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) { $rows[] = $row; }
    mysqli_stmt_close($stmt);
    return $rows;
}

function q_exec($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) { throw new Exception(mysqli_error($conn)); }
    if ($types !== '') { mysqli_stmt_bind_param($stmt, $types, ...$params); }
    mysqli_stmt_execute($stmt);
    $out = ['affected' => mysqli_stmt_affected_rows($stmt), 'id' => mysqli_stmt_insert_id($stmt)];
    mysqli_stmt_close($stmt);
    return $out;
}

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ------------------------------------------------------------------
// Estados: se comparan sin importar mayúsculas ni espacios sobrantes
// ------------------------------------------------------------------
function estado_norm($s) { return strtolower(trim((string)$s)); }
function es_final($estado) { return in_array(estado_norm($estado), ['entregado', 'cancelado'], true); }
function es_pagado($estado_pago) {
    return in_array(estado_norm($estado_pago), ['pagado', 'aprobado', 'approved', 'paid'], true);
}

function recalc_total($conn, $id) {
    $r = q_all($conn, "SELECT COALESCE(SUM(cantidad * precio_unitario), 0) AS s FROM detalle_pedido WHERE id_pedido = ?", "i", [$id]);
    $s = (float)$r[0]['s'];
    q_exec($conn, "UPDATE pedidos SET total_pedido = ?, total_final = IF(total_final > 0, ?, total_final) WHERE id = ?", "ddi", [$s, $s, $id]);
    return $s;
}

function norm_ean($e) { return ltrim(trim((string)$e), '0'); }

function ruta_segura($p) {
    $p = trim((string)$p);
    if ($p === '') { return null; }
    if (strpos($p, '..') !== false || substr($p, 0, 2) === '//') { return null; }
    if (preg_match('#^https?://#i', $p)) { return $p; }
    return preg_match('#^/?[A-Za-z0-9_\-./]+$#', $p) ? $p : null;
}

// ------------------------------------------------------------------
// Columnas extra (se crean solas la primera vez; si el hosting no permite ALTER,
// ejecuta el SQL que aparece en el aviso amarillo una sola vez en phpMyAdmin)
//   - detalle_pedido.ean_escaneado : lo que se escaneó en cada producto
//   - pedidos.aprobado_en          : NULL = en revisión, con fecha = aprobado
// ------------------------------------------------------------------
function tiene_col($conn, $tabla, $col) {
    return (bool) q_all($conn, "SHOW COLUMNS FROM `$tabla` LIKE '$col'");
}
function asegurar_col($conn, $tabla, $col, $definicion) {
    if (tiene_col($conn, $tabla, $col)) { return true; }
    try {
        mysqli_query($conn, "ALTER TABLE `$tabla` ADD COLUMN `$col` $definicion");
        return tiene_col($conn, $tabla, $col);
    } catch (Throwable $e) {
        error_log("pre-pedidos: no se pudo crear $tabla.$col: " . $e->getMessage());
        return false;
    }
}
$tiene_scan  = asegurar_col($conn, 'detalle_pedido', 'ean_escaneado', 'VARCHAR(32) NULL DEFAULT NULL');
$tiene_aprob = asegurar_col($conn, 'pedidos', 'aprobado_en', 'DATETIME NULL DEFAULT NULL');

// En revisión = sin aprobar y que no esté entregado, cancelado ni en proceso
function en_revision($estado, $aprobado_en) {
    return empty($aprobado_en) && !es_final($estado) && estado_norm($estado) !== 'en_proceso';
}

// Solo se edita lo que está en revisión
function order_editable($conn, $id) {
    global $tiene_aprob;
    $campo = $tiene_aprob ? 'aprobado_en' : 'NULL AS aprobado_en';
    $r = q_all($conn, "SELECT estado_pedido, $campo FROM pedidos WHERE id = ?", "i", [$id]);
    return $r && en_revision($r[0]['estado_pedido'], $r[0]['aprobado_en']);
}

// ------------------------------------------------------------------
// ENDPOINTS AJAX (mismo archivo)
// ------------------------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action !== '') {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($csrf_token, $_POST['csrf'] ?? '')) {
            json_out(['error' => 'Sesión vencida, recarga la página'], 403);
        }
        $id_pedido = (int)($_POST['id_pedido'] ?? $_GET['id'] ?? 0);
        $no_editable = ['error' => 'Este pedido ya no se puede editar (aprobado, entregado o cancelado)'];

        switch ($action) {

            case 'detalle':
                $ped = q_all($conn, "SELECT * FROM pedidos WHERE id = ?", "i", [$id_pedido]);
                if (!$ped) { json_out(['error' => 'Pedido no encontrado'], 404); }
                $pedido = $ped[0];

                $items = q_all($conn,
                    "SELECT dp.*, COALESCE(NULLIF(dp.ean, ''), d.ean) AS ean_final
                     FROM detalle_pedido dp
                     LEFT JOIN donjorgito1 d ON d.id = dp.id_producto
                     WHERE dp.id_pedido = ? ORDER BY dp.id", "i", [$id_pedido]);
                foreach ($items as &$it) {
                    $it['subtotal'] = $it['cantidad'] * $it['precio_unitario'];
                    $esperado = $it['ean_final'];
                    $leido    = $it['ean_escaneado'] ?? null;
                    if ($leido === null || $leido === '') {
                        $it['escaneo'] = 'pendiente';
                    } else {
                        $it['escaneo'] = ($esperado !== null && $esperado !== '' && norm_ean($leido) === norm_ean($esperado)) ? 'ok' : 'error';
                    }
                }
                unset($it);

                $pedido['comprobante_path'] = ruta_segura($pedido['comprobante_path'] ?? '');

                json_out([
                    'pedido'    => $pedido,
                    'productos' => $items,
                    'editable'  => en_revision($pedido['estado_pedido'], $pedido['aprobado_en'] ?? null),
                    'pagado'    => es_pagado($pedido['estado_pago']),
                ]);

            case 'buscar':
                $q = trim($_GET['q'] ?? '');
                if (strlen($q) < 2) { json_out([]); }
                $like = '%' . addcslashes($q, '%_') . '%';
                json_out(q_all($conn,
                    "SELECT id, producto, caracteristica, ean, value_final
                     FROM donjorgito1
                     WHERE (producto LIKE ? OR caracteristica LIKE ? OR marca LIKE ? OR ean LIKE ?)
                       AND (estado IS NULL OR estado <> 'descontinuado')
                     ORDER BY producto LIMIT 10", "ssss", [$like, $like, $like, $like]));

            case 'add_item':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                $id_producto = (int)($_POST['id_producto'] ?? 0);
                $cant   = max(1, min(9999, (int)($_POST['cantidad'] ?? 1)));
                $precio = max(0, (float)($_POST['precio_unitario'] ?? 0));

                $prod = q_all($conn, "SELECT id, producto, caracteristica, ean FROM donjorgito1 WHERE id = ?", "i", [$id_producto]);
                if (!$prod) { json_out(['error' => 'Producto no encontrado en el catálogo'], 404); }
                $prod = $prod[0];

                $ya = q_all($conn, "SELECT id, cantidad FROM detalle_pedido WHERE id_pedido = ? AND id_producto = ? LIMIT 1", "ii", [$id_pedido, $id_producto]);
                if ($ya) {
                    $nueva = $ya[0]['cantidad'] + $cant;
                    q_exec($conn, "UPDATE detalle_pedido SET cantidad = ?, precio_unitario = ?, total = ? WHERE id = ?",
                           "iddi", [$nueva, $precio, $nueva * $precio, $ya[0]['id']]);
                } else {
                    $ean = ($prod['ean'] === null || $prod['ean'] === '') ? null : $prod['ean'];
                    q_exec($conn, "INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, caracteristica, cantidad, precio_unitario, total, ean)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                           "iissidds", [$id_pedido, $id_producto, $prod['producto'], $prod['caracteristica'], $cant, $precio, $cant * $precio, $ean]);
                }
                json_out(['ok' => true, 'total' => recalc_total($conn, $id_pedido), 'nombre' => $prod['producto']]);

            case 'update_item':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                $cant   = max(1, min(9999, (int)($_POST['cantidad'] ?? 1)));
                $precio = max(0, (float)($_POST['precio_unitario'] ?? 0));
                q_exec($conn, "UPDATE detalle_pedido SET cantidad = ?, precio_unitario = ?, total = ? WHERE id = ? AND id_pedido = ?",
                       "iddii", [$cant, $precio, $cant * $precio, (int)($_POST['id'] ?? 0), $id_pedido]);
                json_out(['ok' => true, 'total' => recalc_total($conn, $id_pedido)]);

            case 'delete_item':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                q_exec($conn, "DELETE FROM detalle_pedido WHERE id = ? AND id_pedido = ?", "ii", [(int)($_POST['id'] ?? 0), $id_pedido]);
                json_out(['ok' => true, 'total' => recalc_total($conn, $id_pedido)]);

            case 'scan_item':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                $id_linea = (int)($_POST['id'] ?? 0);
                $codigo = preg_replace('/[^A-Za-z0-9\-]/', '', trim($_POST['codigo'] ?? ''));
                if ($codigo === '') { json_out(['error' => 'Código vacío'], 400); }

                $l = q_all($conn,
                    "SELECT dp.id, dp.nombre_producto, COALESCE(NULLIF(dp.ean, ''), d.ean) AS esperado
                     FROM detalle_pedido dp LEFT JOIN donjorgito1 d ON d.id = dp.id_producto
                     WHERE dp.id = ? AND dp.id_pedido = ?", "ii", [$id_linea, $id_pedido]);
                if (!$l) { json_out(['error' => 'Producto no encontrado en el pedido'], 404); }
                $esperado = $l[0]['esperado'];

                if ($esperado === null || $esperado === '') {
                    json_out(['status' => 'sin_ean', 'codigo' => $codigo, 'producto' => $l[0]['nombre_producto']]);
                }

                if ($tiene_scan) {
                    q_exec($conn, "UPDATE detalle_pedido SET ean_escaneado = ? WHERE id = ?", "si", [$codigo, $id_linea]);
                }
                $coincide = norm_ean($codigo) === norm_ean($esperado);
                $otro = null;
                if (!$coincide) {
                    $c = q_all($conn, "SELECT producto, caracteristica FROM donjorgito1 WHERE ean = ? LIMIT 1", "s", [$codigo]);
                    $otro = $c ? trim($c[0]['producto'] . ' ' . $c[0]['caracteristica']) : null;
                }
                json_out(['status' => $coincide ? 'ok' : 'error', 'codigo' => $codigo, 'esperado' => $esperado,
                          'producto' => $l[0]['nombre_producto'], 'otro' => $otro]);

            case 'set_ean':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                $id_linea = (int)($_POST['id'] ?? 0);
                $codigo = preg_replace('/[^A-Za-z0-9\-]/', '', trim($_POST['codigo'] ?? ''));
                if ($codigo === '') { json_out(['error' => 'Código vacío'], 400); }
                $l = q_all($conn, "SELECT id_producto FROM detalle_pedido WHERE id = ? AND id_pedido = ?", "ii", [$id_linea, $id_pedido]);
                if (!$l) { json_out(['error' => 'Producto no encontrado en el pedido'], 404); }

                if ($tiene_scan) {
                    q_exec($conn, "UPDATE detalle_pedido SET ean = ?, ean_escaneado = ? WHERE id = ?", "ssi", [$codigo, $codigo, $id_linea]);
                } else {
                    q_exec($conn, "UPDATE detalle_pedido SET ean = ? WHERE id = ?", "si", [$codigo, $id_linea]);
                }
                q_exec($conn, "UPDATE donjorgito1 SET ean = ? WHERE id = ? AND (ean IS NULL OR ean = '')", "si", [$codigo, (int)$l[0]['id_producto']]);
                json_out(['ok' => true]);

            case 'clear_scan':
                if (!order_editable($conn, $id_pedido)) { json_out($no_editable, 400); }
                if ($tiene_scan) {
                    q_exec($conn, "UPDATE detalle_pedido SET ean_escaneado = NULL WHERE id = ? AND id_pedido = ?", "ii", [(int)($_POST['id'] ?? 0), $id_pedido]);
                }
                json_out(['ok' => true]);

            // --- APROBAR: el pedido sale de esta página y pasa a admin_pedidos.php ---
            case 'aprobar':
                if (!$tiene_aprob) {
                    json_out(['error' => 'Falta la columna pedidos.aprobado_en (mira el aviso amarillo de la página)'], 500);
                }
                $n = q_all($conn, "SELECT COUNT(*) AS n FROM detalle_pedido WHERE id_pedido = ?", "i", [$id_pedido]);
                if ((int)$n[0]['n'] === 0) { json_out(['error' => 'El pedido no tiene productos'], 400); }
                // Una sola sentencia: evita aprobar dos veces o aprobar algo ya entregado/cancelado
                $r = q_exec($conn,
                    "UPDATE pedidos SET aprobado_en = NOW()
                     WHERE id = ? AND aprobado_en IS NULL
                       AND LOWER(TRIM(COALESCE(estado_pedido, ''))) NOT IN ('entregado', 'cancelado', 'en_proceso')",
                    "i", [$id_pedido]);
                if ($r['affected'] < 1) { json_out(['error' => 'El pedido ya fue aprobado o ya no se puede aprobar'], 400); }
                json_out(['ok' => true]);

            // --- CANCELAR: cualquier pedido que no esté entregado ni cancelado ---
            case 'cancelar':
                // Una sola sentencia: evita cancelar dos veces o cancelar algo ya entregado
                $r = q_exec($conn,
                    "UPDATE pedidos SET estado_pedido = 'cancelado'
                     WHERE id = ? AND LOWER(TRIM(COALESCE(estado_pedido, ''))) NOT IN ('entregado', 'cancelado')",
                    "i", [$id_pedido]);
                if ($r['affected'] < 1) { json_out(['error' => 'El pedido ya está entregado o cancelado'], 400); }
                json_out(['ok' => true]);

            default:
                json_out(['error' => 'Acción desconocida'], 400);
        }
    } catch (Throwable $e) {
        error_log('pre-pedidos [' . $action . ']: ' . $e->getMessage());
        json_out(['error' => 'Error del servidor: ' . $e->getMessage()], 500);
    }
}

// ------------------------------------------------------------------
// Consulta y tarjetas
// ------------------------------------------------------------------
function render_order_card($pedido, $tab) {
    $fecha = $pedido['fecha_pedido'] ? date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) : '—';
    $total = $pedido['total_final'] > 0 ? $pedido['total_final'] : $pedido['total_pedido'];
    $total_formateado = number_format((float)$total, 0, ',', '.');
    $id = (int)$pedido['id'];
    $pagado = es_pagado($pedido['estado_pago']);
    $en_revision = in_array($tab, ['pendiente', 'pagado', 'otros'], true);

    $card  = '<div class="card order-card h-100">';
    $card .= '<div class="card-header order-card-header status-' . h($tab) . ' d-flex justify-content-between align-items-center gap-2">';
    $card .= '<span>Pedido #' . $id . '</span>';
    $card .= '<span class="badge bg-light text-dark text-truncate">' . h($pedido['metodo_pago'] ?: '—') . ($pagado ? ' · pagado' : '') . '</span>';
    $card .= '</div>';

    $card .= '<div class="card-body">';
    $card .= '<h5 class="card-title text-primary">Total: $' . $total_formateado . '</h5>';
    $card .= '<p class="card-text mb-1">Cliente: <strong>' . h($pedido['nombre_cliente']) . '</strong></p>';
    $card .= '<p class="card-text mb-1 text-muted small">Tel: ' . h($pedido['telefono_cliente']) . ' · ' . $fecha . '</p>';
    if ($tab === 'otros') {
        $card .= '<p class="card-text mb-2 small text-muted">Estado: <code>' . h($pedido['estado_pedido'] ?? 'NULL') . '</code> · Pago: <code>' . h($pedido['estado_pago']) . '</code></p>';
    }
    if ($tab === 'aprobado') {
        $apr = !empty($pedido['aprobado_en']) ? date('d/m/Y H:i', strtotime($pedido['aprobado_en'])) : '—';
        $est = str_replace('_', ' ', estado_norm($pedido['estado_pedido']));
        $card .= '<p class="card-text mb-1 small text-muted">Aprobado: ' . $apr . ' · <span class="badge bg-secondary">' . h($est ?: 'sin estado') . '</span></p>';
    }

    // Orden fijo de botones: Detalle | Aprobar | Cancelar
    // Aprobar solo mientras está en revisión; Cancelar mientras no esté entregado ni cancelado
    $puede_cancelar = ($tab !== 'cancelado') && estado_norm($pedido['estado_pedido']) !== 'entregado';
    $card .= '<div class="d-flex gap-2 mt-3 card-actions">';
    $card .= '<button type="button" class="btn btn-sm btn-outline-secondary view-details-btn flex-fill" data-id="' . $id . '"
                data-bs-toggle="modal" data-bs-target="#orderDetailsModal">
                <i class="fas fa-eye"></i> Detalle</button>';
    if ($en_revision) {
        $card .= '<button type="button" class="btn btn-sm btn-success btn-approve flex-fill" data-id="' . $id . '">
                <i class="fas fa-check"></i> Aprobar</button>';
    }
    if ($puede_cancelar) {
        $card .= '<button type="button" class="btn btn-sm btn-outline-danger btn-cancel-order flex-fill" data-id="' . $id . '" data-pagado="' . ($pagado ? '1' : '0') . '">
                <i class="fas fa-times"></i> Cancelar</button>';
    }
    $card .= '</div>';

    $card .= '</div></div>';
    return $card;
}

function render_tab_content($pedidos, $tab, $vacio, $nota = '') {
    if (!$pedidos) {
        return '<div class="alert alert-light border text-center">' . h($vacio) . '</div>';
    }
    $html = $nota ? '<p class="small text-muted mb-2">' . h($nota) . '</p>' : '';
    $html .= '<div class="row g-3">';
    foreach ($pedidos as $p) {
        $html .= '<div class="col-12 col-md-6 col-xl-4">' . render_order_card($p, $tab) . '</div>';
    }
    return $html . '</div>';
}

// Se traen TODOS los pedidos y se reparten en pestañas (ninguno desaparece):
//   Pre-pedidos / Pagados / Otros -> en revisión (sin aprobar)
//   Aprobados                     -> ya aprobados (incluye los que están en proceso o entregados)
//   Cancelados                    -> cancelados
$campo_aprob = $tiene_aprob ? 'aprobado_en' : 'NULL AS aprobado_en';
$todos = q_all($conn,
    "SELECT id, nombre_cliente, total_pedido, total_final, direccion_cliente, telefono_cliente,
            estado_pedido, fecha_pedido, metodo_pago, estado_pago, $campo_aprob
     FROM pedidos ORDER BY fecha_pedido DESC, id DESC");

$grupos = ['pendiente' => [], 'pagado' => [], 'otros' => [], 'aprobado' => [], 'cancelado' => []];
foreach ($todos as $p) {
    $est = estado_norm($p['estado_pedido']);
    if     ($est === 'cancelado')                                       { $grupos['cancelado'][] = $p; }
    elseif (!en_revision($p['estado_pedido'], $p['aprobado_en']))       { $grupos['aprobado'][]  = $p; }
    elseif (es_pagado($p['estado_pago']))                               { $grupos['pagado'][]    = $p; }
    elseif ($est === 'pendiente')                                       { $grupos['pendiente'][] = $p; }
    else                                                                { $grupos['otros'][]     = $p; }
}

// Aprobados y cancelados crecen con el tiempo: se muestran los más recientes
$LIMITE = 100;
$nota = ['aprobado' => '', 'cancelado' => ''];
foreach (['aprobado', 'cancelado'] as $g) {
    if (count($grupos[$g]) > $LIMITE) {
        $nota[$g] = "Se muestran los $LIMITE más recientes de " . count($grupos[$g]) . '.';
        $grupos[$g] = array_slice($grupos[$g], 0, $LIMITE);
    }
}

$tabs = [
    'pendiente' => ['Pre-pedidos', $grupos['pendiente'], 'No hay pre-pedidos por revisar.', ''],
    'pagado'    => ['Pagados', $grupos['pagado'], 'No hay pedidos pagados por revisar.', ''],
];
if ($grupos['otros']) { $tabs['otros'] = ['Otros', $grupos['otros'], '', '']; }
$tabs['aprobado']  = ['Aprobados', $grupos['aprobado'], 'Aún no hay pedidos aprobados.', $nota['aprobado']];
$tabs['cancelado'] = ['Cancelados', $grupos['cancelado'], 'No hay pedidos cancelados.', $nota['cancelado']];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Revisión de pre-pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f8; }
        .order-card-header { font-weight: 600; color: #fff; }
        .status-pendiente  { background: #f0ad4e; }
        .status-pagado     { background: #0aa2c0; }
        .status-otros      { background: #6f42c1; }
        .status-aprobado   { background: #198754; }
        .status-cancelado  { background: #dc3545; }
        .ean { color: #6c757d; font-variant-numeric: tabular-nums; }
        .nav-pills { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .nav-pills .nav-link { white-space: nowrap; }

        /* Información del cliente / pago */
        .info-card { background: #fff; border: 1px solid #e3e6ea; border-radius: .6rem; padding: .25rem .9rem; height: 100%; }
        .info-card h6 { margin: .6rem 0 .25rem; font-weight: 700; }
        .kv { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: .45rem 0; border-top: 1px solid #f0f2f4; font-size: .9rem; }
        .kv:first-of-type { border-top: 0; }
        .kv .k { color: #6c757d; flex: 0 0 auto; }
        .kv .v { text-align: right; min-width: 0; overflow-wrap: anywhere; font-weight: 500; }

        /* Tabla de productos */
        .tbl-resp .inp-line { width: 90px; margin-left: auto; }
        .scan-inline { min-width: 230px; }
        .tbl-resp tr.line-ok > td    { background: #e9f7ef; }
        .tbl-resp tr.line-error > td { background: #fdecea; }
        .scan-panel { background: #fff; border: 1px solid #0d6efd; border-radius: .5rem; padding: .75rem; }
        #reader { width: 100%; max-width: 420px; margin: 0 auto; }
        .prod-result { cursor: pointer; text-align: left; }
        .card-actions .btn { white-space: nowrap; padding-left: .4rem; padding-right: .4rem; }
        #modalFooter { justify-content: space-between; gap: .75rem; flex-wrap: nowrap; }
        #modalFooter .btn { white-space: nowrap; }
        #modalFooter .tot { font-size: 1.15rem; font-weight: 700; line-height: 1.1; }
        #modalFooter .tot small { display: block; font-size: .72rem; font-weight: 500; color: #6c757d; }

        /* Celular */
        @media (max-width: 767.98px) {
            .container { padding-left: .6rem; padding-right: .6rem; }
            .modal-body { padding: .75rem; overflow-x: hidden; }

            .tbl-resp, .tbl-resp tbody, .tbl-resp tr, .tbl-resp td { display: block; width: 100%; }
            .tbl-resp thead { display: none; }

            /* cada producto = una tarjeta con rejilla de 2 columnas */
            .tbl-resp tr.line {
                display: grid; grid-template-columns: 1fr 1fr; column-gap: .75rem; row-gap: .15rem;
                background: #fff; border: 1px solid #dee2e6; border-left: 4px solid #ced4da;
                border-radius: .6rem; margin-bottom: .75rem; padding: .75rem;
            }
            .tbl-resp tr.line.line-ok    { border-left-color: #198754; background: #f3fbf6; }
            .tbl-resp tr.line.line-error { border-left-color: #dc3545; background: #fff5f5; }
            .tbl-resp tr.line > td { border: 0; padding: 0; min-width: 0; background: transparent !important; }

            .tbl-resp td.prod  { grid-column: 1 / -1; font-weight: 600; font-size: 1rem; text-align: left; }
            .tbl-resp td.c-ean { grid-column: 1 / -1; display: flex; justify-content: space-between; font-size: .85rem; padding: .25rem 0; }
            .tbl-resp td.c-ean::before,
            .tbl-resp td.c-sub::before { content: attr(data-label); color: #6c757d; font-weight: 600; }
            .tbl-resp td.c-scan { grid-column: 1 / -1; margin: .4rem 0 .6rem; text-align: left; }
            .tbl-resp td.c-qty, .tbl-resp td.c-price { display: flex; flex-direction: column; gap: .15rem; }
            .tbl-resp td.c-qty::before, .tbl-resp td.c-price::before { content: attr(data-label); font-size: .75rem; color: #6c757d; font-weight: 600; }
            .tbl-resp td.c-sub { grid-column: 1 / -1; display: flex; justify-content: space-between; font-weight: 700; margin-top: .6rem; padding-top: .5rem; border-top: 1px dashed #ced4da !important; }
            .tbl-resp td.acc   { grid-column: 1 / -1; margin-top: .6rem; }
            .tbl-resp td.acc .btn { width: 100%; }
            .tbl-resp .inp-line { width: 100%; margin: 0; }

            .scan-cell { align-items: stretch !important; }
            .scan-cell > div:first-child { justify-content: flex-start !important; }
            .scan-inline { min-width: 0; width: 100%; }
            .tbl-resp tr.scan-row > td { padding: 0 0 .75rem; border: 0; }
        }
    </style>
</head>
<body>
     

     
<div class="container py-4">
    <a href="https://your-link-here.com" target="_blank" style="display: inline-block; padding: 10px 20px; font-size: 16px; color: white; background-color: #007bff; text-decoration: none; border-radius: 5px;">
  admin_pedidos
</a>
    <h1 class="h3 mb-1">Revisión de pre-pedidos</h1>
    <p class="text-muted small mb-3">Revisa los productos y pulsa <strong>Aprobar</strong> (pasa a <em>admin_pedidos</em> para procesarlo y entregarlo) o <strong>Cancelar</strong>. Aquí también puedes consultar los <strong>Aprobados</strong> y <strong>Cancelados</strong>.</p>

    <?php if (!$tiene_scan || !$tiene_aprob): ?>
    <div class="alert alert-warning">
        No se pudieron crear columnas automáticamente. Ejecuta esto una vez en phpMyAdmin:
        <?php if (!$tiene_aprob): ?><br><code>ALTER TABLE pedidos ADD COLUMN aprobado_en DATETIME NULL DEFAULT NULL;</code><?php endif; ?>
        <?php if (!$tiene_scan): ?><br><code>ALTER TABLE detalle_pedido ADD COLUMN ean_escaneado VARCHAR(32) NULL DEFAULT NULL;</code><?php endif; ?>
    </div>
    <?php endif; ?>

    <ul class="nav nav-pills mb-3 gap-1" role="tablist">
        <?php $first = true; foreach ($tabs as $key => $t): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="pill"
                    data-bs-target="#tab-<?= h($key) ?>" type="button" role="tab">
                <?= h($t[0]) ?> <span class="badge bg-secondary"><?= count($t[1]) ?></span>
            </button>
        </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <div class="tab-content">
        <?php $first = true; foreach ($tabs as $key => $t): ?>
        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= h($key) ?>" role="tabpanel">
            <?= render_tab_content($t[1], $key, $t[2], $t[3] ?? '') ?>
        </div>
        <?php $first = false; endforeach; ?>
    </div>
</div>

<!-- Modal de revisión (pantalla completa en celular) -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-md-down modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detailBox">
                <div class="text-center text-muted py-4">Cargando…</div>
            </div>
            <div class="modal-footer" id="modalFooter"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const CSRF = <?= json_encode($csrf_token) ?>;
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const cop = n => '$' + Number(n || 0).toLocaleString('es-CO', {maximumFractionDigits: 0});
// Fila de información; si no hay valor, no se muestra (menos ruido en el celular)
const kv = (k, v) => v ? `<div class="kv"><span class="k">${k}</span><span class="v">${v}</span></div>` : '';

let currentId = null, editable = false, dirty = false;
let scanner = null, scanLineId = null, scanBusy = false;
let searchResults = [], picked = null, searchTimer = null;
let flash = null;

function reloadKeepTab() {
    try {
        const a = document.querySelector('.nav-pills .nav-link.active');
        if (a) sessionStorage.setItem('adminTab', a.dataset.bsTarget);
    } catch {}
    location.reload();
}
(function restoreTab() {
    try {
        const t = sessionStorage.getItem('adminTab');
        sessionStorage.removeItem('adminTab');
        if (!t) return;
        const b = document.querySelector('.nav-pills [data-bs-target="' + t + '"]');
        if (b) bootstrap.Tab.getOrCreateInstance(b).show();
    } catch {}
})();

async function api(action, data = {}) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('csrf', CSRF);
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch(location.pathname, {method: 'POST', body: fd});
    const j = await r.json().catch(() => ({error: 'Respuesta inválida del servidor'}));
    if (!r.ok) throw new Error(j.error || 'Error');
    return j;
}

// ---------- Detalle del pedido ----------
async function loadDetail() {
    await stopScanner();
    const box = $('detailBox'), foot = $('modalFooter');
    try {
        const r = await fetch('?action=detalle&id=' + encodeURIComponent(currentId));
        if (!r.ok) throw new Error();
        const {pedido: p, productos, editable: ed, pagado} = await r.json();
        editable = ed;

        const total = Number(p.total_final) > 0 ? p.total_final : p.total_pedido;
        const okCount = productos.filter(x => x.escaneo === 'ok').length;
        const pct = productos.length ? Math.round(okCount / productos.length * 100) : 0;

        const scanCell = x => {
            const code = esc(x.ean_escaneado);
            let st = '<span class="text-muted">Sin escanear</span>';
            if (x.escaneo === 'ok')    st = `<span class="text-success fw-semibold"><i class="fas fa-check-circle"></i> ${code}</span>`;
            if (x.escaneo === 'error') st = `<span class="text-danger fw-semibold"><i class="fas fa-times-circle"></i> ${code}</span>`;
            if (!editable) return st;
            return `<div class="d-flex flex-column gap-1 align-items-stretch align-items-md-end scan-cell">
                <div class="d-flex align-items-center gap-2 justify-content-end flex-wrap">${st}
                    ${x.ean_escaneado ? `<button type="button" class="btn btn-sm btn-outline-secondary btn-clear-scan" data-id="${x.id}" title="Borrar lectura"><i class="fas fa-eraser"></i></button>` : ''}
                </div>
                <div class="input-group input-group-sm scan-inline">
                    <input type="text" inputmode="numeric" class="form-control inline-ean" data-id="${x.id}" placeholder="Escribir EAN" aria-label="Escribir el EAN de ${esc(x.nombre_producto)}">
                    <button type="button" class="btn btn-outline-success btn-inline-check" data-id="${x.id}" title="Comprobar el código escrito"><i class="fas fa-check"></i></button>
                    <button type="button" class="btn btn-outline-primary btn-scan" data-id="${x.id}" title="Escanear con la cámara"><i class="fas fa-camera"></i></button>
                </div>
            </div>`;
        };

        const filas = productos.length ? productos.map(x => `
            <tr class="line ${x.escaneo === 'ok' ? 'line-ok' : x.escaneo === 'error' ? 'line-error' : ''}" data-id="${x.id}" data-name="${esc(x.nombre_producto)}">
                <td class="prod">${esc(x.nombre_producto)}${x.caracteristica ? `<br><small class="ean fw-normal">${esc(x.caracteristica)}</small>` : ''}</td>
                <td data-label="EAN" class="c-ean ean">${esc(x.ean_final) || '—'}</td>
                <td data-label="Escáner" class="c-scan">${scanCell(x)}</td>
                <td data-label="Cantidad" class="c-qty text-md-end">${editable ? `<input type="number" min="1" inputmode="numeric" class="form-control form-control-sm inp-line inp-qty" data-id="${x.id}" value="${esc(x.cantidad)}">` : esc(x.cantidad)}</td>
                <td data-label="Precio" class="c-price text-md-end">${editable ? `<input type="number" min="0" step="1" inputmode="numeric" class="form-control form-control-sm inp-line inp-price" data-id="${x.id}" value="${Math.round(x.precio_unitario)}">` : cop(x.precio_unitario)}</td>
                <td data-label="Subtotal" class="c-sub text-md-end">${cop(x.subtotal)}</td>
                ${editable ? `<td class="acc text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-del-line" data-id="${x.id}" title="Quitar del pedido"><i class="fas fa-trash"></i> <span class="d-md-none">Quitar producto</span></button></td>` : ''}
            </tr>`).join('')
            : `<tr><td colspan="7" class="text-center text-muted">Este pedido no tiene productos.</td></tr>`;

        const comp = p.comprobante_path
            ? `<a href="${esc(p.comprobante_path)}" target="_blank" rel="noopener">Ver comprobante</a>` : '';
        const tel = p.telefono_cliente ? `<a href="tel:${esc(p.telefono_cliente)}">${esc(p.telefono_cliente)}</a>` : '';
        const mail = p.email_cliente ? `<a href="mailto:${esc(p.email_cliente)}">${esc(p.email_cliente)}</a>` : '';

        const flashHtml = flash ? `<div class="alert alert-${flash.type} alert-dismissible fade show" role="alert">${flash.html}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>` : '';
        flash = null;

        const readOnly = !editable
            ? `<div class="alert alert-secondary py-2 small"><i class="fas fa-lock"></i> Pedido solo de lectura (ya aprobado, en proceso, entregado o cancelado).</div>` : '';

        const paidWarn = (editable && pagado)
            ? `<div class="alert alert-warning py-2 small"><i class="fas fa-exclamation-triangle"></i> Este pedido ya está <strong>pagado</strong>. Si cambias cantidades o precios, el total cambia pero no lo que el cliente ya pagó.</div>` : '';

        box.innerHTML = `
            ${flashHtml}
            ${readOnly}
            ${paidWarn}
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-user text-muted"></i> Cliente</h6>
                        ${kv('Nombre', esc(p.nombre_cliente))}
                        ${kv('Teléfono', tel)}
                        ${kv('Email', mail)}
                        ${kv('Dirección', esc(p.direccion_cliente))}
                        ${kv('Ciudad', esc(p.ciudad_cliente))}
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="info-card">
                        <h6><i class="fas fa-credit-card text-muted"></i> Pago</h6>
                        ${kv('Método', esc(p.metodo_pago))}
                        ${kv('Estado del pedido', esc(p.estado_pedido))}
                        ${kv('Estado del pago', esc(p.estado_pago))}
                        ${kv('Referencia', esc(p.referencia_pago))}
                        ${kv('Revisión', esc((p.estado_revision || '').replace(/_/g, ' ')))}
                        ${kv('Comprobante', comp)}
                        ${kv('Fecha', esc(p.fecha_pedido))}
                        ${kv('Aprobado', esc(p.aprobado_en))}
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0">Productos</h6>
                        <small class="text-muted">${okCount} de ${productos.length} verificados</small>
                    </div>
                    <div class="progress mb-3" style="height:6px"><div class="progress-bar bg-success" style="width:${pct}%"></div></div>
                    <table class="table table-sm align-middle tbl-resp mb-0">
                        <thead><tr>
                            <th>Producto</th><th>EAN</th><th>Escáner</th>
                            <th class="text-end">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th>
                            ${editable ? '<th></th>' : ''}
                        </tr></thead>
                        <tbody>${filas}</tbody>
                    </table>
                </div>
                ${editable ? `
                <div class="col-12">
                    <div class="card card-body bg-light">
                        <h6>Agregar producto</h6>
                        <input id="prodSearch" class="form-control" placeholder="Buscar por nombre, marca o EAN" autocomplete="off">
                        <div id="prodResults" class="list-group mt-2"></div>
                        <div id="prodPicked" class="mt-2 d-none"></div>
                    </div>
                </div>` : ''}
            </div>`;

        // Pie fijo del modal: total + Aprobar (siempre visible, también en el celular)
        const est = String(p.estado_pedido || '').trim().toLowerCase();
        const canCancel = est !== 'entregado' && est !== 'cancelado';
        foot.innerHTML = `
            <div class="tot"><small>Total</small>${cop(total)}</div>
            <div class="d-flex gap-2">
                ${canCancel ? `<button type="button" class="btn btn-outline-danger btn-cancel-order" data-id="${esc(p.id)}" data-pagado="${pagado ? 1 : 0}">
                    <i class="fas fa-times"></i> Cancelar</button>` : ''}
                ${editable ? `<button type="button" class="btn btn-success btn-approve" data-id="${esc(p.id)}"
                    data-ok="${okCount}" data-n="${productos.length}" ${productos.length ? '' : 'disabled'}>
                    <i class="fas fa-check"></i> Aprobar</button>` : ''}
            </div>`;
        picked = null;
    } catch {
        box.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el pedido.</div>';
        foot.innerHTML = '';
    }
}

async function mutate(action, data, okMsg) {
    try {
        await api(action, {id_pedido: currentId, ...data});
        dirty = true;
        if (okMsg) flash = {type: 'success', html: okMsg};
    } catch (err) {
        flash = {type: 'danger', html: esc(err.message)};
    }
    await loadDetail();
}

// Abrir modal
document.addEventListener('click', e => {
    const btn = e.target.closest('.view-details-btn');
    if (!btn) return;
    currentId = btn.dataset.id;
    dirty = false; flash = null;
    $('modalTitle').textContent = 'Pedido #' + currentId;
    $('detailBox').innerHTML = '<div class="text-center text-muted py-4">Cargando…</div>';
    $('modalFooter').innerHTML = '';
    loadDetail();
});

// Acciones dentro del modal
$('orderDetailsModal').addEventListener('click', e => {
    const del = e.target.closest('.btn-del-line');
    if (del) {
        if (confirm('¿Quitar este producto del pedido?')) mutate('delete_item', {id: del.dataset.id}, 'Producto quitado del pedido.');
        return;
    }

    const res = e.target.closest('.prod-result');
    if (res) { pickProduct(searchResults[res.dataset.i]); return; }

    const add = e.target.closest('#btnAddItem');
    if (add && picked) {
        add.disabled = true;
        const nombre = picked.producto;
        mutate('add_item', {
            id_producto: picked.id,
            cantidad: $('addQty').value || 1,
            precio_unitario: $('addPrice').value || 0
        }, `<strong>${esc(nombre)}</strong> agregado al pedido.`);
        return;
    }

    const sc = e.target.closest('.btn-scan');
    if (sc) { openScanPanel(sc.dataset.id, true); return; }

    const chk = e.target.closest('.btn-inline-check');
    if (chk) { submitInline(chk.dataset.id, chk.closest('.scan-inline').querySelector('.inline-ean').value); return; }

    const clr = e.target.closest('.btn-clear-scan');
    if (clr) { mutate('clear_scan', {id: clr.dataset.id}); return; }

    if (e.target.closest('.btn-scan-close')) { closeScanPanel(); return; }

    const man = e.target.closest('.btn-manual');
    if (man) {
        const inp = man.closest('.scan-panel').querySelector('.manual-ean');
        handleCode(inp.value);
        return;
    }

    const save = e.target.closest('.btn-save-ean');
    if (save) {
        mutate('set_ean', {id: scanLineId, codigo: save.dataset.code}, 'EAN guardado en el producto.');
    }
});

$('orderDetailsModal').addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.classList.contains('manual-ean')) {
        e.preventDefault();
        handleCode(e.target.value);
    } else if (e.target.classList.contains('inline-ean')) {
        e.preventDefault();
        submitInline(e.target.dataset.id, e.target.value);
    }
});

$('orderDetailsModal').addEventListener('change', e => {
    if (!e.target.classList.contains('inp-line')) return;
    const tr = e.target.closest('tr');
    mutate('update_item', {
        id: e.target.dataset.id,
        cantidad: tr.querySelector('.inp-qty').value,
        precio_unitario: tr.querySelector('.inp-price').value
    });
});

// ---------- Buscar producto en el catálogo ----------
$('orderDetailsModal').addEventListener('input', e => {
    if (e.target.id !== 'prodSearch') return;
    clearTimeout(searchTimer);
    const q = e.target.value.trim();
    searchTimer = setTimeout(async () => {
        const list = $('prodResults');
        if (!list) return;
        if (q.length < 2) { list.innerHTML = ''; return; }
        try {
            const r = await fetch('?action=buscar&q=' + encodeURIComponent(q));
            searchResults = await r.json();
            list.innerHTML = searchResults.length
                ? searchResults.map((x, i) => `
                    <button type="button" class="list-group-item list-group-item-action prod-result" data-i="${i}">
                        <span class="fw-semibold">${esc(x.producto)}</span> <small class="text-muted">${esc(x.caracteristica)}</small>
                        <span class="float-end">${cop(x.value_final)}</span>
                    </button>`).join('')
                : '<div class="list-group-item text-muted">No se encontró ningún producto.</div>';
        } catch { list.innerHTML = '<div class="list-group-item text-danger">Error al buscar.</div>'; }
    }, 250);
});

function pickProduct(prod) {
    if (!prod) return;
    picked = prod;
    $('prodResults').innerHTML = '';
    $('prodSearch').value = '';
    const box = $('prodPicked');
    box.classList.remove('d-none');
    box.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5"><strong>${esc(prod.producto)}</strong><br><small class="ean">${esc(prod.caracteristica)} ${prod.ean ? '· EAN ' + esc(prod.ean) : ''}</small></div>
            <div class="col-5 col-md-2"><label class="form-label small mb-0" for="addQty">Cantidad</label><input id="addQty" type="number" min="1" value="1" class="form-control"></div>
            <div class="col-7 col-md-3"><label class="form-label small mb-0" for="addPrice">Precio unitario</label><input id="addPrice" type="number" min="0" step="1" value="${Math.round(prod.value_final)}" class="form-control"></div>
            <div class="col-12 col-md-2"><button type="button" id="btnAddItem" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Agregar</button></div>
        </div>`;
}

// ---------- Escáner de cada producto ----------
async function stopScanner() {
    if (scanner) {
        try { await scanner.stop(); scanner.clear(); } catch {}
        scanner = null;
    }
}

async function closeScanPanel() {
    await stopScanner();
    document.querySelectorAll('.scan-row').forEach(r => r.remove());
    scanLineId = null; scanBusy = false;
}

async function openScanPanel(lineId, camera = true) {
    await closeScanPanel();
    const tr = document.querySelector(`tr.line[data-id="${lineId}"]`);
    if (!tr) return;
    scanLineId = lineId;

    const panel = document.createElement('tr');
    panel.className = 'scan-row';
    panel.innerHTML = `<td colspan="${tr.children.length}">
        <div class="scan-panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Escanear: ${esc(tr.dataset.name)}</strong>
                <button type="button" class="btn-close btn-scan-close" aria-label="Cerrar escáner"></button>
            </div>
            <div id="reader"></div>
            <div class="input-group mt-2">
                <input type="text" inputmode="numeric" class="form-control manual-ean" placeholder="O escribe el EAN">
                <button type="button" class="btn btn-outline-secondary btn-manual">Comprobar</button>
            </div>
            <div class="scan-msg mt-2"></div>
        </div></td>`;
    tr.after(panel);
    panel.scrollIntoView({block: 'nearest', behavior: 'smooth'});
    if (!camera) { panel.querySelector('#reader').remove(); return; }

    if (typeof Html5Qrcode === 'undefined') {
        panelMsg('warning', 'No cargó la librería de la cámara. Escribe el EAN a mano.');
        return;
    }
    try {
        scanner = new Html5Qrcode('reader', {
            formatsToSupport: [Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8,
                               Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E,
                               Html5QrcodeSupportedFormats.CODE_128],
            experimentalFeatures: {useBarCodeDetectorIfSupported: true},
            verbose: false
        });
        await scanner.start({facingMode: 'environment'}, {fps: 10, qrbox: {width: 260, height: 110}}, handleCode, () => {});
    } catch {
        scanner = null;
        panelMsg('danger', 'No se pudo abrir la cámara. Revisa el permiso del navegador (requiere HTTPS) o escribe el EAN a mano.');
    }
}

async function submitInline(lineId, value) {
    await openScanPanel(lineId, false);
    if (!String(value || '').trim()) { panelMsg('warning', 'Escribe el código EAN y vuelve a pulsar el botón verde.'); return; }
    handleCode(value);
}

function panelMsg(type, html) {
    const el = document.querySelector('.scan-row .scan-msg');
    if (el) el.innerHTML = `<div class="alert alert-${type} mb-0 py-2">${html}</div>`;
}

async function handleCode(code) {
    code = String(code || '').trim();
    if (!code || scanBusy || !scanLineId) return;
    scanBusy = true;
    await stopScanner();
    try {
        const r = await api('scan_item', {id_pedido: currentId, id: scanLineId, codigo: code});
        if (r.status === 'ok') {
            navigator.vibrate?.(120);
            dirty = true;
            flash = {type: 'success', html: `<i class="fas fa-check-circle"></i> <strong>${esc(r.producto)}</strong>: el código ${esc(r.codigo)} coincide.`};
            await loadDetail();
        } else if (r.status === 'error') {
            navigator.vibrate?.([80, 60, 80]);
            dirty = true;
            flash = {type: 'danger', html: `<i class="fas fa-times-circle"></i> <strong>${esc(r.producto)}</strong>: no coincide. Se leyó ${esc(r.codigo)}${r.otro ? ' (' + esc(r.otro) + ')' : ''} y se esperaba ${esc(r.esperado)}.`};
            await loadDetail();
        } else if (r.status === 'sin_ean') {
            panelMsg('warning', `Este producto no tiene EAN registrado. Se leyó <strong>${esc(r.codigo)}</strong>.
                <button type="button" class="btn btn-sm btn-dark ms-1 btn-save-ean" data-code="${esc(r.codigo)}">Guardar como su EAN</button>`);
            scanBusy = false;
        }
    } catch (err) {
        panelMsg('danger', esc(err.message));
        scanBusy = false;
    }
}

// Al cerrar el modal: apagar cámara y refrescar los totales de las tarjetas
$('orderDetailsModal').addEventListener('hidden.bs.modal', async () => {
    await closeScanPanel();
    if (dirty) reloadKeepTab();
});

// ---------- CANCELAR (desde la tarjeta o desde el pie del modal) ----------
document.addEventListener('click', async e => {
    const btn = e.target.closest('.btn-cancel-order');
    if (!btn) return;
    const id = btn.dataset.id;
    let msg = `¿Cancelar el pedido #${id}?\nPasará a la pestaña Cancelados y el cliente lo verá como cancelado.`;
    if (btn.dataset.pagado === '1') {
        msg = `⚠️ Este pedido ya está PAGADO. Recuerda devolver el dinero al cliente.\n\n` + msg;
    }
    if (!confirm(msg)) return;
    btn.disabled = true;
    try {
        await api('cancelar', {id_pedido: id});
        dirty = false;
        reloadKeepTab();
    } catch (err) {
        alert('No se pudo cancelar: ' + err.message);
        btn.disabled = false;
    }
});

// ---------- APROBAR (desde la tarjeta o desde el pie del modal) ----------
document.addEventListener('click', async e => {
    const btn = e.target.closest('.btn-approve');
    if (!btn) return;
    const id = btn.dataset.id;
    let msg = `¿Aprobar el pedido #${id}?\nDejará de aparecer aquí y pasará a admin_pedidos.`;
    if (btn.dataset.n !== undefined && btn.dataset.ok !== btn.dataset.n) {
        msg = `Solo ${btn.dataset.ok} de ${btn.dataset.n} productos están verificados con el escáner.\n\n` + msg;
    }
    if (!confirm(msg)) return;
    btn.disabled = true;
    try {
        await api('aprobar', {id_pedido: id});
        dirty = false;
        reloadKeepTab();
    } catch (err) {
        alert('No se pudo aprobar: ' + err.message);
        btn.disabled = false;
    }
});
</script>
</body>
</html>