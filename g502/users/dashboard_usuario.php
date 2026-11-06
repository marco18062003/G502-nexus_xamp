<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }

        .panel { max-width: 700px; margin: auto; }

        /* Header */
        .header { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px; border-top: 4px solid #2563eb; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700; flex-shrink: 0; }
        .header-info h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
        .header-info p  { font-size: 0.82rem; color: #64748b; margin-top: 2px; }
        .points-badge { margin-left: auto; text-align: right; }
        .points-badge span { display: block; font-size: 0.72rem; color: #64748b; }
        .points-badge strong { font-size: 1.4rem; color: #2563eb; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .stat label { font-size: 0.72rem; color: #64748b; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat strong { font-size: 1.4rem; font-weight: 700; }
        .stat.blue strong  { color: #2563eb; }
        .stat.red strong   { color: #dc2626; }
        .stat.green strong { color: #16a34a; }

        /* Card */
        .card { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .card h3 { font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }

        /* Fiao alert */
        .fiao-alert { background: #fff5f5; border: 1.5px dashed #fca5a5; border-radius: 12px; padding: 16px 20px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .fiao-alert h4 { font-size: 0.9rem; color: #b91c1c; margin-bottom: 3px; }
        .fiao-alert small { font-size: 0.75rem; color: #ef4444; }
        .fiao-amount { font-size: 1.8rem; font-weight: 800; color: #dc2626; }

        /* Fiao table */
        .fiao-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
        .fiao-table th { padding: 8px 0; color: #94a3b8; font-weight: 500; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .fiao-table td { padding: 8px 0; border-bottom: 1px solid #f8fafc; color: #334155; }
        .fiao-table td:last-child { text-align: right; font-weight: 600; color: #dc2626; }

        /* Menu grid */
        .menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
        .menu-item { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1e293b; font-size: 0.85rem; font-weight: 500; transition: all 0.15s; }
        .menu-item:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-1px); }
        .menu-item .icon { font-size: 1.2rem; }
        .menu-item .badge { margin-left: auto; background: #dbeafe; color: #1d4ed8; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
        .menu-item .badge.red { background: #fee2e2; color: #dc2626; }
        .menu-item .badge.amber { background: #fef3c7; color: #92400e; }

        /* Logout */
        .logout { display: block; text-align: center; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 12px; border-radius: 10px; border: 1px solid #fecaca; background: #fff5f5; transition: 0.15s; }
        .logout:hover { background: #fee2e2; }
    </style>
</head>
<body>
<div class="panel">

    <!-- Header -->
    <div class="header">
        <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 2)); ?></div>
        <div class="header-info">
            <h2>Hola, <?php echo $nombre; ?></h2>
            <p>Cliente activo · <?php echo $email; ?></p>
        </div>
        <div class="points-badge">
            <span>Puntos</span>
            <strong>1,240</strong>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat blue">
            <label>Pedidos</label>
            <strong><?php echo $total_pedidos; ?></strong>
        </div>
        <div class="stat red">
            <label>Fiao</label>
            <strong>$<?php echo number_format($total_deuda/1000, 0); ?>k</strong>
        </div>
        <div class="stat green">
            <label>Gastado</label>
            <strong>$<?php echo number_format($total_gastado/1000, 0); ?>k</strong>
        </div>
    </div>

    <!-- Fiao alert -->
    <?php if ($total_deuda > 0): ?>
    <div class="fiao-alert">
        <div>
            <h4><i class="fas fa-wallet"></i> Saldo Pendiente (Fiao)</h4>
            <small>Recuerda pagar en la tienda física.</small>
        </div>
        <div class="fiao-amount">$<?php echo number_format($total_deuda, 0); ?></div>
    </div>
    <?php endif; ?>

    <!-- Chart -->
    <div class="card">
        <h3><i class="fas fa-chart-bar"></i> Compras por mes</h3>
        <canvas id="chartCompras" height="120"></canvas>
    </div>

    <!-- Fiao detail -->
    <?php if ($total_deuda > 0): ?>
    <div class="card">
        <h3><i class="fas fa-history"></i> Últimos fiaos</h3>
        <table class="fiao-table">
            <tr><th>Fecha</th><th>Detalle</th><th>Monto</th></tr>
            <?php while($item = mysqli_fetch_assoc($res_detalles)): ?>
            <tr>
                <td><?php echo date('d/m/y', strtotime($item['fecha_deuda'])); ?></td>
                <td><?php echo htmlspecialchars($item['productos_detalle']); ?></td>
                <td>$<?php echo number_format($item['monto_total'], 0); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Menu -->
    <div class="menu">
        <a href="../publico1/index.php" class="menu-item">
            <span class="icon">🛒</span> Ir a la tienda
        </a>
        <a href="mis_pedidos.php" class="menu-item">
            <span class="icon">📦</span> Mis pedidos
            <?php if($total_pedidos > 0): ?>
            <span class="badge"><?php echo $total_pedidos; ?></span>
            <?php endif; ?>
        </a>
        <a href="../users/mi_fiado.php" class="menu-item">
            <span class="icon">💳</span> Mi fiao
            <?php if($total_deuda > 0): ?>
            <span class="badge red">!</span>
            <?php endif; ?>
        </a>
        <a href="../publico1/favoritos.php" class="menu-item">
            <span class="icon">❤️</span> Favoritos
        </a>
        <a href="mis_puntos.php" class="menu-item">
            <span class="icon">⭐</span> Mis puntos
            <span class="badge amber">Nuevo</span>
        </a>
        <a href="soporte.php" class="menu-item">
            <span class="icon">💬</span> Soporte
        </a>
        <a href="mi_perfil.php" class="menu-item">
            <span class="icon">👤</span> Mi perfil
        </a>
    </div>

    <a href="../publico1/logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión segura
    </a>

</div>

<script>
const ctx = document.getElementById('chartCompras').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Ene','Feb','Mar','Abr','May','Jun'],
        datasets: [{
            label: 'Compras $',
            data: [45000, 82000, 63000, 120000, 95000, <?php echo $total_gastado; ?>],
            backgroundColor: '#bfdbfe',
            borderColor: '#2563eb',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: v => '$' + (v/1000) + 'k' }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>