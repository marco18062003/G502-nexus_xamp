<?php 
require_once '../config/db.php'; 
date_default_timezone_set('America/Bogota'); 

if(isset($_POST['save_excel'])){
    $cat   = $_POST['category'];
    $plus  = $_POST['plu'];
    $qtys  = $_POST['qty'];
    $descs = $_POST['desc'];
    $tipos = $_POST['tipo'] ?? []; 
    $dates = $_POST['date']; 
    $count = 0;
    
    $current_time = date('Y-m-d H:i:s');

    // Declare the variables BEFORE binding them
    $name = '';
    $plu = '';
    $qty = 0;
    $desc = '';
    $tipo = '';
    $vence = null;

    $stmt = $conn->prepare(
        "INSERT INTO plu_vencer (name, plu_code, category, quantity, description, state, tipo, created_at, fecha_vence) 
         VALUES (?, ?, ?, ?, ?, '', ?, ?, ?)"
    );
    $stmt->bind_param('sssissss', $name, $plu, $cat, $qty, $desc, $tipo, $current_time, $vence);

    for($i=0; $i < count($plus); $i++) {
        $plu = trim($plus[$i]);
        if(empty($plu)) continue;

        $qty   = !empty($qtys[$i]) ? (int)$qtys[$i] : 1;
        $desc  = !empty($descs[$i]) ? $descs[$i] : 'Sin descripción';
        $tipo  = !empty($tipos[$i]) ? $tipos[$i] : '';
        $vence = !empty($dates[$i]) ? $dates[$i] : null;
        $name  = "Item " . $plu;

        if($stmt->execute()) { $count++; }
    }

    if($count > 0) { $msg = "✔ ¡Éxito! $count productos cargados en $cat."; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>g502 | Carga Masiva</title>
    <style>
        :root { --primary: #1a73e8; --success: #28a745; --bg: #f0f2f5; --accent: #7c3aed; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 10px; }
        .card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.05); max-width: 900px; margin: auto; }
        .header { text-align: center; margin-bottom: 20px; }
        .step-title { font-weight: bold; margin: 15px 0 8px; display: block; color: #444; }
        select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; }
        .excel-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .excel-table th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 8px; border: 1px solid #e2e8f0; }
        .excel-table td { border: 1px solid #e2e8f0; padding: 0; position: relative; }
        .excel-table input { width: 100%; border: none; padding: 12px; box-sizing: border-box; outline: none; font-size: 1rem; }
        .plu-cell { display: flex; align-items: center; background: white; }
        .btn-scan-trigger { border: none; background: #f1f5f9; padding: 10px; cursor: pointer; border-left: 1px solid #e2e8f0; }
        .btn-add { width: 100%; background: #e2e8f0; border: none; padding: 12px; border-radius: 8px; cursor: pointer; margin-top: 10px; font-weight: bold; }
        .btn-submit { width: 100%; background: var(--success); color: white; border: none; padding: 15px; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 25px; }
        #scanner-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; flex-direction: column; align-items: center; justify-content: center; }
        #reader { width: 320px; border-radius: 15px; overflow: hidden; border: 4px solid var(--accent); }
        .btn-close-scanner { margin-top: 20px; background: white; border: none; padding: 12px 25px; border-radius: 50px; font-weight: bold; }
        .btn-master { background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: white; border: none; padding: 15px 30px; border-radius: 50px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        @media screen and (max-width: 600px) {
            .excel-table thead { display: none; }
            .excel-table tr { display: block; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
            .excel-table td { display: block; border: none; border-bottom: 1px solid #eee; }
            .excel-table td::before { content: attr(data-label); font-size: 0.7rem; color: #999; padding: 5px 10px 0; display: block; text-transform: uppercase; font-weight: bold; }
        }
    </style>
</head>
<body>

<datalist id="productList"></datalist>

<div id="scanner-modal">
    <div id="reader"></div>
    <button type="button" class="btn-close-scanner" onclick="stopScanner()">CANCELAR ESCÁNER</button>
</div>

<div class="card">
    <div class="header"><h2>g502 | PROXIMOS A VENCER</h2></div>

    <?php if(isset($msg)) echo "<div style='padding:15px; background:#d4edda; color:#155724; border-radius:8px; text-align:center; margin-bottom:20px;'>$msg</div>"; ?>

    <form method="POST">
        <label class="step-title">1. Departamento</label>
        <select name="category" required>
            <option value="" disabled selected>-- Elegir --</option>
            <option value="CARNES">🥩 CARNES</option>
            <option value="FRUVER">🍎 FRUVER</option>
            <option value="DELI">🧀 DELI</option>
            <option value="PANADERIA">🥖 PANADERIA</option>
            <option value="MERCADERISTA">👨 MERCADERISTA</option>
            <option value="G502">☠️ G502</option>
            <option value="JEFE">💎 JEFE</option>
            <option value="POP">📜 POP</option>
            <option value="PGS">📦 PGS</option>
        </select>

        <label class="step-title">2. Datos de Productos</label>
        <table class="excel-table">
            <thead>
                <tr>
                    <th style="width: 35%;">PLU / EAN</th>
                    <th style="width: 15%;">Cant.</th>
                    <th>Descripción</th>
                    <th style="width: 20%;">VENCE</th>
                </tr>
            </thead>
            <tbody id="excelBody">
                <?php for($i=0; $i<5; $i++): ?>
                <tr>
                    <td data-label="PLU">
                        <div class="plu-cell">
                            <input type="text" name="plu[]" class="plu-input" list="productList" autocomplete="off" placeholder="Ej: 1252">
                            <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
                        </div>
                    </td>
                    <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
                    <td data-label="Descripción"><input type="text" name="desc[]" placeholder="Nombre..."></td>
                    <td data-label="VENCE"><input type="date" name="date[]"></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        
        <button type="button" class="btn-add" onclick="addRow()">+ Añadir fila</button>
        <button type="submit" name="save_excel" class="btn-submit"> SUBIR AL SISTEMA</button>
        
        
    </form>
    <a href="https://donjorgito.shop/g502/plu/dbplusvencer.php" style="display:block; text-align:center; margin-top:20px; color:#1a73e8; text-decoration:none; font-weight:bold;">← Volver</a>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let html5QrCode;
let activeInput = null;

function startScanner(button) {
    activeInput = button.parentElement.querySelector('.plu-input');
    document.getElementById('scanner-modal').style.display = 'flex';
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start({ facingMode: "environment" }, { fps: 15, qrbox: 250 }, (text) => {
        stopScanner();
        resolveScannedCode(text);
    }).catch(err => alert("Error: " + err));
}

function resolveScannedCode(code) {
    fetch(`get_plu_by_code.php?code=${encodeURIComponent(code)}`)
        .then(res => res.json())
        .then(data => {
            if (data.found) {
                activeInput.value = data.plu;
                const row = activeInput.closest('tr');
                const descInput = row.querySelector('input[name="desc[]"]');
                if (descInput && !descInput.value) {
                    descInput.value = data.name;
                }
            } else {
                activeInput.value = code;
                alert("Código no encontrado en la base de datos: " + code);
            }
            activeInput.dispatchEvent(new Event('input', { bubbles: true }));
        })
        .catch(err => {
            console.error(err);
            activeInput.value = code;
        });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => document.getElementById('scanner-modal').style.display = 'none');
    }
}

function addRow() {
    const tbody = document.getElementById('excelBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td data-label="PLU">
            <div class="plu-cell">
                <input type="text" name="plu[]" class="plu-input" list="productList" autocomplete="off" placeholder="Ej: 1252">
                <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
            </div>
        </td>
        <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
        <td data-label="Descripción"><input type="text" name="desc[]" placeholder="Nombre..."></td>
        <td data-label="VENCE"><input type="date" name="date[]"></td>
    `;
    tbody.appendChild(newRow);
}

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('plu-input')) {
        const query = e.target.value.trim();
        const row = e.target.closest('tr');
        const descInput = row.querySelector('input[name="desc[]"]');

        if (query.length < 2) return;

        fetch(`search_product.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                const dl = document.getElementById('productList');
                dl.innerHTML = '';
                data.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.plu; 
                    opt.textContent = `${item.name} [${item.plu}]`;
                    dl.appendChild(opt);
                });

                const exactMatch = data.find(item => item.plu === query || item.ean === query);
                if (exactMatch) {
                    descInput.value = exactMatch.name;
                }
            });
    }
});

function abrirProyectoG502() {
    ['https://donjorgito.shop/g502/pdf/index1.php','https://donjorgito.shop/g502/pdf/','https://donjorgito.shop/g502/plu/dbplus.php'].forEach(url => window.open(url, '_blank'));
}
</script>
</body>
</html>