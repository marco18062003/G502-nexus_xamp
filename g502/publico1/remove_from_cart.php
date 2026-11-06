<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['product_id'])) {
    $id = $data['product_id'];

    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);

        $total_cart_price = 0;
        $new_cart_total_items = 0;

        foreach ($_SESSION['cart'] as $item) {
            $total_cart_price += $item['price'] * $item['quantity'];
            $new_cart_total_items += $item['quantity'];
        }

        echo json_encode([
            'success' => true,
            'total_cart_price' => $total_cart_price,
            'new_cart_total_items' => $new_cart_total_items
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'No se pudo eliminar']);