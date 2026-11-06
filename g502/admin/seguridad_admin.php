<?php
// admin/seguridad_admin.php
// Este archivo protege tus páginas de administrador Y establece la conexión a la DB.

session_start();

// 1. Verificar si la sesión existe y el rol de administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: ../publico1/login.php'); 
    exit();
}

// ----------------------------------------------------
// 2. LÓGICA DE CONEXIÓN A LA BASE DE DATOS (¡CORREGIDA!)
// ----------------------------------------------------

// Configuracion para XAMPP y tu DB 'dios1'
define('DB_HOST', 'localhost');
define('DB_USER', 'u584797177_Marco');        // <-- ¡¡CORREGIDO A root!!
define('DB_PASS', 'Grupoexito2025@');       
define('DB_NAME', 'u584797177_dios1');   // <-- ¡¡CORREGIDO A dios1!!

// Crear la conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Si la conexión falla ahora, admin_pedidos.php mostrará el error amigable.
// Pero con esta corrección, debería funcionar, ya que:
// 1. MySQL está corriendo (puerto 3306).
// 2. El usuario 'root' y la contraseña vacía son correctos.
// 3. El nombre de la base de datos 'dios1' es correcto.

// Si llega hasta aquí, el usuario es un administrador y $conn está definida.
?>