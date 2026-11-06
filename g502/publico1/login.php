<?php
// publico1/login.php
session_start(); 

// Si el usuario ya está logueado, redirigir a la página correspondiente
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'administrador') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../users/mi_panel.php"); // Página principal del usuario normal
    }
    exit(); 
}

// Incluir el archivo de conexión a la base de datos
require_once '../config/db.php';

$mensaje = ""; 

if (isset($_GET['registro']) && $_GET['registro'] == 'exito') {
    $mensaje = "<span style='color: green; font-weight: bold;'>¡Registro exitoso! Por favor, inicia sesión.</span>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre_usuario_o_email = htmlspecialchars(stripslashes(trim($_POST['nombre_usuario_o_email'])));
    $password_ingresada = $_POST['password'];

    if (empty($nombre_usuario_o_email) || empty($password_ingresada)) {
        $mensaje = "Por favor, ingresa tu nombre de usuario/correo y contraseña.";
    } else {
        // Seleccionamos 'rol' para la redirección condicional
        $stmt = $conn->prepare("SELECT id, nombre_usuario, password, rol FROM usuarios WHERE nombre_usuario = ? OR email = ? LIMIT 1");

        if ($stmt === false) {
            $mensaje = "Error interno al preparar la consulta: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $nombre_usuario_o_email, $nombre_usuario_o_email);
            $stmt->execute();
            $stmt->store_result(); 

            if ($stmt->num_rows == 1) {
                // Vinculamos todas las variables, incluyendo $rol_db
                $stmt->bind_result($id_usuario, $nombre_usuario_db, $hashed_password_db, $rol_db);
                $stmt->fetch(); 

                if (password_verify($password_ingresada, $hashed_password_db)) {
                    
                    session_regenerate_id(true); 
                    $_SESSION['user_id'] = $id_usuario; 
                    $_SESSION['nombre_usuario'] = $nombre_usuario_db;
                    $_SESSION['user_rol'] = $rol_db; 

                    // Redirección condicional
                    if ($rol_db === 'administrador') {
                        header("Location: ../admin/index.php"); 
                    } else {
                        header("Location: ../users/mi_panel.php"); 
                    }
                    exit();
                } else {
                    $mensaje = "Usuario o contraseña incorrectos.";
                }
            } else {
                $mensaje = "Usuario o contraseña incorrectos.";
            }

            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Privado | g502 - Don Jorgito</title>
    <link href="https://fonts.googleapis.com/css2?family=Bormioli:wght@700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d4af37;
            --amber: #ff9f1c;
            --wine: #800020;
            --dark-bg: #0a0a0a;
            --card-bg: rgba(26, 26, 26, 0.95);
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body { 
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            /* Gradiente que simula iluminación de barra de bar */
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 159, 28, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(128, 0, 32, 0.05) 0%, transparent 50%);
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            color: #fff;
            overflow-x: hidden;
        }

        /* Animación de entrada */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card { 
            background: var(--card-bg); 
            padding: 3rem 2rem; 
            border-radius: 4px;
            width: 90%;
            max-width: 420px; 
            text-align: center; 
            border: 1px solid #333;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px); /* Efecto cristal */
            animation: fadeInUp 0.8s ease-out;
            position: relative;
        }

        /* Decoración de esquina industrial */
        .login-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 40px; height: 40px;
            border-top: 2px solid var(--amber);
            border-left: 2px solid var(--amber);
        }

        h1 { 
            font-family: 'Bormioli', serif; 
            color: var(--amber); 
            font-size: 2.5rem;
            letter-spacing: 4px;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 2.5rem;
        }

        .form-group { 
            margin-bottom: 1.5rem; 
            text-align: left; 
            opacity: 0; 
            animation: fadeInUp 0.5s ease-out forwards;
        }
        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.5s; }

        label { 
            display: block; 
            margin-bottom: 0.6rem; 
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gold);
            letter-spacing: 1px;
        }

        input { 
            width: 100%; 
            padding: 1rem; 
            background: #111;
            border: 1px solid #333; 
            border-radius: 0; 
            font-size: 1rem; 
            color: #fff;
            transition: var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--amber);
            box-shadow: 0 0 15px rgba(255, 159, 28, 0.1);
            background: #161616;
        }

        .btn-login { 
            width: 100%; 
            padding: 1.2rem; 
            background: transparent; 
            color: var(--amber); 
            border: 1px solid var(--amber); 
            font-size: 1rem; 
            font-weight: 700; 
            text-transform: uppercase;
            cursor: pointer; 
            transition: var(--transition);
            margin-top: 1rem;
            letter-spacing: 2px;
        }

        .btn-login:hover { 
            background: var(--amber); 
            color: #000;
            box-shadow: 0 0 20px rgba(255, 159, 28, 0.4);
            transform: translateY(-2px);
        }

        .btn-login:active { transform: translateY(0); }

        /* Mensajes mejorados */
        .msg {
            padding: 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            border-radius: 2px;
            animation: fadeInUp 0.4s ease;
        }
        .msg-err { background: rgba(128, 0, 32, 0.2); color: #ff6b6b; border: 1px solid var(--wine); }
        .msg-ok { background: rgba(40, 167, 69, 0.1); color: #73f08b; border: 1px solid #28a745; }

        .footer-links { 
            margin-top: 2rem; 
            border-top: 1px solid #222;
            padding-top: 1.5rem;
        }

        .footer-links a { 
            color: #555; 
            text-decoration: none; 
            font-size: 0.8rem;
            transition: color 0.3s;
        }

        .footer-links a:hover { color: var(--amber); }

        /* Responsive Adjustments */
        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.5rem; }
            h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h1>g502</h1>
        <p class="brand-subtitle">Don Jorgito | Bar & Bodega</p>
        
        <?php if (!empty($mensaje)): ?>
            <div class="msg <?php echo (strpos($mensaje, 'exitoso') !== false) ? 'msg-ok' : 'msg-err'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" autocomplete="off">
            <div class="form-group">
                <label>IDENTIFICACIÓN DE SOCIO</label>
                <input type="text" name="nombre_usuario_o_email" required placeholder="Usuario o Email">
            </div>
            
            <div class="form-group">
                <label>LLAVE MAESTRA</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">Abrir Bodega</button>
        </form>
        
        <div class="footer-links">
            <a href="registro.php">¿Aún no eres miembro del club? Regístrate</a>
        </div>
    </div>

</body>
</html>