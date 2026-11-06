<?php
require_once '../../config/db.php';
date_default_timezone_set('America/Bogota');

$sql = "SELECT e.*, 
        DATE(e.created_at) as only_date,
        TIME_FORMAT(e.created_at, '%h:%i %p') as only_time,
        DATEDIFF(e.fecha_vencimiento, CURDATE()) as dias_restantes,
        h.EAN as producto_ean
        FROM place_expiration e
        LEFT JOIN Hoja1 h ON e.plu_code COLLATE utf8mb4_unicode_ci = h.PLU COLLATE utf8mb4_unicode_ci
        WHERE NOT EXISTS (
            SELECT 1 FROM place_expiration e2
            WHERE e2.plu_code = e.plu_code
              AND e2.departamento <=> e.departamento
              AND e2.fecha_vencimiento <=> e.fecha_vencimiento
              AND e2.id > e.id
        )
        ORDER BY e.created_at DESC";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Expiration | Manager</title>
    <style>
        :root { --primary: #2563eb; --border: #e2e8f0; --bg: #f1f5f9; --success: #22c55e; --danger: #ef4444; --warning: #f59e0b; --sacar: #dc2626; --precio: #2563eb; }

        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); margin: 0; padding: 10px; color: #1e293b; }
        .container { max-width: 1150px; margin: auto; background: white; padding: 15px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }

        .filter-bar { display: flex; flex-direction: column; gap: 15px; background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px; }
        .tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .tabs::-webkit-scrollbar { display: none; }

        .tab-btn { padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); background: #fff; cursor: pointer; font-weight: 600; white-space: nowrap; transition: 0.2s; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; background: #f8fafc; padding: 12px; border-bottom: 2px solid var(--border); font-size: 0.75rem; text-transform: uppercase; color: #64748b; }
        td { padding: 12px; border-bottom: 1px solid var(--border); }

        .plu-cell { font-family: 'Courier New', monospace; font-weight: bold; font-size: 1.1rem; color: #0f172a; }
        .row-done { background: #f8fafc !important; opacity: 0.5; }
        .row-done .plu-cell { text-decoration: line-through; }

        .nota-text { display: block; margin-top: 4px; font-size: 0.75rem; color: #7c3aed; font-style: italic; }

        .plan-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; white-space: nowrap; }
        .plan-badge.sacar { background: #fee2e2; color: var(--sacar); }
        .plan-badge.precio { background: #dbeafe; color: var(--precio); }

        .btn-copy { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.2s; }
        .btn-copy:active { transform: scale(0.95); }

        /* ─── Selector "Plan of option" (Hecho / No hecho) ────────────── */
        .plan-toggle { display: flex; gap: 4px; }
        .btn-toggle {
            flex: 1; padding: 8px 6px; border-radius: 8px; border: 1px solid var(--border);
            background: #fff; cursor: pointer; font-size: 0.75rem; font-weight: bold;
            transition: 0.2s; white-space: nowrap;
        }
        .btn-toggle.hecho.active { background: var(--success); color: white; border-color: var(--success); }
        .btn-toggle.no-hecho.active { background: #fee2e2; color: var(--danger); border-color: var(--danger); }
        .btn-toggle:not(.active) { color: #94a3b8; }

        @media (max-width: 768px) {
            .header-flex { flex-direction: column; gap: 15px; text-align: center; }

            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }

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

            .status-col { position: static; margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; }
            .plu-cell { font-size: 1.2rem; display: block; }
            td:last-child { margin-top: 10px; border-top: 1px solid #eee; padding-top: 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin:0;">Place Expiration | Manager</h2>
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
            <label style="font-size: 0.7rem; font-weight: bold; color: #64748b;">DEPARTAMENTO:</label>
            <div class="tabs">
                <button class="tab-btn active" data-depto="ALL" onclick="setDepto('ALL', this)">TODO</button>
                <button class="tab-btn" data-depto="PLACE1" onclick="setDepto('PLACE1', this)">PLACE1</button>
                <button class="tab-btn" data-depto="PLACE2" onclick="setDepto('PLACE2', this)">PLACE2</button>
                <button class="tab-btn" data-depto="PLACE3" onclick="setDepto('PLACE3', this)">PLACE3</button>
                <button class="tab-btn" data-depto="PLACE4" onclick="setDepto('PLACE4', this)">PLACE4</button>
                <button class="tab-btn" data-depto="PGS" onclick="setDepto('PGS', this)">PGS</button>
                <button class="tab-btn" data-depto="CAVA" onclick="setDepto('CAVA', this)">CAVA</button>
            </div>
        </div>

        <div class="filter-group">
            <label style="font-size: 0.7rem; font-weight: bold; color: #64748b;">FILTRO:</label>
            <div class="tabs">
                <button class="tab-btn active" data-plan="ALL" onclick="setPlan('ALL', this)">TODO</button>
                <button class="tab-btn" data-plan="SOON" onclick="setPlan('SOON', this)">⌛ PRÓXIMOS</button>
                <button class="tab-btn" data-plan="VENCIDOS" onclick="setPlan('VENCIDOS', this)">🚨 VENCIDOS</button>
                <button class="tab-btn" data-plan="SACAR" onclick="setPlan('SACAR', this)">🔻 SACAR</button>
                <button class="tab-btn" data-plan="PEDIR_PRECIOS" onclick="setPlan('PEDIR_PRECIOS', this)">💰 PRECIOS</button>
                <button class="tab-btn" data-plan="DONE" onclick="setPlan('DONE', this)">✅ HECHOS</button>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Plan Estado</th>
                <th style="width: 15%;">PLU / EAN / Hora</th>
                <th style="width: 8%; text-align: center;">Cant</th>
                <th>Descripción / Nota</th>
                <th style="width: 12%; text-align: center;">Vence</th>
                <th style="width: 13%; text-align: center;">Plan</th>
                <th style="width: 13%;">Acción</th>
            </tr>
        </thead>
        <tbody id="pluTable">
        <?php
        $result = $conn->query($sql);
        if ($result):
            while ($row = $result->fetch_assoc()):
                $currentState = trim($row['state'] ?? '');
                $isDoneClass = ($currentState === 'X') ? 'row-done' : '';

                $vence = $row['fecha_vencimiento'];
                $dias = $row['dias_restantes'];
                $alerta_estilo = "color: #475569;";

                if ($vence) {
                    if ($dias < 0) $alerta_estilo = "color: #dc2626; font-weight: bold;";
                    elseif ($dias <= 2) $alerta_estilo = "color: #f59e0b; font-weight: bold;";
                }

                $plu_safe = htmlspecialchars($row['plu_code'] ?? '', ENT_QUOTES, 'UTF-8');
                $ean_safe = htmlspecialchars($row['producto_ean'] ?? '', ENT_QUOTES, 'UTF-8');
                $depto_safe = htmlspecialchars($row['departamento'] ?? '', ENT_QUOTES, 'UTF-8');
                $depto_attr = $depto_safe !== '' ? $depto_safe : 'NONE';

                $plan = $row['plan_accion'] ?? '';
                if ($plan === 'SACAR') {
                    $plan_html = '<span class="plan-badge sacar">🔻 SACAR</span>';
                } elseif ($plan === 'PEDIR_PRECIOS') {
                    $plan_html = '<span class="plan-badge precio">💰 PRECIOS</span>';
                } else {
                    $plan_html = '<span style="color:#cbd5e1; font-size:0.75rem;">—</span>';
                    $plan = 'NONE';
                }
        ?>
<tr class="plu-row <?php echo $isDoneClass; ?>"
    data-plan="<?php echo htmlspecialchars($plan, ENT_QUOTES, 'UTF-8'); ?>"
    data-depto="<?php echo $depto_attr; ?>"
    data-date="<?php echo $row['only_date']; ?>"
    data-state="<?php echo ($currentState === 'X') ? 'X' : ''; ?>"
    data-days="<?php echo ($row['dias_restantes'] !== null) ? $row['dias_restantes'] : 999; ?>"
    data-created="<?php echo strtotime($row['created_at']); ?>">

                <td class="status-col" data-label="Plan Estado">
                    <div class="plan-toggle">
                        <button type="button"
                                class="btn-toggle hecho <?php echo ($currentState === 'X') ? 'active' : ''; ?>"
                                data-id="<?php echo (int)$row['id']; ?>"
                                data-value="X"
                                onclick="setPlanEstado(this)">
                            ✅ Hecho
                        </button>
                        <button type="button"
                                class="btn-toggle no-hecho <?php echo ($currentState !== 'X') ? 'active' : ''; ?>"
                                data-id="<?php echo (int)$row['id']; ?>"
                                data-value=""
                                onclick="setPlanEstado(this)">
                            ⬜ No hecho
                        </button>
                    </div>
                </td>

                <td class="plu-cell" data-label="PLU / EAN">
                    <?php echo $plu_safe; ?>
                    
                    <?php if (!empty($ean_safe)): ?>
                        <br><small style="color: #0284c7; font-size: 0.75rem; font-family: monospace; font-weight: bold;">EAN: <?php echo $ean_safe; ?></small>
                    <?php endif; ?>

                    <br>
                    <small style="color: #64748b; font-size: 0.7rem; font-weight: normal;">
                        <?php echo $row['only_time']; ?>
                    </small>
                    <?php if ($depto_safe !== ''): ?>
                        <br><span style="display:inline-block; margin-top:4px; padding:2px 8px; background:#eef2ff; color:#4338ca; border-radius:10px; font-size:0.65rem; font-weight:bold;"><?php echo $depto_safe; ?></span>
                    <?php endif; ?>
                </td>

                <td style="text-align:center;" data-label="Cant"><strong><?php echo (int)$row['quantity']; ?></strong></td>

                <td style="font-size: 0.85rem; color: #475569;" data-label="Descripción">
                    <?php echo htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!empty($row['nota'])): ?>
                        <span class="nota-text">📝 <?php echo htmlspecialchars($row['nota'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </td>

                <td style="text-align:center; font-size: 0.85rem; <?php echo $alerta_estilo; ?>" data-label="Vence">
                    <?php echo ($vence) ? date('d/m/y', strtotime($vence)) : '---'; ?>
                    <br>
                    <small style="font-size: 0.7rem;">
                        <?php
                            if ($vence) {
                                if ($dias < 0) echo "VENCIDO";
                                elseif ($dias == 0) echo "¡HOY!";
                                else echo "Faltan $dias d";
                            }
                        ?>
                    </small>
                </td>

                <td style="text-align:center;" data-label="Plan">
                    <?php echo $plan_html; ?>
                </td>

                <td data-label="Acción">
                    <button class="btn-copy" data-plu="<?php echo $plu_safe; ?>" onclick="processCopy(this)">
                        Copy
                    </button>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>

<script>
let currentPlan = 'ALL';
let currentDepto = 'ALL';

function setPlan(plan, btn) {
    btn.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentPlan = plan;
    applyFilters();
}

function setDepto(depto, btn) {
    btn.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentDepto = depto;
    applyFilters();
}

function applyFilters() {
    const selectedDate = document.getElementById('dateFilter').value;
    const container = document.getElementById('pluTable');
    const rows = Array.from(document.querySelectorAll('.plu-row'));
    const isMobile = window.innerWidth <= 768;

    // Recolectar los datos de cada fila una sola vez
    const items = rows.map(row => ({
        element: row,
        days: parseInt(row.getAttribute('data-days')),
        isDone: row.classList.contains('row-done'),
        depto: row.getAttribute('data-depto'),
        date: row.getAttribute('data-date'),
        plan: row.getAttribute('data-plan'),
        created: parseInt(row.getAttribute('data-created')) || 0
    }));

    const deptoMatch = item => (currentDepto === 'ALL' || item.depto === currentDepto);
    const dateMatch  = item => (selectedDate === "" || item.date === selectedDate);

    let filtered;

    if (currentPlan === 'DONE') {
        // ─── "HECHOS": solo lo ya revisado, más reciente primero ───
        filtered = items.filter(item => item.isDone && deptoMatch(item));
        filtered.sort((a, b) => b.created - a.created);

    } else if (currentPlan === 'SOON' || currentPlan === 'VENCIDOS') {
        filtered = items.filter(item => {
            if (item.isDone || item.days === 999 || !deptoMatch(item)) return false;
            return currentPlan === 'SOON' ? item.days >= 0 : item.days < 0;
        });
        if (currentPlan === 'SOON') {
            filtered.sort((a, b) => a.days - b.days);      // vencen antes primero
        } else {
            filtered.sort((a, b) => b.days - a.days);       // vencidos más recientemente primero
        }

    } else if (currentPlan === 'SACAR' || currentPlan === 'PEDIR_PRECIOS') {
        filtered = items.filter(item =>
            !item.isDone && item.plan === currentPlan && deptoMatch(item) && dateMatch(item)
        );
        filtered.sort((a, b) => b.created - a.created);      // más reciente primero

    } else {
        // ALL / TODO
        filtered = items.filter(item => !item.isDone && deptoMatch(item) && dateMatch(item));
        filtered.sort((a, b) => b.created - a.created);      // más reciente primero
    }

    rows.forEach(r => r.style.display = "none");
    filtered.forEach(item => {
        item.element.style.display = isMobile ? "block" : "table-row";
        container.appendChild(item.element);
    });
}

window.onresize = applyFilters;

function setPlanEstado(btn) {
    const id = btn.dataset.id;
    const value = btn.dataset.value; 
    const row = btn.closest('.plu-row');
    const toggleGroup = btn.closest('.plan-toggle');

    toggleGroup.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    if (value === 'X') {
        row.classList.add('row-done');
        row.setAttribute('data-state', 'X');
    } else {
        row.classList.remove('row-done');
        row.setAttribute('data-state', '');
    }

    // Refleja el cambio de inmediato: si se marcó "Hecho", desaparece de la vista actual
    // (a menos que ya estemos en la pestaña HECHOS)
    applyFilters();

    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append('state', value);
    fetch('update_state.php', { method: 'POST', body: formData })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
        })
        .catch(err => {
            console.error("Error guardando el estado:", err);
            alert("No se pudo guardar el cambio. Intenta de nuevo.");
            toggleGroup.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
            toggleGroup.querySelector(`[data-value="${value === 'X' ? '' : 'X'}"]`).classList.add('active');
            row.classList.toggle('row-done');
            row.setAttribute('data-state', row.classList.contains('row-done') ? 'X' : '');
            applyFilters(); // refleja también la reversión
        });
}

function processCopy(btn) {
    const val = btn.dataset.plu + ' exito';
    navigator.clipboard.writeText(val).then(() => {
        const original = btn.innerText;
        btn.innerText = "¡Copiado!";
        setTimeout(() => { btn.innerText = original; }, 1000);
    });
}

window.onload = applyFilters;
</script>
</body>
</html>