<?php
/**
 * ventas_helper.php
 *
 * Lógica centralizada para registrar una venta (factura_base + factura).
 * La usan tanto facturas.php (efectivo) como webhook_wompi.php (pago virtual),
 * así evitamos tener la misma lógica de inserción duplicada en dos archivos.
 *
 * Requiere que $conn (mysqli) ya esté definido antes de incluir este archivo.
 */

/**
 * Registra una venta completa dentro de una transacción.
 *
 * @param mysqli $conn
 * @param array  $productos    Igual formato que carritoPos en el JS: [{id, nombre, value_final, cantidad}, ...]
 * @param float  $total
 * @param string $metodoPago   'Efectivo' | 'Wompi'
 * @param string $cambio       Como string, igual que ya lo maneja el POS
 * @param int    $cajeroId
 * @param int    $clienteId
 * @return array  ['status' => 'success', 'numero_factura' => ..., 'venta_id' => ...]
 * @throws Exception si algo falla (el caller decide qué responder)
 */
function registrarVenta(mysqli $conn, array $productos, float $total, string $metodoPago, string $cambio, int $cajeroId, int $clienteId = 1): array
{
    if (empty($productos)) {
        throw new Exception('El carrito está vacío');
    }

    mysqli_begin_transaction($conn);

    try {
        $numeroFactura = "FAC-" . strtoupper(substr(uniqid(), -5));
        $subtotal = $total;
        $impuestos = 0;
        $estado = 'Completada';

        $sqlBase = "INSERT INTO factura_base (numero_factura, fecha_venta, cliente_id, cajero_id, subtotal, impuestos, total, metodo_pago, estado, cambio)
                    VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtBase = mysqli_prepare($conn, $sqlBase);
        if (!$stmtBase) {
            throw new Exception("Error preparando factura_base: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param(
            $stmtBase,
            "siidddsss",
            $numeroFactura,
            $clienteId,
            $cajeroId,
            $subtotal,
            $impuestos,
            $total,
            $metodoPago,
            $estado,
            $cambio
        );

        if (!mysqli_stmt_execute($stmtBase)) {
            throw new Exception("Error en factura_base: " . mysqli_error($conn));
        }
        $ventaId = mysqli_insert_id($conn);

        $sqlDetalle = "INSERT INTO factura (venta_id, producto_id, nombre_producto, precio_unidad, cantidad, subtotal_item)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmtDetalle = mysqli_prepare($conn, $sqlDetalle);
        if (!$stmtDetalle) {
            throw new Exception("Error preparando detalle de factura: " . mysqli_error($conn));
        }

        foreach ($productos as $prod) {
            // Validación mínima: si falta un campo esperado, mejor fallar aquí
            // que insertar una fila corrupta.
            if (!isset($prod['id'], $prod['nombre'], $prod['value_final'], $prod['cantidad'])) {
                throw new Exception("Producto con datos incompletos en el carrito");
            }
            $subtotalItem = $prod['value_final'] * $prod['cantidad'];
            mysqli_stmt_bind_param(
                $stmtDetalle,
                "iisdid",
                $ventaId,
                $prod['id'],
                $prod['nombre'],
                $prod['value_final'],
                $prod['cantidad'],
                $subtotalItem
            );
            if (!mysqli_stmt_execute($stmtDetalle)) {
                throw new Exception("Error en detalle de factura: " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);

        return [
            'status' => 'success',
            'numero_factura' => $numeroFactura,
            'venta_id' => $ventaId
        ];
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
}