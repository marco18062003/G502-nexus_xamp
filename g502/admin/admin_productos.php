<?php
require_once('seguridad_admin.php');
require_once('../config/db.php');

$por_pagina = 20;
$pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$busqueda_escaped = mysqli_real_escape_string($conn, $busqueda);

$where = !empty($busqueda)
    ? " WHERE ca LIKE '%$busqueda_escaped%' OR producto LIKE '%$busqueda_escaped%' OR caracteristica LIKE '%$busqueda_escaped%' OR plu LIKE '%$busqueda_escaped%' OR ean LIKE '%$busqueda_escaped%'"
    : "";

$result_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM donjorgito1" . $where);
$total_registros = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_registros / $por_pagina);

$query = "SELECT id, ca, producto, caracteristica, precio, margen, value_final, cantidad, imagen, es_tendencia, marca, plu, ean, estado
          FROM donjorgito1 $where ORDER BY producto ASC LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel G502 - Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .table-img { width: 45px; height: 45px; object-fit: cover; border-radius: 6px; cursor: pointer; }
        .img-placeholder { width: 45px; height: 45px; background: #e9ecef; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #adb5bd; font-size: 18px; }
        .form-control-sm, .form-select-sm { font-size: 0.82rem; }
        td { vertical-align: middle !important; }
        .table thead th { background-color: #0d6efd; color: white; font-weight: 500; font-size: 0.82rem; white-space: nowrap; text-align: center; }
        .btn-actualizar { padding: 4px 10px; font-size: 0.8rem; }
        .img-preview-modal img { max-width: 100%; border-radius: 10px; }

        /* --- Responsive: tabla se convierte en tarjetas en pantallas angostas --- */
        @media (max-width: 768px) {
            .table thead {
                display: none;
            }

            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100% !important;
            }

            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                padding: 10px;
                background: #fff;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            }

            .table td {
                border: none !important;
                padding: 8px 4px !important;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
            }

            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6c757d;
                font-size: 0.75rem;
                text-transform: uppercase;
                flex-shrink: 0;
                width: 90px;
            }

            .table td input,
            .table td select,
            .table td .input-group {
                width: 100% !important;
                max-width: 200px;
            }

            .table td[data-label="Img"] {
                justify-content: center;
            }
            .table td[data-label="Img"]::before {
                display: none;
            }

            .table td[data-label="ID"] {
                justify-content: center;
                font-size: 1rem;
            }
            .table td[data-label="ID"]::before {
                display: none;
            }

            .save-bar {
                position: sticky;
                bottom: 0;
                z-index: 10;
                box-shadow: 0 -2px 8px rgba(0,0,0,0.08);
            }
            .save-bar .btn {
                width: 100%;
                padding: 12px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-3">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <span class="fw-bold fs-5 align-middle">Gestión de Productos</span>
                <span class="badge bg-primary ms-2 align-middle"><?= $total_registros ?> productos</span>
            </div>
            <div class="text-muted small">
                Admin: <strong><?= htmlspecialchars($_SESSION['nombre_usuario']) ?></strong>
                | <a href="../publico1/logout.php" class="text-danger text-decoration-none">Cerrar sesión</a>
            </div>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show py-2">
                    <i class="fas fa-check-circle me-1"></i> Producto actualizado correctamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] === 'error'): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2">
                    <i class="fas fa-exclamation-circle me-1"></i> Error al actualizar. Revisa los datos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-2">
                <form method="GET" action="admin_productos.php" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6 col-lg-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="buscar" id="busqueda" class="form-control"
                                   placeholder="Buscar por categoría, producto, PLU, EAN..."
                                   autocomplete="off" value="<?= htmlspecialchars($busqueda) ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="escanearParaFila('busqueda')" title="Escanear para buscar">
                                <i class="fas fa-barcode"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary px-3">Buscar</button>
                    </div>
                    <?php if (!empty($busqueda)): ?>
                    <div class="col-auto">
                        <a href="admin_productos.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md text-md-end text-muted small align-self-center mt-1 mt-md-0">
                        Página <?= $pagina_actual ?> de <?= $total_paginas ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
        <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <form action="procesar_edicion_masiva.php" method="POST">
        <input type="hidden" name="pagina" value="<?= $pagina_actual ?>">
        <input type="hidden" name="buscar" value="<?= htmlspecialchars($busqueda) ?>">

        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0" style="font-size: 0.83rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Img</th>
                        <th>Categoría</th>
                        <th>Producto</th>
                        <th>Característica</th>
                        <th>Precio</th>
                        <th>Margen</th>
                        <th>value_final</th>
                        <th>Stock</th>
                        <th>Marca</th>
                        <th>PLU</th>
                        <th>EAN</th>
                        <th>Estado</th>
                        <th>Tendencia</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($item = mysqli_fetch_assoc($resultado)): $id = $item['id']; ?>
                    <tr>
                        <td data-label="ID" class="text-muted text-center fw-bold"><?= $id ?></td>

                        <td data-label="Img" class="text-center">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($item['imagen']) ?>"
                                     class="table-img"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     onclick="verImagen('<?= htmlspecialchars($item['imagen']) ?>', '<?= htmlspecialchars($item['producto']) ?>')"
                                     title="Ver imagen">
                                <div class="img-placeholder" style="display:none;"><i class="fas fa-image"></i></div>
                            <?php else: ?>
                                <div class="img-placeholder m-auto"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </td>

                        <td data-label="Categoría">
                            <input type="text" name="items[<?= $id ?>][ca]" class="form-control form-control-sm" style="width:95px"
                                   value="<?= htmlspecialchars($item['ca']) ?>" placeholder="Categ." required>
                        </td>
                        <td data-label="Producto">
                            <input type="text" name="items[<?= $id ?>][producto]" class="form-control form-control-sm" style="width:160px"
                                   value="<?= htmlspecialchars($item['producto']) ?>" required>
                        </td>
                        <td data-label="Característica">
                            <input type="text" name="items[<?= $id ?>][caracteristica]" class="form-control form-control-sm" style="width:110px"
                                   value="<?= htmlspecialchars($item['caracteristica']) ?>">
                        </td>
                        <td data-label="Precio">
                            <input type="number" name="items[<?= $id ?>][precio]" step="0.01" min="0" class="form-control form-control-sm" style="width:90px"
                                   value="<?= htmlspecialchars($item['precio']) ?>" required>
                        </td>
                        <td data-label="Margen">
                            <input type="number" name="items[<?= $id ?>][margen]" step="0.01" min="0" class="form-control form-control-sm" style="width:70px"
                                   value="<?= htmlspecialchars($item['margen'] ?? 0) ?>" required>
                        </td>
                        <td data-label="Value final">
                            <input type="number" name="items[<?= $id ?>][value_final]" step="0.01" min="0" class="form-control form-control-sm" style="width:90px"
                                   value="<?= htmlspecialchars($item['value_final'] ?? 0) ?>" required>
                        </td>
                        <td data-label="Stock">
                            <input type="number" name="items[<?= $id ?>][cantidad]" min="0" class="form-control form-control-sm" style="width:70px"
                                   value="<?= htmlspecialchars($item['cantidad']) ?>" required>
                        </td>
                        <td data-label="Marca">
                            <input type="text" name="items[<?= $id ?>][marca]" class="form-control form-control-sm" style="width:90px"
                                   value="<?= htmlspecialchars($item['marca']) ?>" placeholder="Marca">
                        </td>
                        <td data-label="PLU">
                            <div class="input-group input-group-sm" style="width: 125px;">
                                <input type="text" name="items[<?= $id ?>][plu]" id="plu_<?= $id ?>" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($item['plu']) ?>" placeholder="PLU">
                                <button type="button" class="btn btn-outline-secondary px-2 d-flex align-items-center"
                                        onclick="escanearParaFila('plu_<?= $id ?>')" title="Escanear PLU">
                                    <i class="fas fa-barcode"></i>
                                </button>
                            </div>
                        </td>
                        <td data-label="EAN">
                            <div class="input-group input-group-sm" style="width: 145px;">
                                <input type="text" name="items[<?= $id ?>][ean]" id="ean_<?= $id ?>" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($item['ean']) ?>" placeholder="EAN">
                                <button type="button" class="btn btn-outline-secondary px-2 d-flex align-items-center"
                                        onclick="escanearParaFila('ean_<?= $id ?>')" title="Escanear EAN">
                                    <i class="fas fa-barcode"></i>
                                </button>
                            </div>
                        </td>
                        <td data-label="estado">
                          <select name="items[<?= $id ?>][estado]" class="form-select form-select-sm" style="width:125px">
                            <option value="activo" <?= ($item['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="descontinuado" <?= ($item['estado'] ?? '') === 'descontinuado' ? 'selected' : '' ?>>Descontinuado</option>
                            <option value="agotado" <?= ($item['estado'] ?? '') === 'agotado' ? 'selected' : '' ?>>Agotado</option>
                           </select>
                        </td>
                        <td data-label="Tendencia">
                            <select name="items[<?= $id ?>][es_tendencia]" class="form-select form-select-sm" style="width:85px">
                                <option value="1" <?= $item['es_tendencia'] == 1 ? 'selected' : '' ?>>⭐ Sí</option>
                                <option value="0" <?= $item['es_tendencia'] == 0 ? 'selected' : '' ?>>No</option>
                            </select>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 text-end border-top bg-light save-bar">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Guardar todos los cambios
            </button>
        </div>

        </form>
    </div>
</div>

        <?php if ($total_paginas > 1): ?>
        <nav class="mt-3 d-flex justify-content-center">
            <ul class="pagination pagination-sm shadow-sm">
                <?php if ($pagina_actual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $pagina_actual - 1 ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                    <li class="page-item <?= $i === $pagina_actual ? 'active' : '' ?>">
                        <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $pagina_actual + 1 ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-warning shadow-sm">
                <i class="fas fa-box-open me-2"></i>
                No se encontraron productos <?= !empty($busqueda) ? "para: <strong>" . htmlspecialchars($busqueda) . "</strong>" : "" ?>.
            </div>
        <?php endif; ?>

    </div>

    <div class="modal fade" id="modalImagen" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalImagenTitulo"></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center img-preview-modal">
                    <img id="modalImagenSrc" src="" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Variables globales para el control del flujo del escáner
let inputDestinoActual = null;
let html5QrcodeScanner = null;
let modalEscanerBootstrap = null;

// Inicializar el modal de Bootstrap cuando cargue la página
document.addEventListener("DOMContentLoaded", function() {
    modalEscanerBootstrap = new bootstrap.Modal(document.getElementById('modalEscaner'));
});

function escanearParaFila(inputId) {
    // 1. Guardamos el input que recibirá el código
    inputDestinoActual = document.getElementById(inputId);
    
    // 2. Mostramos el modal donde se verá la cámara
    modalEscanerBootstrap.show();

    // 3. Retardo mínimo para asegurar que el contenedor HTML '#lector-camara' ya exista en el DOM
    setTimeout(() => {
        // Inicializamos la instancia del lector de códigos
        html5QrcodeScanner = new Html5Qrcode("lector-camara");
        
        // Configuración óptima para leer códigos de barras tradicionales (EAN-13, EAN-8, UPC, Code 128)
        const configuracion = { 
            fps: 15, 
            qrbox: { width: 280, height: 160 }, // Caja guía rectangular ideal para barras horizontales
            aspectRatio: 1.0 
        };

        // Iniciamos la cámara trasera ('environment')
        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            configuracion,
            (textoCodigo) => {
                // Callback si lee el código con éxito
                alDetectarCodigoExitoso(textoCodigo);
            },
            (error) => {
                // Silenciamos los errores de escaneo continuo por consola para no saturarla
            }
        ).catch(err => {
            console.error("Error al iniciar la cámara: ", err);
            alert("No se pudo acceder a la cámara. Asegúrate de dar los permisos correspondientes.");
            modalEscanerBootstrap.hide();
        });
    }, 400);
}

function alDetectarCodigoExitoso(codigoDetectado) {
    if (inputDestinoActual) {
        // Insertamos el valor obtenido en el input correspondiente
        inputDestinoActual.value = codigoDetectado;
        
        // Feedback visual verde de éxito en el input
        inputDestinoActual.classList.add('is-valid');
        setTimeout(() => inputDestinoActual.classList.remove('is-valid'), 1500);
        
        // Si escaneó desde el buscador general superior, enviamos el form de inmediato
        if (inputDestinoActual.id === 'busqueda') {
            inputDestinoActual.closest('form').submit();
        }
    }
    // Cerramos la cámara y ocultamos el modal de forma limpia
    detenerEscaner();
    modalEscanerBootstrap.hide();
}

function detenerEscaner() {
    // Apaga la cámara y libera los recursos de hardware del dispositivo
    if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
        html5QrcodeScanner.stop().then(() => {
            document.getElementById('lector-camara').innerHTML = ""; // Limpiamos el contenedor
        }).catch(err => console.error("Error al detener la cámara: ", err));
    }
    inputDestinoActual = null;
}
    </script>
    <script src="https://unpkg.com/html5-qrcode"></script>

<div class="modal fade" id="modalEscaner" tabindex="-1" aria-labelledby="modalEscanerLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="modalEscanerLabel"><i class="fas fa-camera me-2"></i>Escaneando Código...</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="detenerEscaner()"></button>
            </div>
            <div class="modal-body bg-dark p-0 position-relative" style="min-height: 300px;">
                <div id="lector-camara" style="width: 100%;"></div>
            </div>
            <div class="modal-footer py-1 justify-content-center">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" onclick="detenerEscaner()">Cancelar</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>