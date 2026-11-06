<?php
session_start();
require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Políticas de Servicio | DON JORGITO</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; }
        .policy-header { background: #232f3e; color: white; padding: 60px 0; text-align: center; }
        .policy-card { background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-top: -40px; }
        .policy-section { margin-bottom: 30px; border-left: 4px solid #e47911; padding-left: 20px; }
        .policy-section h3 { color: #232f3e; font-size: 1.25rem; font-weight: 700; }
        .warning-box { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 10px; color: #856404; font-weight: 600; }
    </style>
</head>
<body>

<div class="policy-header">
    <div class="container">
        <h1 style="color:#fff3cd;">Políticas de Distribución y Servicio</h1>
        <p>Transparencia y legalidad en cada entrega de g502</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="policy-card">
                
                <div class="warning-box mb-4">
                    <i class="fas fa-exclamation-triangle"></i> PROHÍBASE EL EXPENDIO DE BEBIDAS EMBRIAGANTES A MENORES DE EDAD. EL EXCESO DE ALCOHOL ES PERJUDICIAL PARA LA SALUD.
                </div>

                <div class="policy-section">
                    <h3>1. Restricción de Edad</h3>
                    <p>En cumplimiento con la <strong>Ley 124 de 1994</strong>, DON JORGITO prohíbe la venta de bebidas alcohólicas a menores de 18 años. Al realizar un pedido, el cliente confirma que es mayor de edad. Nuestro domiciliario exigirá la cédula de ciudadanía original al momento de la entrega.</p>
                </div>

                <div class="policy-section">
                    <h3>2. Tiempos de Entrega y Cobertura</h3>
                    <p>Realizamos distribución de licores y alimentos (snacks/pasabocas) en las zonas autorizadas. Los tiempos de entrega pueden variar entre 30 a 60 minutos dependiendo de la demanda y las condiciones climáticas. Los pedidos realizados fuera del horario legal de expendio en Colombia serán programados para el día siguiente.</p>
                </div>

                <div class="policy-section">
                    <h3>3. Estado de los Productos (Alimentos)</h3>
                    <p>Garantizamos que todos nuestros comestibles (chips, pasabocas, etc.) cuentan con registro sanitario vigente y fechas de vencimiento visibles. Por razones de higiene y salud, no se aceptan devoluciones de alimentos una vez el sello de seguridad ha sido abierto.</p>
                </div>

                <div class="policy-section">
                    <h3>4. Política de Devoluciones de Licores</h3>
                    <p>Solo se realizarán cambios o devoluciones si el producto presenta defectos en la estampilla de rentas departamentales o filtraciones visibles antes de ser abierto. No aceptamos devoluciones de botellas con sellos rotos.</p>
                </div>

                <div class="policy-section">
                    <h3>5. Consumo Responsable</h3>
                    <p>DON JORGITO promueve el consumo responsable. Nos reservamos el derecho de cancelar entregas en lugares públicos o si detectamos que el solicitante se encuentra en estado de embriaguez extrema que ponga en riesgo la seguridad del domiciliario.</p>
                </div>

                <div class="text-center mt-5">
                    <a href="index.php" class="btn btn-warning px-5 py-3 fw-bold text-white" style="background: #e47911; border-radius: 50px;">Entendido, volver a la tienda</a>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>