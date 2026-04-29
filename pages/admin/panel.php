<?php
session_start();
require_once '../../includes/db.php';

// Solo administradores (id_rol = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: /GolfClass/index.php");
    exit();
}

// Cambiar estado de una reserva (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reserva'], $_POST['nuevo_estado'])) {
    $estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'];
    $id_reserva   = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
    $nuevo_estado = $_POST['nuevo_estado'];

    if ($id_reserva && in_array($nuevo_estado, $estados_validos)) {
        $stmt = $pdo->prepare("UPDATE reservas SET estado = ? WHERE id_reserva = ?");
        $stmt->execute([$nuevo_estado, $id_reserva]);
    }
    header("Location: panel.php?updated=1");
    exit();
}

// Filtro por estado
$filtro_estado = $_GET['estado'] ?? '';
$estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'];

$where  = "WHERE r.activo = 1";
$params = [];
if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $where  .= " AND r.estado = ?";
    $params[] = $filtro_estado;
}

$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado, r.fecha_creacion,
           alumno.nombre  AS alumno_nombre,  alumno.apellidos  AS alumno_apellidos,  alumno.email AS alumno_email,
           profe.nombre   AS profe_nombre,   profe.apellidos   AS profe_apellidos,
           c.nombre_campo, c.ciudad
    FROM reservas r
    JOIN usuarios  alumno ON r.id_alumno   = alumno.id_usuario
    JOIN profesores p     ON r.id_profesor = p.id_profesor
    JOIN usuarios  profe  ON p.id_usuario  = profe.id_usuario
    JOIN campos_golf c    ON p.id_campo    = c.id_campo
    $where
    ORDER BY r.fecha_clase DESC, r.hora_inicio DESC
");
$stmt->execute($params);
$reservas = $stmt->fetchAll();

// Totales por estado
$totales_stmt = $pdo->query("SELECT estado, COUNT(*) AS total FROM reservas WHERE activo = 1 GROUP BY estado");
$totales_raw  = $totales_stmt->fetchAll();
$totales = ['Pendiente' => 0, 'Confirmada' => 0, 'Cancelada' => 0, 'Completada' => 0];
foreach ($totales_raw as $t) {
    $totales[$t['estado']] = $t['total'];
}

$estado_badge = [
    'Pendiente'   => 'warning',
    'Confirmada'  => 'success',
    'Cancelada'   => 'secondary',
    'Completada'  => 'primary',
];

include '../../includes/header.php';
?>

<div class="container-fluid py-5 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Panel de <span class="text-golf">Administración</span></h2>
        <span class="badge bg-dark fs-6">Admin: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Estado actualizado correctamente.</div>
    <?php endif; ?>

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
        <?php
        $stat_config = [
            'Pendiente'  => ['color' => 'warning',   'icon' => '⏳'],
            'Confirmada' => ['color' => 'success',   'icon' => '✅'],
            'Completada' => ['color' => 'primary',   'icon' => '🏆'],
            'Cancelada'  => ['color' => 'secondary', 'icon' => '❌'],
        ];
        foreach ($stat_config as $estado => $cfg):
        ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-2"><?php echo $cfg['icon']; ?></div>
                <div class="fs-3 fw-bold text-<?php echo $cfg['color']; ?>"><?php echo $totales[$estado]; ?></div>
                <div class="text-muted small"><?php echo $estado; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 d-flex gap-2 flex-wrap">
            <a href="panel.php" class="btn btn-sm <?php echo !$filtro_estado ? 'btn-golf' : 'btn-outline-secondary'; ?>">Todas</a>
            <?php foreach ($estados_validos as $est): ?>
                <a href="panel.php?estado=<?php echo $est; ?>"
                   class="btn btn-sm <?php echo $filtro_estado === $est ? 'btn-golf' : 'btn-outline-secondary'; ?>">
                    <?php echo $est; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla de reservas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($reservas)): ?>
                <p class="text-muted text-center py-5">No hay reservas con el filtro seleccionado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Alumno</th>
                                <th>Profesor</th>
                                <th>Campo</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r): ?>
                                <tr>
                                    <td class="text-muted small">#<?php echo $r['id_reserva']; ?></td>
                                    <td>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($r['alumno_nombre'] . ' ' . $r['alumno_apellidos']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['alumno_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['profe_nombre'] . ' ' . $r['profe_apellidos']); ?></td>
                                    <td>
                                        <span class="small"><?php echo htmlspecialchars($r['nombre_campo']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($r['ciudad']); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?></td>
                                    <td><?php echo substr($r['hora_inicio'], 0, 5) . '–' . substr($r['hora_fin'], 0, 5); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $estado_badge[$r['estado']]; ?>">
                                            <?php echo htmlspecialchars($r['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex gap-1 flex-wrap">
                                            <input type="hidden" name="id_reserva" value="<?php echo $r['id_reserva']; ?>">
                                            <select name="nuevo_estado" class="form-select form-select-sm" style="width:auto;">
                                                <?php foreach ($estados_validos as $est): ?>
                                                    <option value="<?php echo $est; ?>" <?php echo $r['estado'] === $est ? 'selected' : ''; ?>>
                                                        <?php echo $est; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-golf btn-sm">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
