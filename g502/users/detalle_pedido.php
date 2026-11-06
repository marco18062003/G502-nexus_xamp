<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/db.php';

$id_usuario = $_SESSION['user_id'];
$nombre     = htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario');

// Validar que se reciba un ID de pedido válido en la URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mis_pedidos.php");
    exit();
}

$id_pedido = (int)$_GET['id'];

// 1. Obtener la información general del pedido usando tus columnas exactas
$st_pedido = mysqli_prepare($conn, "
    SELECT id, nombre_cliente, email_cliente, telefono_cliente, direccion_cliente, 
           ciudad_cliente, total_pedido, total_final, fecha_pedido, estado_pedido
    FROM pedidos 
    WHERE id = ? AND id_cliente = ?
    LIMIT 1
");
mysqli_stmt_bind_param($st_pedido, "ii", $id_pedido, $id_usuario);
mysqli_stmt_execute($st_pedido);
$res_pedido = mysqli_stmt_get_result($st_pedido);
$pedido = mysqli_fetch_assoc($res_pedido);
mysqli_stmt_close($st_pedido);

// Si el pedido no existe o no pertenece al usuario logueado, redirigir
if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit();
}

// 2. Obtener los productos de la tabla 'detalle_pedido' basada en tu captura exacta
$st_items = mysqli_prepare($conn, "
    SELECT id_producto, nombre_producto, caracteristica, cantidad, precio_unitario
    FROM detalle_pedido
    WHERE id_pedido = ?
");
mysqli_stmt_bind_param($st_items, "i", $id_pedido);
mysqli_stmt_execute($st_items);
$res_items = mysqli_stmt_get_result($st_items);
$items = mysqli_fetch_all($res_items, MYSQLI_ASSOC);
mysqli_stmt_close($st_items);

$estado = $pedido['estado_pedido'] ?? 'pendiente';
$fecha  = date('d/m/Y H:i', strtotime($pedido['fecha_pedido']));

// Validar si usar total_final o total_pedido (en tu ejemplo total_final está en 0.00 y total_pedido tiene el valor)
$total_mostrar = ($pedido['total_final'] > 0) ? $pedido['total_final'] : $pedido['total_pedido'];
$total_formateado = number_format($total_mostrar, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Pedido #<?php echo $id_pedido; ?> - g502</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .panel { max-width: 700px; margin: auto; }

        /* Enlace para volver */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #64748b; text-decoration: none; font-size: 0.82rem;
            font-weight: 500; margin-bottom: 16px; transition: color 0.15s;
        }
        .back-link:hover { color: #2563eb; }

        /* Contenedor Principal */
        .card {
            background: white; border-radius: 16px; padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 16px;
        }

        /* Header del detalle */
        .detail-header {
            display: flex; justify-content: space-between; align-items: flex-start;
            border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px;
        }
        .detail-header h2 { font-size: 1.2rem; font-weight: 700; color: #0f172a; }
        .detail-header p { font-size: 0.8rem; color: #94a3b8; margin-top: 2px; }

        /* Estados */
        .status {
            font-size: 0.72rem; font-weight: 700; padding: 4px 12px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .status-pendiente  { background: #fef3c7; color: #92400e; }
        .status-en_proceso { background: #dbeafe; color: #1d4ed8; }
        .status-entregado  { background: #dcfce7; color: #15803d; }
        .status-cancelado  { background: #fee2e2; color: #991b1b; }

        /* Secciones de información */
        .info-section { margin-bottom: 24px; }
        .section-title { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; margin-bottom: 10px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; }
        .info-item label { font-size: 0.68rem; color: #64748b; display: block; text-transform: uppercase; }
        .info-item span { font-size: 0.85rem; font-weight: 500; color: #1e293b; }

        /* Lista de productos */
        .product-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
        .product-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0; border-bottom: 1px solid #f1f5f9;
        }
        .product-item:last-child { border-bottom: none; }
        
        .product-info { display: flex; align-items: center; gap: 12px; }
        .product-img { 
            width: 45px; height: 45px; border-radius: 8px; background: #e2e8f0; 
            display: flex; align-items: center; justify-content: center; color: #94a3b8;
            font-size: 1.1rem; overflow: hidden;
        }
        .product-name { font-size: 0.88rem; font-weight: 600; color: #1e293b; }
        .product-qty { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .product-variant { font-size: 0.7rem; color: #2563eb; background: #eff6ff; padding: 1px 6px; border-radius: 4px; display: inline-block; margin-top: 3px; font-weight: 500; }
        
        .product-price { font-size: 0.9rem; font-weight: 700; color: #0f172a; }

        /* Totales */
        .summary-box { border-top: 2px dashed #f1f5f9; padding-top: 16px; margin-top: 16px; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; color: #64748b; }
        .summary-row.total { font-size: 1.1rem; font-weight: 700; color: #16a34a; margin-top: 10px; }
    </style>
</head>
<body>
<div class="panel">

    <a href="mis_pedidos.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Volver a mis pedidos
    </a>

    <div class="card">
        <div class="detail-header">
            <div>
                <h2>Pedido #<?php echo $pedido['id']; ?></h2>
                <p>Realizado el <?php echo $fecha; ?></p>
            </div>
            <span class="status status-<?php echo $estado; ?>">
                <?php echo str_replace('_', ' ', $estado); ?>
            </span>
        </div>

        <div class="info-section">
            <div class="section-title">Datos de Entrega y Contacto</div>
            <div class="info-grid">
                <div class="info-item">
                    <label>Cliente</label>
                    <span><?php echo htmlspecialchars($pedido['nombre_cliente'] ?: $nombre); ?></span>
                </div>
                <div class="info-item">
                    <label>Teléfono</label>
                    <span><?php echo htmlspecialchars($pedido['telefono_cliente'] ?? '—'); ?></span>
                </div>
                <div class="info-item">
                    <label>Ciudad</label>
                    <span><?php echo htmlspecialchars($pedido['ciudad_cliente'] ?? '—'); ?></span>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <span><?php echo htmlspecialchars($pedido['email_cliente'] ?? '—'); ?></span>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <label>Dirección de envío</label>
                    <span><?php echo htmlspecialchars($pedido['direccion_cliente'] ?? '—'); ?></span>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="section-title">Artículos en este pedido</div>
            <div class="product-list">
                <?php if (empty($items)): ?>
                    <p style="font-size: 0.85rem; color: #94a3b8; text-align: center; padding: 10px;">
                        Detalles de artículos no disponibles para este pedido.
                    </p>
                <?php else: ?>
                    <?php foreach ($items as $item): 
                        $subtotal_item = $item['cantidad'] * $item['precio_unitario'];
                    ?>
                        <div class="product-item">
                            <div class="product-info">
                                <div class="product-img"><i class="fas fa-box"></i></div>
                                
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($item['nombre_producto']); ?></div>
                                    <div class="product-qty">
                                        <?php echo $item['cantidad']; ?> x $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?>
                                    </div>
                                    <?php if(!empty($item['caracteristica'])): ?>
                                        <div class="product-variant">
                                            <i class="fas fa-tags" style="font-size:0.65rem;"></i> <?php echo htmlspecialchars($item['caracteristica']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="product-price">
                                $<?php echo number_format($subtotal_item, 0, ',', '.'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?php echo $total_formateado; ?></span>
            </div>
            <div class="summary-row">
                <span>Envío</span>
                <span style="color: #16a34a; font-weight: 500;">Gratis</span>
            </div>
            <div class="summary-row total">
                <span>Total pagado</span>
                <span>$<?php echo $total_formateado; ?></span>
            </div>
        </div>

    </div>
</div>
</body>
</html>