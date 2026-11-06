<?php
// db_pdo.php - Conexión PDO (mismas credenciales que db.php de producción)

$host     = 'localhost';
$user     = 'u584797177_Marco';
$password = 'Grupoexito2025@';   //
$database = 'u584797177_dios1';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Error de conexión PDO: " . $e->getMessage());
    die("Error de conexión a la base de datos.");
}
