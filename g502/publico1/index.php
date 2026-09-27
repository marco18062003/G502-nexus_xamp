<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$user_name = '';
if ($is_logged_in) {
    $user_name = htmlspecialchars($_SESSION['nombre_usuario']);
}

require_once '../config/db.php';

/* ==========================================================
   Consultas a la Base de Datos
   ========================================================== */

// --- Productos nuevos ---
$productos_en_tendencia = [];
if ($conn) {
    $sql_tendencia = "SELECT id, producto, imagen, precio FROM donjorgito1 ORDER BY id DESC LIMIT 10";
    $result_tendencia = mysqli_query($conn, $sql_tendencia);
    if ($result_tendencia) {
        while ($row = mysqli_fetch_assoc($result_tendencia)) {
            $productos_en_tendencia[] = $row;
        }
        mysqli_free_result($result_tendencia);
    }
}

// --- Categorías únicas ---
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

// --- Marcas únicas ---
$marcas_unicas = [];
if ($conn) {
    $sql_marcas_unicas = "SELECT DISTINCT marca FROM donjorgito1 WHERE marca IS NOT NULL AND marca != '' ORDER BY marca ASC";
    $resultado_marcas_unicas = mysqli_query($conn, $sql_marcas_unicas);
    if ($resultado_marcas_unicas) {
        while ($fila = mysqli_fetch_assoc($resultado_marcas_unicas)) {
            $marcas_unicas[] = htmlspecialchars($fila['marca']);
        }
        mysqli_free_result($resultado_marcas_unicas);
    }
}

// --- Producto destacado + minis ---
$es_tendencia = null;
$productos_mini = [];
if ($conn) {
    $sql_principal = "SELECT id, producto, precio, imagen, marca, ca FROM donjorgito1 WHERE es_tendencia = 2 LIMIT 1";
    $resultado_principal = mysqli_query($conn, $sql_principal);
    if ($resultado_principal && mysqli_num_rows($resultado_principal) > 0) {
        $es_tendencia = mysqli_fetch_assoc($resultado_principal);
        mysqli_free_result($resultado_principal);
    }

    $sql_mini = "SELECT id, producto, precio, imagen, marca, ca FROM donjorgito1 WHERE es_tendencia = 1 LIMIT 9";
    $resultado_mini = mysqli_query($conn, $sql_mini);
    if ($resultado_mini) {
        while ($fila = mysqli_fetch_assoc($resultado_mini)) {
            $productos_mini[] = $fila;
        }
        mysqli_free_result($resultado_mini);
    }
}

// --- Más vendidos ---
$productos_mas_vendidos = [];
if ($conn) {
    $sql_vendidos = "SELECT id, producto, precio, imagen, marca FROM donjorgito1 WHERE es_tendencia = 1 ORDER BY RAND() LIMIT 20";
    $resultado_vendidos = mysqli_query($conn, $sql_vendidos);
    if ($resultado_vendidos) {
        while ($fila = mysqli_fetch_assoc($resultado_vendidos)) {
            $productos_mas_vendidos[] = $fila;
        }
        mysqli_free_result($resultado_vendidos);
    }
}

// --- Slides del hero ---
$base_carousel_image_path = '../images/';
$carousel_slides = [
    [
        'image' => $base_carousel_image_path . 'slide.jpg',
        'eyebrow' => 'Promociones de temporada',
        'title' => 'El buen trago empieza aquí',
        'subtitle' => 'Los mejores licores con descuentos increíbles.',
        'button_text' => 'Ver ofertas',
        'button_link' => 'buscar.php?query=promociones',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-1.jpg',
        'eyebrow' => 'Selección de la casa',
        'title' => 'Productos exclusivos',
        'subtitle' => 'Una selección única para paladares exigentes.',
        'button_text' => 'Explorar',
        'button_link' => 'productos.php',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-3.jpg',
        'eyebrow' => 'A tu puerta',
        'title' => 'Envío rápido y seguro',
        'subtitle' => 'Disfruta sin salir de casa.',
        'button_text' => 'Más información',
        'button_link' => 'ayuda.php#envio',
    ],
    [
        'image' => $base_carousel_image_path . 'slide-4.jpg',
        'eyebrow' => 'Solo para nuevos clientes',
        'title' => 'Regístrate y obtén 10% off',
        'subtitle' => 'No te pierdas nuestras ofertas exclusivas.',
        'button_text' => 'Registrarse',
        'button_link' => 'registro.php',
    ],
];

$iconos_categoria = [
    'cerveza' => 'fa-beer', 'aguardiente' => 'fa-wine-bottle', 'ron' => 'fa-martini-glass',
    'whisky' => 'fa-whiskey-glass', 'vinos' => 'fa-wine-glass', 'cigarrillos' => 'fa-smoking',
    'comestibles' => 'fa-cookie-bite', 'bebidas' => 'fa-bottle-water', 'cremas' => 'fa-mug-hot',
];
function dj_icono_categoria($nombre, $mapa) {
    $key = strtolower($nombre);
    return $mapa[$key] ?? 'fa-tag';
}
?>
<!DOCTYPE html>
<?php include 'includes/header.php'; ?>
<html lang="es">
<head>
  <title>DON JORGITO — Licorería</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="author" content="Marco">
  <meta name="keywords" content="Pagina de Licores">
  <meta name="description" content="Pagina de Licores">
  <link rel="icon" href="assets/icon1.png" type="image/jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Work+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" type="text/css" href="assets/css/estilosindex.css?v=1">
  <link rel="stylesheet" type="text/css" href="assets/css/mobile.css?v=1">
</head>

<body>
<!-- Menú lateral móvil (drawer) -->
<div class="dj-drawer" id="dj-drawer">
  <div class="dj-drawer-backdrop" id="dj-drawer-backdrop"></div>
  <nav class="dj-drawer-panel">
    <button class="dj-drawer-close" id="dj-drawer-close" aria-label="Cerrar menú"><i class="fas fa-xmark"></i></button>
    <div class="dj-select-wrap">
      <form action="buscar.php" method="GET">
        <select class="dj-select" name="query" onchange="this.form.submit()">
          <option selected value="all">Categorías</option>
          <?php foreach ($categorias_unicas as $cat): ?>
            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
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


<!-- ============ HERO ============ -->
<section class="dj-hero hero-demo-bg" id="dj-hero">
  <?php foreach ($carousel_slides as $i => $slide): ?>
    <div class="dj-hero-slide<?php echo $i === 0 ? ' is-active' : ''; ?>" style="background-image:url('<?php echo htmlspecialchars($slide['image']); ?>');">
      <div class="dj-hero-bg" style="background-image:url('<?php echo htmlspecialchars($slide['image']); ?>');"></div>
    </div>
  <?php endforeach; ?>

  <div class="dj-container dj-hero-content">
    <p class="dj-eyebrow" id="dj-hero-eyebrow"><?php echo htmlspecialchars($carousel_slides[0]['eyebrow']); ?></p>
    <h1 id="dj-hero-title"><?php echo htmlspecialchars($carousel_slides[0]['title']); ?></h1>
    <p id="dj-hero-subtitle"><?php echo htmlspecialchars($carousel_slides[0]['subtitle']); ?></p>
    <div style="display:flex; gap:1rem; flex-wrap:wrap;">
      <a class="dj-btn dj-btn--gold" id="dj-hero-btn" href="<?php echo htmlspecialchars($carousel_slides[0]['button_link']); ?>"><?php echo htmlspecialchars($carousel_slides[0]['button_text']); ?></a>
      <a class="dj-btn dj-btn--ghost" href="buscar.php?query=all">Explorar catálogo</a>
    </div>
  </div>

  <div class="dj-hero-dots" id="dj-hero-dots">
    <?php foreach ($carousel_slides as $i => $slide): ?>
      <button data-index="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'is-active' : ''; ?>" aria-label="Ir al slide <?php echo $i + 1; ?>"></button>
    <?php endforeach; ?>
  </div>
</section>

<!-- ============ FRANJA DE BENEFICIOS ============ -->
<div class="dj-container" style="margin-top:-1px;">
  <div class="dj-perks">
    <div class="dj-perk">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      <h5>Recoger en tienda</h5>
      <p>Listo en 30 minutos, sin costo.</p>
    </div>
    <div class="dj-perk">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 3h13v13H3zM16 8h4l3 3v5h-7z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>
      <h5>Envío rápido y seguro</h5>
      <p>Entrega garantizada el mismo día.</p>
    </div>
    <div class="dj-perk">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/></svg>
      <h5>Garantía y cambios</h5>
      <p>Devoluciones fáciles y gratuitas.</p>
    </div>
    <div class="dj-perk">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M2 10h20"/></svg>
      <h5>Nequi y Daviplata</h5>
      <p>Aceptamos todos los medios de pago.</p>
    </div>
  </div>
</div>

<!-- ============ CATEGORÍAS ============ -->
<section class="dj-section">
  <div class="dj-container">
    <h3 class="dj-section-title"><span>Explora</span>Por categoría</h3>
    <?php if (!empty($categorias_unicas)): ?>
      <div class="dj-scroller">
        <?php foreach ($categorias_unicas as $nombre_categoria): ?>
          <a href="buscar.php?query=<?php echo urlencode($nombre_categoria); ?>" class="dj-circle" title="Ver productos de <?php echo $nombre_categoria; ?>">
            <div class="dj-circle-frame"><i class="fas <?php echo dj_icono_categoria($nombre_categoria, $iconos_categoria); ?>"></i></div>
            <span><?php echo $nombre_categoria; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="dj-text-center" style="color:var(--muted);">Aún no hay categorías configuradas.</p>
    <?php endif; ?>
  </div>
</section>

<!-- ============ MARCAS ============ -->
<section class="dj-section dj-section--panel">
  <div class="dj-container">
    <h3 class="dj-section-title"><span>Catásssssssssssssssssslogo</span>Marcas</h3>
    <?php if (!empty($marcas_unicas)): ?>
      <div class="dj-scroller">
        <?php foreach ($marcas_unicas as $nombre_marca): ?>
          <a href="buscar.php?query=<?php echo urlencode($nombre_marca); ?>" class="dj-circle" title="Ver productos de <?php echo $nombre_marca; ?>">
            <div class="dj-circle-frame"><i class="fas fa-store"></i></div>
            <span><?php echo $nombre_marca; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="dj-text-center" style="color:var(--muted);">Aún no hay marcas configuradas.</p>
    <?php endif; ?>
  </div>
</section>

<div class="dj-container"><div class="dj-divider"></div></div>

<!-- ============ OFERTAS DESTACADAS ============ -->
<section class="dj-section">
  <div class="dj-container">
    <h3 class="dj-section-title"><span>Selección de la casa</span>Ofertas exclusivas</h3>

    <?php if ($es_tendencia && !empty($productos_mini)): ?>
      <div style="display:grid; grid-template-columns: 1.1fr 1fr; gap: var(--space-md);" class="dj-ofertas-grid">
        <a href="buscar.php?query=<?php echo htmlspecialchars($es_tendencia['id']); ?>" class="dj-feature-main" title="Ver oferta destacada">
          <div class="dj-seal"><span class="dj-seal-text">Más<br>vendido</span></div>
          <img src="../Donjorgitofinal/<?php echo htmlspecialchars($es_tendencia['imagen']); ?>" alt="<?php echo htmlspecialchars($es_tendencia['producto']); ?>">
          <div class="dj-feature-info">
            <p class="dj-eyebrow"><?php echo htmlspecialchars($es_tendencia['ca']); ?></p>
            <h3><?php echo htmlspecialchars($es_tendencia['producto']); ?></h3>
          </div>
        </a>

        <div class="dj-mini-grid">
          <?php $contador_mini = 0; foreach ($productos_mini as $producto): $contador_mini++; if ($contador_mini > 9) break; ?>
            <a href="buscar.php?query=<?php echo htmlspecialchars($producto['id']); ?>" class="dj-mini-item" title="<?php echo htmlspecialchars($producto['producto']); ?>">
              <img src="../Donjorgitofinal/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['producto']); ?>">
              <div class="dj-mini-info">
                <small><?php echo htmlspecialchars($producto['marca']); ?></small>
                <p><?php echo htmlspecialchars($producto['producto']); ?></p>
                <span>$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></span>
              </div>
            </a>
          <?php endforeach; ?>
          <?php $faltantes = 9 - count($productos_mini); for ($i = 0; $i < $faltantes; $i++): ?>
            <div class="dj-mini-item" style="display:flex; align-items:center; justify-content:center; color:var(--muted); font-size:.75rem; text-align:center; padding:.5rem;">
              Próximamente más productos
            </div>
          <?php endfor; ?>
        </div>
      </div>
    <?php else: ?>
      <p class="dj-text-center" style="color:var(--muted);">Aún no hay ofertas completas. Marca un producto con <code>es_tendencia = 2</code> y al menos uno con <code>es_tendencia = 1</code>.</p>
    <?php endif; ?>
  </div>
</section>

<!-- ============ BANNERS ============ -->
<section class="dj-section" style="padding-top:0;">
  <div class="dj-container dj-banner-grid" style="display:grid; grid-template-columns:1fr 1fr; gap: var(--space-md);">
    <div class="dj-banner">
      <div class="dj-banner-content">
        <p class="dj-eyebrow">Hasta 25% off</p>
        <h3>RED BULL</h3>
        <a class="dj-btn dj-btn--gold" href="buscar.php?query=red+bull">Mostrar</a>
      </div>
      <img src="../images/imagenespresentacion/9002490100490_31-.png" alt="Club Colombia" class="dj-banner-img">
    </div>
    <div class="dj-banner">
      <div class="dj-banner-content">
        <p class="dj-eyebrow">Hasta 25% off</p>
        <h3>Águila</h3>
        <a class="dj-btn dj-btn--gold" href="buscar.php?query=aguila">Mostrar</a>
      </div>
      <img src="../images/imagenespresentacion/x2.png" alt="Águila" class="dj-banner-img">
    </div>
  </div>
</section>

<!-- ============ MÁS VENDIDOS ============ -->
<section class="dj-section dj-section--panel">
  <div class="dj-container">
    <h3 class="dj-section-title"><span>Lo que todos piden</span>Más vendidos</h3>
    <?php if (!empty($productos_mas_vendidos)): ?>
      <div class="dj-scroller">
        <?php foreach ($productos_mas_vendidos as $producto): ?>
          <a href="buscar.php?query=<?php echo htmlspecialchars($producto['id']); ?>" class="dj-card" title="<?php echo htmlspecialchars($producto['producto']); ?>">
            <figure>
              <img src="../Donjorgitofinal/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['producto']); ?>">
            </figure>
            <div class="dj-card-body">
              <p class="dj-card-brand"><?php echo htmlspecialchars($producto['marca']); ?></p>
              <p class="dj-card-name"><?php echo htmlspecialchars($producto['producto']); ?></p>
              <p class="dj-card-price">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="dj-text-center" style="color:var(--muted);">Aún no hay productos marcados como "Más vendidos".</p>
    <?php endif; ?>
  </div>
</section>

<!-- ============ BANNERS 2 ============ -->
<section class="dj-section" style="padding-top:0;">
  <div class="dj-container dj-banner-grid" style="display:grid; grid-template-columns:1fr 1fr; gap: var(--space-md);">
    <div class="dj-banner">
      <div class="dj-banner-content">
        <p class="dj-eyebrow">Hasta 25% off</p>
        <h3>Club Colombia</h3>
        <a class="dj-btn dj-btn--gold" href="buscar.php?query=club+colombia">Mostrar</a>
      </div>
      <img src="../images/imagenespresentacion/504921_Clun_negra-removebg-preview.png" alt="Club Colombia" class="dj-banner-img">
    </div>
    <div class="dj-banner">
      <div class="dj-banner-content">
        <p class="dj-eyebrow">Hasta 25% off</p>
        <h3>Águila</h3>
        <a class="dj-btn dj-btn--gold" href="buscar.php?query=aguila">Mostrar</a>
      </div>
      <img src="../images/imagenespresentacion/x2.png" alt="Águila" class="dj-banner-img">
    </div>
  </div>
</section>

<!-- ============ PRODUCTOS NUEVOS ============ -->
<section class="dj-section dj-section--panel">
  <div class="dj-container">
    <h3 class="dj-section-title"><span>Recién llegado</span>Productos nuevos</h3>
    <?php if (!empty($productos_en_tendencia)): ?>
      <div class="dj-scroller">
        <?php foreach ($productos_en_tendencia as $producto): ?>
          <a href="buscar.php?query=<?php echo $producto['id']; ?>" class="dj-card" title="<?php echo htmlspecialchars($producto['producto']); ?>">
            <figure>
              <?php if (!empty($producto['imagen'])): ?>
                <img src="../Donjorgitofinal/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['producto']); ?>" loading="lazy">
              <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--muted);">
                  <i class="fas fa-image fa-2x"></i>
                </div>
              <?php endif; ?>
            </figure>
            <div class="dj-card-body">
              <p class="dj-card-name"><?php echo htmlspecialchars($producto['producto']); ?></p>
              <?php if ($is_logged_in): ?>
                <p class="dj-card-price">$<?php echo number_format($producto['precio'], 0, ',', '.'); ?></p>
              <?php else: ?>
                <a href="login.php" class="dj-btn dj-btn--ghost" style="padding:.5rem 1rem; font-size:.65rem; width:100%; justify-content:center;">Ver precio</a>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="dj-text-center" style="color:var(--muted);">Próximamente nuevos productos para ti.</p>
    <?php endif; ?>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="dj-footer">
  <div class="dj-container">
    <div class="dj-footer-grid">
      <div>
        <h5>Don Jorgito</h5>
        <p style="max-width:320px;">Tu licorería de confianza. Disfruta responsablemente.</p>
        <ul style="margin-top:1rem;">
          <li><i class="fas fa-map-marker-alt" style="margin-right:.5rem;"></i>Dirección ####</li>
          <li><i class="fas fa-phone" style="margin-right:.5rem;"></i>+57 ####</li>
          <li><i class="fas fa-envelope" style="margin-right:.5rem;"></i>contacto@donjorgito.com</li>
        </ul>
      </div>
      <div>
        <h5>Enlaces rápidos</h5>
        <ul>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="buscar.php?query=a">Productos</a></li>
          <li><a href="weabout.php">Nuestra historia</a></li>
          <li><a href="politicas.php">Políticas</a></li>
        </ul>
      </div>
      <div>
        <h5>Síguenos</h5>
        <div class="dj-social">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        </div>
      </div>
    </div>
    <div class="dj-footer-bottom">&copy; <?php echo date('Y'); ?> Don Jorgito. Todos los derechos reservados.</div>
  </div>
</footer>

<script src="assets/js/imagenesmove.js" defer></script>
<script src="assets/js/search-ia.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // ---- Menú lateral móvil ----
  const drawer = document.getElementById('dj-drawer');
  const openBtn = document.getElementById('dj-menu-toggle');
  const closeBtn = document.getElementById('dj-drawer-close');
  const backdrop = document.getElementById('dj-drawer-backdrop');
  const openDrawer = () => drawer.classList.add('is-open');
  const closeDrawer = () => drawer.classList.remove('is-open');
  openBtn?.addEventListener('click', openDrawer);
  closeBtn?.addEventListener('click', closeDrawer);
  backdrop?.addEventListener('click', closeDrawer);

  // ---- Hero carousel ----
  const slides = document.querySelectorAll('#dj-hero .dj-hero-slide');
  const dots = document.querySelectorAll('#dj-hero-dots button');
  const eyebrow = document.getElementById('dj-hero-eyebrow');
  const title = document.getElementById('dj-hero-title');
  const subtitle = document.getElementById('dj-hero-subtitle');
  const btn = document.getElementById('dj-hero-btn');

  const slideData = <?php echo json_encode(array_map(function ($s) {
      return [
          'eyebrow' => $s['eyebrow'], 'title' => $s['title'],
          'subtitle' => $s['subtitle'], 'button_text' => $s['button_text'],
          'button_link' => $s['button_link'],
      ];
  }, $carousel_slides), JSON_UNESCAPED_UNICODE); ?>;

  let current = 0;
  function goToSlide(i) {
    slides.forEach(s => s.classList.remove('is-active'));
    dots.forEach(d => d.classList.remove('is-active'));
    slides[i]?.classList.add('is-active');
    dots[i]?.classList.add('is-active');
    const data = slideData[i];
    if (data) {
      eyebrow.textContent = data.eyebrow;
      title.textContent = data.title;
      subtitle.textContent = data.subtitle;
      btn.textContent = data.button_text;
      btn.href = data.button_link;
    }
    current = i;
  }
  dots.forEach(d => d.addEventListener('click', () => goToSlide(parseInt(d.dataset.index, 10))));
  if (slides.length > 1) {
    setInterval(() => goToSlide((current + 1) % slides.length), 6000);
  }
});
</script>

</body>
</html>