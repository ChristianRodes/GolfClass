<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/strikes.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit();
}

$yo = (int) $_SESSION['user_id'];

// ── Cancelar reserva ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_id'])) {
    $id_reserva = filter_input(INPUT_POST, 'cancelar_id', FILTER_VALIDATE_INT);
    if ($id_reserva) {
        $stmt = $pdo->prepare("
            SELECT fecha_clase, hora_inicio FROM reservas
            WHERE id_reserva = ? AND id_alumno = ?
              AND estado IN ('Pendiente','Confirmada')
        ");
        $stmt->execute([$id_reserva, $yo]);
        $r = $stmt->fetch();

        if ($r) {
            $clase_ts = strtotime($r['fecha_clase'] . ' ' . $r['hora_inicio']);
            if ($clase_ts - time() > 86400) {
                $pdo->prepare("UPDATE reservas SET estado='Cancelada' WHERE id_reserva=?")
                    ->execute([$id_reserva]);

                // Registrar strike por cancelación
                $resultado = registrar_strike($pdo, $yo, $id_reserva, 'cancelacion');

                if ($resultado['tipo'] === 'permanente') {
                    session_destroy();
                    header("Location: ../auth/login.php?error=bloqueado_permanente"); exit();
                }
                if ($resultado['tipo'] === 'temporal') {
                    header("Location: mis_reservas.php?cancelled=1&strike=1&ban=1"); exit();
                }
                header("Location: mis_reservas.php?cancelled=1&strike=1"); exit();
            } else {
                header("Location: mis_reservas.php?error=plazo"); exit();
            }
        }
    }
    header("Location: mis_reservas.php"); exit();
}

// ── Filtros ───────────────────────────────────────────────────────────────
$filtro_estado = $_GET['estado'] ?? '';
$filtro_vista  = $_GET['vista']  ?? 'proximas';
$filtro_fecha  = trim($_GET['fecha'] ?? '');

$where  = "WHERE r.id_alumno = ? AND r.activo = 1";
$params = [$yo];

if ($filtro_vista === 'proximas') {
    $where .= " AND r.fecha_clase >= CURDATE()";
} elseif ($filtro_vista === 'pasadas') {
    $where .= " AND r.fecha_clase < CURDATE()";
}

$estados_validos = ['Pendiente','Confirmada','Completada','Cancelada','No presentado'];
if ($filtro_estado && in_array($filtro_estado, $estados_validos)) {
    $where .= " AND r.estado = ?";
    $params[] = $filtro_estado;
}
if ($filtro_fecha) {
    $where .= " AND r.fecha_clase = ?";
    $params[] = $filtro_fecha;
}

$stmt = $pdo->prepare("
    SELECT r.id_reserva, r.fecha_clase, r.hora_inicio, r.hora_fin, r.estado, r.fecha_creacion,
           u.nombre AS profesor_nombre, u.apellidos AS profesor_apellidos,
           p.id_profesor, p.precio_hora,
           COALESCE(p.ubicacion, CONCAT(c.nombre_campo, ', ', c.ciudad)) AS ubicacion_display
    FROM reservas r
    JOIN profesores p ON r.id_profesor = p.id_profesor
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    {$where}
    ORDER BY r.fecha_clase DESC, r.hora_inicio DESC
");
$stmt->execute($params);
$reservas = $stmt->fetchAll();

$estado_badge = [
    'Pendiente'     => 'warning',
    'Confirmada'    => 'success',
    'Cancelada'     => 'secondary',
    'Completada'    => 'primary',
    'No presentado' => 'danger',
];

// ── Info de strikes del alumno ────────────────────────────────────────────
$strike_info = get_strike_info($pdo, $yo);

include '../../includes/header.php';
?>

<div class="container py-5">

    <!-- Encabezado -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h2 class="fw-bold mb-0">Mis <span class="text-golf">Reservas</span></h2>
        <a href="/GolfClass/pages/profesores/catalogo.php" class="btn btn-golf">+ Nueva reserva</a>
    </div>

    <!-- Alertas de cancelación / strikes -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">¡Reserva realizada correctamente!</div>
    <?php endif; ?>
    <?php if (isset($_GET['cancelled'])): ?>
        <div class="alert alert-<?php echo isset($_GET['strike']) ? 'warning' : 'info'; ?>">
            Reserva cancelada.
            <?php if (isset($_GET['strike'])): ?>
                <strong>⚡ Has recibido 1 strike</strong> por esta cancelación.
                <?php if (isset($_GET['ban'])): ?>
                    <br>🚫 <strong>Ban de 48h activado</strong> — no puedes hacer nuevas reservas hasta
                    <?php echo date('d/m/Y H:i', strtotime($strike_info['ban_hasta'])); ?>.
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['modified'])): ?>
        <div class="alert alert-success">Reserva modificada correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <?php if ($_GET['error'] === 'plazo'): ?>
            <div class="alert alert-danger">No se puede cancelar con menos de 24h de antelación.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ── Panel de strikes ────────────────────────────────────────── -->
    <?php
    $strike_total  = $strike_info['total'];
    $strike_en_5   = $strike_info['en_ultimas_5'];
    $ban_activo    = $strike_info['ban_activo'];
    $ban_hasta     = $strike_info['ban_hasta'];

    // Color del panel según gravedad
    $panel_color = 'success';
    if ($ban_activo || $strike_total >= 4)        $panel_color = 'danger';
    elseif ($strike_en_5 >= 1 || $strike_total >= 2) $panel_color = 'warning';
    ?>
    <div class="card border-0 shadow-sm mb-4 border-start border-4 border-<?php echo $panel_color; ?>">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                <!-- Estado global -->
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-3">
                        ⚡ Estado de tu cuenta
                        <?php if ($ban_activo): ?>
                            <span class="badge bg-danger ms-2">🚫 BAN ACTIVO</span>
                        <?php elseif ($strike_total === 0): ?>
                            <span class="badge bg-success ms-2">✅ Sin restricciones</span>
                        <?php endif; ?>
                    </h6>

                    <div class="row g-3">
                        <!-- Strikes totales -->
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold">Strikes totales</small>
                                <small class="<?php echo $strike_total >= 4 ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    <?php echo $strike_total; ?> / 5
                                </small>
                            </div>
                            <div class="progress" style="height:8px;">
                                <?php
                                $pct   = min(100, $strike_total * 20);
                                $color = $strike_total >= 4 ? 'danger' : ($strike_total >= 2 ? 'warning' : 'success');
                                ?>
                                <div class="progress-bar bg-<?php echo $color; ?>"
                                     style="width:<?php echo $pct; ?>%;transition:width .5s;"></div>
                            </div>
                            <small class="text-muted">5 strikes = bloqueo permanente</small>
                        </div>

                        <!-- En últimas 5 reservas -->
                        <div class="col-sm-6">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold">En tus últimas 5 reservas</small>
                                <small class="<?php echo $strike_en_5 >= 2 ? 'text-danger fw-bold' : 'text-muted'; ?>">
                                    <?php echo $strike_en_5; ?> / 2
                                </small>
                            </div>
                            <div class="progress" style="height:8px;">
                                <?php
                                $pct5   = min(100, $strike_en_5 * 50);
                                $color5 = $strike_en_5 >= 2 ? 'danger' : ($strike_en_5 === 1 ? 'warning' : 'success');
                                ?>
                                <div class="progress-bar bg-<?php echo $color5; ?>"
                                     style="width:<?php echo $pct5; ?>%;transition:width .5s;"></div>
                            </div>
                            <small class="text-muted">2 en las últimas 5 = ban de 48h</small>
                        </div>
                    </div>

                    <!-- Ban activo -->
                    <?php if ($ban_activo): ?>
                        <div class="alert alert-danger mt-3 mb-0 py-2">
                            🚫 <strong>Reservas bloqueadas hasta <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($ban_hasta)); ?></strong>
                            — te quedan <?php echo tiempo_restante_ban($ban_hasta); ?>.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Historial compacto -->
                <?php if (!empty($strike_info['historial'])): ?>
                    <div style="min-width:220px;">
                        <small class="fw-bold text-muted d-block mb-2">Historial de strikes</small>
                        <?php foreach ($strike_info['historial'] as $s): ?>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge <?php echo $s['motivo']==='cancelacion' ? 'bg-warning text-dark' : 'bg-danger'; ?>" style="font-size:.65rem;">
                                    <?php echo $s['motivo'] === 'cancelacion' ? 'Cancelación' : 'No presentado'; ?>
                                </span>
                                <small class="text-muted">
                                    <?php echo date('d/m/y', strtotime($s['fecha_clase'])); ?>
                                    · <?php echo htmlspecialchars($s['prof_nombre']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reglas colapsables -->
            <div class="mt-3">
                <button class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                        data-bs-toggle="collapse" data-bs-target="#reglas_strikes">
                    ℹ️ Ver normas del sistema de strikes
                </button>
                <div class="collapse mt-2" id="reglas_strikes">
                    <div class="card card-body bg-light border-0 py-3" style="font-size:.88rem;">
                        <strong class="d-block mb-2">¿Cómo funcionan los strikes?</strong>
                        <ul class="mb-0 ps-3">
                            <li>Recibes <strong>1 strike</strong> si cancelas una reserva (aunque sea con más de 24h).</li>
                            <li>Recibes <strong>1 strike</strong> si el instructor te marca como "No presentado".</li>
                            <li>Si acumulas <strong>2 strikes en tus últimas 5 reservas</strong>, tus reservas quedan bloqueadas durante <strong>48 horas</strong>.</li>
                            <li>Si llegas a <strong>5 strikes en total</strong>, tu cuenta queda <strong>bloqueada permanentemente</strong>.</li>
                            <li>Para cancelar sin strike, contacta con el instructor por el chat con suficiente antelación.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Filtros ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-6 col-md-auto">
                    <div class="btn-group">
                        <?php foreach (['proximas'=>'Próximas','pasadas'=>'Pasadas','todas'=>'Todas'] as $v=>$l): ?>
                            <a href="?vista=<?php echo $v; ?>&estado=<?php echo urlencode($filtro_estado); ?>"
                               class="btn btn-sm <?php echo $filtro_vista===$v?'btn-golf':'btn-outline-secondary'; ?>">
                                <?php echo $l; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-6 col-md-auto">
                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados_validos as $e): ?>
                            <option value="<?php echo $e; ?>" <?php echo $filtro_estado===$e?'selected':''; ?>><?php echo $e; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="vista" value="<?php echo $filtro_vista; ?>">
                </div>
                <div class="col-6 col-md-auto">
                    <input type="date" name="fecha" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($filtro_fecha); ?>"
                           onchange="this.form.submit()">
                </div>
                <?php if ($filtro_estado || $filtro_fecha): ?>
                    <div class="col-auto"><a href="?vista=<?php echo $filtro_vista; ?>" class="text-muted small">✕ Limpiar</a></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ── Listado ──────────────────────────────────────────────────── -->
    <?php if (empty($reservas)): ?>
        <div class="text-center py-5">
            <p class="text-muted fs-5">No hay reservas con este filtro.</p>
            <a href="/GolfClass/pages/profesores/catalogo.php" class="btn btn-golf mt-2">Explorar instructores</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($reservas as $r):
                $clase_ts  = strtotime($r['fecha_clase'] . ' ' . $r['hora_inicio']);
                $puede_mod = ($r['estado'] === 'Pendiente' && $clase_ts - time() > 86400);
                $puede_can = (in_array($r['estado'], ['Pendiente','Confirmada']) && $clase_ts - time() > 86400);
                $es_pasada = ($clase_ts < time());
                $qr_data   = urlencode("GolfClass #{$r['id_reserva']} | {$r['profesor_nombre']} {$r['profesor_apellidos']} | {$r['fecha_clase']} " . substr($r['hora_inicio'],0,5));
                $qr_url    = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$qr_data}";

                // Strike en esta reserva?
                $tiene_strike = false;
                foreach ($strike_info['historial'] as $sh) {
                    if ($sh['fecha_clase'] === $r['fecha_clase']) { $tiene_strike = true; break; }
                }
            ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm <?php echo $es_pasada?'opacity-75':''; ?>"
                     style="<?php echo $r['estado']==='No presentado'?'border-left:4px solid var(--golf-red)!important':''; ?>">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($r['profesor_nombre'].' '.$r['profesor_apellidos']); ?></h6>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($r['ubicacion_display']); ?></small>
                            </div>
                            <div class="col-md-3">
                                <strong class="d-block"><?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?></strong>
                                <small class="text-muted"><?php echo substr($r['hora_inicio'],0,5).' – '.substr($r['hora_fin'],0,5); ?></small>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-<?php echo $estado_badge[$r['estado']]??'secondary'; ?> fs-6">
                                    <?php echo htmlspecialchars($r['estado']); ?>
                                </span>
                                <?php if ($r['estado'] === 'No presentado'): ?>
                                    <span class="badge bg-danger ms-1" style="font-size:.65rem;">⚡ Strike</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 d-flex flex-wrap gap-1 justify-content-md-end">
                                <?php if ($puede_mod): ?>
                                    <a href="modificar_reserva.php?id=<?php echo $r['id_reserva']; ?>"
                                       class="btn btn-outline-secondary btn-sm">Modificar</a>
                                <?php endif; ?>
                                <?php if ($puede_can): ?>
                                    <form method="POST"
                                          onsubmit="return confirm('¿Cancelar esta reserva?\n\nAtención: recibirás 1 strike por esta cancelación.');">
                                        <input type="hidden" name="cancelar_id" value="<?php echo $r['id_reserva']; ?>">
                                        <button class="btn btn-outline-danger btn-sm">Cancelar</button>
                                    </form>
                                <?php endif; ?>
                                <?php
                                $stmt_uid = $pdo->prepare("SELECT u.id_usuario FROM profesores p JOIN usuarios u ON p.id_usuario=u.id_usuario WHERE p.id_profesor=?");
                                $stmt_uid->execute([$r['id_profesor']]);
                                $uid_prof = $stmt_uid->fetchColumn();
                                ?>
                                <a href="/GolfClass/pages/chat/conversacion.php?con=<?php echo $uid_prof; ?>"
                                   class="btn btn-outline-secondary btn-sm" title="Chat">💬</a>
                                <button class="btn btn-outline-secondary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#modalQR_<?php echo $r['id_reserva']; ?>"
                                        title="QR">⬛</button>
                                <a href="descargar_ical.php?id=<?php echo $r['id_reserva']; ?>"
                                   class="btn btn-outline-secondary btn-sm" title="iCal">📅</a>
                            </div>
                        </div>
                        <?php if (!$puede_can && !$es_pasada && in_array($r['estado'], ['Pendiente','Confirmada'])): ?>
                            <p class="text-muted small mt-2 mb-0">⚠️ Menos de 24h para la clase — cancelación no disponible.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Modal QR -->
            <div class="modal fade" id="modalQR_<?php echo $r['id_reserva']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow text-center">
                        <div class="modal-header border-0 pb-0">
                            <h6 class="modal-title w-100 fw-bold">Justificante #<?php echo $r['id_reserva']; ?></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body pt-2">
                            <img src="<?php echo $qr_url; ?>" alt="QR" class="img-fluid mb-2">
                            <p class="small text-muted mb-0">
                                <?php echo htmlspecialchars($r['profesor_nombre'].' '.$r['profesor_apellidos']); ?><br>
                                <?php echo date('d/m/Y', strtotime($r['fecha_clase'])); ?>
                                · <?php echo substr($r['hora_inicio'],0,5).'–'.substr($r['hora_fin'],0,5); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
