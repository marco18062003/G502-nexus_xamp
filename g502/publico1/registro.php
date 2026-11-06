<?php
// registro.php
// Inicia la sesión. Es crucial para usar variables de sesión, aunque no se usen directamente aquí,
// es buena práctica tenerla para futuras funcionalidades (ej. mensajes flash).
session_start();

// Incluir el archivo de conexión a la base de datos
require_once '../config/db.php';

$mensaje = ""; // Variable para almacenar mensajes de éxito o error para el usuario

// Verifica si la solicitud es de tipo POST (es decir, el formulario ha sido enviado)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener y limpiar los datos del formulario.
    // htmlspecialchars() convierte caracteres especiales en entidades HTML para prevenir ataques XSS.
    $nombre_usuario = htmlspecialchars(trim($_POST['nombre_usuario']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password']; // La contraseña no se limpia con htmlspecialchars antes de hashear
    $confirm_password = $_POST['confirm_password'];

    // --- Validaciones del lado del servidor (¡MUY IMPORTANTES!) ---
    if (empty($nombre_usuario) || empty($email) || empty($password) || empty($confirm_password)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Valida que el email tenga un formato correcto
        $mensaje = "El formato del correo electrónico es inválido.";
    } elseif ($password !== $confirm_password) {
        // Verifica que ambas contraseñas coincidan
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) { // Recomiendo mínimo 8 caracteres para contraseñas
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        // --- Procesamiento y Seguridad de la Contraseña ---
        // Genera un hash seguro de la contraseña. password_hash() usa un algoritmo fuerte (Argon2id o bcrypt por defecto)
        // y genera un 'salt' aleatorio por cada hash, lo que la hace muy segura.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // --- Inserción en la Base de Datos (con Sentencias Preparadas) ---
        // Prepara la consulta SQL para insertar un nuevo usuario.
        // Los signos de interrogación (?) son placeholders para los valores.
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, password) VALUES (?, ?, ?)");

        // Verifica si la preparación de la consulta falló
        if ($stmt === false) {
            $mensaje = "Error al preparar la consulta: " . $conn->error;
        } else {
            // Vincula los parámetros a la consulta preparada.
            // "sss" indica que los tres parámetros son de tipo string (s).
            $stmt->bind_param("sss", $nombre_usuario, $email, $hashed_password);

            // Ejecuta la consulta
            if ($stmt->execute()) {
                $mensaje = "<span class='success-message'>¡Registro exitoso! Ahora puedes iniciar sesión.</span>";
                // Redirigir al usuario a la página de login después de un registro exitoso
                // Esto es una buena práctica para evitar re-envíos del formulario.
                header("Location: login.php?registro=exito");
                exit(); // Es crucial llamar a exit() después de un header() para asegurar la redirección.
            } else {
                // Manejo de errores en la ejecución de la consulta.
                // El error 1062 es para entradas duplicadas (UNIQUE key violation),
                // lo que significa que el nombre de usuario o el email ya existen.
                if ($conn->errno == 1062) {
                    $mensaje = "El nombre de usuario o correo electrónico ya están registrados.";
                } else {
                    $mensaje = "Error al registrar: " . $stmt->error;
                }
            }
            // Cierra la sentencia preparada
            $stmt->close();
        }
    }
}
// Cierra la conexión a la base de datos al final del script
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Tu Tienda Virtual</title>
    <style>
        /* Estilos CSS básicos para que el formulario se vea decente */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 90%;
            text-align: center;
        }
        h2 {
            color: #343a40;
            margin-bottom: 25px;
            font-size: 1.8em;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: calc(100% - 22px); /* Ancho total menos padding y borde */
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box; /* Incluye padding y borde en el ancho */
            font-size: 1em;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .message {
            color: #dc3545; /* Rojo para errores */
            margin-top: 15px;
            font-size: 0.95em;
            font-weight: bold;
        }
        .success-message {
            color: #28a745; /* Verde para éxito */
        }
        .link {
            display: block;
            margin-top: 25px;
            color: #007bff;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.3s ease;
        }
        .link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Crear Cuenta</h2>
        <?php if (!empty($mensaje)): ?>
            <p class="message">
                <?php echo $mensaje; ?>
            </p>
        <?php endif; ?>
        <form action="registro.php" method="post">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" required
                       value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
               <button type="submit">Registrarse</button>
        </form>
        <a href="login.php" class="link">¿Ya tienes una cuenta? Inicia sesión aquí.</a>
    </div>
</body>
</html>