<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Buscamos en todas las columnas que pediste + la característica para diferenciar
$sql = "SELECT id, producto, caracteristica, precio, plu, ean 
        FROM donjorgito1 
        WHERE producto LIKE ? 
        OR id = ? 
        OR plu = ? 
        OR ean = ? 
        ORDER BY producto ASC 
        LIMIT 100";

$stmt = mysqli_prepare($conn, $sql);
$likeTerm = "%$q%";
mysqli_stmt_bind_param($stmt, "ssss", $likeTerm, $q, $q, $q);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$productos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $productos[] = $row;
}

echo json_encode($productos);