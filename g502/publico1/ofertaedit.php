<?php
session_start();
require_once '../config/db.php';

// ⚠️ Protección básica: solo usuarios con sesión iniciada.
// TODO: reemplazar por una verificación de rol de administrador real
// (ej. columna `rol` en tu tabla de usuarios) antes de usar en producción.
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Trae todas las ofertas existentes, unidas con donjorgito1, para la tabla de gestión.
$ofertas = [];
if ($conn) {
    $sql = "SELECT
                d.id, d.producto_id, d.description, d.discount_percent, d.discounted_price, d.active,
                p.producto, p.imagen, p.marca, p.value_final AS precio_original
            FROM descuentos d
            INNER JOIN donjorgito1 p ON d.producto_id = p.id
            ORDER BY d.created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $ofertas = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ofertas - DON JORGITO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/buscar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/ofertaedit.css">
</head>
<body class="oe-body">

<div id="toast-container"></div>

<div class="oe-container">
    <h1 class="oe-heading"><i class="fas fa-tags"></i> Gestión de Ofertas</h1>

    <!-- ============ FORMULARIO: Crear / Editar oferta ============ -->
    <div class="oe-card">
        <h2 id="oe-form-title">Nueva oferta</h2>

        <form id="oe-form" autocomplete="off">
            <input type="hidden" id="oe-oferta-id" value="">

            <!-- Buscador de producto -->
            <div class="oe-field">
                <label for="oe-product-search">Buscar producto</label>
                <input type="text" id="oe-product-search" placeholder="Escribe el nombre, marca o PLU...">
                <div id="oe-search-results" class="oe-search-results"></div>
            </div>

            <!-- Vista previa del producto seleccionado -->
            <div id="oe-selected-product" class="oe-selected-product" style="display:none;">
                <img id="oe-selected-img" src="" alt="">
                <div>
                    <p id="oe-selected-name" class="oe-selected-name"></p>
                    <p id="oe-selected-meta" class="oe-selected-meta"></p>
                </div>
                <button type="button" id="oe-clear-product" class="oe-clear-btn" aria-label="Quitar producto">✕</button>
            </div>
            <input type="hidden" id="oe-producto-id" value="">

            <!-- Descripción de la oferta -->
            <div class="oe-field">
                <label for="oe-description">Descripción de la oferta</label>
                <input type="text" id="oe-description" maxlength="255" placeholder='Ej: "1x2", "50% de descuento", "Envío gratis"'>
            </div>

            <!-- Tipo de descuento -->
            <div class="oe-field">
                <label>Tipo de descuento</label>
                <div class="oe-radio-group">
                    <label class="oe-radio"><input type="radio" name="oe-discount-type" value="percent" checked> Porcentaje (%)</label>
                    <label class="oe-radio"><input type="radio" name="oe-discount-type" value="fixed"> Precio fijo ($)</label>
                </div>
            </div>

            <div class="oe-field">
                <label for="oe-discount-value" id="oe-discount-label">Porcentaje de descuento</label>
                <input type="number" id="oe-discount-value" min="0" step="0.01" placeholder="Ej: 50">
                <p id="oe-price-preview" class="oe-price-preview"></p>
            </div>

            <!-- Activo -->
            <div class="oe-field oe-checkbox-field">
                <label class="oe-checkbox">
                    <input type="checkbox" id="oe-active" checked> Oferta activa (visible en la página)
                </label>
            </div>

            <div class="oe-form-actions">
                <button type="submit" class="oe-btn oe-btn-primary" id="oe-submit-btn">
                    <i class="fas fa-save"></i> Guardar oferta
                </button>
                <button type="button" class="oe-btn oe-btn-secondary" id="oe-cancel-edit" style="display:none;">
                    Cancelar edición
                </button>
            </div>
        </form>
    </div>

    <!-- ============ TABLA: Ofertas existentes ============ -->
    <div class="oe-card">
        <h2>Ofertas activas y programadas</h2>

        <?php if (empty($ofertas)): ?>
            <p class="oe-empty">Aún no has creado ninguna oferta.</p>
        <?php else: ?>
            <div class="oe-table-wrap">
                <table class="oe-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Descripción</th>
                            <th>Precio original</th>
                            <th>Precio con oferta</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ofertas as $o): ?>
                            <?php
                                if ($o['discounted_price'] !== null) {
                                    $precioFinal = (float)$o['discounted_price'];
                                } elseif ($o['discount_percent'] !== null) {
                                    $precioFinal = round((float)$o['precio_original'] * (1 - ((float)$o['discount_percent'] / 100)), 0);
                                } else {
                                    $precioFinal = (float)$o['precio_original'];
                                }
                            ?>
                            <tr
                                data-oferta-id="<?php echo (int)$o['id']; ?>"
                                data-producto-id="<?php echo (int)$o['producto_id']; ?>"
                                data-producto-nombre="<?php echo htmlspecialchars($o['producto']); ?>"
                                data-producto-marca="<?php echo htmlspecialchars($o['marca'] ?? ''); ?>"
                                data-producto-imagen="<?php echo htmlspecialchars($o['imagen'] ?? ''); ?>"
                                data-producto-precio="<?php echo (float)$o['precio_original']; ?>"
                                data-description="<?php echo htmlspecialchars($o['description']); ?>"
                                data-discount-percent="<?php echo $o['discount_percent'] !== null ? (float)$o['discount_percent'] : ''; ?>"
                                data-discounted-price="<?php echo $o['discounted_price'] !== null ? (float)$o['discounted_price'] : ''; ?>"
                                data-active="<?php echo (int)$o['active']; ?>"
                            >
                                <td class="oe-td-product">
                                    <?php if (!empty($o['imagen'])): ?>
                                        <img src="../Donjorgitofinal/<?php echo htmlspecialchars($o['imagen']); ?>" alt="">
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($o['producto']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($o['description']); ?></td>
                                <td>$<?php echo number_format($o['precio_original'], 0, ',', '.'); ?></td>
                                <td class="oe-td-final-price">$<?php echo number_format($precioFinal, 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($o['active']): ?>
                                        <span class="oe-status oe-status-active">Activa</span>
                                    <?php else: ?>
                                        <span class="oe-status oe-status-inactive">Oculta</span>
                                    <?php endif; ?>
                                </td>
                                <td class="oe-td-actions">
                                    <button type="button" class="oe-icon-btn oe-edit-btn" title="Editar"><i class="fas fa-pen"></i></button>
                                    <button type="button" class="oe-icon-btn oe-delete-btn" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/ofertaedit.js"></script>

<?php if ($conn) { mysqli_close($conn); } ?>
</body>
</html>