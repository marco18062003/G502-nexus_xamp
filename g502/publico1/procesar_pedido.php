<?php
// procesar_pedido.php - Guarda el pedido en la BD y envía un correo electrónico

session_start(); // ¡Importante! Siempre al inicio del archivo

// Asegúrate de que este archivo exista y la conexión a la BD sea correcta
require_once '../config/db.php'; 

// --- Configuración de Correo ---
// Cambia estas direcciones por las reales de tu tienda.
// Es crucial que el 'From' sea de un dominio válido para evitar problemas de SPAM.
$admin_email = "tu_correo_admin@donjorgito.com"; // <-- CORREO DEL ADMINISTRADOR/PROPIETARIO DE LA TIENDA
$from_email = "no-responder@tudominio.com"; // <-- CAMBIA 'tudominio.com' por el dominio real de tu sitio web
$reply_to_email = $admin_email; // Las respuestas irán al administrador


// Solo procesa si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger datos del cliente desde el formulario y sanitizarlos
    $nombre_cliente = htmlspecialchars(trim($_POST['nombre_cliente'] ?? ''));
    $email_cliente = htmlspecialchars(trim($_POST['email_cliente'] ?? ''));
    $telefono_cliente = htmlspecialchars(trim($_POST['telefono_cliente'] ?? ''));
    $direccion_cliente = htmlspecialchars(trim($_POST['direccion_cliente'] ?? ''));
    $ciudad_cliente = htmlspecialchars(trim($_POST['ciudad_cliente'] ?? ''));

    // --- Validación de datos de entrada ---
    $errors = []; // Array para guardar errores de validación

    if (empty($nombre_cliente)) $errors[] = "El nombre del cliente es obligatorio.";
    if (empty($email_cliente)) $errors[] = "El email del cliente es obligatorio.";
    if (empty($telefono_cliente)) $errors[] = "El teléfono del cliente es obligatorio.";
    if (empty($direccion_cliente)) $errors[] = "La dirección del cliente es obligatoria.";
    if (empty($ciudad_cliente)) $errors[] = "La ciudad del cliente es obligatoria.";
    
    if (!empty($email_cliente) && !filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }

    // Si hay errores de validación, redirigir o mostrar un mensaje
    if (!empty($errors)) {
        die("Errores de validación: <br>" . implode("<br>", $errors));
    }

    // Asegurarse de que el carrito no esté vacío
    if (empty($_SESSION['cart'])) {
        header("Location: ver_carrito.php?error=carrito_vacio");
        exit();
    }

    $cartItems = $_SESSION['cart'];
    $totalPedido = 0;

    // Calcular el total del pedido
    foreach ($cartItems as $item) {
        $price = is_numeric($item['price']) ? (float)$item['price'] : 0;
        $quantity = is_numeric($item['quantity']) ? (int)$item['quantity'] : 0;
        $totalPedido += $price * $quantity;
    }

    // 2. Iniciar transacción para asegurar la integridad de los datos
    mysqli_autocommit($conn, FALSE); // Desactivar el auto-commit
    $insert_pedido_success = false;
    $id_pedido = 0; // Inicializar por si falla

    // 3. Guardar el pedido en la tabla `pedidos`
    $stmt_pedido = mysqli_prepare($conn, "INSERT INTO pedidos (nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, ciudad_cliente, total_pedido, estado_pedido) VALUES (?, ?, ?, ?, ?, ?, 'pendiente')");
    
    if ($stmt_pedido === false) {
        error_log("Error de preparación de statement para pedidos: " . mysqli_error($conn));
        mysqli_rollback($conn);
        die("Error interno al procesar el pedido. Por favor, inténtalo de nuevo más tarde. (Código: P1)");
    }

    // 'sssssd' -> string, string, string, string, string, double (tipos de las variables)
    mysqli_stmt_bind_param($stmt_pedido, "sssssd", $nombre_cliente, $email_cliente, $telefono_cliente, $direccion_cliente, $ciudad_cliente, $totalPedido);

    if (mysqli_stmt_execute($stmt_pedido)) {
        $id_pedido = mysqli_insert_id($conn); // Obtener el ID del pedido recién insertado
        $insert_pedido_success = true;
    } else {
        error_log("Error al insertar pedido: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt_pedido);

    // 4. Guardar los detalles del pedido en la tabla `detalle_pedido`
    $insert_detalle_success = true; // Asumimos éxito inicialmente
    if ($insert_pedido_success) { // Solo si el pedido principal se insertó bien
        foreach ($cartItems as $productId => $item) {
            $nombre_producto = $item['name'] ?? 'Producto Desconocido';
            $cantidad = $item['quantity'] ?? 0;
            $precio_unitario = $item['price'] ?? 0;
            $caracteristica_producto = $item['caracteristica'] ?? NULL; // Obtener la característica

            // Asegúrate de que los tipos sean correctos para bind_param
            $product_id_int = (int)$productId;
            $cantidad_int = (int)$cantidad;
            $precio_unitario_float = (float)$precio_unitario;
            $total_linea = $precio_unitario_float * $cantidad_int; // Total de esta línea del pedido

            // --- Obtener el EAN del producto ---
            // El carrito no guarda el EAN, así que lo buscamos en la tabla productos.
            // AJUSTA el nombre de la tabla/columnas si en tu BD se llaman distinto.
            $ean_producto = $item['ean'] ?? null; // por si en algún momento sí viene en el carrito

            if (empty($ean_producto)) {
                $stmt_ean = mysqli_prepare($conn, "SELECT ean FROM donjorgito1 WHERE id = ? LIMIT 1");
                if ($stmt_ean !== false) {
                    mysqli_stmt_bind_param($stmt_ean, "i", $product_id_int);
                    mysqli_stmt_execute($stmt_ean);
                    mysqli_stmt_bind_result($stmt_ean, $ean_result);
                    if (mysqli_stmt_fetch($stmt_ean)) {
                        $ean_producto = $ean_result;
                    }
                    mysqli_stmt_close($stmt_ean);
                } else {
                    error_log("Error al preparar la búsqueda de EAN: " . mysqli_error($conn));
                }
            }
            $ean_producto = $ean_producto ?? ''; // fallback para no insertar NULL si la columna no lo permite

            // Prepara la consulta incluyendo total y ean
            // i(id_pedido) i(id_producto) s(nombre) s(caracteristica) i(cantidad) d(precio_unitario) d(total) s(ean)
            $stmt_detalle = mysqli_prepare($conn, "INSERT INTO detalle_pedido (id_pedido, id_producto, nombre_producto, caracteristica, cantidad, precio_unitario, total, ean) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt_detalle === false) {
                error_log("Error de preparación de statement para detalle_pedido: " . mysqli_error($conn));
                $insert_detalle_success = false;
                break; // Salir del bucle si hay un error crítico
            }
            
            // i(id_pedido) i(id_producto) s(nombre) s(caracteristica) i(cantidad) d(precio) d(total) s(ean) = 8 tipos, 8 variables
            mysqli_stmt_bind_param(
                $stmt_detalle,
                "iissidds",
                $id_pedido, $product_id_int, $nombre_producto, $caracteristica_producto,
                $cantidad_int, $precio_unitario_float, $total_linea, $ean_producto
            );

            if (!mysqli_stmt_execute($stmt_detalle)) {
                $insert_detalle_success = false;
                error_log("Error al insertar detalle de pedido para producto ID " . $productId . ": " . mysqli_error($conn));
                break; // Salir del bucle si hay un error en la inserción de detalles
            }
            mysqli_stmt_close($stmt_detalle);
        }
    }

    // 5. Confirmar o revertir la transacción
    if ($insert_pedido_success && $insert_detalle_success) {
        mysqli_commit($conn); // Confirmar todas las operaciones si todo fue bien
        
        // 6. Vaciar el carrito después de un pedido exitoso
        unset($_SESSION['cart']); 

        // 7. Enviar correo electrónico de notificación (al administrador y al cliente)
        
        $orderDetailsMessage = "";
        foreach ($cartItems as $item) {
            $char_display = !empty($item['caracteristica']) ? " (" . htmlspecialchars($item['caracteristica']) . ")" : "";
            $orderDetailsMessage .= "- " . htmlspecialchars($item['name']) . $char_display . " (x" . htmlspecialchars($item['quantity']) . ") - $" . number_format(htmlspecialchars($item['price']), 0, ',', '.') . " c/u\n";
        }

        // --- Correo para el Administrador ---
        $email_subject_admin = "Nuevo Pedido de Don Jorgito (#" . $id_pedido . ")";
        $email_message_admin = "Se ha realizado un nuevo pedido en tu tienda:\n\n";
        $email_message_admin .= "ID del Pedido: #" . $id_pedido . "\n";
        $email_message_admin .= "Cliente: " . $nombre_cliente . "\n";
        $email_message_admin .= "Email: " . $email_cliente . "\n";
        $email_message_admin .= "Teléfono: " . $telefono_cliente . "\n";
        $email_message_admin .= "Dirección: " . $direccion_cliente . ", " . $ciudad_cliente . "\n";
        $email_message_admin .= "Total del Pedido: $" . number_format($totalPedido, 0, ',', '.') . "\n\n";
        $email_message_admin .= "Detalles del Pedido:\n" . $orderDetailsMessage;
        $email_message_admin .= "\nAccede a tu panel de administración para ver los detalles completos.";

        $headers_admin = "From: " . $from_email . "\r\n";
        $headers_admin .= "Reply-To: " . $reply_to_email . "\r\n";
        $headers_admin .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $mail_sent_admin = mail($admin_email, $email_subject_admin, $email_message_admin, $headers_admin);
        if (!$mail_sent_admin) {
            error_log("Error al enviar correo al administrador para el pedido #" . $id_pedido);
        }

        // --- Correo para el Cliente ---
        $email_subject_client = "Confirmación de Pedido #" . $id_pedido . " en Don Jorgito";
        $email_message_client = "¡Gracias por tu compra, " . $nombre_cliente . "!\n\n";
        $email_message_client .= "Hemos recibido tu pedido (#" . $id_pedido . ") con éxito.\n\n";
        $email_message_client .= "Detalles de tu pedido:\n" . $orderDetailsMessage;
        $email_message_client .= "\nTotal del Pedido: $" . number_format($totalPedido, 0, ',', '.') . "\n";
        $email_message_client .= "\nTe contactaremos pronto para coordinar la entrega.\n\n";
        $email_message_client .= "Atentamente,\nEl equipo de Don Jorgito";

        $headers_client = "From: " . $from_email . "\r\n";
        $headers_client .= "Reply-To: " . $reply_to_email . "\r\n";
        $headers_client .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        $mail_sent_client = mail($email_cliente, $email_subject_client, $email_message_client, $headers_client);
        if (!$mail_sent_client) {
            error_log("Error al enviar correo al cliente para el pedido #" . $id_pedido . ": " . $email_cliente);
        }

        // 8. Redirigir a una página de confirmación exitosa
        header("Location: confirmacion_pedido.php?id=" . $id_pedido);
        exit();

    } else {
        mysqli_rollback($conn); // Revertir todas las operaciones si algo falló
        error_log("Fallo al procesar pedido completo para cliente: " . $email_cliente . ". Detalles: " . mysqli_error($conn));
        header("Location: ver_carrito.php?error=fallo_al_procesar_pedido");
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}

mysqli_close($conn); 
?>