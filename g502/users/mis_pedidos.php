<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

$id_usuario = $_SESSION['user_id'];
$nombre     = htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario');

// ─── Get all orders ───────────────────────────────────────────────────────────
$st = mysqli_prepare($conn, "
    SELECT id, nombre_cliente, total_final, total_pedido, 
           fecha_pedido, estado_pedido, direccion_cliente, ciudad_cliente
    FROM pedidos 
    WHERE id_cliente = ? 
    ORDER BY fecha_pedido DESC
");
mysqli_stmt_bind_param($st, "i", $id_usuario);
mysqli_stmt_execute($st);
$pedidos = mysqli_stmt_get_result($st);
$all_pedidos = mysqli_fetch_all($pedidos, MYSQLI_ASSOC);
mysqli_stmt_close($st);

$total_pedidos   = count($all_pedidos);
$total_gastado   = array_sum(array_column($all_pedidos, 'total_final'));
$pedidos_activos = count(array_filter($all_pedidos, fn($p) => $p['estado_pedido'] === 'pendiente' || $p['estado_pedido'] === 'en_proceso'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - g502</title>
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

        /* Header */
        .header {
            background: white; border-radius: 16px; padding: 20px 24px;
            margin-bottom: 16px; display: flex; align-items: center; gap: 16px;
            border-top: 4px solid #2563eb; box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .avatar {
            width: 50px; height: 50px; border-radius: 50%;
            background: #dbeafe; color: #1d4ed8;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 700; flex-shrink: 0;
        }
        .header h2 { font-size: 1.1rem; font-weight: 700; color: #0f172a; }
        .header p  { font-size: 0.8rem; color: #64748b; margin-top: 2px; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
        .stat { background: white; border-radius: 12px; padding: 14px 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .stat label { font-size: 0.7rem; color: #94a3b8; display: block; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .stat strong { font-size: 1.3rem; font-weight: 700; }
        .stat.blue strong  { color: #2563eb; }
        .stat.green strong { color: #16a34a; }
        .stat.amber strong { color: #d97706; }

        /* Filter tabs */
        .filter-tabs { display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; padding-bottom: 2px; }
        .filter-tabs::-webkit-scrollbar { display: none; }
        .tab {
            padding: 7px 14px; border-radius: 20px; font-size: 0.78rem;
            font-weight: 600; cursor: pointer; white-space: nowrap;
            border: 1px solid #e2e8f0; background: white; color: #64748b;
            transition: all 0.15s;
        }
        .tab.active { background: #2563eb; color: white; border-color: #2563eb; }
        .tab:hover:not(.active) { background: #f8fafc; }

        /* Empty state */
        .empty {
            background: white; border-radius: 16px; padding: 50px 24px;
            text-align: center; color: #94a3b8; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .empty i { font-size: 3rem; margin-bottom: 12px; display: block; }
        .empty p { font-size: 0.9rem; }
        .empty a {
            display: inline-block; margin-top: 16px; padding: 10px 20px;
            background: #2563eb; color: white; border-radius: 10px;
            text-decoration: none; font-size: 0.85rem; font-weight: 600;
        }

        /* Order card */
        .order-card {
            background: white; border-radius: 16px; margin-bottom: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
            transition: box-shadow 0.15s;
        }
        .order-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        .order-header {
            padding: 14px 18px; display: flex;
            justify-content: space-between; align-items: center;
            border-bottom: 1px solid #f1f5f9; cursor: pointer;
        }
        .order-id { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
        .order-date { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }

        /* Status badges */
        .status {
            font-size: 0.72rem; font-weight: 700; padding: 4px 12px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .status-pendiente  { background: #fef3c7; color: #92400e; }
        .status-en_proceso { background: #dbeafe; color: #1d4ed8; }
        .status-entregado  { background: #dcfce7; color: #15803d; }
        .status-cancelado  { background: #fee2e2; color: #991b1b; }

        /* Order body (collapsible) */
        .order-body { padding: 0 18px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s; }
        .order-body.open { max-height: 500px; padding: 14px 18px; }

        .order-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; }
        .order-row:last-child { border-bottom: none; }
        .order-row label { color: #94a3b8; font-size: 0.72rem; text-transform: uppercase; }
        .order-row span  { color: #1e293b; font-weight: 500; }

        .order-total {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0 0; margin-top: 8px;
            border-top: 1px solid #f1f5f9;
        }
        .order-total label { font-size: 0.8rem; color: #64748b; font-weight: 600; }
        .order-total strong { font-size: 1.1rem; font-weight: 700; color: #16a34a; }

        /* Chevron */
        .chevron { transition: transform 0.3s; color: #94a3b8; font-size: 0.9rem; }
        .chevron.open { transform: rotate(180deg); }

        /* Details link */
        .btn-detail {
            display: inline-block; margin-top: 12px; padding: 8px 16px;
            background: #f1f5f9; color: #2563eb; border-radius: 8px;
            font-size: 0.78rem; font-weight: 600; text-decoration: none;
            transition: background 0.15s;
        }
        .btn-detail:hover { background: #dbeafe; }

        /* Progress tracker */
        .progress-track {
            display: flex; align-items: center; gap: 0;
            margin: 12px 0; padding: 12px 0;
            border-top: 1px solid #f1f5f9;
        }
        .step { display: flex; flex-direction: column; align-items: center; flex: 1; }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem; font-weight: 700; margin-bottom: 4px;
            background: #f1f5f9; color: #94a3b8;
        }
        .step-dot.done  { background: #dcfce7; color: #15803d; }
        .step-dot.active { background: #dbeafe; color: #1d4ed8; }
        .step-label { font-size: 0.65rem; color: #94a3b8; text-align: center; }
        .step-label.done   { color: #15803d; font-weight: 600; }
        .step-label.active { color: #1d4ed8; font-weight: 600; }
        .step-line { flex: 1; height: 2px; background: #f1f5f9; margin-bottom: 18px; }
        .step-line.done { background: #86efac; }
    </style>
</head>
<body>
<div class="panel">

    <a href="mi_panel.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver al panel
    </a>

    <!-- Header -->
    <div class="header">
        <div class="avatar"><?php echo strtoupper(substr($nombre, 0, 2)); ?></div>
        <div>
            <h2>Mis Pedidos</h2>
            <p><?php echo $nombre; ?> · historial completo</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat blue">
            <label>Total pedidos</label>
            <strong><?php echo $total_pedidos; ?></strong>
        </div>
        <div class="stat amber">
            <label>Activos</label>
            <strong><?php echo $pedidos_activos; ?></strong>
        </div>
        <div class="stat green">
            <label>Gastado</label>
            <strong>$<?php echo number_format($total_gastado/1000, 0); ?>k</strong>
        </div>
    </div>

    <!-- Filter tabs -->
    <div class="filter-tabs">
        <button class="tab active" onclick="filterOrders('todos', this)">Todos</button>
        <button class="tab" onclick="filterOrders('pendiente', this)">⏳ Pendiente</button>
        <button class="tab" onclick="filterOrders('en_proceso', this)">🚚 En proceso</button>
        <button class="tab" onclick="filterOrders('entregado', this)">✅ Entregado</button>
        <button class="tab" onclick="filterOrders('cancelado', this)">❌ Cancelado</button>
    </div>

    <!-- Orders list -->
    <div id="ordersList">
        <?php if (empty($all_pedidos)): ?>
        <div class="empty">
            <i class="fas fa-box-open"></i>
            <p>Aún no tienes pedidos registrados.</p>
            <a href="index.php">Explorar productos</a>
        </div>
        <?php else: ?>
            <?php foreach ($all_pedidos as $p):
                $estado    = $p['estado_pedido'] ?? 'pendiente';
                $fecha     = date('d/m/Y H:i', strtotime($p['fecha_pedido']));
                $total     = number_format($p['total_final'] ?: $p['total_pedido'], 0, ',', '.');

                // Progress steps
                $steps = ['pendiente' => 1, 'en_proceso' => 2, 'entregado' => 3, 'cancelado' => 0];
                $current_step = $steps[$estado] ?? 1;
            ?>
            <div class="order-card" data-status="<?php echo $estado; ?>">
                <div class="order-header" onclick="toggleOrder(this)">
                    <div>
                        <div class="order-id"># <?php echo $p['id']; ?></div>
                        <div class="order-date"><?php echo $fecha; ?></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="status status-<?php echo $estado; ?>">
                            <?php echo str_replace('_', ' ', $estado); ?>
                        </span>
                        <i class="fas fa-chevron-down chevron"></i>
                    </div>
                </div>

                <div class="order-body">

                    <!-- Progress tracker (only for non-cancelled) -->
                    <?php if ($estado !== 'cancelado'): ?>
                    <div class="progress-track">
                        <div class="step">
                            <div class="step-dot <?php echo $current_step >= 1 ? 'done' : ''; ?>">
                                <?php echo $current_step >= 1 ? '✓' : '1'; ?>
                            </div>
                            <div class="step-label <?php echo $current_step >= 1 ? 'done' : ''; ?>">Recibido</div>
                        </div>
                        <div class="step-line <?php echo $current_step >= 2 ? 'done' : ''; ?>"></div>
                        <div class="step">
                            <div class="step-dot <?php echo $current_step >= 2 ? ($current_step === 2 ? 'active' : 'done') : ''; ?>">
                                <?php echo $current_step >= 3 ? '✓' : '2'; ?>
                            </div>
                            <div class="step-label <?php echo $current_step >= 2 ? ($current_step === 2 ? 'active' : 'done') : ''; ?>">En camino</div>
                        </div>
                        <div class="step-line <?php echo $current_step >= 3 ? 'done' : ''; ?>"></div>
                        <div class="step">
                            <div class="step-dot <?php echo $current_step >= 3 ? 'done' : ''; ?>">
                                <?php echo $current_step >= 3 ? '✓' : '3'; ?>
                            </div>
                            <div class="step-label <?php echo $current_step >= 3 ? 'done' : ''; ?>">Entregado</div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="padding:10px 0; font-size:0.82rem; color:#991b1b; background:#fff5f5; border-radius:8px; text-align:center; margin-bottom:10px;">
                        ❌ Este pedido fue cancelado
                    </div>
                    <?php endif; ?>

                    <!-- Order details -->
                    <div class="order-row">
                        <label>Dirección</label>
                        <span><?php echo htmlspecialchars($p['direccion_cliente'] ?? '—'); ?></span>
                    </div>
                    <div class="order-row">
                        <label>Ciudad</label>
                        <span><?php echo htmlspecialchars($p['ciudad_cliente'] ?? '—'); ?></span>
                    </div>

                    <div class="order-total">
                        <label>Total del pedido</label>
                        <strong>$<?php echo $total; ?></strong>
                    </div>

                    <a href="detalle_pedido.php?id=<?php echo $p['id']; ?>" class="btn-detail">
                        <i class="fas fa-list"></i> Ver productos del pedido
                    </a>

                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
// ─── Toggle order card ────────────────────────────────────────────────────────
function toggleOrder(header) {
    const body    = header.nextElementSibling;
    const chevron = header.querySelector('.chevron');
    body.classList.toggle('open');
    chevron.classList.toggle('open');
}

// ─── Filter by status ─────────────────────────────────────────────────────────
function filterOrders(status, btn) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.order-card').forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        card.style.display = (status === 'todos' || cardStatus === status) ? 'block' : 'none';
    });
}
</script>
</body>
</html>