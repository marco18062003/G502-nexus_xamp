<?php
// add_to_cart_oferta.php - Añade una oferta (descuentos + donjorgito1) al carrito de sesión

header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($conn) || !$conn) {
    $response['message'] = 'Error de conexión a la base de datos.';
    http_response_code(500);
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $ofertaId = $data['oferta_id'] ?? null;
    $quantity = $data['quantity'] ?? null;

    if (
        $ofertaId && is_numeric($ofertaId) &&
        $quantity !== null && is_numeric($quantity) &&
        $quantity > 0 && $quantity <= 100
    ) {
        $ofertaId = (int)$ofertaId;
        $quantity = (int)$quantity;
        $cartKey = 'oferta_' . $ofertaId;

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            $response['success'] = true;
            $response['message'] = 'Cantidad de la oferta actualizada en el carrito.';
        } else {
            // Trae siempre los datos ACTUALES del producto, uniendo con donjorgito1.
            $stmt = mysqli_prepare($conn, "
                SELECT
                    d.description, d.discount_percent, d.discounted_price,
                    p.id AS producto_id, p.producto, p.imagen, p.marca, p.value_final AS precio_original
                FROM descuentos d
                INNER JOIN donjorgito1 p ON d.producto_id = p.id
                WHERE d.id = ? AND d.active = 1
            ");

            if ($stmt === false) {
                $response['message'] = 'Error en la preparación de la consulta SQL: ' . mysqli_error($conn);
                http_response_code(500);
                echo json_encode($response);
                exit();
            }

            mysqli_stmt_bind_param($stmt, "i", $ofertaId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $oferta = mysqli_fetch_assoc($result);

            if ($oferta) {
                if ($oferta['discounted_price'] !== null) {
                    $precioFinal = (float)$oferta['discounted_price'];
                } elseif ($oferta['discount_percent'] !== null) {
                    $precioFinal = round((float)$oferta['precio_original'] * (1 - ((float)$oferta['discount_percent'] / 100)), 0);
                } else {
                    $precioFinal = (float)$oferta['precio_original'];
                }

                $_SESSION['cart'][$cartKey] = [
                    'producto_id' => (int)$oferta['producto_id'],
                    'name' => $oferta['producto'],
                    'price' => $precioFinal,
                    'quantity' => $quantity,
                    'image_name' => $oferta['imagen'],
                    'caracteristica' => $oferta['description'],
                    'brand' => $oferta['marca'],
                    'is_offer' => true
                ];
                $response['success'] = true;
                $response['message'] = 'Oferta añadida al carrito.';
            } else {
                $response['message'] = 'Oferta no encontrada o ya no está activa.';
            }
            mysqli_stmt_close($stmt);
        }

        $total_items_in_cart = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_items_in_cart += $item['quantity'];
        }
        $_SESSION['cart_count'] = $total_items_in_cart;
        $response['cart_total_items'] = $total_items_in_cart;

    } else {
        $response['message'] = 'Datos de oferta (ID o cantidad) inválidos o faltantes.';
        http_response_code(400);
    }
} else {
    $response['message'] = 'Método de solicitud no permitido. Se requiere POST.';
    http_response_code(405);
}

echo json_encode($response);
exit();
?>