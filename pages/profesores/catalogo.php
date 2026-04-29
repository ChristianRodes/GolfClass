<?php
require_once '../../includes/db.php';

$ciudad      = trim($_GET['ciudad'] ?? '');
$nombre_campo = trim($_GET['campo'] ?? '');

$where  = "WHERE p.activo = 1 AND u.activo = 1";
$params = [];

if ($ciudad !== '') {
    $where  .= " AND c.ciudad LIKE ?";
    $params[] = "%{$ciudad}%";
}
if ($nombre_campo !== '') {
    $where  .= " AND c.nombre_campo LIKE ?";
    $params[] = "%{$nombre_campo}%";
}

$stmt = $pdo->prepare("
    SELECT p.id_profesor, p.foto_perfil, p.precio_hora, p.puntuacion_simulada,
           p.bio, p.experiencia_anos,
           u.nombre, u.apellidos,
           c.nombre_campo, c.ciudad
    FROM profesores p
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN campos_golf c ON p.id_campo = c.id_campo
    {$where}
    ORDER BY p.puntuacion_simulada DESC
");
$stmt->execute($params);
$profesores = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="container py-5">

    <!-- Buscador avanzado -->
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Buscar instructor</h5>
            <form method="GET" action="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">¿En qué ciudad buscas?</label>
                        <input type="text" name="ciudad" class="form-control"
                               placeholder="Ej: Madrid, Barcelona…"
                               value="<?php echo htmlspecialchars($ciudad); ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Nombre del Campo de Golf</label>
                        <input type="text" name="campo" class="form-control"
                               placeholder="Ej: La Moraleja, Valderrama…"
                               value="<?php echo htmlspecialchars($nombre_campo); ?>">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-golf">Buscar</button>
                    </div>
                </div>
                <?php if ($ciudad !== '' || $nombre_campo !== ''): ?>
                    <div class="mt-2">
                        <a href="catalogo.php" class="text-muted small">✕ Limpiar filtros</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Resultado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <?php if ($ciudad !== '' || $nombre_campo !== ''): ?>
                Resultados <span class="text-golf">(<?php echo count($profesores); ?>)</span>
            <?php else: ?>
                Todos los <span class="text-golf">Instructores</span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($profesores)): ?>
        <div class="text-center py-5">
            <p class="text-muted fs-5">No hemos encontrado instructores con esos filtros.</p>
            <a href="catalogo.php" class="btn btn-golf mt-2">Ver todos</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($profesores as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 profesor-card">
                        <div class="card-body p-4 d-flex flex-column">

                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($p['foto_perfil'])): ?>
                                    <img src="/GolfClass/uploads/<?php echo htmlspecialchars($p['foto_perfil']); ?>"
                                         class="rounded-circle me-3" style="width:64px;height:64px;object-fit:cover;" alt="Foto">
                                <?php else: ?>
                                    <div class="avatar-initials me-3">
                                        <?php echo strtoupper(mb_substr($p['nombre'], 0, 1) . mb_substr($p['apellidos'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellidos']); ?></h5>
                                    <small class="text-muted">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-geo-alt-fill me-1" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                        <?php echo htmlspecialchars($p['nombre_campo'] . ', ' . $p['ciudad']); ?>
                                    </small>
                                </div>
                            </div>

                            <?php if (!empty($p['experiencia_anos'])): ?>
                                <p class="text-muted small mb-1">
                                    <strong><?php echo (int)$p['experiencia_anos']; ?> años</strong> de experiencia
                                </p>
                            <?php endif; ?>

                            <p class="text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(mb_substr($p['bio'] ?? '', 0, 115)) . (mb_strlen($p['bio'] ?? '') > 115 ? '…' : ''); ?>
                            </p>

                            <div class="d-flex align-items-center justify-content-between mt-3 mb-3">
                                <div>
                                    <?php
                                    $stars = round((float)($p['puntuacion_simulada'] ?? 5));
                                    echo '<span class="text-warning fw-bold">' . str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) . '</span>';
                                    echo '<small class="text-muted ms-1">' . number_format($p['puntuacion_simulada'] ?? 5, 1) . '</small>';
                                    ?>
                                </div>
                                <span class="fs-5 fw-bold text-golf">
                                    <?php echo number_format($p['precio_hora'], 0); ?>€<small class="text-muted fw-normal fs-6">/h</small>
                                </span>
                            </div>

                            <a href="/GolfClass/pages/booking/book_class.php?id=<?php echo $p['id_profesor']; ?>"
                               class="btn btn-golf w-100">
                                Reservar clase
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .profesor-card { transition: transform .2s, box-shadow .2s; }
    .profesor-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    .avatar-initials {
        width: 64px; height: 64px; flex-shrink: 0;
        background: var(--golf-red); color: white;
        font-weight: 700; font-size: 1.2rem;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
