<?php
session_start(); // Always start the session at the very beginning of your PHP file

// --- CORRECCIÓN CRUCIAL AQUÍ: Usar 'usuario_id' para que coincida con tu login.php ---
// Check if the 'usuario_id' and 'nombre_usuario' are set in the session
// This determines if the user is logged in
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = ''; // Initialize an empty string for the user's name

if ($is_logged_in) {
    // If logged in, get the user's name from the session user_id
    // Make sure 'nombre_usuario' is set when you log in a user
    $user_name = htmlspecialchars($_SESSION['nombre_usuario']);
}
?>
<?php
// buscar.php - Lógica para procesar la búsqueda y mostrar resultados

// Activar la visualización de errores de PHP para depuración (¡QUÍTALA en producción!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Incluir el archivo de conexión a la base de datos
// Asegúrate de que 'db.php' esté en la misma carpeta o ajusta la ruta.
require_once '../config/db.php'; 

// Variable para el término de búsqueda
$searchTerm = '';
$resultsFound = false;
$result = null; // Inicializamos $result a null para evitar posibles errores

// 2. Obtener el término de búsqueda de la URL
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $searchTerm = trim($_GET['query']); // Limpia la entrada para HTML
    
    // Escapar el término de búsqueda para seguridad SQL (PREVENIR INYECCIONES SQL)
    // Es crucial usar mysqli_real_escape_string con la conexión correcta.
    $searchTermEscaped = mysqli_real_escape_string($conn, $searchTerm);

    // 3. Construir la consulta SQL
    // Conectando las condiciones LIKE con OR
    // 1. Capturamos la opción de orden del usuario (si no hay, por defecto es 'default')
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';

// 2. Decidimos el ORDER BY según la opción
$orderBy = "id DESC"; // Orden por defecto
if ($sort == 'price_desc') $orderBy = "precio DESC";
if ($sort == 'price_asc') $orderBy = "precio ASC";

// 3. Tu consulta SQL original, pero con el ORDER BY al final
$sql = "SELECT * FROM donjorgito1 
        WHERE producto LIKE '%$searchTermEscaped%' 
        OR caracteristica LIKE '%$searchTermEscaped%' 
        OR ca LIKE '%$searchTermEscaped%' 
        OR id LIKE '%$searchTermEscaped%' 
        OR marca LIKE '%$searchTermEscaped%'
        ORDER BY $orderBy"; // <--- Esta es la clave

    // Puedes añadir una línea de depuración temporal para ver la consulta SQL
    // echo "Consulta SQL generada: " . $sql . "<br>";

    // 4. Ejecutar la consulta
    $result = mysqli_query($conn, $sql);

    // Manejo de errores en la consulta (importante para depuración)
    if (!$result) {
        die("Error en la consulta SQL: " . mysqli_error($conn));
    }

    // Comprobar si se encontraron resultados
    if (mysqli_num_rows($result) > 0) {
        $resultsFound = true;
    }

} else {
    // Si no se proporcionó ningún término de búsqueda válido
    $searchTerm = ''; // Asegurarse de que searchTerm esté vacío si no hay búsqueda
}

// 5. Mostrar los resultados en HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de búsqueda<?php echo !empty($searchTerm) ? ' para "' . $searchTerm . '"' : ''; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styleme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="assets/css/buscar.css?v=1730030000">
    <link rel="stylesheet" type="text/css" href="assets/css/estilosbuscar.css"> 
    <link rel="stylesheet" type="text/css" href="assets/css/estilosindex.css">
    <link rel="stylesheet" type="text/css" href="assets/css/mobile.css">
    

    
      

    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
      rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
</head>
<body>
<?php include 'includes/header.php'; ?>

<main class="container">
    <?php if (!empty($searchTerm)): ?>
        <h1 class="search-results-heading">Resultados para "<span><?php echo htmlspecialchars($searchTerm); ?></span>"</h1>
    <?php else: ?>
        <h1 class="search-results-heading">Busca tus productos favoritos</h1>
    <?php endif; ?>
    

     <div class="filter-bar-g502">
    <div class="filter-info">
        <i class="fas fa-sliders-h"></i> 
        <span><?php echo ($resultsFound) ? mysqli_num_rows($result) : 0; ?> productos encontrados</span>
    </div>
    
    <div class="filter-actions">
        <form action="buscar.php" method="GET" id="filterForm">
            <input type="hidden" name="query" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <div class="select-wrapper">
                <select name="sort" onchange="this.form.submit()">
                    <option value="default">Ordenar por: Relevancia</option>
                    <option value="price_desc" <?php if($sort == 'price_desc') echo 'selected'; ?>>Precio: Mayor a Menor</option>
                    <option value="price_asc" <?php if($sort == 'price_asc') echo 'selected'; ?>>Precio: Menor a Mayor</option>
                </select>
                <i class="fas fa-chevron-down"></i>
            </div>
        </form>
    </div>
</div> 

    <div class="products-grid">
        <?php
        if (!empty($searchTerm)) {
            if ($resultsFound) {
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="product-card" style="position: relative;">
                        <button class="btn-fav-toggle" 
                                data-id="<?php echo $row['id']; ?>" 
                                style="position: absolute; top: 10px; right: 10px; z-index: 10; border: none; background: white; border-radius: 50%; width: 35px; height: 35px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <i class="far fa-heart" style="color: #ff4757;"></i>
                        </button>

                        <?php if (!empty($row['imagen'])): ?>
                            <a href="producto_detalle.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="product-image-link">
                                <img src="../Donjorgitofinal/<?php echo htmlspecialchars($row['imagen']); ?>" alt="<?php echo htmlspecialchars($row['producto']); ?>" class="product-image">
                            </a>
                        <?php else: ?>
                            <a href="producto_detalle.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="product-image-link">
                                <div class="no-image-placeholder"><i class="fas fa-image"></i> Sin Imagen</div>
                            </a>
                        <?php endif; ?>

                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="producto_detalle.php?id=<?php echo htmlspecialchars($row['id']); ?>">
                                    <?php echo htmlspecialchars($row['producto']); ?>
                                </a>
                            </h3>
                            <p class="product-category"><?php echo htmlspecialchars($row['caracteristica']); ?></p>

                            <?php if ($is_logged_in): ?>
                                <p class="product-price">$<?php echo number_format($row['value_final'], 0, ',', '.'); ?></p>
                            <?php endif; ?>

                            <div class="quantity-control">
                                <button type="button" class="quantity-btn decrease-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">-</button>
                                <input type="number" class="quantity-input" value="1" min="1" data-id="<?php echo htmlspecialchars($row['id']); ?>" id="quantity-<?php echo htmlspecialchars($row['id']); ?>">
                                <button type="button" class="quantity-btn increase-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">+</button>
                            </div>

                            <button class="add-to-cart-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">
                                <i class="fas fa-shopping-cart"></i> 
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='no-results'><p><i class='fas fa-exclamation-circle'></i> Lo sentimos, no encontramos resultados para \"<strong>" . htmlspecialchars($searchTerm) . "</strong>\".</p><p>Prueba con un término de búsqueda diferente.</p></div>";
            }
        } else {
            echo "<div class='no-results'><p><i class='fas fa-info-circle'></i> Usa la barra de búsqueda superior para encontrar tus productos.</p></div>";
        }
        ?>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Aumentar la cantidad
        const increaseButtons = document.querySelectorAll('.increase-btn');
        increaseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                const quantityInput = document.querySelector(`#quantity-${productId}`);
                let currentQuantity = parseInt(quantityInput.value);
                quantityInput.value = currentQuantity + 1;
            });
        });

        // Disminuir la cantidad
        const decreaseButtons = document.querySelectorAll('.decrease-btn');
        decreaseButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                const quantityInput = document.querySelector(`#quantity-${productId}`);
                let currentQuantity = parseInt(quantityInput.value);
                if (currentQuantity > 1) {
                    quantityInput.value = currentQuantity - 1;
                }
            });
        });
    });
</script>


<?php include 'includes/footer.php'; ?>

    <script src="../config/carrito.js"></script>

    <?php
    // 6. Cerrar la conexión a la base de datos
    mysqli_close($conn);
    ?>
    <script>
document.querySelectorAll('.btn-fav-toggle').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault(); // Evita cualquier salto de página
        
        const productId = this.getAttribute('data-id');
        const icon = this.querySelector('i');
        const btn = this;

        // Enviamos la información mediante FormData
        const formData = new FormData();
        formData.append('producto_id', productId);

        fetch('logica_favorito.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (data === 'added') {
                icon.classList.replace('far', 'fas'); // Corazón lleno
                btn.style.transform = "scale(1.2)";
                setTimeout(() => btn.style.transform = "scale(1)", 200);
            } else if (data === 'removed') {
                icon.classList.replace('fas', 'far'); // Corazón vacío
            } else if (data === 'error_auth') {
                alert("Por favor, inicia sesión para guardar favoritos.");
                window.location.href = 'login.php';
            }
        })
        .catch(error => console.error('Error:', error));
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Código de inicialización del carrusel "Productos Más Vendidos"
        const productsCarousel = new Swiper('.products-carousel', {
            // Parámetros esenciales de Swiper para ver múltiples slides
            slidesPerView: 1, 
            spaceBetween: 20, 
            loop: true, 

            // Breakpoints responsivos
            breakpoints: {
                576: { slidesPerView: 2, spaceBetween: 30, },
                768: { slidesPerView: 3, spaceBetween: 40, },
                992: { slidesPerView: 4, spaceBetween: 50, },
                1200: { slidesPerView: 5, spaceBetween: 60, },
            },

            // Flechas de navegación
            navigation: {
                nextEl: '.products-carousel-next',
                prevEl: '.products-carousel-prev',
            },

            // ... (Paginación comentada)
        });
    });
</script>
<script src="js/jquery-1.11.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/jarallax@2.1.3/dist/jarallax.min.js"></script> 

<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"></script>

<script src="js/plugins.js"></script>
<script src="js/script.js"></script>

<script src="assets/js/imagenesmove.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" 
    integrity="sha256-/JqT3SQfawR5GIIeSjXgLqj1qF6tH5A5Uf1oG3H9v2Q=" 
    crossorigin="anonymous"></script> 

<script src="assets/js/search-ia.js"></script>
<script>
// Supongamos que le pones id="boton-menu" a tu botón de 3 rayitas
document.getElementById('boton-menu').addEventListener('click', function() {
    // Esto quita o pone la clase 'abierto' a tu nav
    document.querySelector('.secondary-nav').classList.toggle('abierto');
});
</script>

</body>
</html>