<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .panel { max-width: 700px; margin: auto; }
        .header { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; display: flex; align-items: center; gap: 16px; border-top: 4px solid #16a34a; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .avatar { width: 52px; height: 52px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 700; flex-shrink: 0; }
        .header-info h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
        .header-info p  { font-size: 0.82rem; color: #64748b; }
        .online-badge { margin-left: auto; background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .stat label { font-size: 0.72rem; color: #64748b; display: block; margin-bottom: 4px; text-transform: uppercase; }
        .stat strong { font-size: 1.4rem; font-weight: 700; }
        .stat.blue strong { color: #2563eb; }
        .stat.green strong { color: #16a34a; }
        .stat.red strong { color: #dc2626; }
        .card { background: white; border-radius: 16px; padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .card h3 { font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 14px; }
        .task-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; }
        .task-row:last-child { border-bottom: none; }
        .badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .badge.red    { background: #fee2e2; color: #dc2626; }
        .badge.blue   { background: #dbeafe; color: #1d4ed8; }
        .badge.green  { background: #dcfce7; color: #15803d; }
        .badge.amber  { background: #fef3c7; color: #92400e; }
        .menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 16px; }
        .menu-item { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #1e293b; font-size: 0.85rem; font-weight: 500; transition: all 0.15s; }
        .menu-item:hover { background: #f8fafc; transform: translateY(-1px); }
        .menu-item .icon { font-size: 1.2rem; }
        .menu-item .cnt { margin-left: auto; background: #fee2e2; color: #dc2626; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; font-weight: 700; }
        .logout { display: block; text-align: center; color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.85rem; padding: 12px; border-radius: 10px; border: 1px solid #fecaca; background: #fff5f5; }
    </style>
</head>
<body>
<div class="panel">

    <div class="header">
        <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 2)); ?></div>
        <div class="header-info">
            <h2><?php echo $nombre; ?></h2>
            <p>Empleado · <?php echo date('d/m/Y'); ?></p>
        </div>
        <div class="online-badge">● En línea</div>
    </div>

    <div class="stats">
        <div class="stat blue"><label>Tareas hoy</label><strong>8</strong></div>
        <div class="stat green"><label>Completadas</label><strong>5</strong></div>
        <div class="stat red"><label>Pendientes</label><strong>3</strong></div>
    </div>

    <div class="card">
        <h3><i class="fas fa-list-check"></i> Tareas pendientes</h3>
        <div class="task-row">
            <span>Revisar inventario Fruver</span>
            <span class="badge red">Urgente</span>
        </div>
        <div class="task-row">
            <span>Subir PLUs panadería</span>
            <span class="badge blue">Normal</span>
        </div>
        <div class="task-row">
            <span>Confirmar pedidos activos</span>
            <span class="badge amber">Pendiente</span>
        </div>
    </div>

    <div class="menu">
        <a href="../bread1/index.php" class="menu-item">
            <span class="icon">📸</span> Scanner IA
        </a>
        <a href="../bread1/vencer.php" class="menu-item">
            <span class="icon">⏳</span> Próx. a vencer
        </a>
        <a href="../bread1/todo.php" class="menu-item">
            <span class="icon">📋</span> Todos los PLUs
        </a>
        <a href="admin_pedidos.php" class="menu-item">
            <span class="icon">🚚</span> Pedidos activos
            <span class="cnt">2</span>
        </a>
        <a href="mi_perfil.php" class="menu-item">
            <span class="icon">👤</span> Mi perfil
        </a>
        <a href="soporte.php" class="menu-item">
            <span class="icon">💬</span> Mensajes
        </a>
    </div>

    <a href="logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión segura
    </a>

</div>
</body>
</html>