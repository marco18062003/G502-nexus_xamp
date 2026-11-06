<?php
require_once '../config/db.php';

if (isset($_GET['query'])) {
    $searchTerm = trim($_GET['query']);
    $searchTerm = mysqli_real_escape_string($conn, $searchTerm);
    
    if (empty($searchTerm)) {
        echo json_encode([]);
        exit;
    }
    
    // USAMOS ALIAS EN EL SELECT (AS plu, AS ean, AS name) para asegurar cómo se llaman las llaves
    $sql = "SELECT PLU AS plu, EAN AS ean, NOMBRE AS name FROM Hoja1 
            WHERE PLU LIKE '%$searchTerm%' 
               OR NOMBRE LIKE '%$searchTerm%' 
               OR EAN LIKE '%$searchTerm%' 
            LIMIT 11";
    
    $result = $conn->query($sql);
    $suggestions = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Ahora mapeamos usando directamente las llaves en minúscula del alias
            $suggestions[] = [
                'plu'  => $row['plu'],
                'ean'  => $row['ean'],   
                'name' => $row['name']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode($suggestions);
}