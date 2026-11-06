<?php
session_start();
require_once '../config/db.php';

// Verificamos que el usuario esté logueado y que llegue un ID de producto
if (isset($_SESSION['user_id']) && isset($_POST['producto_id'])) {
    $user_id = $_SESSION['user_id'];
    $prod_id = intval($_POST['producto_id']);

    // 1. Verificamos si ya existe en favoritos
    $check_sql = "SELECT id_favorito FROM favoritos WHERE usuario_id = ? AND producto_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $prod_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($result_check) > 0) {
        // YA ES FAVORITO -> Lo eliminamos (Toggle off)
        $delete_sql = "DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?";
        $stmt_del = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt_del, "ii", $user_id, $prod_id);
        mysqli_stmt_execute($stmt_del);
        echo "removed";
    } else {
        // NO ES FAVORITO -> Lo agregamos (Toggle on)
        $insert_sql = "INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)";
        $stmt_ins = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt_ins, "ii", $user_id, $prod_id);
        mysqli_stmt_execute($stmt_ins);
        echo "added";
    }
} else {
    echo "error_auth";
}
?>