<?php 
// 1. Incluimos el header que ya tiene el session_start() y el menú dinámico
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <div class="p-5 mb-4 bg-light rounded-3 shadow-sm">
        <div class="container-fluid py-5">
            <h1 class="display-5 fw-bold text-success">Bienvenido a GolfClass ⛳</h1>
            <p class="col-md-8 fs-4">
                La plataforma diseñada para conectar a los mejores instructores de golf con alumnos apasionados. 
                <?php if(isset($_SESSION['user_name'])): ?>
                    ¡Hola de nuevo, <strong><?php echo $_SESSION['user_name']; ?></strong>!
                <?php endif; ?>
            </p>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="pages/auth/register.php" class="btn btn-success btn-lg">Empezar ahora</a>
                <a href="pages/auth/login.php" class="btn btn-outline-secondary btn-lg">Iniciar sesión</a>
            <?php else: ?>
                <a href="#" class="btn btn-success btn-lg">Explorar Profesores</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// 2. Aquí iría el catálogo de profesores
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>