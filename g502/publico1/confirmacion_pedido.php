<?php
// confirmacion_pedido.php - Procesa el pedido y lo guarda en la base de datos

session_start();

// 1. En producción NUNCA mostramos errores al usuario; los registramos en el log.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once '../config/db.php'; // ASEGÚRATE DE QUE ESTA RUTA ES CORRECTA

$order_id = 'N/A'; // Valor por defecto
$metodo_pago = 'efectivo'; // Valor por defecto, se sobreescribe abajo

/**
 * Muestra un error genérico al usuario y registra el detalle real en el log del servidor.
 */
function fallar_pedido(string $logMessage, string $userMessage = 'Hubo un error al procesar tu pedido. Por favor, inténtalo de nuevo.'): void {
    error_log('[confirmacion_pedido] ' . $logMessage);
    http_response_code(400);
    die(htmlspecialchars($userMessage));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Validación de CSRF: el token debe coincidir con el generado en ver_carrito.php
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        fallar_pedido('CSRF token inválido o ausente.', 'Tu sesión expiró o la solicitud no es válida. Por favor, vuelve al carrito e inténtalo de nuevo.');
    }

    // 3. Recuperar y validar datos del cliente
    $nombre_cliente    = trim($_POST['nombre_cliente'] ?? '');
    $email_cliente     = trim($_POST['email_cliente'] ?? '');
    $telefono_cliente  = trim($_POST['telefono_cliente'] ?? '');
    $direccion_cliente = trim($_POST['direccion_cliente'] ?? '');
    $ciudad_cliente    = trim($_POST['ciudad_cliente'] ?? '');

    if ($nombre_cliente === '' || $email_cliente === '' || $telefono_cliente === '' || $direccion_cliente === '' || $ciudad_cliente === '') {
        fallar_pedido('Faltan campos requeridos del cliente.', 'Por favor, completa todos los campos del formulario.');
    }

    if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
        fallar_pedido("Email inválido: $email_cliente", 'El correo electrónico ingresado no es válido.');
    }

    if (!preg_match('/^[0-9+ ]{7,15}$/', $telefono_cliente)) {
        fallar_pedido("Teléfono inválido: $telefono_cliente", 'El número de teléfono ingresado no es válido.');
    }

    if (mb_strlen($nombre_cliente) > 150 || mb_strlen($direccion_cliente) > 255 || mb_strlen($ciudad_cliente) > 100) {
        fallar_pedido('Uno o más campos exceden la longitud permitida.', 'Uno de los campos ingresados es demasiado largo.');
    }

    // 3b. Validar método de pago (whitelist estricta, nunca confiar en el valor crudo del POST)
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
    if (!in_array($metodo_pago, ['efectivo', 'wompi'], true)) {
        fallar_pedido("Método de pago inválido: $metodo_pago", 'El método de pago seleccionado no es válido.');
    }

    // 4. Recuperar y normalizar el carrito de la sesión
    $rawCart = $_SESSION['cart'] ?? [];
    if (empty($rawCart) || !is_array($rawCart)) {
        fallar_pedido('El carrito está vacío al intentar confirmar el pedido.', 'Tu carrito está vacío. Agrega productos antes de continuar.');
    }

    $cartItems = [];
    foreach ($rawCart as $cartKey => $item) {
        $price    = isset($item['price']) ? (float) $item['price'] : 0;
        $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 0;
        $name     = trim($item['name'] ?? '');

        // IMPORTANTE: el ID real del producto NO siempre es la clave del arreglo.
        // Los ítems agregados desde ofertas.php usan claves tipo "oferta_3", así
        // que el producto_id verdadero viene guardado dentro del propio ítem
        // (ver add_to_cart.php y add_to_cart_oferta.php).
        $productoIdReal = isset($item['producto_id']) && is_numeric($item['producto_id'])
            ? (int) $item['producto_id']
            : (is_numeric($cartKey) ? (int) $cartKey : 0);

        // Si un ítem llega corrupto (sin nombre, precio inválido, cantidad 0, o sin
        // un producto_id real resoluble) no lo incluimos para evitar romper la
        // inserción en detalle_pedido (posible violación de llave foránea).
        if ($name === '' || $price <= 0 || $quantity <= 0 || $productoIdReal <= 0) {
            error_log("[confirmacion_pedido] Ítem de carrito descartado por datos inválidos. Clave: $cartKey, producto_id resuelto: $productoIdReal");
            continue;
        }

        $cartItems[] = [
            'producto_id'    => $productoIdReal,
            'name'           => $name,
            'caracteristica' => $item['caracteristica'] ?? '',
            'price'          => $price,
            'quantity'       => $quantity,
        ];
    }

    if (empty($cartItems)) {
        fallar_pedido('Todos los ítems del carrito eran inválidos tras la normalización.', 'Tu carrito no contiene productos válidos. Por favor, revísalo e inténtalo de nuevo.');
    }

    $costo_domicilio = 0; // Debe coincidir con el valor mostrado en ver_carrito.php
    $totalCartPrice = 0;
    foreach ($cartItems as $item) {
        $totalCartPrice += $item['price'] * $item['quantity'];
    }
    $totalCartPrice += $costo_domicilio;

    // 5. id_cliente: solo confiamos en la sesión, NUNCA en un valor enviado por el cliente.
    $id_cliente = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    // 5b. Si el pago es con Wompi, generamos una referencia única AHORA, antes de guardar,
    //     para poder buscarla luego cuando el webhook confirme el pago.
    $referenciaPago = null;
    $estadoPago = 'no_aplica'; // efectivo: se paga contra entrega, no hay estado de pago que rastrear
    if ($metodo_pago === 'wompi') {
        $referenciaPago = 'PEDIDO_' . time() . '_' . bin2hex(random_bytes(3));
        $estadoPago = 'pendiente';
    }

    mysqli_begin_transaction($conn);

    try {
        // 6. Insertar el pedido en la tabla 'pedidos'
        $sql_pedido = "INSERT INTO pedidos 
                       (id_cliente, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, ciudad_cliente, total_final, total_pedido, fecha_pedido, estado_pedido, metodo_pago, referencia_pago, estado_pago) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente', ?, ?, ?)";

        $stmt_pedido = mysqli_prepare($conn, $sql_pedido);
        if (!$stmt_pedido) {
            throw new Exception("Error al preparar la consulta de pedido: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt_pedido, "isssssddsss",
            $id_cliente,
            $nombre_cliente,
            $email_cliente,
            $telefono_cliente,
            $direccion_cliente,
            $ciudad_cliente,
            $totalCartPrice,
            $totalCartPrice,
            $metodo_pago,
            $referenciaPago,
            $estadoPago
        );

        if (!mysqli_stmt_execute($stmt_pedido)) {
            throw new Exception("Error al ejecutar la inserción del pedido: " . mysqli_stmt_error($stmt_pedido));
        }

        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_pedido);

        // 7. Insertar los detalles del pedido
        $sql_detalle = "INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, caracteristica, cantidad, precio_unitario, total) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_detalle = mysqli_prepare($conn, $sql_detalle);
        if (!$stmt_detalle) {
            throw new Exception("Error al preparar la consulta de detalle de pedido: " . mysqli_error($conn));
        }

        foreach ($cartItems as $item) {
            $prod_id     = $item['producto_id'];
            $line_total  = $item['price'] * $item['quantity'];

            mysqli_stmt_bind_param($stmt_detalle, "iissidd",
                $order_id,
                $prod_id,
                $item['name'],
                $item['caracteristica'],
                $item['quantity'],
                $item['price'],
                $line_total
            );

            if (!mysqli_stmt_execute($stmt_detalle)) {
                throw new Exception("Error al ejecutar la inserción del detalle: " . mysqli_stmt_error($stmt_detalle));
            }
        }

        mysqli_stmt_close($stmt_detalle);
        mysqli_commit($conn);

        // 8. Vaciar el carrito y rotar el token CSRF tras un pedido exitoso.
        unset($_SESSION['cart']);
        unset($_SESSION['csrf_token']);

        // 8b. Si es Wompi, redirigimos a la pantalla de pago.
        if ($metodo_pago === 'wompi') {
            header('Location: pagar_pedido_tienda.php?ref=' . urlencode($referenciaPago));
            exit;
        }

    } catch (Exception $e) {
        mysqli_rollback($conn);
        fallar_pedido('Error al guardar el pedido: ' . $e->getMessage());
    }
    // Nota: no cerramos $conn aquí a propósito — includes/header.php reutiliza
    // esta misma conexión más abajo. PHP la cierra automáticamente al terminar el script.

}
?>
<?php include 'includes/header.php'; ?>
<link rel="stylesheet" type="text/css" href="assets/css/confirmacion_pedido.css">

<main class="container">
    <div class="confirmation-container">
        <i class="fas fa-check-circle"></i>
        <h1>¡Pedido Confirmado!</h1>

        <?php if (!empty($nombre_cliente)): ?>
            <p>Gracias, <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong>, por tu compra en Don Jorgito.</p>
        <?php else: ?>
            <p>Gracias por tu compra en Don Jorgito.</p>
        <?php endif; ?>

        <p>Tu pedido con ID: <span class="order-id">#<?php echo htmlspecialchars($order_id); ?></span> ha sido recibido con éxito.</p>

        <?php if (!empty($id_cliente)): ?>
            <p class="customer-id">Asociado a tu cuenta de cliente <strong>#<?php echo (int) $id_cliente; ?></strong>.</p>
        <?php endif; ?>

        <p>Pagarás <strong>en efectivo contra entrega</strong>. Hemos enviado una confirmación a tu correo electrónico. Te contactaremos pronto para coordinar la entrega.</p>
        <a href="index.php" class="btn-continue">Volver a la Tienda</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>