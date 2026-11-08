<?php
require_once('seguridad_admin.php');

if (session_status() === PHP_SESSION_NONE) session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const PROFIT_RATE = 0.18;

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 0, ',', '.'); }
function sanitizeMonth(?string $m): string {
    return ($m !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) ? $m : date('Y-m');
}
function monthLabel(string $month): string {
    $n = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    [$y, $m] = explode('-', $month);
    return ucfirst($n[(int)$m - 1]) . ' ' . $y;
}

class FinanceController {
    public function __construct(private mysqli $db) {}

    private function range(string $month): array {
        $s = $month . '-01';
        return [$s, date('Y-m-d', strtotime($s . ' +1 month'))];
    }

    /** Gastos del mes. ABS() porque los gastos pueden guardarse en negativo. */
    public function getExpenses(string $month): array {
        $st = $this->db->prepare(
            "SELECT IFNULL(SUM(ABS(monto)), 0) AS total, COUNT(*) AS n
             FROM expenses WHERE month COLLATE utf8mb4_unicode_ci = ?"
        );
        $st->bind_param("s", $month);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return ['total' => (float)$r['total'], 'rows' => (int)$r['n']];
    }

    /** Ventas por día = finance + finance2 combinadas */
    public function getDailySales(string $month): array {
        [$a, $b] = $this->range($month);
        $st = $this->db->prepare(
            "SELECT d, SUM(v) AS total, SUM(src = 1) AS from_finance, SUM(src = 2) AS from_finance2
             FROM (
                SELECT fecha AS d, monto AS v, 1 AS src FROM finance WHERE fecha >= ? AND fecha < ?
                UNION ALL
                SELECT DATE(fecha_pedido) AS d, total AS v, 2 AS src FROM finance2 WHERE fecha_pedido >= ? AND fecha_pedido < ?
             ) t GROUP BY d ORDER BY d ASC"
        );
        $st->bind_param("ssss", $a, $b, $a, $b);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSplit(string $month): array {
        [$a, $b] = $this->range($month);
        $st = $this->db->prepare(
            "SELECT
              (SELECT IFNULL(SUM(monto),0) FROM finance WHERE fecha >= ? AND fecha < ?) AS f1,
              (SELECT IFNULL(SUM(total),0) FROM finance2 WHERE fecha_pedido >= ? AND fecha_pedido < ?) AS f2"
        );
        $st->bind_param("ssss", $a, $b, $a, $b);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        return ['finance' => (float)$r['f1'], 'finance2' => (float)$r['f2']];
    }

    public function getTransactions(string $month): array {
        [$a, $b] = $this->range($month);
        $st = $this->db->prepare("SELECT fecha, monto FROM finance WHERE fecha >= ? AND fecha < ? ORDER BY fecha DESC");
        $st->bind_param("ss", $a, $b);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function save(string $date, float $amount): void {
        $st = $this->db->prepare("INSERT INTO finance (fecha, monto) VALUES (?, ?) ON DUPLICATE KEY UPDATE monto = VALUES(monto)");
        $st->bind_param("sd", $date, $amount);
        $st->execute();
    }

    /** Todas las métricas en un solo lugar */
    public function metrics(string $month): array {
        $split    = $this->getSplit($month);
        $exp      = $this->getExpenses($month);
        $venta    = $split['finance'] + $split['finance2'];
        $gastos   = $exp['total'];
        $ganancia = $venta * PROFIT_RATE;
        $neto     = $ganancia - $gastos;                 // utilidad real del mes
        $meta     = $gastos / PROFIT_RATE;               // ventas necesarias para cubrir gastos
        $falta    = max(0, $meta - $venta);
        $progreso = $meta > 0 ? min(100, ($venta / $meta) * 100) : 0;
        $faltaPct = $meta > 0 ? 100 - $progreso : 0;

        $daysInMonth = (int)date('t', strtotime($month . '-01'));
        $today       = date('Y-m');
        $elapsed     = $month < $today ? $daysInMonth : ($month > $today ? 0 : (int)date('j'));
        $daysLeft    = max(0, $daysInMonth - $elapsed);

        return compact('split', 'venta', 'gastos', 'ganancia', 'neto', 'meta', 'falta',
                       'progreso', 'faltaPct', 'daysInMonth', 'elapsed', 'daysLeft') + [
            'expRows'    => $exp['rows'],
            'ritmoNeeded'=> $daysLeft > 0 ? $falta / $daysLeft : 0,
            'proyeccion' => $elapsed > 0 ? ($venta / $elapsed) * $daysInMonth : 0,
        ];
    }
}

$ctl       = new FinanceController($conn);
$viewMonth = sanitizeMonth($_GET['month_view'] ?? null);
$debug     = isset($_GET['debug']);

// Exportar CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ventas_' . $viewMonth . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['fecha', 'total_ventas']);
    foreach ($ctl->getDailySales($viewMonth) as $r) fputcsv($out, [$r['d'], $r['total']]);
    exit;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tx_submit'])) {
    $date = $_POST['date'] ?? '';
    $amt  = $_POST['amount'] ?? '';
    $dt   = DateTime::createFromFormat('Y-m-d', $date);
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) $error = 'Sesión inválida. Recarga la página.';
    elseif (!$dt || $dt->format('Y-m-d') !== $date)           $error = 'Fecha inválida.';
    elseif (!is_numeric($amt) || (float)$amt <= 0)            $error = 'Monto inválido.';
    else {
        $ctl->save($date, (float)$amt);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?month_view=' . $dt->format('Y-m'));
        exit;
    }
}

$m            = $ctl->metrics($viewMonth);
$daily        = $ctl->getDailySales($viewMonth);
$transactions = $ctl->getTransactions($viewMonth);

// Series para la gráfica: ventas por día y acumulado
$byDay = [];
foreach ($daily as $r) $byDay[(int)date('j', strtotime($r['d']))] = (float)$r['total'];
$labels = $dailyVals = $cumVals = [];
$run = 0;
$lastDay = $m['elapsed'] > 0 ? $m['elapsed'] : $m['daysInMonth'];
for ($d = 1; $d <= $m['daysInMonth']; $d++) {
    $labels[]    = $d;
    $dailyVals[] = $byDay[$d] ?? 0;
    $run        += $byDay[$d] ?? 0;
    $cumVals[]   = $d <= $lastDay ? $run : null;
}

// Estado y navegación
if ($m['gastos'] <= 0)          { $status = ['warn', 'Sin gastos registrados este mes: la meta es $0.']; }
elseif ($m['falta'] <= 0)       { $status = ['ok',   '¡Meta alcanzada! Ya cubriste tus gastos.']; }
elseif ($m['proyeccion'] >= $m['meta'] && $m['elapsed'] > 0) { $status = ['ok', 'Vas en ritmo de cumplir la meta.']; }
else                            { $status = ['bad',  'Al ritmo actual no alcanzarás la meta.']; }

$prev = date('Y-m', strtotime($viewMonth . '-01 -1 month'));
$next = date('Y-m', strtotime($viewMonth . '-01 +1 month'));
$ringC = 2 * M_PI * 52;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>G502 | Finance Hub</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
<style>
:root{--bg:#0b1220;--panel:#16213a;--panel2:#1c2a49;--accent:#38bdf8;--text:#f1f5f9;--muted:#94a3b8;--ok:#22c55e;--bad:#f87171;--warn:#fbbf24;--border:#2b3a5c}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{background:radial-gradient(1200px 600px at 80% -10%,#15305a55,transparent),var(--bg);color:var(--text);font-family:Inter,system-ui,sans-serif;margin:0;padding:12px;line-height:1.4}
.container{max-width:1200px;margin:0 auto}
a{color:var(--accent);text-decoration:none}
header{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center;margin-bottom:16px}
h1{margin:0;font-size:1.4rem}
.nav{display:flex;gap:6px;align-items:center}
.nav a,.nav button,.nav input{background:var(--panel);border:1px solid var(--border);color:#fff;padding:9px 12px;border-radius:10px;font-size:14px;margin:0;width:auto}
.nav button{background:var(--accent);color:var(--bg);font-weight:700;border:none;cursor:pointer}
.banner{padding:12px 14px;border-radius:12px;margin-bottom:16px;font-size:.9rem;display:flex;gap:8px;align-items:center}
.banner.ok{background:#22c55e1f;border:1px solid var(--ok);color:var(--ok)}
.banner.bad{background:#f871711f;border:1px solid var(--bad);color:var(--bad)}
.banner.warn{background:#fbbf241f;border:1px solid var(--warn);color:var(--warn)}
.hero{display:grid;gap:14px;grid-template-columns:1fr;margin-bottom:16px}
.panel{background:var(--panel);padding:16px;border-radius:16px;border:1px solid rgba(255,255,255,.04)}
.panel h3{margin:0 0 10px;font-size:1rem}
.ring-wrap{display:flex;align-items:center;gap:16px}
.ring{position:relative;width:120px;height:120px;flex:none}
.ring svg{transform:rotate(-90deg)}
.ring .pct{position:absolute;inset:0;display:grid;place-items:center;font-size:1.5rem;font-weight:800}
.ring-info div{margin-bottom:6px;font-size:.85rem;color:var(--muted)}
.ring-info strong{display:block;color:var(--text);font-size:1.05rem}
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.card{background:var(--panel2);border-radius:12px;padding:12px}
.lbl{color:var(--muted);text-transform:uppercase;font-size:.62rem;font-weight:700;letter-spacing:.5px}
.val{font-size:1.1rem;font-weight:800;margin-top:4px;word-break:break-word}
.hint{font-size:.68rem;color:var(--muted);margin-top:2px}
.main{display:grid;gap:16px;grid-template-columns:1fr}
.left{display:flex;flex-direction:column;gap:16px}
.chart-box{height:260px;margin-top:8px}
.tabs{display:flex;gap:6px}
.tabs button{width:auto;margin:0;padding:6px 12px;font-size:.8rem;background:var(--panel2);color:var(--muted);border:1px solid var(--border)}
.tabs button.on{background:var(--accent);color:var(--bg);border-color:var(--accent)}
.tbl{max-height:360px;overflow:auto}
table{width:100%;border-collapse:collapse}
th{text-align:left;color:var(--muted);font-size:.7rem;padding:8px 4px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--panel)}
td{padding:10px 4px;border-bottom:1px solid var(--border);font-size:.85rem}
input,button{width:100%;background:var(--bg);border:1px solid var(--border);color:#fff;padding:13px;margin-top:10px;border-radius:10px;font-size:16px}
button{background:var(--accent);color:var(--bg);font-weight:800;border:none;cursor:pointer}
.error{background:#f871711f;border:1px solid var(--bad);color:var(--bad);padding:10px;border-radius:8px;margin-bottom:12px;font-size:.9rem}
.debug{font-family:monospace;font-size:.75rem;white-space:pre-wrap;color:var(--warn)}
@media(min-width:768px){.grid{grid-template-columns:repeat(3,1fr)}}
@media(min-width:1024px){.hero{grid-template-columns:360px 1fr}.main{grid-template-columns:1fr 320px}}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
  <header>
    <h1>G502 <span style="color:var(--accent)">Finance Hub</span></h1>
    <form method="GET" class="nav">
      <a href="?month_view=<?= e($prev) ?>" aria-label="Mes anterior"><i class="ri-arrow-left-s-line"></i></a>
      <input type="month" name="month_view" value="<?= e($viewMonth) ?>">
      <button type="submit">Ver</button>
      <a href="?month_view=<?= e($next) ?>" aria-label="Mes siguiente"><i class="ri-arrow-right-s-line"></i></a>
      <a href="?month_view=<?= e($viewMonth) ?>&export=1" title="Exportar CSV"><i class="ri-download-2-line"></i></a>
    </form>
  </header>

  <?php if ($error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>

  <div class="banner <?= $status[0] ?>"><i class="ri-information-line"></i> <b><?= e(monthLabel($viewMonth)) ?>:</b> <?= e($status[1]) ?></div>

  <div class="hero">
    <div class="panel">
      <h3>Progreso hacia la meta</h3>
      <div class="ring-wrap">
        <div class="ring">
          <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="52" fill="none" stroke="#2b3a5c" stroke-width="10"/>
            <circle cx="60" cy="60" r="52" fill="none" stroke="<?= $m['falta'] <= 0 && $m['gastos'] > 0 ? '#22c55e' : '#38bdf8' ?>"
                    stroke-width="10" stroke-linecap="round"
                    stroke-dasharray="<?= $ringC ?>" stroke-dashoffset="<?= $ringC * (1 - $m['progreso'] / 100) ?>"/>
          </svg>
          <div class="pct"><?= number_format($m['progreso'], 0) ?>%</div>
        </div>
        <div class="ring-info">
          <div>Vendido <strong><?= money($m['venta']) ?></strong></div>
          <div>Meta <strong><?= money($m['meta']) ?></strong></div>
          <div>Falta <strong style="color:var(--bad)"><?= money($m['falta']) ?> (<?= number_format($m['faltaPct'], 0) ?>%)</strong></div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h3>Resumen</h3>
      <div class="grid">
        <div class="card"><div class="lbl">Ganancia (<?= (int)(PROFIT_RATE*100) ?>%)</div><div class="val" style="color:var(--ok)"><?= money($m['ganancia']) ?></div></div>
        <div class="card"><div class="lbl">Gastos</div><div class="val" style="color:var(--bad)"><?= money($m['gastos']) ?></div><div class="hint"><a href="expenses.php?month_view=<?= urlencode($viewMonth) ?>">ver detalle →</a></div></div>
        <div class="card"><div class="lbl">Utilidad neta</div><div class="val" style="color:<?= $m['neto'] >= 0 ? 'var(--ok)' : 'var(--bad)' ?>"><?= ($m['neto'] < 0 ? '−' : '') . money(abs($m['neto'])) ?></div><div class="hint">ganancia − gastos</div></div>
        <div class="card"><div class="lbl">Ritmo diario necesario</div><div class="val"><?= $m['daysLeft'] > 0 ? money($m['ritmoNeeded']) : '—' ?></div><div class="hint"><?= $m['daysLeft'] ?> días restantes</div></div>
        <div class="card"><div class="lbl">Proyección del mes</div><div class="val"><?= $m['elapsed'] > 0 ? money($m['proyeccion']) : '—' ?></div><div class="hint">según el ritmo actual</div></div>
        <div class="card"><div class="lbl">Ventas por origen</div><div class="val" style="font-size:.85rem"><?= money($m['split']['finance']) ?> + <?= money($m['split']['finance2']) ?></div><div class="hint">finance + pedidos</div></div>
      </div>
    </div>
  </div>

  <div class="main">
    <div class="left">
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h3 style="margin:0">Flujo mensual</h3>
          <div class="tabs">
            <button type="button" class="on" data-mode="cum">Acumulado</button>
            <button type="button" data-mode="day">Diario</button>
          </div>
        </div>
        <div class="chart-box"><canvas id="chart"></canvas></div>
      </div>

      <div class="panel">
        <h3>Transacciones (<?= count($transactions) ?>)</h3>
        <div class="tbl">
          <table>
            <thead><tr><th>Fecha</th><th>Monto</th></tr></thead>
            <tbody>
            <?php if ($transactions): foreach ($transactions as $r): ?>
              <tr><td><?= e(date('d/m/Y', strtotime($r['fecha']))) ?></td><td><strong><?= money((float)$r['monto']) ?></strong></td></tr>
            <?php endforeach; else: ?>
              <tr><td colspan="2" style="text-align:center;opacity:.4;padding:30px 0">No hay transacciones este mes.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div>
      <div class="panel">
        <h3><i class="ri-add-circle-line"></i> Nueva entrada</h3>
        <form method="POST" id="form">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <input type="date" name="date" value="<?= e(date('Y-m-d')) ?>" required>
          <input type="text" id="vis" placeholder="Monto ($)" required autocomplete="off" inputmode="numeric">
          <input type="hidden" name="amount" id="raw">
          <button type="submit" name="tx_submit">Registrar fondos</button>
        </form>
        <p class="hint" style="margin-bottom:0">Si ya hay una entrada ese día, se reemplaza su monto.</p>
      </div>

      <?php if ($debug): ?>
      <div class="panel" style="margin-top:16px">
        <h3>Depuración</h3>
        <div class="debug"><?= e(print_r([
            'mes'                 => $viewMonth,
            'ventas finance'      => $m['split']['finance'],
            'ventas finance2'     => $m['split']['finance2'],
            'filas en expenses'   => $m['expRows'],
            'gastos (suma ABS)'   => $m['gastos'],
            'meta = gastos/0.18'  => $m['meta'],
            'falta = meta-venta'  => $m['falta'],
        ], true)) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const labels = <?= json_encode($labels) ?>;
const cum    = <?= json_encode($cumVals) ?>;
const day    = <?= json_encode($dailyVals) ?>;
const meta   = <?= json_encode(round($m['meta'])) ?>;
const fmt    = new Intl.NumberFormat('es-CO');

const chart = new Chart(document.getElementById('chart'), {
  type: 'line',
  data: { labels, datasets: [
    { label: 'Ventas', data: cum, borderColor: '#38bdf8', backgroundColor: 'rgba(56,189,248,.12)', fill: true, tension: .3, borderWidth: 2, pointRadius: 0, spanGaps: false },
    { label: 'Meta', data: labels.map(() => meta), borderColor: '#fbbf24', borderDash: [6,6], borderWidth: 1.5, pointRadius: 0 }
  ]},
  options: {
    responsive: true, maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: { legend: { labels: { color: '#94a3b8', boxWidth: 12 } },
               tooltip: { callbacks: { label: c => c.dataset.label + ': $' + fmt.format(c.parsed.y) } } },
    scales: {
      y: { ticks: { color: '#64748b', callback: v => '$' + fmt.format(v) }, grid: { color: 'rgba(255,255,255,.05)' } },
      x: { ticks: { color: '#64748b', maxTicksLimit: 10 }, grid: { display: false } }
    }
  }
});

document.querySelectorAll('.tabs button').forEach(b => b.addEventListener('click', () => {
  document.querySelectorAll('.tabs button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  const isCum = b.dataset.mode === 'cum';
  chart.data.datasets[0].data = isCum ? cum : day;
  chart.data.datasets[1].hidden = !isCum;   // la meta solo tiene sentido en acumulado
  chart.update();
}));

const vis = document.getElementById('vis'), raw = document.getElementById('raw');
vis.addEventListener('input', () => {
  const d = vis.value.replace(/\D/g, '');
  raw.value = d;
  vis.value = d ? fmt.format(Number(d)) : '';
});
</script>
</body>
</html>