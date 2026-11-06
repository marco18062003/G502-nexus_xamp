<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$code = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($code === '') {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT PLU, NOMBRE FROM Hoja1 WHERE EAN = ? OR PLU = ? LIMIT 1");
$stmt->bind_param('ss', $code, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'found' => true,
        'plu'   => $row['PLU'],
        'name'  => $row['NOMBRE']
    ]);
} else {
    echo json_encode(['found' => false]);
}