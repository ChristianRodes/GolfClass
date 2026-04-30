<?php
session_start();
require_once '../../includes/db.php';

$msg_ok  = '';
$msg_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id_usuario, nombre FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expiry = ? WHERE id_usuario = ?")
            ->execute([$token, $expiry, $user['id_usuario']]);

        // En producción esto se enviaría por email. En el TFG mostramos el enlace directamente.
        $link = "http://localhost/GolfClass/pages/auth/reset_password.php?token={$token}";
        $msg_ok = "Enlace generado (en producción se enviaría por email):<br>
                   <a href='{$link}' class='alert-link'>{$link}</a>";
    } else {
        // Mensaje genérico por seguridad
        $msg_ok = "Si el email existe, recibirás un enlace en breve.";
    }
}

include '../../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="fw-bold mb-1">Recuperar contraseña</h3>
                    <p class="text-muted small mb-4">Introduce tu email y te enviaremos un enlace para restablecerla.</p>

                    <?php if ($msg_ok): ?>
                        <div class="alert alert-success"><?php echo $msg_ok; ?></div>
                    <?php endif; ?>
                    <?php if ($msg_err): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($msg_err); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email de tu cuenta</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-golf w-100">Enviar enlace</button>
                    </form>
                    <p class="text-center text-muted small mt-3">
                        <a href="login.php" class="text-golf">← Volver al inicio de sesión</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
