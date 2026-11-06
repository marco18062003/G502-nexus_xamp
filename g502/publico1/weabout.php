<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Don Jorgito | La Herencia del Sabor</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/icon1.png" type="image/jpeg">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@200;400;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --gold: #d4af37;
            --soft-gold: #f1e5ac;
            --deep-black: #050505;
            --subtle-gray: #1a1a1a;
        }

        body {
            background-color: var(--deep-black);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        /* Animated Background Aura */
        .aura {
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.08) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            animation: moveAura 20s infinite alternate linear;
        }

        @keyframes moveAura {
            0% { top: -10%; left: -10%; }
            100% { top: 80%; left: 80%; }
        }

        h1, h2, h3 { font-family: 'Playfair Display', serif; font-style: italic; }

        /* Hero */
        .hero-split {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hero-title {
            font-size: clamp(3.5rem, 12vw, 8rem);
            background: linear-gradient(to bottom, #fff 30%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            letter-spacing: -2px;
        }

        /* Pretty Content Styling */
        .floating-img {
            border-radius: 250px 250px 0 0;
            border: 1px solid rgba(212, 175, 55, 0.4);
            padding: 15px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
            transition: 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .floating-img:hover { transform: translateY(-20px) scale(1.02); }

        .luxury-card {
            border-left: 1px solid var(--gold);
            padding: 10px 30px;
            margin: 30px 0;
            opacity: 0.8;
            transition: 0.4s;
        }
        .luxury-card:hover { opacity: 1; border-left-width: 5px; }

        /* Shop Information Section (The "Pretty" Part) */
        .shop-info-grid {
            padding: 100px 0;
            background: rgba(255,255,255,0.02);
        }

        .contact-item {
            text-align: center;
            padding: 40px;
            transition: 0.5s;
        }

        .contact-item i {
            font-size: 2rem;
            color: var(--gold);
            margin-bottom: 20px;
            display: block;
        }

        .contact-item h4 {
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            letter-spacing: 4px;
            font-size: 0.8rem;
            margin-bottom: 15px;
            color: var(--soft-gold);
        }

        .contact-item p { font-weight: 200; font-size: 1.1rem; color: #eee; }

        .btn-luxury {
            background: transparent;
            color: var(--gold);
            padding: 15px 50px;
            letter-spacing: 5px;
            text-transform: uppercase;
            border: 1px solid var(--gold);
            font-weight: 400;
            transition: 0.5s;
        }

        .btn-luxury:hover {
            background: var(--gold);
            color: #000;
            box-shadow: 0 0 40px rgba(212, 175, 55, 0.3);
        }

        .scroll-indicator {
            position: absolute;
            bottom: 40px;
            animation: bounce 2s infinite;
            font-size: 2rem;
            color: var(--gold);
            opacity: 0.3;
        }

        @keyframes bounce { 0%, 100% {transform: translateY(0);} 50% {transform: translateY(10px);} }
    </style>
</head>
<body>

    <div class="aura"></div>

    <section class="hero-split">
        <div class="text-center">
            <p class="text-uppercase mb-3" style="letter-spacing: 12px; font-size: 0.7rem;">Establecido en g502</p>
            <h1 class="hero-title">Don Jorgito</h1>
            <p class="lead italic" style="font-family: 'Playfair Display'; opacity: 0.6;">El arte de saber elegir.</p>
        </div>
        <i class="fas fa-chevron-down scroll-indicator"></i>
    </section>

    <section class="py-5 mt-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5 text-center">
                    <img src="assets/icon1.png" alt="Icon" class="img-fluid floating-img">
                </div>
                <div class="col-lg-7">
                    <h2 class="display-3 mb-5">Nuestra <span class="text-gold">Promesa</span></h2>
                    <p class="mb-5" style="line-height: 2.2; font-weight: 200; font-size: 1.15rem;">
                        No somos una simple tienda de licores. En <strong>Don Jorgito</strong>, cada botella es seleccionada bajo el rigor de la excelencia. Creemos que el brindis es el momento más sagrado de cualquier reunión, y nuestra misión es que ese momento sea perfecto.
                    </p>
                    
                    <div class="luxury-card">
                        <h3>Curaduría</h3>
                        <p>Solo etiquetas que superan nuestra prueba de sabor y origen.</p>
                    </div>
                    
                    <div class="luxury-card">
                        <h3>Confianza</h3>
                        <p>Garantía total de autenticidad en cada sello.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="shop-info-grid my-5">
        <div class="container">
            <div class="row g-0">
                <div class="col-md-4 border-end border-secondary">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <h4>Ubicación</h4>
                        <p>Calle Principal #123<br>Tu Ciudad, CP 0000</p>
                    </div>
                </div>
                <div class="col-md-4 border-end border-secondary">
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <h4>Contacto Directo</h4>
                        <p>+57 300 123 4567<br>Atención Personalizada</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <h4>Horario</h4>
                        <p>Lun - Sáb: 10:00 - 20:00<br>Dom: Cerrado</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-5">
                <a href="index.php" class="btn btn-luxury">Volver a la Tienda</a>
            </div>
        </div>
    </section>

    <footer class="py-5 text-center" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="container">
            <h2 class="text-gold" style="font-size: 1.2rem; letter-spacing: 5px;">DON JORGITO</h2>
            <p class="small text-secondary mt-3">© <?php echo date('Y'); ?> Proyecto g502. El exceso de alcohol es perjudicial para la salud.</p>
        </div>
    </footer>

</body>
</html>