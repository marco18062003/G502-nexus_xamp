<?php
// eliminar_oferta.php - Elimina una oferta de la tabla `descuentos`

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
$ofertaId = $data['oferta_id'] ?? null;

if (!$ofertaId || !is_numeric($ofertaId)) {
    $response['message'] = 'ID de oferta inválido.';
    echo json_encode($response);
    exit();
}

$ofertaId = (int)$ofertaId;

$stmt = mysqli_prepare($conn, "DELETE FROM descuentos WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $ofertaId);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $response['success'] = true;
        $response['message'] = 'Oferta eliminada correctamente.';
    } else {
        $response['message'] = 'No se encontró la oferta a eliminar.';
    }
} else {
    $response['message'] = 'Error al eliminar la oferta: ' . mysqli_stmt_error($stmt);
    http_response_code(500);
}

mysqli_stmt_close($stmt);
echo json_encode($response);
exit();
?>