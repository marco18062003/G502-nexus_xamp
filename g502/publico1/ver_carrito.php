<?php
session_start();
require_once '../config/keys.php';

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// ─── CSRF token ───────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ─── Auto-fill if user is logged in ──────────────────────────────────────────
$usuario_logueado = null;
if (isset($_SESSION['user_id'])) {
    require_once '../config/db.php';
    $st = mysqli_prepare($conn, "SELECT nombre_completo, email, telefono, direccion, ciudad FROM usuarios WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($st);
    $usuario_logueado = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ─── Normalize cart items so a malformed session entry can never break the page ──
$cartItems      = [];
$totalCartPrice = 0;
foreach ($_SESSION['cart'] as $productId => $item) {
    $price    = isset($item['price']) ? (float) $item['price'] : 0;
    $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;
    $cartItems[$productId] = [
        'name'           => $item['name'] ?? 'Producto',
        'caracteristica' => $item['caracteristica'] ?? 'N/A',
        'imagen'         => $item['image_name'] ?? '',
        'price'          => $price,
        'quantity'       => $quantity,
    ];
    $totalCartPrice += $price * $quantity;
}
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" type="text/css" href="assets/css/ver_carrito.css">

<main class="container">
    <div class="cart-container">
        <h1 class="cart-title">Tu Carrito de Compras</h1>

        <?php if (!empty($cartItems)): ?>
            <div class="table-responsive">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Característica</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $productId => $item): ?>
                            <?php $subtotal = $item['price'] * $item['quantity']; ?>
                            <tr id="cart-item-<?php echo htmlspecialchars($productId); ?>">
                                <td data-label="Imagen">
                                    <?php if (!empty($item['imagen'])): ?>
                                        <img class="cart-item-image"
                                             src="../Donjorgitofinal/<?php echo htmlspecialchars($item['imagen']); ?>"
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             loading="lazy"
                                             onerror="this.replaceWith(Object.assign(document.createElement('div'), {className:'cart-item-noimage', innerText:'Sin imagen'}));">
                                    <?php else: ?>
                                        <div class="cart-item-noimage">Sin imagen</div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Producto">
                                    <span class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                </td>
                                <td data-label="Característica"><?php echo htmlspecialchars($item['caracteristica']); ?></td>
                                <td data-label="Precio Unitario" class="cart-item-price">
                                    $<?php echo number_format($item['price'], 0, ',', '.'); ?>
                                </td>
                                <td data-label="Cantidad">
                                    <input type="number"
                                        class="quantity-input-cart"
                                        value="<?php echo htmlspecialchars($item['quantity']); ?>"
                                        min="1"
                                        max="99"
                                        inputmode="numeric"
                                        aria-label="Cantidad de <?php echo htmlspecialchars($item['name']); ?>"
                                        data-id="<?php echo htmlspecialchars($productId); ?>"
                                        id="cart-quantity-<?php echo htmlspecialchars($productId); ?>"
                                        style="width:60px; text-align:center;">
                                </td>
                                <td data-label="Subtotal" class="cart-item-subtotal"
                                    id="subtotal-<?php echo htmlspecialchars($productId); ?>">
                                    $<?php echo number_format($subtotal, 0, ',', '.'); ?>
                                </td>
                                <td data-label="Acciones">
                                    <button class="remove-item-btn"
                                            type="button"
                                            data-id="<?php echo htmlspecialchars($productId); ?>"
                                            aria-label="Eliminar <?php echo htmlspecialchars($item['name']); ?> del carrito">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            
            <div class="cart-total" id="total-cart-price">
                Domicilio: $10.000
            <br>
            <?php
                $costo_domicilio = 0;
            ?>
            
            
                Total: $<?php echo number_format($totalCartPrice + $costo_domicilio , 0, ',', '.'); ?>
            </div>

            <div class="cart-actions">
                <a href="index.php" class="btn-continue-shopping">Seguir Comprando</a>
                <button class="btn-checkout" id="checkout-button" type="button">Proceder al Pago</button>
            </div>

        <?php else: ?>
            <p class="cart-empty-message" id="cartEmptyMessage">Tu carrito de compras está vacío.</p>
            <div style="text-align:center; margin-top:20px;">
                <a href="index.php" class="btn-continue-shopping">Explorar Productos</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- ─── CHECKOUT MODAL ──────────────────────────────────────────────────────── -->
<div id="checkoutModal" role="dialog" aria-modal="true" aria-labelledby="checkoutModalTitle" aria-hidden="true">
    <div class="modal-content">
        <h3 id="checkoutModalTitle">Información para el Pedido</h3>

        <?php if ($usuario_logueado): ?>
        <div class="autofill-banner logged">
            ✅ Sesión activa — tus datos fueron pre-llenados automáticamente.
        </div>
        <?php else: ?>
        <div class="autofill-banner guest">
            💡 <a href="login.php">Inicia sesión</a> para llenar tus datos automáticamente.
        </div>
        <?php endif; ?>

        <form id="checkoutForm" action="confirmacion_pedido.php" method="POST" novalidate>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <?php if ($usuario_logueado): ?>
            <input type="hidden" name="id_cliente" value="<?php echo (int) $_SESSION['user_id']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="nombre_cliente" class="form-label">Nombre Completo:</label>
                <input type="text" id="nombre_cliente" name="nombre_cliente"
                       class="form-control" required autocomplete="name"
                       placeholder="Ej: Juan Pérez"
                       value="<?php echo htmlspecialchars($usuario_logueado['nombre_completo'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="email_cliente" class="form-label">Correo Electrónico:</label>
                <input type="email" id="email_cliente" name="email_cliente"
                       class="form-control" required autocomplete="email"
                       placeholder="correo@ejemplo.com"
                       value="<?php echo htmlspecialchars($usuario_logueado['email'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="telefono_cliente" class="form-label">Teléfono:</label>
                <input type="tel" id="telefono_cliente" name="telefono_cliente"
                       class="form-control" required autocomplete="tel"
                       pattern="[0-9+ ]{7,15}"
                       placeholder="Ej: 3001234567"
                       value="<?php echo htmlspecialchars($usuario_logueado['telefono'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="direccion_cliente" class="form-label">Dirección:</label>
                <div class="location-row">
                    <input type="text" id="direccion_cliente" name="direccion_cliente"
                           class="form-control" required autocomplete="street-address"
                           placeholder="Escribe o usa tu ubicación 📍"
                           value="<?php echo htmlspecialchars($usuario_logueado['direccion'] ?? ''); ?>">
                    <button type="button" id="btnGetLocation" class="btn-location"
                            title="Usar mi ubicación actual" aria-label="Usar mi ubicación actual">📍</button>
                </div>
                <input type="hidden" id="lat_cliente" name="lat_cliente">
                <input type="hidden" id="lng_cliente" name="lng_cliente">
                <p id="locationStatus" role="status" aria-live="polite"></p>
            </div>

            <div class="mb-3">
                <label for="ciudad_cliente" class="form-label">Ciudad:</label>
                <input type="text" id="ciudad_cliente" name="ciudad_cliente"
                       class="form-control" required autocomplete="address-level2"
                       placeholder="Ej: Bogotá"
                       value="<?php echo htmlspecialchars($usuario_logueado['ciudad'] ?? ''); ?>">
            </div>

            <!-- ─── MÉTODO DE PAGO (NUEVO) ────────────────────────────────────────── -->
            <div class="mb-3">
                <label class="form-label">Método de pago:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metodo_pago"
                           id="pago_efectivo" value="efectivo" checked>
                    <label class="form-check-label" for="pago_efectivo">
                        Efectivo contra entrega
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="metodo_pago"
                           id="pago_wompi" value="wompi">
                    <label class="form-check-label" for="pago_wompi">
                        Pagar ahora con Wompi (tarjeta / PSE / Nequi)
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-checkout w-100 mb-2" id="submitOrderBtn">
                Confirmar Pedido
            </button>
            <button type="button" id="closeModal" class="btn btn-secondary w-100">
                Cancelar
            </button>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;

    const formatMoney = (number) =>
        '$' + new Intl.NumberFormat('es-CO').format(number);

    const postJSON = async (url, payload) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CSRF_TOKEN
            },
            body: JSON.stringify(payload)
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json();
    };

    const updateCartCount = (count) => {
        const el = document.querySelector('.cart-count');
        if (el) el.textContent = count;
    };

    // ─── 1. Quantity change (debounced + guarded) ─────────────────────────────
    document.querySelectorAll('.quantity-input-cart').forEach(input => {
        let debounceTimer;
        const originalValue = input.value;

        input.addEventListener('change', (e) => {
            let newQuantity = parseInt(e.target.value, 10);
            const max = parseInt(e.target.max, 10) || 99;

            if (isNaN(newQuantity) || newQuantity < 1) newQuantity = 1;
            if (newQuantity > max) newQuantity = max;
            e.target.value = newQuantity;

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                const productId = e.target.dataset.id;
                e.target.disabled = true;
                try {
                    const data = await postJSON('update_cart.php', {
                        product_id: productId,
                        quantity: newQuantity
                    });
                    if (data.success) {
                        document.getElementById(`subtotal-${productId}`).textContent =
                            formatMoney(data.item_subtotal);
                        document.getElementById('total-cart-price').textContent =
                            'Total: ' + formatMoney(data.total_cart_price);
                        updateCartCount(data.new_cart_total_items);
                    } else {
                        e.target.value = originalValue;
                        alert(data.message || 'No se pudo actualizar la cantidad.');
                    }
                } catch (err) {
                    e.target.value = originalValue;
                    alert('Error al actualizar cantidad. Intenta de nuevo.');
                } finally {
                    e.target.disabled = false;
                }
            }, 300);
        });
    });

    // ─── 2. Remove item ───────────────────────────────────────────────────────
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const productId = btn.dataset.id;
            if (!confirm('¿Eliminar este producto?')) return;

            btn.disabled = true;
            try {
                const data = await postJSON('remove_from_cart.php', { product_id: productId });
                if (data.success) {
                    const row = document.getElementById(`cart-item-${productId}`);
                    if (row) row.remove();
                    document.getElementById('total-cart-price').textContent =
                        'Total: ' + formatMoney(data.total_cart_price);
                    updateCartCount(data.new_cart_total_items);
                    if (data.new_cart_total_items == 0) location.reload();
                } else {
                    alert(data.message || 'No se pudo eliminar el producto.');
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Error al eliminar producto. Intenta de nuevo.');
                btn.disabled = false;
            }
        });
    });

    // ─── 3. Checkout modal (with focus + ESC handling) ────────────────────────
    const checkoutBtn = document.getElementById('checkout-button');
    const modal        = document.getElementById('checkoutModal');
    const closeBtn      = document.getElementById('closeModal');
    let lastFocusedEl;

    const openModal = () => {
        lastFocusedEl = document.activeElement;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('nombre_cliente')?.focus();
        document.addEventListener('keydown', onKeydown);
    };

    const closeModalFn = () => {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.removeEventListener('keydown', onKeydown);
        lastFocusedEl?.focus();
    };

    const onKeydown = (e) => {
        if (e.key === 'Escape') closeModalFn();
    };

    if (checkoutBtn) checkoutBtn.onclick = (e) => { e.preventDefault(); openModal(); };
    if (closeBtn)    closeBtn.onclick    = closeModalFn;
    window.addEventListener('click', (event) => { if (event.target === modal) closeModalFn(); });

    // ─── 4. Checkout form: basic client-side guard against double submit ──────
    const checkoutForm  = document.getElementById('checkoutForm');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', () => {
            submitOrderBtn.disabled = true;
            submitOrderBtn.textContent = 'Enviando...';
        });
    }

    // ─── 5. GPS Location button ────────────────────────────────────────────────
    const btnGetLocation = document.getElementById('btnGetLocation');
    const locationStatus = document.getElementById('locationStatus');
    const MAPS_KEY       = <?php echo json_encode(defined('GOOGLE_MAPS_KEY') ? GOOGLE_MAPS_KEY : ''); ?>;

    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', () => {
            if (!navigator.geolocation) {
                locationStatus.style.color = '#ef4444';
                locationStatus.innerText   = '⚠️ Tu navegador no soporta geolocalización.';
                return;
            }
            if (!MAPS_KEY) {
                locationStatus.style.color = '#ef4444';
                locationStatus.innerText   = '⚠️ Servicio de mapas no disponible.';
                return;
            }
            btnGetLocation.innerHTML   = '⏳';
            btnGetLocation.disabled    = true;
            locationStatus.style.color = '#64748b';
            locationStatus.innerText   = '📡 Obteniendo tu ubicación...';

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    document.getElementById('lat_cliente').value = lat;
                    document.getElementById('lng_cliente').value = lng;
                    try {
                        const res = await fetch(
                            `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${MAPS_KEY}&language=es`
                        );
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        if (data.status === 'OK' && data.results.length > 0) {
                            document.getElementById('direccion_cliente').value =
                                data.results[0].formatted_address;
                            const city = data.results[0].address_components
                                .find(c => c.types.includes('locality'));
                            if (city && !document.getElementById('ciudad_cliente').value)
                                document.getElementById('ciudad_cliente').value = city.long_name;
                            locationStatus.style.color = '#15803d';
                            locationStatus.innerText   = '✅ Ubicación detectada correctamente.';
                        } else {
                            document.getElementById('direccion_cliente').value = `${lat}, ${lng}`;
                            locationStatus.style.color = '#f59e0b';
                            locationStatus.innerText   = '⚠️ No se pudo convertir la dirección.';
                        }
                    } catch (e) {
                        document.getElementById('direccion_cliente').value = `${lat}, ${lng}`;
                        locationStatus.style.color = '#f59e0b';
                        locationStatus.innerText   = '⚠️ Error de red. Se guardaron las coordenadas.';
                    }
                    btnGetLocation.innerHTML = '📍';
                    btnGetLocation.disabled  = false;
                },
                (err) => {
                    btnGetLocation.innerHTML   = '📍';
                    btnGetLocation.disabled    = false;
                    locationStatus.style.color = '#ef4444';
                    const messages = {
                        1: '⚠️ Permiso denegado. Activa la ubicación en tu navegador.',
                        2: '⚠️ No se pudo detectar tu ubicación.',
                        3: '⚠️ Tiempo agotado. Intenta de nuevo.'
                    };
                    locationStatus.innerText = messages[err.code] || '⚠️ Error desconocido.';
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }
});
</script>
</body>
</html>