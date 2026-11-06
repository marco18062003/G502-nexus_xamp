<!--

/**

 * Clase/Archivo: RegistroUsuarioController.php

 *

 * Propósito: Gestiona el registro de nuevos usuarios en la base de datos dios1.

 *

 * Descripción:

 * Este script PHP maneja el registro de nuevos usuarios, recopilando los datos del formulario,

 * sanitizándolos, y almacenando de manera segura la contraseña con un hash en la base de datos.

 *

 * Funcionalidades Clave:

 * 1. **Conexión a DB:** Establece una conexión a la base de datos dios1 utilizando mysqli.

 * 2. **Procesamiento POST:** Recoge y sanitiza los datos del formulario (nombre, email y teléfono)

 *    usando htmlspecialchars y trim.

 * 3. **Paso Crítico de Seguridad:** La contraseña proporcionada se hashea inmediatamente

 *    utilizando password_hash() con el algoritmo PASSWORD_DEFAULT.

 * 4. **Inserción Segura:** Utiliza una sentencia preparada ($stmt->prepare()) para insertar

 *    de forma segura los datos (nombre, email, teléfono y contraseña hasheada) en la tabla contactos.

 * 5. **Feedback:** Muestra un mensaje de éxito o error al usuario, y cierra la conexión a la base de datos

 *    al finalizar el proceso.

 *

 * Uso:

 * Se invoca cuando un usuario desea registrarse en la aplicación para crear una cuenta,

 * almacenando sus datos de forma segura en la base de datos.

 */

-->
<?php
// --- CONFIGURACIÓN DE LA BASE DE DATOS ---
$host = 'localhost';
$usuario_db = 'root';
$password_db = '';
$nombre_db = 'dios1'; // Asegúrate de que este es el nombre correcto de tu DB

// Crear una nueva conexión a la base de datos
$mysqli = new mysqli($host, $usuario_db, $password_db, $nombre_db);

// Verificar si la conexión falló
if ($mysqli->connect_errno) {
    die("<div class='mensaje error'>Error de conexión a la base de datos: " . $mysqli->connect_error . "</div>");
}

$mysqli->set_charset("utf8mb4");

// --- LÓGICA PARA PROCESAR EL FORMULARIO ---
$mensaje_usuario = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recoger y "sanitizar" los datos enviados desde el formulario
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $password_plana = $_POST['password']; // Capturamos la contraseña tal cual se envió

    // 2. HASHEAR LA CONTRASEÑA (¡Paso de seguridad CRÍTICO!)
    // password_hash() crea un hash seguro de la contraseña.
    // PASSWORD_DEFAULT utiliza el algoritmo de hashing más fuerte disponible y lo gestiona por ti.
    $password_hasheada = password_hash($password_plana, PASSWORD_DEFAULT);

    // 3. Preparar la consulta SQL para insertar los datos
    // Asegúrate de que las columnas coincidan con las de tu tabla en phpMyAdmin
    // Hemos añadido 'password' a la lista de columnas y a los valores
    $sql = "INSERT INTO contactos (nombre, email, telefono, password) VALUES (?, ?, ?, ?)";

    // 4. Crear una sentencia preparada
    if ($stmt = $mysqli->prepare($sql)) {
        // 5. Vincular los parámetros
        // "ssss" porque ahora son 4 strings (nombre, email, telefono, y la CONTRASEÑA HASHEADA)
        $stmt->bind_param("ssss", $nombre, $email, $telefono, $password_hasheada);

        // 6. Ejecutar la consulta preparada
        if ($stmt->execute()) {
            $mensaje_usuario = "<div class='mensaje exito'>¡Registro completado con éxito!</div>";
        } else {
            $mensaje_usuario = "<div class='mensaje error'>Error al guardar los datos: " . $stmt->error . "</div>";
        }

        // 7. Cerrar la sentencia preparada
        $stmt->close();
    } else {
        $mensaje_usuario = "<div class='mensaje error'>Error al preparar la consulta: " . $mysqli->error . "</div>";
    }
}

// --- Cerrar la conexión a la base de datos ---
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Registro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f8f8f8; color: #333; }
        form { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 450px; margin: 30px auto; }
        h2 { text-align: center; color: #0056b3; margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
        /* input[type="password"] para el campo de contraseña */
        input[type="text"], input[type="email"], input[type="password"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #28a745; /* Color verde para "Registrar" */
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .mensaje {
            margin: 20px auto;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            max-width: 450px;
        }
        .exito { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    <style>
        /* This is where your CSS goes */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: blue; /* Set the background color here */
            color: #333;
        }
        /* ... (rest of your existing styles) ... */
    </style>
</head>
<body background="color:blue">
    <?php echo $mensaje_usuario; ?>

    <form action="" method="POST">
        <h2>Registro de Usuario</h2>
        
        <div>
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>
        
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div>
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono">
        </div>

        <div>
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            </div>
        
        <input type="submit" value="Registrar Usuario">
    </form>
</body>
</html>