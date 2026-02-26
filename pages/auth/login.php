<?php include '../../includes/header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">¡Registro correcto! Ya puedes iniciar sesión.</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Iniciar Sesión</h2>
                    <form action="login_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password_login" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" id="btnToggleLogin">👁️</button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-golf w-100 mt-3">Entrar</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-muted">¿No tienes cuenta? Regístrate aquí</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Reutilizamos la lógica del "ojo"
    const btnToggle = document.querySelector('#btnToggleLogin');
    const inputPass = document.querySelector('#password_login');

    btnToggle.addEventListener('click', () => {
        const type = inputPass.getAttribute('type') === 'password' ? 'text' : 'password';
        inputPass.setAttribute('type', type);
        btnToggle.textContent = type === 'password' ? '👁️' : '🙈';
    });
</script>

<?php include '../../includes/footer.php'; ?>