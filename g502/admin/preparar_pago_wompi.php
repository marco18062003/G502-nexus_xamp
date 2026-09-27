<?php
/**
 * preparar_pago_wompi.php
 *
 * El POS llama aquí ANTES de abrir la pestaña de Wompi.
 * Guardamos el carrito completo (no solo el total) asociado a una referencia única,
 * para que cuando llegue el webhook de confirmación de pago, sepamos EXACTAMENTE
 * qué productos facturar.
 */

// --- Blindaje de salida limpia ---
// Si algo (un warning, un notice, un include con espacio de más, etc.) imprime
// texto ANTES de nuestro json_encode, el navegador recibe una respuesta que ya
// no es JSON válido y falla al parsearla con fetch().json(), aunque el INSERT
// en la base de datos se haya hecho bien. Por eso capturamos TODO lo que se
// imprima y lo descartamos antes de mandar nuestra respuesta.
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // no imprimir errores al navegador, solo loguearlos

session_start();
require_once '../config/db.php';

// Limpiamos cualquier salida que db.php u otros includes hayan podido generar
// (BOM, espacios en blanco, warnings de conexión, etc.)
ob_clean();

header('Content-Type: application/json');

function responderJson(array $payload): void
{
    // Por si algo más quedó en el buffer entre este punto y la respuesta final
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['productos']) || empty($data['total'])) {
    responderJson(['status' => 'error', 'message' => 'Carrito o total inválido']);
}

$total = (int)$data['total'];
if ($total <= 0) {
    responderJson(['status' => 'error', 'message' => 'Total inválido']);
}

$clienteNombre = $data['customer_name'] ?? 'Cliente G502';
$cajeroId = $_SESSION['user_id'] ?? 1;
$referencia = "G502_" . time() . "_" . bin2hex(random_bytes(3)); // única y no adivinable
$carritoJson = json_encode($data['productos']);

$sql = "INSERT INTO venta_pendiente (referencia_unica, carrito_json, cliente_nombre, total, cajero_id, estado)
        VALUES (?, ?, ?, ?, ?, 'PENDIENTE')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssii", $referencia, $carritoJson, $clienteNombre, $total, $cajeroId);

if (!mysqli_stmt_execute($stmt)) {
    responderJson(['status' => 'error', 'message' => 'No se pudo guardar la venta pendiente: ' . mysqli_error($conn)]);
}

responderJson(['status' => 'success', 'referencia' => $referencia]);