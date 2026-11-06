<?php
// admin_pedidos.php - Panel de pedidos APROBADOS: procesar, entregar y cancelar.
// Los pedidos llegan aquí desde pre-pedidos.php cuando se pulsa "Aprobar".

session_start();

// ------------------------------------------------------------------
// SEGURIDAD: cambia a true cuando tengas tu login y ajusta la variable de sesión
// ------------------------------------------------------------------
$EXIGIR_ADMIN = false;
if ($EXIGIR_ADMIN && empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

// 1. CONEXIÓN
require_once '../config/db.php';
require_once '../config/keys.php';

if (empty($conn)) {
    http_response_code(500);
    echo "Error de conexión a la base de datos: No se pudo establecer la conexión.";
    exit;
}
mysqli_set_charset($conn, 'utf8mb4');

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Los estados se comparan sin importar mayúsculas ni espacios sobrantes
function estado_norm($s) { return strtolower(trim((string)$s)); }
function es_final($estado) { return in_array(estado_norm($estado), ['entregado', 'cancelado'], true); }
function es_pagado($estado_pago) {
    return in_array(estado_norm($estado_pago), ['pagado', 'aprobado', 'approved', 'paid'], true);
}

// 2. Consulta: solo pedidos APROBADOS en pre-pedidos.php (columna pedidos.aprobado_en con fecha).
// Los que ya están en proceso, entregados o cancelados se siguen mostrando siempre,
// para que los pedidos anteriores no desaparezcan.
// Si la columna aún no existe, se muestran todos (hasta que abras pre-pedidos.php una vez, que la crea).
function get_all_orders($conn) {
    $res_col = mysqli_query($conn, "SHOW COLUMNS FROM pedidos LIKE 'aprobado_en'");
    $tiene   = $res_col && mysqli_num_rows($res_col) > 0;

    $where = $tiene
        ? "WHERE aprobado_en IS NOT NULL
              OR LOWER(TRIM(COALESCE(estado_pedido, ''))) IN ('en_proceso', 'entregado', 'cancelado')"
        : "";

    $sql = "SELECT id, nombre_cliente, total_pedido, total_final, direccion_cliente, telefono_cliente,
                   estado_pedido, fecha_pedido, metodo_pago, estado_pago
            FROM pedidos
            $where
            ORDER BY fecha_pedido DESC, id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res === false) {
        error_log("admin_pedidos: " . mysqli_error($conn));
        return [];
    }
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
    return $rows;
}

// 3. Tarjeta de pedido (los botones dependen del estado real del pedido)
function render_order_card($pedido) {
    $est    = estado_norm($pedido['estado_pedido']);
    $final  = es_final($est);
    $pagado = es_pagado($pedido['estado_pago']);

    // Color del encabezado
    if ($final || $est === 'en_proceso') { $clase = $est; }
    elseif ($pagado)                     { $clase = 'pagado'; }
    elseif ($est === 'pendiente')        { $clase = 'pendiente'; }
    else                                 { $clase = 'otros'; }

    $fecha = $pedido['fecha_pedido'] ? date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) : '—';
    $total = $pedido['total_final'] > 0 ? $pedido['total_final'] : $pedido['total_pedido'];
    $total_formateado = number_format((float)$total, 0, ',', '.');
    $id = (int)$pedido['id'];

    $card  = '<div class="card order-card">';
    $card .= '<div class="card-header order-card-header status-' . h($clase) . ' d-flex justify-content-between align-items-center">';
    $card .= '<span>Pedido #' . $id . '</span>';
    if ($pagado) { $card .= '<span class="badge bg-light text-dark">Pagado</span>'; }
    $card .= '</div>';

    $card .= '<div class="card-body">';
    $card .= '<h5 class="card-title text-primary">Total: $' . $total_formateado . '</h5>';
    $card .= '<p class="card-text mb-1">Cliente: <strong>' . h($pedido['nombre_cliente']) . '</strong></p>';
    $card .= '<p class="card-text mb-1 text-muted small">Tel: ' . h($pedido['telefono_cliente']) . ' - ' . $fecha . '</p>';
    if ($clase === 'otros') {
        $card .= '<p class="card-text mb-1 small text-muted">Estado: <code>' . h($pedido['estado_pedido'] ?? 'NULL') . '</code></p>';
    }
    $card .= '<div class="mb-2"></div>';

    $card .= '<div class="btn-group action-btn-group w-100" role="group">';

    // Botón de Detalles (abre el modal)
    $card .= '<button type="button" class="btn btn-sm btn-outline-secondary view-details-btn"
                data-id="' . $id . '"
                data-client="' . h($pedido['nombre_cliente']) . '"
                data-address="' . h($pedido['direccion_cliente']) . '"
                data-total="' . h($total) . '"
                data-bs-toggle="modal" data-bs-target="#orderDetailsModal">
                <i class="fas fa-eye"></i> <span class="btn-label">Detalles</span>
            </button>';

    if (!$final) {
        if ($est === 'en_proceso') {
            $card .= '<button type="button" class="btn btn-sm btn-success update-status-btn" data-id="' . $id . '" data-new-status="entregado"><i class="fas fa-check"></i> <span class="btn-label">Entregado</span></button>';
        } else {
            // pendiente, pagado, o cualquier estado desconocido: se puede pasar a "en proceso"
            $card .= '<button type="button" class="btn btn-sm btn-primary update-status-btn" data-id="' . $id . '" data-new-status="en_proceso"><i class="fas fa-truck"></i> <span class="btn-label">Procesar</span></button>';
        }
        $card .= '<button type="button" class="btn btn-sm btn-danger update-status-btn" data-id="' . $id . '" data-new-status="cancelado"><i class="fas fa-times"></i> <span class="btn-label">Cancelar</span></button>';
    }
    $card .= '</div>';

    if ($final) {
        $card .= '<span class="badge bg-' . ($est === 'entregado' ? 'success' : 'danger') . ' w-100 p-2 text-uppercase mt-2">' . h($est) . '</span>';
    }

    $card .= '</div></div>';
    return $card;
}

// 4. Repartir los pedidos en pestañas
// La pestaña la decide SOLO estado_pedido (lo que ve el cliente).
// Todo lo que no sea en_proceso / cancelado / entregado vive en "Pendiente",
// separado en tres secciones: Pendientes, Pagados y Otros.
$todos = get_all_orders($conn);
$pend = ['pendientes' => [], 'pagados' => [], 'otros' => []];
$proceso = $entregados = $cancelados = [];
foreach ($todos as $p) {
    $est = estado_norm($p['estado_pedido']);
    if     ($est === 'entregado')  { $entregados[] = $p; }
    elseif ($est === 'cancelado')  { $cancelados[] = $p; }
    elseif ($est === 'en_proceso') { $proceso[] = $p; }
    elseif (es_pagado($p['estado_pago'])) { $pend['pagados'][] = $p; }      // pagado y aún sin procesar
    elseif ($est === 'pendiente')  { $pend['pendientes'][] = $p; }
    else                           { $pend['otros'][] = $p; }               // NULL o estado desconocido
}

function tab_count($secciones) {
    $n = 0;
    foreach ($secciones as $s) { $n += count($s[1]); }
    return $n;
}

// clave => [etiqueta, color del badge, mensaje si está vacía, secciones [[título|null, pedidos], ...]]
$tabs = [
    'pendiente' => ['🔴 Pendiente', 'bg-warning text-dark', '🎉 ¡No hay pedidos pendientes!', [
        ['Pendientes', $pend['pendientes']],
        ['Pagados',    $pend['pagados']],
        ['Otros (estado vacío o desconocido)', $pend['otros']],
    ]],
    'proceso'   => ['🟡 En Proceso', 'bg-primary', 'No hay pedidos en preparación.', [[null, $proceso]]],
    'cancelado' => ['🔴 Cancelado',  'bg-danger',  'No hay pedidos cancelados.',      [[null, $cancelados]]],
    'entregado' => ['🟢 Entregado',  'bg-success', 'Aún no hay pedidos entregados.',  [[null, $entregados]]],
];

$maps_key = defined('GOOGLE_MAPS_KEY') ? GOOGLE_MAPS_KEY : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Pedidos - g502</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #212529;
            --text-light: #f8f9fa;
        }
        * { box-sizing: border-box; }
        html, body { overflow-x: hidden; max-width: 100%; }
        body { background-color: #f4f5f7; }
        .admin-header {
            background-color: var(--primary-dark);
            color: var(--text-light);
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .admin-header a { color: var(--text-light); text-decoration: none; }
        .admin-header .text-muted { color: #aaa !important; }
        .admin-header h1 { font-size: 1.75rem; margin-bottom: 0; }

        .container { padding-left: 12px; padding-right: 12px; }

        /* Pestañas */
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

        /* Tarjetas de pedido */
        .order-card {
            border: none;
            margin-bottom: 20px;
            border-radius: 8px;
            transition: box-shadow 0.3s;
            background-color: white;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        .order-card:hover { box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15); }
        .order-card-header {
            padding: 12px 15px;
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
            color: white;
        }
        .card-body { padding-top: 5px; }
        .card-title { font-size: 1.3em; font-weight: 700; }
        .card-text strong { color: #343a40; }

        /* Colores por estado */
        .status-pendiente  { background-color: #ffc107 !important; color: #343a40 !important; }
        .status-en_proceso { background-color: #007bff !important; color: white !important; }
        .status-pagado     { background-color: #0aa2c0 !important; color: white !important; }
        .status-otros      { background-color: #6f42c1 !important; color: white !important; }
        .status-entregado  { background-color: #28a745 !important; color: white !important; }
        .status-cancelado  { background-color: #dc3545 !important; color: white !important; }

        .tab-content { min-height: 500px; padding-top: 15px; }
        .section-title { margin: 8px 0 14px; padding-bottom: 6px; border-bottom: 2px solid #dee2e6; font-weight: 700; color: #495057; }

        .action-btn-group { flex-wrap: wrap; gap: 6px; }
        .action-btn-group .btn {
            flex: 1 1 auto;
            min-width: 0;
            border-radius: 6px !important;
            white-space: nowrap;
        }

        /* Mapa */
        #map { height: 380px; width: 100%; max-width: 100%; border-radius: 10px; margin-top: 15px; }
        .route-info { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        .route-pill {
            background: #f1f5f9; border-radius: 20px;
            padding: 6px 14px; font-size: 0.85rem; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
        }
        .route-pill.green  { background: #dcfce7; color: #15803d; }
        .route-pill.blue   { background: #dbeafe; color: #1d4ed8; }
        .route-pill.orange { background: #ffedd5; color: #c2410c; }
        .origin-toggle { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .btn-origin {
            padding: 7px 14px; border-radius: 8px; border: 2px solid #e2e8f0;
            background: white; font-size: 0.82rem; font-weight: 700;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-origin.active { background: #2563eb; color: white; border-color: #2563eb; }

        /* Tabla de productos: scroll horizontal en vez de desbordar */
        .table-responsive-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        #modal-products-list, .table { font-size: 0.9rem; }

        /* Tablets y móviles */
        @media (max-width: 768px) {
            .admin-header { padding: 14px 0; margin-bottom: 18px; }
            .admin-header h1 { font-size: 1.4rem; }
            .admin-header p { font-size: 0.85rem; }
            .nav-tabs .nav-link { padding: 8px 12px; margin-right: 6px; font-size: 0.85rem; }
            .order-card-header { font-size: 1em; padding: 10px 12px; }
            .card-title { font-size: 1.1em; }
            .card-body { padding: 12px; }

            .action-btn-group { flex-direction: column; }
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
        @media (max-width: 576px) {
            .table { font-size: 0.8rem; }
            .table th, .table td { padding: 0.4rem; }
        }
        @media (max-width: 420px) {
            .admin-header h1 { font-size: 1.2rem; }
            .nav-tabs .nav-link { font-size: 0.78rem; padding: 7px 9px; }
            .card-title { font-size: 1em; }
            #map { height: 220px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>


<main class="container">
    <ul class="nav nav-tabs justify-content-md-center" id="myTab" role="tablist">
        <?php $first = true; foreach ($tabs as $key => $t): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $first ? 'active' : '' ?>" id="<?= h($key) ?>-tab" data-bs-toggle="tab"
                    data-bs-target="#<?= h($key) ?>" type="button" role="tab"
                    aria-controls="<?= h($key) ?>" aria-selected="<?= $first ? 'true' : 'false' ?>">
                <?= $t[0] ?> <span class="badge <?= $t[1] ?> ms-1"><?= tab_count($t[3]) ?></span>
            </button>
        </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <div class="tab-content" id="myTabContent">
        <?php $first = true; foreach ($tabs as $key => $t): ?>
        <div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="<?= h($key) ?>" role="tabpanel" aria-labelledby="<?= h($key) ?>-tab">
            <?php if (tab_count($t[3]) === 0): ?>
                <div class="row"><div class="col-12"><p class="text-center p-5 text-muted"><?= h($t[2]) ?></p></div></div>
            <?php else: ?>
                <?php foreach ($t[3] as $sec): if (!$sec[1]) continue; ?>
                    <?php if ($sec[0] !== null): ?>
                        <h6 class="section-title"><?= h($sec[0]) ?> <span class="badge bg-secondary"><?= count($sec[1]) ?></span></h6>
                    <?php endif; ?>
                    <div class="row">
                        <?php foreach ($sec[1] as $pedido): ?>
                            <div class="col-lg-4 col-md-6" id="pedido-<?= (int)$pedido['id'] ?>">
                                <?= render_order_card($pedido) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php $first = false; endforeach; ?>
    </div>
</main>

<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Detalles del Pedido #<span id="modal-order-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">

                <p>Cliente: <strong id="modal-client-name"></strong></p>
                <p>Dirección: <span id="modal-client-address"></span></p>
                <hr>

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
                                <th>Código</th>
                            </tr>
                        </thead>
                        <tbody id="modal-products-list"></tbody>
                    </table>
                </div>
                <h5 class="text-end mt-3">
                    Total: <strong class="text-primary" id="modal-order-total"></strong>
                </h5>

                <hr>

                <h6>🗺️ Ruta de Entrega</h6>

                <div class="origin-toggle">
                    <button type="button" class="btn-origin active" id="btnStore" onclick="setOrigin('store')">🏪 Desde la Tienda</button>
                    <button type="button" class="btn-origin" id="btnGPS" onclick="setOrigin('gps')">📍 Desde Mi Ubicación</button>
                </div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ─── CONFIG ──────────────────────────────────────────────────────────────────
const STORE_ADDRESS = "Carrera 7 #32-16, Bogotá, Colombia"; // ← cambia por la dirección de tu tienda

// ─── ESTADO ──────────────────────────────────────────────────────────────────
let map = null;
let directionsService = null;
let directionsRenderer = null;
let currentDestination = "";
let currentOriginMode = "store"; // 'store' o 'gps'
let userLatLng = null;

const esc = s => String(s ?? '').replace(/[&<>"']/g, c =>
    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const cop = n => '$' + Number(n || 0).toLocaleString('es-CO', {maximumFractionDigits: 0});

// ─── INICIAR MAPA (callback de Google Maps) ──────────────────────────────────
function initMap() {
    directionsService  = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: false });

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: { lat: 4.7110, lng: -74.0721 }, // Bogotá
        mapTypeControl: false,
        streetViewControl: false,
    });
    directionsRenderer.setMap(map);
}

// ─── CAMBIAR ORIGEN ──────────────────────────────────────────────────────────
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
            () => {
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

// ─── CALCULAR RUTA ───────────────────────────────────────────────────────────
function calculateRoute() {
    if (!currentDestination || !directionsService) return;

    const origin = (currentOriginMode === 'gps' && userLatLng)
        ? new google.maps.LatLng(userLatLng.lat, userLatLng.lng)
        : STORE_ADDRESS;

    document.getElementById('mapStatus').innerText = '⏳ Calculando ruta...';

    directionsService.route({
        origin: origin,
        destination: currentDestination + ", Colombia",
        travelMode: google.maps.TravelMode.DRIVING,
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

// ─── MODAL + CAMBIO DE ESTADO ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const updateStatusUrl = 'update_order_status.php';
    const detailsFetchUrl = 'fetch_order_details.php';

    // Recordar la pestaña activa al recargar
    try {
        const t = sessionStorage.getItem('adminTab');
        sessionStorage.removeItem('adminTab');
        if (t) {
            const b = document.querySelector('#myTab [data-bs-target="' + t + '"]');
            if (b) bootstrap.Tab.getOrCreateInstance(b).show();
        }
    } catch {}
    function reloadKeepTab() {
        try {
            const a = document.querySelector('#myTab .nav-link.active');
            if (a) sessionStorage.setItem('adminTab', a.dataset.bsTarget);
        } catch {}
        location.reload();
    }

    // Ver detalles
    document.addEventListener('click', event => {
        const button = event.target.closest('.view-details-btn');
        if (!button) return;

        const orderId       = button.dataset.id;
        const clientAddress = button.dataset.address;

        document.getElementById('modal-order-id').textContent       = orderId;
        document.getElementById('modal-client-name').textContent    = button.dataset.client;
        document.getElementById('modal-client-address').textContent = clientAddress;
        document.getElementById('modal-order-total').textContent    = cop(button.dataset.total);
        document.getElementById('modal-products-list').innerHTML    =
            '<tr><td colspan="6" class="text-center">Cargando...</td></tr>';

        // Reiniciar mapa
        currentDestination = clientAddress;
        currentOriginMode  = 'store';
        userLatLng         = null;
        document.getElementById('btnStore').classList.add('active');
        document.getElementById('btnGPS').classList.remove('active');
        document.getElementById('routeInfo').style.display = 'none';
        document.getElementById('mapStatus').innerText = '';

        fetchOrderDetails(orderId);

        // Calcular la ruta cuando el modal termine de animarse
        setTimeout(() => {
            if (map) {
                google.maps.event.trigger(map, 'resize');
                calculateRoute();
            }
        }, 400);
    });

    // Cambiar estado (lo que ve el cliente)
    document.addEventListener('click', event => {
        const button = event.target.closest('.update-status-btn');
        if (!button) return;

        const orderId   = button.dataset.id;
        const newStatus = button.dataset.newStatus;
        if (!confirm(`¿Cambiar Pedido #${orderId} a ${newStatus.toUpperCase().replace('_', ' ')}?`)) return;

        button.disabled = true;
        fetch(updateStatusUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, new_status: newStatus }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) reloadKeepTab();
            else { alert('Error: ' + data.message); button.disabled = false; }
        })
        .catch(() => { alert('Error de conexión.'); button.disabled = false; });
    });

    function fetchOrderDetails(orderId) {
        fetch(detailsFetchUrl + '?id=' + encodeURIComponent(orderId))
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('modal-products-list');
            list.innerHTML = '';
            if (data.success && data.details.length > 0) {
                data.details.forEach(item => {
                    const codigo = item.ean || item.codigo || 'N/A';
                    list.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${esc(item.nombre_producto)}</td>
                            <td>${esc(item.caracteristica || 'N/A')}</td>
                            <td class="text-end">${esc(item.cantidad)}</td>
                            <td class="text-end">${cop(item.precio_unitario)}</td>
                            <td class="text-end">${cop(item.total)}</td>
                            <td>${esc(codigo)}</td>
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

<!-- Google Maps al final -->
<script src="https://maps.googleapis.com/maps/api/js?key=<?= h($maps_key) ?>&callback=initMap&libraries=places" async defer></script>
</body>
</html>