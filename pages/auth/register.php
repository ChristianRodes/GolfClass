<?php include '../../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Crear Cuenta</h2>
                    <form action="register_process.php" method="POST" id="registerForm">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" id="btnToggle">
                                    👁️
                                </button>
                            </div>
                            <small class="text-muted">Mínimo 6 caracteres.</small>
                        </div>
                        <button type="submit" class="btn btn-golf w-100 mt-3">Registrarse</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Lógica del "ojo"
    const btnToggle = document.querySelector('#btnToggle');
    const inputPass = document.querySelector('#password');

    btnToggle.addEventListener('click', () => {
        const type = inputPass.getAttribute('type') === 'password' ? 'text' : 'password';
        inputPass.setAttribute('type', type);
        btnToggle.textContent = type === 'password' ? '👁️' : '🙈';
    });
</script>

<?php include '../../includes/footer.php'; ?>