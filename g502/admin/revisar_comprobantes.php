<?php
// revisar_comprobantes.php
// Panel para que el administrador (tú) revise los comprobantes que los
// clientes subieron después de pagar con Wompi, y apruebe o rechace el pedido.

session_start();
require_once '../config/db.php';

// Igual que en el resto del panel admin, exige sesión iniciada.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// ─── Acción: aprobar o rechazar ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int)($_POST['pedido_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($pedidoId > 0 && in_array($accion, ['aprobar', 'rechazar'], true)) {
        $nuevoEstadoRevision = $accion === 'aprobar' ? 'aprobado' : 'rechazado';
        $nuevoEstadoPedido   = $accion === 'aprobar' ? 'confirmado' : 'rechazado';

        $stmt = mysqli_prepare($conn, "UPDATE pedidos SET estado_revision = ?, estado_pedido = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $nuevoEstadoRevision, $nuevoEstadoPedido, $pedidoId);
        mysqli_stmt_execute($stmt);
    }
    header('Location: revisar_comprobantes.php');
    exit;
}

// ─── Listar pedidos en revisión ────────────────────────────────────────────
// Base pública real donde quedan los comprobantes subidos.
// Usamos ruta absoluta (con dominio) para que el link funcione sin importar
// desde qué carpeta se esté viendo esta página de administración.
define('URL_BASE_COMPROBANTES', 'https://donjorgito.shop/g502/uploads/comprobantes/');

$sql = "SELECT id, nombre_cliente, email_cliente, total_final, fecha_pedido, comprobante_path, estado_revision
        FROM pedidos
        WHERE metodo_pago = 'wompi' AND estado_revision != 'sin_comprobante'
        ORDER BY 
            CASE estado_revision WHEN 'en_revision' THEN 0 ELSE 1 END,
            fecha_pedido DESC";
$resultado = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes de Pago - G502</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .card-comprobante { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .comprobante-preview { max-width: 120px; max-height: 120px; border-radius: 8px; border: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4"><i class="fas fa-receipt"></i> Comprobantes de Pago (Wompi)</h2>

    <?php if (mysqli_num_rows($resultado) === 0): ?>
        <div class="alert alert-info">No hay comprobantes para revisar todavía.</div>
    <?php endif; ?>

    <?php while ($p = mysqli_fetch_assoc($resultado)): ?>
        <div class="card card-comprobante mb-3">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h5 class="mb-1">Pedido #<?php echo (int)$p['id']; ?> — <?php echo htmlspecialchars($p['nombre_cliente']); ?></h5>
                    <div class="text-muted small"><?php echo htmlspecialchars($p['email_cliente']); ?></div>
                    <div class="fw-bold">$<?php echo number_format($p['total_final'], 0, ',', '.'); ?></div>
                    <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($p['fecha_pedido'])); ?></div>
                    <span class="badge <?php echo $p['estado_revision'] === 'en_revision' ? 'bg-warning text-dark' : ($p['estado_revision'] === 'aprobado' ? 'bg-success' : 'bg-danger'); ?>">
                        <?php echo htmlspecialchars($p['estado_revision']); ?>
                    </span>
                </div>

                <div>
                    <?php if ($p['comprobante_path']):
                        $urlComprobante = URL_BASE_COMPROBANTES . basename($p['comprobante_path']);
                    ?>
                        <a href="<?php echo htmlspecialchars($urlComprobante); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($urlComprobante); ?>"
                                 class="comprobante-preview"
                                 onerror="this.replaceWith(Object.assign(document.createElement('a'), {href:'<?php echo htmlspecialchars($urlComprobante); ?>', target:'_blank', innerText:'Ver PDF adjunto'}))">
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($p['estado_revision'] === 'en_revision'): ?>
                <div class="d-flex gap-2">
                    <form method="POST">
                        <input type="hidden" name="pedido_id" value="<?php echo (int)$p['id']; ?>">
                        <input type="hidden" name="accion" value="aprobar">
                        <button type="submit" class="btn btn-success">✓ Aprobar</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="pedido_id" value="<?php echo (int)$p['id']; ?>">
                        <input type="hidden" name="accion" value="rechazar">
                        <button type="submit" class="btn btn-outline-danger">✕ Rechazar</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>

</div>
</body>
</html>