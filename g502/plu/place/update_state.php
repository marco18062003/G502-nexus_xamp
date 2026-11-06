<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

$id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$state = isset($_POST['state']) ? trim($_POST['state']) : '';

// Solo se permiten estos dos valores para evitar datos basura
if (!in_array($state, ['X', ''], true) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$stmt = $conn->prepare("UPDATE place_expiration SET state = ? WHERE id = ?");
$stmt->bind_param('si', $state, $id);

if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'id' => $id, 'state' => $state]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar']);
}