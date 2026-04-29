<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_profesor = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_profesor) {
    header("Location: /GolfClass/index.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.id_profesor, p.precio_hora, p.rating, p.bio, p.foto_perfil,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE p.id_profesor = ? AND p.activo = 1 AND u.activo = 1
");
$stmt->execute([$id_profesor]);
$profesor = $stmt->fetch();

if (!$profesor) {
    header("Location: /GolfClass/index.php");
    exit();
}

$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime('+1 day'));

$horas_disponibles = [];
for ($h = 8; $h <= 17; $h++) {
    $horas_disponibles[] = sprintf('%02d:00', $h);
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <a href="/GolfClass/index.php#profesores" class="btn btn-outline-secondary btn-sm mb-4">← Volver al catálogo</a>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?php
                    $errores = [
                        'datos'      => 'Por favor, completa todos los campos.',
                        'pasado'     => 'La fecha seleccionada debe ser futura.',
                        'ocupado'    => 'Ese horario ya está reservado. Elige otra hora.',
                        'db'         => 'Error al guardar la reserva. Inténtalo de nuevo.',
                    ];
                    $code = htmlspecialchars($_GET['error']);
                    echo $errores[$code] ?? 'Error desconocido.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- Tarjeta del profesor -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($profesor['foto_perfil']) && file_exists('../../uploads/' . $profesor['foto_perfil'])): ?>
                            <img src="/GolfClass/uploads/<?php echo htmlspecialchars($profesor['foto_perfil']); ?>"
                                 class="rounded-circle me-4" style="width:80px;height:80px;object-fit:cover;" alt="Foto">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-4"
                                 style="width:80px;height:80px;background:var(--golf-red);color:white;font-size:1.5rem;font-weight:700;flex-shrink:0;">
                                <?php echo strtoupper(mb_substr($profesor['nombre'], 0, 1) . mb_substr($profesor['apellidos'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="mb-1 fw-bold"><?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellidos']); ?></h4>
                            <p class="text-muted mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                <?php echo htmlspecialchars($profesor['nombre_campo'] . ', ' . $profesor['ciudad']); ?>
                            </p>
                            <span class="text-warning fw-bold me-2">
                                <?php $r = round($profesor['rating']); echo str_repeat('★', $r) . str_repeat('☆', 5 - $r); ?>
                            </span>
                            <span class="fs-5 fw-bold text-golf"><?php echo number_format($profesor['precio_hora'], 0); ?>€/h</span>
                        </div>
                    </div>
                    <?php if (!empty($profesor['bio'])): ?>
                        <p class="text-muted mt-3 mb-0"><?php echo htmlspecialchars($profesor['bio']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulario de reserva -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-golf">Selecciona fecha y hora</h5>
                    <form action="reservar_process.php" method="POST">
                        <input type="hidden" name="id_profesor" value="<?php echo $profesor['id_profesor']; ?>">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fecha de la clase</label>
                            <input type="date" name="fecha_clase" class="form-control form-control-lg"
                                   min="<?php echo $manana; ?>"
                                   value="<?php echo $manana; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Hora de inicio</label>
                            <div class="row g-2">
                                <?php foreach ($horas_disponibles as $hora): ?>
                                    <div class="col-4 col-md-3">
                                        <input type="radio" class="btn-check" name="hora_inicio"
                                               id="hora_<?php echo str_replace(':', '', $hora); ?>"
                                               value="<?php echo $hora; ?>" required>
                                        <label class="btn btn-outline-secondary w-100" for="hora_<?php echo str_replace(':', '', $hora); ?>">
                                            <?php echo $hora; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">La clase tiene duración de 1 hora.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-golf btn-lg">Confirmar reserva</button>
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
