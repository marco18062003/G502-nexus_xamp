<?php
session_start();
require_once '../config/db.php'; 

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Cajero';

// Consulta actualizada
$query = "SELECT id, nombre_usuario, nombre_completo, email, telefono, ciudad, rol, fecha_registro, direccion FROM usuarios ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error en la consulta de g502: " . mysqli_error($conn));
}
$usuarios = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Clientes - DON JORGITO (g502)</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/posdes.css?v=<?php echo time(); ?>">
    <style>
        .main-content { padding: 20px; background-color: #f8f9fa; min-height: 100vh; }
        .card-table { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .table thead { background-color: #343a40; color: white; }
        .badge-admin { background-color: #dc3545; }
        .badge-user { background-color: #0d6efd; }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>



<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold"><i class="fas fa-users-cog me-2"></i>Gestión de Clientes</h3>
            <button type="button" class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
                <i class="fas fa-plus-circle me-2"></i>NUEVO CLIENTE
            </button>
        </div>

        <div class="card card-table overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Usuario / Nombre</th>
                            <th>Contacto</th>
                            <th>Ubicación</th>
                            <th>Rol</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="ps-4">
    <span class="fw-bold text-dark" style="font-family: monospace; letter-spacing: 1px;">
        REF-<?php echo str_pad($u['id'], 4, "0", STR_PAD_LEFT); ?>
    </span>
</td>
                            <td>
                                <strong><?php echo htmlspecialchars($u['nombre_usuario']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($u['nombre_completo'] ?? 'Sin nombre'); ?></small>
                            </td>
                            <td>
                                <i class="fas fa-envelope fa-xs"></i> <?php echo htmlspecialchars($u['email']); ?><br>
                                <i class="fas fa-phone fa-xs"></i> <?php echo htmlspecialchars($u['telefono'] ?? '-'); ?>
                            </td>
                            <td>
                                <i class="fas fa-country fa-xs"></i> <?php echo htmlspecialchars($u['ciudad'] ?? '-'); ?><br>
                                <i class="fas fa-home fa-xs"></i> <?php echo htmlspecialchars($u['direccion'] ?? '-'); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo ($u['rol'] === 'administrador') ? 'badge-admin' : 'badge-user'; ?> rounded-pill">
                                    <?php echo strtoupper($u['rol']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="editarus.php?id=<?php echo $u['id']; ?>" class="btn btn-outline-dark"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-outline-danger" onclick="confirmarEliminar(<?php echo $u['id']; ?>)"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-labelledby="modalNuevoClienteLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 15px;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="modalNuevoClienteLabel"><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="guardarus.php" method="POST">
          <div class="modal-body p-4">
              <div class="row mb-3">
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Nombre de Usuario</label>
                      <input type="text" name="nombre_usuario" class="form-control" required placeholder="Ej: jorgito_cliente">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Nombre Completo</label>
                      <input type="text" name="nombre_completo" class="form-control" placeholder="Ej: Jorge Pérez">
                  </div>
              </div>
              <div class="row mb-3">
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Correo Electrónico</label>
                      <input type="email" name="email" class="form-control" required placeholder="correo@ejemplo.com">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Teléfono</label>
                      <input type="text" name="telefono" class="form-control" placeholder="Ej: 123456789">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Dirección</label>
                      <input type="text" name="direccion" class="form-control" placeholder="Ej: Calle 123, Ciudad + Descripción de referencia">
                  </div>

              </div>
              <div class="mb-3">
                  <label class="form-label fw-bold">Contraseña</label>
                  <input type="password" name="password" class="form-control" required placeholder="Crea una clave segura">
              </div>
              <div class="row mb-3">
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Ciudad</label>
                      <input type="text" name="ciudad" class="form-control">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Rol</label>
                      <select name="rol" class="form-select">
                          <option value="usuario">Usuario</option>
                          <option value="Empleado">Empleado</option>
                          <option value="administrador">Administrador</option>
                      </select>
                  </div>
              </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            <button type="submit" class="btn btn-dark px-4">GUARDAR CLIENTE</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarEliminar(id) {
    if(confirm('¿Eliminar a este cliente?')) {
        window.location.href = 'eliminarusu.php?id=' + id;
    }
}
</script>
</body>
</html>