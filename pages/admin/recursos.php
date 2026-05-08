<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: /index.php"); exit();
}

$tab = $_GET['tab'] ?? 'profesores';

// ── Profesores ────────────────────────────────────────────────
$profesores = $pdo->query("
    SELECT p.*, u.nombre, u.apellidos, u.email,
           c.nombre_campo, c.ciudad AS ciudad_campo
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    LEFT JOIN campos_golf c ON p.id_campo = c.id_campo
    ORDER BY p.id_profesor DESC
")->fetchAll();

// ── Campos de golf ────────────────────────────────────────────
$campos = $pdo->query("SELECT * FROM campos_golf ORDER BY id_campo DESC")->fetchAll();

include '../../includes/header.php';
?>
<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Gestión de <span class="text-golf">Recursos</span></h2>
        <a href="panel.php" class="btn btn-outline-secondary btn-sm">← Panel</a>
    </div>

    <?php foreach (['ok_prof'=>'Instructor guardado.','ok_campo'=>'Campo guardado.','ok_del'=>'Recurso eliminado.'] as $k=>$v): ?>
        <?php if (isset($_GET[$k])): ?><div class="alert alert-success"><?php echo $v; ?></div><?php endif; ?>
    <?php endforeach; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab==='profesores'?'active':''; ?>" href="?tab=profesores">
                👨‍🏫 Instructores (<?php echo count($profesores); ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab==='campos'?'active':''; ?>" href="?tab=campos">
                ⛳ Campos de Golf (<?php echo count($campos); ?>)
            </a>
        </li>
    </ul>

    <?php if ($tab === 'profesores'): ?>
    <!-- ── Tab Instructores ──────────────────────────────── -->
    <div class="row g-4">
        <!-- Formulario crear/editar -->
        <?php
        $edit_id = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
        $edit_p  = null;
        if ($edit_id) {
            $s = $pdo->prepare("SELECT p.*, u.nombre, u.apellidos, u.email FROM profesores p JOIN usuarios u ON p.id_usuario = u.id_usuario WHERE p.id_profesor = ?");
            $s->execute([$edit_id]);
            $edit_p = $s->fetch();
        }
        $todos_campos = $pdo->query("SELECT * FROM campos_golf WHERE activo=1 ORDER BY ciudad")->fetchAll();
        $usuarios_sin_perfil = $pdo->query("SELECT u.id_usuario, u.nombre, u.apellidos, u.email FROM usuarios u WHERE u.id_rol=2 AND u.activo=1 AND u.id_usuario NOT IN (SELECT id_usuario FROM profesores)")->fetchAll();
        ?>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-golf"><?php echo $edit_p ? 'Editar Instructor' : 'Nuevo Instructor'; ?></h5>
                    <form action="recurso_process.php" method="POST">
                        <input type="hidden" name="id_profesor" value="<?php echo $edit_p['id_profesor'] ?? ''; ?>">

                        <?php if (!$edit_p): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Usuario (rol Profesor) <span class="text-danger">*</span></label>
                            <select name="id_usuario" class="form-select" required>
                                <option value="">-- Selecciona usuario --</option>
                                <?php foreach ($usuarios_sin_perfil as $u): ?>
                                    <option value="<?php echo $u['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($u['nombre'].' '.$u['apellidos'].' — '.$u['email']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Solo usuarios con rol Profesor sin perfil aún.</small>
                        </div>
                        <?php else: ?>
                            <p class="text-muted small mb-3">Editando: <strong><?php echo htmlspecialchars($edit_p['nombre'].' '.$edit_p['apellidos']); ?></strong></p>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ubicación <span class="text-danger">*</span></label>
                            <input type="text" name="ubicacion" class="form-control" required maxlength="255"
                                   value="<?php echo htmlspecialchars($edit_p['ubicacion'] ?? ''); ?>"
                                   placeholder="Club, ciudad, a domicilio…">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Campo de Golf vinculado</label>
                            <select name="id_campo" class="form-select">
                                <option value="">-- Ninguno --</option>
                                <?php foreach ($todos_campos as $c): ?>
                                    <option value="<?php echo $c['id_campo']; ?>" <?php echo ($edit_p && $edit_p['id_campo']==$c['id_campo'])?'selected':''; ?>>
                                        <?php echo htmlspecialchars($c['nombre_campo'].' — '.$c['ciudad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Precio/hora (€) <span class="text-danger">*</span></label>
                                <input type="number" name="precio_hora" class="form-control" min="1" max="999" step="0.5" required
                                       value="<?php echo htmlspecialchars($edit_p['precio_hora'] ?? ''); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Capacidad (alumnos)</label>
                                <input type="number" name="capacidad" class="form-control" min="1" max="20"
                                       value="<?php echo htmlspecialchars($edit_p['capacidad'] ?? 1); ?>">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Experiencia (años)</label>
                                <input type="number" name="experiencia_anos" class="form-control" min="0" max="50"
                                       value="<?php echo htmlspecialchars($edit_p['experiencia_anos'] ?? 0); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Puntuación</label>
                                <input type="number" name="puntuacion_simulada" class="form-control" min="1" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($edit_p['puntuacion_simulada'] ?? 5.0); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Titulaciones</label>
                            <textarea name="titulaciones" class="form-control" rows="3"
                                      placeholder="Una por línea…"><?php echo htmlspecialchars($edit_p['titulaciones'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Biografía</label>
                            <textarea name="bio" class="form-control" rows="4" maxlength="1000"><?php echo htmlspecialchars($edit_p['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="clases_online" id="cl_online" value="1"
                                   <?php echo (!empty($edit_p['clases_online']))?'checked':''; ?>>
                            <label class="form-check-label" for="cl_online">Ofrece clases online</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="activo" class="form-select">
                                <option value="1" <?php echo (!$edit_p || $edit_p['activo'])?'selected':''; ?>>Activo</option>
                                <option value="0" <?php echo ($edit_p && !$edit_p['activo'])?'selected':''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-golf"><?php echo $edit_p ? 'Guardar cambios' : 'Crear instructor'; ?></button>
                            <?php if ($edit_p): ?><a href="recursos.php?tab=profesores" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista instructores -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr><th>Instructor</th><th>Ubicación</th><th>€/h</th><th>Cap.</th><th>★</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($profesores as $p): ?>
                            <tr class="<?php echo !$p['activo']?'table-secondary text-muted':''; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($p['nombre'].' '.$p['apellidos']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(mb_substr($p['ubicacion'] ?? ($p['nombre_campo']??'—'),0,40)); ?></td>
                                <td><?php echo number_format($p['precio_hora'],0); ?>€</td>
                                <td><?php echo $p['capacidad'] ?? 1; ?></td>
                                <td><?php echo number_format($p['puntuacion_simulada']??5,1); ?></td>
                                <td><span class="badge bg-<?php echo $p['activo']?'success':'secondary'; ?>"><?php echo $p['activo']?'Activo':'Inactivo'; ?></span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?tab=profesores&editar=<?php echo $p['id_profesor']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                        <form method="POST" action="recurso_process.php" onsubmit="return confirm('¿Eliminar este instructor?');">
                                            <input type="hidden" name="id_profesor" value="<?php echo $p['id_profesor']; ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <button class="btn btn-sm btn-outline-danger">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── Tab Campos de Golf ─────────────────────────────── -->
    <div class="row g-4">
        <?php
        $edit_cid = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
        $edit_c   = null;
        if ($edit_cid) {
            $s = $pdo->prepare("SELECT * FROM campos_golf WHERE id_campo = ?");
            $s->execute([$edit_cid]);
            $edit_c = $s->fetch();
        }
        ?>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3 text-golf"><?php echo $edit_c ? 'Editar Campo' : 'Nuevo Campo de Golf'; ?></h5>
                    <form action="campo_process.php" method="POST">
                        <input type="hidden" name="id_campo" value="<?php echo $edit_c['id_campo'] ?? ''; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_campo" class="form-control" required maxlength="150"
                                   value="<?php echo htmlspecialchars($edit_c['nombre_campo'] ?? ''); ?>"
                                   placeholder="Ej: Club de Golf La Moraleja">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ciudad <span class="text-danger">*</span></label>
                            <input type="text" name="ciudad" class="form-control" required maxlength="100"
                                   value="<?php echo htmlspecialchars($edit_c['ciudad'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Dirección</label>
                            <input type="text" name="direccion" class="form-control" maxlength="255"
                                   value="<?php echo htmlspecialchars($edit_c['direccion'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <select name="activo" class="form-select">
                                <option value="1" <?php echo (!$edit_c||$edit_c['activo'])?'selected':''; ?>>Activo</option>
                                <option value="0" <?php echo ($edit_c&&!$edit_c['activo'])?'selected':''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-golf"><?php echo $edit_c?'Guardar':'Crear campo'; ?></button>
                            <?php if ($edit_c): ?><a href="?tab=campos" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr><th>Nombre</th><th>Ciudad</th><th>Dirección</th><th>Estado</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campos as $c): ?>
                            <tr class="<?php echo !$c['activo']?'table-secondary text-muted':''; ?>">
                                <td><strong><?php echo htmlspecialchars($c['nombre_campo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['ciudad']); ?></td>
                                <td><?php echo htmlspecialchars($c['direccion'] ?? '—'); ?></td>
                                <td><span class="badge bg-<?php echo $c['activo']?'success':'secondary'; ?>"><?php echo $c['activo']?'Activo':'Inactivo'; ?></span></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="?tab=campos&editar=<?php echo $c['id_campo']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                        <form method="POST" action="campo_process.php" onsubmit="return confirm('¿Eliminar este campo?');">
                                            <input type="hidden" name="id_campo" value="<?php echo $c['id_campo']; ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <button class="btn btn-sm btn-outline-danger">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
