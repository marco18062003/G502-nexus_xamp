<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>DonJorgito | Bodega & Licorera Premium</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ScrollReveal JS -->
    <script src="https://unpkg.com/scrollreveal"></script>

    <style>
        :root {
            /* Black & Gold Luxury Palette */
            --bg-dark: #070707;
            --bg-card: #121212;
            --bg-card-hover: #1a1a1a;
            --gold-primary: #D4AF37;
            --gold-light: #F3E5AB;
            --gold-dark: #AA771C;
            --gold-gradient: linear-gradient(135deg, #BF953F 0%, #FCF6BA 25%, #B38728 50%, #FBF5B7 75%, #AA771C 100%);
            --gold-gradient-soft: linear-gradient(135deg, rgba(212, 175, 55, 0.18) 0%, rgba(170, 119, 28, 0.03) 100%);
            --text-white: #f4f4f5;
            --text-muted: #a1a1aa;
            --text-gold: #e5c158;
            --border-gold: rgba(212, 175, 55, 0.25);
            --border-gold-glow: rgba(212, 175, 55, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 100%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Gold Gradient Text Utility */
        .gold-text {
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        /* Ambient Glow Background Element */
        .ambient-glow {
            position: absolute;
            width: clamp(280px, 50vw, 600px);
            height: clamp(280px, 50vw, 600px);
            background: radial-gradient(circle, rgba(212, 175, 55, 0.12) 0%, rgba(0, 0, 0, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 1;
        }

        /* Responsive Navigation Header */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 16px clamp(16px, 4vw, 50px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            transition: all 0.4s ease;
            background: rgba(7, 7, 7, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.18);
        }

        .nav-logo {
            font-family: 'Cinzel', serif;
            font-weight: 800;
            font-size: clamp(1.3rem, 2.5vw, 1.8rem);
            letter-spacing: 2px;
            text-decoration: none;
            color: var(--text-white);
            white-space: nowrap;
        }

        .nav-logo span {
            color: var(--gold-primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-badge {
            font-size: 0.75rem;
            letter-spacing: 1.5px;
            padding: 8px 18px;
            border: 1px solid var(--border-gold);
            border-radius: 50px;
            text-transform: uppercase;
            color: var(--gold-light);
            background: rgba(212, 175, 55, 0.05);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .nav-cta {
            font-family: 'Cinzel', serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            text-decoration: none;
            color: #000;
            background: var(--gold-gradient);
            padding: 10px 20px;
            border-radius: 4px;
            transition: all 0.3s ease;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.5);
        }

        /* Mobile Hamburger Button */
        .mobile-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--border-gold);
            color: var(--gold-primary);
            width: 44px;
            height: 44px;
            border-radius: 6px;
            font-size: 1.2rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .mobile-toggle:hover {
            background: rgba(212, 175, 55, 0.1);
        }

        /* Mobile Drawer */
        .mobile-menu {
            position: fixed;
            top: 70px;
            left: 0;
            width: 100%;
            background: rgba(12, 12, 12, 0.98);
            border-bottom: 1px solid var(--border-gold);
            padding: 25px 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
            text-align: center;
            transform: translateY(-150%);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 99;
            backdrop-filter: blur(20px);
        }

        .mobile-menu.active {
            transform: translateY(0);
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 100vh;
            min-height: 100dvh;
            background: linear-gradient(180deg, rgba(7, 7, 7, 0.78) 0%, rgba(7, 7, 7, 0.94) 85%, #070707 100%),
                        url('https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: clamp(100px, 15vh, 140px) clamp(16px, 5vw, 40px) clamp(40px, 8vh, 80px);
            overflow: hidden;
            width: 100%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-tag {
            font-family: 'Cinzel', serif;
            font-size: clamp(0.75rem, 1.8vw, 0.95rem);
            text-transform: uppercase;
            letter-spacing: clamp(2px, 0.5vw, 5px);
            color: var(--gold-primary);
            margin-bottom: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .hero h1 {
            font-family: 'Cinzel', serif;
            font-size: clamp(2.5rem, 8.5vw, 5.5rem);
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 12px;
            letter-spacing: 1px;
            text-shadow: 0 0 35px rgba(212, 175, 55, 0.25);
            word-break: break-word;
        }

        .hero .slogan-col {
            font-family: 'Cinzel', serif;
            font-size: clamp(0.95rem, 2.5vw, 1.35rem);
            color: var(--gold-light);
            font-weight: 600;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: clamp(1px, 0.4vw, 4px);
        }

        .hero p {
            font-size: clamp(0.95rem, 1.8vw, 1.15rem);
            max-width: 680px;
            margin: 0 auto clamp(25px, 4vh, 40px);
            color: var(--text-muted);
            font-weight: 300;
            line-height: 1.7;
            padding: 0 10px;
        }

        /* Gold Shimmer Metallic Button */
        .btn-gold {
            position: relative;
            padding: clamp(14px, 2vh, 20px) clamp(28px, 4vw, 50px);
            font-family: 'Cinzel', serif;
            font-size: clamp(0.85rem, 1.8vw, 1rem);
            font-weight: 700;
            letter-spacing: 2px;
            text-decoration: none;
            text-transform: uppercase;
            color: #070707;
            background: var(--gold-gradient);
            border: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 0 25px rgba(212, 175, 55, 0.35);
            transition: all 0.4s ease;
            overflow: hidden;
            min-height: 48px;
            max-width: 100%;
        }

        .btn-gold::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                60deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            transform: rotate(30deg) translateX(-100%);
            transition: transform 0.8s ease;
        }

        .btn-gold:hover::before {
            transform: rotate(30deg) translateX(100%);
        }

        .btn-gold:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 35px rgba(212, 175, 55, 0.6);
            color: #000;
        }

        /* Trust Badges Responsive Grid */
        .trust-badge-container {
            margin-top: clamp(35px, 6vh, 60px);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: clamp(10px, 2vw, 20px);
            width: 100%;
            max-width: 900px;
        }

        .trust-card {
            background: rgba(18, 18, 18, 0.65);
            border: 1px solid var(--border-gold);
            padding: clamp(12px, 1.8vh, 18px) 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            min-height: 52px;
        }

        .trust-card:hover {
            border-color: var(--gold-primary);
            box-shadow: 0 4px 20px rgba(212, 175, 55, 0.18);
            transform: translateY(-2px);
        }

        .trust-card i {
            color: var(--gold-primary);
            font-size: 1.2rem;
            filter: drop-shadow(0 0 5px rgba(212, 175, 55, 0.5));
            flex-shrink: 0;
        }

        .trust-card span {
            font-size: clamp(0.78rem, 1.5vw, 0.88rem);
            font-weight: 500;
            letter-spacing: 0.3px;
            color: var(--text-white);
            text-align: left;
        }

        /* Categories Section */
        .categories-section {
            padding: clamp(60px, 10vh, 100px) clamp(16px, 4vw, 40px);
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            width: 100%;
        }

        .section-header {
            margin-bottom: clamp(35px, 6vh, 60px);
        }

        .section-header h2 {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 700;
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: clamp(0.88rem, 1.6vw, 1rem);
            letter-spacing: 1px;
        }

        .category-divider {
            width: 70px;
            height: 2px;
            background: var(--gold-gradient);
            margin: 15px auto 0;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: clamp(16px, 2.5vw, 30px);
            width: 100%;
        }

        .category-card {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: 12px;
            padding: clamp(25px, 4vh, 40px) 20px;
            text-align: center;
            transition: all 0.4s ease;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
        }

        .category-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gold-gradient-soft);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .category-card:hover {
            transform: translateY(-8px);
            border-color: var(--gold-primary);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .category-icon {
            width: clamp(56px, 8vw, 70px);
            height: clamp(56px, 8vw, 70px);
            background: rgba(212, 175, 55, 0.08);
            border: 1px solid var(--border-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
            transition: all 0.4s ease;
            flex-shrink: 0;
        }

        .category-card:hover .category-icon {
            background: var(--gold-gradient);
            border-color: transparent;
        }

        .category-icon i {
            font-size: clamp(1.4rem, 2.5vw, 1.8rem);
            color: var(--gold-primary);
            transition: all 0.4s ease;
        }

        .category-card:hover .category-icon i {
            color: #070707;
        }

        .category-card h3 {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.1rem, 2vw, 1.3rem);
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .category-card p {
            font-size: clamp(0.82rem, 1.5vw, 0.9rem);
            color: var(--text-muted);
            position: relative;
            z-index: 2;
            line-height: 1.5;
        }

        /* Banner CTA Section */
        .cta-banner {
            background: linear-gradient(rgba(7,7,7,0.88), rgba(7,7,7,0.88)),
                        url('https://images.unsplash.com/photo-1527281400683-1aae777175f8?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: scroll;
            border-top: 1px solid var(--border-gold);
            border-bottom: 1px solid var(--border-gold);
            padding: clamp(50px, 8vh, 80px) clamp(16px, 4vw, 30px);
            text-align: center;
            margin: clamp(30px, 5vh, 60px) 0;
            width: 100%;
        }

        .cta-banner h2 {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 4vw, 2.5rem);
            margin-bottom: 12px;
        }

        .cta-banner p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 25px;
            font-size: clamp(0.9rem, 1.6vw, 1rem);
        }

        /* Footer */
        footer {
            background-color: #030303;
            border-top: 1px solid rgba(212, 175, 55, 0.15);
            padding: clamp(40px, 6vh, 60px) 20px 30px;
            text-align: center;
            position: relative;
            width: 100%;
        }

        .footer-logo {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .footer-socials {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 20px 0;
        }

        .footer-socials a {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 1px solid var(--border-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-socials a:hover {
            background: var(--gold-gradient);
            color: #000;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
            transform: translateY(-3px);
        }

        .footer-text {
            font-size: clamp(0.8rem, 1.5vw, 0.88rem);
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 15px;
        }

        .footer-law {
            font-size: clamp(0.68rem, 1.2vw, 0.75rem);
            color: #666;
            margin-top: 18px;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        /* Breakpoints for Responsive Layout */

        /* Laptop & Large Desktop */
        @media (min-width: 1440px) {
            .hero-content { max-width: 1100px; }
            .categories-section { max-width: 1350px; }
        }

        /* Tablet (Medium screens) */
        @media (max-width: 1024px) {
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile & Small Tablets */
        @media (max-width: 768px) {
            .nav-badge, .nav-cta {
                display: none;
            }

            .mobile-toggle {
                display: flex;
            }

            .trust-badge-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .cta-banner {
                background-attachment: scroll;
            }
        }

        /* Very Small Phones (e.g. 320px - 480px) */
        @media (max-width: 480px) {
            .trust-badge-container {
                grid-template-columns: 1fr;
            }

            .category-grid {
                grid-template-columns: 1fr;
            }

            .trust-card {
                justify-content: flex-start;
                padding-left: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Glow Background Element -->
    <div class="ambient-glow"></div>

    <!-- Navigation Header -->
    <header>
        <a href="#" class="nav-logo">DON<span class="gold-text">JORGITO</span></a>
        
        <div class="nav-links">
            <div class="nav-badge"><i class="fas fa-crown gold-text"></i> Premium Liquor Store</div>
            <a href="https://donjorgito.shop/g502/publico1/index.php" class="nav-cta">Catálogo</a>
            <button class="mobile-toggle" id="menuToggle" aria-label="Toggle Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Mobile Drawer Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="nav-badge"><i class="fas fa-crown gold-text"></i> Premium Liquor Store</div>
        <a href="https://donjorgito.shop/g502/publico1/index.php" class="btn-gold" style="width: 100%;">
            Ver Catálogo Digital <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <span class="hero-tag"><i class="fas fa-gem"></i> Exclusive Selection</span>
            <h1 id="title" class="gold-text">DonJorgito</h1>
            <div class="slogan-col">CALIDAD Y TRADICIÓN EN CADA BOTELLA</div>
            <p id="subtitle">
                Explora nuestra exclusiva selección de licores nacionales e internacionales de alta gama.
                Garantizamos autenticidad certificada y entrega express para tus celebraciones más exigentes.
            </p>
            
            <a href="https://donjorgito.shop/g502/publico1/index.php" class="btn-gold">
                Explorar Catálogo <i class="fas fa-arrow-right"></i>
            </a>

            <!-- Trust Badges -->
            <div class="trust-badge-container">
                <div class="trust-card">
                    <i class="fas fa-shield-alt"></i>
                    <span>Original Garantizado</span>
                </div>
                <div class="trust-card">
                    <i class="fas fa-bolt"></i>
                    <span>Domicilio Veloz</span>
                </div>
                <div class="trust-card">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Bogotá, Colombia</span>
                </div>
                <div class="trust-card">
                    <i class="fas fa-credit-card"></i>
                    <span>Medios de Pago</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories / Highlights Section -->
    <section class="categories-section">
        <div class="section-header">
            <h2>Cava de <span class="gold-text">Selección</span></h2>
            <p>Descubre las categorías más distinguidas de nuestra licorera</p>
            <div class="category-divider"></div>
        </div>

        <div class="category-grid">
            <a href="https://donjorgito.shop/g502/publico1/index.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-glass-whiskey"></i>
                </div>
                <h3>Whisky & Cognac</h3>
                <p>Single malts, blends de reserva y destilados de refinada maduración.</p>
            </a>

            <a href="https://donjorgito.shop/g502/publico1/index.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-wine-bottle"></i>
                </div>
                <h3>Tequila & Mezcal</h3>
                <p>100% agave azul, añejos y reposados artesanales de alta alcurnia.</p>
            </a>

            <a href="https://donjorgito.shop/g502/publico1/index.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-wine-glass"></i>
                </div>
                <h3>Vinos de Reserva</h3>
                <p>Cosechas seleccionadas de las casas vinícolas más prestigiosas.</p>
            </a>

            <a href="https://donjorgito.shop/g502/publico1/index.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-cocktail"></i>
                </div>
                <h3>Ron Premium</h3>
                <p>Rones añejados en barricas de roble con matices caribeños únicos.</p>
            </a>
        </div>
    </section>

    <!-- Call to Action Banner -->
    <section class="cta-banner">
        <h2>¿Listo para <span class="gold-text">Celebrar</span>?</h2>
        <p>Accede directamente a nuestra plataforma digital y realiza tu pedido con envío inmediato.</p>
        <a href="https://donjorgito.shop/g502/publico1/index.php" class="btn-gold">
            Pedir Ahora <i class="fas fa-shopping-cart"></i>
        </a>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-logo">DON<span class="gold-text">JORGITO</span></div>
        <p class="footer-text">La bodega & licorera de referencia para momentos inolvidables. Bogotá, Colombia.</p>
        
        <div class="footer-socials">
            <a href="https://donjorgito.shop/g502/publico1/index.php" title="Instagram" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="https://donjorgito.shop/g502/publico1/index.php" title="Facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://donjorgito.shop/g502/publico1/index.php" title="WhatsApp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
        </div>

        <p class="footer-text">&copy; 2026 DonJorgito Bodega & Licorera. Todos los derechos reservados.</p>
        <p class="footer-law">EL EXCESO DE ALCOHOL ES PERJUDICIAL PARA LA SALUD. PROHIBIDA LA VENTA A MENORES DE EDAD.</p>
    </footer>

    <script>
        // Mobile Drawer Toggle
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuIcon = menuToggle.querySelector('i');

        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
            if (mobileMenu.classList.contains('active')) {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-times');
            } else {
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        });

        // Close menu on click outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target) && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        });

        // ScrollReveal Animations
        ScrollReveal().reveal('.hero-content', { 
            delay: 150,
            distance: '40px',
            origin: 'bottom',
            duration: 1000
        });

        ScrollReveal().reveal('.category-card', { 
            delay: 100,
            distance: '30px',
            origin: 'bottom',
            duration: 800,
            interval: 120
        });

        ScrollReveal().reveal('.section-header', { 
            delay: 80,
            distance: '20px',
            origin: 'top',
            duration: 800
        });

        // Desktop Interactive mouse parallax
        if (window.innerWidth > 768) {
            document.querySelector('.hero').addEventListener('mousemove', (e) => {
                let moveX = (e.pageX / window.innerWidth - 0.5) * 12;
                let moveY = (e.pageY / window.innerHeight - 0.5) * 12;
                document.querySelector('.hero').style.backgroundPosition = `calc(50% + ${moveX}px) calc(50% + ${moveY}px)`;
            });
        }
    </script>
</body>
</html>