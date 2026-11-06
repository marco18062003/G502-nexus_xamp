<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>g502 - Poster Maker Pro</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; text-align: center; padding: 40px; background: #222; color: #fff; }
        .card { background: #333; padding: 30px; border-radius: 15px; display: inline-block; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        select, input[type="file"] { margin: 15px 0; padding: 10px; border-radius: 5px; width: 100%; }
        .loader { display: none; color: #00d1b2; font-weight: bold; margin-top: 15px; }
        button { background: #00d1b2; color: #222; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>g502 Poster System</h1>
        <p>Selecciona el tamaño y sube tu PDF</p>

        <label for="escala">Tamaño del póster:</label>
        <select id="escala">
            <option value="2">2x2 (4 hojas - Mediano)</option>
            <option value="3">3x3 (9 hojas - Grande)</option>
            <option value="4">4x4 (16 hojas - Gigante)</option>
        </select>

        <input type="file" id="pdf-upload" accept="application/pdf">
        <div id="status" class="loader">Procesando páginas... esto puede tardar según el tamaño.</div>

        <form id="image-form" action="poster_gen.php" method="POST">
            <input type="hidden" name="image_data" id="image_data">
            <input type="hidden" name="num_divisiones" id="num_divisiones">
        </form>
    </div>
    

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        document.getElementById('pdf-upload').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            const escala = document.getElementById('escala').value;
            if (!file) return;

            document.getElementById('status').style.display = 'block';

            const reader = new FileReader();
            reader.onload = async function() {
                const typedarray = new Uint8Array(this.result);
                const pdf = await pdfjsLib.getDocument(typedarray).promise;
                const page = await pdf.getPage(1);

                // A mayor escala, necesitamos más resolución (scale: 4)
                const viewport = page.getViewport({ scale: 4 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                await page.render({ canvasContext: context, viewport: viewport }).promise;

                document.getElementById('image_data').value = canvas.toDataURL('image/jpeg', 0.85);
                document.getElementById('num_divisiones').value = escala;
                document.getElementById('image-form').submit();
            };
            reader.readAsArrayBuffer(file);
        });
    </script>
</body>
</html>