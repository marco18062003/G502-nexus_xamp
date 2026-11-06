<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

$id_usuario = $_SESSION['user_id'];
$nombre     = htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario');

// 1. Calcular el Saldo Pendiente Total (Monto Total - Monto Pagado) de deudas no finalizadas
// Asumiendo que las deudas vigentes tienen estado 'Pendiente' o similar. 
// Esta consulta suma la diferencia de lo que falta por pagar de las deudas activas.
$st_saldo = mysqli_prepare($conn, "
    SELECT SUM(monto_total - monto_pagado) as saldo_pendiente 
    FROM deudas_g502 
    WHERE cliente_id = ? AND estado != 'Pagado'
");
mysqli_stmt_bind_param($st_saldo, "i", $id_usuario);
mysqli_stmt_execute($st_saldo);
$res_saldo = mysqli_stmt_get_result($st_saldo);
$fila_saldo = mysqli_fetch_assoc($res_saldo);
$saldo_pendiente = $fila_saldo['saldo_pendiente'] ?? 0;
mysqli_stmt_close($st_saldo);

// 2. Obtener todo el historial de fiaos/deudas del cliente
$st_deudas = mysqli_prepare($conn, "
    SELECT id, productos_detalle, monto_total, monto_pagado, estado, fecha_deuda
    FROM deudas_g502
    WHERE cliente_id = ?
    ORDER BY fecha_deuda DESC
");
mysqli_stmt_bind_param($st_deudas, "i", $id_usuario);
mysqli_stmt_execute($st_deudas);
$res_deudas = mysqli_stmt_get_result($st_deudas);
$all_deudas = mysqli_fetch_all($res_deudas, MYSQLI_ASSOC);
mysqli_stmt_close($st_deudas);

$total_creditos = count($all_deudas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Fiao - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .panel { max-width: 700px; margin: auto; }

        /* Back link */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #64748b; text-decoration: none; font-size: 0.82rem;
            font-weight: 500; margin-bottom: 16px; transition: color 0.15s;
        }
        .back-link:hover { color: #2563eb; }

        /* Card Saldo Destacado */
        .debt-banner {
            background: white; border-radius: 16px; padding: 24px;
            margin-bottom: 16px; text-align: center;
            border-top: 4px solid #ea580c; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .debt-banner label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .debt-banner h1 { font-size: 2.2rem; font-weight: 700; color: #1e293b; margin: 6px 0; }
        .debt-banner p { font-size: 0.8rem; color: #64748b; background: #fff7ed; color: #c2410c; display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: 500; }
        .debt-banner p.no-debt { background: #f0fdf4; color: #15803d; }

        /* Stats rápidas */
        .stats-mini { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); text-align: center;}
        .stat label { font-size: 0.7rem; color: #94a3b8; display: block; text-transform: uppercase; margin-bottom: 4px; }
        .stat strong { font-size: 1.1rem; font-weight: 700; color: #334155; }

        /* Listado de deudas */
        .section-title { font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin: 20px 0 10px; }
        .debt-card {
            background: white; border-radius: 16px; padding: 16px 20px; margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center;
        }
        .debt-details h3 { font-size: 0.92rem; font-weight: 600; color: #1e293b; }
        .debt-details .date { font-size: 0.75rem; color: #94a3b8; margin-bottom: 4px; }
        .debt-details .breakdown { font-size: 0.75rem; color: #64748b; margin-top: 2px; }

        /* Badges de Estado */
        .status {
            font-size: 0.7rem; font-weight: 700; padding: 4px 10px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em; display: inline-block; margin-top: 4px;
        }
        .status-pagado { background: #dcfce7; color: #15803d; }
        .status-pendiente { background: #fee2e2; color: #991b1b; }
        .status-parcial { background: #fef3c7; color: #92400e; }

        /* Valores numéricos de la derecha */
        .debt-amounts { text-align: right; }
        .debt-amounts .total-amount { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .debt-amounts .paid-amount { font-size: 0.72rem; color: #16a34a; font-weight: 500; }

        /* Empty State */
        .empty {
            background: white; border-radius: 16px; padding: 50px 24px;
            text-align: center; color: #94a3b8; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .empty i { font-size: 3rem; margin-bottom: 12px; display: block; color: #cbd5e1; }
    </style>
</head>
<body>
<div class="panel">

    <a href="mi_panel.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al panel
    </a>

    <div class="debt-banner">
        <label>Saldo Pendiente Total (Mi Fiao)</label>
        <h1>$<?php echo number_format($saldo_pendiente, 0, ',', '.'); ?></h1>
        <?php if ($saldo_pendiente > 0): ?>
            <p><i class="fas fa-store"></i> Recuerda pagar en la tienda física.</p>
        <?php else: ?>
            <p class="no-debt"><i class="fas fa-check-circle"></i> ¡Estás al día! No tienes saldos pendientes.</p>
        <?php endif; ?>
    </div>

    <div class="stats-mini">
        <div class="stat">
            <label>Créditos Solicitados</label>
            <strong><?php echo $total_creditos; ?></strong>
        </div>
        <div class="stat">
            <label>Estado de Cuenta</label>
            <strong style="color: <?php echo ($saldo_pendiente > 0) ? '#ea580c' : '#16a34a'; ?>">
                <?php echo ($saldo_pendiente > 0) ? 'Con Deuda' : 'Al Día'; ?>
            </strong>
        </div>
    </div>

    <div class="section-title">Historial de Fiaos</div>
    
    <div class="debt-list">
        <?php if (empty($all_deudas)): ?>
            <div class="empty">
                <i class="fas fa-receipt"></i>
                <p>No tienes ningún registro de fiaos en tu cuenta.</p>
            </div>
        <?php else: ?>
            <?php foreach ($all_deudas as $d): 
                $fecha = date('d/m/Y', strtotime($d['fecha_deuda']));
                $monto_tot = $d['monto_total'];
                $monto_pag = $d['monto_paid'] ?? $d['monto_pagado'];
                
                // Determinar la clase del estado de forma dinámica
                $estado_actual = strtolower($d['estado']);
                $clase_estado = 'pendiente';
                if ($estado_actual === 'pagado') {
                    $clase_estado = 'pagado';
                } elseif ($monto_pag > 0 && $monto_pag < $monto_tot) {
                    $clase_estado = 'parcial';
                    $d['estado'] = 'Pago Parcial';
                }
            ?>
                <div class="debt-card">
                    <div class="debt-details">
                        <div class="date"><?php echo $fecha; ?></div>
                        <h3><?php echo htmlspecialchars($d['productos_detalle']); ?></h3>
                        
                        <span class="status status-<?php echo $clase_estado; ?>">
                            <?php echo htmlspecialchars($d['estado']); ?>
                        </span>
                    </div>
                    
                    <div class="debt-amounts">
                        <div class="total-amount">$<?php echo number_format($monto_tot, 0, ',', '.'); ?></div>
                        <?php if ($monto_pag > 0): ?>
                            <div class="paid-amount">Abonado: $<?php echo number_format($monto_pag, 0, ',', '.'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>