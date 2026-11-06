<?php
// 1. Configuración de rutas
define('FPDF_FONTPATH', __DIR__ . '/fpdf/font/'); 
require_once('fpdf/fpdf.php');
require_once('fpdi/src/autoload.php');

use setasign\Fpdi\Fpdi;

if (isset($_GET['file'])) {
    $fullPath = $_GET['file'];

    // 2. Parámetros de edición (puedes cambiarlos desde la URL)
    // Ejemplo: edit_pdf.php?file=archivo.pdf&x=50&y=50&w=40&txt=Hola
    $posX = isset($_GET['x']) ? $_GET['x'] : 10;          // Posición Horizontal
    $posY = isset($_GET['y']) ? $_GET['y'] : 10;          // Posición Vertical
    $anchoParche = isset($_GET['w']) ? $_GET['w'] : 50;   // Ancho del espacio blanco
    $altoParche = isset($_GET['h']) ? $_GET['h'] : 10;    // Alto del espacio blanco
    $nuevoTexto = isset($_GET['txt']) ? $_GET['txt'] : "NUEVA PALABRA";

    if (!file_exists($fullPath)) {
        die("Error: Archivo no encontrado.");
    }

    $pdf = new Fpdi();
    
    try {
        $pageCount = $pdf->setSourceFile($fullPath);
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // --- PASO A: EL PARCHE BLANCO ---
            $pdf->SetFillColor(255, 255, 255); // Color blanco puro
            // Dibujamos el rectángulo sin borde ('F' de Fill)
            $pdf->Rect($posX, $posY, $anchoParche, $altoParche, 'F'); 

            // --- PASO B: EL NUEVO TEXTO ---
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(0, 0, 0); // Texto negro para que combine
            
            // Ajustamos el cursor un poco dentro del parche para que quede bien
            $pdf->SetXY($posX, $posY);
            $pdf->Cell($anchoParche, $altoParche, utf8_decode($nuevoTexto), 0, 0, 'L');
        }

        // 'I' para ver el resultado en el navegador
        $pdf->Output('I', 'g502_editado.pdf');
        
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}