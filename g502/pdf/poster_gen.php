<?php
require_once('fpdf/fpdf.php');

if (isset($_POST['image_data'])) {
    $img = $_POST['image_data'];
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    $escala = (int)$_POST['num_divisiones']; // 2, 3 o 4
    $tempFile = 'temp_g502_' . time() . '.jpg';
    file_put_contents($tempFile, $data);

    $pdf = new FPDF();
    $w = 215.9; // mm (Carta)
    $h = 279.4; // mm (Carta)

    // El ancho y alto total de la imagen será (hojas * tamaño_hoja)
    $imgFullW = $w * $escala;
    $imgFullH = $h * $escala;

    // Recorremos filas y columnas
    for ($fila = 0; $fila < $escala; $fila++) {
        for ($col = 0; $col < $escala; $col++) {
            $pdf->AddPage('P', 'Letter');
            
            // Calculamos el desplazamiento negativo
            $posX = -($col * $w);
            $posY = -($fila * $h);
            
            // Colocamos la parte de la imagen correspondiente
            $pdf->Image($tempFile, $posX, $posY, $imgFullW, $imgFullH);
            
            // Marcas de corte (Cruces grises en las esquinas)
            $pdf->SetDrawColor(180, 180, 180);
            $pdf->Line($w-10, 0, $w-10, 5); // Guía derecha
            $pdf->Line(0, $h-10, 5, $h-10); // Guía inferior
        }
    }

    unlink($tempFile);
    $pdf->Output('D', "g502_poster_{$escala}x{$escala}.pdf");
}
?><?php
require_once('fpdf/fpdf.php');

if (isset($_POST['image_data'])) {
    // 1. Limpiar los datos Base64 que vienen de JS
    $img = $_POST['image_data'];
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);

    // 2. Guardar imagen temporalmente
    $tempFile = 'temp_poster_' . time() . '.jpg';
    file_put_contents($tempFile, $data);

    $pdf = new FPDF();
    $w = 215.9; // mm
    $h = 279.4; // mm

    // Queremos 4 hojas (2x2)
    // Cada hoja mostrará un cuadrante de la imagen escalada
    $quadrants = [
        [0, 0],       // Arriba Izq
        [-$w, 0],      // Arriba Der
        [0, -$h],     // Abajo Izq
        [-$w, -$h]     // Abajo Der
    ];

    foreach ($quadrants as $q) {
        $pdf->AddPage('P', 'Letter');
        // Insertamos la imagen al doble del tamaño de la hoja
        // Los valores negativos en X y Y hacen el efecto de "recorte"
        $pdf->Image($tempFile, $q[0], $q[1], $w * 2, $h * 2);
        
        // Guía de corte tenue
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Rect(0, 0, $w, $h);
    }

    // 3. Limpiar y descargar
    unlink($tempFile);
    $pdf->Output('D', 'g502_poster_final.pdf');
}
?>