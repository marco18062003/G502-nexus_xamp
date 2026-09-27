<?php
// Ensure session is started to check user_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info if they are logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['nombre_usuario'] ?? 'Invitado';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4 no-print">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="fas fa-box-open me-2 text-primary"></i> G502 SYSTEM
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="POS.php">
                        <i class="fas fa-cash-register me-1"></i> Nueva Venta
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($user_id): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn btn-outline-light btn-sm px-3" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">