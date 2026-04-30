<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: /GolfClass/index.php");
    exit();
}

$id_usuario = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT nombre, apellidos, email, telefono FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM profesores WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$profesor = $stmt->fetch();

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="d-flex align-items-center mb-4">
                <div class="me-3 rounded-circle d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px;background:var(--golf-red);flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="white" viewBox="0 0 16 16">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">Mi Perfil <span class="text-golf">Coach</span></h2>
                    <small class="text-muted">Gestiona tu información profesional visible en el catálogo</small>
                </div>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">¡Perfil actualizado correctamente!</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    $errores = [
                        'datos'       => 'Completa los campos obligatorios (precio y ubicación).',
                        'tipo_foto'   => 'Formato de imagen no válido. Usa JPG, PNG, WEBP o GIF.',
                        'foto_grande' => 'La imagen supera el límite de 3 MB.',
                        'db'          => 'Error al guardar. Inténtalo de nuevo.',
                    ];
                    echo $errores[htmlspecialchars($_GET['error'])] ?? 'Error desconocido.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Cuenta (solo lectura) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:.06em;">Datos de cuenta</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted">Nombre</label>
                            <input type="text" class="form-control bg-light"
                                   value="<?php echo htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellidos'] ?? '')); ?>" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted">Email</label>
                            <input type="text" class="form-control bg-light"
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" readonly>
                        </div>
                    </div>
                    <small class="text-muted">Para cambiar estos datos ve a <a href="/GolfClass/pages/student/profile.php">Mi Perfil</a>.</small>
                </div>
            </div>

            <!-- Datos profesionales -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted mb-4 text-uppercase" style="font-size:.75rem;letter-spacing:.06em;">Información profesional</h6>

                    <form action="edit_profile_process.php" method="POST" enctype="multipart/form-data">

                        <!-- FOTO DE PERFIL ──────────────────────────── -->
                        <div class="mb-4 text-center">
                            <label class="form-label fw-semibold d-block">Foto de perfil</label>

                            <div class="position-relative d-inline-block">
                                <!-- Vista previa / avatar actual -->
                                <div class="gc-avatar gc-avatar-xl mx-auto" id="avatarPreview"
                                     style="cursor:pointer;" onclick="document.getElementById('inputFoto').click()"
                                     title="Haz clic para cambiar la foto">
                                    <?php if (!empty($profesor['foto_perfil'])): ?>
                                        <img id="previewImg"
                                             src="/GolfClass/uploads/<?php echo htmlspecialchars($profesor['foto_perfil']); ?>"
                                             alt="">
                                    <?php else: ?>
                                        <span id="previewInitials">
                                            <?php echo strtoupper(mb_substr($usuario['nombre'],0,1).mb_substr($usuario['apellidos']??'',0,1)); ?>
                                        </span>
                                        <img id="previewImg" src="" alt="" style="display:none;">
                                    <?php endif; ?>
                                </div>

                                <!-- Botón de cámara -->
                                <button type="button"
                                        class="btn btn-golf btn-sm rounded-circle position-absolute"
                                        style="bottom:4px;right:4px;width:30px;height:30px;padding:0;line-height:1;"
                                        onclick="document.getElementById('inputFoto').click()"
                                        title="Cambiar foto">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="white" viewBox="0 0 16 16">
                                        <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                                        <path d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4zm.5 2a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m9 2.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Input oculto -->
                            <input type="file" id="inputFoto" name="foto_perfil"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="d-none" onchange="previewFoto(this)">
                            <div class="mt-2">
                                <small class="text-muted">JPG, PNG, WEBP o GIF · Máx. 3 MB</small>
                            </div>
                            <?php if (!empty($profesor['foto_perfil'])): ?>
                                <div class="form-check mt-2 justify-content-center d-flex">
                                    <input class="form-check-input me-2" type="checkbox" name="eliminar_foto" id="eliminar_foto" value="1">
                                    <label class="form-check-label text-muted small" for="eliminar_foto">Eliminar foto actual</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <hr class="mb-4">

                        <!-- UBICACIÓN LIBRE ──────────────────────────── -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">¿Dónde impartes clases? <span class="text-danger">*</span></label>
                            <input type="text" name="ubicacion" class="form-control" maxlength="255"
                                   placeholder="Ej: Club de Golf La Moraleja, Madrid · A domicilio · Online · Club privado Bilbao…"
                                   value="<?php echo htmlspecialchars($profesor['ubicacion'] ?? ''); ?>" required>
                            <small class="text-muted">Escribe libremente: nombre del club, ciudad, a domicilio, online, etc.</small>
                        </div>

                        <!-- CLASES ONLINE -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="clases_online"
                                       id="clases_online" value="1" style="width:2.5em;height:1.3em;"
                                       <?php echo (!empty($profesor['clases_online'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label ms-2 fw-semibold" for="clases_online">
                                    También ofrezco clases online
                                    <span class="badge ms-1" style="background:var(--golf-red);font-size:.72rem;">ONLINE</span>
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1 ms-5">Se mostrará una etiqueta en tu tarjeta del catálogo.</small>
                        </div>

                        <hr class="my-4">

                        <!-- PRECIO Y EXPERIENCIA -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Precio por hora (€) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="precio_hora" class="form-control"
                                           min="1" max="999" step="0.5" required
                                           value="<?php echo htmlspecialchars($profesor['precio_hora'] ?? ''); ?>">
                                    <span class="input-group-text">€/h</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Años de experiencia</label>
                                <input type="number" name="experiencia_anos" class="form-control"
                                       min="0" max="50"
                                       value="<?php echo htmlspecialchars($profesor['experiencia_anos'] ?? 0); ?>">
                            </div>
                        </div>

                        <!-- CIUDAD Y PUNTUACIÓN -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tu ciudad de residencia</label>
                                <input type="text" name="ciudad" class="form-control" maxlength="100"
                                       placeholder="Ej: Madrid"
                                       value="<?php echo htmlspecialchars($profesor['ciudad'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">
                                    Puntuación simulada
                                    <span class="text-muted fw-normal">(1.0 – 5.0)</span>
                                </label>
                                <input type="number" name="puntuacion_simulada" class="form-control"
                                       min="1" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($profesor['puntuacion_simulada'] ?? 5.0); ?>">
                                <small class="text-muted">Solo para demo del TFG.</small>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- TITULACIONES -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Titulaciones y certificaciones</label>
                            <textarea name="titulaciones" class="form-control" rows="4"
                                      placeholder="Una titulación por línea. Ejemplos:&#10;PGA España — Instructor Nivel 2&#10;Real Federación Española de Golf — Entrenador Nacional&#10;TrackMan Certified Professional"><?php echo htmlspecialchars($profesor['titulaciones'] ?? ''); ?></textarea>
                            <small class="text-muted">Escribe una por línea. Se mostrarán como etiquetas en tu perfil.</small>
                        </div>

                        <!-- BIO -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Biografía profesional</label>
                            <textarea name="bio" class="form-control" rows="5" maxlength="1000"
                                      placeholder="Cuéntales a tus futuros alumnos quién eres, tu trayectoria y tu metodología de enseñanza…"><?php echo htmlspecialchars($profesor['bio'] ?? ''); ?></textarea>
                            <small class="text-muted">Máximo 1.000 caracteres.</small>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-golf px-4">Guardar cambios</button>
                            <a href="/GolfClass/pages/profesores/catalogo.php" class="btn btn-outline-secondary">Ver catálogo</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    // Validar tamaño en cliente (3 MB)
    if (file.size > 3 * 1024 * 1024) {
        alert('La imagen es demasiado grande. Máximo 3 MB.');
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        const img       = document.getElementById('previewImg');
        const initials  = document.getElementById('previewInitials');
        img.src         = e.target.result;
        img.style.display = 'block';
        if (initials) initials.style.display = 'none';
    };
    reader.readAsDataURL(file);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
