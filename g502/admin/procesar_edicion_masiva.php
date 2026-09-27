<?php
require_once('seguridad_admin.php');
require_once('../config/db_pdo.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['items']) || !is_array($_POST['items'])) {
    header('Location: admin_productos.php?status=error');
    exit;
}

$pagina = isset($_POST['pagina']) ? (int)$_POST['pagina'] : 1;
$buscar = isset($_POST['buscar']) ? $_POST['buscar'] : '';

$sql = "UPDATE donjorgito1 SET
            ca = :ca,
            producto = :producto,
            caracteristica = :caracteristica,
            precio = :precio,
            margen = :margen,
            value_final = :value_final,
            cantidad = :cantidad,
            marca = :marca,
            plu = :plu,
            ean = :ean,
            es_tendencia = :es_tendencia,
            estado = :estado
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

$exito = true;

try {
    $pdo->beginTransaction();

    foreach ($_POST['items'] as $id => $campos) {
        $id = (int)$id;
        $id = (int)$id;
        if ($id <= 0) continue;

        $ca             = trim($campos['ca'] ?? '');
        $producto       = trim($campos['producto'] ?? '');
        $caracteristica = trim($campos['caracteristica'] ?? '');
        $precio         = (float)($campos['precio'] ?? 0);
        $margen         = (float)($campos['margen'] ?? 0);
        $value_final    = (float)($campos['value_final'] ?? 0);
        $cantidad       = (int)($campos['cantidad'] ?? 0);
        $marca          = trim($campos['marca'] ?? '');
        $plu            = trim($campos['plu'] ?? '');
        $ean            = trim($campos['ean'] ?? '');
        $es_tendencia   = (isset($campos['es_tendencia']) && $campos['es_tendencia'] == 1) ? 1 : 0;
        $estado = trim($campos['estado'] ?? 'activo');
        $allowed_estados = ['activo', 'descontinuado', 'agotado'];
        if (!in_array($estado, $allowed_estados)) {
         $estado = 'activo';
        }

        // Validación mínima
        if ($ca === '' || $producto === '') {
            $exito = false;
            break;
        }

        $stmt->execute([
            ':ca'             => $ca,
            ':producto'       => $producto,
            ':caracteristica' => $caracteristica,
            ':precio'         => $precio,
            ':margen'         => $margen,
            ':value_final'    => $value_final,
            ':cantidad'       => $cantidad,
            ':marca'          => $marca,
            ':plu'            => $plu,
            ':ean'            => $ean,
            ':es_tendencia'   => $es_tendencia,
            ':estado'         => $estado,
            ':id'             => $id,
        ]);
    }

    if ($exito) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al actualizar productos: " . $e->getMessage());
    $exito = false;
}

$redirect = "admin_productos.php?status=" . ($exito ? "success" : "error") . "&pagina=" . $pagina;
if (!empty($buscar)) {
    $redirect .= "&buscar=" . urlencode($buscar);
}
header("Location: $redirect");
exit;