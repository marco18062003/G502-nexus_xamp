<?php
// Ver nota de blindaje de salida en preparar_pago_wompi.php: evita que un
// warning/notice de PHP ensucie la respuesta JSON que espera el navegador.
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
require_once '../config/db.php'; // Aquí ya viene definida tu variable $conn
require_once 'ventas_helper.php';

ob_clean();
header('Content-Type: application/json');

function responderJsonFactura(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

// Recibimos la información del JSON enviado por JS
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['productos'])) {
    responderJsonFactura(['status' => 'error', 'message' => 'El carrito está vacío']);
}

$cajeroId = $_SESSION['user_id'] ?? 1;

try {
    $resultado = registrarVenta(
        $conn,
        $data['productos'],
        (float)$data['total'],
        $data['metodo_pago'],
        (string)$data['cambio'],
        (int)$cajeroId
    );
    responderJsonFactura($resultado);
} catch (Exception $e) {
    responderJsonFactura(['status' => 'error', 'message' => $e->getMessage()]);
}