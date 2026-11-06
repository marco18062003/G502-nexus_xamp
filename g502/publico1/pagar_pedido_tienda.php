<?php
// pagar_pedido_tienda.php
// Pantalla de pago con Wompi para pedidos hechos por clientes desde la tienda online.
// Equivalente a admin/paymeth2.php, pero para la tabla `pedidos` en vez de `venta_pendiente`.

session_start();
require_once '../config/db.php';

$referencia = $_GET['ref'] ?? '';
if ($referencia === '' || strpos($referencia, 'PEDIDO_') !== 0) {
    exit("<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#d4af37;'>Referencia inválida</h1>
            <p>Este link de pago no es válido.</p>
            <a href='index.php' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver a la tienda</a>
          </div>");
}

$stmt = mysqli_prepare($conn, "SELECT id, nombre_cliente, total_final, estado_pago FROM pedidos WHERE referencia_pago = ?");
mysqli_stmt_bind_param($stmt, "s", $referencia);
mysqli_stmt_execute($stmt);
$pedido = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$pedido) {
    exit("<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#d4af37;'>Pedido no encontrado</h1>
            <a href='index.php' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver a la tienda</a>
          </div>");
}

if ($pedido['estado_pago'] === 'pagado') {
    exit("<div style='background:#000; color:#fff; text-align:center; padding-top:100px; font-family:sans-serif;'>
            <h1 style='color:#2ecc71;'>¡Este pedido ya fue pagado!</h1>
            <p>Pedido #" . (int)$pedido['id'] . "</p>
            <a href='index.php' style='color:#d4af37; text-decoration:none; border:1px solid #d4af37; padding:10px 20px; border-radius:5px;'>Volver a la tienda</a>
          </div>");
}

if ($pedido['estado_pago'] === 'fallido') {
    // Permitimos reintentar: no bloqueamos, solo avisamos.
    // (El botón de Wompi de abajo generará un nuevo intento de pago sobre la misma referencia.)
}

$precio_pesos = (float)$pedido['total_final'];
$nombre_cliente = $pedido['nombre_cliente'];
$monto_centavos = (int)($precio_pesos * 100);
$moneda = "COP";

// Mismas llaves de producción que ya usas en admin/paymeth2.php
$llave_publica = "pub_prod_Id4Oj4RzKFYJrkitudFtYLIQ4DxwBmNo";
$secreto_integridad = "prod_integrity_v1z7jKwqxZdShVUtooJoQsCeTJiOvKQ7";

$cadena = $referencia . $monto_centavos . $moneda . $secreto_integridad;
$firma = hash("sha256", $cadena);

// A dónde manda Wompi al cliente cuando termina de pagar (aprobado, rechazado o cancelado).
// Aquí es donde arreglamos el problema de "se queda en la misma pantalla":
// antes no existía este atributo, así que el widget no navegaba a ningún lado.
$redirectUrl = "https://donjorgito.shop/g502/publico1/billshow.php?ref=" . urlencode($referencia);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar mi pedido | Don Jorgito</title>
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
            <h2 style="color: #d4af37; letter-spacing: 2px;">DON JORGITO</h2>
            <hr style="border-color: #333;">
            <p class="lead">Pedido para: <br><strong class="text-uppercase"><?php echo htmlspecialchars($nombre_cliente); ?></strong></p>
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
                        data-redirect-url="<?php echo htmlspecialchars($redirectUrl); ?>"
                        data-signature:integrity="<?php echo $firma; ?>">
                    </script>
                </form>
            </div>

            <p class="mt-4 small text-muted">
                No cierres esta pestaña hasta ver la confirmación de Wompi.<br>
                Recibirás la confirmación de tu pedido por correo cuando el pago sea aprobado.
            </p>
            <div class="mt-2">
                <small class="text-muted">Pedido #<?php echo (int)$pedido['id']; ?> · Referencia: <?php echo htmlspecialchars($referencia); ?></small>
            </div>
        </div>
        <div class="mt-3">
            <a href="index.php" class="text-decoration-none" style="color: #666;">&larr; Cancelar y volver a la tienda</a>
        </div>
    </div>
</body>
</html>