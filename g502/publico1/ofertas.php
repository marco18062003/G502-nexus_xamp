<?php
session_start();
require_once '../config/db.php';

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = '';
if ($is_logged_in) {
    $user_name = htmlspecialchars($_SESSION['nombre_usuario'] ?? '');
}

// ofertas.php - Muestra productos en descuento, uniendo `descuentos` con `donjorgito1`
// para obtener siempre nombre, imagen, marca y precio ACTUALIZADOS.

$ofertas = [];
if ($conn) {
    $sql = "SELECT
                d.id AS oferta_id,
                d.description,
                d.discount_percent,
                d.discounted_price,
                p.id AS producto_id,
                p.producto,
                p.imagen,
                p.caracteristica,
                p.marca,
                p.value_final AS precio_original
            FROM descuentos d
            INNER JOIN donjorgito1 p ON d.producto_id = p.id
            WHERE d.active = 1
            ORDER BY d.created_at DESC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Calcula el precio final de la oferta:
            // 1) Si hay un precio fijo de oferta, se usa ese.
            // 2) Si hay un porcentaje, se aplica sobre el precio original.
            // 3) Si no hay ninguno, se muestra el precio original sin descuento.
            if ($row['discounted_price'] !== null) {
                $row['precio_final'] = (float)$row['discounted_price'];
            } elseif ($row['discount_percent'] !== null) {
                $row['precio_final'] = round((float)$row['precio_original'] * (1 - ((float)$row['discount_percent'] / 100)), 0);
            } else {
                $row['precio_final'] = (float)$row['precio_original'];
            }
            $ofertas[] = $row;
        }
        mysqli_free_result($result);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ofertas y Descuentos - DON JORGITO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="assets/css/buscar.css?v=1730030000">
    <link rel="stylesheet" type="text/css" href="assets/css/ofertas.css?v=1">
    <link rel="stylesheet" type="text/css" href="assets/css/mobile.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="container">
    <h1 class="search-results-heading ofertas-heading">
        <i class="fas fa-fire"></i> Ofertas y Descuentos
    </h1>

    <div class="products-grid">
        <?php if (!empty($ofertas)): ?>
            <?php foreach ($ofertas as $row): ?>
                <div class="product-card oferta-card" style="position: relative;">

                    <span class="oferta-badge"><?php echo htmlspecialchars($row['description']); ?></span>

                    <?php if (!empty($row['imagen'])): ?>
                        <a href="producto_detalle.php?id=<?php echo (int)$row['producto_id']; ?>" class="product-image-link">
                            <img src="../Donjorgitofinal/<?php echo htmlspecialchars($row['imagen']); ?>" alt="<?php echo htmlspecialchars($row['producto']); ?>" class="product-image">
                        </a>
                    <?php else: ?>
                        <div class="no-image-placeholder"><i class="fas fa-image"></i> Sin Imagen</div>
                    <?php endif; ?>

                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($row['producto']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($row['caracteristica']); ?></p>

                        <?php if ($is_logged_in): ?>
                            <div class="oferta-price-row">
                                <?php if ($row['precio_final'] < $row['precio_original']): ?>
                                    <span class="oferta-original-price">$<?php echo number_format($row['precio_original'], 0, ',', '.'); ?></span>
                                <?php endif; ?>
                                <span class="product-price oferta-price">$<?php echo number_format($row['precio_final'], 0, ',', '.'); ?></span>
                            </div>

                            <div class="quantity-control">
                                <button type="button" class="quantity-btn decrease-btn" data-id="<?php echo (int)$row['oferta_id']; ?>">-</button>
                                <input type="number" class="quantity-input" value="1" min="1" data-id="<?php echo (int)$row['oferta_id']; ?>" id="quantity-oferta-<?php echo (int)$row['oferta_id']; ?>">
                                <button type="button" class="quantity-btn increase-btn" data-id="<?php echo (int)$row['oferta_id']; ?>">+</button>
                            </div>

                            <button class="add-to-cart-btn add-oferta-btn" data-id="<?php echo (int)$row['oferta_id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Agregar
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="add-to-cart-btn" style="text-align:center; text-decoration:none; display:block;">
                                Inicia sesión para ver precio
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <p><i class="fas fa-info-circle"></i> No hay ofertas activas en este momento.</p>
                <p>Vuelve pronto, siempre tenemos algo nuevo.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/ofertas.js"></script>

<?php if ($conn) { mysqli_close($conn); } ?>
</body>
</html>