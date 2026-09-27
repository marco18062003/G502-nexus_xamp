<?php
$categorias_unicas = [];
if ($conn) {
    $sql_categorias_unicas = "SELECT DISTINCT ca FROM donjorgito1 WHERE ca IS NOT NULL AND ca != '' ORDER BY ca ASC";
    $resultado_categorias_unicas = mysqli_query($conn, $sql_categorias_unicas);
    if ($resultado_categorias_unicas) {
        while ($fila = mysqli_fetch_assoc($resultado_categorias_unicas)) {
            $categorias_unicas[] = htmlspecialchars($fila['ca']);
        }
        mysqli_free_result($resultado_categorias_unicas);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

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
    <link rel="stylesheet" type="text/css" 
      href="assets/css/style.css?v=1730030000">
     

    <link rel="stylesheet" type="text/css" href="assets/css/estilosindex.css?v=1">
  <link rel="stylesheet" type="text/css" href="assets/css/mobile.css?v=1">
      
    <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <!--<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">-->
  
</head>

<body>
    
  <!-- ============ HEADER ÚNICO ============ -->
<header class="dj-nav" role="banner">
  <div class="dj-container">
    <div class="dj-nav-row">
      
      <!-- Brand Logo -->
      <a class="dj-brand" href="index.php" aria-label="Ir a la página de inicio">
        DON JORGITO <small>Licorería</small>
      </a>

      <!-- Buscador principal (Desktop) -->
      <div class="dj-search-bar dj-desktop-search">
        <label for="buscador-desktop" class="sr-only">Buscar productos</label>
        <i class="fas fa-search dj-search-icon" aria-hidden="true"></i>
        <form action="buscar.php" method="GET" class="dj-search-form" role="search">
          <input 
            type="search" 
            name="query" 
            id="buscador-desktop" 
            placeholder="Buscar " 
            value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>"
            autocomplete="off"
            aria-label="Buscar licores"
          >
        </form>
      </div>

      <!-- Links (Desktop) -->
      <nav class="dj-nav-links" aria-label="Navegación principal">
        <a href="brand.php">Marcas</a>
        <a href="buscar.php?query=aguardiente">Más vendidos</a>
        <a href="ofertas.php">Promociones</a>
        <a href="newproducts.php">Nuevo</a>
        <a href="weabout.php">Nosotros</a>
      </nav>

      <!-- User Actions -->
      <div class="dj-nav-actions">
        <?php if ($is_logged_in): ?>
          <a class="dj-icon-btn" href="../users/mi_panel.php" aria-label="Mi cuenta" title="¡Hola, <?php echo $user_name; ?>!">
            <i class="fas fa-user" aria-hidden="true"></i>
          </a>
        <?php else: ?>
          <a class="dj-icon-btn" href="login.php" aria-label="Iniciar sesión">
            <i class="fas fa-user" aria-hidden="true"></i>
          </a>
        <?php endif; ?>

        <a class="dj-icon-btn" href="favoritos.php" aria-label="Mis favoritos">
          <i class="fas fa-heart" aria-hidden="true"></i>
        </a>

        <a class="dj-icon-btn" href="ver_carrito.php" aria-label="Ver carrito de compras">
          <i class="fas fa-shopping-cart" aria-hidden="true"></i>
          <?php $cart_count = $_SESSION['cart_count'] ?? 0; ?>
          <span
            id="cart-counter"
            class="dj-badge-dot"
            style="<?php echo $cart_count > 0 ? '' : 'display:none;'; ?>"
          ><?php echo (int)$cart_count; ?></span>
        </a>

        <button 
          class="dj-hamburger" 
          id="dj-menu-toggle" 
          aria-label="Abrir menú de navegación" 
          aria-expanded="false" 
          aria-controls="dj-drawer"
        >
          <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
      </div>

    </div>

    <!-- ========================================================= -->
    <!-- ELEMENTOS EXCLUSIVOS DE MÓVIL                             -->
    <!-- ========================================================= -->

    <!-- Buscador dedicado (Móvil) -->
    <!-- Barra de búsqueda exclusiva para móvil (Diseño Premium) -->
<div class="dj-mobile-search-row dj-only-mobile">
  <form action="buscar.php" method="GET" class="dj-mobile-search-form" role="search">
    <label for="buscador-mobile" class="sr-only">Buscar en la tienda</label>
    
    <div class="search-input-wrapper">
      <i class="fas fa-search search-icon" aria-hidden="true"></i>
      <input 
        type="search" 
        name="query" 
        id="buscador-mobile"
        placeholder="buscar1" 
        value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>"
        aria-label="Buscar en la tienda"
        autocomplete="off"
      >
    </div>

    <button type="submit" class="search-btn" aria-label="Buscar">
      <span>Buscar</span>
      <i class="fas fa-arrow-right" aria-hidden="true"></i>
    </button>
  </form>
</div>

    <!-- Chips de Categoría (Móvil) -->
    

  </div>
</header>

<!-- Menú lateral móvil (drawer) -->
<div class="dj-drawer" id="dj-drawer" aria-hidden="true">
  <div class="dj-drawer-backdrop" id="dj-drawer-backdrop"></div>
  <nav class="dj-drawer-panel" aria-label="Menú móvil">
    <button class="dj-drawer-close" id="dj-drawer-close" aria-label="Cerrar menú"><i class="fas fa-xmark"></i></button>
    <div class="dj-select-wrap">
      <form action="buscar.php" method="GET">
        <select class="dj-select" name="query" onchange="this.form.submit()">
          <option selected value="all">Categorías</option>
          <?php foreach ($categorias_unicas as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
    <a href="brand.php">Marcas</a>
    <a href="scannerme/index.php">Scanner</a>
    <a href="buscar.php?query=aguardiente">Más vendidos</a>
    <a href="buscar.php?query=cigarrillos">Promociones</a>
    <a href="hire2.php">Contratar</a>
    <a href="weabout.php">Sobre nosotros</a>
    <a href="newproducts.php">Nuevo</a>
  </nav>
</div>

<!-- Contenedor de notificaciones toast (usado por carrito.js) -->
<div id="toast-container"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('dj-menu-toggle');
  const drawer    = document.getElementById('dj-drawer');
  const backdrop  = document.getElementById('dj-drawer-backdrop');
  const closeBtn  = document.getElementById('dj-drawer-close');
  let lastFocusedEl;

  if (!toggleBtn || !drawer) return;

  const openDrawer = () => {
    lastFocusedEl = document.activeElement;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    toggleBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('dj-drawer-locked');
    closeBtn?.focus();
    document.addEventListener('keydown', onKeydown);
  };

  const closeDrawer = () => {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    toggleBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('dj-drawer-locked');
    document.removeEventListener('keydown', onKeydown);
    lastFocusedEl?.focus();
  };

  const onKeydown = (e) => {
    if (e.key === 'Escape') closeDrawer();
  };

  toggleBtn.addEventListener('click', () => {
    drawer.classList.contains('is-open') ? closeDrawer() : openDrawer();
  });
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);

  // Cierra el drawer si la ventana pasa a tamaño de escritorio
  window.addEventListener('resize', () => {
    if (window.innerWidth > 900 && drawer.classList.contains('is-open')) closeDrawer();
  });
});
</script>