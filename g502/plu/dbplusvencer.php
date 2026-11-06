<?php 
require_once '../config/db.php'; 
date_default_timezone_set('America/Bogota'); 

// 1. DEFINIR LA CONSULTA SQL PRIMERO (Para evitar el error de "Undefined variable")
$sql = "SELECT *, 
        DATE(created_at) as only_date, 
        TIME_FORMAT(created_at, '%h:%i %p') as only_time,
        DATEDIFF(fecha_vence, CURDATE()) as dias_restantes 
        FROM plu_vencer 
        ORDER BY created_at DESC";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>g502 | Manager</title>
    <style>
        :root { --primary: #2563eb; --border: #e2e8f0; --bg: #f1f5f9; --success: #22c55e; --danger: #ef4444; --warning: #f59e0b; }
        
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); margin: 0; padding: 10px; color: #1e293b; }
        .container { max-width: 1100px; margin: auto; background: white; padding: 15px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        /* Header */
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        /* Filtros */
        .filter-bar { display: flex; flex-direction: column; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px; }
        .tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .tabs::-webkit-scrollbar { display: none; }
        
        .tab-btn { padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-weight: 600; white-space: nowrap; transition: 0.2s; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); }

        /* Tabla Responsiva */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fafc; padding: 12px; border-bottom: 2px solid var(--border); font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        td { padding: 12px; border-bottom: 1px solid var(--border); }
        
        .plu-cell { font-family: 'Courier New', monospace; font-weight: bold; font-size: 1.1rem; color: #0f172a; }
        .row-done { background: #f8fafc !important; opacity: 0.5; }
        .row-done .plu-cell { text-decoration: line-through; }
        
        .btn-copy { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.2s; }
        .btn-copy:active { transform: scale(0.95); }

        /* DISEÑO MOBILE (Transformación a Cards) */
        @media (max-width: 768px) {
            .header-flex { flex-direction: column; gap: 15px; text-align: center; }
            
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; } /* Escondemos los encabezados en móvil */
            
            .plu-row { 
                background: white; 
                border: 1px solid var(--border); 
                border-radius: 12px; 
                margin-bottom: 15px; 
                padding: 15px;
                position: relative;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            }

            td { border: none; padding: 5px 0; display: flex; justify-content: space-between; align-items: center; }
            td::before { content: attr(data-label); font-size: 0.75rem; font-weight: bold; color: #94a3b8; text-transform: uppercase; }
            
            .status-col { position: absolute; top: 15px; right: 15px; font-size: 1.2rem; }
            .plu-cell { font-size: 1.2rem; display: block; }
            td:last-child { margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; }
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
            <label style="font-size: 0.7rem; font-weight: bold; color: #64748b;">FECHA REGISTRO:</label>
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
                <button class="tab-btn" data-cat="SOON" onclick="setCategory('SOON', this)">⌛ PRÓXIMOS</button>
                <button class="tab-btn" data-cat="CARNES" onclick="setCategory('CARNES', this)">🥩</button>
                <button class="tab-btn" data-cat="FRUVER" onclick="setCategory('FRUVER', this)">🍎</button>
                <button class="tab-btn" data-cat="DELI" onclick="setCategory('DELI', this)">🧀</button>
                <button class="tab-btn" data-cat="PANADERIA" onclick="setCategory('PANADERIA', this)">🥖</button>
                <button class="tab-btn" data-cat="MERCADERISTA" onclick="setCategory('MERCADERISTA', this)">👨</button>
                <button class="tab-btn" data-cat="G502" onclick="setCategory('G502', this)">☠️</button>
                <button class="tab-btn" data-cat="JEFE" onclick="setCategory('JEFE', this)">💎</button>
                <button class="tab-btn" data-cat="POP" onclick="setCategory('POP', this)">📜</button>
                <button class="tab-btn" data-cat="PGS" onclick="setCategory('PGS', this)">📦</button>
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
                <th style="width: 15%; text-align: center;">Vence</th>
                <th style="width: 15%;">Acción</th>
            </tr>
        </thead>
        <tbody id="pluTable">
        <?php
        $result = $conn->query($sql);
        if($result):
            while($row = $result->fetch_assoc()):
                $currentState = trim($row['state'] ?? ''); 
                $isDoneClass = ($currentState === 'X') ? 'row-done' : '';
                
                $vence = $row['fecha_vence'];
                $dias = $row['dias_restantes'];
                $alerta_estilo = "color: #475569;"; 
                
                if($vence) {
                    if($dias < 0) $alerta_estilo = "color: #dc2626; font-weight: bold;"; 
                    elseif($dias <= 2) $alerta_estilo = "color: #f59e0b; font-weight: bold;"; 
                }
        ?>
            <!-- Busca tu línea del <tr> y déjala así -->
<tr class="plu-row <?php echo $isDoneClass; ?>" 
    data-cat="<?php echo strtoupper($row['category']); ?>" 
    data-date="<?php echo $row['only_date']; ?>"
    data-days="<?php echo ($row['dias_restantes'] !== null) ? $row['dias_restantes'] : 999; ?>">
                
                <td class="status-col" id="stat-<?php echo $row['id']; ?>">
                    <?php echo ($currentState === 'X') ? 'X' : ''; ?>
                </td>
                
                <td class="plu-cell">
                    <?php echo $row['plu_code']; ?><br>
                    <small style="color: #64748b; font-size: 0.7rem; font-weight: normal;">
                        <?php echo $row['only_time']; ?>
                    </small>
                </td>
                
                <td style="text-align:center;"><strong><?php echo $row['quantity']; ?></strong></td>
                
                <td style="font-size: 0.85rem; color: #475569;">
                    <?php echo htmlspecialchars($row['description']); ?>
                </td>

                <td style="text-align:center; font-size: 0.85rem; <?php echo $alerta_estilo; ?>">
                    <?php echo ($vence) ? date('d/m/y', strtotime($vence)) : '---'; ?>
                    <br>
                    <small style="font-size: 0.7rem;">
                        <?php 
                            if($vence) {
                                if($dias < 0) echo "VENCIDO";
                                elseif($dias == 0) echo "¡HOY!";
                                else echo "Faltan $dias d";
                            }
                        ?>
                    </small>
                </td>
                
                <td>
                    <button class="btn-copy" onclick="processCopy('<?php echo $row['plu_code']; ?>', '<?php echo $row['id']; ?>', this)">
                        Copy
                    </button>
                </td>
            </tr>
        <?php endwhile; endif; ?>
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
    const container = document.getElementById('pluTable');
    const rows = Array.from(document.querySelectorAll('.plu-row'));
    const isMobile = window.innerWidth <= 768;

    if (currentCategory === 'SOON') {
        // --- LÓGICA ESPECIAL PARA "SOON" ---
        
        // 1. Filtrar: Solo los que NO han vencido (días > 0), tienen fecha y NO están marcados como hechos (X)
        const filtered = rows.map(row => {
            const days = parseInt(row.getAttribute('data-days'));
            const isDone = row.classList.contains('row-done');
            return { element: row, days: days, isDone: isDone };
        }).filter(item => item.days > 0 && item.days < 999 && !item.isDone);

        // 2. Ordenar: El que vence más rápido primero (ascendente)
        filtered.sort((a, b) => a.days - b.days);

        // 3. Mostrar y Reordenar en el DOM
        rows.forEach(r => r.style.display = "none"); // Esconder todos
        filtered.forEach(item => {
            item.element.style.display = isMobile ? "block" : "table-row";
            container.appendChild(item.element); // Los mueve al final del contenedor en el nuevo orden
        });

    } else {
        // --- LÓGICA NORMAL (TODO / CATEGORÍAS) ---
        
        rows.forEach(row => {
            const rowDate = row.getAttribute('data-date');
            const rowCat = row.getAttribute('data-cat');
            
            const dateMatch = (selectedDate === "" || rowDate === selectedDate);
            const catMatch = (currentCategory === 'ALL' || rowCat === currentCategory);
            
            if (dateMatch && catMatch) {
                row.style.display = isMobile ? "block" : "table-row";
            } else {
                row.style.display = "none";
            }
        });
    }
}

// Ajuste por si giras el celular (responsive)
window.onresize = applyFilters;

function processCopy(val, id, btn) {
    navigator.clipboard.writeText(val).then(() => {
        document.getElementById('stat-' + id).innerText = "X";
        btn.closest('.plu-row').classList.add('row-done');
        
        // Si estamos en modo SOON, ocultamos la fila de inmediato al marcarla como hecha
        if (currentCategory === 'SOON') {
            btn.closest('.plu-row').style.display = "none";
        }
        
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('state', 'X');
        fetch('update_state.php', { method: 'POST', body: formData })
        .catch(err => console.error("Error:", err));
    });
}

window.onload = applyFilters;
</script>
</body>
</html> 