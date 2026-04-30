<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit(); }

$id_reserva = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_reserva) { header("Location: mis_reservas.php"); exit(); }

// Fetch reserva (solo del alumno actual, pendiente y con >24h)
$stmt = $pdo->prepare("
    SELECT r.*, p.id_profesor, p.precio_hora,
           u.nombre AS prof_nombre, u.apellidos AS prof_apellidos,
           COALESCE(p.ubicacion, CONCAT(c.nombre_campo,', ',c.ciudad)) AS ubicacion_display
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE r.id_reserva = ? AND r.id_alumno = ? AND r.estado = 'Pendiente' AND r.activo = 1
");
$stmt->execute([$id_reserva, $_SESSION['user_id']]);
$reserva = $stmt->fetch();

if (!$reserva) { header("Location: mis_reservas.php"); exit(); }

$clase_ts = strtotime($reserva['fecha_clase'] . ' ' . $reserva['hora_inicio']);
if ($clase_ts - time() <= 86400) {
    header("Location: mis_reservas.php?error=plazo"); exit();
}

$manana = date('Y-m-d', strtotime('+1 day'));
$horas  = [];
for ($h = 8; $h <= 17; $h++) $horas[] = sprintf('%02d:00', $h);

include '../../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <a href="mis_reservas.php" class="btn btn-outline-secondary btn-sm mb-4">← Mis Reservas</a>
            <h3 class="fw-bold mb-4">Modificar <span class="text-golf">Reserva #<?php echo $id_reserva; ?></span></h3>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">Ese horario ya no está disponible. Elige otro.</div>
            <?php endif; ?>

            <!-- Info del instructor -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($reserva['prof_nombre'].' '.$reserva['prof_apellidos']); ?></h6>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($reserva['ubicacion_display']); ?></p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 text-golf">Nueva fecha y hora</h5>
                    <form action="modificar_reserva_process.php" method="POST">
                        <input type="hidden" name="id_reserva" value="<?php echo $id_reserva; ?>">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Fecha</label>
                            <input type="date" name="fecha_clase" class="form-control form-control-lg"
                                   min="<?php echo $manana; ?>"
                                   value="<?php echo htmlspecialchars($reserva['fecha_clase']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Hora de inicio</label>
                            <div class="row g-2">
                                <?php foreach ($horas as $h): ?>
                                    <div class="col-4 col-md-3">
                                        <input type="radio" class="btn-check" name="hora_inicio"
                                               id="mh_<?php echo str_replace(':','',$h); ?>"
                                               value="<?php echo $h; ?>" required
                                               <?php echo (substr($reserva['hora_inicio'],0,5)===$h)?'checked':''; ?>>
                                        <label class="btn btn-outline-secondary w-100"
                                               for="mh_<?php echo str_replace(':','',$h); ?>">
                                            <?php echo $h; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-golf btn-lg w-100">Confirmar cambio</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
