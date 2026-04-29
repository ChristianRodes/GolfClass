<?php
$role_param = $_GET['role'] ?? '';
$es_profesor = ($role_param === 'teacher');
include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <?php if ($es_profesor): ?>
                <div class="alert alert-light border-start border-4 border-danger mb-4 p-3">
                    <strong class="text-golf">Registro de Instructor</strong><br>
                    <small class="text-muted">Crea tu cuenta de profesor y empieza a recibir alumnos hoy mismo.</small>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="text-center mb-1"><?php echo $es_profesor ? 'Únete como Instructor' : 'Crear Cuenta'; ?></h2>
                    <p class="text-center text-muted small mb-4">
                        <?php echo $es_profesor ? 'Cuenta de profesor · GolfClass' : 'Accede a los mejores instructores de golf'; ?>
                    </p>

                    <form action="register_process.php" method="POST" id="registerForm">
                        <!-- Rol (oculto, controlado por URL) -->
                        <input type="hidden" name="role_id" value="<?php echo $es_profesor ? 2 : 3; ?>">

                        <div class="mb-3">
                            <label class="form-label">Nombre completo</label>
                            <input type="text" name="name" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" id="btnToggle">👁️</button>
                            </div>
                            <small class="text-muted">Mínimo 6 caracteres.</small>
                        </div>

                        <!-- Selector visual de tipo de cuenta -->
                        <div class="mb-3">
                            <label class="form-label">Tipo de cuenta</label>
                            <div class="d-flex gap-2">
                                <div class="form-check flex-fill border rounded p-3 <?php echo !$es_profesor ? 'border-danger bg-light' : ''; ?>"
                                     style="cursor:pointer;" onclick="setRole(3, this)">
                                    <input class="form-check-input d-none" type="radio" name="role_display" value="3" <?php echo !$es_profesor ? 'checked' : ''; ?>>
                                    <div class="text-center">
                                        <div class="fs-4">🏌️</div>
                                        <strong class="small">Alumno</strong>
                                        <p class="text-muted small mb-0">Busca y reserva clases</p>
                                    </div>
                                </div>
                                <div class="form-check flex-fill border rounded p-3 <?php echo $es_profesor ? 'border-danger bg-light' : ''; ?>"
                                     style="cursor:pointer;" onclick="setRole(2, this)">
                                    <input class="form-check-input d-none" type="radio" name="role_display" value="2" <?php echo $es_profesor ? 'checked' : ''; ?>>
                                    <div class="text-center">
                                        <div class="fs-4">🏅</div>
                                        <strong class="small">Instructor</strong>
                                        <p class="text-muted small mb-0">Imparte y gestiona clases</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-golf w-100 mt-3">
                            <?php echo $es_profesor ? 'Registrarme como Instructor' : 'Crear mi cuenta'; ?>
                        </button>
                    </form>

                    <p class="text-center text-muted small mt-3 mb-0">
                        ¿Ya tienes cuenta? <a href="login.php" class="text-golf">Inicia sesión</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    const btnToggle = document.querySelector('#btnToggle');
    const inputPass = document.querySelector('#password');
    btnToggle.addEventListener('click', () => {
        const type = inputPass.getAttribute('type') === 'password' ? 'text' : 'password';
        inputPass.setAttribute('type', type);
        btnToggle.textContent = type === 'password' ? '👁️' : '🙈';
    });

    function setRole(roleId, card) {
        // Actualizar input oculto del formulario
        document.querySelector('input[name="role_id"]').value = roleId;
        // Actualizar estilos
        document.querySelectorAll('.form-check.flex-fill').forEach(el => {
            el.classList.remove('border-danger', 'bg-light');
        });
        card.classList.add('border-danger', 'bg-light');
        // Actualizar texto del botón
        const btn = document.querySelector('button[type="submit"]');
        btn.textContent = roleId == 2 ? 'Registrarme como Instructor' : 'Crear mi cuenta';
    }
</script>

<?php include '../../includes/footer.php'; ?>
