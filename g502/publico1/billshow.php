<?php
// billshow.php
// El cliente llega aquí después de pagar con Wompi (o cancelar), y debe
// subir un comprobante (captura de pantalla o PDF) para que el pedido
// quede en revisión manual, sin importar lo que ya haya confirmado el
// webhook de Wompi automáticamente.

session_start();
require_once '../config/db.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$referencia = $_GET['ref'] ?? $_POST['ref'] ?? '';
$mensaje = null;
$mensajeTipo = null; // 'success' | 'error'

if ($referencia === '' || strpos($referencia, 'PEDIDO_') !== 0) {
    exit("<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#d4af37;'>Referencia inválida</h1>
            <a href='index.php' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver a la tienda</a>
          </div>");
}

$stmt = mysqli_prepare($conn, "SELECT id, nombre_cliente, total_final, estado_pago, estado_revision, comprobante_path FROM pedidos WHERE referencia_pago = ?");
mysqli_stmt_bind_param($stmt, "s", $referencia);
mysqli_stmt_execute($stmt);
$pedido = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$pedido) {
    exit("<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#d4af37;'>Pedido no encontrado</h1>
            <a href='index.php' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver a la tienda</a>
          </div>");
}

// ─── Procesar la subida del comprobante ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrfPost)) {
        $mensaje = 'Tu sesión expiró. Recarga la página e intenta de nuevo.';
        $mensajeTipo = 'error';
    } elseif (empty($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'No se pudo recibir el archivo. Intenta de nuevo.';
        $mensajeTipo = 'error';
    } else {
        $archivo = $_FILES['comprobante'];

        // Validar tamaño (máx 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            $mensaje = 'El archivo es muy grande (máximo 5MB).';
            $mensajeTipo = 'error';
        } else {
            // Validar tipo real del archivo (no solo la extensión, que se puede falsificar)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $archivo['tmp_name']);
            finfo_close($finfo);

            $tiposPermitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
            ];

            if (!isset($tiposPermitidos[$mime])) {
                $mensaje = 'Solo se aceptan imágenes (JPG, PNG, WEBP) o PDF.';
                $mensajeTipo = 'error';
            } else {
                $extension = $tiposPermitidos[$mime];
                $nombreArchivo = 'comprobante_' . $pedido['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

                $carpetaDestino = __DIR__ . '/uploads/comprobantes/';
                if (!is_dir($carpetaDestino)) {
                    mkdir($carpetaDestino, 0755, true);
                }

                $rutaDestino = $carpetaDestino . $nombreArchivo;

                if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                    // Guardamos solo el nombre del archivo (no la ruta completa del servidor)
                    $rutaRelativa = 'uploads/comprobantes/' . $nombreArchivo;

                    $stmtUpd = mysqli_prepare($conn, "UPDATE pedidos SET comprobante_path = ?, estado_revision = 'en_revision' WHERE referencia_pago = ?");
                    mysqli_stmt_bind_param($stmtUpd, "ss", $rutaRelativa, $referencia);
                    mysqli_stmt_execute($stmtUpd);

                    $pedido['comprobante_path'] = $rutaRelativa;
                    $pedido['estado_revision'] = 'en_revision';

                    $mensaje = '¡Comprobante recibido! Tu pedido está en revisión, te avisaremos por correo cuando lo confirmemos.';
                    $mensajeTipo = 'success';
                } else {
                    $mensaje = 'No se pudo guardar el archivo. Intenta de nuevo.';
                    $mensajeTipo = 'error';
                }
            }
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sube tu comprobante | Don Jorgito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #000; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .voucher-container {
            border: 1px solid #d4af37;
            padding: 30px;
            border-radius: 20px;
            background: #111;
            margin-top: 50px;
            max-width: 500px;
        }
        .price-tag { font-size: 32px; font-weight: bold; color: #d4af37; margin: 15px 0; }
        .form-control { background: #1a1a1a; border-color: #444; color: #fff; }
        .form-control:focus { background: #1a1a1a; border-color: #d4af37; color: #fff; box-shadow: none; }
        .btn-gold { background: #d4af37; border: none; color: #000; font-weight: bold; }
        .btn-gold:hover { background: #c19b2e; color: #000; }
    </style>
</head>
<body class="text-center">
    <div class="container d-flex justify-content-center">
        <div class="voucher-container">
            <h2 style="color: #d4af37; letter-spacing: 2px;">DON JORGITO</h2>
            <hr style="border-color: #333;">

            <?php if ($pedido['estado_revision'] === 'aprobado'): ?>
                <div class="alert alert-success mt-3">
                    ✅ Este pedido ya fue aprobado. ¡Gracias por tu compra!
                </div>
                <a href="index.php" class="btn btn-gold w-100 mt-2">Volver a la tienda</a>

            <?php elseif ($pedido['estado_revision'] === 'en_revision' && !$mensaje): ?>
                <div class="alert alert-warning mt-3">
                    ⏳ Ya subiste tu comprobante para este pedido. Está en revisión — te avisaremos por correo cuando lo confirmemos.
                </div>
                <a href="index.php" class="btn btn-gold w-100 mt-2">Volver a la tienda</a>

            <?php else: ?>
                <p class="lead">Pedido de: <br><strong><?php echo htmlspecialchars($pedido['nombre_cliente']); ?></strong></p>
                <div class="price-tag">$<?php echo number_format($pedido['total_final'], 0, ',', '.'); ?> COP</div>
                <p class="small text-muted">Pedido #<?php echo (int)$pedido['id']; ?></p>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?php echo $mensajeTipo === 'success' ? 'success' : 'danger'; ?> mt-3">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <?php if ($mensajeTipo !== 'success'): ?>
                <p class="mt-4">Sube una captura de pantalla o el PDF del comprobante de tu pago con Wompi:</p>
                <form method="POST" enctype="multipart/form-data" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="ref" value="<?php echo htmlspecialchars($referencia); ?>">
                    <input type="file" name="comprobante" accept="image/jpeg,image/png,image/webp,application/pdf"
                           class="form-control mb-3" required>
                    <button type="submit" class="btn btn-gold w-100">Enviar comprobante</button>
                </form>
                <?php else: ?>
                    <a href="index.php" class="btn btn-gold w-100 mt-2">Volver a la tienda</a>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>