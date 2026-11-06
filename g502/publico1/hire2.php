<?php
include '../config/db.php'; // Ensure you have your DB connection here
session_start();

// Fetch active job offers
$query = "SELECT * FROM vacantes ORDER BY fecha_publicacion DESC";
$resultado = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Talento | Don Jorgito g502</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/icon1.png" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700&family=Montserrat:wght@200;300;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        :root { --gold: #d4af37; --deep-black: #080808; }
        body { background-color: var(--deep-black); color: #fff; font-family: 'Montserrat', sans-serif; }
        
        .offer-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(212, 175, 55, 0.2);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .offer-card:hover { border-color: var(--gold); background: rgba(212, 175, 55, 0.05); }
        
        /* Modal Styling to keep it "Pretty" */
        .modal-content { background: #111; border: 1px solid var(--gold); color: #fff; border-radius: 25px; }
        .form-control { background: #1a1a1a; border: 1px solid #333; color: #fff; }
        .form-control:focus { background: #222; border-color: var(--gold); color: #fff; box-shadow: none; }
        .btn-gold { background: var(--gold); color: #000; font-weight: 600; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container py-5">
    <h1 class="text-center mb-5" style="font-family: 'Playfair Display';">Oportunidades en <span class="text-gold">g502</span></h1>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php if(mysqli_num_rows($resultado) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                    <div class="offer-card d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="text-gold"><?php echo $row['titulo']; ?></h3>
                            <p class="mb-0 text-secondary"><?php echo substr($row['descripcion'], 0, 100); ?>...</p>
                        </div>
                        <button class="btn btn-outline-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#applyModal" 
                                onclick="setPosition('<?php echo $row['titulo']; ?>')">
                            Postular
                        </button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center opacity-50">No hay vacantes abiertas en este momento. Vuelve pronto.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="modal-header border-0">
                <h2 class="modal-title fs-4">Enviar mi <span class="text-gold">Propuesta</span></h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="applied_position" id="applied_position">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Nombre Completo</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Tu "Deal" / Propuesta Valor</label>
                        <textarea name="deal" class="form-control" rows="4" placeholder="¿Por qué tú?"></textarea>
                    </div>

                    <div class="mb-4 text-center">
                        <label class="form-label d-block text-start small">Hoja de Vida</label>
                        <input type="file" name="cv_file" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-gold w-100 py-3">ENVIAR AL PROYECTO G502</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setPosition(name) {
        document.getElementById('applied_position').value = name;
    }
</script>
</body>
</html>