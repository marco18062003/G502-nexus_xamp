<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

$id_usuario = $_SESSION['user_id'];
$msg_ok  = '';
$msg_err = '';

// ─── Load user data ───────────────────────────────────────────────────────────
$st = mysqli_prepare($conn, "SELECT * FROM usuarios WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $id_usuario);
mysqli_stmt_execute($st);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if (!$user) {
    header("Location: logout.php");
    exit();
}

// ─── Handle profile update ────────────────────────────────────────────────────
if (isset($_POST['actualizar_perfil'])) {
    $nombre_completo  = trim($_POST['nombre_completo']);
    $telefono         = trim($_POST['telefono']);
    $direccion        = trim($_POST['direccion']);
    $ciudad           = trim($_POST['ciudad']);
    $estado_provincia = trim($_POST['estado_provincia']);

    $st = mysqli_prepare($conn, "UPDATE usuarios SET nombre_completo=?, telefono=?, direccion=?, ciudad=?, estado_provincia=? WHERE id=?");
    mysqli_stmt_bind_param($st, "sssssi", $nombre_completo, $telefono, $direccion, $ciudad, $estado_provincia, $id_usuario);

    if (mysqli_stmt_execute($st)) {
        $msg_ok = '✅ Perfil actualizado correctamente.';
        // Refresh user data
        $st2 = mysqli_prepare($conn, "SELECT * FROM usuarios WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($st2, "i", $id_usuario);
        mysqli_stmt_execute($st2);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st2));
        mysqli_stmt_close($st2);
    } else {
        $msg_err = '❌ Error al actualizar: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($st);
}

// ─── Handle password change ───────────────────────────────────────────────────
if (isset($_POST['cambiar_password'])) {
    $pass_actual = $_POST['password_actual'];
    $pass_nueva  = $_POST['password_nueva'];
    $pass_conf   = $_POST['password_confirmar'];

    if (!password_verify($pass_actual, $user['password'])) {
        $msg_err = '❌ La contraseña actual es incorrecta.';
    } elseif (strlen($pass_nueva) < 6) {
        $msg_err = '❌ La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass_nueva !== $pass_conf) {
        $msg_err = '❌ Las contraseñas nuevas no coinciden.';
    } else {
        $hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
        $st   = mysqli_prepare($conn, "UPDATE usuarios SET password=? WHERE id=?");
        mysqli_stmt_bind_param($st, "si", $hash, $id_usuario);
        if (mysqli_stmt_execute($st)) {
            $msg_ok = '✅ Contraseña cambiada correctamente.';
        } else {
            $msg_err = '❌ Error al cambiar la contraseña.';
        }
        mysqli_stmt_close($st);
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
$initials = strtoupper(substr($user['nombre_completo'] ?? $user['nombre_usuario'], 0, 2));
$rol_labels = [
    'administrador' => ['🛡️ Administrador', '#fee2e2', '#991b1b'],
    'empleado'      => ['💼 Empleado',       '#dcfce7', '#15803d'],
    'usuario'       => ['👤 Cliente',         '#dbeafe', '#1d4ed8'],
];
[$rol_text, $rol_bg, $rol_color] = $rol_labels[$user['rol']] ?? ['👤 Usuario', '#dbeafe', '#1d4ed8'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .panel { max-width: 680px; margin: auto; }

        /* Header card */
        .profile-header {
            background: white; border-radius: 16px; padding: 28px 24px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 20px;
            border-top: 4px solid #2563eb; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .avatar {
            width: 70px; height: 70px; border-radius: 50%;
            background: #dbeafe; color: #1d4ed8;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; font-weight: 700; flex-shrink: 0;
        }
        .profile-header h2 { font-size: 1.2rem; font-weight: 700; color: #0f172a; }
        .profile-header p  { font-size: 0.82rem; color: #64748b; margin-top: 3px; }
        .rol-badge {
            display: inline-block; font-size: 0.75rem; font-weight: 600;
            padding: 4px 12px; border-radius: 20px; margin-top: 6px;
            background: <?php echo $rol_bg; ?>; color: <?php echo $rol_color; ?>;
        }
        .meta-row {
            display: flex; gap: 16px; margin-top: 8px; flex-wrap: wrap;
        }
        .meta-item { font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 4px; }

        /* Cards */
        .card {
            background: white; border-radius: 16px; padding: 24px;
            margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .card h3 {
            font-size: 0.9rem; font-weight: 600; color: #0f172a;
            margin-bottom: 18px; padding-bottom: 10px;
            border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px;
        }

        /* Read-only info rows */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        @media(max-width:500px){ .info-grid { grid-template-columns: 1fr; } }
        .info-item label { font-size: 0.72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 3px; }
        .info-item span  { font-size: 0.9rem; color: #1e293b; font-weight: 500; }
        .info-item.full  { grid-column: 1 / -1; }

        /* Form */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media(max-width:500px){ .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.78rem; color: #64748b; font-weight: 500; }
        .form-group input {
            padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
            font-size: 0.9rem; font-family: inherit; color: #1e293b;
            transition: border-color 0.15s;
        }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }

        /* Buttons */
        .btn-save {
            margin-top: 16px; width: 100%; padding: 12px;
            background: #2563eb; color: white; border: none;
            border-radius: 10px; font-size: 0.9rem; font-weight: 600;
            font-family: inherit; cursor: pointer; transition: background 0.15s;
        }
        .btn-save:hover { background: #1d4ed8; }
        .btn-save.red { background: #dc2626; }
        .btn-save.red:hover { background: #b91c1c; }

        /* Alerts */
        .alert {
            padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
            font-size: 0.85rem; font-weight: 500;
        }
        .alert.ok  { background: #dcfce7; color: #15803d; }
        .alert.err { background: #fee2e2; color: #991b1b; }

        /* Readonly field */
        .readonly-field {
            padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 8px; font-size: 0.9rem; color: #64748b;
        }

        /* Back link */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #64748b; text-decoration: none; font-size: 0.82rem;
            font-weight: 500; margin-bottom: 16px; transition: color 0.15s;
        }
        .back-link:hover { color: #2563eb; }

        /* Stats row */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .stat label { font-size: 0.7rem; color: #94a3b8; display: block; text-transform: uppercase; margin-bottom: 4px; }
        .stat strong { font-size: 1.2rem; font-weight: 700; color: #2563eb; }
    </style>
</head>
<body>
<div class="panel">

    <a href="mi_panel.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al panel
    </a>

    <?php if ($msg_ok): ?>
        <div class="alert ok"><?php echo $msg_ok; ?></div>
    <?php endif; ?>
    <?php if ($msg_err): ?>
        <div class="alert err"><?php echo $msg_err; ?></div>
    <?php endif; ?>

    <!-- Profile header -->
    <div class="profile-header">
        <div class="avatar"><?php echo $initials; ?></div>
        <div>
            <h2><?php echo htmlspecialchars($user['nombre_completo']); ?></h2>
            <p>@<?php echo htmlspecialchars($user['nombre_usuario']); ?></p>
            <span class="rol-badge"><?php echo $rol_text; ?></span>
            <div class="meta-row">
                <span class="meta-item"><i class="fas fa-circle" style="color:#22c55e; font-size:8px;"></i> <?php echo ucfirst($user['estado_cuenta']); ?></span>
                <span class="meta-item"><i class="fas fa-calendar-alt"></i> Desde <?php echo date('M Y', strtotime($user['fecha_registro'])); ?></span>
                <span class="meta-item"><i class="fas fa-id-badge"></i> ID #<?php echo $user['id']; ?></span>
            </div>
        </div>
    </div>

    <!-- Read-only info -->
    <div class="card">
        <h3><i class="fas fa-info-circle"></i> Información de cuenta</h3>
        <div class="info-grid">
            <div class="info-item">
                <label>Usuario</label>
                <span>@<?php echo htmlspecialchars($user['nombre_usuario']); ?></span>
            </div>
            <div class="info-item">
                <label>Email</label>
                <span><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-item">
                <label>Estado</label>
                <span style="color:#16a34a; font-weight:600;">● <?php echo ucfirst($user['estado_cuenta']); ?></span>
            </div>
            <div class="info-item">
                <label>Rol</label>
                <span><?php echo $rol_text; ?></span>
            </div>
            <div class="info-item full">
                <label>Miembro desde</label>
                <span><?php echo date('d \d\e F \d\e Y', strtotime($user['fecha_registro'])); ?></span>
            </div>
        </div>
    </div>

    <!-- Editable profile form -->
    <div class="card">
        <h3><i class="fas fa-user-edit"></i> Editar información personal</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Nombre completo</label>
                    <input type="text" name="nombre_completo"
                           value="<?php echo htmlspecialchars($user['nombre_completo']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono"
                           value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>"
                           placeholder="Ej: 3001234567">
                </div>
                <div class="form-group">
                    <label>Ciudad</label>
                    <input type="text" name="ciudad"
                           value="<?php echo htmlspecialchars($user['ciudad'] ?? ''); ?>"
                           placeholder="Ej: Bogotá">
                </div>
                <div class="form-group full">
                    <label>Dirección</label>
                    <input type="text" name="direccion"
                           value="<?php echo htmlspecialchars($user['direccion'] ?? ''); ?>"
                           placeholder="Ej: Carrera 7 #32-16">
                </div>
                <div class="form-group full">
                    <label>Departamento / Provincia</label>
                    <input type="text" name="estado_provincia"
                           value="<?php echo htmlspecialchars($user['estado_provincia'] ?? ''); ?>"
                           placeholder="Ej: Cundinamarca">
                </div>
                <div class="form-group full">
                    <label>Email (no editable)</label>
                    <div class="readonly-field"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
            </div>
            <button type="submit" name="actualizar_perfil" class="btn-save">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </form>
    </div>

    <!-- Password change -->
    <div class="card">
        <h3><i class="fas fa-lock"></i> Cambiar contraseña</h3>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Contraseña actual</label>
                    <input type="password" name="password_actual" required placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" name="password_nueva" required placeholder="Mín. 6 caracteres">
                </div>
                <div class="form-group">
                    <label>Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmar" required placeholder="Repetir contraseña">
                </div>
            </div>
            <button type="submit" name="cambiar_password" class="btn-save red">
                <i class="fas fa-key"></i> Cambiar contraseña
            </button>
        </form>
    </div>

    <!-- Danger zone — only show to non-admins -->
    <?php if ($user['rol'] !== 'administrador'): ?>
    <div class="card" style="border-left: 3px solid #dc2626;">
        <h3 style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Zona de peligro</h3>
        <p style="font-size:0.85rem; color:#64748b; margin-bottom:14px;">
            Si cierras tu cuenta perderás acceso a tu historial de pedidos y fiados.
            Esta acción no se puede deshacer.
        </p>
        <a href="logout.php"
           style="display:block; text-align:center; padding:11px; background:#fff5f5;
                  color:#dc2626; border:1px solid #fecaca; border-radius:10px;
                  font-weight:600; font-size:0.85rem; text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>
    <?php endif; ?>

</div>
</body>
</html>