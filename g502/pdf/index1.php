<?php
require_once '../config/db.php';
$message = "";

// 1. MANEJO DE SUBIDA DE ARCHIVOS
if (isset($_POST['submit'])) {
    $targetDir = "uploads/"; 
    if (!is_dir($targetDir)) { mkdir($targetDir, 0755, true); }

    $originalName = basename($_FILES["fileToUpload"]["name"]);
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $newFileName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $targetFilePath = $targetDir . $newFileName;

    $prohibited = ['php', 'exe', 'sh', 'js'];

    if (!in_array($fileExtension, $prohibited)) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFilePath)) {
            $stmt = $conn->prepare("INSERT INTO pdf_files (file_name, file_path, file_type) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $originalName, $targetFilePath, $fileExtension);
            if ($stmt->execute()) {
                $message = "<div class='alert success'>✔ '$originalName' subido con éxito!</div>";
            }
            $stmt->close();
        }
    } else {
        $message = "<div class='alert error'>✖ Tipo de archivo no permitido.</div>";
    }
}

// 2. LÓGICA DE RENOMBRADO
if (isset($_POST['rename_file'])) {
    $fileId = $_POST['file_id'];
    $oldPath = $_POST['old_path'];
    $ext = pathinfo($oldPath, PATHINFO_EXTENSION);
    
    $newNameInput = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_POST['new_name']);
    $newNameWithExt = $newNameInput . "." . $ext;
    $newPath = "uploads/" . time() . "_" . $newNameWithExt;

    if (file_exists($oldPath)) {
        if (rename($oldPath, $newPath)) {
            $stmt = $conn->prepare("UPDATE pdf_files SET file_name = ?, file_path = ? WHERE id = ?");
            $stmt->bind_param("ssi", $newNameWithExt, $newPath, $fileId);
            $stmt->execute();
            $stmt->close();
            $message = "<div class='alert success'>✔ Renombrado a: $newNameWithExt</div>";
        }
    }
}

$result = $conn->query("SELECT * FROM pdf_files ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G502 | File Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --download: #0ea5e9;
        }

        body { background-color: var(--bg); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; border: 1px solid var(--border); }
        
        h2 { margin-top: 0; font-weight: 700; color: var(--primary); text-align: center; }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }

        .upload-section { background: #f8fafc; border: 2px dashed var(--border); border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 30px; }
        
        .btn { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn:hover { background: var(--primary-hover); }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 15px 12px; border-bottom: 1px solid var(--border); }

        .file-info { display: flex; align-items: center; gap: 10px; }
        .file-icon { font-size: 1.4rem; color: var(--text-muted); }
        
        .rename-form { display: flex; gap: 5px; }
        .rename-form input { flex: 1; padding: 8px; border: 1px solid var(--border); border-radius: 4px; font-size: 0.9rem; }

        .badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; font-weight: bold; background: #e2e8f0; }
        .badge-sql { background: #dbeafe; color: #1e40af; }
        .badge-pdf { background: #fee2e2; color: #991b1b; }

        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .actions a { text-decoration: none; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 4px; }
        .btn-view { color: var(--primary); }
        .btn-download { color: var(--download); }
        .btn-edit { color: #f59e0b; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { border: 1px solid var(--border); border-radius: 8px; margin-bottom: 15px; padding: 10px; background: #fff; }
            td { border: none; padding-left: 40%; position: relative; min-height: 40px; }
            td:before { position: absolute; left: 10px; font-weight: bold; color: var(--text-muted); font-size: 0.7rem; text-transform: uppercase; }
            td:nth-of-type(1):before { content: "Archivo"; }
            td:nth-of-type(2):before { content: "Tipo"; }
            td:nth-of-type(3):before { content: "Acciones"; }
            .rename-form { flex-direction: column; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2><i class="fa-solid fa-folder-tree"></i> G502 File Manager</h2>
        
        <?php echo $message; ?>

        <div class="upload-section">
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="fileToUpload" required>
                <button type="submit" name="submit" class="btn"><i class="fa-solid fa-upload"></i> Subir</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Nombre / Renombrar</th>
                    <th>Extensión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): 
                    $ext = $row['file_type'];
                    $bClass = ($ext == 'pdf') ? 'badge-pdf' : (($ext == 'sql') ? 'badge-sql' : '');
                ?>
                <tr>
                    <td>
                        <div class="file-info">
                            <i class="fa-solid fa-file file-icon"></i>
                            <form method="post" class="rename-form">
                                <input type="hidden" name="file_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="old_path" value="<?php echo $row['file_path']; ?>">
                                <input type="text" name="new_name" value="<?php echo pathinfo($row['file_name'], PATHINFO_FILENAME); ?>">
                                <button type="submit" name="rename_file" class="btn" style="padding: 5px 10px; font-size: 0.7rem;">OK</button>
                            </form>
                        </div>
                    </td>
                    <td><span class="badge <?php echo $bClass; ?>">.<?php echo $ext; ?></span></td>
                    <td class="actions">
                        <!-- VER -->
                        <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn-view">
                            <i class="fa-solid fa-eye"></i> Ver
                        </a>
                        
                        <!-- DESCARGAR (Nueva función) -->
                        <a href="<?php echo $row['file_path']; ?>" download="<?php echo $row['file_name']; ?>" class="btn-download">
                            <i class="fa-solid fa-download"></i> Bajar
                        </a>

                        <?php if($ext == 'pdf'): ?>
                            <a href="#" class="btn-edit" onclick="alert('Editor')">
                                <i class="fa-solid fa-pen"></i> Editar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>