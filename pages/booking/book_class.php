<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $current = urlencode('/GolfClass/pages/booking/book_class.php?' . $_SERVER['QUERY_STRING']);
    header("Location: /GolfClass/pages/auth/login.php?redirect={$current}");
    exit();
}

$id_profesor = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_profesor) {
    header("Location: /GolfClass/pages/profesores/catalogo.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT p.id_profesor, p.foto_perfil, p.precio_hora, p.puntuacion_simulada,
           p.bio, p.experiencia_anos, p.titulaciones, p.clases_online,
           p.ubicacion,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad AS ciudad_campo
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE p.id_profesor = ? AND p.activo = 1 AND u.activo = 1
");
$stmt->execute([$id_profesor]);
$profesor = $stmt->fetch();

if (!$profesor) {
    header("Location: /GolfClass/pages/profesores/catalogo.php");
    exit();
}

$ubicacion_display = $profesor['ubicacion']
    ?: (($profesor['nombre_campo'] ?? '') . (isset($profesor['ciudad_campo']) ? ', ' . $profesor['ciudad_campo'] : ''));

$titulines = array_filter(array_map('trim', explode("\n", $profesor['titulaciones'] ?? '')));

$manana = date('Y-m-d', strtotime('+1 day'));
$horas  = [];
for ($h = 8; $h <= 17; $h++) {
    $horas[] = sprintf('%02d:00', $h);
}

// Verificar ban antes de mostrar el formulario
require_once '../../includes/strikes.php';
$ban_estado = isset($_SESSION['user_id']) ? check_ban($pdo, (int)$_SESSION['user_id']) : null;

$errores_msg = [
    'datos'     => 'Por favor, completa todos los campos.',
    'pasado'    => 'La fecha seleccionada debe ser futura.',
    'ocupado'   => 'Ese horario ya está reservado. Elige otra hora.',
    'bloqueado' => 'El instructor ha bloqueado ese horario. Elige otra fecha u hora.',
    'db'        => 'Error al guardar la reserva. Inténtalo de nuevo.',
    'ban'       => '🚫 Tienes un ban temporal activo. No puedes hacer nuevas reservas por ahora.',
];

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <a href="/GolfClass/pages/profesores/catalogo.php" class="btn btn-outline-secondary btn-sm mb-4">
                ← Volver al catálogo
            </a>

            <?php if (isset($_GET['error']) && isset($errores_msg[$_GET['error']])): ?>
                <div class="alert alert-danger mb-4"><?php echo $errores_msg[htmlspecialchars($_GET['error'])]; ?></div>
            <?php endif; ?>

            <!-- Ficha del profesor -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-4">

                        <!-- Avatar -->
                        <div class="gc-avatar gc-avatar-lg flex-shrink-0">
                            <?php if (!empty($profesor['foto_perfil'])): ?>
                                <img src="/GolfClass/uploads/<?php echo htmlspecialchars($profesor['foto_perfil']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(mb_substr($profesor['nombre'],0,1).mb_substr($profesor['apellidos']??'',0,1)); ?>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                            <h4 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellidos']); ?>
                            </h4>
                            <p class="text-muted mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                <?php echo htmlspecialchars($ubicacion_display); ?>
                            </p>

                            <!-- Badges -->
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php if (!empty($profesor['clases_online'])): ?>
                                    <span class="badge" style="background:var(--golf-red);">Online disponible</span>
                                <?php endif; ?>
                                <?php if (!empty($profesor['experiencia_anos'])): ?>
                                    <span class="badge bg-light text-dark border"><?php echo (int)$profesor['experiencia_anos']; ?> años exp.</span>
                                <?php endif; ?>
                                <?php
                                $s = round((float)($profesor['puntuacion_simulada'] ?? 5));
                                echo '<span class="text-warning">' . str_repeat('★', $s) . str_repeat('☆', 5 - $s) . '</span>';
                                echo ' <small class="text-muted">' . number_format($profesor['puntuacion_simulada'] ?? 5, 1) . '</small>';
                                ?>
                                <span class="fs-5 fw-bold text-golf ms-auto">
                                    <?php echo number_format($profesor['precio_hora'], 0); ?>€<small class="text-muted fw-normal fs-6">/h</small>
                                </span>
                            </div>

                            <!-- Titulaciones -->
                            <?php if (!empty($titulines)): ?>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($titulines as $t): ?>
                                        <span class="badge bg-light text-dark border" style="font-size:.72rem;font-weight:500;">
                                            🎓 <?php echo htmlspecialchars($t); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($profesor['bio'])): ?>
                                <p class="text-muted small mt-2 mb-0"><?php echo htmlspecialchars($profesor['bio']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de reserva -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-golf">Elige fecha y hora</h5>
                    <form action="book_class_process.php" method="POST">
                        <input type="hidden" name="id_profesor" value="<?php echo $profesor['id_profesor']; ?>">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fecha de la clase</label>
                            <input type="date" name="fecha_clase" class="form-control form-control-lg"
                                   min="<?php echo $manana; ?>" value="<?php echo $manana; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Hora de inicio</label>
                            <div class="row g-2">
                                <?php foreach ($horas as $h): ?>
                                    <div class="col-4 col-md-3">
                                        <input type="radio" class="btn-check" name="hora_inicio"
                                               id="h_<?php echo str_replace(':', '', $h); ?>"
                                               value="<?php echo $h; ?>" required>
                                        <label class="btn btn-outline-secondary w-100"
                                               for="h_<?php echo str_replace(':', '', $h); ?>">
                                            <?php echo $h; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted mt-1 d-block">La clase tiene duración de 1 hora.</small>
                        </div>

                        <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                            <span>Total estimado</span>
                            <strong class="text-golf fs-5"><?php echo number_format($profesor['precio_hora'], 2); ?> €</strong>
                        </div>

                        <div class="d-grid">
                            <?php if ($ban_estado): ?>
                                <button type="submit" class="btn btn-golf btn-lg" disabled>Reservar (bloqueado)</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-golf btn-lg">Confirmar reserva</button>
                            <?php endif; ?>
                        </div>
                        <?php if ($ban_estado === 'temporal'): ?>
                            <?php
                            $stmt_ban = $pdo->prepare("SELECT ban_hasta FROM usuarios WHERE id_usuario=?");
                            $stmt_ban->execute([$_SESSION['user_id']]);
                            $bh = $stmt_ban->fetchColumn();
                            ?>
                            <div class="alert alert-danger mt-3 mb-0">
                                🚫 <strong>Ban temporal activo</strong> hasta
                                <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($bh)); ?>.
                                Tienes restringidas las reservas por acumular strikes.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
