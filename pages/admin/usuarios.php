<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: /index.php"); exit();
}

// Acciones POST inline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion     = $_POST['accion'] ?? '';
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

    if ($id_usuario && $id_usuario != $_SESSION['user_id']) { // no auto-modificarse
        if ($accion === 'toggle_activo') {
            $pdo->prepare("UPDATE usuarios SET activo = 1 - activo WHERE id_usuario = ?")
                ->execute([$id_usuario]);
        } elseif ($accion === 'cambiar_rol') {
            $nuevo_rol = filter_input(INPUT_POST, 'nuevo_rol', FILTER_VALIDATE_INT);
            if (in_array($nuevo_rol, [1, 2, 3])) {
                $pdo->prepare("UPDATE usuarios SET id_rol = ? WHERE id_usuario = ?")
                    ->execute([$nuevo_rol, $id_usuario]);
            }
        } elseif ($accion === 'eliminar') {
            // Baja lógica + anonimización básica
            $pdo->prepare("UPDATE usuarios SET activo = 0, email = CONCAT('deleted_', id_usuario, '@gc.com') WHERE id_usuario = ?")
                ->execute([$id_usuario]);
        }
    }
    header("Location: usuarios.php?ok=1"); exit();
}

// Filtros
$buscar = trim($_GET['q'] ?? '');
$rol_f  = filter_input(INPUT_GET, 'rol', FILTER_VALIDATE_INT) ?: 0;

$where  = "WHERE 1=1";
$params = [];
if ($buscar !== '') {
    $where   .= " AND (u.nombre LIKE ? OR u.apellidos LIKE ? OR u.email LIKE ?)";
    $like     = "%{$buscar}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($rol_f) {
    $where   .= " AND u.id_rol = ?";
    $params[] = $rol_f;
}

$stmt = $pdo->prepare("
    SELECT u.id_usuario, u.nombre, u.apellidos, u.email, u.telefono,
           u.activo, u.fecha_registro, r.nombre_rol, u.id_rol
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id_rol
    {$where}
    ORDER BY u.fecha_registro DESC
");
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY id_rol")->fetchAll();

include '../../includes/header.php';
?>
<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Gestión de <span class="text-golf">Usuarios</span></h2>
            <small class="text-muted"><?php echo count($usuarios); ?> usuarios encontrados</small>
        </div>
        <a href="panel.php" class="btn btn-outline-secondary btn-sm">← Panel</a>
    </div>

    <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success">Acción realizada correctamente.</div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Buscar por nombre o email</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Nombre, apellido o email…" value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Rol</label>
                    <select name="rol" class="form-select form-select-sm">
                        <option value="">Todos los roles</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id_rol']; ?>" <?php echo $rol_f == $r['id_rol'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-golf btn-sm">Filtrar</button>
                </div>
                <div class="col-md-2 d-grid">
                    <a href="usuarios.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="<?php echo !$u['activo'] ? 'table-secondary text-muted' : ''; ?>">
                        <td class="text-muted"><?php echo $u['id_usuario']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u['nombre'] . ' ' . ($u['apellidos'] ?? '')); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['telefono'] ?? '—'); ?></td>
                        <td>
                            <form method="POST" class="d-inline-flex gap-1 align-items-center">
                                <input type="hidden" name="accion" value="cambiar_rol">
                                <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                <select name="nuevo_rol" class="form-select form-select-sm py-0"
                                        style="width:auto;font-size:.8rem;"
                                        onchange="this.form.submit()"
                                        <?php echo ($u['id_usuario'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?php echo $r['id_rol']; ?>"
                                                <?php echo $u['id_rol'] == $r['id_rol'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($r['nombre_rol']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $u['activo'] ? 'success' : 'secondary'; ?>">
                                <?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                        <td>
                            <?php if ($u['id_usuario'] != $_SESSION['user_id']): ?>
                            <div class="d-flex gap-1">
                                <form method="POST">
                                    <input type="hidden" name="accion" value="toggle_activo">
                                    <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                    <button class="btn btn-sm <?php echo $u['activo'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>">
                                        <?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('¿Eliminar este usuario? La acción es irreversible.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?php echo $u['id_usuario']; ?>">
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </div>
                            <?php else: ?>
                                <span class="text-muted small">Tú</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
