<?php
session_start();
require_once '../../includes/db.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

// Buscar token válido y no expirado
$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE reset_token = ? AND reset_expiry > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user && $token !== '') {
    $error = 'El enlace ha expirado o no es válido. Solicita uno nuevo.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass1) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hash = password_hash($pass1, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id_usuario = ?")
            ->execute([$hash, $user['id_usuario']]);
        $done = true;
    }
}

include '../../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-4">Nueva contraseña</h3>

                    <?php if ($done): ?>
                        <div class="alert alert-success">
                            ¡Contraseña restablecida! <a href="login.php" class="alert-link">Inicia sesión</a>.
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php if (!$user): ?>
                            <a href="forgot_password.php" class="btn btn-golf w-100">Solicitar nuevo enlace</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nueva contraseña</label>
                                <input type="password" name="password" class="form-control" minlength="6" required autofocus>
                                <small class="text-muted">Mínimo 6 caracteres.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Confirmar contraseña</label>
                                <input type="password" name="password2" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-golf w-100">Restablecer contraseña</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
