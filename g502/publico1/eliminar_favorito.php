<?php
session_start();
require_once '../config/db.php';

// 1. Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Verificar que se haya enviado un ID válido por la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $producto_id = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Ejecutar la eliminación
    // Solo borramos si el favorito pertenece al usuario actual (por seguridad)
    $sql_delete = "DELETE FROM favoritos WHERE producto_id = '$producto_id' AND usuario_id = '$user_id'";
    
    if (mysqli_query($conn, $sql_delete)) {
        // Éxito: Redirigir de vuelta a favoritos
        header("Location: favoritos.php?mensaje=eliminado");
        exit;
    } else {
        // Error de base de datos
        echo "Error al intentar eliminar: " . mysqli_error($conn);
    }
} else {
    // Si no hay ID, volvemos a favoritos
    header("Location: favoritos.php");
    exit;
}
?>