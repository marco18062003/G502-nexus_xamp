<?php
require_once '../config/db.php';

if (isset($_GET['query'])) {
    $searchTerm = '%' . $_GET['query'] . '%';

    $sql = "SELECT PLU, NOMBRE, EAN FROM Hoja1 
            WHERE PLU LIKE ? OR NOMBRE LIKE ? OR EAN LIKE ?
            LIMIT 6";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = [
                'plu'  => $row['PLU'],
                'name' => $row['NOMBRE'],
                'ean'  => $row['EAN']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($suggestions);
}