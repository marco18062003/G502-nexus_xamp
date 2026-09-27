<?php
// Archivo: paymeth2.php
session_start();
include '../config/db.php';

// --- FLUJO NUEVO (recomendado): viene con ?ref=... desde preparar_pago_wompi.php ---
$referencia = $_GET['ref'] ?? '';
$precio_pesos = 0;
$nombre_cliente = 'Cliente G502';

if ($referencia !== '') {
    $stmt = mysqli_prepare($conn, "SELECT total, cliente_nombre, estado FROM venta_pendiente WHERE referencia_unica = ?");
    mysqli_stmt_bind_param($stmt, "s", $referencia);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$row) {
        exit("<div style='background:#000;color:#fff;text-align:center;padding-top:100px;font-family:sans-serif;'>
                <h1 style='color:#d4af37;'>Referencia no encontrada</h1>
                <p>Este link de pago no es válido o ya expiró.</p>
                <a href='https://donjorgito.shop/g502/publico1/' style='color:#d4af37;text-decoration:none;border:1px solid #d4af37;padding:10px 20px;border-radius:5px;'>Volver al POS</a>
              </div>");
    }
    if ($row['estado'] !== 'PENDIENTE') {
        exit("<div style='background:#000;color:#fff;text-align:center;padding-top:100px;font-family:sans-serif;'>
                <h1 style='color:#d4af37;'>Esta venta ya fue procesada</h1>
                <p>Esta referencia de pago ya no está pendiente.</p>
                <a href='https://donjorgito.shop/g502/publico1/' style='color:#d4af37;text-decoration:none;border:1px solid #d4af37;padding:10px 20px;border-radius:5px;'>Volver al POS</a>
              </div>");
    }

    $precio_pesos = (int)$row['total'];
    $nombre_cliente = $row['cliente_nombre'];
} else {
    // --- FLUJO ANTIGUO (fallback por si algo abre este archivo sin ref) ---
    // Nota: este camino NO queda ligado a venta_pendiente, así que el webhook
    // no podrá reconstruir el carrito. Solo debería usarse temporalmente.
    if (isset($_SESSION['pago_pendiente_monto']) && $_SESSION['pago_pendiente_monto'] > 0) {
        $precio_pesos = $_SESSION['pago_pendiente_monto'];
    } elseif (isset($_GET['value_final'])) {
        $precio_pesos = (int)$_GET['value_final'];
    }
    if (isset($_SESSION['pago_pendiente_cliente'])) {
        $nombre_cliente = $_SESSION['pago_pendiente_cliente'];
    } elseif (isset($_GET['customer_name'])) {
        $nombre_cliente = $_GET['customer_name'];
    }
    // Generamos una referencia "suelta" solo para poder mostrar el widget.
    $referencia = "G502_LEGACY_" . time();
}

if ($precio_pesos <= 0) {
    echo "<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#d4af37;'>Acceso No Autorizado</h1>
            <p>No recibimos un monto válido. Por favor, intenta de nuevo desde el POS.</p>
            <a href='https://donjorgito.shop/g502/publico1/' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver al POS</a>
          </div>";
    exit;
}

// Configuración Wompi
$monto_centavos = (int)($precio_pesos * 100);
$moneda = "COP";
$llave_publica = "pub_prod_Id4Oj4RzKFYJrkitudFtYLIQ4DxwBmNo";
$secreto_integridad = "prod_integrity_v1z7jKwqxZdShVUtooJoQsCeTJiOvKQ7";

// Firma de Integridad (para el widget de checkout)
$cadena = $referencia . $monto_centavos . $moneda . $secreto_integridad;
$firma = hash("sha256", $cadena);

// Registro en wompi1 (usa la MISMA referencia que venta_pendiente, así el webhook puede cruzar ambas tablas)
$nombre_safe = mysqli_real_escape_string($conn, $nombre_cliente);
$sql_insert = "INSERT INTO wompi1 (referencia_unica, monto_centavos, estado_pago, email_cliente)
               VALUES ('$referencia', '$monto_centavos', 'PENDIENTE', '$nombre_safe')";
mysqli_query($conn, $sql_insert);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pago | g502</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #000; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .checkout-container {
            border: 1px solid #d4af37;
            padding: 30px;
            border-radius: 20px;
            background: #111;
            margin-top: 50px;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }
        .price-tag { font-size: 45px; font-weight: bold; color: #d4af37; margin: 20px 0; }
        .wompi-container { margin-top: 30px; }
    </style>
</head>
<body class="text-center">
    <div class="container">
        <div class="checkout-container">
            <h2 style="color: #d4af37; letter-spacing: 2px;">G502 CHECKOUT</h2>
            <hr style="border-color: #333;">
            <p class="lead">Orden para: <br><strong class="text-uppercase"><?php echo htmlspecialchars($nombre_cliente); ?></strong></p>
            <div class="price-tag">$<?php echo number_format($precio_pesos, 0, ',', '.'); ?> <small style="font-size: 15px;">COP</small></div>

            <div class="wompi-container">
                <form>
                    <script
                        src="https://checkout.wompi.co/widget.js"
                        data-render="button"
                        data-public-key="<?php echo $llave_publica; ?>"
                        data-currency="<?php echo $moneda; ?>"
                        data-amount-in-cents="<?php echo $monto_centavos; ?>"
                        data-reference="<?php echo $referencia; ?>"
                        data-signature:integrity="<?php echo $firma; ?>">
                    </script>
                </form>
            </div>

            <p class="mt-4 small text-muted">
                No cierres esta pestaña hasta ver la confirmación de Wompi.<br>
                La venta se registra automáticamente cuando el pago es aprobado.
            </p>
            <div class="mt-2">
                <small class="text-muted">Referencia: <?php echo htmlspecialchars($referencia); ?></small>
            </div>
        </div>
        <div class="mt-3">
            <a href="https://donjorgito.shop/g502/publico1/" class="text-decoration-none" style="color: #666;">&larr; Cancelar y volver</a>
        </div>
    </div>
</body>
</html>