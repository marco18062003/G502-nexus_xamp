<?php
// fetch_order_details.php - Endpoint para obtener los productos de un pedido por ID

session_start();
header('Content-Type: application/json');

// 1. CONEXIÓN: Asume que db.php está un nivel arriba.
require_once '../config/db.php'; 

if (!$conn) {
    // Esto solo ocurrirá si la conexión falló en db.php
    echo json_encode(['success' => false, 'message' => 'Error: No se pudo conectar a la base de datos.']);
    exit;
}

// 2. Obtener el ID del pedido (viene por GET en la URL)
$orderId = $_GET['id'] ?? null;

// Validación básica del ID
if (!filter_var($orderId, FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido.']);
    mysqli_close($conn);
    exit;
}

// 3. Consulta de los detalles del pedido, usando tus columnas:
// nombre_producto, caracteristica, cantidad, precio_unitario, total y ean
$sql = "SELECT nombre_producto, caracteristica, cantidad, precio_unitario, total, ean 
        FROM detalle_pedido 
        WHERE id_pedido = ?";

try {
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt === false) {
        // En un entorno de producción, esto debería ir a un log, no al usuario.
        throw new Exception("Error al preparar la consulta SQL. Puede que la tabla 'detalle_pedido' no exista o las columnas sean incorrectas.");
    }
    
    mysqli_stmt_bind_param($stmt, "i", $orderId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $details = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $details[] = $row;
    }
    
    echo json_encode(['success' => true, 'details' => $details]);
    mysqli_stmt_close($stmt);

} catch (\Exception $e) {
    // Manejo de errores que captura problemas de conexión o consulta
    echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>