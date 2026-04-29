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
                <li class="nav-item"><a class="nav-link" href="/GolfClass/index.php">Profesores</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] == 1): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold text-golf" href="/GolfClass/pages/admin/panel.php">Panel Admin</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_role'] == 3): ?>
                        <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/student/mis_reservas.php">Mis Reservas</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/student/profile.php">Mi Perfil</a></li>
                    <li class="nav-item">
                        <span class="badge bg-light text-dark border ms-2 p-2">
                            👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </span>
                    </li>
                    <li class="nav-item"><a class="btn btn-outline-danger btn-sm ms-3" href="/GolfClass/pages/auth/logout.php">Salir</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/auth/login.php">Iniciar Sesión</a></li>
                    <li class="nav-item"><a class="btn btn-golf ms-lg-3" href="/GolfClass/pages/auth/register.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>