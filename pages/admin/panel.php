<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: /GolfClass/index.php"); exit();
}

// Cambiar estado de una reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reserva'], $_POST['nuevo_estado'])) {
    $estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'];
    $id_reserva   = filter_input(INPUT_POST, 'id_reserva', FILTER_VALIDATE_INT);
    $nuevo_estado = $_POST['nuevo_estado'];
    if ($id_reserva && in_array($nuevo_estado, $estados_validos)) {
        $pdo->prepare("UPDATE reservas SET estado = ? WHERE id_reserva = ?")->execute([$nuevo_estado, $id_reserva]);
    }
    // Mantener filtros activos
    $qs = http_build_query(array_intersect_key($_POST, array_flip(['estado','fecha_ini','fecha_fin','q_usuario','id_profesor_f'])));
    header("Location: panel.php?updated=1&{$qs}"); exit();
}

// ── Filtros ──────────────────────────────────────────────────────────────
$filtro_estado    = $_GET['estado'] ?? '';
$filtro_fecha_ini = $_GET['fecha_ini'] ?? '';
$filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
$filtro_usuario   = trim($_GET['q_usuario'] ?? '');
$filtro_profesor  = filter_input(INPUT_GET, 'id_profesor_f', FILTER_VALIDATE_INT) ?: 0;
$exportar_csv     = isset($_GET['csv']);

$estados_validos = ['Pendiente', 'Confirmada', 'Cancelada', 'Completada'];

$where  = "WHERE r.activo = 1";
$params = [];

if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $where .= " AND r.estado = ?"; $params[] = $filtro_estado;
}
if ($filtro_fecha_ini) {
    $where .= " AND r.fecha_clase >= ?"; $params[] = $filtro_fecha_ini;
}
if ($filtro_fecha_fin) {
    $where .= " AND r.fecha_clase <= ?"; $params[] = $filtro_fecha_fin;
}
if ($filtro_usuario !== '') {
    $where .= " AND (alumno.nombre LIKE ? OR alumno.apellidos LIKE ? OR alumno.email LIKE ?)";
    $like = "%{$filtro_usuario}%"; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($filtro_profesor) {
    $where .= " AND r.id_profesor = ?"; $params[] = $filtro_profesor;
}

$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado, r.fecha_creacion,
           alumno.nombre AS alumno_nombre, alumno.apellidos AS alumno_apellidos, alumno.email AS alumno_email,
           profe.nombre  AS profe_nombre,  profe.apellidos  AS profe_apellidos,
           COALESCE(p.ubicacion, CONCAT(c.nombre_campo,', ',c.ciudad)) AS ubicacion_display
    FROM reservas r
    JOIN usuarios alumno ON r.id_alumno   = alumno.id_usuario
    JOIN profesores p    ON r.id_profesor = p.id_profesor
    JOIN usuarios profe  ON p.id_usuario  = profe.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    {$where}
    ORDER BY r.fecha_clase DESC, r.hora_inicio DESC
");
$stmt->execute($params);
$reservas = $stmt->fetchAll();

// ── CSV Export ───────────────────────────────────────────────────────────
if ($exportar_csv) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reservas_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['#','Alumno','Email','Instructor','Ubicación','Fecha','Hora inicio','Hora fin','Estado','Creada el'], ';');
    foreach ($reservas as $r) {
        fputcsv($out, [
            $r['id_reserva'],
            $r['alumno_nombre'].' '.$r['alumno_apellidos'],
            $r['alumno_email'],
            $r['profe_nombre'].' '.$r['profe_apellidos'],
            $r['ubicacion_display'],
            $r['fecha_clase'],
            substr($r['hora_inicio'],0,5),
            substr($r['hora_fin'],0,5),
            $r['estado'],
            $r['fecha_creacion'],
        ], ';');
    }
    fclose($out);
    exit();
}

// ── Stats globales ───────────────────────────────────────────────────────
$totales_stmt = $pdo->query("SELECT estado, COUNT(*) AS total FROM reservas WHERE activo=1 GROUP BY estado");
$totales_raw  = $totales_stmt->fetchAll();
$totales = ['Pendiente'=>0,'Confirmada'=>0,'Cancelada'=>0,'Completada'=>0];
foreach ($totales_raw as $t) $totales[$t['estado']] = $t['total'];

// Top 3 instructores más reservados
$top_inst = $pdo->query("
    SELECT u.nombre, u.apellidos, COUNT(*) AS total
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    WHERE r.activo=1 AND r.estado != 'Cancelada'
    GROUP BY r.id_profesor ORDER BY total DESC LIMIT 3
")->fetchAll();

// Horas con más demanda
$top_horas = $pdo->query("
    SELECT TIME_FORMAT(hora_inicio,'%H:00') AS hora, COUNT(*) AS total
    FROM reservas WHERE activo=1 AND estado!='Cancelada'
    GROUP BY hora ORDER BY total DESC LIMIT 5
")->fetchAll();

// Lista de profesores para el filtro
$lista_profesores = $pdo->query("
    SELECT p.id_profesor, u.nombre, u.apellidos FROM profesores p JOIN usuarios u ON p.id_usuario=u.id_usuario ORDER BY u.nombre
")->fetchAll();

$estado_badge = ['Pendiente'=>'warning','Confirmada'=>'success','Cancelada'=>'secondary','Completada'=>'primary'];

include '../../includes/header.php';
?>
<div class="container-fluid py-4 px-4">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-bold mb-0">Panel de <span class="text-golf">Administración</span></h2>
            <small class="text-muted">GolfClass · <?php echo date('d/m/Y'); ?></small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="usuarios.php" class="btn btn-outline-secondary btn-sm">👥 Usuarios</a>
            <a href="recursos.php" class="btn btn-outline-secondary btn-sm">⛳ Recursos</a>
            <a href="?<?php echo http_build_query(array_merge($_GET,['csv'=>1])); ?>" class="btn btn-outline-success btn-sm">⬇ CSV</a>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Estado actualizado.</div><?php endif; ?>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <?php foreach ([['Pendiente','warning','⏳'],['Confirmada','success','✅'],['Completada','primary','🏆'],['Cancelada','secondary','❌']] as [$est,$col,$ico]): ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3"><?php echo $ico; ?></div>
                <div class="fs-2 fw-bold text-<?php echo $col; ?>"><?php echo $totales[$est]; ?></div>
                <div class="text-muted small"><?php echo $est; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Estadísticas avanzadas -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">🏅 Instructores más reservados</h6>
                    <?php foreach ($top_inst as $i => $t): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo ($i+1).'. '.htmlspecialchars($t['nombre'].' '.$t['apellidos']); ?></span>
                            <span class="badge" style="background:var(--golf-red);"><?php echo $t['total']; ?> clases</span>
                        </div>
                        <div class="progress mb-2" style="height:6px;">
                            <div class="progress-bar" style="width:<?php echo min(100, $t['total']*10); ?>%;background:var(--golf-red);"></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($top_inst)): ?><p class="text-muted small">Sin datos aún.</p><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">🕐 Horas con más demanda</h6>
                    <?php foreach ($top_horas as $h): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo htmlspecialchars($h['hora']); ?></span>
                            <span class="badge bg-light text-dark border"><?php echo $h['total']; ?> reservas</span>
                        </div>
                        <div class="progress mb-2" style="height:6px;">
                            <div class="progress-bar bg-warning" style="width:<?php echo min(100, $h['total']*10); ?>%;"></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($top_horas)): ?><p class="text-muted small">Sin datos aún.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros avanzados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($estados_validos as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo $filtro_estado===$e?'selected':''; ?>><?php echo $e; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Fecha desde</label>
                    <input type="date" name="fecha_ini" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($filtro_fecha_ini); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Fecha hasta</label>
                    <input type="date" name="fecha_fin" class="form-control form-select-sm"
                           value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Buscar alumno</label>
                    <input type="text" name="q_usuario" class="form-control form-control-sm"
                           placeholder="Nombre o email…"
                           value="<?php echo htmlspecialchars($filtro_usuario); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Instructor</label>
                    <select name="id_profesor_f" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($lista_profesores as $lp): ?>
                            <option value="<?php echo $lp['id_profesor']; ?>" <?php echo $filtro_profesor==$lp['id_profesor']?'selected':''; ?>>
                                <?php echo htmlspecialchars($lp['nombre'].' '.$lp['apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-golf btn-sm">Filtrar</button>
                </div>
            </form>
            <?php if ($filtro_estado||$filtro_fecha_ini||$filtro_fecha_fin||$filtro_usuario||$filtro_profesor): ?>
                <div class="mt-2"><a href="panel.php" class="text-muted small">✕ Limpiar filtros</a></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de reservas -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <span class="fw-bold"><?php echo count($reservas); ?> reservas</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($reservas)): ?>
                <p class="text-muted text-center py-5">No hay reservas con el filtro seleccionado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Alumno</th><th>Instructor</th><th>Ubicación</th>
                                <th>Fecha</th><th>Hora</th><th>Estado</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservas as $r): ?>
                            <tr>
                                <td class="text-muted">#<?php echo $r['id_reserva']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['alumno_nombre'].' '.$r['alumno_apellidos']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($r['alumno_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($r['profe_nombre'].' '.$r['profe_apellidos']); ?></td>
                                <td><small><?php echo htmlspecialchars(mb_substr($r['ubicacion_display'],0,35)); ?></small></td>
                                <td><?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?></td>
                                <td><?php echo substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5); ?></td>
                                <td><span class="badge bg-<?php echo $estado_badge[$r['estado']]??'secondary'; ?>"><?php echo htmlspecialchars($r['estado']); ?></span></td>
                                <td>
                                    <form method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="id_reserva" value="<?php echo $r['id_reserva']; ?>">
                                        <?php foreach (['estado','fecha_ini','fecha_fin','q_usuario','id_profesor_f'] as $k): ?>
                                            <?php if (!empty($_GET[$k])): ?><input type="hidden" name="<?php echo $k; ?>" value="<?php echo htmlspecialchars($_GET[$k]); ?>"><?php endif; ?>
                                        <?php endforeach; ?>
                                        <select name="nuevo_estado" class="form-select form-select-sm" style="width:auto;font-size:.78rem;">
                                            <?php foreach ($estados_validos as $est): ?>
                                                <option value="<?php echo $est; ?>" <?php echo $r['estado']===$est?'selected':''; ?>><?php echo $est; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-golf btn-sm">✓</button>
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
</body></html>
