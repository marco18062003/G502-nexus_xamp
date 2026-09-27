<?php
session_start();
require_once '../config/db.php'; 

// 1. CAPTURAR LA BÚSQUEDA
$busqueda = $_GET['busqueda'] ?? '';

// 2. CONSTRUIR LA CONSULTA
$sql = "SELECT fb.*, u.nombre_usuario 
        FROM factura_base fb 
        LEFT JOIN usuarios u ON fb.cajero_id = u.id";

if (!empty($busqueda)) {
    $b = mysqli_real_escape_string($conn, $busqueda);
    $sql .= " WHERE fb.numero_factura LIKE '%$b%' 
              OR fb.fecha_venta LIKE '%$b%' 
              OR DATE_FORMAT(fb.fecha_venta, '%d/%m/%Y') LIKE '%$b%' 
              OR u.nombre_usuario LIKE '%$b%' 
              OR fb.metodo_pago LIKE '%$b%' 
              OR fb.total LIKE '%$b%'";
}

$sql .= " ORDER BY fb.fecha_venta DESC"; 
$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    die("Error en la búsqueda: " . mysqli_error($conn));
}
$myVariable = 10000;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas - G502-NEXUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .card-factura { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* FIX FOR PRINTING: Oculta el fondo y muestra solo el contenido de la factura */
        @media print {
    /* Hide everything in the background */
    body * { visibility: hidden; }

    /* Show the modal content */
    #modalTicket, #modalTicket * { visibility: visible; }

    /* CRITICAL: Remove height restrictions for long bills */
    .modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        display: block !important;
        height: auto !important;
        overflow: visible !important;
    }

    .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
    }

    .modal-content {
        border: none !important;
        height: auto !important;
    }

    #contenido-ticket {
        padding: 0 !important;
        height: auto !important;
        overflow: visible !important;
    }

    /* Hide the modal's X button and the print button inside the modal */
    .btn-close, .modal-footer, .modal-header { display: none !important; }
}
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Historial G502-NEXUS</h2>
        <a href="POS.php" class="btn btn-dark"><i class="fas fa-plus"></i> Nueva Venta</a>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Buscar por N° Factura, Fecha, Cajero..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Buscar</button>
                    <?php if(!empty($busqueda)): ?>
                        <a href="basefacturas.php" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-factura shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>N° Factura</th>
                        <th>Fecha</th>
                        <th>Cajero</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($f = mysqli_fetch_assoc($resultado)): ?>
                    <tr>
                        <td class="fw-bold"><?php echo $f['numero_factura']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($f['fecha_venta'])); ?></td>
                        <td><?php echo $f['nombre_usuario'] ?? 'Sistema'; ?></td>
                        <td><span class="badge bg-info text-dark"><?php echo $f['metodo_pago']; ?></span></td>
                        <td class="fw-bold">$<?php echo number_format($f['total'] + $myVariable); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(<?php echo $f['id']; ?>)">
                                <i class="fas fa-eye"></i> Ver Factura
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 900px;"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div id="contenido-ticket" class="modal-body p-4 bg-white">
                </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-success w-100" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir Factura Completa
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalle(id) {
    const modalElement = document.getElementById('modalTicket');
    const modal = new bootstrap.Modal(modalElement);
    
    document.getElementById('contenido-ticket').innerHTML = 
        '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">Cargando diseño G502...</p></div>';
    
    modal.show();
    
    fetch(`get_ticket.php?id=${id}`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('contenido-ticket').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('contenido-ticket').innerHTML = '<div class="alert alert-danger">Error al cargar datos.</div>';
        });
}
</script>
</body>
</html>