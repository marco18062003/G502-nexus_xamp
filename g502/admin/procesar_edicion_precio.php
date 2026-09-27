<?php
require_once('seguridad_admin.php');
require_once('../config/db.php');

// Validar que la petición llegue por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Capturar y limpiar el ID del producto (Obligatorio)
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id > 0) {
        // Capturar y limpiar campos obligatorios
        $ca             = mysqli_real_escape_string($conn, trim($_POST['ca']));
        $producto       = mysqli_real_escape_string($conn, trim($_POST['producto']));
        $caracteristica = mysqli_real_escape_string($conn, trim($_POST['caracteristica']));
        $precio         = (float)$_POST['precio'];
        $margen         = (float)$_POST['margen'];
        $cantidad       = (int)$_POST['cantidad'];
        $marca          = mysqli_real_escape_string($conn, trim($_POST['marca']));
        
        // Campos opcionales (PLU y EAN) - Si están vacíos, se guardará NULL o texto vacío de forma segura
        $plu            = isset($_POST['plu']) ? mysqli_real_escape_string($conn, trim($_POST['plu'])) : '';
        $ean            = isset($_POST['ean']) ? mysqli_real_escape_string($conn, trim($_POST['ean'])) : '';
        
        // Estado de tendencia
        $es_tendencia   = isset($_POST['es_tendencia']) ? (int)$_POST['es_tendencia'] : 0;

        // Validar que los campos críticos no se hayan enviado vacíos en el formulario
        if (empty($ca) || empty($producto)) {
            header("Location: admin_productos.php?status=error&reason=campos_vacios");
            exit();
        }

        // Consulta de actualización estructurada de forma segura
        $query_update = "UPDATE donjorgito1 SET 
                            ca = '$ca', 
                            producto = '$producto', 
                            caracteristica = '$caracteristica', 
                            precio = $precio, 
                            margen = $margen, 
                            cantidad = $cantidad, 
                            marca = '$marca', 
                            plu = " . ($plu !== '' ? "'$plu'" : "NULL") . ", 
                            ean = " . ($ean !== '' ? "'$ean'" : "NULL") . ", 
                            es_tendencia = $es_tendencia 
                         WHERE id = $id";

        // Ejecutar la consulta y verificar si tuvo éxito
        if (mysqli_query($conn, $query_update)) {
            // Redirecciona mostrando el mensaje verde de éxito
            header("Location: admin_productos.php?status=success");
            exit();
        } else {
            // Si la base de datos reporta un error (ej. error de sintaxis o tipos de columnas)
            // Puedes descomentar la siguiente línea temporalmente si necesitas ver el error exacto en pantalla:
            // die("Error en la base de datos: " . mysqli_error($conn));
            header("Location: admin_productos.php?status=error&reason=db_error");
            exit();
        }
    } else {
        header("Location: admin_productos.php?status=error&reason=id_invalido");
        exit();
    }
} else {
    // Si alguien intenta entrar directamente al archivo sin enviar el formulario
    header("Location: admin_productos.php");
    exit();
}
?>