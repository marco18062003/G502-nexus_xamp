<?php
// buscar_producto_admin.php - Endpoint AJAX para el buscador de productos en ofertaedit.php
// Devuelve coincidencias de donjorgito1 en formato JSON.

header('Content-Type: application/json');
session_start();
require_once '../../../config/db.php';

// ⚠️ Protección básica: solo usuarios con sesión iniciada.
// TODO: reemplazar por una verificación de rol de administrador real.
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if (!$is_logged_in) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado.']);
    exit();
}

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos.']);
    exit();
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$stmt = mysqli_prepare($conn, "
    SELECT id, producto, marca, imagen, value_final, plu
    FROM donjorgito1
    WHERE producto LIKE CONCAT('%', ?, '%')
       OR marca LIKE CONCAT('%', ?, '%')
       OR plu LIKE CONCAT('%', ?, '%')
       OR ean LIKE CONCAT('%', ?, '%')
    ORDER BY producto ASC
    LIMIT 10
");

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta SQL: ' . mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "ssss", $query, $query, $query, $query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$productos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $productos[] = [
        'id' => (int)$row['id'],
        'producto' => $row['producto'],
        'marca' => $row['marca'],
        'imagen' => $row['imagen'],
        'value_final' => (float)$row['value_final'],
        'plu' => $row['plu'],
    ];
}

mysqli_stmt_close($stmt);
echo json_encode($productos);
exit();
?>