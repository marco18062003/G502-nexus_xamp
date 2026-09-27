<?php

require_once '../config/db.php'; 

// Evita el warning "Undefined variable $mensaje_usuario"
// cuando la página se carga sin haber procesado el formulario todavía.
$mensaje_usuario = '';

// ----------------------------------------------------------------
// Procesar el formulario cuando se envía por POST
// ----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Recoger y limpiar los campos de texto
    $ca             = mysqli_real_escape_string($conn, trim($_POST['ca'] ?? ''));
    $marca          = mysqli_real_escape_string($conn, trim($_POST['marca'] ?? ''));
    $producto       = mysqli_real_escape_string($conn, trim($_POST['producto'] ?? ''));
    $caracteristica = mysqli_real_escape_string($conn, trim($_POST['caracteristica'] ?? ''));
    $ean            = mysqli_real_escape_string($conn, trim($_POST['ean'] ?? ''));
    $precio         = mysqli_real_escape_string($conn, trim($_POST['precio'] ?? ''));
    $cantidad       = mysqli_real_escape_string($conn, trim($_POST['cantidad'] ?? ''));
    $estado         = mysqli_real_escape_string($conn, trim($_POST['estado'] ?? ''));

    $imagenNombre = '';
    $errorImagen  = false;

    // 2. Procesar la imagen subida
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {

        // Carpeta física donde se guardan las imágenes (misma ruta que usa buscar.php: ../Donjorgitofinal/)
        $carpetaDestino = __DIR__ . '/../Donjorgitofinal/';

        // Si la carpeta no existe, la creamos
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        $extension       = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $extPermitidas   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($extension, $extPermitidas)) {
            // Nombre único para evitar sobrescrituras
            $imagenNombre = uniqid('prod_') . '.' . $extension;
            $rutaDestino  = $carpetaDestino . $imagenNombre;

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $errorImagen = true;
            }
        } else {
            $errorImagen = true;
        }
    } else {
        $errorImagen = true;
    }

    // 3. Validar que los campos obligatorios estén presentes
    if ($ca === '' || $marca === '' || $producto === '' || $caracteristica === '' || $precio === '' || $cantidad === ''|| $estado === '') {
        $mensaje_usuario = '<div class="mensaje error">⚠️ Por favor completa todos los campos obligatorios.</div>';
    } elseif ($errorImagen) {
        $mensaje_usuario = '<div class="mensaje error">⚠️ No se pudo subir la imagen. Verifica el formato (jpg, jpeg, png, webp, gif).</div>';
    } else {
        // 4. Insertar en la base de datos
        $sqlInsert = "INSERT INTO donjorgito1 (ca, marca, producto, caracteristica, ean, precio, cantidad, imagen, estado)
                      VALUES ('$ca', '$marca', '$producto', '$caracteristica', '$ean', '$precio', '$cantidad', '$imagenNombre','$estado' )";

        if (mysqli_query($conn, $sqlInsert)) {
            $mensaje_usuario = '<div class="mensaje exito">✅ Producto registrado correctamente.</div>';
        } else {
            $mensaje_usuario = '<div class="mensaje error">❌ Error al guardar en la base de datos: ' . htmlspecialchars(mysqli_error($conn)) . '</div>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Productos</title>

    <!-- Librería para escanear códigos de barras/QR -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        :root {
            --gold: #d4af37;
            --gold-light: #f4e5b2;
            --gold-dark: #a8860f;
            --black: #0d0d0d;
            --black-soft: #1a1a1a;
            --gray-dark: #2b2b2b;
            --white: #ffffff;
        }

        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: var(--black);
            color: var(--gold-light);
        }
        form {
            background: var(--black-soft);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--gold-dark);
            box-shadow: 0 4px 18px rgba(212, 175, 55, 0.15);
            max-width: 450px;
            margin: 30px auto;
        }
        h2 {
            text-align: center;
            color: var(--gold);
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: var(--gold-light); }
        input[type="text"], select, input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--gold-dark);
            border-radius: 4px;
            background-color: var(--black);
            color: var(--white);
        }
        input[type="text"]::placeholder { color: #888; }
        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 6px rgba(212, 175, 55, 0.5);
        }
        select option { background-color: var(--black); color: var(--white); }

        input[type="file"] {
            padding: 8px;
            color: var(--gold-light);
        }

        input[type="submit"] {
            background: linear-gradient(90deg, var(--gold-dark), var(--gold));
            color: var(--black);
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
            transition: filter 0.3s ease, transform 0.15s ease;
        }
        input[type="submit"]:hover { filter: brightness(1.1); transform: scale(1.01); }

        .mensaje { margin: 20px auto; padding: 12px; border-radius: 5px; text-align: center; font-weight: bold; max-width: 450px; }
        .exito { background-color: #143d1f; color: var(--gold); border: 1px solid var(--gold-dark); }
        .error { background-color: #3d1414; color: #ff8080; border: 1px solid #8a2b2b; }

        a { color: var(--gold) !important; }
        a:hover { color: var(--gold-light) !important; }

        /* EAN input con botón de cámara */
        .ean-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 15px;
        }
        .ean-wrapper input {
            flex: 1;
            margin-bottom: 0;
        }
        .btn-scan {
            padding: 10px 14px;
            font-size: 20px;
            border: 1px solid var(--gold-dark);
            border-radius: 4px;
            background: var(--black);
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
            white-space: nowrap;
        }
        .btn-scan:hover { background: var(--gold-dark); border-color: var(--gold); }

        /* Modal del escáner */
        #scanner-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        #scanner-modal.active { display: flex; }
        .scanner-box {
            background: var(--black-soft);
            border: 1px solid var(--gold-dark);
            border-radius: 12px;
            padding: 20px;
            width: 320px;
            max-width: 95vw;
            text-align: center;
        }
        .scanner-box h3 { margin: 0 0 15px; color: var(--gold); }
        #reader { width: 100%; border-radius: 8px; overflow: hidden; }
        #btn-close-scanner {
            margin-top: 15px;
            padding: 10px 24px;
            background: #8a2b2b;
            color: var(--white);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            font-weight: 600;
        }
        #btn-close-scanner:hover { background: #a33333; }
        #scanner-status { margin-top: 10px; font-size: 13px; color: var(--gold-light); min-height: 20px; }
    </style>
</head>
<body>

<?php echo $mensaje_usuario; ?>

<!-- MODAL DEL ESCÁNER -->
<div id="scanner-modal">
    <div class="scanner-box">
        <h3>📷 Escanear EAN</h3>
        <div id="reader"></div>
        <p id="scanner-status">Apunta la cámara al código de barras</p>
        <button id="btn-close-scanner" onclick="stopScanner()">✕ Cancelar</button>
    </div>
</div>

<form action="" method="POST" enctype="multipart/form-data">
    <p><a href="../admin/index.php" style="text-decoration:none;">← Volver al Dashboard</a></p>
    <h2>Registro de Productos</h2>

    <div>
        <label for="ca">Categoría:</label>
        <select id="ca" name="ca" required>
            <option value="">Selecciona una categoría</option>
            <option value="aguardiente">Aguardiente</option>
            <option value="aperitivo">Aperitivo</option>
            <option value="bebes">Bebes</option>
            <option value="bebidas">Bebidas</option>
            <option value="bebidas_instantaneas">Bebidas instantáneas</option>
            <option value="brandy">Brandy</option>
            <option value="canasta_familiar">Canasta familiar/Despensa</option>
            <option value="carnes frias">Carnes frías</option>
            <option value="cereales">Cereales</option>
            <option value="cerveza">Cerveza</option>
            <option value="champaña">Champaña</option>
            <option value="chocolates">Chocolates</option>
            <option value="cigarrillos">Cigarrillos</option>
            <option value="comidas_instantaneas">Comidas instantáneas</option>
            <option value="cremas">Cremas de whisky</option>
            <option value="desechables">Desechables</option>
            <option value="dulces">Dulces</option>
            <option value="galletas">Galletas</option>
            <option value="gomas">Gomas</option>
            <option value="lacteos">Lácteos</option>
            <option value="limpieza">Limpieza</option>
            <option value="pm">Mujer</option>
            <option value="ph">Hombre</option>
            <option value="otros">Otros</option>
            <option value="panaderia">Panadería</option>
            <option value="pasabocas">Pasabocas</option>
            <option value="ponques">Ponqués</option>
            <option value="promociones">Promociones</option>
            <option value="ron">Ron</option>
            <option value="tequila">Tequila</option>
            <option value="vinos">Vinos</option>
            <option value="whisky">Whisky</option>
        </select>
    </div>

    <div>
        <label for="marca">Marca:</label>
        <input type="text" id="marca" name="marca" required>
    </div>

    <div>
        <label for="producto">Producto:</label>
        <input type="text" id="producto" name="producto" required>
    </div>

    <div>
        <label for="caracteristica">Características:</label>
        <input type="text" id="caracteristica" name="caracteristica" required>
    </div>

    <!-- EAN con botón de escaneo -->
    <div>
        <label for="ean">EAN (código de barras):</label>
        <div class="ean-wrapper">
            <input type="text" id="ean" name="ean" placeholder="Ej: 7702001234567" autocomplete="off">
            <button type="button" class="btn-scan" onclick="startScanner()" title="Escanear con cámara">📷</button>
        </div>
    </div>

    <div>
        <label for="precio">Precio:</label>
        <input type="text" id="precio" name="precio" required>
    </div>

    <div>
        <label for="cantidad">Cantidad:</label>
        <input type="text" id="cantidad" name="cantidad" required>
    </div>
    
    <div>
        <label for="estado">Estado:</label>
        <select id="estado" name="estado" required>
            <option value="">Selecciona un estado</option>
            <option value="activo">activo</option>
            <option value="descontinuado">descontinuado</option>
            <option value="agotado">agotado</option>            
        </select>
    </div>

    <div>
        <label for="imagen">Seleccionar Imagen:</label>
        <input type="file" id="imagen" name="imagen" accept="image/*" required>
    </div>

    <div>
        <input type="submit" value="Registrar Producto">
    </div>
</form>

<script>
    let html5QrCode = null;

    function startScanner() {
        document.getElementById('scanner-modal').classList.add('active');
        document.getElementById('scanner-status').textContent = 'Iniciando cámara...';

        html5QrCode = new Html5Qrcode("reader");

        const config = {
            fps: 15,
            qrbox: { width: 250, height: 120 },
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.QR_CODE
            ]
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                // Código leído exitosamente
                document.getElementById('ean').value = decodedText;
                document.getElementById('scanner-status').textContent = '✅ Código: ' + decodedText;
                if (navigator.vibrate) navigator.vibrate(150);
                // Cerrar automáticamente después de 800ms para que el usuario vea el resultado
                setTimeout(() => stopScanner(), 800);
            }
        ).then(() => {
            document.getElementById('scanner-status').textContent = 'Apunta la cámara al código de barras';
        }).catch(err => {
            document.getElementById('scanner-status').textContent = '❌ Error: ' + err;
            console.error(err);
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(err => console.warn("Stop error:", err));
        }
        document.getElementById('scanner-modal').classList.remove('active');
    }

    // Cerrar modal si se hace clic fuera del recuadro
    document.getElementById('scanner-modal').addEventListener('click', function(e) {
        if (e.target === this) stopScanner();
    });
</script>

</body>
</html>