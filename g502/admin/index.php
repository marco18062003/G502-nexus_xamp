<?php
// admin/index.php - Panel Principal de Administración para g502
require_once('seguridad_admin.php'); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Panel de Administración - g502</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --sidebar-bg: #2c3e50;
            --sidebar-active: #3498db;
            --light-bg: #f4f7f6;
            --card-bg: #ffffff;
            --text-color: #ecf0f1;
            --primary-color: #34495e;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --sidebar-width: 260px;
        }

        body { font-family: 'Segoe UI', sans-serif; background-color: var(--light-bg); margin: 0; color: #333; overflow-x: hidden; }
        
        /* --- LAYOUT --- */
        .dashboard-container { display: flex; min-height: 100vh; position: relative; }

        /* --- BARRA SUPERIOR MÓVIL --- */
        .mobile-header {
            display: none;
            background: var(--sidebar-bg);
            color: white;
            padding: 15px;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* --- SIDEBAR (Mejorado) --- */
        .sidebar { 
            width: var(--sidebar-width); 
            background-color: var(--sidebar-bg); 
            color: white; 
            position: fixed; /* Fixed para que no se mueva */
            left: 0;
            top: 0; 
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease-in-out;
            z-index: 999;
        }

        .sidebar h2 { 
            text-align: center; 
            padding: 25px 10px; 
            margin: 0;
            background: rgba(0,0,0,0.2);
            color: var(--sidebar-active);
            font-size: 1.2rem;
            letter-spacing: 1px;
        }

        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar ul li a, .dropdown-btn {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: 0.2s;
            font-size: 0.95rem;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
        }

        .sidebar ul li a:hover, .dropdown-btn:hover {
            background-color: rgba(255,255,255,0.1);
            color: var(--sidebar-active);
        }

        .sidebar i { width: 25px; font-size: 1.1rem; margin-right: 10px; }

        .dropdown-container {
            display: none;
            background-color: #1a252f;
        }
        .dropdown-container a { padding-left: 50px !important; font-size: 0.85rem !important; }

        /* --- CONTENIDO PRINCIPAL --- */
        .content { 
            flex-grow: 1; 
            padding: 30px; 
            margin-left: var(--sidebar-width); /* Deja espacio para el sidebar fixed */
            transition: margin 0.3s ease;
        }

        /* --- TARJETAS (WIDGETS) --- */
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .widget-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-top: 4px solid #ddd; /* Cambiado a top para mejor look móvil */
        }
        .widget-card h3 { margin: 0 0 10px 0; font-size: 0.8rem; color: #7f8c8d; }
        .widget-card p { font-size: 1.8rem; font-weight: bold; margin: 0; color: var(--primary-color); }
        
        .widget-card.pending { border-top-color: var(--warning-color); }
        .widget-card.income { border-top-color: var(--success-color); }
        .widget-card.stock { border-top-color: var(--danger-color); }

        /* --- RESPONSIVE BREAKPOINTS --- */
        @media (max-width: 992px) {
            .mobile-header { display: flex; }
            .sidebar { transform: translateX(-100%); } /* Escondido a la izquierda */
            .sidebar.active { transform: translateX(0); } /* Entra al activar */
            .content { margin-left: 0; padding: 20px; }
            
            /* Overlay cuando el menú está abierto */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 998;
            }
            .sidebar.active + .sidebar-overlay { display: block; }
        }

        @media (max-width: 480px) {
            .widget-grid { grid-template-columns: 1fr; } /* Una columna en teléfonos pequeños */
            .content h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="mobile-header">
    <span>Admin <strong>g502</strong></span>
    <i class="fas fa-bars" onclick="toggleSidebar()" style="cursor:pointer; font-size: 1.5rem;"></i>
</div>

<div class="dashboard-container">
    <nav class="sidebar" id="sidebarMenu">
        <h2>Don Jorgito g502</h2>
        <ul>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="basefacturas.php"><i class="fas fa-database"></i> Base de Facturas</a></li>
            <li><a href="clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
            <li><a href="admin_cuentas.php"><i class="fas fa-chart-line"></i> Cuentas / Finanzas</a></li>
            <li><a href="gestion_deudas.php"><i class="fas fa-hand-holding-usd"></i> Deudas Clientes</a></li>

            <li>
                <button class="dropdown-btn"><i class="fas fa-paint-brush"></i> Diseño Página 
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-container">
                    <a href="facturaelectronica.php"><i class="fas fa-image"></i> Banners</a>
                    <a href="facturaelectronica.php"><i class="fas fa-palette"></i> Colores</a>
                    <a href="facturaelectronica.php"><i class="fas fa-vector-square"></i> Logos</a>
                </div>
            </li>

            <li><a href="black.php"><i class="fas fa-user-tie"></i> Empleados</a></li>
            <li><a href="POS.php"><i class="fas fa-barcode"></i> Escanear y Registra</a></li>
            <li><a href="facturaelectronica.php"><i class="fas fa-file-invoice-dollar"></i> Factura Electrónica</a></li>

            <li>
                <button class="dropdown-btn"><i class="fas fa-exclamation-circle"></i> IC (Inv. Crítico) 
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-container">
                    <a href="facturaelectronica.php"><i class="fas fa-sort-numeric-up"></i> Cantidad</a>
                    <a href="facturaelectronica.php"><i class="fas fa-check-circle"></i> Disponible</a>
                    <a href="facturaelectronica.php"><i class="fas fa-heart-broken"></i> Daños</a>
                    <a href="facturaciong502.php"><i class="fas fa-file-invoice"></i> Factura electrónica</a>
                    <a href="mi_empresa.php"><i class="fas fa-building"></i> Factura electrónica g502</a>
                    <a href="expenses.php"><i class="fas fa-money-bill-wave"></i> Gastos</a>
                    <a href="https://donjorgito.shop/g502/plu/vencer.php"><i class="fas fa-hourglass-end"></i> Registrar a Vencer</a>
                    <a href="expenses.php"><i class="fas fa-money-bill-wave"></i> Gastos</a>
                    <a href="https://donjorgito.shop/g502/publico1/ofertaedit.php"><i class="fas fa-money-bill-wave"></i> Ofertas</a>
                </div>
            </li>

            <li><a href="brand.php"><i class="fas fa-copyright"></i> Marcas</a></li>
            <li><a href="paymeth.php"><i class="fas fa-credit-card"></i> Pagos Medios</a></li>
            <li>
                <button class="dropdown-btn"><i class="fas fa-paint-brush"></i> orden
                    <i class="fa fa-caret-down"></i>
                </button>
                <div class="dropdown-container">
                    <a href="https://donjorgito.shop/g502/admin/pre-pedidos.php"><i class="fas fa-image"></i> pre-pedido</a>
                    <a href="https://donjorgito.shop/g502/admin/admin_pedidos.php"><i class="fas fa-palette"></i> pedido</a>
                    
                </div>
            </li>
            <li><a href="admin_productos.php"><i class="fas fa-tags"></i> Productos</a></li>
            <li><a href="expenses.php"><i class="fas fa-calendar-alt"></i> Próximos Cobros</a></li>
            <li><a href="../publico1/donjorgito.php"><i class="fas fa-upload"></i> Subir</a></li>
            <li><a href="subirarchivos.php"><i class="fas fa-file-upload"></i> Subir Documentos</a></li>
            <li><a href="https://donjorgito.shop/g502/admin/revisar_comprobantes.php"><i class="fas fa-file-upload"></i> comprobantes</a></li>
            <li><a href="../publico1/index.php"><i class="fas fa-store"></i> Tienda</a></li>
            
            <li style="margin-top: 20px; border-top: 1px solid #444;">
                <a href="../publico1/logout.php" style="color: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<main class="content">
    
    <header class="content-header">
        <h1>Dashboard Overview</h1>
        <p>General statistics for <strong>g502</strong></p>
    </header>

    <div class="widget-grid">
        <div class="widget-card pending">
            <h3>Orders</h3>
            <p>12</p>
            <small><i class="fas fa-shopping-cart"></i> Pending review</small>
        </div>

        <div class="widget-card income">
            <h3>Total Revenue</h3>
            <p>$4.500.000</p>
            <small><i class="fas fa-hand-holding-usd"></i> Total monthly income</small>
        </div>

        <div class="widget-card fulfillment">
            <h3>Fulfillment</h3>
            <p>85%</p>
            <small><i class="fas fa-chart-line"></i> Monthly goal progress</small>
        </div>

        <div class="widget-card target-gap">
            <h3>Target Gap</h3>
            <p>$750.000</p>
            <small><i class="fas fa-bullseye"></i> Remaining for objective</small>
        </div>

        <div class="widget-card customers">
            <h3>Total Clients</h3>
            <p>158</p>
            <small><i class="fas fa-user-check"></i> Active users</small>
        </div>

        <div class="widget-card message">
            <h3>Messages</h3>
            <p>12</p>
            <small><i class="fas fa-envelope"></i> Unread notifications</small>
        </div>
        <div class="widget-card message">
            <h3>POST</h3>
            <p>12</p>
            <small><i class="fas fa-envelope"></i> Unread notifications</small>
        </div>
    </div>

    <hr style="border: 0; border-top: 1px dashed #ccc; margin: 40px 0;">

    <header class="content-header">
        <h2>Today's Activity <span style="font-size: 0.9rem; color: #7f8c8d;">(<?php echo date('d M, Y'); ?>)</span> <button>filtro por mes</button><button>see grafic</button></h2>
    </header>

    <div class="widget-grid">
        <div class="widget-card income">
            <h3>Daily Income</h3>
            <p>$12.000</p>
            <small><i class="fas fa-cash-register"></i> Today's entries</small>
        </div>

        <div class="widget-card customers">
            <h3>Daily Clients</h3>
            <p>158</p>
            <small><i class="fas fa-users"></i> Active today</small>
        </div>

        <div class="widget-card fulfillment">
            <h3>Daily Progress</h3>
            <p>85%</p>
            <small><i class="fas fa-tasks"></i> Today's target</small>
        </div>

        <div class="widget-card target-gap">
            <h3>Remaining Today</h3>
            <p>$750.000</p> 
            <small><i class="fas fa-clock"></i> Still needed for today</small>
        </div>
        <div class="widget-card pending">
            <h3>Pending Orders</h3>
            <p>12</p>
            <small><i class="fas fa-hourglass-half"></i> Awaiting processing</small>
        </div>
    </div>





        <section style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <h3>Estado del Sistema</h3>
            <p>Bienvenido al administrador de <strong>g502</strong>. El sistema de facturación electrónica está operando con normalidad.</p>
        </section>
    </main>
</div>

<script>
    // Manejo de Dropdowns mejorado
    var dropdowns = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].addEventListener("click", function() {
            this.classList.toggle("active");
            var container = this.nextElementSibling;
            container.style.display = (container.style.display === "block") ? "none" : "block";
        });
    }

    // Toggle Sidebar para móviles
    function toggleSidebar() {
        document.getElementById("sidebarMenu").classList.toggle("active");
    }
</script>

</body>
</html>