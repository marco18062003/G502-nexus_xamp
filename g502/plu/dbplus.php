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
        :root { 
            --primary: #2563eb; 
            --primary-dark: #1d4ed8;
            --border: #e2e8f0; 
            --bg: #f8fafc; 
            --success: #22c55e; 
            --success-dark: #16a34a;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: var(--bg); 
            margin: 0; 
            padding: 12px; 
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .container { 
            max-width: 1200px; 
            margin: auto; 
            background: white; 
            padding: 20px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
        }
        
        /* HEADER ESTILIZADO */
        .header-top {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
            gap: 15px;
        }
        .header-top h2 { 
            margin: 0; 
            font-size: 1.6rem; 
            font-weight: 800; 
            letter-spacing: -0.025em;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* FILTROS MEJORADOS */
        .filter-bar { 
            display: flex; 
            flex-direction: column;
            gap: 15px; 
            background: #f1f5f9; 
            padding: 15px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
        }
        .filter-group { 
            display: flex; 
            flex-direction: column; 
            gap: 6px; 
            width: 100%;
        }
        .filter-label {
            font-size: 0.75rem; 
            font-weight: 700; 
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }
        
        /* TABS RESPONSIVAS */
        .tabs { 
            display: flex; 
            gap: 6px; 
            overflow-x: auto; 
            padding-bottom: 4px;
            scrollbar-width: none; /* Firefox */
        }
        .tabs::-webkit-scrollbar { display: none; /* Chrome/Safari */ }
        
        .tab-btn { 
            padding: 10px 14px; 
            border-radius: 8px; 
            border: 1px solid var(--border); 
            background: #fff; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 0.9rem;
            white-space: nowrap; 
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .tab-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        /* BOTONES GENÉRICOS */
        .btn-secondary {
            font-size: 0.8rem; 
            font-weight: 600;
            cursor: pointer; 
            border: 1px solid var(--border); 
            border-radius: 8px; 
            padding: 8px 14px; 
            background: white;
            transition: all 0.15s;
        }
        .btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }

        .btn-add {
            background: var(--success); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 700;
            transition: background 0.15s;
            box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
        }
        .btn-add:hover { background: var(--success-dark); }

        /* TABLA CONTROLADA Y PROPORCIONAL */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; /* Mantiene proporciones fijas en escritorio */
        }
        th { 
            text-align: left; 
            background: #f8fafc; 
            padding: 14px 12px; 
            border-bottom: 2px solid var(--border); 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            color: var(--text-muted); 
            letter-spacing: 0.05em;
        }
        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid var(--border); 
            vertical-align: middle;
            word-wrap: break-word; /* Evita desborde de texto largo */
        }
        
        /* CONFIGURACIÓN EXACTA DE ANCHOS DE CELDA (PROPORCIONES) */
        .col-st   { width: 45px; text-align: center; }
        .col-plu  { width: 140px; }
        .col-cant { width: 70px; text-align: center; }
        .col-desc { width: 30%; } /* Toma espacio proporcional ideal */
        .col-nota { width: 25%; }
        .col-tipo { width: 120px; text-align: center; }
        .col-act  { width: 100px; }

        /* DETALLES DE CELDAS INTERNAS */
        .status-col { 
            color: #ef4444; 
            font-weight: 800; 
            font-size: 1.1rem;
        }
        .plu-cell { 
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; 
            font-weight: 700; 
            font-size: 1.05rem; 
            color: #1e293b; 
            line-height: 1.2;
        }
        .cant-badge {
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
        }
        
        /* COMPORTAMIENTO FILAS COMPLETADAS (CHECK OUT) */
        .row-done { background: #f8fafc !important; }
        .row-done td { opacity: 0.45; }
        .row-done .plu-cell { text-decoration: line-through; }
        
        .btn-copy { 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 10px 12px; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-weight: 700; 
            font-size: 0.85rem;
            transition: all 0.15s ease; 
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.15);
        }
        .btn-copy:hover { background: var(--primary-dark); }
        .btn-copy:active { transform: scale(0.96); }

        /* CORRECCIÓN INTERFAZ MÓVIL (MÁXIMO CONTROL RESPONSIVO) */
        @media (min-width: 768px) {
            .filter-bar { flex-direction: row; }
            .filter-group { width: auto; }
            .filter-group:last-child { flex-grow: 1; }
        }

        @media (max-width: 767px) {
            /* Transformación completa de Tabla a Tarjetas Apiladas */
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; } /* Ocultamos th tradicional */
            
            table { table-layout: auto; }
            .plu-row {
                background: white;
                border: 1px solid var(--border);
                border-radius: 12px;
                margin-bottom: 15px;
                padding: 12px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.01);
                position: relative;
            }
            
            td { 
                display: flex;
                align-items: center;
                padding: 6px 0; 
                border-bottom: none;
                width: 100% !important;
                text-align: left !important;
            }
            
            /* Generamos etiquetas dinámicas simulando las columnas */
            td::before {
                content: attr(data-label);
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                color: var(--text-muted);
                width: 95px;
                min-width: 95px;
                display: inline-block;
            }

            /* Reajustes visuales específicos para modo tarjeta */
            .status-col {
                position: absolute;
                top: 12px;
                right: 12px;
                width: auto !important;
                padding: 0;
            }
            .status-col::before { display: none; } /* No necesita etiqueta */

            td.plu-cell { border-bottom: 1px dashed var(--border); padding-bottom: 10px; margin-bottom: 5px; }
            td.plu-cell::before { content: "PRODUCTO:"; }
            
            .cant-badge { background: #eff6ff; color: var(--primary); }
            
            td:last-child {
                padding-top: 10px;
                border-top: 1px dashed var(--border);
                margin-top: 5px;
            }
            td:last-child::before { display: none; } /* Botón Copy toma el 100% */
            .btn-copy { padding: 12px; font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-top">
        <h2>g502 | Manager</h2>
        <div class="header-actions">
            <button class="btn-secondary" onclick="document.getElementById('dateFilter').value = ''; applyFilters();">Ver Todo</button>
            <a href="upload.php" class="btn-add">+ Cargar</a>
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-group">
            <label class="filter-label" for="dateFilter">FECHA TRABAJO</label>
            <?php 
                $h = (int)date('H');
                $default_date = ($h >= 19) ? date('Y-m-d', strtotime('+1 day')) : date('Y-m-d');
            ?>
            <input type="date" id="dateFilter" onchange="applyFilters()" value="<?php echo $default_date; ?>" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border); outline: none; font-weight: 600; font-family: inherit;">
        </div>

        <div class="filter-group">
            <label class="filter-label">FILTRAR SECCIÓN</label>
            <div class="tabs">
                <button class="tab-btn active" data-cat="ALL" onclick="setCategory('ALL', this)">TODO</button>
                <button class="tab-btn" data-cat="CARNES" onclick="setCategory('CARNES', this)">🥩 Carnes</button>
                <button class="tab-btn" data-cat="FRUVER" onclick="setCategory('FRUVER', this)">🍎 Fruver</button>
                <button class="tab-btn" data-cat="DELI" onclick="setCategory('DELI', this)">🧀 Deli</button>
                <button class="tab-btn" data-cat="PANADERIA" onclick="setCategory('PANADERIA', this)">🥖 Panadería</button>
                <button class="tab-btn" data-cat="MERCADERISTA" onclick="setCategory('MERCADERISTA', this)">👨 Mercaderista</button>
                <button class="tab-btn" data-cat="G502" onclick="setCategory('G502', this)">☠️ G502</button>
                <button class="tab-btn" data-cat="JEFE" onclick="setCategory('JEFE', this)">💎 Jefe</button>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="col-st">St</th>
                    <th class="col-plu">PLU / Hora</th>
                    <th class="col-cant" style="text-align: center;">Cant</th>
                    <th class="col-desc">Descripción Base</th>
                    <th class="col-nota">Nota / Plantilla</th>
                    <th class="col-tipo" style="text-align: center;">Tipo</th>
                    <th class="col-act">Acción</th>
                </tr>
            </thead>
            <tbody id="pluTable">
            <?php
            $sql = "SELECT *, 
                    DATE(created_at) as only_date, 
                    TIME_FORMAT(created_at, '%h:%i %p') as only_time 
                    FROM plu_products 
                    ORDER BY created_at DESC";
            
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()):
                $currentState = trim($row['state'] ?? ''); 
                $isDoneClass = ($currentState === 'X') ? 'row-done' : '';
                
                $tipoIcono = '—';
                $t = strtolower(trim($row['tipo'] ?? ''));
                if($t == 'diamante') $tipoIcono = ' Diamante';
                elseif($t == 'impresionante') $tipoIcono = '⚡Impresionante';
                elseif($t == 'insuperable') $tipoIcono = 'Insuperable';
                elseif($t == 'aniversario') $tipoIcono = ' Aniversario';
                elseif($t == 'descuento') $tipoIcono = '️Mi Descuento';
            ?>
                <tr class="plu-row <?php echo $isDoneClass; ?>" 
                    data-cat="<?php echo strtoupper($row['category']); ?>" 
                    data-date="<?php echo $row['only_date']; ?>">
                    
                    <td class="status-col" id="stat-<?php echo $row['id']; ?>">
                        <?php echo ($currentState === 'X') ? 'X' : ''; ?>
                    </td>
                    
                    <td class="plu-cell">
                        <?php echo $row['plu_code']; ?><br>
                        <small style="color: var(--text-muted); font-weight: 500; font-size: 0.72rem;">
                            🕒 <?php echo $row['only_time']; ?>
                        </small>
                    </td>
                    
                    <td data-label="Cantidad" style="text-align:center;">
                        <span class="cant-badge"><?php echo $row['quantity']; ?></span>
                    </td>
                    
                    <td data-label="Descripción" style="font-size: 0.9rem; font-weight: 500; color: #334155;">
                        <?php echo htmlspecialchars($row['description']); ?>
                    </td>

                    <td data-label="Nota Manual" style="font-size: 0.9rem; color: #475569; font-style: italic;">
                        <?php echo htmlspecialchars($row['description2'] ?? '—'); ?>
                    </td>

                    <td data-label="Tipo" style="text-align:center; font-size: 0.85rem; font-weight: 600; color: #475569;">
                        <?php echo $tipoIcono; ?>
                    </td>
                    
                    <td>
                        <button class="btn-copy" onclick="processCopy('<?php echo $row['plu_code']; ?>', '<?php echo $row['id']; ?>', this)">
                            Copiar PLU
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
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
        document.getElementById('stat-' + id).innerText = "X";
        btn.closest('.plu-row').classList.add('row-done');
        
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('state', 'X');
        fetch('update_state.php', { method: 'POST', body: formData })
        .catch(err => console.error("Error al actualizar estado:", err));
    });
}

window.onload = applyFilters;
</script>
</body>
</html>