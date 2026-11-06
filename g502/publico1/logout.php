<?php
// logout.php - Maneja el cierre de sesión del usuario

session_start(); // Inicia la sesión (necesario para acceder a $_SESSION)

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión, también es necesario borrar la cookie de sesión.
// Nota: Esto destruirá la sesión, y no solo los datos de sesión.
// Esto es útil si quieres que el usuario tenga que iniciar sesión desde cero la próxima vez.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir al usuario a la página de inicio de sesión o a la página principal
header("Location: login.php?logout=success"); // Puedes redirigir a login.php o index.php
exit(); // Es crucial usar exit() después de un header() para asegurar que la redirección se ejecute inmediatamente.
?>