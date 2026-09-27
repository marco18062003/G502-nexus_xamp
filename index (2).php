<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonJorgito | Bodega & Licorera</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/scrollreveal"></script>

    <style>
        :root {
            /* Nueva paleta Azul Profundo / Colombiana Premium */
            --azul-fondo: #0a0e17;
            --azul-acento: #1e293b;
            --cian-brillante: #38bdf8;
            --blanco: #f8fafc;
            --gris-texto: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--azul-fondo);
            color: var(--blanco);
            overflow-x: hidden;
        }

        /* Hero Section mejorado */
        .hero {
            height: 100vh;
            background: linear-gradient(rgba(10, 14, 23, 0.8), rgba(10, 14, 23, 0.9)), 
                        url('https://images.unsplash.com/photo-1597290282695-edc43d0e7129?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 5rem;
            margin-bottom: 5px;
            color: var(--blanco);
            text-shadow: 2px 2px 10px rgba(56, 189, 248, 0.3);
        }

        .hero .slogan-col {
            font-size: 1.5rem;
            color: var(--cian-brillante);
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .hero p {
            font-size: 1.1rem;
            max-width: 650px;
            margin-bottom: 35px;
            color: var(--gris-texto);
            line-height: 1.6;
        }

        /* Botón estilo "Premium Bodega" */
        .btn-enter {
            padding: 18px 45px;
            background-color: var(--cian-brillante);
            color: var(--azul-fondo);
            text-decoration: none;
            font-weight: 700;
            border-radius: 8px; /* Menos redondo para verse más moderno/serio */
            text-transform: uppercase;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.4);
            display: inline-block;
        }

        .btn-enter:hover {
            transform: translateY(-5px);
            background-color: var(--blanco);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }

        /* Badge de confianza (Muy común en Colombia) */
        .trust-badge {
            margin-top: 40px;
            font-size: 0.8rem;
            display: flex;
            gap: 20px;
            color: var(--gris-texto);
        }

        .trust-badge span i {
            color: var(--cian-brillante);
            margin-right: 5px;
        }

        footer {
            padding: 30px;
            text-align: center;
            background-color: rgba(0,0,0,0.3);
            font-size: 0.8rem;
            color: var(--gris-texto);
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 3rem; }
            .hero .slogan-col { font-size: 1.1rem; }
        }
    </style>
</head>
<body>

    <section class="hero">
        <div class="hero-content">
    <h1 id="title">DonJorgito</h1>
    <div class="slogan-generic">CALIDAD Y TRADICIÓN EN CADA BOTELLA</div>
    <p id="subtitle">Explora nuestra exclusiva selección de licores nacionales e internacionales. 
       Garantizamos autenticidad y entrega inmediata en todos tus pedidos.</p>
    
    <a href="https://donjorgito.shop/g502/publico1/index.php" class="btn-enter">
        Explorar Catálogo
    </a>

            <div class="trust-badge">
                <span><i class="fas fa-check-circle"></i> Original Garantizado</span>
                <span><i class="fas fa-truck"></i> Domicilio Veloz</span>
                <span><i class="fas fa-map-marker-alt"></i> Bogota, Colombia</span>
                <span><i class="fas fa-credit-card"></i> Diversos Medios de Pago</span>
            </div>
        </div>
    </section>



    <script>
        ScrollReveal().reveal('.hero-content', { 
            delay: 200,
            distance: '60px',
            origin: 'bottom',
            duration: 1200
        });

        // Efecto parallax sutil en el fondo azul
        document.querySelector('.hero').addEventListener('mousemove', (e) => {
            let moveX = (e.pageX / window.innerWidth) * 20;
            let moveY = (e.pageY / window.innerHeight) * 20;
            document.querySelector('.hero').style.backgroundPosition = `${50 + moveX}% ${50 + moveY}%`;
        });
    </script>

</body>
</html>