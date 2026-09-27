<?php
require_once '../../config/db.php';
date_default_timezone_set('America/Bogota');

$count = 0;
$duplicates = [];

if (isset($_POST['save_batch'])) {
    $plus   = $_POST['plu']          ?? [];
    $qtys   = $_POST['qty']          ?? [];
    $descs  = $_POST['desc']         ?? [];
    $notas  = $_POST['nota']         ?? [];
    $dates  = $_POST['date']         ?? [];
    $planes = $_POST['plan_accion']  ?? [];

    // Departamento: allow-list para evitar valores inventados
    $deptoRaw = $_POST['departamento'] ?? '';
    $depto    = in_array($deptoRaw, ['PLACE1', 'PLACE2', 'PLACE3', 'PLACE4', 'CAVA', 'PGS' ], true) ? $deptoRaw : null;

    $current_time = date('Y-m-d H:i:s');

    // Declarar variables ANTES de bind_param
    $plu   = '';
    $qty   = 1;
    $desc  = '';
    $nota  = '';
    $vence = null;
    $plan  = '';

    // ── Verificación de duplicados: mismo PLU + misma fecha de vencimiento + mismo departamento ──
    // El operador <=> (null-safe equal) trata los NULL de forma correcta al comparar.
    $checkPlu = '';
    $checkDepto = null;
    $checkVence = null;
    $checkStmt = $conn->prepare(
        "SELECT id FROM place_expiration
         WHERE plu_code = ?
           AND departamento <=> ?
           AND fecha_vencimiento <=> ?
         LIMIT 1"
    );
    $checkStmt->bind_param('sss', $checkPlu, $checkDepto, $checkVence);

    $stmt = $conn->prepare(
        "INSERT INTO place_expiration (plu_code, departamento, quantity, description, nota, fecha_vencimiento, plan_accion, state, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, '', ?)"
    );
    $stmt->bind_param('ssisssss', $plu, $depto, $qty, $desc, $nota, $vence, $plan, $current_time);

    for ($i = 0; $i < count($plus); $i++) {
        $plu = trim($plus[$i]);
        if (empty($plu)) continue;

        $qty   = !empty($qtys[$i]) ? (int)$qtys[$i] : 1;
        $desc  = !empty($descs[$i]) ? $descs[$i] : 'Sin descripción';
        $nota  = !empty($notas[$i]) ? $notas[$i] : null;
        $vence = !empty($dates[$i]) ? $dates[$i] : null;

        // Allow-list: solo se guardan valores conocidos
        $planRaw = $planes[$i] ?? '';
        $plan    = in_array($planRaw, ['SACAR', 'PEDIR_PRECIOS'], true) ? $planRaw : null;

        // ── ¿Ya existe este producto con la misma fecha y departamento? ──
        $checkPlu   = $plu;
        $checkDepto = $depto;
        $checkVence = $vence;
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->free_result();
            $duplicates[] = $plu . ($vence ? " ($vence)" : '');
            continue; // se omite: ya fue cargado antes
        }
        $checkStmt->free_result();

        if ($stmt->execute()) { $count++; }
    }

    $checkStmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Expiration | Carga</title>
    <style>
        :root { --primary: #1a73e8; --success: #28a745; --bg: #f0f2f5; --accent: #7c3aed; --sacar: #dc2626; --precio: #2563eb; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 10px; }
        .card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.05); max-width: 1000px; margin: auto; }
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

        .plan-toggle { display: flex; gap: 4px; padding: 6px; }
        .plan-btn { flex: 1; border: 1px solid #e2e8f0; background: #f8fafc; color: #64748b; padding: 8px 4px; border-radius: 6px; font-size: 0.7rem; font-weight: bold; cursor: pointer; transition: 0.15s; text-align: center; }
        .plan-btn.active-sacar { background: var(--sacar); border-color: var(--sacar); color: white; }
        .plan-btn.active-precio { background: var(--precio); border-color: var(--precio); color: white; }

        .msg-box { padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 10px; font-weight: 600; }
        .msg-success { background: #d4edda; color: #155724; }
        .msg-warning { background: #fff3cd; color: #856404; }

        @media screen and (max-width: 600px) {
            .excel-table thead { display: none; }
            .excel-table tr { display: block; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
            .excel-table td { display: block; border: none; border-bottom: 1px solid #eee; }
            .excel-table td::before { content: attr(data-label); font-size: 0.7rem; color: #999; padding: 5px 10px 0; display: block; text-transform: uppercase; font-weight: bold; }
        }
    </style>
</head>
<body>

<div id="scanner-modal">
    <div id="reader"></div>
    <button type="button" class="btn-close-scanner" onclick="stopScanner()">CANCELAR ESCÁNER</button>
</div>

<div class="card">
    <div class="header"><h2>Place Expiration | Carga</h2></div>

    <?php if (isset($_POST['save_batch'])): ?>
        <?php if ($count > 0): ?>
            <div class="msg-box msg-success">
                ✔ ¡Éxito! <?php echo $count; ?> producto<?php echo $count === 1 ? '' : 's'; ?> cargado<?php echo $count === 1 ? '' : 's'; ?>.
            </div>
        <?php endif; ?>

        <?php if (!empty($duplicates)): ?>
            <div class="msg-box msg-warning">
                ⚠ Ese producto ya fue cargado:<br>
                <?php echo htmlspecialchars(implode(', ', $duplicates), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <label class="step-title">Departamento</label>
        <select name="departamento" required>
            <option value="" disabled selected>-- Elegir --</option>
            <option value="PLACE1">PLACE1</option>
            <option value="PLACE2">PLACE2</option>
            <option value="PLACE3">PLACE3</option>
            <option value="PLACE4">PLACE4</option>
            <option value="PGS">PGS</option>
            <option value="CAVA">CAVA</option>
        </select>

        <label class="step-title">Datos de Productos</label>
        <table class="excel-table">
            <thead>
                <tr>
                    <th style="width: 20%;">PLU / EAN</th>
                    <th style="width: 8%;">Cant.</th>
                    <th style="width: 22%;">Descripción</th>
                    <th style="width: 20%;">Nota</th>
                    <th style="width: 12%;">Vence</th>
                    <th style="width: 18%;">Plan de Acción</th>
                </tr>
            </thead>
            <tbody id="excelBody">
                <?php for ($i = 0; $i < 5; $i++): ?>
                <tr>
                    <td data-label="PLU">
                        <div class="plu-cell">
                            <input type="text" name="plu[]" class="plu-input" autocomplete="off" placeholder="Ej: 1252">
                            <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
                        </div>
                    </td>
                    <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
                    <td data-label="Descripción"><input type="text" name="desc[]" placeholder="Nombre..."></td>
                    <td data-label="Nota"><input type="text" name="nota[]" placeholder="Opcional..."></td>
                    <td data-label="VENCE"><input type="date" name="date[]"></td>
                    <td data-label="Plan de Acción">
                        <input type="hidden" name="plan_accion[]" class="plan-input" value="">
                        <div class="plan-toggle">
                            <button type="button" class="plan-btn" onclick="setPlan(this, 'SACAR')">🔻 SACAR</button>
                            <button type="button" class="plan-btn" onclick="setPlan(this, 'PEDIR_PRECIOS')">💰 PRECIOS</button>
                        </div>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <button type="button" class="btn-add" onclick="addRow()">+ Añadir fila</button>
        <button type="submit" name="save_batch" class="btn-submit">SUBIR AL SISTEMA</button>
    </form>
    <a href="index.php" style="display:block; text-align:center; margin-top:20px; color:#1a73e8; text-decoration:none; font-weight:bold;">← Volver</a>
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
            const row = activeInput.closest('tr');
            const descInput = row.querySelector('input[name="desc[]"]');

            if (data.found) {
                activeInput.value = data.plu;
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

function setPlan(btn, value) {
    const row = btn.closest('tr');
    const hiddenInput = row.querySelector('.plan-input');
    const buttons = row.querySelectorAll('.plan-btn');

    buttons.forEach(b => b.classList.remove('active-sacar', 'active-precio'));

    if (hiddenInput.value === value) {
        hiddenInput.value = '';
    } else {
        hiddenInput.value = value;
        btn.classList.add(value === 'SACAR' ? 'active-sacar' : 'active-precio');
    }
}

function addRow() {
    const tbody = document.getElementById('excelBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td data-label="PLU">
            <div class="plu-cell">
                <input type="text" name="plu[]" class="plu-input" autocomplete="off" placeholder="Ej: 1252">
                <button type="button" class="btn-scan-trigger" onclick="startScanner(this)">📸</button>
            </div>
        </td>
        <td data-label="Cantidad"><input type="number" name="qty[]" placeholder="1"></td>
        <td data-label="Descripción"><input type="text" name="desc[]" placeholder="Nombre..."></td>
        <td data-label="Nota"><input type="text" name="nota[]" placeholder="Opcional..."></td>
        <td data-label="VENCE"><input type="date" name="date[]"></td>
        <td data-label="Plan de Acción">
            <input type="hidden" name="plan_accion[]" class="plan-input" value="">
            <div class="plan-toggle">
                <button type="button" class="plan-btn" onclick="setPlan(this, 'SACAR')">🔻 SACAR</button>
                <button type="button" class="plan-btn" onclick="setPlan(this, 'PEDIR_PRECIOS')">💰 PRECIOS</button>
            </div>
        </td>
    `;
    tbody.appendChild(newRow);
}
</script>
</body>
</html>