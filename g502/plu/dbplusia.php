<?php 
require_once '../config/db.php'; 
date_default_timezone_set('America/Bogota'); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>g502 | Manager</title>
    <style>
        :root { --primary: #2563eb; --border: #e2e8f0; --bg: #f8fafc; --success: #22c55e; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; padding: 10px; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        .filter-bar { display: flex; flex-wrap: wrap; gap: 10px; background: #fff; padding: 15px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; flex-grow: 1; }
        
        .tabs { display: flex; gap: 5px; overflow-x: auto; padding-bottom: 5px; }
        .tab-btn { padding: 8px 12px; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-weight: 600; white-space: nowrap; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fafc; padding: 10px; border: 1px solid var(--border); font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        td { padding: 10px; border: 1px solid var(--border); vertical-align: middle; }
        
        .status-col { width: 30px; text-align: center; color: #dc2626; font-weight: bold; }
        .plu-cell { font-family: monospace; font-weight: bold; font-size: 1.1rem; color: #1e293b; }
        .row-done { background: #f1f5f9 !important; opacity: 0.6; }
        .row-done .plu-cell { text-decoration: line-through; }
        
        .btn-copy { background: var(--primary); color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; transition: background 0.2s; }
        .btn-copy:active { background: #1d4ed8; transform: scale(0.98); }

        /* RESPONSIVO */
        @media (max-width: 600px) {
            /* Ocultamos Descripción y Tipo en móvil para priorizar PLU, Cant y Acción */
            th:nth-child(4), td:nth-child(4),
            th:nth-child(5), td:nth-child(5) { 
                display: none; 
            }
            .container { padding: 10px; }
            .plu-cell { font-size: 1rem; }
            td { padding: 8px 5px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin:0;">g502 | Manager</h2>
        <div style="display: flex; gap: 10px;">
            <button onclick="document.getElementById('dateFilter').value = ''; applyFilters();" style="font-size: 0.7rem; cursor: pointer; border: 1px solid #ccc; border-radius: 5px; padding: 5px 10px; background: white;">Ver Todo</button>
            <a href="upload.php" style="background: var(--success); color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; font-weight: bold;">+ Cargar</a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label style="font-size: 0.7rem; font-weight: bold; color: #64748b;">FECHA:</label>
            <?php 
                $h = (int)date('H');
                $default_date = ($h >= 19) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
            ?>
            <input type="date" id="dateFilter" onchange="applyFilters()" value="<?php echo $default_date; ?>" style="padding: 8px; border-radius: 6px; border: 1px solid var(--border); outline: none;">
        </div>

        <div class="filter-group">
            <label style="font-size: 0.7rem; font-weight: bold; color: #64748b;">DEPTO:</label>
            <div class="tabs">
                <button class="tab-btn active" data-cat="ALL" onclick="setCategory('ALL', this)">TODO</button>
                <button class="tab-btn" data-cat="CARNES" onclick="setCategory('CARNES', this)">🥩</button>
                <button class="tab-btn" data-cat="FRUVER" onclick="setCategory('FRUVER', this)">🍎</button>
                <button class="tab-btn" data-cat="DELI" onclick="setCategory('DELI', this)">🧀</button>
                <button class="tab-btn" data-cat="PANADERIA" onclick="setCategory('PANADERIA', this)">🥖</button>
                <button class="tab-btn" data-cat="MERCADERISTA" onclick="setCategory('MERCADERISTA', this)">👨</button>
                <button class="tab-btn" data-cat="G502" onclick="setCategory('G502', this)">☠️</button>
                <button class="tab-btn" data-cat="JEFE" onclick="setCategory('JEFE', this)">💎</button>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">St</th>
                <th style="width: 25%;">PLU / Hora</th>
                <th style="width: 10%; text-align: center;">Cant</th>
                <th>Descripción</th>
                <th style="width: 10%; text-align: center;">Tipo</th>
                <th style="width: 15%;">Acción</th>
            </tr>
        </thead>
        <tbody id="pluTable">
        <?php
        $sql = "SELECT *, 
                DATE(created_at) as only_date, 
                TIME_FORMAT(created_at, '%h:%i %p') as only_time 
                FROM productos_ia
                ORDER BY created_at DESC";
        
        $result = $conn->query($sql);
        while($row = $result->fetch_assoc()):
            $currentState = trim($row['state'] ?? ''); 
            $isDoneClass = ($currentState === 'X') ? 'row-done' : '';
            
            // Lógica de iconos
            $tipoIcono = '';
            $t = $row['tipo'] ?? '';
            if($t == 'diamante') $tipoIcono = 'diamante';
            elseif($t == 'impresionante') $tipoIcono = 'impresionante';
            elseif($t == 'insuperable') $tipoIcono = 'insuperable';
        ?>
            <tr class="plu-row <?php echo $isDoneClass; ?>" 
                data-cat="<?php echo strtoupper($row['category']); ?>" 
                data-date="<?php echo $row['only_date']; ?>">
                
                <td class="status-col" id="stat-<?php echo $row['id']; ?>">
                    <?php echo ($currentState === 'X') ? 'X' : ''; ?>
                </td>
                
                <td class="plu-cell">
                    <?php echo $row['plu_code']; ?><br>
                    <small style="color: #64748b; font-weight: normal; font-size: 0.7rem;">
                        <?php echo $row['only_time']; ?>
                    </small>
                </td>
                
                <td style="text-align:center;"><strong><?php echo $row['quantity']; ?></strong></td>
                
                <td style="font-size: 0.85rem; color: #475569;">
                    <?php echo htmlspecialchars($row['description']); ?>
                </td>

                <td style="text-align:center; font-size: 1.2rem;">
                    <?php echo $tipoIcono; ?>
                </td>
                
                <td>
                    <button class="btn-copy" onclick="processCopy('<?php echo $row['plu_code']; ?>', '<?php echo $row['id']; ?>', this)">
                        Copy
                    </button>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
let currentCategory = 'ALL';

function setCategory(cat, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCategory = cat;
    applyFilters();
}

function applyFilters() {
    const selectedDate = document.getElementById('dateFilter').value;
    const rows = document.querySelectorAll('.plu-row');

    rows.forEach(row => {
        const rowDate = row.getAttribute('data-date');
        const rowCat = row.getAttribute('data-cat');
        const dateMatch = (selectedDate === "" || rowDate === selectedDate);
        const catMatch = (currentCategory === 'ALL' || rowCat === currentCategory);
        
        row.style.display = (dateMatch && catMatch) ? "table-row" : "none";
    });
}

function processCopy(val, id, btn) {
    navigator.clipboard.writeText(val).then(() => {
        // Actualización visual inmediata
        document.getElementById('stat-' + id).innerText = "X";
        btn.closest('.plu-row').classList.add('row-done');
        
        // Guardar estado en BD
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('state', 'X');
        fetch('update_state.php', { method: 'POST', body: formData })
        .catch(err => console.error("Error al actualizar estado:", err));
    });
}

// Ejecutar filtros al cargar para aplicar la fecha por defecto
window.onload = applyFilters;
</script>
</body>
</html>