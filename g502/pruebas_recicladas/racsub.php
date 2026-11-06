<?php
// buscar.php - Lógica para procesar la búsqueda y mostrar resultados

// Activar la visualización de errores de PHP para depuración (¡QUÍTALA en producción!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Incluir el archivo de conexión a la base de datos
// Asegúrate de que 'db.php' esté en la misma carpeta o ajusta la ruta.
require_once 'db.php'; 

// Variable para el término de búsqueda
$searchTerm = '';
$resultsFound = false;
$result = null; // Inicializamos $result a null para evitar posibles errores

// 2. Obtener el término de búsqueda de la URL
if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $searchTerm = htmlspecialchars(trim($_GET['query'])); // Limpia la entrada para HTML
    
    // Escapar el término de búsqueda para seguridad SQL (PREVENIR INYECCIONES SQL)
    // Es crucial usar mysqli_real_escape_string con la conexión correcta.
    $searchTermEscaped = mysqli_real_escape_string($conn, $searchTerm);

    // 3. Construir la consulta SQL
    // Conectando las condiciones LIKE con OR
    $sql = "SELECT * FROM donjorgito1 WHERE producto LIKE '%$searchTermEscaped%' OR caracteristica LIKE '%$searchTermEscaped%' OR ca LIKE '%$searchTermEscaped%'";

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
    <link rel="stylesheet" href="estilosbuscar.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="styleme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="styleme.css">
    <link
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
      rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        .quantity-control {
            display: flex;
            align-items: center;
            margin-bottom: 10px; /* Espacio entre el control y el botón de añadir */
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden; /* Para que los bordes redondeados se apliquen bien */
            width: fit-content; /* Ajustar al contenido */
        }

        .quantity-btn {
            background-color: #f0f0f0;
            border: none;
            padding: 8px 12px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: #e0e0e0;
        }

        .quantity-input {
            width: 50px; /* Ancho para el input de cantidad */
            text-align: center;
            border: none;
            padding: 8px 0; /* Padding vertical, sin horizontal */
            -moz-appearance: textfield; /* Oculta flechas en Firefox */
        }

        /* Oculta las flechas de los inputs tipo number en WebKit (Chrome, Safari) */
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .add-to-cart-btn {
            background-color: #28a745; /* Color verde para el botón de añadir */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            width: 100%; /* Para que ocupe todo el ancho disponible */
            margin-top: 5px; /* Espacio por si necesitas entre el control y el botón */
        }

        .add-to-cart-btn:hover {
            background-color: #218838;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .no-results p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 10px;
        }

        .no-results .fas {
            font-size: 2em;
            color: #007bff;
            margin-bottom: 15px;
        }
        
        .search-results-heading {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 30px;
            color: #333;
            font-size: 2.2em;
        }

        .search-results-heading span {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="main-header">
    <div class="header-content">
        <div class="header-logo">
            <a href="index.php">
               <img src="images/logo1.png" alt="Tu Logo" width="200" height="100">
            </a>
        </div>

        <div class="header-slogan" sty>
            <span> ;) </span>
        </div>

      <div class="header-dropdown-menu">
        <form id="categoryForm" action="buscar.php" method="GET">
         <select class="category-select" name="query" aria-label="Seleccionar categoría" style="background-color:#232f3e;color:#f8f8f8" onchange="this.form.submit()">
            <option selected value="all">Categorías</option>
            <option value="cerveza">Cerveza</option>
            <option value="aguardiente">Aguardiente</option>
            <option value="ron">Ron</option>
            <option value="whisky">Whisky</option>
            <option value="vinos">Vinos</option>
            <option value="cremas">Cremas de whisky</option>
            <option value="cigarrillos">Cigarrillos</option>
            <option value="comestibles">Comestibles</option>
            <option value="otros">Otros</option>
          </select>
        </form>
      </div>

        <div class="header-search">
          <form action="buscar.php" method="GET">
             <div class="search-container">
            <input type="text" placeholder="Buscar productos..." name="query" class="search-input">
            <button type="submit" class="search-icon">
                <i class="fas fa-search"></i> </button>
             </div>
          </form>
        </div>


        <div class="header-user-actions" >
            <a href="login.php" class="action-item" style="color:#333" aria-label="Mi Cuenta">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user" style="color:#333">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12" cy="7" r="4"></circle>
    </svg>
    <span>Cuenta</span>
</a>
            <a href="donjorgito.php" class="action-item" aria-label="Mis Favoritos" style="color:#333">
                <svg xmlns="http://www.w3.org/2000/svg" style="color:#333" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                <span>Favoritos</span>
            </a>
            <a href="ver_carrito.php" class="action-item cart-item" aria-label="Ver Carrito" style="color:#333">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" style="color:#333" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-cart">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-count">0</span>
                <span>Carrito</span>
            </a>
        </div>
    </div>

    <nav class="secondary-nav" style="text-align: center">
    <ul>
        <li><a href="buscar.php?query=cerveza">Cerveza</a></li>
        <li><a href="buscar.php?query=aguardiente">Aguardiente</a></li>
        <li><a href="buscar.php?query=ron">Ron</a></li>
        <li><a href="buscar.php?query=whisky">Whisky</a></li>
        <li><a href="buscar.php?query=vinos">Vinos</a></li>
        <li><a href="buscar.php?query=cremas">Cremas</a></li>
        <li><a href="buscar.php?query=cigarrillos">Cigarrillos</a></li>
        <li><a href="buscar.php?query=comestibles">Comestibles</a></li>
        <li><a href="buscar.php?query=otros">Promoxion</a></li>
        <li><a href="buscar.php?query=otros">Mas Vendidos</a></li>
        <li><a href="buscar.php?query=otros">Descuentos</a></li>
    </ul>
</nav>

</header style="borden:0px">

    <main class="container">
        <?php if (!empty($searchTerm)): ?>
            <h1 class="search-results-heading">Resultados para "<span><?php echo htmlspecialchars($searchTerm); ?></span>"</h1>
        <?php else: ?>
            <h1 class="search-results-heading">Busca tus productos favoritos</h1>
        <?php endif; ?>

        <div class="products-grid">
            <?php
            if (!empty($searchTerm)) {
                if ($resultsFound) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        ?>
                        <div class="product-card">
                            <?php if (!empty($row['imagen'])): ?>
                                <a href="producto_detalle.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="product-image-link">
                                    <img src="Donjorgitofinal/<?php echo htmlspecialchars($row['imagen']); ?>" alt="<?php echo htmlspecialchars($row['producto']); ?>" class="product-image">
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
                            <!--
                                <p class="product-price">$<?php echo number_format($row['precio'], 0, ',', '.'); ?></p>
                              -->  
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn decrease-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">-</button>
                                    <input type="number" class="quantity-input" value="1" min="1" data-id="<?php echo htmlspecialchars($row['id']); ?>" id="quantity-<?php echo htmlspecialchars($row['id']); ?>">
                                    <button type="button" class="quantity-btn increase-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">+</button>
                                </div>
                                <button class="add-to-cart-btn" data-id="<?php echo htmlspecialchars($row['id']); ?>">
                                    <i class="fas fa-shopping-cart"></i> Agregar al carrito
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

    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Tu Tienda. Todos los derechos reservados.</p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="carrito.js"></script>

    <?php
    // 6. Cerrar la conexión a la base de datos
    mysqli_close($conn);
    ?>
</body>
</html>