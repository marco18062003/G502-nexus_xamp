<?php
/**
 * G502 - Smart IA Scanner con Google Vision
 * Proyecto: Nexus g502
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php'; 

$errorMsg = ""; 
$successMsg = "";
$extractedData = []; 
require_once '../config/keys.php';
$apiKey = GOOGLE_VISION_KEY;

// ─── Líneas a ignorar siempre (encabezados, totales, promociones) ──────────
function esLineaValida($linea) {
    // Encabezados / metadatos de factura
    if (preg_match('/NIT|TEL|FECHA|TOTAL|IVA|FACTURA|C\.C|PAGINA|SUBTOTAL/i', $linea)) return false;
    // Líneas de promoción tipo "PGUE 1LLEVE 2 MA" (pague 1 lleve 2, etc.)
    if (preg_match('/PGUE|LLEVE|DESCTO|DSCTO|PROMO/i', $linea)) return false;
    return true;
}

// ─── Busca el nombre real del producto en la base de datos ─────────────────
function buscarNombreProducto($conn, $plu) {
    $lookupStmt = $conn->prepare("SELECT NOMBRE FROM Hoja1 WHERE PLU = ? OR EAN = ? LIMIT 1");
    $lookupStmt->bind_param("ss", $plu, $plu);
    $lookupStmt->execute();
    $lookupRow = $lookupStmt->get_result()->fetch_assoc();
    $lookupStmt->close();
    return $lookupRow ? $lookupRow['NOMBRE'] : null;
}

// ─── Procesa el texto de una página/imagen y llena $extractedData ──────────
function procesarTexto($fullText, $conn, $stmt, $tipo, &$extractedData) {
    $lines = explode("\n", $fullText);

    foreach ($lines as $linea) {
        $linea = trim($linea);
        if ($linea === '') continue;
        if (!esLineaValida($linea)) continue;

        if (preg_match('/\b(\d{4,15})\b/', $linea, $m)) {
            $plu = $m[1];

            // Descarta NITs / códigos de identificación tributaria (prefijos 800/890/900/901 largos)
            if (preg_match('/^(800|890|900|901)/', $plu) && strlen($plu) > 8) continue;

            // Evita duplicados
            if (in_array($plu, array_column($extractedData, 'plu'))) continue;

            // 1) Intenta traer el nombre real desde la base de datos
            $name = buscarNombreProducto($conn, $plu);

            // 2) Si no existe en la BD, usa el texto de la línea (quitando el código)
            if (!$name) {
                $textoLinea = trim(preg_replace('/[:\-_|.]/', '', str_replace($plu, '', $linea)));
                $name = (strlen($textoLinea) >= 3) ? $textoLinea : "Producto Desconocido ($plu)";
            }

            $extractedData[] = ['plu' => $plu, 'nombre' => $name];

            $cat = 'General';
            $qty = 1;
            $desc = 'Escaneado por IA G502';
            $st = 'Activo';
            $stmt->bind_param("sssisss", $name, $plu, $cat, $qty, $desc, $st, $tipo);
            $stmt->execute();
        }
    }
}

if (isset($_POST['scan'])) {
    if (!isset($_FILES['fileToScan']) || $_FILES['fileToScan']['error'] == UPLOAD_ERR_NO_FILE) {
        $errorMsg = "❌ Por favor, selecciona un archivo primero.";
    } else {
        $tempFile = $_FILES['fileToScan']['tmp_name'];
        $fileType = $_FILES['fileToScan']['type'];

        // ─── PDF: todas las páginas ───────────────────────────────────────────
        if ($fileType == 'application/pdf') {
            if (!class_exists('Imagick')) {
                $errorMsg = "❌ El servidor no tiene Imagick instalado.";
            } else {
                try {
                    $imgCount = new Imagick();
                    $imgCount->pingImage($tempFile);
                    $totalPages = $imgCount->getNumberImages();
                    $imgCount->destroy();

                    $stmt = $conn->prepare("INSERT INTO productos_ia (name, plu_code, category, quantity, description, state, tipo) VALUES (?, ?, ?, ?, ?, ?, ?)");

                    for ($page = 0; $page < $totalPages; $page++) {
                        $img = new Imagick();
                        $img->setResolution(300, 300);
                        $img->readImage($tempFile . '[' . $page . ']');
                        $img->setImageFormat('jpg');
                        $img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
                        $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                        $imageData = base64_encode($img->getImageBlob());
                        $img->destroy();

                        $payload = json_encode([
                            "requests" => [[
                                "image" => ["content" => $imageData],
                                "features" => [["type" => "TEXT_DETECTION"]],
                                "imageContext" => ["languageHints" => ["es"]]
                            ]]
                        ]);

                        $ch = curl_init("https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                        $resData = json_decode(curl_exec($ch), true);
                        curl_close($ch);

                        if (!isset($resData['responses'][0]['fullTextAnnotation']['text'])) continue;

                        procesarTexto(
                            $resData['responses'][0]['fullTextAnnotation']['text'],
                            $conn,
                            $stmt,
                            'PDF',
                            $extractedData
                        );
                    }

                    $stmt->close();
                    $successMsg = "✅ " . count($extractedData) . " productos detectados en $totalPages página(s).";

                } catch (Exception $e) {
                    $errorMsg = "❌ Error procesando PDF: " . $e->getMessage();
                }
            }

        // ─── IMAGEN ───────────────────────────────────────────────────────────
        } else {
            $imageData = base64_encode(file_get_contents($tempFile));

            $payload = json_encode([
                "requests" => [[
                    "image" => ["content" => $imageData],
                    "features" => [["type" => "TEXT_DETECTION"]],
                    "imageContext" => ["languageHints" => ["es"]]
                ]]
            ]);

            $ch = curl_init("https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $rawResponse = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);
            $resData = json_decode($rawResponse, true);

            // 🔍 DEBUG opcional — visita la página con ?debug=1 si necesitas
            // volver a inspeccionar la respuesta cruda de Google Vision.
            if (isset($_GET['debug'])) {
                echo "<pre style='background:#000;color:#0f0;padding:15px;overflow:auto;'>";
                echo "cURL error: " . ($curlErr ?: "ninguno") . "\n\n";
                echo "Respuesta cruda de Google Vision:\n";
                echo htmlspecialchars($rawResponse);
                echo "</pre>";
            }

            if (isset($resData['responses'][0]['fullTextAnnotation']['text'])) {
                $stmt = $conn->prepare("INSERT INTO productos_ia (name, plu_code, category, quantity, description, state, tipo) VALUES (?, ?, ?, ?, ?, ?, ?)");

                procesarTexto(
                    $resData['responses'][0]['fullTextAnnotation']['text'],
                    $conn,
                    $stmt,
                    'IMG',
                    $extractedData
                );

                $stmt->close();
                $successMsg = "✅ " . count($extractedData) . " productos detectados.";
            } else {
                $errorMsg = "⚠️ No se detectó texto legible.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G502 | IA Smart Scanner</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root { --primary: #2563eb; --bg: #f1f5f9; --text: #0f172a; --accent: #10b981; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 15px; }
        .container { max-width: 600px; margin: auto; }
        .card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 15px; }
        h1 { font-size: 1.5rem; text-align: center; margin-bottom: 5px; }
        .header-desc { text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 20px; }

        .btn { padding: 12px 20px; border-radius: 10px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s; font-size: 14px; }
        .btn-primary { background: var(--primary); color: white; width: 100%; display: block; }
        .btn-outline { background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; }
        .btn-copy { background: var(--accent); color: white; }
        .btn-success { background: #22c55e; color: white; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; padding: 10px; }
        td { padding: 8px 10px; border-top: 1px solid #f1f5f9; vertical-align: middle; }
        .badge { background: #e2e8f0; padding: 4px 8px; border-radius: 6px; font-family: 'Courier New', monospace; font-weight: 700; }

        .upload-bar { display: flex; gap: 10px; align-items: center; padding: 15px; border-bottom: 2px solid #f1f5f9; flex-wrap: wrap; }
        .upload-bar select { flex: 1; min-width: 150px; padding: 10px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 600; background: #f8fafc; color: #475569; cursor: pointer; }
        .upload-bar .btn-primary { width: auto; }

        .inline-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px; font-size: 13px; box-sizing: border-box; }
        input[type="number"].inline-input { text-align: center; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; padding: 25px; border-radius: 24px; width: 90%; max-width: 400px; text-align: center; }
        .barcode-view { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 15px 0; }
        #barcode { width: 100%; height: auto; }
        .plu-large { font-size: 2.5rem; font-weight: 800; margin: 10px 0; letter-spacing: 2px; }

        /* Responsividad Móvil */
        @media (max-width: 480px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; }
            tr { margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 12px; background: white; padding: 10px; }
            td { border: none; display: flex; justify-content: space-between; align-items: center; padding: 5px; }
            td::before { content: attr(data-label); font-weight: 600; font-size: 11px; color: #94a3b8; }
            .btn-group { width: 100%; display: flex; gap: 8px; margin-top: 10px; }
            .btn-group button { flex: 1; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card" style="text-align: center;">
        <h1>🚀 g502 Smart IA</h1>
        <p class="header-desc">Scanner de Facturas y Generador de Barras</p>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="fileToScan" id="file" accept="image/*,application/pdf" hidden required>
            <label for="file" class="btn btn-outline" style="display:block; margin-bottom:10px;">📁 Seleccionar Archivo</label>
            <button type="submit" name="scan" class="btn btn-primary">🔍 Iniciar Escaneo</button>
        </form>
    </div>

    <?php if ($errorMsg): ?>
    <div style="color:#b91c1c; background:#fee2e2; padding:12px; border-radius:10px; margin-bottom:10px;"><?php echo $errorMsg; ?></div>
    <?php endif; ?>

    <?php if ($successMsg): ?>
    <div style="color:#15803d; background:#dcfce7; padding:12px; border-radius:10px; margin-bottom:10px;"><?php echo $successMsg; ?></div>
    <?php endif; ?>

    <?php if (!empty($extractedData)): ?>
    <div class="card" style="padding:0;">

        <!-- Barra de acción: FUERA del loop, ENCIMA de la tabla -->
        <div class="upload-bar">
            <input type="checkbox" id="selectAll" style="width:18px;height:18px;cursor:pointer;" title="Seleccionar todos">
            <select id="categorySelect">
                <option value="">-- Categoría --</option>
                <option value="Carnes">Carnes</option>
                <option value="Fruver">Fruver</option>
                <option value="Deli">Deli</option>
                <option value="Panaderia">Panadería</option>
                <option value="MERCADERISTA">Mercaderista</option>
                <option value="G502">G502</option>
                <option value="Jefe">Jefe</option>
            </select>
            <button class="btn btn-primary" style="width:auto;" onclick="uploadSelected()">📤 Subir Seleccionados</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($extractedData as $p): ?>
                <tr>
                    <td data-label="SUBIR">
                        <input type="checkbox"
                               value="<?php echo htmlspecialchars($p['plu']); ?>"
                               data-nombre="<?php echo htmlspecialchars($p['nombre']); ?>"
                               class="product-check"
                               style="width:18px;height:18px;cursor:pointer;">
                    </td>
                    <td data-label="CÓDIGO"><span class="badge"><?php echo htmlspecialchars($p['plu']); ?></span></td>
                    <td data-label="PRODUCTO" style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td data-label="CANT." style="width:70px;">
                        <input type="number" min="1" value="1" class="inline-input qty-input">
                    </td>
                    <td data-label="DESC.">
                        <input type="text" placeholder="Nota..." class="inline-input desc-input">
                    </td>
                    <td data-label="OPCIONES">
                        <div class="btn-group">
                            <button class="btn btn-outline" onclick="openBarcode('<?php echo htmlspecialchars($p['plu']); ?>')">👁️</button>
                            <button class="btn btn-copy" onclick="copyText('<?php echo htmlspecialchars($p['plu']); ?>', this)">📋</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para Código de Barras -->
<div id="barcodeModal" class="modal">
    <div class="modal-content">
        <h2 style="margin:0; font-size:1.2rem;">Visor g502</h2>
        <div style="display:flex; gap:5px; margin: 15px 0;">
            <button class="btn btn-primary" style="font-size:11px;" onclick="render('CODE128')">Standard</button>
            <button class="btn btn-primary" style="font-size:11px; background:#f59e0b;" onclick="render('EAN13')">Panadería</button>
        </div>
        <div class="barcode-view">
            <svg id="barcode"></svg>
            <div id="pluLabel" class="plu-large"></div>
        </div>
        <button class="btn btn-outline" style="width:100%;" onclick="closeModal()">Cerrar</button>
    </div>
</div>

<script>
let currentCode = "";

// ─── Calcula el dígito de control EAN13 ──────────────────────────────────────
function calcEAN13Checksum(code12) {
    let sum = 0;
    for (let i = 0; i < 12; i++) {
        sum += parseInt(code12[i]) * (i % 2 === 0 ? 1 : 3);
    }
    return (10 - (sum % 10)) % 10;
}

function openBarcode(code) {
    currentCode = String(code).trim();
    document.getElementById('barcodeModal').style.display = 'flex';
    let format = (currentCode.replace(/\D/g, '').length >= 12 && !isNaN(currentCode.replace(/\D/g, ''))) ? 'EAN13' : 'CODE128';
    render(format);
}

function render(type) {
    let codeToRender = currentCode.replace(/\D/g, '');

    if (type === 'EAN13') {
        // Asegurar exactamente 12 dígitos base
        if (codeToRender.length > 12) {
            codeToRender = codeToRender.substring(0, 12);
        } else {
            codeToRender = codeToRender.padStart(12, '0');
        }
        // Calcular y agregar dígito de control correcto
        const check = calcEAN13Checksum(codeToRender);
        codeToRender = codeToRender + check;
    }

    try {
        JsBarcode("#barcode", codeToRender, {
            format: type,
            width: 2.5,
            height: 70,
            displayValue: false,
            fontSize: 20
        });
        document.getElementById('pluLabel').innerText = codeToRender;
    } catch (e) {
        // Fallback a CODE128 si EAN13 falla
        JsBarcode("#barcode", codeToRender, {
            format: "CODE128",
            width: 2.5,
            height: 70,
            displayValue: false
        });
        document.getElementById('pluLabel').innerText = codeToRender;
    }
}

function closeModal() {
    document.getElementById('barcodeModal').style.display = 'none';
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const old = btn.innerHTML;
        btn.innerHTML = "✅";
        setTimeout(() => btn.innerHTML = old, 1000);
    });
}

window.onclick = function(e) {
    if (e.target.id === 'barcodeModal') closeModal();
}

// Fix: solo engancha el listener si el checkbox existe en el DOM
// (no existe cuando todavía no hay resultados escaneados)
const selectAllCheckbox = document.getElementById('selectAll');
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        document.querySelectorAll('.product-check').forEach(cb => cb.checked = this.checked);
    });
}

async function uploadSelected() {
    const category = document.getElementById('categorySelect').value;
    if (!category) { alert('⚠️ Selecciona una categoría primero.'); return; }

    const checked = [...document.querySelectorAll('.product-check:checked')];
    if (checked.length === 0) { alert('⚠️ Selecciona al menos un producto.'); return; }

    const products = checked.map(cb => {
        const row = cb.closest('tr');
        return {
            plu: cb.value,
            nombre: cb.dataset.nombre,
            qty: row.querySelector('.qty-input').value || 1,
            desc: row.querySelector('.desc-input').value || 'Escaneado por IA G502'
        };
    });

    const res = await fetch('upload_productos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category, products })
    });

    const data = await res.json();
    alert(data.message);
}

</script>

</body>
</html>