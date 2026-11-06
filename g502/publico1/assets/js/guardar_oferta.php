<?php
// guardar_oferta.php - Crea o edita una oferta en la tabla `descuentos`

header('Content-Type: application/json');
session_start();
require_once '../../../config/db.php';

$response = ['success' => false, 'message' => ''];

// ⚠️ Protección básica: solo usuarios con sesión iniciada.
// TODO: reemplazar por una verificación de rol de administrador real.
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if (!$is_logged_in) {
    http_response_code(403);
    $response['message'] = 'No autorizado.';
    echo json_encode($response);
    exit();
}

if (!isset($conn) || !$conn) {
    http_response_code(500);
    $response['message'] = 'Error de conexión a la base de datos.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Método no permitido. Se requiere POST.';
    echo json_encode($response);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$ofertaId      = $data['oferta_id'] ?? null;        // null = crear nueva, número = editar existente
$productoId    = $data['producto_id'] ?? null;
$description   = trim($data['description'] ?? '');
$discountType  = $data['discount_type'] ?? '';        // 'percent' o 'fixed'
$discountValue = $data['discount_value'] ?? null;
$active        = isset($data['active']) && $data['active'] ? 1 : 0;

// --- Validaciones ---
if (!$productoId || !is_numeric($productoId)) {
    $response['message'] = 'Debes seleccionar un producto válido.';
    echo json_encode($response);
    exit();
}

if ($description === '' || mb_strlen($description) > 255) {
    $response['message'] = 'La descripción de la oferta es obligatoria (máx. 255 caracteres).';
    echo json_encode($response);
    exit();
}

if (!in_array($discountType, ['percent', 'fixed'], true)) {
    $response['message'] = 'Tipo de descuento inválido.';
    echo json_encode($response);
    exit();
}

if ($discountValue === null || !is_numeric($discountValue) || $discountValue <= 0) {
    $response['message'] = 'El valor del descuento debe ser un número mayor que 0.';
    echo json_encode($response);
    exit();
}

if ($discountType === 'percent' && $discountValue > 100) {
    $response['message'] = 'El porcentaje de descuento no puede ser mayor a 100.';
    echo json_encode($response);
    exit();
}

$productoId = (int)$productoId;
$discountValue = (float)$discountValue;

// --- Verifica que el producto exista en donjorgito1 ---
$stmtCheck = mysqli_prepare($conn, "SELECT id FROM donjorgito1 WHERE id = ?");
mysqli_stmt_bind_param($stmtCheck, "i", $productoId);
mysqli_stmt_execute($stmtCheck);
mysqli_stmt_store_result($stmtCheck);
if (mysqli_stmt_num_rows($stmtCheck) === 0) {
    $response['message'] = 'El producto seleccionado no existe.';
    echo json_encode($response);
    mysqli_stmt_close($stmtCheck);
    exit();
}
mysqli_stmt_close($stmtCheck);

// --- Prepara los valores según el tipo de descuento ---
$discountPercent = ($discountType === 'percent') ? $discountValue : null;
$discountedPrice = ($discountType === 'fixed') ? $discountValue : null;

if ($ofertaId && is_numeric($ofertaId)) {
    // --- EDITAR oferta existente ---
    $ofertaId = (int)$ofertaId;
    $stmt = mysqli_prepare($conn, "
        UPDATE descuentos
        SET producto_id = ?, description = ?, discount_percent = ?, discounted_price = ?, active = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param(
        $stmt, "isddii",
        $productoId, $description, $discountPercent, $discountedPrice, $active, $ofertaId
    );
    $actionMessage = 'Oferta actualizada correctamente.';
} else {
    // --- CREAR nueva oferta ---
    $stmt = mysqli_prepare($conn, "
        INSERT INTO descuentos (producto_id, description, discount_percent, discounted_price, active)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param(
        $stmt, "isddi",
        $productoId, $description, $discountPercent, $discountedPrice, $active
    );
    $actionMessage = 'Oferta creada correctamente.';
}

if ($stmt === false) {
    $response['message'] = 'Error al preparar la consulta: ' . mysqli_error($conn);
    http_response_code(500);
    echo json_encode($response);
    exit();
}

if (mysqli_stmt_execute($stmt)) {
    $response['success'] = true;
    $response['message'] = $actionMessage;
} else {
    $response['message'] = 'Error al guardar la oferta: ' . mysqli_stmt_error($stmt);
    http_response_code(500);
}

mysqli_stmt_close($stmt);
echo json_encode($response);
exit();
?>