<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: /index.php");
    exit();
}

$id_usuario = (int) $_SESSION['user_id'];

// Obtener id_profesor del usuario
$stmt = $pdo->prepare("SELECT id_profesor FROM profesores WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$row = $stmt->fetch();

if (!$row) {
    // Aún no tiene perfil de profesor
    header("Location: edit_profile.php?error=noperfil");
    exit();
}
$id_profesor = (int) $row['id_profesor'];

// ── Semana actual (navegable por GET ?semana=offset) ──────────────────────
$offset = (int) ($_GET['semana'] ?? 0);

$lunes = new DateTime('monday this week');
$lunes->modify("{$offset} weeks");
$domingo = clone $lunes;
$domingo->modify('+6 days');

$fecha_ini = $lunes->format('Y-m-d');
$fecha_fin = $domingo->format('Y-m-d');

// ── Reservas de la semana ─────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado,
           u.nombre AS alumno_nombre, u.apellidos AS alumno_apellidos,
           u.email  AS alumno_email,  u.telefono  AS alumno_tel
    FROM reservas r
    JOIN usuarios u ON r.id_alumno = u.id_usuario
    WHERE r.id_profesor = ? AND r.fecha_clase BETWEEN ? AND ?
      AND r.activo = 1
    ORDER BY r.fecha_clase, r.hora_inicio
");
$stmt->execute([$id_profesor, $fecha_ini, $fecha_fin]);
$reservas_semana = $stmt->fetchAll();

// ── Slots bloqueados de la semana ─────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT * FROM slots_bloqueados
    WHERE id_profesor = ? AND fecha BETWEEN ? AND ?
    ORDER BY fecha, hora_inicio
");
$stmt->execute([$id_profesor, $fecha_ini, $fecha_fin]);
$bloqueados_semana = $stmt->fetchAll();

// ── Construir índices para renderizar el grid ─────────────────────────────
// Reservas: índice exacto por [fecha][hora_inicio]
$reservas_idx = [];
foreach ($reservas_semana as $r) {
    $h = substr($r['hora_inicio'], 0, 5);
    $reservas_idx[$r['fecha_clase']][$h] = $r;
}
// Bloqueos: agrupados por fecha (array de bloques) para detectar solapamientos
$bloqueados_por_fecha = [];
foreach ($bloqueados_semana as $b) {
    $bloqueados_por_fecha[$b['fecha']][] = $b;
}

// Devuelve el bloqueo que cubre la celda [fecha, hora] o null
function getBloqueoParaCelda(array $bloqueados_por_fecha, string $fecha, string $hora): ?array {
    if (empty($bloqueados_por_fecha[$fecha])) return null;
    $hora_fin_cel = date('H:i', strtotime($hora . ' +1 hour'));
    foreach ($bloqueados_por_fecha[$fecha] as $b) {
        $bi = substr($b['hora_inicio'], 0, 5);
        $bf = substr($b['hora_fin'],   0, 5);
        if ($bi < $hora_fin_cel && $bf > $hora) return $b;
    }
    return null;
}

// ── Próximas reservas (lista lateral) ────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado,
           u.nombre AS alumno_nombre, u.apellidos AS alumno_apellidos,
           u.email AS alumno_email, u.telefono AS alumno_tel
    FROM reservas r
    JOIN usuarios u ON r.id_alumno = u.id_usuario
    WHERE r.id_profesor = ? AND r.fecha_clase >= CURDATE()
      AND r.activo = 1 AND r.estado != 'Cancelada'
    ORDER BY r.fecha_clase, r.hora_inicio
    LIMIT 20
");
$stmt->execute([$id_profesor]);
$proximas = $stmt->fetchAll();

// ── Stats ─────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT estado, COUNT(*) AS total
    FROM reservas WHERE id_profesor = ? AND activo = 1
    GROUP BY estado
");
$stmt->execute([$id_profesor]);
$stats_raw = $stmt->fetchAll();
$stats = ['Pendiente' => 0, 'Confirmada' => 0, 'Completada' => 0, 'Cancelada' => 0];
foreach ($stats_raw as $s) { $stats[$s['estado']] = $s['total']; }

// ── Columnas del calendario ───────────────────────────────────────────────
$dias_semana = [];
$dia = clone $lunes;
for ($i = 0; $i < 7; $i++) {
    $dias_semana[] = clone $dia;
    $dia->modify('+1 day');
}

$horas = [];
for ($h = 8; $h <= 17; $h++) {
    $horas[] = sprintf('%02d:00', $h);
}

$nombres_dia = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$estado_class = [
    'Pendiente'  => 'cel-pendiente',
    'Confirmada' => 'cel-confirmada',
    'Completada' => 'cel-completada',
    'Cancelada'  => 'cel-vacio',
];

include '../../includes/header.php';
?>

<div class="container-fluid py-4 px-4">

    <!-- ── Encabezado ─────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="fw-bold mb-0">Mi <span class="text-golf">Dashboard</span></h2>
            <small class="text-muted">Panel de gestión de clases</small>
        </div>
        <div class="d-flex gap-2">
            <a href="edit_profile.php" class="btn btn-outline-secondary btn-sm">Editar perfil</a>
            <button class="btn btn-golf btn-sm" data-bs-toggle="modal" data-bs-target="#modalBloquear">
                + Bloquear horario
            </button>
        </div>
    </div>

    <!-- Feedback de acciones -->
    <?php if (isset($_GET['ok'])): ?>
        <?php if ($_GET['ok'] === 'nopresentado'): ?>
            <div class="alert alert-warning">
                ⚡ Marcado como "No presentado" — se ha emitido 1 strike al alumno.
                <?php if (($_GET['strike'] ?? '') === 'permanente'): ?>
                    <strong>El alumno ha acumulado 5 strikes: cuenta bloqueada permanentemente.</strong>
                <?php elseif (($_GET['strike'] ?? '') === 'temporal'): ?>
                    <strong>El alumno tiene ban de 48h por 2 strikes en sus últimas 5 reservas.</strong>
                <?php endif; ?>
            </div>
        <?php elseif ($_GET['ok'] === 'bloqueado'): ?>
            <div class="alert alert-success">Horario bloqueado.</div>
        <?php elseif ($_GET['ok'] === 'desbloqueado'): ?>
            <div class="alert alert-info">Horario desbloqueado.</div>
        <?php else: ?>
            <div class="alert alert-success">Cambio guardado.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ── Stats ──────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php
        $stat_config = [
            ['Pendiente',  'warning',   '⏳', 'Pendientes'],
            ['Confirmada', 'success',   '✅', 'Confirmadas'],
            ['Completada', 'primary',   '🏆', 'Completadas'],
            ['Cancelada',  'secondary', '❌', 'Canceladas'],
        ];
        foreach ($stat_config as [$est, $color, $icon, $label]):
        ?>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="fs-3"><?php echo $icon; ?></div>
                <div class="fs-2 fw-bold text-<?php echo $color; ?>"><?php echo $stats[$est]; ?></div>
                <div class="text-muted small"><?php echo $label; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">

        <!-- ── Calendario semanal ─────────────────────────────── -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">

                    <!-- Navegación de semana -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <a href="?semana=<?php echo $offset - 1; ?>" class="btn btn-outline-secondary btn-sm">‹ Anterior</a>
                        <div class="text-center">
                            <strong>
                                <?php echo $lunes->format('d M'); ?> – <?php echo $domingo->format('d M Y'); ?>
                            </strong>
                            <?php if ($offset !== 0): ?>
                                <a href="dashboard.php" class="text-muted small ms-2">Hoy</a>
                            <?php endif; ?>
                        </div>
                        <a href="?semana=<?php echo $offset + 1; ?>" class="btn btn-outline-secondary btn-sm">Siguiente ›</a>
                    </div>

                    <!-- Grid -->
                    <div class="table-responsive">
                        <table class="table table-bordered cal-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:60px;" class="text-center text-muted small"></th>
                                    <?php foreach ($dias_semana as $i => $d): ?>
                                        <?php
                                        $es_hoy    = ($d->format('Y-m-d') === date('Y-m-d'));
                                        $es_pasado = ($d->format('Y-m-d') < date('Y-m-d'));
                                        $fecha_th  = $d->format('Y-m-d');
                                        ?>
                                        <th class="text-center small <?php echo $es_hoy ? 'bg-golf-soft' : ($es_pasado ? 'text-muted' : ''); ?>"
                                            style="min-width:100px;">
                                            <div class="fw-bold"><?php echo $nombres_dia[$i]; ?></div>
                                            <div class="<?php echo $es_hoy ? 'text-golf fw-bold' : ''; ?>">
                                                <?php echo $d->format('d/m'); ?>
                                            </div>
                                            <?php if (!$es_pasado): ?>
                                                <button type="button"
                                                        class="btn btn-link p-0 mt-1 text-danger"
                                                        style="font-size:.65rem;line-height:1;"
                                                        onclick="bloquearDiaCompleto('<?php echo $fecha_th; ?>')"
                                                        title="Bloquear día completo">
                                                    🔒 día
                                                </button>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horas as $hora): ?>
                                    <tr>
                                        <td class="text-center text-muted small align-middle" style="font-size:.75rem;">
                                            <?php echo $hora; ?>
                                        </td>
                                        <?php foreach ($dias_semana as $d):
                                            $fecha_d   = $d->format('Y-m-d');
                                            $es_pasado = ($fecha_d < date('Y-m-d'));
                                            $reserva   = $reservas_idx[$fecha_d][$hora] ?? null;
                                            $bloqueo   = getBloqueoParaCelda($bloqueados_por_fecha, $fecha_d, $hora);
                                            // Mostrar botón desbloquear solo en la primera celda del bloqueo
                                            $es_primera_celda = $bloqueo && substr($bloqueo['hora_inicio'],0,5) === $hora;
                                        ?>
                                            <td class="cal-cell p-1
                                                <?php
                                                if ($bloqueo)                          echo 'cel-bloqueado';
                                                elseif ($reserva)                      echo $estado_class[$reserva['estado']] ?? 'cel-vacio';
                                                elseif ($es_pasado)                    echo 'cel-pasado';
                                                else                                   echo 'cel-vacio cel-clickable';
                                                ?>"
                                                <?php if (!$reserva && !$bloqueo && !$es_pasado): ?>
                                                    data-fecha="<?php echo $fecha_d; ?>"
                                                    data-hora="<?php echo $hora; ?>"
                                                    onclick="abrirModalBloquear(this)"
                                                    title="Clic para bloquear"
                                                <?php endif; ?>>

                                                <?php if ($bloqueo): ?>
                                                    <div class="cal-content">
                                                        <?php if ($es_primera_celda): ?>
                                                            <span class="d-block fw-semibold" style="font-size:.7rem;">
                                                                🔒 <?php echo substr($bloqueo['hora_inicio'],0,5).'–'.substr($bloqueo['hora_fin'],0,5); ?>
                                                            </span>
                                                            <?php if (!empty($bloqueo['motivo'])): ?>
                                                                <span class="d-block text-truncate" style="font-size:.65rem;">
                                                                    <?php echo htmlspecialchars($bloqueo['motivo']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <form method="POST" action="desbloquear_process.php" class="mt-1">
                                                                <input type="hidden" name="id_bloqueo" value="<?php echo $bloqueo['id_bloqueo']; ?>">
                                                                <input type="hidden" name="semana" value="<?php echo $offset; ?>">
                                                                <button type="submit" class="btn btn-link p-0 text-danger" style="font-size:.65rem;">
                                                                    Desbloquear
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php elseif ($reserva && $reserva['estado'] !== 'Cancelada'): ?>
                                                    <div class="cal-content">
                                                        <span class="d-block fw-semibold text-truncate" style="font-size:.7rem;">
                                                            <?php echo htmlspecialchars($reserva['alumno_nombre']); ?>
                                                        </span>
                                                        <span class="d-block" style="font-size:.65rem;">
                                                            <?php echo substr($reserva['hora_inicio'],0,5).'–'.substr($reserva['hora_fin'],0,5); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Leyenda -->
                    <div class="d-flex flex-wrap gap-3 mt-3 ps-1" style="font-size:.78rem;">
                        <span><span class="legend-dot cel-pendiente"></span> Pendiente</span>
                        <span><span class="legend-dot cel-confirmada"></span> Confirmada</span>
                        <span><span class="legend-dot cel-completada"></span> Completada</span>
                        <span><span class="legend-dot cel-bloqueado"></span> Bloqueado</span>
                        <span class="text-muted">Haz clic en un hueco libre para bloquearlo</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Próximas reservas ───────────────────────────────── -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Próximas reservas</h6>

                    <?php if (empty($proximas)): ?>
                        <p class="text-muted small text-center py-4">No tienes reservas futuras.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($proximas as $r): ?>
                                <div class="reserva-item p-3 rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong class="d-block">
                                                <?php echo htmlspecialchars($r['alumno_nombre'] . ' ' . $r['alumno_apellidos']); ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?>
                                                · <?php echo substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5); ?>
                                            </small>
                                            <?php if (!empty($r['alumno_email'])): ?>
                                                <small class="d-block text-muted text-truncate">
                                                    <?php echo htmlspecialchars($r['alumno_email']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($r['alumno_tel'])): ?>
                                                <small class="d-block text-muted">
                                                    📞 <?php echo htmlspecialchars($r['alumno_tel']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $badge = ['Pendiente'=>'warning','Confirmada'=>'success','Completada'=>'primary'];
                                        $b = $badge[$r['estado']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $b; ?> ms-2 flex-shrink-0">
                                            <?php echo htmlspecialchars($r['estado']); ?>
                                        </span>
                                    </div>

                                    <!-- Chat con el alumno -->
                                    <a href="/pages/chat/conversacion.php?con=<?php
                                        $stmt_auid = $pdo->prepare("SELECT id_alumno FROM reservas WHERE id_reserva=?");
                                        $stmt_auid->execute([$r['id_reserva']]);
                                        echo (int)$stmt_auid->fetchColumn();
                                    ?>" class="btn btn-outline-secondary btn-sm mt-2 d-inline-flex align-items-center gap-1"
                                       style="font-size:.75rem;">
                                        💬 Chatear
                                    </a>

                                    <!-- Cambiar estado -->
                                    <form method="POST" action="cambiar_estado_reserva.php" class="mt-2 d-flex gap-1"
                                          onsubmit="return this.nuevo_estado.value !== 'No presentado' || confirm('¿Marcar como No presentado?\n\nEsto emitirá 1 strike al alumno.');">
                                        <input type="hidden" name="id_reserva" value="<?php echo $r['id_reserva']; ?>">
                                        <input type="hidden" name="semana" value="<?php echo $offset; ?>">
                                        <select name="nuevo_estado" class="form-select form-select-sm" style="width:auto;font-size:.75rem;">
                                            <?php foreach (['Pendiente','Confirmada','Completada','Cancelada','No presentado'] as $est): ?>
                                                <option value="<?php echo $est; ?>"
                                                        <?php echo $r['estado']===$est?'selected':''; ?>
                                                        <?php echo $est==='No presentado'?'style="color:#dc3545;font-weight:600;"':''; ?>>
                                                    <?php echo $est === 'No presentado' ? '⚡ '.$est : $est; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-golf btn-sm" style="font-size:.75rem;">
                                            Guardar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</div><!-- /container -->

<!-- ══ Modal: Bloquear horario ═════════════════════════════════════════ -->
<div class="modal fade" id="modalBloquear" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="bloquear_process.php">
                <input type="hidden" name="semana" value="<?php echo $offset; ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">🔒 Bloquear horario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Toggle día completo -->
                    <div class="form-check form-switch mb-3 p-3 bg-light rounded-3">
                        <input class="form-check-input" type="checkbox" id="chk_dia_completo"
                               style="width:2.2em;height:1.2em;" onchange="toggleDiaCompleto(this)">
                        <label class="form-check-label ms-2 fw-semibold" for="chk_dia_completo">
                            Bloquear el día completo <small class="text-muted fw-normal">(08:00 – 18:00)</small>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fecha</label>
                        <input type="date" name="fecha" id="modal_fecha" class="form-control"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="row g-2 mb-3" id="bloque_horas">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hora inicio</label>
                            <select name="hora_inicio" id="modal_hora_inicio" class="form-select" required>
                                <?php for ($h = 8; $h <= 17; $h++): ?>
                                    <option value="<?php printf('%02d:00', $h); ?>"><?php printf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Hora fin</label>
                            <select name="hora_fin" id="modal_hora_fin" class="form-select" required>
                                <?php for ($h = 9; $h <= 18; $h++): ?>
                                    <option value="<?php printf('%02d:00', $h); ?>"><?php printf('%02d:00', $h); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Motivo <span class="text-muted fw-normal">(opcional)</span></label>
                        <input type="text" name="motivo" class="form-control" maxlength="255"
                               placeholder="Ej: Viaje, reunión, descanso…">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-golf">Bloquear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Calendario */
    .cal-table { font-size: .8rem; border-color: #e9ecef !important; }
    .cal-table th, .cal-table td { border-color: #e9ecef !important; }
    .cal-cell { height: 64px; vertical-align: top; }
    .cal-content { padding: 2px 4px; height: 100%; }

    .cel-vacio    { background: #fff; }
    .cel-pasado   { background: #f8f9fa; }
    .cel-clickable:hover { background: #fff3f3; cursor: pointer; }

    .cel-pendiente  { background: #fff8e1; border-left: 3px solid #ffc107 !important; }
    .cel-confirmada { background: #e8f5e9; border-left: 3px solid #28a745 !important; }
    .cel-completada { background: #e8eaf6; border-left: 3px solid #6c757d !important; }
    .cel-bloqueado  {
        background: repeating-linear-gradient(
            45deg, #fee2e2, #fee2e2 4px, #fff5f5 4px, #fff5f5 10px
        );
        border-left: 3px solid var(--golf-red) !important;
    }

    .bg-golf-soft { background: #fff0f0 !important; }

    /* Leyenda */
    .legend-dot {
        display: inline-block;
        width: 12px; height: 12px;
        border-radius: 2px;
        margin-right: 4px;
        vertical-align: middle;
    }
    .legend-dot.cel-pendiente  { background: #fff8e1; border: 2px solid #ffc107; }
    .legend-dot.cel-confirmada { background: #e8f5e9; border: 2px solid #28a745; }
    .legend-dot.cel-completada { background: #e8eaf6; border: 2px solid #6c757d; }
    .legend-dot.cel-bloqueado  { background: #fee2e2; border: 2px solid var(--golf-red); }

    /* Reservas laterales */
    .reserva-item { transition: border-color .15s; }
    .reserva-item:hover { border-color: var(--golf-red) !important; }
</style>

<script>
function _setSelect(sel, val) {
    for (let opt of sel.options) { if (opt.value === val) { opt.selected = true; break; } }
}

function abrirModalBloquear(cel) {
    const fecha = cel.dataset.fecha;
    const hora  = cel.dataset.hora;
    // Resetear día completo
    document.getElementById('chk_dia_completo').checked = false;
    document.getElementById('bloque_horas').style.opacity = '1';
    document.getElementById('bloque_horas').style.pointerEvents = 'auto';

    if (fecha) document.getElementById('modal_fecha').value = fecha;
    if (hora) {
        _setSelect(document.getElementById('modal_hora_inicio'), hora);
        const [h] = hora.split(':');
        _setSelect(document.getElementById('modal_hora_fin'),
                   String(parseInt(h) + 1).padStart(2, '0') + ':00');
    }
    new bootstrap.Modal(document.getElementById('modalBloquear')).show();
}

function bloquearDiaCompleto(fecha) {
    document.getElementById('modal_fecha').value = fecha;
    const chk = document.getElementById('chk_dia_completo');
    chk.checked = true;
    toggleDiaCompleto(chk);
    new bootstrap.Modal(document.getElementById('modalBloquear')).show();
}

function toggleDiaCompleto(chk) {
    const bloque = document.getElementById('bloque_horas');
    if (chk.checked) {
        _setSelect(document.getElementById('modal_hora_inicio'), '08:00');
        _setSelect(document.getElementById('modal_hora_fin'),    '18:00');
        bloque.style.opacity      = '0.4';
        bloque.style.pointerEvents = 'none';
    } else {
        bloque.style.opacity      = '1';
        bloque.style.pointerEvents = 'auto';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
