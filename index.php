<?php 
include 'includes/header.php'; 
?>

<div class="hero-section" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/header.webp') center/cover no-repeat; min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="container-fluid py-5 text-center">
        <h1 class="display-3 fw-bold text-white mb-3">No necesitas más vídeos de Youtube, necesitas <span class="text-golf">GolfClass.</span></h1>
        <p class="col-md-8 mx-auto fs-5 text-white mb-4">
            La plataforma diseñada para conectarte con los mejores instructores del mundo 🌍.
            <?php if(isset($_SESSION['user_name'])): ?>
                <br><br>¡Hola de nuevo, <strong class="text-white"><?php echo $_SESSION['user_name']; ?></strong>!
            <?php endif; ?>
        </p>
        
        <div class="d-flex justify-content-center gap-3">
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="pages/auth/register.php" class="btn btn-golf btn-lg px-4">Empezar ahora</a>
                <a href="pages/auth/login.php" class="btn btn-outline-white btn-lg px-4">Iniciar sesión</a>
            <?php else: ?>
                <a href="#" class="btn btn-golf btn-lg px-4">Explorar Profesores</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// include 'includes/footer.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>