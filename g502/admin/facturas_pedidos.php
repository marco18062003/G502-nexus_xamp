<?php
// facturas_pedidos.php — PEDIDOS WEB COBRADOS: pago online aprobado (estado_pago = 'pagado') + contraentrega ya entregada
// Archivo INDEPENDIENTE: utilidades + listado + factura (facturas_pedidos.php?ticket=ID).
require_once 'seguridad_admin.php';   // session_start + rol admin + $conn

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------
const TABLA_PEDIDOS = 'pedidos';
const TABLA_DETALLE = 'detalle_pedido';
const ESTADO_PAGADO = 'pagado';   // valor de pedidos.estado_pago que significa "pago aprobado"
const IVA_RATE      = 0.19;
const METODO_EFECTIVO   = 'efectivo';    // metodo_pago de los pedidos contraentrega
const ESTADO_ENTREGADO  = 'entregado';   // estado_pedido cuando el cliente ya recibió (y pagó en efectivo)

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 0, ',', '.'); }
function fdate(?string $d, string $fmt = 'd/m/Y H:i'): string { return $d ? date($fmt, strtotime($d)) : '—'; }
function validDate(string $d): string {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return ($dt && $dt->format('Y-m-d') === $d) ? $d : '';
}

function run(mysqli $c, string $sql, string $types = '', array $params = []): mysqli_result {
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    return $st->get_result();
}
function fetchOne(mysqli $c, string $sql, string $types = '', array $params = []): ?array {
    return run($c, $sql, $types, $params)->fetch_assoc() ?: null;
}

/** Muestra el error real solo en localhost; en producción solo un mensaje genérico */
function dbFail(Throwable $ex, string $where, bool $fragment = false) {
    error_log("$where: " . $ex->getMessage());
    http_response_code(500);
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    $msg = 'No se pudo cargar la información.' . ($local ? '<br><b>Detalle (solo local):</b> ' . e($ex->getMessage()) : '');
    echo $fragment ? '<div class="alert alert-danger mb-0">' . $msg . '</div>' : $msg;
    exit;
}

/**
 * Detecta los nombres REALES de las columnas de `pedidos`.
 * Así la página no se rompe si una columna se llama distinto a lo esperado.
 * Los nombres devueltos salen de SHOW COLUMNS (no del usuario), por eso son seguros en SQL.
 */
function resolveCols(mysqli $c): array {
    $candidates = [
        'id'                 => ['id'],
        'estado_pago'        => ['estado_pago'],
        'pagado_en'          => ['pagado_en'],                 // OPCIONAL: si no existe se usa fecha_pedido (ver migracion_pagado_en.sql)
        'aprobado_en'        => ['aprobado_en'],
        'estado_pedido'      => ['estado_pedido'],
        'nombre'             => ['nombre_cliente', 'cliente_nombre', 'nombre_completo', 'nombre', 'cliente'],
        'email'              => ['email', 'correo', 'email_cliente', 'correo_cliente'],
        'telefono'           => ['telefono', 'celular', 'telefono_cliente'],
        'direccion'          => ['direccion_cliente', 'direccion', 'direccion_envio', 'direccion_entrega'],
        'ciudad'             => ['ciudad_cliente', 'ciudad'],
        'total_final'        => ['total_final'],
        'total_pedido'       => ['total_pedido'],
        'fecha'              => ['fecha_pedido', 'fecha', 'creado_en', 'created_at', 'fecha_creacion'],
        'metodo_pago'        => ['metodo_pago', 'metodo', 'forma_pago'],
        'referencia'         => ['referencia_pago', 'referencia', 'ref_pago', 'wompi_ref', 'referencia_wompi'],
        'comprobante'        => ['comprobante_path', 'comprobante', 'comprobante_url'],
        'estado_comprobante' => ['estado_revision', 'estado_comprobante'],
    ];
    $existing = [];
    $r = $c->query('SHOW COLUMNS FROM `' . TABLA_PEDIDOS . '`');
    while ($row = $r->fetch_assoc()) $existing[strtolower($row['Field'])] = $row['Field'];

    $map = [];
    foreach ($candidates as $key => $names) {
        $map[$key] = null;
        foreach ($names as $n) if (isset($existing[$n])) { $map[$key] = $existing[$n]; break; }
    }
    return [$map, array_values($existing)];
}

/** Columna lista para SQL (alias p) o NULL si no existe */
function colSql(array $C, string $key): string { return $C[$key] ? '`p`.`' . $C[$key] . '`' : 'NULL'; }

/** Valor de una fila usando el mapa de columnas */
function val(array $row, array $C, string $key, $default = '') {
    return ($C[$key] && isset($row[$C[$key]])) ? $row[$C[$key]] : $default;
}

/** Total SQL: total_final si es > 0; si no, total_pedido (total_final queda en 0.00 en pedidos sin cerrar) */
function totalSql(array $C): string {
    if ($C['total_final'] && $C['total_pedido']) return 'COALESCE(NULLIF(`p`.`' . $C['total_final'] . '`, 0), `p`.`' . $C['total_pedido'] . '`)';
    if ($C['total_pedido']) return '`p`.`' . $C['total_pedido'] . '`';
    if ($C['total_final'])  return '`p`.`' . $C['total_final'] . '`';
    return 'NULL';
}
function totalVal(array $row, array $C): float {
    $f = (float)val($row, $C, 'total_final', 0);
    return $f > 0 ? $f : (float)val($row, $C, 'total_pedido', 0);
}

/**
 * Condición SQL de "pedido cobrado".
 *  - 'pagado'        : estado_pago = 'pagado' (Wompi / comprobante aprobado)
 *  - 'contraentrega' : metodo_pago = efectivo AND estado_pedido = entregado
 *  - 'todos'         : cualquiera de las dos
 * Los pedidos en efectivo NUNCA pasan a 'pagado' (quedan 'no_aplica'), por eso se cuentan al entregarse.
 */
function cobradoSql(array $C, string $modo = 'todos'): string {
    $paid = colSql($C, 'estado_pago') . " = '" . ESTADO_PAGADO . "'";
    $cash = ($C['metodo_pago'] && $C['estado_pedido'])
        ? '(' . colSql($C, 'metodo_pago') . " = '" . METODO_EFECTIVO . "' AND " . colSql($C, 'estado_pedido') . " = '" . ESTADO_ENTREGADO . "')"
        : null;
    if ($modo === 'pagado')        return $paid;
    if ($modo === 'contraentrega') return $cash ?? '0';
    return $cash ? "($paid OR $cash)" : $paid;
}
function esCobrado(array $row, array $C): bool {
    if (val($row, $C, 'estado_pago') === ESTADO_PAGADO) return true;
    return val($row, $C, 'metodo_pago') === METODO_EFECTIVO && val($row, $C, 'estado_pedido') === ESTADO_ENTREGADO;
}
/** Fecha de cobro: pagado_en; si no hay, aprobado_en; si no, fecha del pedido (aproximada) */
function fechaCobroSql(array $C): string {
    $parts = array_values(array_filter([$C['pagado_en'] ? colSql($C, 'pagado_en') : null,
                                        $C['aprobado_en'] ? colSql($C, 'aprobado_en') : null,
                                        $C['fecha'] ? colSql($C, 'fecha') : null]));
    return !$parts ? 'NULL' : (count($parts) === 1 ? $parts[0] : 'COALESCE(' . implode(', ', $parts) . ')');
}
function fechaCobroVal(array $row, array $C): ?string {
    foreach (['pagado_en', 'aprobado_en', 'fecha'] as $k) { $v = val($row, $C, $k, null); if ($v) return $v; }
    return null;
}

/** Solo se enlazan comprobantes dentro de ../uploads/comprobantes/ (evita javascript: y rutas raras) */
function comprobanteUrl(?string $p): ?string {
    if (!$p || !str_starts_with($p, '../uploads/comprobantes/') || str_contains(substr($p, 3), '..')) return null;
    return $p;
}

function qty(float $n): string { return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ','); }
function fail(int $code, string $msg) {
    http_response_code($code);
    echo '<div class="alert alert-danger mb-0">' . e($msg) . '</div>';
    exit;
}

// =====================================================================
// MODO FACTURA: facturas_pedidos.php?ticket=ID  ->  devuelve solo el fragmento HTML (va al modal)
// =====================================================================
if (isset($_GET['ticket'])) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');

    $id = filter_var($_GET['ticket'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) fail(400, 'ID no válido.');

try {
    [$C] = resolveCols($conn);
    if (!$C['id'] || !$C['estado_pago']) fail(500, 'Configuración de columnas incompleta en la tabla de pedidos.');

    $me = fetchOne($conn, "SELECT * FROM mefacturaciong502 WHERE id = 1") ?? [];

    $p = fetchOne($conn, "SELECT p.* FROM `" . TABLA_PEDIDOS . "` p WHERE " . colSql($C, 'id') . " = ?", 'i', [$id]);
    if (!$p) fail(404, 'Pedido inexistente.');

    // Solo se factura lo que está pagado/aprobado
    if (!esCobrado($p, $C)) fail(403, 'Este pedido aún no está cobrado (ni pago aprobado ni contraentrega entregada).');

    // Productos: mismo producto + mismo precio => una fila. El total de línea se calcula (la columna `total` de detalle viene en 0).
    $items = run($conn,
        "SELECT MAX(dd.nombre_producto) AS nombre_producto,
                MAX(dd.caracteristica)  AS caracteristica,
                COALESCE(NULLIF(NULLIF(MAX(dd.ean), ''), '0'), MAX(d.ean)) AS referencia,
                SUM(dd.cantidad)        AS total_cantidad,
                dd.precio_unitario,
                SUM(dd.cantidad * dd.precio_unitario) AS total_subtotal
         FROM `" . TABLA_DETALLE . "` dd
         LEFT JOIN donjorgito1 d ON dd.id_producto = d.id
         WHERE dd.id_pedido = ?
         GROUP BY dd.id_producto, dd.precio_unitario
         ORDER BY MAX(dd.nombre_producto)",
        'i', [$id]
    )->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $ex) {
    dbFail($ex, 'get_ticket_pedido.php', true);
}

// ---- Totales ----
$sumaLineas  = array_sum(array_map(fn($i) => (float)$i['total_subtotal'], $items));
$totalPedido = totalVal($p, $C);
$granTotal   = $totalPedido > 0 ? $totalPedido : $sumaLineas;           // si el total guardado es 0, se usa la suma de productos
$descuadre   = $items && $totalPedido > 0 && abs($totalPedido - $sumaLineas) > 1;
$subtotal    = round($granTotal / (1 + IVA_RATE));                        // COP sin centavos
$iva         = $granTotal - $subtotal;

$numero  = 'PED-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
$pagadoEn = val($p, $C, 'pagado_en', null) ?: val($p, $C, 'aprobado_en', null);
$esContra = val($p, $C, 'estado_pago') !== ESTADO_PAGADO;   // cobrado en la entrega
$comp    = comprobanteUrl(val($p, $C, 'comprobante', null));
?>
<div class="invoice-wrap">
<div class="invoice-paper">

    <div class="row border-bottom pb-3 mb-3">
        <div class="col-7">
            <h4 class="fw-bold m-0"><?= e($me['mi_razon_social'] ?? '') ?></h4>
            <?php if (!empty($me['mi_nit'])): ?>
                <div class="small">NIT: <?= e($me['mi_nit']) ?><?= isset($me['mi_dv']) ? '-' . e($me['mi_dv']) : '' ?></div>
            <?php endif; ?>
            <?php if (!empty($me['mi_direccion'])): ?><div class="small">Dirección: <?= e($me['mi_direccion']) ?></div><?php endif; ?>
        </div>
        <div class="col-5 text-end">
            <div class="border p-2">
                <div class="fw-bold"><?= $esContra ? 'COMPROBANTE DE PEDIDO ENTREGADO (CONTRAENTREGA)' : 'COMPROBANTE DE PEDIDO PAGADO' ?></div>
                <div class="fs-4 fw-bold">No. <?= e($numero) ?></div>
                <div class="small text-muted" style="font-size:.7rem;">Documento sin validez tributaria</div>
            </div>
        </div>
    </div>

    <div class="row mb-2 small">
        <div class="col-4"><strong>Pedido:</strong> <?= e(fdate(val($p, $C, 'fecha', null))) ?></div>
        <div class="col-4"><strong><?= $esContra ? 'Entregado/cobrado' : 'Pagado' ?>:</strong> <?= $pagadoEn ? e(fdate($pagadoEn)) : '<span class="text-muted">sin fecha registrada</span>' ?></div>
        <div class="col-4"><strong>Método:</strong> <?= e(val($p, $C, 'metodo_pago', '—')) ?></div>
    </div>
    <?php if ($ref = val($p, $C, 'referencia')): ?>
        <div class="small mb-2"><strong>Referencia de pago:</strong> <span class="text-break"><?= e($ref) ?></span></div>
    <?php endif; ?>

    <div class="border p-2 mb-3" style="font-size:.85rem;">
        <div class="fw-bold border-bottom mb-1">CLIENTE</div>
        <div class="fw-bold"><?= e(val($p, $C, 'nombre', '—')) ?></div>
        <?php if ($v = val($p, $C, 'email')): ?><div><?= e($v) ?></div><?php endif; ?>
        <?php if ($v = val($p, $C, 'telefono')): ?><div>Tel: <?= e($v) ?></div><?php endif; ?>
        <div><?= e(trim(val($p, $C, 'direccion') . ' ' . val($p, $C, 'ciudad'))) ?></div>
    </div>

    <table class="table-main">
        <thead>
            <tr>
                <th style="width:12%;">REF</th>
                <th style="width:43%;">DESCRIPCIÓN</th>
                <th style="width:10%;" class="text-center">CANT.</th>
                <th style="width:15%;" class="text-end">UNITARIO</th>
                <th style="width:20%;" class="text-end">TOTAL</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Este pedido no tiene productos registrados.</td></tr>
        <?php else: foreach ($items as $i): ?>
            <tr>
                <td><?= e($i['referencia'] ?? 'N/A') ?></td>
                <td>
                    <div class="fw-bold"><?= e($i['nombre_producto']) ?></div>
                    <?php if (!empty($i['caracteristica'])): ?><div class="text-muted small"><?= e($i['caracteristica']) ?></div><?php endif; ?>
                </td>
                <td class="text-center"><?= qty((float)$i['total_cantidad']) ?></td>
                <td class="text-end"><?= money((float)$i['precio_unitario']) ?></td>
                <td class="text-end fw-bold"><?= money((float)$i['total_subtotal']) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="row justify-content-end mt-3">
        <div class="col-5">
            <table class="table table-sm border">
                <tr><td>SUBTOTAL</td><td class="text-end"><?= money($subtotal) ?></td></tr>
                <tr><td>IVA <?= (int)round(IVA_RATE * 100) ?>%</td><td class="text-end"><?= money($iva) ?></td></tr>
                <tr class="fw-bold bg-light"><td>TOTAL COP</td><td class="text-end"><?= money($granTotal) ?></td></tr>
            </table>
        </div>
    </div>

    <?php if ($descuadre): ?>
        <div class="alert alert-warning small py-2">
            El total guardado del pedido (<?= money($totalPedido) ?>) no coincide con la suma de los productos (<?= money($sumaLineas) ?>).
            Puede incluir domicilio o descuentos; verifica antes de entregar.
        </div>
    <?php endif; ?>

    <?php if ($comp): ?>
        <div class="small text-muted d-print-none mt-2">
            <a href="<?= e($comp) ?>" target="_blank" rel="noopener"><i class="fas fa-paperclip"></i> Ver comprobante de pago adjunto</a>
        </div>
    <?php endif; ?>

</div>
</div>
<?php
    exit;
}

// =====================================================================
// MODO LISTADO
// =====================================================================
try {
    [$C, $allCols] = resolveCols($conn);

    $faltan = array_keys(array_filter(['id' => $C['id'], 'estado_pago' => $C['estado_pago']], fn($v) => !$v));
    if ($faltan) {
        http_response_code(500);
        exit('A la tabla ' . e(TABLA_PEDIDOS) . ' le falta la columna: <b>' . e(implode(', ', $faltan)) .
             '</b>.<br>Columnas encontradas: ' . e(implode(', ', $allCols)));
    }

    $cId  = colSql($C, 'id');
    $cEst = colSql($C, 'estado_pago');
    $tienePagadoEn = (bool)$C['pagado_en'];
    $cPag     = fechaCobroSql($C);          // pagado_en → aprobado_en → fecha_pedido
    $cPagReal = colSql($C, 'pagado_en');    // solo la fecha de pago real
    $cTot = totalSql($C);
    $hayTotal = $cTot !== 'NULL';
    $lblFecha = 'Fecha de cobro';
    $cMet = colSql($C, 'metodo_pago');
    $T    = '`' . TABLA_PEDIDOS . '` p';
    $PAID = "'" . ESTADO_PAGADO . "'";

    // ---------- Filtros ----------
    $q         = trim($_GET['q'] ?? '');
    $metodo    = trim($_GET['metodo'] ?? '');
    $desde     = validDate($_GET['desde'] ?? '');
    $hasta     = validDate($_GET['hasta'] ?? '');
    $soloFecha = isset($_GET['solo_fecha']);
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $modo      = in_array($_GET['modo'] ?? '', ['pagado', 'contraentrega'], true) ? $_GET['modo'] : 'todos';
    $filters   = ['q' => $q, 'modo' => $modo === 'todos' ? '' : $modo, 'metodo' => $metodo, 'desde' => $desde, 'hasta' => $hasta, 'solo_fecha' => $soloFecha ? '1' : ''];

    $where  = [cobradoSql($C, $modo)];
    $types  = '';
    $params = [];

    $searchCols = [];
    foreach (['id', 'nombre', 'email', 'telefono', 'ciudad', 'referencia'] as $k) if ($C[$k]) $searchCols[] = colSql($C, $k);
    if ($q !== '' && $searchCols) {
        $like  = '%' . addcslashes($q, '%_\\') . '%';
        $parts = array_map(fn($c) => "CAST($c AS CHAR) LIKE ?", $searchCols);
        $where[] = '(' . implode(' OR ', $parts) . ')';
        $types  .= str_repeat('s', count($parts));
        $params  = array_merge($params, array_fill(0, count($parts), $like));
    }
    if ($metodo !== '' && $C['metodo_pago']) { $where[] = "$cMet = ?"; $types .= 's'; $params[] = $metodo; }
    if ($desde !== '') { $where[] = "$cPag >= ?"; $types .= 's'; $params[] = $desde . ' 00:00:00'; }
    if ($hasta !== '') { $where[] = "$cPag < ?";  $types .= 's'; $params[] = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00'; }
    if ($soloFecha && $tienePagadoEn) { $where[] = "$cPagReal IS NOT NULL"; }
    $whereSql = ' WHERE ' . implode(' AND ', $where);
    $orderSql = " ORDER BY ($cPag IS NULL), $cPag DESC, $cId DESC";

    // ---------- Exportar CSV (con los mismos filtros) ----------
    if (isset($_GET['export'])) {
        $res = run($conn, "SELECT p.* FROM $T $whereSql $orderSql", $types, $params);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pedidos_pagados_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";   // BOM para que Excel respete tildes
        $out = fopen('php://output', 'w');
        fputcsv($out, ['pedido', 'cliente', 'email', 'telefono', 'metodo', 'referencia', 'total', 'fecha_cobro']);
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [val($r, $C, 'id'), val($r, $C, 'nombre'), val($r, $C, 'email'), val($r, $C, 'telefono'),
                           val($r, $C, 'metodo_pago'), val($r, $C, 'referencia'), totalVal($r, $C), fechaCobroVal($r, $C)]);
        }
        exit;
    }

    // ---------- Resumen ----------
    $sum   = fetchOne($conn, "SELECT COUNT(*) AS n, IFNULL(SUM($cTot), 0) AS s FROM $T $whereSql", $types, $params);
    $count = (int)$sum['n'];
    $suma  = (float)$sum['s'];

    $porMetodo = [];
    if ($C['metodo_pago']) {
        $porMetodo = run($conn, "SELECT $cMet AS m, COUNT(*) AS n, IFNULL(SUM($cTot), 0) AS s FROM $T $whereSql GROUP BY $cMet ORDER BY s DESC", $types, $params)->fetch_all(MYSQLI_ASSOC);
    }
    $metodos = [];
    if ($C['metodo_pago']) {
        $metodos = array_column(run($conn, "SELECT DISTINCT $cMet AS m FROM $T WHERE " . cobradoSql($C, 'todos') . " AND $cMet IS NOT NULL ORDER BY 1")->fetch_all(MYSQLI_ASSOC), 'm');
    }

    // Calidad de datos (sin filtros)
    $cal = ['sin_fecha' => 0, 'fecha_sin_pago' => 0];
    if ($tienePagadoEn) {
        $cal = $conn->query(
            "SELECT SUM($cEst = $PAID AND $cPagReal IS NULL) AS sin_fecha,
                    SUM(COALESCE($cEst, '') <> $PAID AND $cPagReal IS NOT NULL) AS fecha_sin_pago
             FROM $T"
        )->fetch_assoc();
    }

    // ---------- Página ----------
    $perPage = 25;
    $pages   = max(1, (int)ceil($count / $perPage));
    $page    = min($page, $pages);
    $offset  = ($page - 1) * $perPage;
    $res = run($conn, "SELECT p.* FROM $T $whereSql $orderSql LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$perPage, $offset]));
} catch (mysqli_sql_exception $ex) {
    dbFail($ex, 'facturas_pedidos.php');
}

function qs(array $filters, array $over = []): string {
    $all = array_merge($filters, $over);
    return '?' . http_build_query(array_filter($all, fn($v) => $v !== '' && $v !== null && $v !== 1));   // omite vacíos y page=1
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos cobrados - G502-NEXUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card-factura { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,.1); }
        .kpi { border-radius: 14px; border: none; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        .kpi .v { font-size: 1.4rem; font-weight: 800; }

        .invoice-wrap { overflow-x: auto; }
        .invoice-paper { width: 216mm; min-height: 279mm; padding: 10mm; margin: 0 auto; background: #fff; color: #000; }
        .invoice-paper table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        .invoice-paper .table-main th { border-bottom: 2px solid #000; padding: 6px 4px; font-size: .8rem; }
        .invoice-paper .table-main td { border-bottom: 1px solid #ddd; padding: 6px 4px; font-size: .85rem; }
        .invoice-paper thead { display: table-header-group; }
        .invoice-paper tr { page-break-inside: avoid; }

        @media print {
            @page { size: letter; margin: 8mm; }
            body > *:not(#modalTicket) { display: none !important; }
            #modalTicket { position: static !important; display: block !important; opacity: 1 !important; overflow: visible !important; }
            .modal-dialog { max-width: 100% !important; margin: 0 !important; transform: none !important; }
            .modal-content { border: 0 !important; }
            .modal-header, .modal-footer { display: none !important; }
            #contenido-ticket { padding: 0 !important; }
            .invoice-wrap { overflow: visible !important; }
            .invoice-paper { width: 100%; min-height: 0; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <ul class="nav nav-pills mb-4">
        <li class="nav-item"><a class="nav-link" href="basefacturas.php"><i class="fas fa-cash-register"></i> Ventas POS</a></li>
        <li class="nav-item"><a class="nav-link active" href="facturas_pedidos.php"><i class="fas fa-globe"></i> Pedidos cobrados (web)</a></li>
    </ul>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-circle-check text-success"></i> Pedidos cobrados (web)</h2>
        <a href="<?= e(qs($filters, ['export' => '1'])) ?>" class="btn btn-outline-success"><i class="fas fa-file-csv"></i> Exportar CSV</a>
    </div>

    <?php if (!$tienePagadoEn): ?>
        <div class="alert alert-info py-2 small">
            <i class="fas fa-circle-info"></i>
            Tu tabla <code>pedidos</code> <b>no tiene la columna <code>pagado_en</code></b>, así que la fecha de cobro es <b>aproximada</b> (<code>aprobado_en</code> o, si no hay, la fecha del pedido).
            Ejecuta <code>migracion_pagado_en.sql</code> y haz que tu flujo de pago guarde <code>pagado_en = NOW()</code>.
            <?php if ($C['aprobado_en']): ?>(<code>aprobado_en</code> no sirve para esto: en tus datos los pedidos pendientes tienen fecha y el pagado con Wompi no.)<?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ((int)$cal['sin_fecha'] > 0): ?>
        <div class="alert alert-warning py-2 small">
            <i class="fas fa-triangle-exclamation"></i>
            <b><?= (int)$cal['sin_fecha'] ?></b> pedido(s) están <b>pagados pero sin fecha en <code>pagado_en</code></b>.
            Se muestran igual (marcados "sin fecha"). Revisa que tu flujo de pago (Wompi / comprobante aprobado) guarde <code>pagado_en = NOW()</code>.
        </div>
    <?php endif; ?>
    <?php if ((int)$cal['fecha_sin_pago'] > 0): ?>
        <div class="alert alert-warning py-2 small">
            <i class="fas fa-triangle-exclamation"></i>
            <b><?= (int)$cal['fecha_sin_pago'] ?></b> pedido(s) tienen <b>fecha en <code>pagado_en</code> pero su estado de pago NO es "<?= e(ESTADO_PAGADO) ?>"</b>.
            No se listan aquí. Si <code>pagado_en</code> se llena al crear o reservar el pedido, ese campo no sirve para saber cuándo se pagó.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3"><div class="card kpi"><div class="card-body">
            <div class="text-muted small">Pedidos cobrados</div><div class="v"><?= number_format($count, 0, ',', '.') ?></div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card kpi"><div class="card-body">
            <div class="text-muted small">Total recaudado</div><div class="v text-success"><?= $hayTotal ? money($suma) : '—' ?></div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card kpi"><div class="card-body">
            <div class="text-muted small">Ticket promedio</div><div class="v"><?= ($hayTotal && $count) ? money($suma / $count) : '—' ?></div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card kpi"><div class="card-body">
            <div class="text-muted small">Por método</div>
            <div class="small">
                <?php foreach ($porMetodo as $pm): ?>
                    <span class="badge bg-info text-dark"><?= e($pm['m'] ?? '—') ?>: <?= (int)$pm['n'] ?> · <?= money((float)$pm['s']) ?></span>
                <?php endforeach; if (!$porMetodo) echo '—'; ?>
            </div>
        </div></div></div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="search" name="q" class="form-control" placeholder="Pedido, cliente, email, teléfono, referencia..." value="<?= e($q) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Mostrar</label>
                    <select name="modo" class="form-select">
                        <option value="" <?= $modo === 'todos' ? 'selected' : '' ?>>Todos los cobrados</option>
                        <option value="pagado" <?= $modo === 'pagado' ? 'selected' : '' ?>>Pago registrado (Wompi)</option>
                        <option value="contraentrega" <?= $modo === 'contraentrega' ? 'selected' : '' ?>>Contraentrega entregada</option>
                    </select>
                </div>
                <?php if ($metodos): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Método</label>
                    <select name="metodo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($metodos as $m): ?>
                            <option value="<?= e($m) ?>" <?= $m === $metodo ? 'selected' : '' ?>><?= e($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2"><label class="form-label small mb-1"><?= e($lblFecha) ?> desde</label><input type="date" name="desde" class="form-control" value="<?= e($desde) ?>"></div>
                <div class="col-md-2"><label class="form-label small mb-1">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= e($hasta) ?>"></div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1"><i class="fas fa-search"></i></button>
                    <a href="facturas_pedidos.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
                <?php if ($tienePagadoEn): ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="solo_fecha" value="1" id="soloFecha" <?= $soloFecha ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="soloFecha">Solo pedidos con fecha de pago registrada (<code>pagado_en</code>)</label>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card card-factura shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Pedido</th><th>Cliente</th><th>Método</th>
                            <th class="text-end">Total</th><th><?= e($lblFecha) ?></th><th>Comprobante</th><th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($count === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>No hay pedidos cobrados con esos filtros.
                        </td></tr>
                    <?php else: while ($r = $res->fetch_assoc()):
                        $pid   = (int)val($r, $C, 'id');
                        $pagEn = fechaCobroVal($r, $C);
                        $aprox = !val($r, $C, 'pagado_en', null);   // sin pagado_en real
                        $rev   = val($r, $C, 'estado_comprobante');
                        $comp  = comprobanteUrl(val($r, $C, 'comprobante', null));
                    ?>
                        <tr>
                            <td class="fw-bold">PED-<?= str_pad((string)$pid, 6, '0', STR_PAD_LEFT) ?>
                                <?php if ($ep = val($r, $C, 'estado_pedido')): ?><div class="text-muted fw-normal" style="font-size:.7rem;"><?= e($ep) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?= e(val($r, $C, 'nombre', '—')) ?>
                                <div class="text-muted small"><?= e(val($r, $C, 'email')) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark"><?= e(val($r, $C, 'metodo_pago', '—')) ?></span>
                                <?php if ($ref = val($r, $C, 'referencia')): ?><div class="text-muted" style="font-size:.7rem;"><?= e($ref) ?></div><?php endif; ?>
                            </td>
                            <td class="fw-bold text-end"><?= $hayTotal ? money(totalVal($r, $C)) : '—' ?></td>
                            <td>
                                <?php if ($pagEn): ?><?= e(fdate($pagEn)) ?><?= $aprox ? ' <span class="badge bg-warning text-dark" title="Sin pagado_en: fecha aproximada">aprox.</span>' : '' ?>
                                <?php else: ?><span class="badge bg-warning text-dark">sin fecha</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rev !== ''): ?>
                                    <span class="badge <?= $rev === 'aprobado' ? 'bg-success' : 'bg-secondary' ?>"><?= e(str_replace('_', ' ', $rev)) ?></span>
                                <?php endif; ?>
                                <?php if ($comp): ?>
                                    <a href="<?= e($comp) ?>" target="_blank" rel="noopener" title="Ver comprobante adjunto"><i class="fas fa-paperclip"></i></a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-sm btn-outline-primary btn-ver" data-id="<?= $pid ?>"><i class="fas fa-eye"></i> Ver factura</button>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav class="mt-3" aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(qs($filters, ['page' => $page - 1])) ?>">&laquo;</a></li>
                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(qs($filters, ['page' => $p])) ?>"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(qs($filters, ['page' => $page + 1])) ?>">&raquo;</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 900px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista previa de factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div id="contenido-ticket" class="modal-body p-4 bg-white"></div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-success w-100" id="btnImprimir" disabled><i class="fas fa-print"></i> Imprimir factura completa</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal    = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTicket'));
const content  = document.getElementById('contenido-ticket');
const btnPrint = document.getElementById('btnImprimir');
let requestId  = 0;

async function verDetalle(id) {
    const current = ++requestId;
    btnPrint.disabled = true;
    content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando factura...</p></div>';
    modal.show();
    try {
        const res  = await fetch(`${location.pathname}?ticket=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
        const html = await res.text();
        if (current !== requestId) return;
        content.innerHTML = html;
        btnPrint.disabled = !res.ok;
    } catch (err) {
        if (current !== requestId) return;
        content.innerHTML = '<div class="alert alert-danger">Error al cargar la factura.</div>';
    }
}
document.querySelectorAll('.btn-ver').forEach(b => b.addEventListener('click', () => verDetalle(b.dataset.id)));
btnPrint.addEventListener('click', () => window.print());
</script>
</body>
</html>