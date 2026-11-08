<?php
// basefacturas.php — HISTORIAL DE VENTAS POS
// Archivo INDEPENDIENTE: contiene el listado y también la factura (basefacturas.php?ticket=ID).
require_once 'seguridad_admin.php';   // session_start + rol admin + $conn
require_once '../config/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const IVA_RATE = 0.19;   // Tarifa general. Si hay productos excluidos/exentos, guarda el IVA por línea en la venta.

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 0, ',', '.'); }
function qty(float $n): string { return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ','); }

/** Ejecuta una consulta preparada con parámetros opcionales */
function run(mysqli $c, string $sql, string $types = '', array $params = []): mysqli_result {
    $st = $c->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    return $st->get_result();
}
function fetchOne(mysqli $c, string $sql, string $types = '', array $params = []): ?array {
    return run($c, $sql, $types, $params)->fetch_assoc() ?: null;
}
function fail(int $code, string $msg) {
    http_response_code($code);
    echo '<div class="alert alert-danger mb-0">' . e($msg) . '</div>';
    exit;
}

// =====================================================================
// MODO FACTURA: basefacturas.php?ticket=ID  ->  devuelve solo el fragmento HTML (va al modal)
// =====================================================================
if (isset($_GET['ticket'])) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');

    $id = filter_var($_GET['ticket'] ?? null, FILTER_VALIDATE_INT);
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
<?php
    exit;
}

// =====================================================================
// MODO LISTADO
// =====================================================================
// ---------- Búsqueda + paginación ----------
const PER_PAGE = 25;
$busqueda = trim($_GET['busqueda'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));

$from   = "FROM factura_base fb LEFT JOIN usuarios u ON fb.cajero_id = u.id";
$where  = '';
$types  = '';
$params = [];

if ($busqueda !== '') {
    $where = " WHERE fb.numero_factura LIKE ?
                  OR DATE_FORMAT(fb.fecha_venta, '%d/%m/%Y') LIKE ?
                  OR fb.fecha_venta LIKE ?
                  OR u.nombre_usuario LIKE ?
                  OR fb.metodo_pago LIKE ?
                  OR CAST(fb.total + fb.domicilio AS CHAR) LIKE ?";
    $like   = '%' . addcslashes($busqueda, '%_\\') . '%';   // % y _ se buscan literalmente
    $types  = 'ssssss';
    $params = array_fill(0, 6, $like);
}

try {
    $sum    = run($conn, "SELECT COUNT(*) AS n, IFNULL(SUM(fb.total + fb.domicilio), 0) AS s $from $where", $types, $params)->fetch_assoc();
    $count  = (int)$sum['n'];
    $suma   = (float)$sum['s'];
    $pages  = max(1, (int)ceil($count / PER_PAGE));
    $page   = min($page, $pages);
    $offset = ($page - 1) * PER_PAGE;

    $resultado = run(
        $conn,
        "SELECT fb.id, fb.numero_factura, fb.fecha_venta, fb.metodo_pago, fb.total, fb.domicilio, u.nombre_usuario
         $from $where
         ORDER BY fb.fecha_venta DESC, fb.id DESC
         LIMIT ? OFFSET ?",
        $types . 'ii',
        array_merge($params, [PER_PAGE, $offset])
    );
} catch (mysqli_sql_exception $ex) {
    error_log('basefacturas.php: ' . $ex->getMessage());   // el detalle va al log
    http_response_code(500);
    // En localhost (XAMPP) se muestra el error real para depurar; en producción no.
    $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    exit('No se pudo cargar el historial.' . ($local ? '<br><b>Detalle (solo local):</b> ' . e($ex->getMessage()) : ''));
}

function pageUrl(int $p, string $q): string {
    return '?' . http_build_query(array_filter(['busqueda' => $q, 'page' => $p > 1 ? $p : null]));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial de Ventas - G502-NEeeeeeeXUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card-factura { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,.1); }

        /* ===== Estilos de la factura (antes estaban "sueltos" en get_ticket.php) ===== */
        .invoice-wrap { overflow-x: auto; }
        .invoice-paper { width: 216mm; min-height: 279mm; padding: 10mm; margin: 0 auto; background: #fff; color: #000; }
        .invoice-paper table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        .invoice-paper .table-main th { border-bottom: 2px solid #000; padding: 6px 4px; font-size: .8rem; }
        .invoice-paper .table-main td { border-bottom: 1px solid #ddd; padding: 6px 4px; font-size: .85rem; }
        .invoice-paper thead { display: table-header-group; }
        .invoice-paper tfoot { display: table-footer-group; }
        .invoice-paper tr { page-break-inside: avoid; }

        /* ===== Impresión: solo se imprime la factura ===== */
        @media print {
            @page { size: letter; margin: 8mm; }
            body > *:not(#modalTicket) { display: none !important; }   /* oculta header, listado y backdrop */
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
        <li class="nav-item"><a class="nav-link active" href="basefacturas.php"><i class="fas fa-cash-register"></i> Ventas POS</a></li>
        <li class="nav-item"><a class="nav-link" href="facturas_pedidos.php"><i class="fas fa-globe"></i> Pedidos cddobrados (web)</a></li>
    </ul>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Historial G502-NssssEXUS</h2>
        <a href="POS.php" class="btn btn-dark"><i class="fas fa-plus"></i> Nueva Venta</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="search" name="busqueda" class="form-control"
                           placeholder="Buscar por N° factura, fecha (dd/mm/aaaa), cajero, método o total..."
                           value="<?= e($busqueda) ?>" autofocus>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-search"></i> Buscar</button>
                    <?php if ($busqueda !== ''): ?>
                        <a href="<?= e(basename($_SERVER['PHP_SELF'])) ?>" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between text-muted small mb-2 px-1">
        <span><?= number_format($count, 0, ',', '.') ?> factura<?= $count === 1 ? '' : 's' ?><?= $busqueda !== '' ? ' encontradas' : '' ?></span>
        <span>Total listado: <strong class="text-dark"><?= money($suma) ?></strong></span>
    </div>

    <div class="card card-factura shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Factura</th><th>Fecha</th><th>Cajero</th><th>Método</th>
                            <th class="text-end">Total</th><th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($count === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>No se encontraron facturas.
                        </td></tr>
                    <?php else: while ($f = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold"><?= e($f['numero_factura']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($f['fecha_venta']))) ?></td>
                            <td><?= e($f['nombre_usuario'] ?? 'Sistema') ?></td>
                            <td><span class="badge bg-info text-dark"><?= e($f['metodo_pago']) ?></span></td>
                            <td class="fw-bold text-end">
                                <?= money((float)$f['total'] + (float)$f['domicilio']) ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary btn-ver" data-id="<?= (int)$f['id'] ?>">
                                    <i class="fas fa-eye"></i> Ver factura
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav class="mt-3" aria-label="Paginación">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(pageUrl($page - 1, $busqueda)) ?>">&laquo;</a></li>
                    <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(pageUrl($p, $busqueda)) ?>"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(pageUrl($page + 1, $busqueda)) ?>">&raquo;</a></li>
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
                <button type="button" class="btn btn-success w-100" id="btnImprimir" disabled>
                    <i class="fas fa-print"></i> Imprimir factura completa
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modalEl  = document.getElementById('modalTicket');
const modal    = bootstrap.Modal.getOrCreateInstance(modalEl);
const content  = document.getElementById('contenido-ticket');
const btnPrint = document.getElementById('btnImprimir');
let requestId  = 0;   // evita que una respuesta lenta pise a una más reciente

async function verDetalle(id) {
    const current = ++requestId;
    btnPrint.disabled = true;
    content.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando factura...</p></div>';
    modal.show();

    try {
        const res = await fetch(`${location.pathname}?ticket=${encodeURIComponent(id)}`, { credentials: 'same-origin' });
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