<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/auth/login.php"); exit();
}

$yo = (int) $_SESSION['user_id'];

// Obtener todos los interlocutores distintos con última actividad
$stmt = $pdo->prepare("
    SELECT
        u.id_usuario,
        u.nombre,
        u.apellidos,
        r.nombre_rol,
        (
            SELECT m2.mensaje
            FROM mensajes m2
            WHERE (m2.id_emisor = ? AND m2.id_receptor = u.id_usuario)
               OR (m2.id_emisor = u.id_usuario AND m2.id_receptor = ?)
            ORDER BY m2.fecha_envio DESC LIMIT 1
        ) AS ultimo_msg,
        (
            SELECT m3.fecha_envio
            FROM mensajes m3
            WHERE (m3.id_emisor = ? AND m3.id_receptor = u.id_usuario)
               OR (m3.id_emisor = u.id_usuario AND m3.id_receptor = ?)
            ORDER BY m3.fecha_envio DESC LIMIT 1
        ) AS ultima_fecha,
        (
            SELECT COUNT(*)
            FROM mensajes m4
            WHERE m4.id_emisor = u.id_usuario AND m4.id_receptor = ? AND m4.leido = 0
        ) AS no_leidos
    FROM usuarios u
    JOIN roles r ON r.id_rol = u.id_rol
    WHERE u.id_usuario IN (
        SELECT DISTINCT IF(id_emisor = ?, id_receptor, id_emisor)
        FROM mensajes
        WHERE id_emisor = ? OR id_receptor = ?
    )
    ORDER BY ultima_fecha DESC
");
$stmt->execute([$yo,$yo, $yo,$yo, $yo, $yo,$yo,$yo]);
$conversaciones = $stmt->fetchAll();

// Total no leídos
$total_no_leidos = array_sum(array_column($conversaciones, 'no_leidos'));

include '../../includes/header.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">
                    Mensajes
                    <?php if ($total_no_leidos): ?>
                        <span class="badge ms-2" style="background:var(--golf-red);font-size:.75rem;">
                            <?php echo $total_no_leidos; ?> nuevo<?php echo $total_no_leidos>1?'s':''; ?>
                        </span>
                    <?php endif; ?>
                </h2>
                <button class="btn btn-golf btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoChat">
                    + Nuevo mensaje
                </button>
            </div>

            <?php if (empty($conversaciones)): ?>
                <div class="card border-0 shadow-sm text-center py-5">
                    <p class="text-muted fs-5 mb-2">No tienes conversaciones aún.</p>
                    <small class="text-muted">Inicia una clase y podrás chatear con tu instructor.</small>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm overflow-hidden">
                    <?php foreach ($conversaciones as $i => $c): ?>
                        <a href="conversacion.php?con=<?php echo $c['id_usuario']; ?>"
                           class="conv-item d-flex align-items-center gap-3 p-3 text-decoration-none
                                  <?php echo $i < count($conversaciones)-1 ? 'border-bottom' : ''; ?>
                                  <?php echo $c['no_leidos'] ? 'conv-item-unread' : ''; ?>">

                            <!-- Avatar -->
                            <div class="chat-avatar flex-shrink-0">
                                <?php echo strtoupper(mb_substr($c['nombre'],0,1).mb_substr($c['apellidos']??'',0,1)); ?>
                            </div>

                            <!-- Info -->
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-baseline">
                                    <strong class="text-dark">
                                        <?php echo htmlspecialchars($c['nombre'].' '.($c['apellidos']??'')); ?>
                                    </strong>
                                    <small class="text-muted flex-shrink-0 ms-2">
                                        <?php echo $c['ultima_fecha'] ? date('d/m H:i', strtotime($c['ultima_fecha'])) : ''; ?>
                                    </small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted text-truncate">
                                        <?php echo htmlspecialchars(mb_substr($c['ultimo_msg']??'',0,55)); ?>
                                    </small>
                                    <?php if ($c['no_leidos']): ?>
                                        <span class="badge rounded-pill ms-2 flex-shrink-0"
                                              style="background:var(--golf-red);font-size:.65rem;">
                                            <?php echo $c['no_leidos']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <small class="badge bg-light text-muted border" style="font-size:.65rem;">
                                    <?php echo htmlspecialchars($c['nombre_rol']); ?>
                                </small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: nuevo chat -->
<div class="modal fade" id="modalNuevoChat" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Nuevo mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                // Usuarios con los que puede chatear (instructores para alumnos, alumnos para instructores, todos para admin)
                if ($_SESSION['user_role'] == 3) {
                    // Alumno: puede chatear con sus instructores
                    $contactos = $pdo->prepare("
                        SELECT DISTINCT u.id_usuario, u.nombre, u.apellidos, ro.nombre_rol
                        FROM reservas r
                        JOIN profesores p ON r.id_profesor = p.id_profesor
                        JOIN usuarios u ON p.id_usuario = u.id_usuario
                        JOIN roles ro ON ro.id_rol = u.id_rol
                        WHERE r.id_alumno = ? AND u.activo = 1
                        ORDER BY u.nombre
                    ");
                    $contactos->execute([$yo]);
                } elseif ($_SESSION['user_role'] == 2) {
                    // Profesor: puede chatear con sus alumnos
                    $contactos = $pdo->prepare("
                        SELECT DISTINCT u.id_usuario, u.nombre, u.apellidos, ro.nombre_rol
                        FROM reservas r
                        JOIN profesores p ON r.id_profesor = p.id_profesor
                        JOIN usuarios u ON r.id_alumno = u.id_usuario
                        JOIN roles ro ON ro.id_rol = u.id_rol
                        WHERE p.id_usuario = ? AND u.activo = 1
                        ORDER BY u.nombre
                    ");
                    $contactos->execute([$yo]);
                } else {
                    // Admin: puede chatear con cualquiera
                    $contactos = $pdo->prepare("
                        SELECT u.id_usuario, u.nombre, u.apellidos, r.nombre_rol
                        FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
                        WHERE u.id_usuario != ? AND u.activo = 1
                        ORDER BY u.nombre
                    ");
                    $contactos->execute([$yo]);
                }
                $lista = $contactos->fetchAll();
                ?>
                <?php if (empty($lista)): ?>
                    <p class="text-muted small">Realiza una reserva primero para poder chatear con un instructor.</p>
                <?php else: ?>
                    <p class="text-muted small mb-3">Selecciona con quién quieres hablar:</p>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($lista as $c): ?>
                            <a href="conversacion.php?con=<?php echo $c['id_usuario']; ?>"
                               class="d-flex align-items-center gap-3 p-2 rounded-3 border text-decoration-none text-dark conv-item">
                                <div class="chat-avatar" style="width:40px;height:40px;font-size:.9rem;">
                                    <?php echo strtoupper(mb_substr($c['nombre'],0,1).mb_substr($c['apellidos']??'',0,1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($c['nombre'].' '.($c['apellidos']??'')); ?></strong>
                                    <small class="d-block text-muted"><?php echo htmlspecialchars($c['nombre_rol']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.conv-item          { transition: background .15s; }
.conv-item:hover    { background: #f8f9fa; }
.conv-item-unread   { background: #fff8f8; }
.chat-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: var(--golf-red); color: white;
    font-weight: 700; font-size: 1.1rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
