<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GolfClass - Reserva tus clases</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --golf-red: #ea3c3c; /* El rojo estilo Skillest */
        --golf-red-hover: #d12e2e;
        --golf-dark: #1a1a1a;
    }
    
    body { 
        font-family: 'Inter', sans-serif; 
        background-color: #f8f9fa; 
    }
    
    .navbar { 
        background-color: #ffffff; 
        border-bottom: 1px solid #eaeaea;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    /* Logo oscuro con un toque rojo opcional */
    .navbar-brand { 
        font-weight: 700; 
        color: var(--golf-dark) !important; 
    }
    
    /* Nuestro botón principal personalizado */
    .btn-golf { 
        background-color: var(--golf-red); 
        color: white; 
        border-radius: 6px; 
        font-weight: 600;
        border: none;
        padding: 8px 20px;
    }
    
    .btn-golf:hover { 
        background-color: var(--golf-red-hover); 
        color: white; 
    }

    /* Clase para textos destacados */
    .text-golf {
        color: var(--golf-red) !important;
    }

    /* Botón outline blanco */
    .btn-outline-white {
        color: white;
        border: 2px solid white;
        background-color: transparent;
        font-weight: 600;
        border-radius: 6px;
    }

    .btn-outline-white:hover {
        color: var(--golf-red);
        background-color: white;
        border-color: white;
    }

    .navbar-nav .nav-link {
        color: var(--golf-dark) !important;
    }

    .navbar-nav .nav-link:hover {
        color: var(--golf-red) !important;
    }

    .navbar-toggler {
        border-color: var(--golf-dark);
    }

    .navbar-toggler-icon {
        filter: none;
    }

    /* ── Avatares de iniciales (global) ─────────────────────── */
    .gc-avatar {
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--golf-red);
        color: white;
        font-weight: 700;
        flex-shrink: 0;
        line-height: 1;
        user-select: none;
        letter-spacing: -.5px;
    }
    .gc-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gc-avatar-xs  { width: 32px; height: 32px; font-size: .75rem; }
    .gc-avatar-sm  { width: 44px; height: 44px; font-size: .95rem; }
    .gc-avatar-md  { width: 56px; height: 56px; font-size: 1.1rem; }
    .gc-avatar-lg  { width: 80px; height: 80px; font-size: 1.5rem;  }
    .gc-avatar-xl  { width: 104px; height: 104px; font-size: 2rem; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand text-golf" href="/GolfClass/index.php">GolfClass</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="/GolfClass/index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/profesores/catalogo.php">Instructores</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] == 1): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold text-golf" href="/GolfClass/pages/admin/panel.php">Reservas</a></li>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/admin/usuarios.php">Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/admin/recursos.php">Recursos</a></li>
                    <?php elseif ($_SESSION['user_role'] == 2): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold text-golf" href="/GolfClass/pages/teacher/dashboard.php">Mi Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/teacher/edit_profile.php">Mi Perfil Coach</a></li>
                    <?php elseif ($_SESSION['user_role'] == 3): ?>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/student/mis_reservas.php">Mis Reservas</a></li>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/student/profile.php">Mi Perfil</a></li>
                    <?php endif; ?>

                    <?php
                    // Badge de mensajes no leídos
                    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM mensajes WHERE id_receptor = ? AND leido = 0");
                    $stmt_unread->execute([$_SESSION['user_id']]);
                    $n_unread = (int) $stmt_unread->fetchColumn();
                    ?>
                    <li class="nav-item ms-1">
                        <a class="nav-link position-relative px-2" href="/GolfClass/pages/chat/index.php" title="Mensajes">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                            </svg>
                            <?php if ($n_unread > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                                      style="background:var(--golf-red);font-size:.6rem;padding:3px 5px;">
                                    <?php echo min($n_unread, 99); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <span class="badge bg-light text-dark border ms-2 p-2">
                            👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="btn btn-outline-danger btn-sm ms-3" href="/GolfClass/pages/auth/logout.php">Salir</a></li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link text-muted" href="/GolfClass/pages/auth/register.php?role=teacher">¿Eres Coach?</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/auth/login.php">Iniciar Sesión</a></li>
                    <li class="nav-item"><a class="btn btn-golf ms-lg-2" href="/GolfClass/pages/auth/register.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>