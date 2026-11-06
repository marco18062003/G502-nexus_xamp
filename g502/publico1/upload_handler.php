<?php
// Usamos la misma ruta y variable que en tu hire2.php
include '../config/db.php'; 
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Capturar datos y limpiar para seguridad (Usando $conn como en tu db.php)
    $nombre    = mysqli_real_escape_string($conn, $_POST['name']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $propuesta = mysqli_real_escape_string($conn, $_POST['deal']);
    $vacante   = mysqli_real_escape_string($conn, $_POST['applied_position']);

    // 2. Lógica de subida de archivos
    // Tu ruta es ../uploads/postulaciones/
    $target_dir = "../uploads/postulaciones/";
    
    // Si la carpeta no existe, intentamos crearla
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = time() . "_" . basename($_FILES["cv_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Formatos permitidos
    $allowed_types = array("pdf", "doc", "docx", "jpg", "png");

    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($_FILES["cv_file"]["tmp_name"], $target_file)) {
            
            // 3. Insertar en la base de datos
            // Guardamos la ruta del archivo para poder descargarla después
            $sql = "INSERT INTO postulaciones (nombre, email, propuesta, vacante, cv_path) 
                    VALUES ('$nombre', '$email', '$propuesta', '$vacante', '$target_file')";

            if (mysqli_query($conn, $sql)) {
                echo "<script>
                        alert('Postulación enviada con éxito al proyecto g502.');
                        window.location.href='hire2.php';
                      </script>";
            } else {
                echo "Error en la base de datos: " . mysqli_error($conn);
            }

        } else {
            echo "Error: No se pudo mover el archivo a la carpeta destino. Verifica permisos en ../uploads/postulaciones/";
        }
    } else {
        echo "Error: Solo se permiten archivos PDF, DOC, JPG o PNG.";
    }
}
?>