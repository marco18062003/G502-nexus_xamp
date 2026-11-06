<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $prod_id = $_GET['id'];

    // Insertamos la relación
    $sql = "INSERT INTO favoritos (usuario_id, producto_id) VALUES ('$user_id', '$prod_id')";
    mysqli_query($conn, $sql);
    
    header("Location: favoritos.php"); // Lo mandamos a ver sus favoritos
} else {
    header("Location: login.php"); // Si no está logueado, a loguearse
}
?>
