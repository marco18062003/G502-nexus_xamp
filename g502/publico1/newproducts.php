<?php
session_start();
require_once '../config/db.php';

// La sesión guarda 'user_id' (verificado contra login.php / buscar.php).
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = '';
if ($is_logged_in) {
    $user_name = htmlspecialchars($_SESSION['nombre_usuario'] ?? '');
}

// Fetch only the 20 most recent products
$sql_nuevos = "SELECT id, producto, imagen, caracteristica, marca, value_final FROM donjorgito1 ORDER BY id DESC LIMIT 20";
$result_nuevos = mysqli_query($conn, $sql_nuevos);

// Si la consulta falla, evitamos un fatal error y mostramos un array vacío
$productos_nuevos = $result_nuevos ? mysqli_fetch_all($result_nuevos, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Nuevos Lanzamientos | DON JORGITO</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="assets/css/buscar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/estilosindex.css">
    <link rel="stylesheet" type="text/css" href="assets/css/mobile.css">
    <link rel="stylesheet" type="text/css" href="assets/css/newsproducts.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container mb-5">
    <div class="grid-nuevos">
        <?php if (empty($productos_nuevos)): ?>
            <p class="text-muted text-center w-100">No hay productos nuevos disponibles en este momento.</p>
        <?php endif; ?>

        <?php foreach ($productos_nuevos as $producto): ?>
            <div class="product-item shadow-sm border-0 rounded-4 overflow-hidden bg-white h-100 d-flex flex-column">
                <div class="position-relative">
                    <span class="position-absolute top-0 start-0 m-3 badge rounded-pill bg-primary" style="z-index: 5;">NUEVO</span>

                    <a href="buscar.php?query=<?php echo htmlspecialchars(urlencode($producto['producto'])); ?>">
                        <img src="../Donjorgitofinal/<?php echo htmlspecialchars($producto['imagen']); ?>"
                             class="w-100 p-3" style="height: 200px; object-fit: contain;"
                             alt="<?php echo htmlspecialchars($producto['producto']); ?>">
                    </a>
                </div>
                <div class="p-3 mt-auto text-center">
                    <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($producto['producto']); ?></h6>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($producto['caracteristica'] ?? 'Disponible'); ?></p>

                    <?php if ($is_logged_in): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3">
                            <span class="fw-bold text-primary">$<?php echo number_format($producto['value_final'] ?? 0, 0, ',', '.'); ?></span>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-dark btn-sm w-100 rounded-pill">Iniciar sesión para ver precio</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<?php if ($conn) { mysqli_close($conn); } ?>
</body>
</html>