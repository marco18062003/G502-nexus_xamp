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
  <meta name="description" content="Pagina de Licores">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <!-- inactivos por no especificar
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
  <link rel="stylesheet" type="text/css" href="style1.css"> -->

   <link rel="stylesheet" type="text/css" href="style.css">
   <link rel="stylesheet" type="text/css" href="styleme.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap"
    rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
              
        .header-slogan {
            font-family: 'Poppins', sans-serif; /* Fuente importada */
            font-weight: 600; /* Peso de la fuente para darle mayor presencia */
            font-size: 18px; /* Tamaño de fuente */
            color: #333; /* Un color oscuro para mayor contraste */
            letter-spacing: 1px; /* Espaciado entre letras */
            text-transform: uppercase; /* Transformación de texto en mayúsculas */
            text-align: center; /* Centrar el eslogan */
            padding: 10px 0; /* Un poco de espacio arriba y abajo */
        }


    </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            <span>Encuentra los pro0uctos<br> que buscas <br>¡con la mejor calidad¡</span>
        </div>

      <div class="header-dropdown-menu">
        <form id="categoryForm" action="busc" method="GET">
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
          <form action="racsub.php" method="GET">
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
        <li><a href="racsub.php?query=cerveza">Cerveza</a></li>
        <li><a href="racsub.php?query=aguardiente">Aguardiente</a></li>
        <li><a href="racsub.php?query=ron">Ron</a></li>
        <li><a href="racsub.php?query=whisky">Whisky</a></li>
        <li><a href="racsub.php?query=vinos">Vinos</a></li>
        <li><a href="racsub.php?query=cremas">Cremas</a></li>
        <li><a href="racsub.php?query=cigarrillos">Cigarrillos</a></li>
        <li><a href="racsub.php?query=comestibles">Comestibles</a></li>
        <li><a href="racsub.php?query=otros">Promoxion</a></li>
        <li><a href="racsub.php?query=otros">Mas Vendidos</a></li>
        <li><a href="racsub.php?query=otros">Descuentos</a></li>
    </ul>
</nav>

</header style="borden:0px">

  <section>
    <div>
      <div
        class="slideshow slide-in arrow-absolute text-white" style="height: 70vh;">
        <div class="swiper-wrapper">
          
          <div class="swiper-slide jarallax swiper-slide-next">

            <img src="images/slide.jpg" class="jarallax-img" alt="slideshow">
            <div class="banner-content w-100">
              <div class="container-fluid">
                <div class="row justify-content-center text-center">
                  <div class="col-md-10 pt-5">
                    <h2 class="display-xl text-white ls-0 mt-5 pt-5 txt-fx slide-up"></h2>
                  </div>
                </div>
              </div>
            </div>
            
          </div>
         
          

          
          
        <div class="pagination-wrapper position-absolute">
          <div class="container">
            <div class="slideshow-swiper-pagination text-center"></div>
          </div>
        </div>
        <div class="icon-arrow icon-arrow-left text-white"><svg width="50" height="50" viewBox="0 0 24 24">
            <use xlink:href="#arrow-left"></use>
          </svg></div>
        <div class="icon-arrow icon-arrow-right text-white"><svg width="50" height="50" viewBox="0 0 24 24">
            <use xlink:href="#arrow-right"></use>
          </svg></div>
        
      </div>
    </div>
  </section>

  <section class="features" style="position:relative; margin-top: -100px; z-index: 2;">
    <div class="container-lg">
      <div class="bg-white p-5">
        <div class="row">
          <div class="col-md-4">
            <div class="row">
              <div class="col-2">
                <svg width="40" height="40">
                  <use xlink:href="#cart"></use>
                </svg>
              </div>
              <div class="col-10">
                <h4 class="element-title text-capitalize mb-2">RECOGER EN TIENDA</h4>
                <p></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="row">
              <div class="col-2">
                <svg width="40" height="40">
                  <use xlink:href="#gift"></use>
                </svg>
              </div>
              <div class="col-10">
                <h4 class="element-title text-capitalize mb-2">ENVIO A DOMICILIO</h4>
                <p></p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="row">
              <div class="col-2">
                <svg width="40" height="40">
                  <use xlink:href="#love"></use>
                </svg>
              </div>
              <div class="col-10">
                <h4 class="element-title text-capitalize mb-2">DEVOLUCIONES GRATIS</h4>
                <p></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-4">
    <div class="container-fluid">
      <div class="row">

        <div class="col-md-6">
          <div class="banner-ad bg-secondary-subtle mb-3"
               style="background: url('images/imagenespresentacion/x1.png');
                      background-repeat: no-repeat;
                      background-position: right bottom;
                       background-size: 295px 244px;">
           <div class="banner-content p-5">

              <div class="fs-6 pt-5">Hasta un 25% de descuento</div>
              <h3 class="banner-title">CLUB COLOMBIA</h3>
              <a href="#" class="btn btn-dark text-uppercase">MOSTRAR</a>

            </div>

          </div>
        </div>
        <div class="col-md-6">
          <div class="banner-ad bg-secondary-subtle"
            style="background: url('images/imagenespresentacion/x2.png');
                      background-repeat: no-repeat;
                      background-position: right bottom;
                       background-size: 295px 244px;">
          <div class="banner-content p-5">

              <div class="fs-6 pt-5">Hasta un 25% de descuento</div>
              <h3 class="banner-title">AGUILA</h3>
              <a href="#" class="btn btn-dark text-uppercase">MOSTRAR</a>

            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container-fluid">

      <div class="row">
        <div class="col-md-12">

          <h3>Productos en tendencia</h3>

          <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">

            <div class="col">
              <div class="product-item">
                <span class="badge bg-success position-absolute m-3">-16%</span>
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y1.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Bebida Energizante MONSTER Mango loco (473ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$8.560</span><del><!--aqui--></del><span class="text-success">  -16%</span></p>
                  <span class="d-flex">
                    
                  </span>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <span class="badge bg-success position-absolute m-3">-30%</span>
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y2.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Aguardiente Antioqueño azul (750ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$53.100</span><del></del><span class="text-success">-30%</span></p>
                  
                  <!--ILUSTRACION DE ESTRELLAS
                  <span class="d-flex">
                    <svg width="18" height="18" class="text-warning">
                      <use xlink:href="#star-solid"></use>
                    </svg>
                    <svg width="18" height="18" class="text-warning">
                      <use xlink:href="#star-solid"></use>
                    </svg>
                    <svg width="18" height="18" class="text-warning">
                      <use xlink:href="#star-solid"></use>
                    </svg>
                    <svg width="18" height="18" class="text-warning">
                      <use xlink:href="#star-solid"></use>
                    </svg>
                    <svg width="18" height="18" class="text-warning">
                      <use xlink:href="#star-solid"></use>
                    </svg>
                  </span>-->
                  
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <span class="badge bg-success position-absolute m-3">-6%</span>
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y3.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Whisky BUCHANAN S deluxe 12 Años (750ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$169.900</span><del></del><span class="text-success">  -6%</span></p>
                  
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y4.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>CASILLERO DEL DIABLO reserva Cabernet Sauvignon (750 ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$84.900</span><del></del><span class="text-success"><!--porcentaje de descuento--></span></p>
                  
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y5.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>CLUB COLOMBIA dorada botella und (330ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$3300</span><del></del><span class="text-success"></span></p>
                  
                  </span>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y6.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Coca Cola und (400ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$3.000</span><del></del><span class="text-success"></span></p>
                  
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y7.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>SIXPACK CORONITA CORONA (1260ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$19.800</span><del></del><span class="text-success"></span></p>
        
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y8.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Tequila DON JULIO blanco (700 ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$241.000</span><del></del><span class="text-success"></span></p>
                  
                  </span>
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y9.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>FOUR LOKO Sandia lata (473ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$18.000</span><del></del><span class="text-success"></span></p>
                  
                </div>
              </div>
            </div>

            <div class="col">
              <div class="product-item">
                <figure>
                  <a href="single-product.html" title="Product Title">
                    <img src="images/imagenespresentacion/y10.jpg" alt="Product Thumbnail" class="img-fluid">
                  </a>
                </figure>
                <span>Petaco de poker 30 und (330ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$60.000</span><del></del><span class="text-success"></span></p>
                  
                </div>
              </div>
            </div>

          </div>
          <!-- / product-grid -->

        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container-fluid">
      <div class="row">

        <div class="col-md-6">
          <div class="banner-ad bg-secondary-subtle mb-3"
            style="background: url('https://pngimg.com/uploads/red_bull/red_bull_PNG2.png');
                      background-repeat: no-repeat;
                      background-position: right bottom;
                       background-size: 295px 244px;"><div class="banner-content p-5">

              <div class="fs-6 pt-5">Upto 25% Off</div>
              <h3 class="banner-title">RED BULL</h3>
              <a href="#" class="btn btn-dark text-uppercase">MOSTRAR</a>

            </div>

          </div>
        </div>
        <div class="col-md-6">
          <div class="banner-ad bg-secondary-subtle"
            style="background: url('images/imagenespresentacion/x4.png');
                      background-repeat: no-repeat;
                      background-position: right bottom;
                       background-size: 295px 244px;"><div class="banner-content p-5">

              <div class="fs-6 pt-5">Upto 25% Off</div>
              <h3 class="banner-title">SIX PACK</h3>
              <a href="#" class="btn btn-dark text-uppercase">MOSTRAR</a>

            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <section class="py-5 overflow-hidden">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-12">

          <div class="section-header d-flex flex-wrap justify-content-between my-5">

            <h2 class="section-title">PRODUCTOS MAS VENDIDOS</h2>

            <div class="d-flex align-items-center">
              <a href="#" class="btn-link text-decoration-none">OTROS →</a>
              <div class="swiper-buttons">
                <button class="swiper-prev products-carousel-prev btn btn-primary">❮</button>
                <button class="swiper-next products-carousel-next btn btn-primary">❯</button>
              </div>
            </div>
          </div>

        </div>
      </div>
      <div class="row">
        <div class="col-md-12">

          <div class="products-carousel swiper">
            <div class="swiper-wrapper">

              <div class="swiper-slide">
                <div class="product-item">
                  <span class="badge bg-success position-absolute m-3">-12%</span>
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y11.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Ron VIEJO DE CALDAS Tradicional botella (750ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$55.000</span><del></del><span class="text-success">  -12%</span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <span class="badge bg-success position-absolute m-3">-16%</span>
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y12.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Bebida Energizante MONSTER Zero ultra (473ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$8.560</span><del></del><span class="text-success"> -16%</span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <span class="badge bg-success position-absolute m-3">-3%</span>
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y13.png" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>CORONA botella und (330 ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$4.300</span><del></del><span class="text-success">  -3%</span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y14.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Cigarrillos MARLBORO Rojo cajetilla (20 und)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$11.900</span><del></del><span class="text-success"></span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y15.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Cerveza POKER lata (330 ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$3.330</span><del></del><span class="text-success"> </span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y10.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <span>Petaco de poker 30 und (330ml)</span>
                <div class="d-flex justify-content-between">
                  <p><span class="text-dark">$60.000</span><del></del><span class="text-success"></span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y17.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>MARGARITA Onduladas Tomate x10und 32gr (320 gr)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$2.300</span><del></del><span class="text-success"></span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y18.webp" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Pilas Eveready 2und AA</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$2.000</span><del></del><span class="text-success"></span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y19.jpg" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>whisky JOHN THOMAS Botella (750ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$56.300</span><del></del><span class="text-success"></span></p>
                    
                  </div>
                </div>
              </div>

              <div class="swiper-slide">
                <div class="product-item">
                  <figure>
                    <a href="single-product.html" title="Product Title">
                      <img src="images/imagenespresentacion/y20.webp" alt="Product Thumbnail" class="img-fluid">
                    </a>
                  </figure>
                  <p>Light Sixpack AGUILA LIGHT (330ml)</p>
                  <div class="d-flex justify-content-between">
                    <p><span class="text-dark">$17.500</span><del> </del><span class="text-success"></span></p>
                    
                    </span>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <!-- / products-carousel -->

        </div>
      </div>
    </div>
  </section>  

  <section class="py-5">
    <div class="container-fluid">
      <div class="row row-cols-1 row-cols-sm-3 row-cols-lg-5">
        <div class="col">
          <div class="card mb-3 border-0">
            <div class="row">
              <div class="col-md-2 text-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                  <path fill="currentColor"
                    d="M21.5 15a3 3 0 0 0-1.9-2.78l1.87-7a1 1 0 0 0-.18-.87A1 1 0 0 0 20.5 4H6.8l-.33-1.26A1 1 0 0 0 5.5 2h-2v2h1.23l2.48 9.26a1 1 0 0 0 1 .74H18.5a1 1 0 0 1 0 2h-13a1 1 0 0 0 0 2h1.18a3 3 0 1 0 5.64 0h2.36a3 3 0 1 0 5.82 1a2.94 2.94 0 0 0-.4-1.47A3 3 0 0 0 21.5 15Zm-3.91-3H9L7.34 6H19.2ZM9.5 20a1 1 0 1 1 1-1a1 1 0 0 1-1 1Zm8 0a1 1 0 1 1 1-1a1 1 0 0 1-1 1Z" />
                </svg>
              </div>
              <div class="col-md-10">
                <div class="card-body p-0">
                  <h5>Entrega domicilio</h5>
                  <p class="card-text"></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card mb-3 border-0">
            <div class="row">
              <div class="col-md-2 text-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                  <path fill="currentColor"
                    d="M19.63 3.65a1 1 0 0 0-.84-.2a8 8 0 0 1-6.22-1.27a1 1 0 0 0-1.14 0a8 8 0 0 1-6.22 1.27a1 1 0 0 0-.84.2a1 1 0 0 0-.37.78v7.45a9 9 0 0 0 3.77 7.33l3.65 2.6a1 1 0 0 0 1.16 0l3.65-2.6A9 9 0 0 0 20 11.88V4.43a1 1 0 0 0-.37-.78ZM18 11.88a7 7 0 0 1-2.93 5.7L12 19.77l-3.07-2.19A7 7 0 0 1 6 11.88v-6.3a10 10 0 0 0 6-1.39a10 10 0 0 0 6 1.39Zm-4.46-2.29l-2.69 2.7l-.89-.9a1 1 0 0 0-1.42 1.42l1.6 1.6a1 1 0 0 0 1.42 0L15 11a1 1 0 0 0-1.42-1.42Z" />
                </svg>
              </div>
              <div class="col-md-10">
                <div class="card-body p-0">
                  <h5>Pago 100% seguro</h5>
                  <p class="card-text"></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card mb-3 border-0">
            <div class="row">
              <div class="col-md-2 text-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                  <path fill="currentColor"
                    d="M22 5H2a1 1 0 0 0-1 1v4a3 3 0 0 0 2 2.82V22a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1v-9.18A3 3 0 0 0 23 10V6a1 1 0 0 0-1-1Zm-7 2h2v3a1 1 0 0 1-2 0Zm-4 0h2v3a1 1 0 0 1-2 0ZM7 7h2v3a1 1 0 0 1-2 0Zm-3 4a1 1 0 0 1-1-1V7h2v3a1 1 0 0 1-1 1Zm10 10h-4v-2a2 2 0 0 1 4 0Zm5 0h-3v-2a4 4 0 0 0-8 0v2H5v-8.18a3.17 3.17 0 0 0 1-.6a3 3 0 0 0 4 0a3 3 0 0 0 4 0a3 3 0 0 0 4 0a3.17 3.17 0 0 0 1 .6Zm2-11a1 1 0 0 1-2 0V7h2ZM4.3 3H20a1 1 0 0 0 0-2H4.3a1 1 0 0 0 0 2Z" />
                </svg>
              </div>
              <div class="col-md-10">
                <div class="card-body p-0">
                  <h5>Garantía de calidad</h5>
                  <p class="card-text"> </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card mb-3 border-0">
            <div class="row">
              <div class="col-md-2 text-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                  <path fill="currentColor"
                    d="M12 8.35a3.07 3.07 0 0 0-3.54.53a3 3 0 0 0 0 4.24L11.29 16a1 1 0 0 0 1.42 0l2.83-2.83a3 3 0 0 0 0-4.24A3.07 3.07 0 0 0 12 8.35Zm2.12 3.36L12 13.83l-2.12-2.12a1 1 0 0 1 0-1.42a1 1 0 0 1 1.41 0a1 1 0 0 0 1.42 0a1 1 0 0 1 1.41 0a1 1 0 0 1 0 1.42ZM12 2A10 10 0 0 0 2 12a9.89 9.89 0 0 0 2.26 6.33l-2 2a1 1 0 0 0-.21 1.09A1 1 0 0 0 3 22h9a10 10 0 0 0 0-20Zm0 18H5.41l.93-.93a1 1 0 0 0 0-1.41A8 8 0 1 1 12 20Z" />
                </svg>
              </div>
              <div class="col-md-10">
                <div class="card-body p-0">
                  <h5>Ahorros garantizados</h5>
                  <p class="card-text"></p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card mb-3 border-0">
            <div class="row">
              <div class="col-md-2 text-dark">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                  <path fill="currentColor"
                    d="M18 7h-.35A3.45 3.45 0 0 0 18 5.5a3.49 3.49 0 0 0-6-2.44A3.49 3.49 0 0 0 6 5.5A3.45 3.45 0 0 0 6.35 7H6a3 3 0 0 0-3 3v2a1 1 0 0 0 1 1h1v6a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3v-6h1a1 1 0 0 0 1-1v-2a3 3 0 0 0-3-3Zm-7 13H8a1 1 0 0 1-1-1v-6h4Zm0-9H5v-1a1 1 0 0 1 1-1h5Zm0-4H9.5A1.5 1.5 0 1 1 11 5.5Zm2-1.5A1.5 1.5 0 1 1 14.5 7H13ZM17 19a1 1 0 0 1-1 1h-3v-7h4Zm2-8h-6V9h5a1 1 0 0 1 1 1Z" />
                </svg>
              </div>
              <div class="col-md-10">
                <div class="card-body p-0">
                  <h5>Ofertas diarias</h5>
                  <p class="card-text"> </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="py-5">
    <div class="container-fluid">
      <div class="row">
       
        
       

      </div>
    </div>
  </footer>
  <div id="footer-bottom">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6 copyright">
          <p>© 2025 DON JORGITO. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
    crossorigin="anonymous"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
</body>

</html>