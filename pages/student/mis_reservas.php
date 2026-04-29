<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Cancelar reserva (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    $id_reserva = filter_input(INPUT_POST, 'cancelar_id', FILTER_VALIDATE_INT);
    if ($id_reserva) {
        $stmt = $pdo->prepare("
            UPDATE reservas SET estado = 'Cancelada'
            WHERE id_reserva = ? AND id_alumno = ? AND estado = 'Pendiente'
        ");
        $stmt->execute([$id_reserva, $_SESSION['user_id']]);
    }
    header("Location: mis_reservas.php?cancelled=1");
    exit();
}

// Obtener reservas del alumno
$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado, r.fecha_creacion,
           u.nombre AS profesor_nombre, u.apellidos AS profesor_apellidos,
           c.nombre_campo, c.ciudad
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN campos_golf c ON p.id_campo = c.id_campo
    WHERE r.id_alumno = ? AND r.activo = 1
    ORDER BY r.fecha_clase DESC, r.hora_inicio DESC
");
$stmt->execute([$_SESSION['user_id']]);
$reservas = $stmt->fetchAll();

$estado_badge = [
    'Pendiente'   => 'warning',
    'Confirmada'  => 'success',
    'Cancelada'   => 'secondary',
    'Completada'  => 'primary',
];

include '../../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Mis <span class="text-golf">Reservas</span></h2>
        <a href="/GolfClass/index.php#profesores" class="btn btn-golf">+ Nueva reserva</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">¡Reserva realizada con éxito! Te esperamos en la fecha elegida.</div>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-info">Reserva cancelada correctamente.</div>
    <?php endif; ?>

    <?php if (empty($reservas)): ?>
        <div class="text-center py-5">
            <p class="text-muted fs-5">Aún no tienes ninguna reserva.</p>
            <a href="/GolfClass/index.php#profesores" class="btn btn-golf mt-2">Explorar profesores</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($reservas as $r): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($r['profesor_nombre'] . ' ' . $r['profesor_apellidos']); ?>
                                    </h6>
                                    <p class="text-muted small mb-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                        <?php echo htmlspecialchars($r['nombre_campo'] . ', ' . $r['ciudad']); ?>
                                    </p>
                                </div>
                                <div class="col-md-3 mt-2 mt-md-0">
                                    <p class="mb-0 fw-semibold">
                                        <?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        <?php echo substr($r['hora_inicio'], 0, 5) . ' – ' . substr($r['hora_fin'], 0, 5); ?>
                                    </p>
                                </div>
                                <div class="col-md-2 mt-2 mt-md-0">
                                    <span class="badge bg-<?php echo $estado_badge[$r['estado']] ?? 'secondary'; ?> fs-6">
                                        <?php echo htmlspecialchars($r['estado']); ?>
                                    </span>
                                </div>
                                <div class="col-md-1 mt-2 mt-md-0 text-end">
                                    <?php if ($r['estado'] === 'Pendiente'): ?>
                                        <form method="POST" onsubmit="return confirm('¿Cancelar esta reserva?');">
                                            <input type="hidden" name="cancelar_id" value="<?php echo $r['id_reserva']; ?>">
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
