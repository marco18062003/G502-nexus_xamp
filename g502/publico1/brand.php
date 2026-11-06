<?php
session_start();
require_once '../config/db.php'; // Using your existing connection path

// Check if the 'user_id' and 'nombre_usuario' are set in the session
// This determines if the user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = ''; // Initialize an empty string for the user's name

if ($is_logged_in) {
    // If logged in, get the user's name from the session
    $user_name = htmlspecialchars($_SESSION['nombre_usuario']);
}

// Fetch brands from the 'marcas' table
$marcas = [];
$sql = "SELECT * FROM marcas ORDER BY nombre ASC";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $marcas[] = $row;
    }
    mysqli_free_result($result);
}

// --- Fetch Products in Trend ---
$productos_en_tendencia = [];

if ($conn) {
    $sql_tendencia = "SELECT id, producto, imagen, precio FROM donjorgito1 ORDER BY id DESC LIMIT 600";
    $result_tendencia = mysqli_query($conn, $sql_tendencia);

    if ($result_tendencia) {
        while ($row = mysqli_fetch_assoc($result_tendencia)) {
            $productos_en_tendencia[] = $row;
        }
        mysqli_free_result($result_tendencia);
    } else {
        echo "<div class='alert alert-warning'>Error al cargar productos en tendencia: " . htmlspecialchars(mysqli_error($conn)) . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Error al conectar con la base de datos para productos en tendencia.</div>";
}

// Imágenes del carrusel
$base_carousel_image_path = '../images/';

$carousel_slides = [
    [
        'image' => $base_carousel_image_path . 'slide.jpg',
        'title' => 'PROMOCIONES ESPECIALES DE VERANO',
        'subtitle' => 'Los mejores licores con descuentos increíbles.',
        'button_text' => 'Ver Ofertas',
        'button_link' => 'buscar.php?query=promociones',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-1.jpg',
        'title' => 'DESCUBRE NUESTROS PRODUCTOS EXCLUSIVOS',
        'subtitle' => 'Una selección única para paladares exigentes.',
        'button_text' => 'Explorar',
        'button_link' => 'productos.php',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-3.jpg',
        'title' => 'ENVÍO RÁPIDO Y SEGURO A TU PUERTA',
        'subtitle' => 'Disfruta sin salir de casa.',
        'button_text' => 'Más Información',
        'button_link' => 'ayuda.php#envio',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-4.jpg',
        'title' => 'REGÍSTRATE Y OBTÉN UN 10% DE DESCUENTO',
        'subtitle' => 'No te pierdas nuestras ofertas exclusivas.',
        'button_text' => 'Registrarse',
        'button_link' => 'registro.php',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>DON JORGITO</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="Marco">
    <meta name="keywords" content="Pagina de Licores">
    <link rel="icon" href="assets/icon1.png" type="image/jpeg">
    <meta name="description" content="Pagina de Licores">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">

    
    <link rel="stylesheet" type="text/css" href="assets/css/brand.css">
    <link rel="stylesheet" type="text/css" href="assets/css/estilosindex.css">
    <link rel="stylesheet" type="text/css" href="assets/css/mobile.css">
    

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body { background-color: #0d0d0d; }
        .main-footer { background-color: #1a1a1a; border-top: 1px solid #a8860f; color: #f4e5b2; }
        .main-footer h5 { color: #d4af37; }
        .main-footer a.text-white:hover { color: #d4af37 !important; }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="container my-5">
        <h2 class="text-center mb-5 fw-bold">MARCAS QUE MANEJAMOS</h2>

        <div class="brand-container">
            <?php if (empty($marcas)): ?>
                <p class="text-center w-100">No hay marcas registradas aún.</p>
            <?php else: ?>
                <?php foreach ($marcas as $marca): ?>
                    <a href="buscar.php?query=<?php echo urlencode($marca['nombre']); ?>" class="brand-card">
                        <img src="../images/marcas/<?php echo htmlspecialchars($marca['imagen']); ?>"
                             class="brand-image"
                             alt="<?php echo htmlspecialchars($marca['nombre']); ?>">
                        <span class="brand-name"><?php echo htmlspecialchars($marca['nombre']); ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="main-footer pt-5 pb-4">
        <div class="container-lg">
            <div class="row">

                <div class="col-md-4 mb-4 mb-md-0 text-center text-md-start">
                    <h5 class="text-uppercase fw-bold mb-3">DON JORGITO</h5>
                    <p class="small">
                        Tu tienda de licores de confianza. <br>
                        ¡Disfruta responsablemente!
                    </p>
                    <ul class="list-unstyled small mt-3">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Dirección de la Tienda</li>
                        <li><i class="fas fa-phone me-2"></i> +57 (300) 123-4567</li>
                        <li><i class="fas fa-envelope me-2"></i> contacto@donjorgito.com</li>
                    </ul>
                </div>

                <div class="col-md-4 mb-4 mb-md-0 text-center">
                    <h5 class="text-uppercase fw-bold mb-3">Enlaces Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-white small">Inicio</a></li>
                        <li><a href="#" class="text-decoration-none text-white small">Productos</a></li>
                        <li><a href="#" class="text-decoration-none text-white small">Nuestra Historia</a></li>
                        <li><a href="#" class="text-decoration-none text-white small">Política de Envíos</a></li>
                    </ul>
                </div>

                <div class="col-md-4 text-center text-md-end">
                    <h5 class="text-uppercase fw-bold mb-3">Síguenos</h5>
                    <div class="social-links fs-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

            </div>
        </div>

        <div class="container-fluid border-top border-secondary mt-4 pt-3">
            <div class="text-center small">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Tu Tienda. Todos los derechos reservados. | Desarrollado para g502</p>
            </div>
        </div>
    </footer>

</body>
</html>