<?php
// get_ticket.php this page 
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) exit("Error: ID no válido.");

// Consultas Seguras
$stmtMe = $conn->prepare("SELECT * FROM mefacturaciong502 WHERE id = 1");
$stmtMe->execute();
$me = $stmtMe->get_result()->fetch_assoc();

$stmtF = $conn->prepare("SELECT * FROM factura_base WHERE id = ?");
$stmtF->bind_param("i", $id);
$stmtF->execute();
$f = $stmtF->get_result()->fetch_assoc();

if (!$f) exit("Error: Factura inexistente.");

$idCliente = $f['id_cliente'] ?? 1; 
$stmtCli = $conn->prepare("SELECT * FROM facturaciong502 WHERE id = ?");
$stmtCli->bind_param("i", $idCliente);
$stmtCli->execute();
$cli = $stmtCli->get_result()->fetch_assoc();

// 1. We GROUP BY producto_id so identical items merge into one row
// 2. We SUM(cantidad) so 1+1+1 becomes 3
// 3. We JOIN with donjorgito1 to get the real Reference/REF
// Usamos `upc/barra` con comillas invertidas para evitar errores de sintaxis
$stmtDetalle = $conn->prepare("
    SELECT 
        d.`ean` AS referencia, 
        f.nombre_producto, 
        SUM(f.cantidad) AS total_cantidad, 
        f.precio_unidad, 
        SUM(f.subtotal_item) AS total_subtotal,
        d.caracteristica 
    FROM factura f 
    LEFT JOIN donjorgito1 d ON f.producto_id = d.id 
    WHERE f.venta_id = ?
    GROUP BY f.producto_id, f.precio_unidad
");

$stmtDetalle->bind_param("i", $id);
$stmtDetalle->execute();
$resDetalle = $stmtDetalle->get_result();
$myVariable = 10000;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?php echo htmlspecialchars($f['numero_factura']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    /* Force the container to allow natural height */
    .invoice-paper {
        width: 216mm; 
        min-height: 279mm; /* Minimum 1 Letter page height */
        padding: 10mm;
        margin: 0 auto;
        background: white;
    }

    /* THE FIX FOR LARGE BILLS */
    table {
        width: 100%;
        border-collapse: collapse;
        /* Allow the table to break across pages */
        page-break-inside: auto; 
    }

    tr {
        /* Prevent a single row from being cut in half */
        page-break-inside: avoid !important; 
        page-break-after: auto;
    }

    thead {
        /* This repeats the header (REF, DESCRIPCIÓN, etc.) on every new page */
        display: table-header-group; 
    }

    tfoot {
        /* Keeps the footer logic consistent */
        display: table-footer-group;
    }

    @media print {
        html, body {
            height: auto !important;
            overflow: visible !important;
        }
        
        .invoice-paper {
            border: none;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
    }
</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

<div class="invoice-paper">
    <div class="row border-bottom pb-3 mb-3">
        <div class="col-7">
            <h4 class="fw-bold m-0"><?php echo htmlspecialchars($me['mi_razon_social']); ?></h4>
            <div class="small">NIT: <?php echo $me['mi_nit']; ?>-<?php echo $me['mi_dv']; ?></div>
            <div class="small">Dirección: <?php echo htmlspecialchars($me['mi_direccion']); ?></div>
            <div class="small text-uppercase mt-2" style="font-size: 0.7rem;">
                Somos Grandes Contribuyentes - Responsables de IVA
            </div>
        </div>
        <div class="col-5 text-end">
            <div class="border p-2">
                <div class="fw-bold">FACTURA ELECTRÓNICA DE VENTA</div>
                <div class="fs-4 fw-bold">No. <?php echo htmlspecialchars($f['numero_factura']); ?></div>
                <div class="small">CUFE: <span class="text-break" style="font-size: 0.6rem;">c2ffbeb1...</span></div>
            </div>
        </div>
    </div>

    <div class="row mb-3" style="font-size: 0.85rem;">
        <div class="col-6">
            <div class="border p-2 h-100">
                <div class="fw-bold border-bottom mb-1">CLIENTE</div>
                <div>ID: <?php echo htmlspecialchars($cli['nit']); ?></div>
                <div class="fw-bold"><?php echo htmlspecialchars($cli['razon_social']); ?></div>
                <div><?php echo htmlspecialchars($cli['direccion']); ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="border p-2 h-100">
                <div class="fw-bold border-bottom mb-1">DESPACHADO A</div>
                <div class="fw-bold"><?php echo htmlspecialchars($f['nombre_sucursal'] ?? 'CIGARRERIA JORGITO'); ?></div>
                <div><?php echo htmlspecialchars($f['direccion_envio'] ?? 'KR 79 A 35 18'); ?></div>
                <div>Tel: <?php echo htmlspecialchars($f['telefono'] ?? '3227784205'); ?></div>
            </div>
        </div>
    </div>

    <table class="table-main">
    <thead>
        <tr>
            <th style="width: 10%;">REF</th>
            <th style="width: 45%;">DESCRIPCIÓN</th>
            <th style="width: 10%;" class="text-center">CANT.</th>
            <th style="width: 15%;" class="text-end">UNITARIO</th>
            <th style="width: 20%;" class="text-end">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        <?php while($p = $resDetalle->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['referencia'] ?? 'N/A'); ?></td>
            <td>
                <div class="fw-bold"><?php echo htmlspecialchars($p['nombre_producto']); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars($p['caracteristica'] ?? ''); ?></div>
            </td>
            <td class="text-center"><?php echo number_format($p['total_cantidad'], 0); ?></td>
            <td class="text-end">$<?php echo number_format($p['precio_unidad'], 0); ?></td>
            <td class="text-end fw-bold">$<?php echo number_format($p['total_subtotal'], 0); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

    <div class="row justify-content-end">
    <div class="col-4">
        <table class="table table-sm border">
            <tr><td>SUBTOTAL</td><td class="text-end">$<?php echo number_format($f['total'] / 1.19, 2); ?></td></tr>
            <tr><td>IVA 19%</td><td class="text-end">$<?php echo number_format($f['total'] - ($f['total'] / 1.19), 2); ?></td></tr>
            <tr><td>Domicilio</td><td class="text-end">$<?php echo number_format($myVariable, 2); ?></td></tr>
            <tr class="fw-bold bg-light"><td>TOTAL COP</td><td class="text-end">$<?php echo number_format($f['total'] + $myVariable, 2); ?></td></tr>
        </table>
    </div>
</div>
</div>



</body>
</html>