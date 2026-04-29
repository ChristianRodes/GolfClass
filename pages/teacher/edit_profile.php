<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: /GolfClass/index.php");
    exit();
}

$id_usuario = (int) $_SESSION['user_id'];

// Datos del usuario (nombre, email, etc.)
$stmt = $pdo->prepare("SELECT nombre, apellidos, email, telefono FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

// Perfil de profesor (puede no existir aún)
$stmt = $pdo->prepare("SELECT * FROM profesores WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$profesor = $stmt->fetch();

// Todos los campos de golf para el dropdown
$campos = $pdo->query("SELECT id_campo, nombre_campo, ciudad FROM campos_golf WHERE activo = 1 ORDER BY ciudad, nombre_campo")->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <div class="me-3" style="width:48px;height:48px;background:var(--golf-red);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="white" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/></svg>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">Mi Perfil <span class="text-golf">Coach</span></h2>
                    <small class="text-muted">Gestiona tu información profesional</small>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">¡Perfil actualizado correctamente!</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_GET['error'] === 'datos' ? 'Por favor completa todos los campos obligatorios.' : 'Error al guardar. Inténtalo de nuevo.'; ?>
                </div>
            <?php endif; ?>

            <!-- Datos personales (solo lectura) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-muted">Datos de cuenta</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellidos'] ?? '')); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                        </div>
                    </div>
                    <small class="text-muted">Para cambiar tus datos personales ve a <a href="/GolfClass/pages/student/profile.php">Mi Perfil</a>.</small>
                </div>
            </div>

            <!-- Datos profesionales -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-golf">Información Profesional</h5>
                    <form action="edit_profile_process.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Campo de Golf donde impartes clases <span class="text-danger">*</span></label>
                            <select name="id_campo" class="form-select" required>
                                <option value="">-- Selecciona un campo --</option>
                                <?php foreach ($campos as $c): ?>
                                    <option value="<?php echo $c['id_campo']; ?>"
                                        <?php echo ($profesor && $profesor['id_campo'] == $c['id_campo']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre_campo'] . ' — ' . $c['ciudad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Precio por hora (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="precio_hora" class="form-control" min="1" max="999" step="0.5"
                                           value="<?php echo htmlspecialchars($profesor['precio_hora'] ?? ''); ?>" required>
                                    <span class="input-group-text">€/h</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Años de experiencia</label>
                                <input type="number" name="experiencia_anos" class="form-control" min="0" max="50"
                                       value="<?php echo htmlspecialchars($profesor['experiencia_anos'] ?? 0); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tu ciudad</label>
                                <input type="text" name="ciudad" class="form-control" maxlength="100"
                                       placeholder="Ej: Madrid"
                                       value="<?php echo htmlspecialchars($profesor['ciudad'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Puntuación simulada <span class="text-muted fw-normal">(1.0 – 5.0)</span></label>
                                <input type="number" name="puntuacion_simulada" class="form-control" min="1" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($profesor['puntuacion_simulada'] ?? 5.0); ?>">
                                <small class="text-muted">Solo para demostración del TFG.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Biografía profesional</label>
                            <textarea name="bio" class="form-control" rows="5" maxlength="1000"
                                      placeholder="Cuéntales a tus futuros alumnos quién eres, tu experiencia y tu metodología…"><?php echo htmlspecialchars($profesor['bio'] ?? ''); ?></textarea>
                            <small class="text-muted">Máximo 1.000 caracteres.</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-golf px-4">Guardar cambios</button>
                            <a href="/GolfClass/index.php" class="btn btn-outline-secondary">Ver catálogo</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
