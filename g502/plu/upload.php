<?php 
require_once '../config/db.php'; 
date_default_timezone_set('America/Bogota'); 

// PROCESAMIENTO DEL FORMULARIO
if(isset($_POST['save_excel'])){
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $plus = $_POST['plu'];
    $qtys = $_POST['qty'];
    $descs = $_POST['desc'];
    $descs2 = $_POST['desc2'];
    $tipos = $_POST['tipo']; 
    $count = 0;
    
    $current_time = date('Y-m-d H:i:s');

    for($i=0; $i < count($plus); $i++) {
        $plu = trim($plus[$i]);
        if(empty($plu)) continue;

        $qty = !empty($qtys[$i]) ? (int)$qtys[$i] : 1;
        $desc = !empty($descs[$i]) ? mysqli_real_escape_string($conn, $descs[$i]) : 'Sin descripción';
        $desc2 = !empty($descs2[$i]) ? mysqli_real_escape_string($conn, $descs2[$i]) : '';
        $tipo = !empty($tipos[$i]) ? mysqli_real_escape_string($conn, $tipos[$i]) : ''; 
        $name = "Item " . $plu;

        $sql = "INSERT INTO plu_products (name, plu_code, category, quantity, description, description2, state, tipo, created_at) 
                VALUES ('$name', '$plu', '$cat', '$qty', '$desc', '$desc2', '', '$tipo', '$current_time')";
        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>g502 | Carga Masiva con Escáner</title>
    <style>
        :root { 
            --primary: #1a73e8; 
            --success: #28a745; 
            --bg: #f0f2f5; 
            --accent: #7c3aed; 
            --border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 12px; 
            color: var(--text-main);
        }
        .card { 
            background: white; 
            padding: 24px; 
            border-radius: 16px; 
            box-shadow: 0 8px 30px rgba(0,0,0,0.05); 
            max-width: 1000px; 
            margin: auto; 
        }
        .header { text-align: center; margin-bottom: 20px; }
        .step-title { font-weight: 700; margin: 20px 0 8px; display: block; color: var(--text-main); font-size: 1.1rem; }
        
        select, input[type="text"], input[type="number"] { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid var(--border); 
            border-radius: 8px; 
            font-size: 1rem; 
            box-sizing: border-box; 
            outline: none;
            font-family: inherit;
        }
        select:focus, input:focus { border-color: var(--primary); }

        /* TABLA CONTROLADA EN ESCRITORIO */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-top: 10px;
        }
        .excel-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* Forzar proporciones estrictas */
        }
        .excel-table th { 
            background: #f8fafc; 
            color: var(--text-muted); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            padding: 12px 10px; 
            border-bottom: 2px solid var(--border);
            text-align: left;
            letter-spacing: 0.05em;
        }
        .excel-table td { 
            border-bottom: 1px solid var(--border); 
            padding: 8px 10px; 
            vertical-align: middle;
            background: white;
            word-wrap: break-word;
        }
        
        /* Definición de Celdas de Escritorio */
        .col-plu  { width: 220px; }
        .col-cant { width: 90px; }
        .col-desc { width: 30%; }
        .col-nota { width: 25%; }
        .col-tipo { width: 160px; }

        .plu-cell { 
            display: flex; 
            align-items: center; 
            border: 2px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            background: white;
        }
        .plu-cell input { border: none !important; padding: 10px !important; }
        .btn-scan-trigger { 
            border: none; 
            background: #f1f5f9; 
            padding: 10px 14px; 
            cursor: pointer; 
            border-left: 1px solid var(--border); 
            font-size: 1.1rem;
            transition: 0.15s; 
        }
        .btn-scan-trigger:hover { background: #cbd5e1; }

        .tipo-select { border: 2px solid var(--border) !important; border-radius: 8px; padding: 10px; cursor: pointer; }

        /* COMPORTAMIENTO MÓVIL RESPONSIVO (TRANSFORMACIÓN A TARJETAS) */
        @media screen and (max-width: 767px) {
            .excel-table { table-layout: auto; }
            .excel-table thead { display: none; }
            .excel-table tr { 
                display: block; 
                margin-bottom: 16px; 
                border: 2px solid var(--border); 
                border-radius: 12px; 
                padding: 12px;
                background: white; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.01);
            }
            .excel-table td { 
                display: flex; 
                align-items: center;
                border: none; 
                padding: 6px 0;
                width: 100% !important;
            }
            .excel-table td::before { 
                content: attr(data-label); 
                font-size: 0.72rem; 
                color: var(--text-muted); 
                width: 100px;
                min-width: 100px;
                display: inline-block;
                text-transform: uppercase; 
                font-weight: 700; 
            }
            .plu-cell { width: 100%; }
        }

        .btn-add { width: 100%; background: #e2e8f0; border: none; padding: 14px; border-radius: 8px; cursor: pointer; margin-top: 12px; font-weight: bold; color: #475569; font-size: 0.95rem; transition: background 0.15s; }
        .btn-add:hover { background: #cbd5e1; }
        
        .btn-submit { width: 100%; background: var(--success); color: white; border: none; padding: 16px; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 25px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2); transition: background 0.15s; }
        .btn-submit:hover { background: #218838; }
        
        .alert { padding: 15px; border-radius: 8px; text-align: center; margin-top: 20px; font-weight: bold; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .nav-link { display: block; text-align: center; margin-top: 20px; color: var(--primary); text-decoration: none; font-weight: bold; }
        
        .btn-master {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: white; border: none; padding: 15px 30px; border-radius: 50px;
            font-size: 1.1rem; font-weight: bold; cursor: pointer;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3); transition: all 0.2s;
        }

        /* Modal del Escáner */
        #scanner-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); z-index: 9999; flex-direction: column; align-items: center; justify-content: center;
        }
        #reader { width: 320px; border-radius: 15px; overflow: hidden; border: 4px solid var(--accent); }
        .btn-close-scanner {
            margin-top: 20px; background: white; color: black; border: none;
            padding: 12px 25px; border-radius: 50px; font-weight: bold; cursor: pointer;
        }

        .logo-container { display: flex; justify-content: center; align-items: center; width: 100%; margin: 20px 0; }
        .logo-container img { width: 450px; max-width: 90%; height: auto; filter: drop-shadow(0px 4px 6px rgba(0,0,0,0.1)); }

        @media (min-width: 768px) { #logo-mobile-only { display: none !important; } }
    </style>
</head>
<body>

<datalist id="productList"></datalist>

<div id="scanner-modal">
    <div id="reader"></div>
    <button type="button" class="btn-close-scanner" onclick="stopScanner()">CANCELAR ESCÁNER</button>
</div>

<div class="card">
    <div class="header">
        <h2>g502 | Carga Masiva</h2>
        <div id="logo-mobile-only" class="logo-container">
            <img src="../assets/img/carulla.png" alt="Logo Carulla">
        </div>
    </div>
    <a href="https://donjorgito.shop/g502/API/ia_scanner.php" class="nav-link">Subir mediante IA</a>

    <?php if(isset($msg)) echo "<div class='alert'>$msg</div>"; ?>

    <form method="POST">
        <label class="step-title">1. Departamento</label>
        <select name="category" required>
            <option value="" disabled selected>-- Elegir --</option>
            <option value="CARNES">🥩 CARNES</option>
            <option value="FRUVER">🍎 FRUVER</option>
            <option value="DELI">🧀 DELI</option>
            <option value="PANADERIA">🥖 PANADERIA</option>
            <option value="MERCADERISTA">👨 MERCADERISTA</option>
            <option value="Jefe">💎 Jefe</option>
            <option value="G502">☠️ G502</option>
        </select>

        <label class="step-title">2. Datos de Productos</label>
        <div class="table-responsive">
            <table class="excel-table">
                <thead>
                    <tr>
                        <th class="col-plu">PLU / EAN</th>
                        <th class="col-desc">Descripción</th>
                        <th class="col-cant">Cant.</th>
                        <th class="col-nota">Nota Manual</th>
                        <th class="col-tipo">Tipo</th>
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
                        <td data-label="Descripción">
                            <span class="desc-text" style="font-size: 0.9rem; font-weight: 600; color: #334155;">Automático...</span>
                            <input type="hidden" name="desc[]" class="desc-input">
                        </td>
                        <td data-label="Cantidad">
                            <input type="number" name="qty[]" placeholder="1">
                        </td>
                        <td data-label="Nota Manual">
                            <input type="text" name="desc2[]" placeholder="Escribe nota...">
                        </td>
                        <td data-label="Tipo">
                            <select name="tipo[]" class="tipo-select">
                                <option value="">-- Ninguno --</option>
                                <option value="diamante">Diamante</option>
                                <option value="impresionante">Impresionante</option>
                                <option value="insuperable">Insuperable</option>
                                <option value="aniversario">Aniversario</option>
                                <option value="descuento">Mi descuento</option>
                            </select>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        
        <button type="button" class="btn-add" onclick="addRow()">+ Añadir fila</button>
        <button type="submit" name="save_excel" class="btn-submit"> SUBIR AL SISTEMA</button>
        
        <div style="margin: 20px 0; text-align: center;">
            <button type="button" onclick="abrirProyectoG502()" class="btn-master">
                 Iniciar Espacio de Trabajo G502
            </button>
        </div>
    </form>

    <a href="dbplus.php" class="nav-link">← Ir al Panel Principal</a>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let html5QrCode;
let activeInput = null;

function startScanner(button) {
    activeInput = button.parentElement.querySelector('.plu-input');
    
    document.getElementById('scanner-modal').style.display = 'flex';
    
    html5QrCode = new Html5Qrcode("reader");
    const config = { 
        fps: 15, 
        qrbox: { width: 250, height: 150 },
        aspectRatio: 1.0
    };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config, 
        (decodedText) => {
            activeInput.value = decodedText.trim();
            activeInput.dispatchEvent(new Event('input', { bubbles: true }));
            stopScanner();
            
            if (navigator.vibrate) navigator.vibrate(100);
        }
    ).catch(err => {
        alert("Error al iniciar cámara: " + err);
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

function addRow() {
    const tbody = document.getElementById('excelBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td data-label="PLU">
            <div class="plu-cell">
                <input type="text" name="plu[]" class="plu-input" list="productList" autocomplete="off" placeholder="Busca...">
                <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
            </div>
        </td>
        <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
        
        <td data-label="Descripción">
            <span class="desc-text" style="font-size: 0.9rem; font-weight: 600; color: #334155;">Automático...</span>
            <input type="hidden" name="desc[]" class="desc-input">
        </td>
        
        <td data-label="Nota Manual"><input type="text" name="desc2[]" placeholder="Escribe nota..."></td>
        <td data-label="Tipo">
            <select name="tipo[]" class="tipo-select">
                <option value="">-- Ninguno --</option>
                <option value="diamante">Diamante</option>
                <option value="impresionante">Impresionante</option>
                <option value="insuperable">Insuperable</option>
                <option value="aniversario">Aniversario</option>
                <option value="descuento">Mi descuento</option>
            </select>
        </td>
    `;
    tbody.appendChild(newRow);
}

let localProductsCache = [];

// Lógica de Live Search Inteligente y Autocompletado Inmediato corregido para Textos Fijos
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('plu-input')) {
        const inputField = e.target;
        const query = inputField.value.trim();
        const row = inputField.closest('tr');
        const descText = row.querySelector('.desc-text');
        const descHiddenInput = row.querySelector('.desc-input');

        if (query.length < 2) return; 

        fetch(`search_product2.php?query=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                localProductsCache = data; 
                const dl = document.getElementById('productList');
                dl.innerHTML = '';
                
                data.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.plu; 
                    opt.textContent = `${item.name} - PLU: [${item.plu}] - EAN: [${item.ean || ''}]`;
                    dl.appendChild(opt);
                });

                const exactMatch = data.find(item => item.ean === query || item.plu === query);
                if (exactMatch) {
                    inputField.value = exactMatch.plu; 
                    descText.innerText = exactMatch.name; // Cambia el texto estático visible
                    descHiddenInput.value = exactMatch.name; // Prepara el envío POST
                }
            });
    }
});

// Evento complementario para clics manuales en el datalist
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('plu-input')) {
        const inputField = e.target;
        const selectedValue = inputField.value.trim();
        const row = inputField.closest('tr');
        const descText = row.querySelector('.desc-text');
        const descHiddenInput = row.querySelector('.desc-input');

        const matchedProduct = localProductsCache.find(item => item.plu === selectedValue);
        if (matchedProduct) {
            descText.innerText = matchedProduct.name; // Cambia el texto estático visible
            descHiddenInput.value = matchedProduct.name; // Prepara el envío POST
        }
    }
});

function abrirProyectoG502() {
    const urls = [
        'https://donjorgito.shop/g502/pdf/index1.php',
        'https://donjorgito.shop/g502/pdf/',
        'https://donjorgito.shop/g502/plu/dbplus.php',
        'https://donjorgito.shop/g502/bread1/',
        'https://donjorgito.shop/g502/bread1/todo.php'
    ];
    urls.forEach(url => window.open(url, '_blank'));
}
</script>
</body>
</html>