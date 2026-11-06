<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

$id_usuario = $_SESSION['user_id'];
$nombre     = htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario');
$email      = htmlspecialchars($_SESSION['user_email'] ?? '');
$rol        = $_SESSION['rol'] ?? 'usuario';

// ─── Fiao ─────────────────────────────────────────────────────────────────────
$total_deuda  = 0;
$res_detalles = null;

$st = mysqli_prepare($conn, "SELECT SUM(monto_total) as total FROM deudas_g502 WHERE cliente_id = ? AND estado = 'Pendiente'");
mysqli_stmt_bind_param($st, "i", $id_usuario);
mysqli_stmt_execute($st);
$total_deuda = mysqli_fetch_assoc(mysqli_stmt_get_result($st))['total'] ?? 0;
mysqli_stmt_close($st);

$st2 = mysqli_prepare($conn, "SELECT * FROM deudas_g502 WHERE cliente_id = ? ORDER BY fecha_deuda DESC LIMIT 5");
mysqli_stmt_bind_param($st2, "i", $id_usuario);
mysqli_stmt_execute($st2);
$res_detalles = mysqli_stmt_get_result($st2);

// ─── Pedidos ──────────────────────────────────────────────────────────────────
$total_pedidos = 0;
$st3 = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM pedidos WHERE id_cliente = ?");
if ($st3) {
    mysqli_stmt_bind_param($st3, "i", $id_usuario);
    mysqli_stmt_execute($st3);
    $total_pedidos = mysqli_fetch_assoc(mysqli_stmt_get_result($st3))['total'] ?? 0;
    mysqli_stmt_close($st3);
}

// ─── Total gastado ────────────────────────────────────────────────────────────
$total_gastado = 0;
$st4 = mysqli_prepare($conn, "SELECT SUM(total_final) as total FROM pedidos WHERE id_cliente = ? AND estado_pedido = 'entregado'");
if ($st4) {
    mysqli_stmt_bind_param($st4, "i", $id_usuario);
    mysqli_stmt_execute($st4);
    $total_gastado = mysqli_fetch_assoc(mysqli_stmt_get_result($st4))['total'] ?? 0;
    mysqli_stmt_close($st4);
}

// ─── Admin extras ─────────────────────────────────────────────────────────────
$total_usuarios = 0;
$pedidos_hoy    = 0;
$ventas_hoy     = 0;
$baneados       = 0;

if ($rol === 'administrador') {
    $total_usuarios = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM usuarios"))['t'] ?? 0;
    $pedidos_hoy    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()"))['t'] ?? 0;
    $ventas_hoy     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_final) as t FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado_pedido = 'entregado'"))['t'] ?? 0;
    $baneados       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM usuarios_baneados_pan"))['t'] ?? 0;
}

// ─── Route ────────────────────────────────────────────────────────────────────
switch ($rol) {
    case 'administrador': include 'dashboard_admin.php';    break;
    case 'empleado':      include 'dashboard_empleado.php'; break;
    default:              include 'dashboard_usuario.php';  break;
}
?>