<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Cajero';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>POS - DON JORGITO (g502)</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/posdes.css?v=<?php echo time(); ?>">
    <style>
        /* Estilos para el Escáner en el POS */
        #scanner-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 10000; flex-direction: column; align-items: center; justify-content: center;
        }
        #reader { width: 320px; border-radius: 15px; overflow: hidden; border: 4px solid #0d6efd; background: black; }
        .btn-close-scanner {
            margin-top: 20px; background: white; color: black; border: none;
            padding: 12px 25px; border-radius: 50px; font-weight: bold; cursor: pointer;
        }
        .btn-scan-pos {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #444;
            transition: all 0.2s;
        }
        .btn-scan-pos:active { background-color: #e2e6ea; }
    </style>
</head>
<body>
    

<div id="scanner-modal">
    <div id="reader"></div>
    <button type="button" class="btn-close-scanner" onclick="stopScanner()">CANCELAR ESCÁNER</button>
</div>

<nav class="navbar navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="index.php"><i class="fas fa-store"></i> G502 POS</a>
    <span class="text-white small"><?php echo $user_name; ?></span>
</nav>

<div class="pos-main-wrapper">
    <div class="search-section">
        <div class="search-container">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="pos-search" class="form-control" placeholder="Nombre, PLU o Barra..." autofocus autocomplete="off">
                <button class="btn btn-scan-pos" type="button" onclick="startScanner()">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            <div id="resultados-pos" class="dropdown-results"></div>
        </div>
    </div>

    <div class="cart-section">
        <div id="pos-items-list">
            <div class="text-center text-muted mt-5">
                <i class="fas fa-shopping-cart fa-3x"></i>
                <p class="mt-2">Carrito vacío</p>
            </div>
        </div>
        <div class="spacer-bottom"></div>
    </div>
</div>

<footer class="pos-footer">
    <div class="total-info">
        <span class="total-label">TOTAL A PAGAR</span>
        <div class="total-amount" id="pos-total">$ 0</div>
    </div>
    <button class="btn-cobrar" onclick="finalizarVenta()">
        COBRAR <i class="fas fa-check-circle ms-1"></i>
    </button>
</footer>

<div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Finalizar Venta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <span class="text-muted d-block">TOTAL A COBRAR</span>
                    <h2 class="display-5 fw-bold text-primary" id="modal-total-display">$ 0</h2>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <button class="btn btn-outline-primary w-100 py-3 fw-bold" onclick="seleccionarMetodo('efectivo')">
                            <i class="fas fa-money-bill-wave d-block mb-1"></i> EFECTIVO
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-primary w-100 py-3 fw-bold" onclick="generarPagoWompi()">
                            <i class="fas fa-credit-card d-block mb-1"></i> WOMPI / QR
                        </button>
                    </div>
                </div>
                <div id="seccion-efectivo" style="display: none;" class="p-3 border rounded bg-light">
                    <label class="form-label fw-bold small">DINERO RECIBIDO:</label>
                    <input type="number" id="monto-pagado" class="form-control form-control-lg text-center fw-bold mb-3" style="font-size: 2rem; border: 2px solid #0d6efd;" placeholder="0" oninput="calcularCambio()">
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm">
                        <span class="h6 m-0 text-muted">CAMBIO:</span>
                        <span class="h3 m-0 text-success fw-bold" id="cambio-display">$ 0</span>
                    </div>
                    <button class="btn btn-success btn-lg w-100 mt-3 fw-bold btn-confirmar-final" onclick="procesarVentaFinal()" disabled>CONFIRMAR VENTA</button>
                </div>
                <div id="mensaje-virtual" style="display: none;" class="alert alert-warning text-center mt-3">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Medios aún no disponibles</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="assets/poslog.js"></script>

<script>
let html5QrCode;
const posSearchInput = document.getElementById('pos-search');

function startScanner() {
    document.getElementById('scanner-modal').style.display = 'flex';
    html5QrCode = new Html5Qrcode("reader");
    
    const config = { 
        fps: 20, 
        qrbox: { width: 250, height: 150 },
        aspectRatio: 1.0 
    };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        (decodedText) => {
            // Ponemos el código en el input
            posSearchInput.value = decodedText;
            
            // Disparamos el evento para que 'poslog.js' busque el producto automáticamente
            posSearchInput.dispatchEvent(new Event('input', { bubbles: true }));
            
            // Cerramos y vibramos
            if (navigator.vibrate) navigator.vibrate(100);
            stopScanner();
        }
    ).catch(err => {
        console.error("Error de cámara:", err);
        stopScanner();
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            document.getElementById('scanner-modal').style.display = 'none';
        }).catch(() => {
            document.getElementById('scanner-modal').style.display = 'none';
        });
    } else {
        document.getElementById('scanner-modal').style.display = 'none';
    }
}

// Función para el botón del proyecto g502
function abrirProyectoG502() {
    const urls = [
        'https://donjorgito.shop/g502/pdf/index1.php',
        'https://donjorgito.shop/g502/pdf/',
        'https://donjorgito.shop/g502/plu/dbplus.php',
        'https://donjorgito.shop/g502/bread1/'
    ];
    urls.forEach(url => window.open(url, '_blank'));
}
</script>
</body>
</html>
