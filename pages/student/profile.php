<?php
session_start();
require_once '../../includes/db.php';

// Redirigir si no hay sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener datos del usuario
$sql = "SELECT nombre, apellidos, email, telefono FROM usuarios WHERE id_usuario = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

include '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">¡Tus datos se han actualizado correctamente!</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-golf">Mis Datos Personales</h3>
                    <form action="update_profile.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" name="apellidos" class="form-control" value="<?php echo htmlspecialchars($user['apellidos'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control text-muted" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                <small class="text-muted">El email no se puede modificar.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-golf">Guardar Cambios</button>
                    </form>
                </div>
            </div>

            <div class="card border-danger mb-5 shadow-sm">
                <div class="card-body p-4">
                    <h4 class="text-danger">Zona Peligrosa</h4>
                    <p class="text-muted">Si desactivas tu cuenta, tu perfil dejará de ser visible y no podrás hacer nuevas reservas. Podrás reactivarla más adelante iniciando sesión de nuevo.</p>
                    <form action="deactivate_account.php" method="POST" onsubmit="return confirm('¿Estás totalmente seguro de que quieres desactivar tu cuenta?');">
                        <button type="submit" class="btn btn-outline-danger">Desactivar mi cuenta</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>