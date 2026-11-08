<?php
// get_ticket.php — devuelve SOLO el fragmento HTML de la factura (se inyecta en el modal de basefacturas.php)
require_once 'seguridad_admin.php';   // hace session_start(), valida el rol y crea $conn
require_once '../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const IVA_RATE = 0.19;   // Tarifa general. Si hay productos excluidos/exentos, guarda el IVA por línea en la venta.

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 0, ',', '.'); }
function qty(float $n): string { return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ','); }  // 3 -> "3", 0.5 -> "0,5"
function fail(int $code, string $msg) {
    http_response_code($code);
    echo '<div class="alert alert-danger mb-0">' . e($msg) . '</div>';
    exit;
}
function fetchOne(mysqli $c, string $sql, string $types = '', array $params = []): ?array {
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) fail(400, 'ID no válido.');

try {
    $me = fetchOne($conn, "SELECT * FROM mefacturaciong502 WHERE id = 1") ?? [];

    $f = fetchOne($conn,
        "SELECT fb.*, u.nombre_usuario
         FROM factura_base fb LEFT JOIN usuarios u ON fb.cajero_id = u.id
         WHERE fb.id = ?", 'i', [$id]);
    if (!$f) fail(404, 'Factura inexistente.');

    $cli = null;
    if (!empty($f['id_cliente'])) {
        $cli = fetchOne($conn, "SELECT * FROM facturaciong502 WHERE id = ?", 'i', [(int)$f['id_cliente']]);
    }
    $cli = $cli ?? ['nit' => '222222222222', 'razon_social' => 'CONSUMIDOR FINAL', 'direccion' => ''];

    // Items agrupados: mismo producto + mismo precio => una sola fila con la cantidad sumada.
    // MAX() en las columnas no agrupadas para que funcione con ONLY_FULL_GROUP_BY.
    $st = $conn->prepare(
        "SELECT MAX(d.ean)             AS referencia,
                MAX(f.nombre_producto) AS nombre_producto,
                MAX(d.caracteristica)  AS caracteristica,
                SUM(f.cantidad)        AS total_cantidad,
                f.precio_unidad,
                SUM(f.subtotal_item)   AS total_subtotal
         FROM factura f
         LEFT JOIN donjorgito1 d ON f.producto_id = d.id
         WHERE f.venta_id = ?
         GROUP BY f.producto_id, f.precio_unidad
         ORDER BY MAX(f.nombre_producto)"
    );
    $st->bind_param('i', $id);
    $st->execute();
    $items = $st->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $ex) {
    error_log('get_ticket.php: ' . $ex->getMessage());
    fail(500, 'No se pudo cargar la factura.');
}

// ---- Totales: todo sale de los datos guardados, nada "mágico" ----
$totalConIva = (float)$f['total'];                    // total de productos con IVA incluido
$domicilio   = (float)($f['domicilio'] ?? 0);         // columna nueva (ver migracion_domicilio.sql)
$subtotal    = round($totalConIva / (1 + IVA_RATE));  // COP no usa centavos
$iva         = $totalConIva - $subtotal;              // así subtotal + IVA siempre = total
$granTotal   = $totalConIva + $domicilio;

$esElectronica = !empty($f['cufe']);                  // solo se anuncia como electrónica si hay CUFE real
$hayDespacho   = !empty($f['nombre_sucursal']) || !empty($f['direccion_envio']) || !empty($f['telefono']);
?>
<div class="invoice-wrap">
<div class="invoice-paper">

    <div class="row border-bottom pb-3 mb-3">
        <div class="col-7">
            <h4 class="fw-bold m-0"><?= e($me['mi_razon_social'] ?? '') ?></h4>
            <?php if (!empty($me['mi_nit'])): ?>
                <div class="small">NIT: <?= e($me['mi_nit']) ?><?= isset($me['mi_dv']) ? '-' . e($me['mi_dv']) : '' ?></div>
            <?php endif; ?>
            <?php if (!empty($me['mi_direccion'])): ?>
                <div class="small">Dirección: <?= e($me['mi_direccion']) ?></div>
            <?php endif; ?>
        </div>
        <div class="col-5 text-end">
            <div class="border p-2">
                <div class="fw-bold"><?= $esElectronica ? 'FACTURA ELECTRÓNICA DE VENTA' : 'TICKET DE VENTA' ?></div>
                <div class="fs-4 fw-bold">No. <?= e($f['numero_factura']) ?></div>
                <?php if ($esElectronica): ?>
                    <div class="small">CUFE: <span class="text-break" style="font-size:.6rem;"><?= e($f['cufe']) ?></span></div>
                <?php else: ?>
                    <div class="small text-muted" style="font-size:.7rem;">Documento sin validez tributaria</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mb-2 small">
        <div class="col-4"><strong>Fecha:</strong> <?= e(date('d/m/Y H:i', strtotime($f['fecha_venta']))) ?></div>
        <div class="col-4"><strong>Cajero:</strong> <?= e($f['nombre_usuario'] ?? 'Sistema') ?></div>
        <div class="col-4"><strong>Pago:</strong> <?= e($f['metodo_pago'] ?? '—') ?></div>
    </div>

    <div class="row mb-3" style="font-size:.85rem;">
        <div class="<?= $hayDespacho ? 'col-6' : 'col-12' ?>">
            <div class="border p-2 h-100">
                <div class="fw-bold border-bottom mb-1">CLIENTE</div>
                <div>ID: <?= e($cli['nit'] ?? '') ?></div>
                <div class="fw-bold"><?= e($cli['razon_social'] ?? '') ?></div>
                <div><?= e($cli['direccion'] ?? '') ?></div>
            </div>
        </div>
        <?php if ($hayDespacho): ?>
        <div class="col-6">
            <div class="border p-2 h-100">
                <div class="fw-bold border-bottom mb-1">DESPACHADO A</div>
                <div class="fw-bold"><?= e($f['nombre_sucursal'] ?? '') ?></div>
                <div><?= e($f['direccion_envio'] ?? '') ?></div>
                <?php if (!empty($f['telefono'])): ?><div>Tel: <?= e($f['telefono']) ?></div><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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
            <tr><td colspan="5" class="text-center text-muted py-4">Esta factura no tiene productos registrados.</td></tr>
        <?php else: foreach ($items as $p): ?>
            <tr>
                <td><?= e($p['referencia'] ?? 'N/A') ?></td>
                <td>
                    <div class="fw-bold"><?= e($p['nombre_producto']) ?></div>
                    <?php if (!empty($p['caracteristica'])): ?><div class="text-muted small"><?= e($p['caracteristica']) ?></div><?php endif; ?>
                </td>
                <td class="text-center"><?= qty((float)$p['total_cantidad']) ?></td>
                <td class="text-end"><?= money((float)$p['precio_unidad']) ?></td>
                <td class="text-end fw-bold"><?= money((float)$p['total_subtotal']) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="row justify-content-end mt-3">
        <div class="col-5">
            <table class="table table-sm border">
                <tr><td>SUBTOTAL</td><td class="text-end"><?= money($subtotal) ?></td></tr>
                <tr><td>IVA <?= (int)round(IVA_RATE * 100) ?>%</td><td class="text-end"><?= money($iva) ?></td></tr>
                <?php if ($domicilio > 0): ?>
                <tr><td>Domicilio</td><td class="text-end"><?= money($domicilio) ?></td></tr>
                <?php endif; ?>
                <tr class="fw-bold bg-light"><td>TOTAL COP</td><td class="text-end"><?= money($granTotal) ?></td></tr>
            </table>
        </div>
    </div>

</div>
</div>