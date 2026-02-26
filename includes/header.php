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
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .navbar { background-color: #ffffff; border-bottom: 2px solid #e9ecef; }
        .navbar-brand { font-weight: 700; color: #1a431d !important; }
        .btn-golf { background-color: #28a745; color: white; border-radius: 8px; }
        .btn-golf:hover { background-color: #218838; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand" href="/GolfClass/index.php">⛳ GolfClass</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="/GolfClass/index.php">Profesores</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/GolfClass/pages/student/profile.php">Mi Perfil</a></li>
                    <li class="nav-item">
                        <span class="badge bg-light text-dark border ms-2 p-2">
                            👤 <?php echo $_SESSION['user_name']; ?>
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