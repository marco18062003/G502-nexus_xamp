<?php
session_start();

// --- Protección de la Página ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php'; // Asegúrate de que esta ruta sea correcta

$id_usuario_logueado = $_SESSION['user_id'];
$nombre_usuario_logueado = htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario');

// --- CONSULTA DE DEUDAS DEL USUARIO (Fiao) ---
$total_deuda = 0;
$detalles_deuda = [];

try {
    // 1. Consultamos el total pendiente
    // Usamos $conn porque tu archivo db.php parece usar MySQLi según los chats anteriores
    $sql_deuda = "SELECT SUM(monto_total) as total FROM deudas_g502 WHERE cliente_id = $id_usuario_logueado AND estado = 'Pendiente'";
    $res_deuda = mysqli_query($conn, $sql_deuda);
    $data_deuda = mysqli_fetch_assoc($res_deuda);
    $total_deuda = $data_deuda['total'] ?? 0;

    // 2. Consultamos los detalles de sus deudas
    $sql_detalles = "SELECT * FROM deudas_g502 WHERE cliente_id = $id_usuario_logueado ORDER BY fecha_deuda DESC LIMIT 5";
    $res_detalles = mysqli_query($conn, $sql_detalles);
} catch (Exception $e) {
    error_log("Error al cargar deudas: " . $e->getMessage());
}

$email_usuario = htmlspecialchars($_SESSION['user_email'] ?? 'No disponible');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Reutilizamos tu CSS y añadimos el de deudas */
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; flex-direction: column; }
        .dashboard-container { background-color: #ffffff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); width: 800px; max-width: 95%; box-sizing: border-box; text-align: center; border-top: 6px solid #4a90e2; }
        
        /* Sección de Deuda (NUEVO) */
        .debt-card { background: #fff5f5; border: 2px dashed #fc8181; border-radius: 12px; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .debt-amount { color: #e53e3e; font-size: 2em; font-weight: 800; }
        .debt-title { text-align: left; }
        .debt-title h4 { margin: 0; color: #c53030; }
        
        .profile-details { background-color: #f8fbfd; border: 1px solid #e0e6ed; border-radius: 10px; padding: 25px; margin-bottom: 35px; text-align: left; }
        .profile-details p { margin: 8px 0; display: flex; align-items: center; }
        .profile-details strong { width: 150px; color: #333; }
        
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .btn { padding: 15px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: 0.3s; text-align: center; color: white; }
        .btn-blue { background-color: #4a90e2; }
        .btn-green { background-color: #2ecc71; }
        .logout-link { color: #e74c3c; text-decoration: none; font-weight: 600; margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h2 style="margin-top:0;">Mi Panel Personal</h2>
    <p style="font-size: 1.2em;">¡Hola de nuevo, <strong><?php echo $nombre_usuario_logueado; ?></strong>!</p>

    <div class="debt-card">
        <div class="debt-title">
            <h4><i class="fas fa-wallet"></i> Saldo Pendiente (Fiao)</h4>
            <small>Recuerda pagar a tiempo en la tienda física.</small>
        </div>
        <div class="debt-amount">
            $<?php echo number_format($total_deuda, 0); ?>
        </div>
    </div>

    <div class="profile-details">
        <h5 style="margin-top:0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Información de Perfil</h5>
        <p><strong>👤 ID de Usuario:</strong> <?php echo $id_usuario_logueado; ?></p>
        <p><strong>✉️ Email:</strong> <?php echo $email_usuario; ?></p>
        <p><strong>📅 Estado:</strong> <span class="badge" style="background:#d4edda; color:#155724; padding:2px 8px; border-radius:5px;">Activo</span></p>
    </div>

    <?php if ($total_deuda > 0): ?>
    <div style="text-align: left; margin-bottom: 30px;">
        <h5 class="fw-bold"><i class="fas fa-history"></i> Últimos Fiaos</h5>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
            <tr style="border-bottom: 1px solid #eee; color: #888;">
                <th style="padding: 10px 0;">Fecha</th>
                <th>Detalle</th>
                <th style="text-align: right;">Monto</th>
            </tr>
            <?php while($item = mysqli_fetch_assoc($res_detalles)): ?>
            <tr style="border-bottom: 1px solid #f9f9f9;">
                <td style="padding: 8px 0;"><?php echo date('d/m/y', strtotime($item['fecha_deuda'])); ?></td>
                <td><?php echo htmlspecialchars($item['productos_detalle']); ?></td>
                <td style="text-align: right; font-weight: bold;">$<?php echo number_format($item['monto_total'], 0); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php endif; ?>

    <div class="action-grid">
        <a href="index.php" class="btn btn-blue">🛒 Ir a la Tienda</a>
        <a href="mis_pedidos.php" class="btn btn-blue">📦 Mis Pedidos</a>
        <a href="soporte.php" class="btn btn-green">💬 Soporte</a>
    </div>

    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión Segura</a>
</div>

</body>
</html>