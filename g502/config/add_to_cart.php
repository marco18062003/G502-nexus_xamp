<?php
// add_to_cart.php - Procesa la adición de productos al carrito de la sesión

// 1. ESTABLECE EL ENCABEZADO DE LA RESPUESTA PRIMERO: ¡CRÍTICO!
// Debe ir antes de cualquier otra salida para evitar errores de "headers already sent".
header('Content-Type: application/json');

// 2. INICIA LA SESIÓN: Esto es fundamental para que el carrito persista entre solicitudes.
session_start();

// 3. INCLUYE LA CONEXIÓN A LA BASE DE DATOS
require_once 'db.php';

// 4. PREPARA EL ARRAY DE RESPUESTA
$response = ['success' => false, 'message' => ''];

// 5. VERIFICA QUE LA CONEXIÓN A LA BASE DE DATOS EXISTA
if (!isset($conn) || !$conn) {
    $response['message'] = 'Error de conexión a la base de datos.';
    http_response_code(500);
    echo json_encode($response);
    exit();
}

// 6. INICIALIZA EL CARRITO EN LA SESIÓN si aún no existe.
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 7. VERIFICA EL MÉTODO DE LA SOLICITUD: Solo procesamos solicitudes POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 8. OBTIENE Y DECODIFICA LOS DATOS JSON ENVIADOS DESDE JAVASCRIPT.
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 9. EXTRAE LOS DATOS DE PRODUCTO Y CANTIDAD.
    $productId = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? null;

    // 10. VALIDA LA ENTRADA: incluye un límite superior razonable de cantidad.
    if (
        $productId && is_numeric($productId) &&
        $quantity !== null && is_numeric($quantity) &&
        $quantity > 0 && $quantity <= 100
    ) {
        $productId = (int)$productId;
        $quantity = (int)$quantity;

        // 11. LÓGICA DEL CARRITO: Actualiza la cantidad si el producto ya existe, o lo añade.
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
            $response['success'] = true;
            $response['message'] = 'Cantidad del producto actualizada en el carrito.';
        } else {
            // Si es un producto nuevo, OBTÉN SUS DETALLES DE LA BASE DE DATOS.
            $stmt = mysqli_prepare($conn, "SELECT producto, precio, imagen, caracteristica FROM donjorgito1 WHERE id = ?");

            if ($stmt === false) {
                $response['message'] = 'Error en la preparación de la consulta SQL: ' . mysqli_error($conn);
                http_response_code(500);
                echo json_encode($response);
                exit();
            }

            mysqli_stmt_bind_param($stmt, "i", $productId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);

            // 12. SI EL PRODUCTO SE ENCONTRÓ EN LA DB, AÑÁDELO AL CARRITO DE SESIÓN.
            if ($product) {
                $_SESSION['cart'][$productId] = [
                    'producto_id' => $productId,
                    'name' => $product['producto'],
                    'price' => $product['precio'],
                    'quantity' => $quantity,
                    'image_name' => $product['imagen'],
                    'caracteristica' => $product['caracteristica']
                ];
                $response['success'] = true;
                $response['message'] = 'Producto añadido al carrito.';
            } else {
                $response['message'] = 'Producto no encontrado en la base de datos con el ID proporcionado.';
            }
            mysqli_stmt_close($stmt);
        }

        // 13. CALCULA EL TOTAL DE ÍTEMS EN EL CARRITO para poder actualizar el frontend.
        $total_items_in_cart = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_items_in_cart += $item['quantity'];
        }

        // Sincroniza el contador de sesión para que persista entre recargas de página.
        $_SESSION['cart_count'] = $total_items_in_cart;
        $response['cart_total_items'] = $total_items_in_cart;

    } else {
        $response['message'] = 'Datos de producto (ID o cantidad) inválidos o faltantes en la solicitud.';
        http_response_code(400);
    }
} else {
    // 14. SI LA SOLICITUD NO ES POST, ENVIAR UN MENSAJE DE ERROR.
    $response['message'] = 'Método de solicitud no permitido. Se requiere POST.';
    http_response_code(405);
}

// 15. ENCODIFICA LA RESPUESTA PHP A JSON Y LA ENVÍA AL NAVEGADOR.
echo json_encode($response);

// 16. TERMINA LA EJECUCIÓN DEL SCRIPT.
exit();
?>