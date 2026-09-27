<?php
// admin_pedidos.php - Panel de Administración para g502 (Versión Final Corregida y Limpia + Responsive)

session_start();
// NOTA: Implementar la verificación de sesión de administrador aquí.

// 1. CONEXIÓN: Incluir el archivo db.php para obtener la conexión ($conn)
// Asegúrate que la ruta sea correcta: '../config/db.php'
require_once '../config/db.php';
require_once '../config/keys.php';

if (empty($conn)) {
    // Si no hay conexión disponible, devolver error y detener la ejecución
    http_response_code(500);
    echo "Error de conexión a la base de datos: No se pudo establecer la conexión.";
    exit;
}

// 2. Funciones de Consulta
function get_orders_by_status($conn, $status) {
    // Consulta principal, usando 'estado_pedido'
    $sql = "SELECT id, nombre_cliente, total_pedido, direccion_cliente, telefono_cliente, estado_pedido, fecha_pedido
             FROM pedidos 
             WHERE estado_pedido = ? 
             ORDER BY fecha_pedido DESC"; 

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt === false) {
        error_log("Error al preparar la consulta: " . mysqli_error($conn));
        return [];
    }
    
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    
    // Obtener el resultado de forma segura
    $result = mysqli_stmt_get_result($stmt);
    
    $pedidos = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $pedidos[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
    return $pedidos;
}

// 3. FUNCIÓN DE RENDERIZADO DE LA TARJETA
function render_order_card($pedido, $status) {
    // Usamos los nombres de columna de tu tabla
    $fecha = date('d/M/Y H:i', strtotime($pedido['fecha_pedido']));
    // Formateo del total sin decimales, usando punto como separador de miles
    $total_formateado = number_format($pedido['total_pedido'], 0, ',', '.');
    
    $card = '<div class="card order-card">';
    $card .= '<div class="card-header order-card-header status-' . htmlspecialchars($status) . ' d-flex justify-content-between align-items-center">';
    $card .= '<span>Pedido #' . htmlspecialchars($pedido['id']) . '</span>';
    $card .= '</div>'; 
    
    $card .= '<div class="card-body">';
    $card .= '<h5 class="card-title text-primary">Total: $' . $total_formateado . '</h5>';
    $card .= '<p class="card-text mb-1">Cliente: <strong>' . htmlspecialchars($pedido['nombre_cliente']) . '</strong></p>';
    $card .= '<p class="card-text mb-3 text-muted small">Tel: ' . htmlspecialchars($pedido['telefono_cliente']) . ' - ' . $fecha . '</p>';

    $card .= '<div class="btn-group action-btn-group w-100" role="group">';
    
    // Botón de Detalles (Abre el modal)
    $card .= '<button type="button" class="btn btn-sm btn-outline-secondary view-details-btn" 
                data-id="' . $pedido['id'] . '" 
                data-client="' . htmlspecialchars($pedido['nombre_cliente']) . '"
                data-address="' . htmlspecialchars($pedido['direccion_cliente']) . '"
                data-total="' . htmlspecialchars($pedido['total_pedido']) . '"
                data-bs-toggle="modal" data-bs-target="#orderDetailsModal">
                <i class="fas fa-eye"></i> <span class="btn-label">Detalles</span>
            </button>';

    // Botones de Acción según el estado
    if ($status === 'pendiente') {
        $card .= '<button type="button" class="btn btn-sm btn-primary update-status-btn" data-id="' . $pedido['id'] . '" data-new-status="en_proceso"><i class="fas fa-truck"></i> <span class="btn-label">Procesar</span></button>';
    } elseif ($status === 'en_proceso') {
        $card .= '<button type="button" class="btn btn-sm btn-success update-status-btn" data-id="' . $pedido['id'] . '" data-new-status="entregado"><i class="fas fa-check"></i> <span class="btn-label">Entregado</span></button>';
    }
    
    // Botón de Cancelar, disponible en pendiente y en proceso
    if ($status === 'pendiente' || $status === 'en_proceso') {
        $card .= '<button type="button" class="btn btn-sm btn-danger update-status-btn" data-id="' . $pedido['id'] . '" data-new-status="cancelado"><i class="fas fa-times"></i> <span class="btn-label">Cancelar</span></button>';
    }
    
    // Mensaje para estados finales
    if ($status === 'entregado' || $status === 'cancelado') {
        $card .= '<span class="badge bg-' . ($status === 'entregado' ? 'success' : 'danger') . ' w-100 p-2 text-uppercase mt-2">' . str_replace('_', ' ', $status) . '</span>';
    }
    
    $card .= '</div>'; 
    $card .= '</div>'; 
    $card .= '</div>'; 
    return $card;
}

// 4. Obtener todos los pedidos clasificados
$pedidos_pendientes = get_orders_by_status($conn, 'pendiente');
$pedidos_en_proceso = get_orders_by_status($conn, 'en_proceso');
$pedidos_entregados = get_orders_by_status($conn, 'entregado');
$pedidos_cancelados = get_orders_by_status($conn, 'cancelado');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Panel de Pedidos - g502</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* INICIO: CSS para Diseño Elegante y Oscuro (Optimizado) */
        :root {
            --primary-dark: #212529;
            --text-light: #f8f9fa;
        }
        * { box-sizing: border-box; }
        html, body { overflow-x: hidden; max-width: 100%; }
        body { background-color: #f4f5f7; } /* Fondo más claro */
        .admin-header { 
            background-color: var(--primary-dark); 
            color: var(--text-light); 
            padding: 20px 0; 
            margin-bottom: 30px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .admin-header .text-muted { color: #aaa !important; }
        .admin-header h1 { font-size: 1.75rem; margin-bottom: 0; }

        .container { padding-left: 12px; padding-right: 12px; }
        
        /* Pestañas estilo Scrum */
        .nav-tabs { 
            border-bottom: none; 
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .nav-tabs .nav-link { 
            border: none;
            color: #6c757d;
            border-radius: 0;
            margin-right: 15px;
            padding: 10px 20px;
            white-space: nowrap;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #007bff; 
            color: #007bff;
            background-color: #e9ecef;
        }
        
        /* Tarjetas de Pedido */
        .order-card { 
            border: none; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            transition: box-shadow 0.3s;
            background-color: white; 
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1); 
        }
        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        .order-card-header { 
            padding: 12px 15px; 
            border-bottom: none; 
            font-weight: bold;
            font-size: 1.1em;
            color: white; /* Texto blanco para mejor contraste */
        }
        .card-body { padding-top: 5px; }
        .card-title { font-size: 1.3em; font-weight: 700; }
        .card-text strong { color: #343a40; }
        
        /* Colores para los estados */
        .status-pendiente { background-color: #ffc107 !important; color: #343a40 !important; }
        .status-en_proceso { background-color: #007bff !important; color: white !important; }
        .status-entregado { background-color: #28a745 !important; color: white !important; }
        .status-cancelado { background-color: #dc3545 !important; color: white !important; }
        
        .tab-content { min-height: 500px; padding-top: 15px; }

        /* Botones de acción de cada tarjeta */
        .action-btn-group { flex-wrap: wrap; gap: 6px; }
        .action-btn-group .btn {
            flex: 1 1 auto;
            min-width: 0;
            border-radius: 6px !important;
            white-space: nowrap;
        }
        
        /* FIN: CSS para Diseño Elegante y Oscuro (Optimizado) */
        
    #map { height: 380px; width: 100%; max-width: 100%; border-radius: 10px; margin-top: 15px; }
    .route-info {
        display: flex; gap: 10px; flex-wrap: wrap;
        margin-top: 12px;
    }
    .route-pill {
        background: #f1f5f9; border-radius: 20px;
        padding: 6px 14px; font-size: 0.85rem; font-weight: 600;
        display: flex; align-items: center; gap: 6px;
    }
    .route-pill.green { background: #dcfce7; color: #15803d; }
    .route-pill.blue  { background: #dbeafe; color: #1d4ed8; }
    .route-pill.orange{ background: #ffedd5; color: #c2410c; }
    .origin-toggle {
        display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap;
    }
    .btn-origin {
        padding: 7px 14px; border-radius: 8px; border: 2px solid #e2e8f0;
        background: white; font-size: 0.82rem; font-weight: 700;
        cursor: pointer; transition: all 0.15s;
    }
    .btn-origin.active { background: #2563eb; color: white; border-color: #2563eb; }

    /* ─── RESPONSIVE: tablets y móviles ─────────────────────────────────── */
    @media (max-width: 768px) {
        .admin-header { padding: 14px 0; margin-bottom: 18px; }
        .admin-header h1 { font-size: 1.4rem; }
        .admin-header p { font-size: 0.85rem; }

        .nav-tabs .nav-link {
            padding: 8px 12px;
            margin-right: 6px;
            font-size: 0.85rem;
        }

        .order-card-header { font-size: 1em; padding: 10px 12px; }
        .card-title { font-size: 1.1em; }
        .card-body { padding: 12px; }

        /* Botones apilados verticalmente para que el texto no se corte */
        .action-btn-group {
            flex-direction: column;
        }
        .action-btn-group .btn {
            width: 100%;
            justify-content: center;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
        }

        #map { height: 260px; }

        .modal-dialog { margin: 0.5rem; }
        .modal-body { padding: 12px; }

        .route-info { gap: 6px; }
        .route-pill { font-size: 0.75rem; padding: 5px 10px; }

        .btn-origin { flex: 1 1 auto; text-align: center; font-size: 0.75rem; padding: 8px 6px; }
    }

    @media (max-width: 420px) {
        .admin-header h1 { font-size: 1.2rem; }
        .nav-tabs .nav-link { font-size: 0.78rem; padding: 7px 9px; }
        .card-title { font-size: 1em; }
        #map { height: 220px; }
    }

    /* Tabla de productos: scroll horizontal en vez de desbordar la pantalla */
    .table-responsive-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #modal-products-list, .table { font-size: 0.9rem; }
    @media (max-width: 576px) {
        .table { font-size: 0.8rem; }
        .table th, .table td { padding: 0.4rem; }
    }

   </style>
</head>
<body>


<header class="admin-header">
    <div class="container">
        <p><a href="index.php" style="color: var(--primary-color); text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a></p>
        <h1 class="text-center">Panel de Pedidos - g502</h1>
        <p class="text-center mt-2 text-muted">Gestión de Órdenes (<?php echo date('d/m/Y'); ?>)</p>
    </div>
</header>

<main class="container">
    <ul class="nav nav-tabs justify-content-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pendiente-tab" data-bs-toggle="tab" data-bs-target="#pendiente" type="button" role="tab" aria-controls="pendiente" aria-selected="true">
                🔴 Pendiente <span class="badge bg-warning text-dark ms-1"><?php echo count($pedidos_pendientes); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="proceso-tab" data-bs-toggle="tab" data-bs-target="#proceso" type="button" role="tab" aria-controls="proceso" aria-selected="false">
                🟡 En Proceso <span class="badge bg-primary ms-1"><?php echo count($pedidos_en_proceso); ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="finalizado-tab" data-bs-toggle="tab" data-bs-target="#finalizado" type="button" role="tab" aria-controls="finalizado" aria-selected="false">
                🟢 Cerrados <span class="badge bg-success ms-1"><?php echo count($pedidos_entregados) + count($pedidos_cancelados); ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="pendiente" role="tabpanel" aria-labelledby="pendiente-tab">
            <div class="row">
                <?php if (empty($pedidos_pendientes)): ?>
                    <div class="col-12"><p class="text-center p-5 text-muted">🎉 ¡No hay pedidos nuevos pendientes!</p></div>
                <?php else: ?>
                    <?php foreach ($pedidos_pendientes as $pedido): ?>
                        <div class="col-lg-4 col-md-6" id="pedido-<?php echo $pedido['id']; ?>">
                            <?php echo render_order_card($pedido, 'pendiente'); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="proceso" role="tabpanel" aria-labelledby="proceso-tab">
            <div class="row">
                <?php if (empty($pedidos_en_proceso)): ?>
                    <div class="col-12"><p class="text-center p-5 text-muted">Parece que no hay pedidos en preparación.</p></div>
                <?php else: ?>
                    <?php foreach ($pedidos_en_proceso as $pedido): ?>
                        <div class="col-lg-4 col-md-6" id="pedido-<?php echo $pedido['id']; ?>">
                            <?php echo render_order_card($pedido, 'en_proceso'); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="finalizado" role="tabpanel" aria-labelledby="finalizado-tab">
            <div class="row">
                <?php 
                $pedidos_finalizados_all = array_merge($pedidos_entregados, $pedidos_cancelados);
                usort($pedidos_finalizados_all, function($a, $b) {
                    return strtotime($b['fecha_pedido']) - strtotime($a['fecha_pedido']);
                });
                ?>
                <?php if (empty($pedidos_finalizados_all)): ?>
                    <div class="col-12"><p class="text-center p-5 text-muted">No hay pedidos finalizados en el registro.</p></div>
                <?php else: ?>
                    <?php foreach ($pedidos_finalizados_all as $pedido): ?>
                        <div class="col-lg-4 col-md-6" id="pedido-<?php echo $pedido['id']; ?>">
                            <?php echo render_order_card($pedido, $pedido['estado_pedido']); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    Detalles del Pedido #<span id="modal-order-id"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Cliente info -->
                <p>Cliente: <strong id="modal-client-name"></strong></p>
                <p>Dirección: <span id="modal-client-address"></span></p>
                <hr>

                <!-- Productos -->
                <h6>Productos:</h6>
                <div class="table-responsive-wrapper">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                
                                <th>Producto</th>
                                <th>Característica</th>
                                <th class="text-end">Cantidad</th>
                                <th class="text-end">Precio Unitario</th>
                                <th class="text-end">Subtotal</th>
                                <th>codigo</th>
                                
                            </tr>
                        </thead>
                        <tbody id="modal-products-list"></tbody>
                    </table>
                </div>
                <h5 class="text-end mt-3">
                    Total: <strong class="text-primary" id="modal-order-total"></strong>
                </h5>

                <hr>

                <!-- ─── MAPA ─────────────────────────────────────────────── -->
                <h6>🗺️ Ruta de Entrega</h6>

                <!-- Toggle origen -->
                <div class="origin-toggle">
                    <button class="btn-origin active" id="btnStore" onclick="setOrigin('store')">
                        🏪 Desde la Tienda
                    </button>
                    <button class="btn-origin" id="btnGPS" onclick="setOrigin('gps')">
                        📍 Desde Mi Ubicación
                    </button>
                </div>

                <!-- Info de ruta -->
                <div class="route-info" id="routeInfo" style="display:none;">
                    <div class="route-pill blue">📏 <span id="routeDistance">—</span></div>
                    <div class="route-pill orange">🕐 <span id="routeDuration">—</span></div>
                    <div class="route-pill green">✅ Ruta calculada</div>
                </div>

                <div id="map"></div>
                <p id="mapStatus" style="font-size:0.8rem; color:#64748b; margin-top:6px;"></p>

            </div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Google Maps + Directions API -->
<script>
// ─── CONFIG ───────────────────────────────────────────────────────────────────
const STORE_ADDRESS = "Carrera 7 #32-16, Bogotá, Colombia"; // ← Change to your store address
const MAPS_API_KEY  = "<?php echo defined('GOOGLE_MAPS_KEY') ? GOOGLE_MAPS_KEY : ''; ?>";

// ─── STATE ────────────────────────────────────────────────────────────────────
let map             = null;
let directionsService = null;
let directionsRenderer = null;
let currentDestination = "";
let currentOriginMode  = "store"; // 'store' or 'gps'
let userLatLng         = null;

// ─── INIT MAP (called by Google Maps callback) ────────────────────────────────
function initMap() {
    directionsService  = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: false });

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: { lat: 4.7110, lng: -74.0721 }, // Bogotá default
        mapTypeControl: false,
        streetViewControl: false,
    });

    directionsRenderer.setMap(map);
}

// ─── TOGGLE ORIGIN ────────────────────────────────────────────────────────────
function setOrigin(mode) {
    currentOriginMode = mode;
    document.getElementById('btnStore').classList.toggle('active', mode === 'store');
    document.getElementById('btnGPS').classList.toggle('active', mode === 'gps');

    if (mode === 'gps') {
        document.getElementById('mapStatus').innerText = '📡 Obteniendo tu ubicación...';
        navigator.geolocation.getCurrentPosition(
            pos => {
                userLatLng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                document.getElementById('mapStatus').innerText = '✅ Ubicación obtenida.';
                if (currentDestination) calculateRoute();
            },
            err => {
                document.getElementById('mapStatus').innerText = '⚠️ No se pudo obtener tu ubicación. Usando tienda.';
                currentOriginMode = 'store';
                document.getElementById('btnStore').classList.add('active');
                document.getElementById('btnGPS').classList.remove('active');
                if (currentDestination) calculateRoute();
            }
        );
    } else {
        userLatLng = null;
        document.getElementById('mapStatus').innerText = '';
        if (currentDestination) calculateRoute();
    }
}

// ─── CALCULATE ROUTE ─────────────────────────────────────────────────────────
function calculateRoute() {
    if (!currentDestination || !directionsService) return;

    const origin = (currentOriginMode === 'gps' && userLatLng)
        ? new google.maps.LatLng(userLatLng.lat, userLatLng.lng)
        : STORE_ADDRESS;

    document.getElementById('mapStatus').innerText = '⏳ Calculando ruta...';

    directionsService.route({
        origin:      origin,
        destination: currentDestination + ", Colombia",
        travelMode:  google.maps.TravelMode.DRIVING,
    }, (result, status) => {
        if (status === 'OK') {
            directionsRenderer.setDirections(result);

            const leg = result.routes[0].legs[0];
            document.getElementById('routeDistance').innerText = leg.distance.text;
            document.getElementById('routeDuration').innerText = leg.duration.text;
            document.getElementById('routeInfo').style.display = 'flex';
            document.getElementById('mapStatus').innerText = '';
        } else {
            document.getElementById('mapStatus').innerText = '⚠️ No se encontró ruta para esta dirección.';
            document.getElementById('routeInfo').style.display = 'none';
        }
    });
}

// ─── MODAL OPEN: fill data + trigger map ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const updateStatusUrl = 'update_order_status.php';
    const detailsFetchUrl = 'fetch_order_details.php';

    // View details button
    document.addEventListener('click', event => {
        const button = event.target.closest('.view-details-btn');
        if (button) {
            const orderId       = button.dataset.id;
            const clientName    = button.dataset.client;
            const clientAddress = button.dataset.address;
            const total         = button.dataset.total;

            document.getElementById('modal-order-id').textContent      = orderId;
            document.getElementById('modal-client-name').textContent    = clientName;
            document.getElementById('modal-client-address').textContent = clientAddress;
            document.getElementById('modal-order-total').textContent    =
                '$' + Number(total).toLocaleString('es-CL');
            document.getElementById('modal-products-list').innerHTML    =
                '<tr><td colspan="6" class="text-center">Cargando...</td></tr>';

            // Reset map state
            currentDestination = clientAddress;
            currentOriginMode  = 'store';
            userLatLng         = null;
            document.getElementById('btnStore').classList.add('active');
            document.getElementById('btnGPS').classList.remove('active');
            document.getElementById('routeInfo').style.display = 'none';
            document.getElementById('mapStatus').innerText = '';

            fetchOrderDetails(orderId);

            // Calculate route after modal animates in
            setTimeout(() => {
                if (map) {
                    google.maps.event.trigger(map, 'resize');
                    calculateRoute();
                }
            }, 400);
        }
    });

    // Update status button
    document.addEventListener('click', event => {
        const button = event.target.closest('.update-status-btn');
        if (button) {
            const orderId   = button.dataset.id;
            const newStatus = button.dataset.newStatus;
            if (confirm(`¿Cambiar Pedido #${orderId} a ${newStatus.toUpperCase().replace('_',' ')}?`)) {
                fetch(updateStatusUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, new_status: newStatus }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert('Error: ' + data.message);
                })
                .catch(() => alert('Error de conexión.'));
            }
        }
    });

    function fetchOrderDetails(orderId) {
        fetch(detailsFetchUrl + '?id=' + orderId)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('modal-products-list');
            list.innerHTML = '';
            if (data.success && data.details.length > 0) {
                data.details.forEach(item => {
                    const codigo = item.ean || item.codigo || 'N/A';
                    list.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${item.nombre_producto}</td>
                            <td>${item.caracteristica || 'N/A'}</td>
                            <td class="text-end">${item.cantidad}</td>
                            <td class="text-end">$${Number(item.precio_unitario).toLocaleString('es-CL')}</td>
                            <td class="text-end">$${Number(item.total).toLocaleString('es-CL')}</td>
                            <td>${codigo}</td>
                        </tr>
                    `);
                });
            } else {
                list.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin productos.</td></tr>';
            }
        })
        .catch(() => {
            document.getElementById('modal-products-list').innerHTML =
                '<tr><td colspan="6" class="text-center text-danger">Error de conexión.</td></tr>';
        });
    }
});
</script>

<!-- Load Google Maps last -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo defined('GOOGLE_MAPS_KEY') ? GOOGLE_MAPS_KEY : ''; ?>&callback=initMap&libraries=places" async defer></script>
</body>
</html>