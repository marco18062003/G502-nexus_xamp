<?php
// db.php - Archivo de conexión a la base de datos

$host = 'localhost'; // Normalmente 'localhost' si usas XAMPP/WAMP/MAMP
$user = 'u584797177_Marco';      // Tu usuario de MySQL (comúnmente 'root' para entornos de desarrollo)
$password = 'Grupoexito2025@';      // Tu contraseña de MySQL (comúnmente vacío '' para 'root' en XAMPP/WAMP/MAMP)
$database = 'u584797177_dios1'; // ¡Tu base de datos se llama dios1!

// Establecer la conexión
$conn = mysqli_connect($host, $user, $password, $database);

// Verificar si la conexión fue exitosa
if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Opcional pero recomendado: Establecer el juego de caracteres a UTF-8 para evitar problemas con tildes y ñ
mysqli_set_charset($conn, "utf8");

// Puedes añadir una línea de depuración temporal para confirmar la conexión
// echo "Conexión a la base de datos exitosa.<br>";

// *** NO HAY ETIQUETA DE CIERRE ?>