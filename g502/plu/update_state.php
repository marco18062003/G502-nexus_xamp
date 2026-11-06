<?php
require_once '../config/db.php';

// Verificamos que lleguen los datos
if (isset($_POST['id']) && isset($_POST['state'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);

    // Usamos comillas simples para el valor de state
    $sql = "UPDATE plu_products SET state = '$state' WHERE id = '$id'";
    
    if($conn->query($sql)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>