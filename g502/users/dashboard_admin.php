<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .panel { max-width: 800px; margin: auto; }
        .header { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px; border-top: 4px solid #dc2626; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700; flex-shrink: 0; }
        .header-info h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
        .header-info p  { font-size: 0.82rem; color: #64748b; }
        .alert-badge { margin-left: auto; background: #fee2e2; color: #dc2626; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
        @media(max-width:600px){ .stats { grid-template-columns: repeat(2,1fr); } }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .stat label { font-size: 0.7rem; color: #64748b; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em; }
        .stat strong { font-size: 1.4rem; font-weight: 700; }
        .stat.green strong { color: #16a34a; }
        .stat.blue strong  { color: #2563eb; }
        .stat.red strong   { color: #dc2626; }
        .stat.amber strong { color: #d97706; }
        .card { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .card h3 { font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 14px; }
        .menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
        @media(min-width:500px){ .menu { grid-template-columns: repeat(3,1fr); } }
        .menu-item { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1e293b; font-size: 0.85rem; font-weight: 500; transition: all 0.15s; }
        .menu-item:hover { background: #f8fafc; transform: translateY(-1px); border-color: #cbd5e1; }
        .menu-item .icon { font-size: 1.2rem; }
        .menu-item .cnt { margin-left: auto; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
        .cnt-red   { background: #fee2e2; color: #dc2626; }
        .cnt-blue  { background: #dbeafe; color: #1d4ed8; }
        .cnt-green { background: #dcfce7; color: #15803d; }
        .logout { display: block; text-align: center; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 12px; border-radius: 10px; border: 1px solid #fecaca; background: #fff5f5; }
    </style>
</head>
<body>
<div class="panel">

    <div class="header">
        <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 2)); ?></div>
        <div class="header-info">
            <h2><?php echo $nombre; ?> — Admin</h2>
            <p>Acceso total · <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        <div class="alert-badge">⚠ <?php echo $baneados; ?> baneados</div>
    </div>

    <div class="stats">
        <div class="stat green">
            <label>Ventas hoy</label>
            <strong>$<?php echo number_format($ventas_hoy/1000, 0); ?>k</strong>
        </div>
        <div class="stat blue">
            <label>Pedidos hoy</label>
            <strong><?php echo $pedidos_hoy; ?></strong>
        </div>
        <div class="stat blue">
            <label>Usuarios</label>
            <strong><?php echo $total_usuarios; ?></strong>
        </div>
        <div class="stat red">
            <label>Baneados</label>
            <strong><?php echo $baneados; ?></strong>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-chart-line"></i> Ventas últimos 6 meses</h3>
        <canvas id="chartVentas" height="100"></canvas>
    </div>

    <div class="menu">
        <a href="index.php" class="menu-item">
            <span class="icon">🛠️</span> Panel g502
        </a>
        <a href="usuarios.php" class="menu-item">
            <span class="icon">👥</span> Usuarios
            <span class="cnt cnt-blue"><?php echo $total_usuarios; ?></span>
        </a>
        <a href="admin_pedidos.php" class="menu-item">
            <span class="icon">📦</span> Pedidos
            <span class="cnt cnt-blue"><?php echo $pedidos_hoy; ?></span>
        </a>
        <a href="../admin/finance.php" class="menu-item">
            <span class="icon">💰</span> Finanzas
        </a>
        <a href="../bread1/index.php" class="menu-item">
            <span class="icon">📸</span> Scanner IA
        </a>
        <a href="../bread1/todo.php" class="menu-item">
            <span class="icon">📋</span> Todos los PLUs
        </a>
        <a href="usuarios_baneados.php" class="menu-item">
            <span class="icon">🚫</span> Baneados
            <span class="cnt cnt-red"><?php echo $baneados; ?></span>
        </a>
        <a href="admin_pedidos.php" class="menu-item">
            <span class="icon">🗺️</span> Mapa pedidos
        </a>
        <a href="configuracion.php" class="menu-item">
            <span class="icon">⚙️</span> Configuración
        </a>
    </div>

    <a href="logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión segura
    </a>

</div>

<script>
new Chart(document.getElementById('chartVentas').getContext('2d'), {
    type: 'line',
    data: {
        labels: ['Ene','Feb','Mar','Abr','May','Jun'],
        datasets: [{
            label: 'Ventas',
            data: [320000, 480000, 410000, 620000, 550000, <?php echo $ventas_hoy ?: 840000; ?>],
            borderColor: '#dc2626',
            backgroundColor: 'rgba(220,38,38,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#dc2626',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: v => '$' + (v/1000) + 'k' }, grid: { color: '#f8fafc' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
</body>
</html>