<?php 
require_once '../config/db.php'; 
date_default_timezone_set('America/Bogota'); 

// PROCESAMIENTO DEL FORMULARIO
if(isset($_POST['save_excel'])){
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $plus = $_POST['plu'];
    $qtys = $_POST['qty'];
    $descs = $_POST['desc'];
    $tipos = $_POST['tipo']; 
    $count = 0;
    
    $current_time = date('Y-m-d H:i:s');

    for($i=0; $i < count($plus); $i++) {
        $plu = trim($plus[$i]);
        if(empty($plu)) continue;

        $qty = !empty($qtys[$i]) ? (int)$qtys[$i] : 1;
        $desc = !empty($descs[$i]) ? mysqli_real_escape_string($conn, $descs[$i]) : 'Sin descripción';
        $tipo = !empty($tipos[$i]) ? mysqli_real_escape_string($conn, $tipos[$i]) : ''; 
        $name = "Item " . $plu;

        $sql = "INSERT INTO plu_products (name, plu_code, category, quantity, description, state, tipo, created_at) 
                VALUES ('$name', '$plu', '$cat', '$qty', '$desc', '', '$tipo', '$current_time')";
        
        if($conn->query($sql)) { $count++; }
    }
    
    if($count > 0) {
        $msg = "✔ ¡Éxito! $count productos cargados en $cat.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>g502 | Carga Masiva con Escáner</title>
    <style>
        :root { --primary: #1a73e8; --success: #28a745; --bg: #f0f2f5; --accent: #7c3aed; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 10px; }
        .card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.05); max-width: 900px; margin: auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .step-title { font-weight: bold; margin: 15px 0 8px; display: block; color: #444; }
        select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; box-sizing: border-box; }
        .excel-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .excel-table th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 8px; border: 1px solid #e2e8f0; }
        .excel-table td { border: 1px solid #e2e8f0; padding: 0; position: relative; }
        .excel-table input { width: 100%; border: none; padding: 12px; box-sizing: border-box; outline: none; font-size: 1rem; }
        
        .plu-cell { display: flex; align-items: center; background: white; }
        .btn-scan-trigger { border: none; background: #f1f5f9; padding: 10px; cursor: pointer; border-left: 1px solid #e2e8f0; transition: 0.2s; }

        @media screen and (max-width: 600px) {
            .excel-table thead { display: none; }
            .excel-table tr { display: block; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: white; }
            .excel-table td { display: block; border: none; border-bottom: 1px solid #eee; }
            .excel-table td::before { content: attr(data-label); font-size: 0.7rem; color: #999; padding-left: 10px; display: block; padding-top: 5px; text-transform: uppercase; font-weight: bold; }
        }

        .btn-add { width: 100%; background: #e2e8f0; border: none; padding: 12px; border-radius: 8px; cursor: pointer; margin-top: 10px; font-weight: bold; }
        .btn-submit { width: 100%; background: var(--success); color: white; border: none; padding: 15px; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 25px; }
        
        /* Modal Escáner Optimizado */
        #scanner-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.95); z-index: 9999; flex-direction: column; align-items: center; justify-content: center;
        }
        #reader { width: 100%; max-width: 400px; border-radius: 15px; overflow: hidden; border: 4px solid var(--accent); background: #000; }
        .btn-close-scanner { margin-top: 25px; background: #ff4444; color: white; border: none; padding: 15px 40px; border-radius: 50px; font-weight: bold; }
    </style>
</head>
<body>

<datalist id="productList"></datalist>

<div id="scanner-modal">
    <div id="reader"></div>
    <button type="button" class="btn-close-scanner" onclick="stopScanner()">CERRAR CÁMARA</button>
</div>

<div class="card">
    <div class="header">
        <h2>g502 | Carga Masiva</h2>
    </div>

    <?php if(isset($msg)) echo "<div style='padding:15px; background:#d4edda; color:#155724; border-radius:8px; margin-bottom:20px; text-align:center;'>$msg</div>"; ?>

    <form method="POST">
        <label class="step-title">1. Departamento</label>
        <select name="category" required>
            <option value="" disabled selected>-- Elegir --</option>
            <option value="CARNES">🥩 CARNES</option>
            <option value="FRUVER">🍎 FRUVER</option>
            <option value="DELI">🧀 DELI</option>
            <option value="PANADERIA">🥖 PANADERIA</option>
            <option value="MERCADERISTA">👨 MERCADERISTA</option>
        </select>

        <label class="step-title">2. Datos de Productos</label>
        <table class="excel-table">
            <thead>
                <tr>
                    <th style="width: 35%;">PLU / EAN</th>
                    <th style="width: 15%;">Cant.</th>
                    <th>Descripción</th>
                    <th style="width: 25%;">Tipo</th>
                </tr>
            </thead>
            <tbody id="excelBody">
                <?php for($i=0; $i<3; $i++): ?>
                <tr>
                    <td data-label="PLU">
                        <div class="plu-cell">
                            <input type="text" name="plu[]" class="plu-input" list="productList" autocomplete="off">
                            <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
                        </div>
                    </td>
                    <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
                    <td data-label="Descripción"><input type="text" name="desc[]"></td>
                    <td data-label="Tipo">
                        <select name="tipo[]" style="border:none; padding:10px;">
                            <option value="">--</option>
                            <option value="diamante">Diamante</option>
                        </select>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        
        <button type="button" class="btn-add" onclick="addRow()">+ Añadir fila</button>
        <button type="submit" name="save_excel" class="btn-submit"> SUBIR AL SISTEMA</button>
    </form>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let html5QrCode;
let activeInput = null;

function startScanner(button) {
    activeInput = button.parentElement.querySelector('.plu-input');
    document.getElementById('scanner-modal').style.display = 'flex';
    
    // Evitar múltiples instancias
    if(html5QrCode) { html5QrCode.clear(); }

    html5QrCode = new Html5Qrcode("reader");
    
    const config = { 
        fps: 20, 
        qrbox: { width: 280, height: 160 }, // Tamaño ideal para EAN-13
        aspectRatio: 1.0,
        formatsToSupport: [ 
            Html5QrcodeSupportedFormats.EAN_13, 
            Html5QrcodeSupportedFormats.EAN_8, 
            Html5QrcodeSupportedFormats.QR_CODE 
        ]
    };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        (decodedText) => {
            activeInput.value = decodedText;
            activeInput.dispatchEvent(new Event('input', { bubbles: true }));
            stopScanner();
            if (navigator.vibrate) navigator.vibrate(100);
        }
    ).then(() => {
        // TRUCO PARA iPHONE: Forzar playsinline en el video generado
        const video = document.querySelector('#reader video');
        if (video) {
            video.setAttribute('playsinline', 'true');
            video.play();
        }
    }).catch(err => {
        alert("Error de cámara: " + err + ". Asegúrate de usar HTTPS.");
        stopScanner();
    });
}

function stopScanner() {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => {
            document.getElementById('scanner-modal').style.display = 'none';
        }).catch(() => {
            document.getElementById('scanner-modal').style.display = 'none';
        });
    } else {
        document.getElementById('scanner-modal').style.display = 'none';
    }
}

function addRow() {
    const tbody = document.getElementById('excelBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td data-label="PLU">
            <div class="plu-cell">
                <input type="text" name="plu[]" class="plu-input" list="productList" autocomplete="off">
                <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
            </div>
        </td>
        <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
        <td data-label="Descripción"><input type="text" name="desc[]"></td>
        <td data-label="Tipo"><select name="tipo[]" style="border:none; padding:10px;"><option value="">--</option></select></td>
    `;
    tbody.appendChild(newRow);
}
</script>
</body>
</html>
